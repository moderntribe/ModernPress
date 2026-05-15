<?php declare(strict_types=1);

namespace PatternSync\Admin;

use PatternSync\Connections\Connection_Manager;
use PatternSync\Core\Abstract_Subscriber;
use PatternSync\Log\Sync_Log_Service;
use PatternSync\Patterns\Pattern_Registry_Service;
use PatternSync\Sync\Remote_Client;
use PatternSync\Sync\Sync_Service;

class Admin_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ], 10, 0 );
		add_action( 'admin_post_pattern_sync_save', [ $this, 'handle_save_connection' ], 10, 0 );
		add_action( 'admin_post_pattern_sync_delete', [ $this, 'handle_delete_connection' ], 10, 0 );
		add_action( 'admin_post_pattern_sync_run', [ $this, 'handle_run_sync' ], 10, 0 );
		add_action( 'wp_ajax_pattern_sync_test_connection', [ $this, 'ajax_test_connection' ], 10, 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 10, 1 );
	}

	public function add_menu_page(): void {
		add_management_page(
			__( 'Pattern Sync', 'pattern-sync' ),
			__( 'Pattern Sync', 'pattern-sync' ),
			'manage_options',
			'pattern-sync',
			[ $this, 'dispatch_page' ],
			10
		);
	}

	public function dispatch_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pattern-sync' ) );
		}

		$connection_id = isset( $_GET['connection_id'] ) ? sanitize_text_field( wp_unslash( $_GET['connection_id'] ) ) : '';
		if ( $connection_id !== '' ) {
			$manager    = $this->container->get( Connection_Manager::class );
			$connection = $manager->get_by_id( $connection_id );
			if ( $connection !== null ) {
				$this->render_sync_ui( $connection );

				return;
			}
		}

		$action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		$edit_id = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';

		if ( $action === 'log' ) {
			$this->render_sync_log();

			return;
		}

		if ( $action === 'add' || $edit_id !== '' ) {
			$this->render_connection_form( $edit_id );

			return;
		}

		$this->render_connections_list();
	}

	public function handle_save_connection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'pattern-sync' ) );
		}
		if ( ! isset( $_POST['pattern_sync_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pattern_sync_nonce'] ) ), 'pattern_sync_save_connection' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'pattern-sync' ) );
		}

		$edit_id  = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';
		$name     = isset( $_POST['pattern_sync_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pattern_sync_name'] ) ) : '';
		$url      = isset( $_POST['pattern_sync_url'] ) ? esc_url_raw( wp_unslash( $_POST['pattern_sync_url'] ) ) : '';
		$username = isset( $_POST['pattern_sync_username'] ) ? sanitize_text_field( wp_unslash( $_POST['pattern_sync_username'] ) ) : '';
		$app_pass = isset( $_POST['pattern_sync_app_password'] ) ? wp_unslash( $_POST['pattern_sync_app_password'] ) : '';

		if ( $name === '' || $url === '' || $username === '' ) {
			$this->set_admin_message( __( 'Name, URL and Username are required.', 'pattern-sync' ), 'error' );
			wp_safe_redirect( admin_url( 'tools.php?page=pattern-sync&action=' . ( $edit_id !== '' ? 'add&edit=' . rawurlencode( $edit_id ) : 'add' ) ) );
			exit;
		}

		$manager    = $this->container->get( Connection_Manager::class );
		$connection = [
			'name'         => $name,
			'url'          => $url,
			'username'     => $username,
			'app_password' => is_string( $app_pass ) ? $app_pass : '',
		];
		if ( $edit_id !== '' ) {
			$existing = $manager->get_by_id( $edit_id );
			if ( $existing ) {
				$connection['id'] = $edit_id;
				if ( $connection['app_password'] === '' ) {
					$connection['app_password'] = $existing['app_password'];
				}
			}
		}

		$manager->save( $connection );
		$this->set_admin_message( __( 'Connection saved.', 'pattern-sync' ), 'success' );
		wp_safe_redirect( admin_url( 'tools.php?page=pattern-sync' ) );
		exit;
	}

	public function handle_delete_connection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'pattern-sync' ) );
		}
		$id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
		if ( $id === '' || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'pattern_sync_delete_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'pattern-sync' ) );
		}

		$manager = $this->container->get( Connection_Manager::class );
		$manager->delete( $id );
		$this->set_admin_message( __( 'Connection deleted.', 'pattern-sync' ), 'success' );
		wp_safe_redirect( admin_url( 'tools.php?page=pattern-sync' ) );
		exit;
	}

	public function ajax_test_connection(): void {
		check_ajax_referer( 'pattern_sync_test', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'pattern-sync' ) ] );
		}

		$url      = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$app_pass = isset( $_POST['app_password'] ) ? wp_unslash( $_POST['app_password'] ) : '';
		if ( ! is_string( $app_pass ) ) {
			$app_pass = '';
		}

		if ( $url === '' || $username === '' || $app_pass === '' ) {
			wp_send_json_error( [ 'message' => __( 'URL, username and Application Password are required.', 'pattern-sync' ) ] );
		}

		$manager = $this->container->get( Connection_Manager::class );
		$ok      = $manager->test( $url, $username, $app_pass );
		if ( $ok ) {
			wp_send_json_success( [ 'message' => __( 'Connection successful.', 'pattern-sync' ) ] );
		}
		wp_send_json_error( [ 'message' => __( 'Connection failed. Check URL and Application Password.', 'pattern-sync' ) ] );
	}

	public function enqueue_scripts( string $hook ): void {
		if ( $hook !== 'tools_page_pattern-sync' ) {
			return;
		}
		wp_add_inline_script( 'jquery', $this->get_test_connection_script(), 'after' );
	}

	public function handle_run_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'pattern-sync' ) );
		}
		if ( ! isset( $_POST['pattern_sync_run_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pattern_sync_run_nonce'] ) ), 'pattern_sync_run' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'pattern-sync' ) );
		}

		$connection_id = isset( $_POST['connection_id'] ) ? sanitize_text_field( wp_unslash( $_POST['connection_id'] ) ) : '';
		$direction     = isset( $_POST['direction'] ) ? sanitize_text_field( wp_unslash( $_POST['direction'] ) ) : '';
		$pull_patterns = isset( $_POST['pull_patterns'] ) && is_array( $_POST['pull_patterns'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['pull_patterns'] ) ) : [];
		$push_patterns = isset( $_POST['push_patterns'] ) && is_array( $_POST['push_patterns'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['push_patterns'] ) ) : [];

		if ( $connection_id === '' ) {
			$this->set_admin_message( __( 'Missing connection.', 'pattern-sync' ), 'error' );
			wp_safe_redirect( admin_url( 'tools.php?page=pattern-sync' ) );
			exit;
		}

		$sync_service = $this->container->get( Sync_Service::class );
		$results      = [];
		if ( $direction === 'pull' && $pull_patterns !== [] ) {
			$results = $sync_service->pull( $connection_id, $pull_patterns );
		} elseif ( $direction === 'push' && $push_patterns !== [] ) {
			$results = $sync_service->push( $connection_id, $push_patterns );
		} else {
			$this->set_admin_message( __( 'Select at least one pattern and use Pull or Push.', 'pattern-sync' ), 'warning' );
			wp_safe_redirect( admin_url( 'tools.php?page=pattern-sync&connection_id=' . rawurlencode( $connection_id ) ) );
			exit;
		}

		$log_service = $this->container->get( Sync_Log_Service::class );
		$log_service->add_entry( $connection_id, $direction, (int) get_current_user_id(), $results );

		$success_count = count( array_filter( $results, static fn ( $r ) => $r['success'] ) );
		$fail_count    = count( $results ) - $success_count;
		$msg           = $direction === 'pull'
			? sprintf( __( 'Pull: %1$d succeeded, %2$d failed.', 'pattern-sync' ), $success_count, $fail_count )
			: sprintf( __( 'Push: %1$d succeeded, %2$d failed.', 'pattern-sync' ), $success_count, $fail_count );
		$this->set_admin_message( $msg, $fail_count > 0 ? 'warning' : 'success' );
		wp_safe_redirect( admin_url( 'tools.php?page=pattern-sync&connection_id=' . rawurlencode( $connection_id ) ) );
		exit;
	}

	private function render_connections_list(): void {
		// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable -- Variables used in included view.
		$manager           = $this->container->get( Connection_Manager::class );
		$connections       = $manager->get_all();
		$messages          = $this->get_admin_messages();
		$add_form_url      = admin_url( 'tools.php?page=pattern-sync&action=add' );
		$sync_url_template = admin_url( 'tools.php?page=pattern-sync&connection_id=%s' );
		// phpcs:enable
		include __DIR__ . '/Views/connections.php';
	}

	private function render_connection_form( string $edit_id ): void {
		// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable -- Variables used in included view.
		$manager      = $this->container->get( Connection_Manager::class );
		$connection   = null;
		$submit_label = __( 'Add connection', 'pattern-sync' );
		if ( $edit_id !== '' ) {
			$full         = $manager->get_by_id( $edit_id );
			$connection   = $full ? [
				'name'     => $full['name'],
				'url'      => $full['url'],
				'username' => $full['username'],
			] : null;
			$submit_label = __( 'Update connection', 'pattern-sync' );
		}

		$form_action = admin_url( 'admin-post.php' );
		$nonce_field = wp_nonce_field( 'pattern_sync_save_connection', 'pattern_sync_nonce', true, false );
		$back_url    = admin_url( 'tools.php?page=pattern-sync' );
		if ( $edit_id !== '' ) {
			$form_action .= '?action=pattern_sync_save&edit=' . rawurlencode( $edit_id );
		} else {
			$form_action .= '?action=pattern_sync_save';
		}
		// phpcs:enable
		include __DIR__ . '/Views/connection-form.php';
	}

	private function get_test_connection_script(): string {
		return <<<'JS'
(function($){
	$(function(){
		$(document).on('click', '#pattern-sync-test-connection', function(){
			var $btn = $(this), $result = $('#pattern-sync-test-result');
			var url = $('#pattern_sync_url').val(), username = $('#pattern_sync_username').val(), appPassword = $('#pattern_sync_app_password').val();
			if (!url || !username || !appPassword) {
				$result.removeClass('notice-success notice-info').addClass('notice notice-error').show().find('p').text('Please fill URL, Username and Application Password.');
				return;
			}
			$btn.prop('disabled', true);
			$result.removeClass('notice-success notice-error').addClass('notice notice-info').show().find('p').text('Testing connection…');
			$.post(ajaxurl, {
				action: 'pattern_sync_test_connection',
				nonce: $('#pattern_sync_test_nonce').val(),
				url: url,
				username: username,
				app_password: appPassword
			}).done(function(r){
				var msg = (r.data && r.data.message) ? r.data.message : (r.success ? 'Connection successful.' : 'Connection failed.');
				if (r.success) {
					$result.removeClass('notice-error notice-info').addClass('notice notice-success').show().find('p').text(msg);
				} else {
					$result.removeClass('notice-success notice-info').addClass('notice notice-error').show().find('p').text(msg);
				}
			}).fail(function(xhr){
				var msg = 'Request failed.';
				if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					msg = xhr.responseJSON.data.message;
				} else if (xhr && xhr.statusText) {
					msg = 'Request failed: ' + xhr.statusText;
				}
				$result.removeClass('notice-success notice-info').addClass('notice notice-error').show().find('p').text(msg);
			}).always(function(){ $btn.prop('disabled', false); });
		});
	});
})(jQuery);
JS;
	}

	private function get_admin_messages(): array {
		$messages = get_transient( 'pattern_sync_admin_messages' );
		delete_transient( 'pattern_sync_admin_messages' );

		return is_array( $messages ) ? $messages : [];
	}

	private function set_admin_message( string $text, string $type = 'info' ): void {
		$messages   = get_transient( 'pattern_sync_admin_messages' );
		$messages   = is_array( $messages ) ? $messages : [];
		$messages[] = [ 'type' => $type, 'text' => $text ];
		set_transient( 'pattern_sync_admin_messages', $messages, 60 );
	}

	private function render_sync_ui( array $connection ): void {
		$remote_client    = $this->container->get( Remote_Client::class );
		$pattern_registry = $this->container->get( Pattern_Registry_Service::class );

		$remote_list = $remote_client->get_patterns( $connection['url'], $connection['username'], $connection['app_password'] );
		$local_list  = $pattern_registry->list_all();

		// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable -- Variables used in included view.
		$remote_by_category = $this->group_patterns_by_category( $remote_list );
		$local_by_category  = $this->group_patterns_by_category( $local_list );

		$back_url    = admin_url( 'tools.php?page=pattern-sync' );
		$refresh_url = admin_url( 'tools.php?page=pattern-sync&connection_id=' . rawurlencode( $connection['id'] ) );
		$form_action = admin_url( 'admin-post.php?action=pattern_sync_run' );
		$nonce       = wp_nonce_field( 'pattern_sync_run', 'pattern_sync_run_nonce', true, false );
		$messages    = $this->get_admin_messages();
		// phpcs:enable
		include __DIR__ . '/Views/sync.php';
	}

	/**
	 * @param array<int, array{name: string, title: string, categories?: array, source?: string, syncable?: bool, origin?: string}> $patterns
	 *
	 * @return array<string, array<int, array{name: string, title: string, source: string, syncable: bool, origin: string}>>
	 */
	private function group_patterns_by_category( array $patterns ): array {
		$by_cat = [];
		foreach ( $patterns as $p ) {
			$name     = $p['name'] ?? '';
			$title    = $p['title'] ?? $name;
			$source   = $p['source'] ?? '';
			$syncable = $p['syncable'] ?? false;
			$origin   = $p['origin'] ?? 'code';
			$cats     = $p['categories'] ?? [];
			$cat_key  = is_array( $cats ) && $cats !== [] ? (string) reset( $cats ) : 'uncategorized';
			if ( ! isset( $by_cat[ $cat_key ] ) ) {
				$by_cat[ $cat_key ] = [];
			}
			$by_cat[ $cat_key ][] = [ 'name' => $name, 'title' => $title, 'source' => $source, 'syncable' => $syncable, 'origin' => $origin ];
		}

		return $by_cat;
	}

	private function render_sync_log(): void {
		// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable -- Variables used in included view.
		$log_service = $this->container->get( Sync_Log_Service::class );
		$entries     = $log_service->get_recent( 100 );
		$manager     = $this->container->get( Connection_Manager::class );
		$connections = [];
		foreach ( $manager->get_all() as $c ) {
			$connections[ $c['id'] ] = $c['name'];
		}
		$back_url = admin_url( 'tools.php?page=pattern-sync' );
		// phpcs:enable
		include __DIR__ . '/Views/sync-log.php';
	}

}

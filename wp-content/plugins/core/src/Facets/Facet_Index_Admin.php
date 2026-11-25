<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

use Tribe\Plugin\Settings\Facets_Settings;

/**
 * Index status, notices and the manual rebuild on the Facets settings screen.
 */
class Facet_Index_Admin {

	/**
	 * Doubles as the nonce action.
	 */
	public const string REBUILD_ACTION = 'tribe_facets_rebuild_index';

	public function __construct(
		private Facet_Index_Rebuilder $rebuilder,
		private Facet_Index_Table $table,
	) {
	}

	public function schedule_rebuild_after_settings_save( mixed $post_id ): void {
		if ( 'options' !== $post_id || ! $this->is_facets_screen() ) {
			return;
		}

		$this->rebuilder->schedule();
	}

	public function handle_rebuild_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to rebuild the facet index.', 'tribe' ), 403 );
		}

		check_admin_referer( self::REBUILD_ACTION );

		// First batch runs now; a large site continues from WP-Cron.
		$this->rebuilder->start();
		$this->rebuilder->process();

		wp_safe_redirect( add_query_arg( 'page', Facets_Settings::PAGE_SLUG, admin_url( 'admin.php' ) ) );

		exit;
	}

	public function render_notices(): void {
		if ( ! $this->is_facets_screen() ) {
			return;
		}

		$is_built = $this->table->is_built();
		$status   = $this->get_status_message();

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::REBUILD_ACTION ),
			self::REBUILD_ACTION
		);

		printf(
			'<div class="notice %1$s"><p><strong>%2$s</strong> %3$s</p><p><a class="button button-secondary" href="%4$s">%5$s</a></p></div>',
			$is_built || null !== $this->table->get_progress() ? 'notice-info' : 'notice-warning',
			esc_html__( 'Facet index:', 'tribe' ),
			esc_html( $status ),
			esc_url( $url ),
			esc_html__( 'Rebuild Index', 'tribe' )
		);
	}

	private function get_status_message(): string {
		$progress = $this->table->get_progress();

		if ( null !== $progress ) {
			return sprintf(
				/* translators: 1: posts indexed so far, 2: total posts. */
				__( 'Rebuilding: %1$s of %2$s posts indexed. This continues in the background; directories use slower taxonomy queries until it finishes.', 'tribe' ),
				number_format_i18n( $progress['indexed'] ),
				number_format_i18n( $progress['total'] )
			);
		}

		if ( $this->table->is_built() ) {
			return __( 'Built. Rebuild after changing facet definitions or importing content.', 'tribe' );
		}

		return __( 'Not built yet. Directories fall back to slower taxonomy queries until you rebuild.', 'tribe' );
	}

	private function is_facets_screen(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return Facets_Settings::PAGE_SLUG === sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );
	}

}

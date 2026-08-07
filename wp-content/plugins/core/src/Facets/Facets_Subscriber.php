<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

use Tribe\Plugin\Core\Abstract_Subscriber;
use Tribe\Plugin\Settings\Facets_Settings;

class Facets_Subscriber extends Abstract_Subscriber {

	public const string REBUILD_ACTION = 'tribe_facets_rebuild_index';
	public const string REBUILD_NONCE  = 'tribe_facets_rebuild_index';

	public function register(): void {
		add_filter( 'block_editor_settings_all', function ( array $settings ): array {
			$settings['tribeFacets'] = $this->container->get( Facet_Registry::class )->get_editor_catalog();

			return $settings;
		}, 10, 1 );

		add_action( 'rest_api_init', function (): void {
			$this->container->get( Results_Endpoint::class )->register_route();
		}, 10, 0 );

		add_action( 'admin_init', function (): void {
			$this->container->get( Facet_Index::class )->maybe_create_table();
		}, 10, 0 );

		$this->register_index_hooks();
		$this->register_rebuild_hooks();
	}

	private function register_index_hooks(): void {
		add_action( 'save_post', function ( $post_id, $post, $update ): void {
			if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
				return;
			}

			$this->container->get( Facet_Index::class )->index_post( (int) $post_id );
		}, 20, 3 );

		add_action( 'deleted_post', function ( $post_id ): void {
			$this->container->get( Facet_Index::class )->delete_post( (int) $post_id );
		}, 10, 1 );

		add_action( 'set_object_terms', function ( $object_id ): void {
			$this->container->get( Facet_Index::class )->index_post( (int) $object_id );
		}, 20, 1 );

		// Term slug changes invalidate stored slugs for every attached post.
		add_action( 'edited_term', function ( $term_id, $tt_id, $taxonomy ): void {
			$this->container->get( Facet_Index::class )->reindex_term( (int) $term_id, (string) $taxonomy );
		}, 20, 3 );

		add_action( 'pre_delete_term', function ( $term_id, $taxonomy ): void {
			$this->container->get( Facet_Index::class )->reindex_term( (int) $term_id, (string) $taxonomy );
		}, 10, 2 );
	}

	private function register_rebuild_hooks(): void {
		add_action( Facet_Index::REBUILD_HOOK, function (): void {
			$this->container->get( Facet_Index::class )->rebuild();
		}, 10, 0 );

		// Facet definitions changed, so the index no longer matches the config.
		add_action( 'acf/save_post', function ( $post_id ): void {
			if ( 'options' !== $post_id ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( Facets_Settings::PAGE_SLUG !== ( $_GET['page'] ?? '' ) ) {
				return;
			}

			$this->container->get( Facet_Index::class )->schedule_rebuild();
		}, 20, 1 );

		add_action( 'admin_post_' . self::REBUILD_ACTION, function (): void {
			$this->handle_rebuild_request();
		}, 10, 0 );

		add_action( 'admin_notices', function (): void {
			$this->render_rebuild_notice();
			$this->render_rebuild_button();
		}, 10, 0 );
	}

	/**
	 * Index status and rebuild button, shown on the Facets settings screen.
	 */
	private function render_rebuild_button(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( Facets_Settings::PAGE_SLUG !== ( $_GET['page'] ?? '' ) ) {
			return;
		}

		$is_built = $this->container->get( Facet_Index::class )->is_built();

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::REBUILD_ACTION ),
			self::REBUILD_NONCE
		);

		printf(
			'<div class="notice %1$s"><p><strong>%2$s</strong> %3$s</p><p><a class="button button-secondary" href="%4$s">%5$s</a></p></div>',
			$is_built ? 'notice-info' : 'notice-warning',
			esc_html__( 'Facet index:', 'tribe' ),
			esc_html(
				$is_built
					? __( 'Built. Rebuild after changing facet definitions or importing content.', 'tribe' )
					: __( 'Not built yet. Directories fall back to slower taxonomy queries until you rebuild.', 'tribe' )
			),
			esc_url( $url ),
			esc_html__( 'Rebuild Index', 'tribe' )
		);
	}

	private function handle_rebuild_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to rebuild the facet index.', 'tribe' ), 403 );
		}

		check_admin_referer( self::REBUILD_NONCE );

		$indexed = $this->container->get( Facet_Index::class )->rebuild();

		wp_safe_redirect( add_query_arg( [
			'page'                  => Facets_Settings::PAGE_SLUG,
			'tribe-facets-indexed'  => $indexed,
		], admin_url( 'admin.php' ) ) );

		exit;
	}

	private function render_rebuild_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['tribe-facets-indexed'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$indexed = absint( $_GET['tribe-facets-indexed'] );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( sprintf(
				/* translators: %d: number of posts indexed. */
				_n( 'Facet index rebuilt: %d post indexed.', 'Facet index rebuilt: %d posts indexed.', $indexed, 'tribe' ),
				$indexed
			) )
		);
	}

}

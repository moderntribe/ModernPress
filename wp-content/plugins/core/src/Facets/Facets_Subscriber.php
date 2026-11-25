<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

use Tribe\Plugin\Core\Abstract_Subscriber;

class Facets_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		add_filter( 'block_editor_settings_all', function ( array $settings ): array {
			$settings['tribeFacets'] = $this->container->get( Facet_Registry::class )->get_editor_catalog();

			return $settings;
		}, 10, 1 );

		add_action( 'rest_api_init', function (): void {
			$this->container->get( Results_Endpoint::class )->register_route();
		}, 10, 0 );

		add_action( 'admin_init', function (): void {
			$this->container->get( Facet_Index_Table::class )->maybe_create();
		}, 10, 0 );

		$this->register_index_hooks();
		$this->register_admin_hooks();
	}

	private function register_index_hooks(): void {
		add_action( 'save_post', function ( $post_id ): void {
			$this->container->get( Facet_Index_Sync::class )->queue_post( (int) $post_id );
		}, 20, 1 );

		add_action( 'set_object_terms', function ( $object_id, $terms, $tt_ids, $taxonomy ): void {
			$this->container->get( Facet_Index_Sync::class )->queue_post_for_taxonomy( (int) $object_id, (string) $taxonomy );
		}, 20, 4 );

		add_action( 'deleted_post', function ( $post_id ): void {
			$this->container->get( Facet_Index_Sync::class )->delete_post( (int) $post_id );
		}, 10, 1 );

		// Term slug changes invalidate stored slugs for every attached post.
		add_action( 'edited_term', function ( $term_id, $tt_id, $taxonomy ): void {
			$this->container->get( Facet_Index_Sync::class )->reindex_term( (int) $term_id, (string) $taxonomy );
		}, 20, 3 );

		add_action( 'delete_term', function ( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ): void {
			$this->container->get( Facet_Index_Sync::class )->reindex_posts( (string) $taxonomy, (array) $object_ids );
		}, 10, 5 );

		add_action( 'shutdown', function (): void {
			$this->container->get( Facet_Index_Sync::class )->flush_queue();
		}, 10, 0 );

		add_action( Facet_Index_Rebuilder::HOOK, function (): void {
			$this->container->get( Facet_Index_Rebuilder::class )->run();
		}, 10, 0 );
	}

	private function register_admin_hooks(): void {
		// Facet definitions changed, so the index no longer matches the config.
		add_action( 'acf/save_post', function ( $post_id ): void {
			$this->container->get( Facet_Index_Admin::class )->schedule_rebuild_after_settings_save( $post_id );
		}, 20, 1 );

		add_action( 'admin_post_' . Facet_Index_Admin::REBUILD_ACTION, function (): void {
			$this->container->get( Facet_Index_Admin::class )->handle_rebuild_request();
		}, 10, 0 );

		add_action( 'admin_notices', function (): void {
			$this->container->get( Facet_Index_Admin::class )->render_notices();
		}, 10, 0 );
	}

}

<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Announcements\Rules;

use Tribe\Plugin\Object_Meta\Post_Types\Announcement_Meta;

class Page_Visibility_Rule implements Rule_Interface {

	public function passes( \WP_Post $announcement, array $context ): bool {
		$display_type = get_field( Announcement_Meta::FIELD_RULES_DISPLAY_TYPE, $announcement->ID );

		// If set to show everywhere, always pass.
		if ( $display_type === Announcement_Meta::OPTION_EVERY_PAGE ) {
			return true;
		}

		$current_post_id = $context['current_post_id'] ?? get_queried_object_id();
		$is_front_page   = $context['is_front_page'] ?? is_front_page();

		// Handle front page rule.
		$apply_to_front_page = get_field( Announcement_Meta::FIELD_RULES_APPLY_TO_FRONT_PAGE, $announcement->ID );
		if ( $is_front_page && $apply_to_front_page ) {
			return $this->check_front_page_rule( $display_type );
		}

		// Handle taxonomy archives.
		if ( $this->is_taxonomy_archive( $context ) ) {
			return $this->check_taxonomy_archive_rule( $announcement, $display_type, $context );
		}

		// Handle post type archives.
		if ( $this->is_post_type_archive( $context ) ) {
			return $this->check_post_type_archive_rule( $announcement, $display_type, $context );
		}

		// Handle specific posts/pages.
		return $this->check_specific_page_rule( $announcement, $display_type, $current_post_id );
	}

	public function get_name(): string {
		return 'page_visibility';
	}

	/**
	 * Check front page rule
	 */
	private function check_front_page_rule( string $display_type ): bool {
		// If including pages, front page should be included
		// If excluding pages, front page should be excluded
		return $display_type === Announcement_Meta::OPTION_INCLUDE;
	}

	/**
	 * Check if current page is a taxonomy archive
	 */
	private function is_taxonomy_archive( array $context ): bool {
		return $context['is_category'] || $context['is_tag'] || $context['is_tax'];
	}

	/**
	 * Check taxonomy archive rule
	 */
	private function check_taxonomy_archive_rule( \WP_Post $announcement, string $display_type, array $context ): bool {
		$allowed_taxonomies = get_field( Announcement_Meta::FIELD_TAXONOMY_ARCHIVES, $announcement->ID );

		if ( empty( $allowed_taxonomies ) ) {
			// No taxonomy restrictions - follow the main rule
			return ! ( $display_type === Announcement_Meta::OPTION_INCLUDE );
		}

		$current_taxonomy = '';

		if ( $context['is_category'] ?? is_category() ) {
			$current_taxonomy = 'category';
		} elseif ( $context['is_tag'] ?? is_tag() ) {
			$current_taxonomy = 'post_tag';
		} elseif ( $context['is_tax'] ?? is_tax() ) {
			$queried_object = get_queried_object();
			if ( $queried_object && isset( $queried_object->taxonomy ) ) {
				$current_taxonomy = $queried_object->taxonomy;
			}
		}

		$is_allowed_taxonomy = in_array( $current_taxonomy, $allowed_taxonomies, true );

		// For include: show if taxonomy is in allowed list
		// For exclude: show if taxonomy is NOT in allowed list
		return $display_type === Announcement_Meta::OPTION_INCLUDE ? $is_allowed_taxonomy : ! $is_allowed_taxonomy;
	}

	/**
	 * Check if current page is a post type archive
	 */
	private function is_post_type_archive( array $context ): bool {
		return $context['is_archive'] ?? is_post_type_archive();
	}

	/**
	 * Check post type archive rule
	 */
	private function check_post_type_archive_rule( \WP_Post $announcement, string $display_type, array $context ): bool {
		$allowed_post_types = get_field( Announcement_Meta::FIELD_POST_TYPE_ARCHIVES, $announcement->ID );

		if ( empty( $allowed_post_types ) ) {
			// No post type restrictions - follow the main rule
			return ! ( $display_type === Announcement_Meta::OPTION_INCLUDE );
		}

		$current_post_type = get_post_type();
		if ( ! $current_post_type ) {
			$queried_object = get_queried_object();
			if ( $queried_object && isset( $queried_object->name ) ) {
				$current_post_type = $queried_object->name;
			}
		}

		$is_allowed_post_type = in_array( $current_post_type, $allowed_post_types, true );

		// For include: show if post type is in allowed list
		// For exclude: show if post type is NOT in allowed list
		return $display_type === Announcement_Meta::OPTION_INCLUDE ? $is_allowed_post_type : ! $is_allowed_post_type;
	}

	/**
	 * Check specific page rule
	 */
	private function check_specific_page_rule( \WP_Post $announcement, string $display_type, int $current_post_id ): bool {
		if ( $display_type === Announcement_Meta::OPTION_INCLUDE ) {
			return $this->check_include_pages_rule( $announcement, $current_post_id );
		}

		if ( $display_type === Announcement_Meta::OPTION_EXCLUDE ) {
			return $this->check_exclude_pages_rule( $announcement, $current_post_id );
		}

		return true;
	}

	/**
	 * Check include pages rule
	 */
	private function check_include_pages_rule( \WP_Post $announcement, int $current_post_id ): bool {
		$included_pages = get_field( Announcement_Meta::FIELD_RULES_INCLUDE_PAGES, $announcement->ID );

		if ( empty( $included_pages ) ) {
			return false; // No pages specified for inclusion
		}

		// Check if current post is in the included list
		foreach ( $included_pages as $page ) {
			if ( is_object( $page ) && isset( $page->ID ) && $page->ID === $current_post_id ) {
				return true;
			}

			if ( is_numeric( $page ) && (int) $page === $current_post_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check exclude pages rule
	 */
	private function check_exclude_pages_rule( \WP_Post $announcement, int $current_post_id ): bool {
		$excluded_pages = get_field( Announcement_Meta::FIELD_RULES_EXCLUDE_PAGES, $announcement->ID );

		if ( empty( $excluded_pages ) ) {
			return true; // No pages excluded, so show everywhere
		}

		// Check if current post is in the excluded list
		foreach ( $excluded_pages as $page ) {
			if ( is_object( $page ) && isset( $page->ID ) && $page->ID === $current_post_id ) {
				return false; // This page is excluded
			}

			if ( is_numeric( $page ) && (int) $page === $current_post_id ) {
				return false; // This page is excluded
			}
		}

		return true; // Not in excluded list, so show
	}

}

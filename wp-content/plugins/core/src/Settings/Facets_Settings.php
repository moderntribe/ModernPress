<?php declare(strict_types=1);

namespace Tribe\Plugin\Settings;

use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\TrueFalse;
use Tribe\Plugin\Facets\Facet_Types;
use WP_Post_Type;
use WP_Taxonomy;

class Facets_Settings extends Settings_Sub_Page {

	public const string PAGE_SLUG = 'tribe-facets';

	public const string FACETS           = 'tribe_facets';
	public const string FACET_LABEL      = 'label';
	public const string FACET_SLUG       = 'slug';
	public const string FACET_TAXONOMY   = 'taxonomy';
	public const string FACET_POST_TYPES = 'post_types';

	/**
	 * Legacy storage key retained so existing top-bar choices are preserved.
	 */
	public const string FACET_TYPE         = 'type';
	public const string FACET_SIDEBAR_TYPE = 'sidebar_type';
	public const string FACET_MOBILE_TYPE  = 'mobile_type';

	public const string FACET_SIDEBAR_ACCORDION_OPEN = 'sidebar_accordion_open';
	public const string FACET_MOBILE_ACCORDION_OPEN  = 'mobile_accordion_open';

	public function get_title(): string {
		return esc_html__( 'Facets', 'tribe' );
	}

	public function get_parent_slug(): string {
		return Tribe_Settings::PAGE_SLUG;
	}

	public function get_fields(): array {
		$type_choices = Facet_Types::choices();

		return [
			Repeater::make( esc_html__( 'Facets', 'tribe' ), self::FACETS )
				->helperText( esc_html__( 'Define taxonomy facets available to Faceted Directory blocks. Each facet can target one or more post types.', 'tribe' ) )
				->button( esc_html__( 'Add Facet', 'tribe' ) )
				->layout( 'block' )
				->collapsed( self::FACET_LABEL )
				->fields( [
					Text::make( esc_html__( 'Label', 'tribe' ), self::FACET_LABEL )
						->required()
						->column( 50 ),
					Text::make( esc_html__( 'Slug', 'tribe' ), self::FACET_SLUG )
						->required()
						->helperText( esc_html__( 'Unique key used in URLs and markup. Letters, numbers, and hyphens only.', 'tribe' ) )
						->column( 50 ),
					Select::make( esc_html__( 'Taxonomy', 'tribe' ), self::FACET_TAXONOMY )
						->choices( $this->get_taxonomy_choices() )
						->stylized()
						->required()
						->format( 'value' )
						->column( 50 ),
					Select::make( esc_html__( 'Post Types', 'tribe' ), self::FACET_POST_TYPES )
						->choices( $this->get_post_type_choices() )
						->stylized()
						->multiple()
						->nullable()
						->required()
						->format( 'value' )
						->helperText( esc_html__( 'Facet appears in the filter bar only when the directory includes at least one of these post types.', 'tribe' ) )
						->column( 50 ),
					Select::make( esc_html__( 'Top Filter Bar Type', 'tribe' ), self::FACET_TYPE )
						->choices( $type_choices )
						->stylized()
						->required()
						->default( Facet_Types::CHECKBOXES )
						->format( 'value' )
						->helperText( esc_html__( 'Used when the filter bar appears above the directory.', 'tribe' ) )
						->column( 33 ),
					Select::make( esc_html__( 'Sidebar Filter Bar Type', 'tribe' ), self::FACET_SIDEBAR_TYPE )
						->choices( $type_choices )
						->stylized()
						->required()
						->default( Facet_Types::CHECKBOXES )
						->format( 'value' )
						->helperText( esc_html__( 'Used in the desktop sidebar.', 'tribe' ) )
						->column( 33 ),
					Select::make( esc_html__( 'Mobile Flyout Type', 'tribe' ), self::FACET_MOBILE_TYPE )
						->choices( $type_choices )
						->stylized()
						->required()
						->default( Facet_Types::CHECKBOXES )
						->format( 'value' )
						->helperText( esc_html__( 'Used inside the mobile filter flyout.', 'tribe' ) )
						->column( 33 ),
					TrueFalse::make( esc_html__( 'Sidebar Accordion Starts Open', 'tribe' ), self::FACET_SIDEBAR_ACCORDION_OPEN )
						->stylized( esc_html__( 'Open', 'tribe' ), esc_html__( 'Closed', 'tribe' ) )
						->default( false )
						->helperText( esc_html__( 'Expand this facet by default in the desktop sidebar.', 'tribe' ) )
						->column( 50 ),
					TrueFalse::make( esc_html__( 'Mobile Accordion Starts Open', 'tribe' ), self::FACET_MOBILE_ACCORDION_OPEN )
						->stylized( esc_html__( 'Open', 'tribe' ), esc_html__( 'Closed', 'tribe' ) )
						->default( false )
						->helperText( esc_html__( 'Expand this facet by default in the mobile filter flyout.', 'tribe' ) )
						->column( 50 ),
				] ),
		];
	}

	/**
	 * @return array<string, string>
	 */
	private function get_taxonomy_choices(): array {
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

		return array_reduce(
			$taxonomies,
			static function ( array $choices, WP_Taxonomy $taxonomy ): array {
				$choices[ $taxonomy->name ] = $taxonomy->labels->singular_name ?: $taxonomy->label;

				return $choices;
			},
			[]
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function get_post_type_choices(): array {
		$post_types = get_post_types(
			[
				'public' => true,
			],
			'objects'
		);

		unset( $post_types['attachment'] );

		return array_reduce(
			$post_types,
			static function ( array $choices, WP_Post_Type $post_type ): array {
				$choices[ $post_type->name ] = $post_type->labels->singular_name ?: $post_type->label;

				return $choices;
			},
			[]
		);
	}

}

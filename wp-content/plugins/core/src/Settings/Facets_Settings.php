<?php declare(strict_types=1);

namespace Tribe\Plugin\Settings;

use Extended\ACF\ConditionalLogic;
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
	 * Admin label is "Display Type".
	 */
	public const string FACET_TYPE             = 'type';
	public const string FACET_CUSTOMIZE_LAYOUT = 'customize_layout_types';
	public const string FACET_SIDEBAR_TYPE     = 'sidebar_type';
	public const string FACET_MOBILE_TYPE      = 'mobile_type';

	public const string FACET_SIDEBAR_ACCORDION_OPEN = 'sidebar_accordion_open';
	public const string FACET_MOBILE_ACCORDION_OPEN  = 'mobile_accordion_open';

	public function get_title(): string {
		return esc_html__( 'Facets', 'tribe' );
	}

	public function get_parent_slug(): string {
		return Tribe_Settings::PAGE_SLUG;
	}

	public function get_fields(): array {
		$type_choices    = Facet_Types::choices();
		$sidebar_choices = [
			'' => esc_html__( 'Same as Display Type', 'tribe' ),
		] + $type_choices;
		$mobile_choices  = [
			'' => esc_html__( 'Same as Sidebar Type', 'tribe' ),
		] + $type_choices;

		return [
			Repeater::make( esc_html__( 'Facets', 'tribe' ), self::FACETS )
				->helperText( esc_html__( 'Define taxonomy facets available to Faceted Directory blocks. Search and Clear all are built-in — add them on the Filter Bar block. Each facet can target one or more post types.', 'tribe' ) )
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
					Select::make( esc_html__( 'Display Type', 'tribe' ), self::FACET_TYPE )
						->choices( $type_choices )
						->stylized()
						->required()
						->default( Facet_Types::FANCY_DROPDOWN )
						->format( 'value' )
						->helperText( esc_html__( 'Default control used across filter bar layouts. Fancy Dropdown works well above the directory; Checkboxes work well in the sidebar.', 'tribe' ) )
						->column( 50 ),
					TrueFalse::make( esc_html__( 'Customize by Layout', 'tribe' ), self::FACET_CUSTOMIZE_LAYOUT )
						->stylized( esc_html__( 'Yes', 'tribe' ), esc_html__( 'No', 'tribe' ) )
						->default( true )
						->helperText( esc_html__( 'When off, sidebar and mobile use the Display Type. Turn on to set different controls per layout.', 'tribe' ) )
						->column( 50 ),
					Select::make( esc_html__( 'Sidebar Type', 'tribe' ), self::FACET_SIDEBAR_TYPE )
						->choices( $sidebar_choices )
						->stylized()
						->nullable()
						->default( Facet_Types::CHECKBOXES )
						->format( 'value' )
						->helperText( esc_html__( 'Used in the desktop sidebar when the Faceted Directory uses the sidebar filter bar.', 'tribe' ) )
						->column( 50 )
						->conditionalLogic( [
							ConditionalLogic::where( self::FACET_CUSTOMIZE_LAYOUT, '==', 1 ),
						] ),
					Select::make( esc_html__( 'Mobile Flyout Type', 'tribe' ), self::FACET_MOBILE_TYPE )
						->choices( $mobile_choices )
						->stylized()
						->nullable()
						->default( '' )
						->format( 'value' )
						->helperText( esc_html__( 'Used inside the mobile filter flyout. Only applies when the Faceted Directory uses the sidebar filter bar — top-position bars never use this.', 'tribe' ) )
						->column( 50 )
						->conditionalLogic( [
							ConditionalLogic::where( self::FACET_CUSTOMIZE_LAYOUT, '==', 1 ),
						] ),
					TrueFalse::make( esc_html__( 'Sidebar Starts Expanded', 'tribe' ), self::FACET_SIDEBAR_ACCORDION_OPEN )
						->stylized( esc_html__( 'Open', 'tribe' ), esc_html__( 'Closed', 'tribe' ) )
						->default( false )
						->helperText( esc_html__( 'Expand this facet by default in the desktop sidebar.', 'tribe' ) )
						->column( 50 )
						->conditionalLogic( $this->accordion_open_conditionals( self::FACET_SIDEBAR_TYPE ) ),
					TrueFalse::make( esc_html__( 'Mobile Starts Expanded', 'tribe' ), self::FACET_MOBILE_ACCORDION_OPEN )
						->stylized( esc_html__( 'Open', 'tribe' ), esc_html__( 'Closed', 'tribe' ) )
						->default( false )
						->helperText( esc_html__( 'Expand this facet by default in the mobile filter flyout. Only applies with a sidebar filter bar.', 'tribe' ) )
						->column( 50 )
						->conditionalLogic( $this->accordion_open_conditionals( self::FACET_MOBILE_TYPE, true ) ),
				] ),
		];
	}

	/**
	 * Show "starts expanded" when the effective layout type is checkboxes or radio.
	 *
	 * When layout customization is off, the Display Type is the effective type.
	 * When on, the layout-specific field is — empty (inherit) falls through to
	 * Display Type for sidebar, or to sidebar-then-display for mobile.
	 *
	 * @return list<\Extended\ACF\ConditionalLogic>
	 */
	private function accordion_open_conditionals( string $layout_type_field, bool $is_mobile = false ): array {
		$accordion_types = [ Facet_Types::CHECKBOXES, Facet_Types::RADIO ];
		$conditionals    = [];

		foreach ( $accordion_types as $type ) {
			$conditionals[] = ConditionalLogic::where( self::FACET_CUSTOMIZE_LAYOUT, '!=', 1 )
				->and( self::FACET_TYPE, '==', $type );
		}

		foreach ( $accordion_types as $type ) {
			$conditionals[] = ConditionalLogic::where( self::FACET_CUSTOMIZE_LAYOUT, '==', 1 )
				->and( $layout_type_field, '==', $type );
		}

		if ( $is_mobile ) {
			foreach ( $accordion_types as $type ) {
				// Mobile empty → sidebar when sidebar is set.
				$conditionals[] = ConditionalLogic::where( self::FACET_CUSTOMIZE_LAYOUT, '==', 1 )
					->and( self::FACET_MOBILE_TYPE, '==empty' )
					->and( self::FACET_SIDEBAR_TYPE, '==', $type );

				// Mobile + sidebar empty → Display Type.
				$conditionals[] = ConditionalLogic::where( self::FACET_CUSTOMIZE_LAYOUT, '==', 1 )
					->and( self::FACET_MOBILE_TYPE, '==empty' )
					->and( self::FACET_SIDEBAR_TYPE, '==empty' )
					->and( self::FACET_TYPE, '==', $type );
			}

			return $conditionals;
		}

		foreach ( $accordion_types as $type ) {
			// Sidebar empty → Display Type.
			$conditionals[] = ConditionalLogic::where( self::FACET_CUSTOMIZE_LAYOUT, '==', 1 )
				->and( $layout_type_field, '==empty' )
				->and( self::FACET_TYPE, '==', $type );
		}

		return $conditionals;
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

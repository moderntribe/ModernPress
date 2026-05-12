<?php declare(strict_types=1);

namespace Tribe\Plugin\Object_Meta\Post_Types;

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\DateTimePicker;
use Extended\ACF\Fields\Link;
use Extended\ACF\Fields\RadioButton;
use Extended\ACF\Fields\Relationship;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Location;
use Tribe\Plugin\Object_Meta\Meta_Object;
use Tribe\Plugin\Post_Types\Announcement\Announcement;
use WP_Post_Type;
use WP_Taxonomy;

class Announcement_Meta extends Meta_Object {

	public const string POSITION                        = 'normal';
	public const string GROUP_SLUG                      = 'announcement_meta';
	public const string CONTENT_TAB                     = 'content_tab';
	public const string HEADING                         = 'heading';
	public const string BODY                            = 'body';
	public const string CTA_LINK                        = 'cta_link';
	public const string SETUP_TAB                       = 'setup_tab';
	public const string DISMISSIBLE                     = 'dismissible';
	public const string COLOR_THEME                     = 'color_theme';
	public const string CTA_STYLE                       = 'cta_style';
	public const string CTA_STYLE_OUTLINED              = 'outlined';
	public const string CTA_STYLE_GHOST                 = 'ghost';
	public const string COLOR_THEME_BRAND               = 'brand';
	public const string COLOR_THEME_BLACK               = 'black';
	public const string COLOR_THEME_ERROR               = 'error';
	public const string COLOR_THEME_WARNING             = 'warning';
	public const string ALIGNMENT_CENTER                = 'center';
	public const string ALIGNMENT                       = 'alignment';
	public const string ALIGNMENT_LEFT                  = 'left';
	public const string ORIENTATION                     = 'orientation';
	public const string ORIENTATION_HORIZONTAL          = 'horizontal';
	public const string ORIENTATION_VERTICAL            = 'vertical';
	public const string DISPLAY_TAB                     = 'display_tab';
	public const string PLACEMENT                       = 'placement';
	public const string PLACEMENT_ABOVE                 = 'placement_above';
	public const string PLACEMENT_BELOW                 = 'placement_below';
	public const string SCHEDULED                       = 'scheduled';
	public const string SCHEDULING_START_TIME           = 'schedule_start_time';
	public const string SCHEDULING_END_TIME             = 'schedule_end_time';
	public const string FIELD_RULES_DISPLAY_TYPE        = 'rules_display_type';
	public const string FIELD_RULES_APPLY_TO_FRONT_PAGE = 'rules_apply_to_front_page';
	public const string FIELD_RULES_INCLUDE_PAGES       = 'rules_include_pages';
	public const string FIELD_RULES_EXCLUDE_PAGES       = 'rules_exclude_pages';
	public const string FIELD_TAXONOMY_ARCHIVES         = 'taxonomy_archives';
	public const string FIELD_POST_TYPE_ARCHIVES        = 'post_type_archives';
	public const string OPTION_EVERY_PAGE               = 'every_page';
	public const string OPTION_INCLUDE                  = 'include';
	public const string OPTION_EXCLUDE                  = 'exclude';
	public const int    MAX_POSTS                       = 50;

	public function get_slug(): string {
		return self::GROUP_SLUG;
	}

	public function get_title(): string {
		return esc_html__( 'Announcement', 'tribe' );
	}

	public function get_fields(): array {
		return [
			Tab::make( esc_html__( 'Content', 'tribe' ), self::CONTENT_TAB )
				->placement( 'left' ),
			Text::make( esc_html__( 'Heading', 'tribe' ), self::HEADING )
				->required(),
			Textarea::make( esc_html__( 'Body', 'tribe' ), self::BODY )
				->rows( 3 ),
			Link::make( esc_html__( 'CTA URL', 'tribe' ), self::CTA_LINK ),

			Tab::make( esc_html__( 'Setup', 'tribe' ), self::SETUP_TAB )
				->placement( 'left' ),
			RadioButton::make( esc_html__( 'CTA Style', 'tribe' ), self::CTA_STYLE )
				->choices( [
					self::CTA_STYLE_OUTLINED => esc_html__( 'Outlined', 'tribe' ),
					self::CTA_STYLE_GHOST    => esc_html__( 'Ghost', 'tribe' ),
				] )
				->default( self::CTA_STYLE_OUTLINED ),
			TrueFalse::make( esc_html__( 'Dismissible', 'tribe' ), self::DISMISSIBLE )
				->default( 0 )
				->stylized( esc_html__( 'Yes', 'tribe' ), esc_html__( 'No', 'tribe' ) ),
			Select::make( esc_html__( 'Color Theme', 'tribe' ), self::COLOR_THEME )
				->choices( [
					self::COLOR_THEME_BRAND   => esc_html__( 'Brand', 'tribe' ),
					self::COLOR_THEME_BLACK   => esc_html__( 'Black', 'tribe' ),
					self::COLOR_THEME_ERROR   => esc_html__( 'Error', 'tribe' ),
					self::COLOR_THEME_WARNING => esc_html__( 'Warning', 'tribe' ),
				] )
				->default( self::COLOR_THEME_BRAND ),
			RadioButton::make( esc_html__( 'Alignment', 'tribe' ), self::ALIGNMENT )
				->choices( [
					self::ALIGNMENT_LEFT   => esc_html__( 'Left', 'tribe' ),
					self::ALIGNMENT_CENTER => esc_html__( 'Center', 'tribe' ),
				] )
				->default( self::ALIGNMENT_CENTER ),
			RadioButton::make( esc_html__( 'Orientation', 'tribe' ), self::ORIENTATION )
				->choices( [
					self::ORIENTATION_HORIZONTAL => esc_html__( 'Horizontal', 'tribe' ),
					self::ORIENTATION_VERTICAL   => esc_html__( 'Vertical', 'tribe' ),
				] )
				->default( self::ORIENTATION_HORIZONTAL )
				->helperText( esc_html__( 'Desktop layout. On mobile, content is always stacked.', 'tribe' ) ),

			Tab::make( esc_html__( 'Display', 'tribe' ), self::DISPLAY_TAB )
				->placement( 'left' ),
			RadioButton::make( esc_html__( 'Placement', 'tribe' ), self::PLACEMENT )
				->choices( [
					self::PLACEMENT_ABOVE => esc_html__( 'Above Header', 'tribe' ),
					self::PLACEMENT_BELOW => esc_html__( 'Below Header', 'tribe' ),
				] ),
			TrueFalse::make( esc_html__( 'Enable Scheduled', 'tribe' ), self::SCHEDULED )
				->default( false )
				->stylized( esc_html__( 'Yes', 'tribe' ), esc_html__( 'No', 'tribe' ) ),
			DateTimePicker::make( esc_html__( 'Start Date', 'tribe' ), self::SCHEDULING_START_TIME )
				->displayFormat( 'd/m/Y g:i a' )
				->format( 'U' )
				->column( 50 )
				->conditionalLogic([
					ConditionalLogic::where( self::SCHEDULED, '==', 1 ),
				]),
			DateTimePicker::make( esc_html__( 'End Date', 'tribe' ), self::SCHEDULING_END_TIME )
				->displayFormat( 'd/m/Y g:i a' )
				->format( 'U' )
				->column( 50 )
				->conditionalLogic([
					ConditionalLogic::where( self::SCHEDULED, '==', 1 ),
				]),
			RadioButton::make( esc_html__( 'Select a rule', 'tribe' ), self::FIELD_RULES_DISPLAY_TYPE )
				->choices( [
					self::OPTION_EVERY_PAGE => esc_html__( 'Show everywhere', 'tribe' ),
					self::OPTION_INCLUDE    => esc_html__( 'Show only on specified pages', 'tribe' ),
					self::OPTION_EXCLUDE    => esc_html__( 'Exclude from specific pages', 'tribe' ),
				] )
				->default( self::OPTION_EVERY_PAGE ),
			TrueFalse::make( esc_html__( 'Apply the selected rule to the Front Page', 'tribe' ), self::FIELD_RULES_APPLY_TO_FRONT_PAGE )
				->helperText( sprintf(
					'%s<a href="%s">%s</a>%s',
					esc_html__( 'Regardless of the configuration in ', 'tribe' ),
					esc_url( admin_url( 'options-reading.php' ) ),
					esc_html__( 'Settings > Reading', 'tribe' ),
					esc_html__( ', always apply these rules to the front page', 'tribe' )
				) )
				->stylized( esc_html__( 'Yes', 'tribe' ), esc_html__( 'No', 'tribe' ) )
				->default( 0 )
				->conditionalLogic( [
					ConditionalLogic::where( self::FIELD_RULES_DISPLAY_TYPE, '!=', self::OPTION_EVERY_PAGE ),
				] ),
			Relationship::make( esc_html__( 'Select pages where the announcement will appear', 'tribe' ), self::FIELD_RULES_INCLUDE_PAGES )
				->helperText( sprintf(
					esc_html__( 'Select up to %d posts', 'tribe' ),
					(int) apply_filters( 'tribe/announcement/meta/max_posts', self::MAX_POSTS )
				) )
				->postTypes( $this->get_allowed_post_types() )
				->filters( [ 'search', 'post_type', 'taxonomy' ] )
				->minPosts( 0 )
				->maxPosts( (int) apply_filters( 'tribe/announcement/meta/max_posts', self::MAX_POSTS ) )
				->format( 'object' )
				->conditionalLogic( [
					ConditionalLogic::where( self::FIELD_RULES_DISPLAY_TYPE, '==', self::OPTION_INCLUDE ),
				] ),
			Relationship::make( esc_html__( 'Will appear on every page but the following selected pages', 'tribe' ), self::FIELD_RULES_EXCLUDE_PAGES )
				->helperText( sprintf(
					esc_html__( 'Select up to %d posts', 'tribe' ),
					(int) apply_filters( 'tribe/announcement/meta/max_posts', self::MAX_POSTS )
				) )
				->postTypes( $this->get_allowed_post_types() )
				->filters( [ 'search', 'post_type', 'taxonomy' ] )
				->minPosts( 0 )
				->maxPosts( (int) apply_filters( 'tribe/announcement/meta/max_posts', self::MAX_POSTS ) )
				->format( 'object' )
				->conditionalLogic( [
					ConditionalLogic::where( self::FIELD_RULES_DISPLAY_TYPE, '==', self::OPTION_EXCLUDE ),
				] ),
			Select::make( esc_html__( 'Apply the selected rule to the following Taxonomy Archives:', 'tribe' ), self::FIELD_TAXONOMY_ARCHIVES )
				->choices( array_reduce(
					get_taxonomies( [ 'public' => true ], 'objects' ),
					static function ( array $taxonomies, WP_Taxonomy $tax ) {
						$taxonomies[ $tax->name ] = $tax->labels->name;

						return $taxonomies;
					},
					[]
				) )
				->stylized()
				->nullable()
				->default( [] )
				->format( 'value' )
				->multiple()
				->conditionalLogic( [
					ConditionalLogic::where( self::FIELD_RULES_DISPLAY_TYPE, '!=', self::OPTION_EVERY_PAGE ),
				] ),
			Select::make( esc_html__( 'Apply the selected rule to the following Post Type Archives:', 'tribe' ), self::FIELD_POST_TYPE_ARCHIVES )
				->choices( array_reduce(
					get_post_types( [
						'public'             => true,
						'publicly_queryable' => true,
						'has_archive'        => true,
					], 'objects' ),
					static function ( array $post_types, WP_Post_Type $post_type ) {
						$post_types[ $post_type->name ] = $post_type->labels->name;

						return $post_types;
					},
					[]
				) )
				->stylized()
				->nullable()
				->default( [] )
				->format( 'value' )
				->multiple()
				->conditionalLogic( [
					ConditionalLogic::where( self::FIELD_RULES_DISPLAY_TYPE, '!=', self::OPTION_EVERY_PAGE ),
				] ),
		];
	}

	public function get_locations(): array {
		return [
			Location::where( 'post_type', '=', Announcement::NAME ),
		];
	}

	private function get_allowed_post_types(): array {
		return \acf_get_post_types( [ 'exclude' => [ Announcement::NAME, 'attachment' ] ] );
	}

}

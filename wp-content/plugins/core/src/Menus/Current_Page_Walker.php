<?php declare(strict_types=1);

namespace Tribe\Plugin\Menus;

class Current_Page_Walker extends \Walker_Page {

	/**
	 * @param string   $output
	 * @param \WP_Post $data_object
	 * @param int      $depth
	 * @param array    $args
	 * @param int      $current_object_id
	 */
	// phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	public function start_el( &$output, $data_object, $depth = 0, $args = [], $current_object_id = 0 ): void {
		$item = '';
		parent::start_el( $item, $data_object, $depth, $args, $args['current_page'] );

		$tags = new \WP_HTML_Tag_Processor( $item );
		if ( $tags->next_tag( [ 'tag_name' => 'li' ] ) ) {
			$tags->add_class( 'b-inline-navigation__list-item' );
		}
		if ( $tags->next_tag( [ 'tag_name' => 'a' ] ) ) {
			$tags->add_class( 'b-inline-navigation__list-link' );
		}

		$output .= $tags->get_updated_html();
	}

}

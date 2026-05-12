<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Announcements;

use Tribe\Plugin\Components\Abstracts\Abstract_Controller;
use Tribe\Plugin\Components\Blocks\Announcements\Rules\Front_Page_Rule;
use Tribe\Plugin\Components\Blocks\Announcements\Rules\Page_Visibility_Rule;
use Tribe\Plugin\Components\Blocks\Announcements\Rules\Placement_Rule;
use Tribe\Plugin\Components\Blocks\Announcements\Rules\Rule_Interface;
use Tribe\Plugin\Components\Blocks\Announcements\Rules\Scheduling_Rule;
use Tribe\Plugin\Post_Types\Announcement\Announcement;

class Announcement_Controller extends Abstract_Controller {

	private const int QUERY_LIMIT = 50;

	/**
	 * @var \Tribe\Plugin\Components\Blocks\Announcements\Rules\Rule_Interface[]
	 */
	private array $rules = [];

	/**
	 * @var string[]|int[]|bool[]
	 */
	private array $context = [];

	public function __construct() {
		$this->add_rule( new Placement_Rule() );
		$this->add_rule( new Front_Page_Rule() );
		$this->add_rule( new Page_Visibility_Rule() );
		$this->add_rule( new Scheduling_Rule() );
	}

	/**
	 * Get announcements for a specific placement
	 *
	 * @param string $placement The placement location (above_header|below_header)
	 * @param array  $context   Additional context for rule processing
	 *
	 * @return \WP_Post[]
	 */
	public function get_announcement_for_placement( string $placement, array $context = [] ): array {
		$this->context = array_merge( $context, [
			'placement'       => $placement,
			'current_post_id' => get_queried_object_id(),
			'is_home'         => is_home(),
			'is_front_page'   => is_front_page(),
			'is_single'       => is_single(),
			'is_page'         => is_page(),
			'is_category'     => is_category(),
			'is_tag'          => is_tag(),
			'is_tax'          => is_tax(),
			'is_archive'      => is_archive(),
			'current_time'    => current_time( 'U' ),
		] );

		$announcements = $this->get_all_announcements();

		return $this->process_pipeline( $announcements );
	}

	/**
	 * Add a rule to the processing pipeline
	 *
	 * @param \Tribe\Plugin\Components\Blocks\Announcements\Rules\Rule_Interface $rule
	 *
	 * @return \Tribe\Plugin\Components\Blocks\Announcements\Announcement_Controller
	 */
	public function add_rule( Rule_Interface $rule ): self {
		$this->rules[] = $rule;

		return $this;
	}

	/**
	 * Process announcements through the rule pipeline
	 *
	 * @param \WP_Post[] $announcements
	 *
	 * @return \WP_Post[]
	 */
	private function process_pipeline( array $announcements ): array {
		foreach ( $this->rules as $rule ) {
			$announcements = array_filter( $announcements, function ( $announcement ) use ( $rule ) {
				return $rule->passes( $announcement, $this->context );
			} );
		}

		return $announcements;
	}

	/**
	 * Get all published announcements
	 *
	 * @return \WP_Post[]
	 */
	private function get_all_announcements(): array {
		$query = new \WP_Query( [
			'post_type'      => Announcement::NAME,
			'post_status'    => 'publish',
			'posts_per_page' => self::QUERY_LIMIT,
			'orderby'        => [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			],
		] );

		return $query->posts;
	}

}

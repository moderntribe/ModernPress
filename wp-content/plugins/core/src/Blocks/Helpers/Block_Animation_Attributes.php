<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks\Helpers;

class Block_Animation_Attributes {

	protected string $animation_type               = 'none';
	protected string $animation_direction          = 'bottom';
	protected string $animation_duration           = '0.6s';
	protected string $animation_delay              = '0s';
	protected bool $animation_disable_mobile_delay = false;
	protected string $animation_easing             = 'cubic-bezier(0.390, 0.575, 0.565, 1.000)';
	protected bool $animation_trigger              = false;
	protected string $animation_position           = '25';
	protected bool $is_scroll_animation            = true;

	public function __construct( array $attributes = [] ) {
		$this->animation_type = $attributes['animationType'] ?? 'none';

		if ( $this->animation_type === 'none' ) {
			return;
		}

		$this->is_scroll_animation = $this->animation_type !== 'scale-on-hover';

		if ( $this->has_animation_direction ) {
			$this->animation_direction = $attributes['animationDirection'];
		}

		if ( $this->is_scroll_animation ) {
			$this->animation_trigger  = $attributes['animationTrigger'];
			$this->animation_position = $attributes['animationPosition'];
		}

		$this->animation_duration             = $attributes['animationDuration'];
		$this->animation_delay                = $attributes['animationDelay'];
		$this->animation_disable_mobile_delay = $attributes['animationMobileDisableDelay'];
		$this->animation_easing               = $attributes['animationEasing'];
	}

	public function register_animation_attributes(): void {
		$blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		foreach ( $blocks as $block ) {
			/**
			 * in order to grab dynamic blocks, we should check if the block has a render callback
			 * if it does not, we can skip it as it is likely a static block - sometimes the
			 * render callback is a string, so we need to check for that as well
			 */
			if ( is_null( $block->render_callback ) || is_string( $block->render_callback ) ) {
				continue;
			}

			$animation_attributes = [
				'animationType'               => [
					'type'    => 'string',
					'default' => 'none',
				],
				'animationDirection'          => [
					'type'    => 'string',
					'default' => 'bottom',
				],
				'showAdvancedControls'        => [
					'type'    => 'boolean',
					'default' => false,
				],
				'animationDuration'           => [
					'type'    => 'string',
					'default' => '0.6s',
				],
				'animationDelay'              => [
					'type'    => 'string',
					'default' => '0s',
				],
				'animationMobileDisableDelay' => [
					'type'    => 'boolean',
					'default' => false,
				],
				'animationEasing'             => [
					'type'    => 'string',
					'default' => 'var(--easing--ease-out-sine)',
				],
				'animationTrigger'            => [
					'type'    => 'boolean',
					'default' => false,
				],
				'animationPosition'           => [
					'type'    => 'string',
					'default' => '25',
				],
			];

			$block->attributes = array_merge( $block->attributes, $animation_attributes );
		}
	}

	public function get_classes(): string {
		if ( $this->animation_type === 'none' ) {
			return '';
		}

		$classes = "tribe-animation-type-{$this->animation_type}";

		if ( $this->is_scroll_animation ) {
			$classes = "is-animated-on-scroll-{$this->animation_position} {$classes}";
		}

		if ( $this->has_animation_direction() ) {
			$classes .= " tribe-animation-direction-{$this->animation_direction}";
		}

		if ( $this->animation_disable_mobile_delay ) {
			$classes .= ' tribe-animation-mobile-disable-delay';
		}

		if ( $this->is_scroll_animation && $this->animation_trigger ) {
			$classes .= ' tribe-animate-multiple';
		}

		return $classes;
	}

	public function get_styles(): string {
		$styles = '';

		if ( $this->animation_type === 'none' ) {
			return $styles;
		}

		if ( $this->animation_duration ) {
			$styles .= "--tribe-animation-speed: {$this->animation_duration};";

			if ( $this->is_scroll_animation ) {
				$animation_offset = $this->get_animation_offset( $this->animation_duration );
				$styles          .= "--tribe-animation-offset: {$animation_offset};";
			}
		}

		if ( $this->animation_delay ) {
			$styles .= "--tribe-animation-delay: {$this->animation_delay};";
		}

		if ( $this->animation_easing ) {
			$styles .= "--tribe-animation-easing: {$this->animation_easing};";
		}

		return $styles;
	}

	protected function get_animation_offset( string $duration ): string {
		$default_values = [
			'0.3s' => '20px',
			'0.6s' => '50px',
			'0.9s' => '90px',
			'1.2s' => '160px',
			'1.4s' => '280px',
		];

		/**
		 * typically we would grab the values set in theme.json here
		 * but it might be too taxing to do so as it would involve
		 * reading from the filesystem for every block that needs to
		 * render the animation offset, so I'm hardcoding the defaults
		 * here so they can be easily updated if necessary.
		 */

		return $default_values[ $duration ];
	}

	protected function has_animation_direction(): bool {
		return ! in_array( $this->animation_type, [ 'pulse', 'scale-on-hover' ], true );
	}

}

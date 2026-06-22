<?php declare(strict_types=1);

namespace Tribe\Plugin\Theme_Config;

use Tribe\Plugin\Settings\Tribe_Settings;
use WP_Post;

class Google_Maps {

	private bool $should_load = false;

	private bool $loader_rendered = false;

	public function __construct(
		private Tribe_Settings $settings,
	) {
	}

	public function register(): void {
		add_action( 'wp', [ $this, 'detect_location_map_in_post' ], 20 );
		add_filter( 'render_block', [ $this, 'track_location_map_block' ], 10, 2 );
		add_action( 'wp_head', [ $this, 'maybe_render_loader' ], 5 );
		add_action( 'wp_footer', [ $this, 'maybe_render_loader' ], 1 );
	}

	public function detect_location_map_in_post(): void {
		if ( ! $this->settings->is_google_geocoder_active() ) {
			return;
		}

		global $post;

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( ! has_block( 'tribe/location-map', $post ) ) {
			return;
		}

		$this->should_load = true;
	}

	/**
	 * @param array<string, mixed> $block
	 */
	public function track_location_map_block( string $content, array $block ): string {
		if ( 'tribe/location-map' === ( $block['blockName'] ?? '' ) ) {
			$this->should_load = true;
		}

		return $content;
	}

	public function maybe_render_loader(): void {
		if ( ! $this->should_load || $this->loader_rendered ) {
			return;
		}

		$this->render_loader();
		$this->loader_rendered = true;
	}

	public function should_load(): bool {
		return $this->should_load && $this->settings->is_google_geocoder_active();
	}

	private function render_loader(): void {
		if ( ! $this->settings->is_google_geocoder_active() ) {
			return;
		}

		$api_key = esc_attr( $this->settings->get_google_maps_api_key() );

		if ( $api_key === '' ) {
			return;
		}

		printf(
			'<!-- Google Maps -->
			<script>
				(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
				key: "%s",
				v: "weekly",
				});
			</script>
			<!-- End Google Maps -->',
			$api_key
		);
	}

}

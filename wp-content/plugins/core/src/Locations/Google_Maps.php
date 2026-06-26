<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use Tribe\Plugin\Settings\Tribe_Settings;

class Google_Maps {

	public function __construct(
		private Tribe_Settings $settings,
	) {
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', function (): void {
			$this->maybe_enqueue_frontend_loader();
		}, 5, 0 );
	}

	private function maybe_enqueue_frontend_loader(): void {
		if ( is_admin() || ! $this->settings->has_google_maps_api_key() ) {
			return;
		}

		if ( ! has_block( 'tribe/location-map' ) ) {
			return;
		}

		$this->print_loader_script();
	}

	private function print_loader_script(): void {
		$api_key = esc_attr( $this->settings->get_google_maps_api_key() );

		printf(
			'<!-- Google Maps -->
			<script>
				(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
				key: "%s",
				v: "weekly",
				});
			</script>
			<!-- End Google Maps -->',
			$api_key // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		);
	}

}

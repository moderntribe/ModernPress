<?php
/**
 * Admin Settings Page.
 *
 * @package Tribe\AI
 */

namespace Tribe\AI\Admin;

/**
 * Class Settings_Page
 */
class Settings_Page {
	/**
	 * Option name for API key.
	 */
	const API_KEY_OPTION = 'tribe_ai_api_key';

	/**
	 * Initialize settings page.
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add menu page.
	 */
	public function add_menu_page(): void {
		add_options_page(
			__( 'AI Assistant Settings', 'tribe-ai-assistant' ),
			__( 'AI Assistant', 'tribe-ai-assistant' ),
			'manage_options',
			'tribe-ai-assistant',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings(): void {
		register_setting(
			'tribe_ai_settings',
			self::API_KEY_OPTION,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_api_key' ],
			]
		);

		add_settings_section(
			'tribe_ai_api_section',
			__( 'OpenAI API Configuration', 'tribe-ai-assistant' ),
			[ $this, 'render_api_section' ],
			'tribe-ai-assistant'
		);

		add_settings_field(
			'tribe_ai_api_key',
			__( 'OpenAI API Key', 'tribe-ai-assistant' ),
			[ $this, 'render_api_key_field' ],
			'tribe-ai-assistant',
			'tribe_ai_api_section'
		);

	}

	/**
	 * Sanitize API key.
	 *
	 * @param string $value API key value.
	 * @return string
	 */
	public function sanitize_api_key( string $value ): string {
		return sanitize_text_field( trim( $value ) );
	}

	/**
	 * Render settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle cache refresh.
		if ( isset( $_POST['tribe_ai_refresh_cache'] ) && check_admin_referer( 'tribe_ai_refresh_cache' ) ) {
			delete_transient( 'tribe_ai_site_context' );
			if ( class_exists( 'Tribe\AI\AI\Site_Analyzer' ) ) {
				$analyzer = new \Tribe\AI\AI\Site_Analyzer();
				$analyzer->analyze_site();
			}
			add_settings_error(
				'tribe_ai_messages',
				'tribe_ai_cache_refreshed',
				__( 'Site analysis cache refreshed successfully.', 'tribe-ai-assistant' ),
				'success'
			);
		}

		settings_errors( 'tribe_ai_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'tribe_ai_settings' );
				do_settings_sections( 'tribe-ai-assistant' );
				submit_button( __( 'Save Settings', 'tribe-ai-assistant' ) );
				?>
			</form>
			<?php $this->render_cache_section(); ?>
		</div>
		<?php
	}

	/**
	 * Render API section description.
	 */
	public function render_api_section(): void {
		?>
		<p>
			<?php
			esc_html_e(
				'Enter your OpenAI API key to enable AI-powered content generation. Get your API key from',
				'tribe-ai-assistant'
			);
			?>
			<a href="https://platform.openai.com/api-keys" target="_blank">
				<?php esc_html_e( 'OpenAI Platform', 'tribe-ai-assistant' ); ?>
			</a>.
		</p>
		<?php
	}

	/**
	 * Render API key field.
	 */
	public function render_api_key_field(): void {
		$api_key = get_option( self::API_KEY_OPTION, '' );
		?>
		<input
			type="password"
			id="tribe_ai_api_key"
			name="<?php echo esc_attr( self::API_KEY_OPTION ); ?>"
			value="<?php echo esc_attr( $api_key ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Your API key is stored securely and never exposed to the frontend.', 'tribe-ai-assistant' ); ?>
		</p>
		<?php
	}

	/**
	 * Render cache section.
	 */
	public function render_cache_section(): void {
		$cache = get_transient( 'tribe_ai_site_context' );
		?>
		<p>
			<?php esc_html_e( 'The AI assistant analyzes your site content to generate contextually appropriate pages.', 'tribe-ai-assistant' ); ?>
		</p>

		<?php if ( $cache ) : ?>
			<p>
				<strong><?php esc_html_e( 'Cache Status:', 'tribe-ai-assistant' ); ?></strong>
				<?php esc_html_e( 'Active', 'tribe-ai-assistant' ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Last Generated:', 'tribe-ai-assistant' ); ?></strong>
				<?php echo esc_html( human_time_diff( $cache['generated_at'] ) . ' ago' ); ?>
			</p>
			<?php if ( ! empty( $cache['top_blocks'] ) ) : ?>
				<p>
					<strong><?php esc_html_e( 'Most-Used Blocks:', 'tribe-ai-assistant' ); ?></strong><br>
					<?php
					$top_blocks = array_slice( $cache['top_blocks'], 0, 5, true );
					foreach ( $top_blocks as $block => $count ) {
						echo '<code>' . esc_html( $block ) . '</code> (' . esc_html( $count ) . ') ';
					}
					?>
				</p>
			<?php endif; ?>
		<?php else : ?>
			<p>
				<strong><?php esc_html_e( 'Cache Status:', 'tribe-ai-assistant' ); ?></strong>
				<?php esc_html_e( 'Not generated', 'tribe-ai-assistant' ); ?>
			</p>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'tribe_ai_refresh_cache' ); ?>
			<button type="submit" name="tribe_ai_refresh_cache" class="button button-secondary">
				<?php esc_html_e( 'Refresh Analysis Cache', 'tribe-ai-assistant' ); ?>
			</button>
		</form>
		<?php
	}
}

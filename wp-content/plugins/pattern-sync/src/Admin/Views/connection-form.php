<?php declare(strict_types=1);

/**
 * Admin view: Add or edit connection form.
 *
 * @var string $form_action
 * @var string $nonce_field
 * @var array{name: string, url: string, username: string}|null $connection  For edit.
 * @var string $submit_label
 * @var string $back_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$name     = $connection['name'] ?? '';
$url      = $connection['url'] ?? '';
$username = $connection['username'] ?? '';
?>
<div class="wrap">
	<h1><?php echo esc_html( $connection ? __( 'Edit connection', 'pattern-sync' ) : __( 'Add connection', 'pattern-sync' ) ); ?></h1>
	<p><a href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'Back to connections', 'pattern-sync' ); ?></a></p>

	<form method="post" action="<?php echo esc_url( $form_action ); ?>" id="pattern-sync-connection-form">
		<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php wp_nonce_field( 'pattern_sync_test', 'pattern_sync_test_nonce', false ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="pattern_sync_name"><?php esc_html_e( 'Connection name', 'pattern-sync' ); ?></label></th>
				<td>
					<input name="pattern_sync_name" id="pattern_sync_name" type="text" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required />
					<p class="description"><?php esc_html_e( 'A friendly name for this connection (e.g. "Staging site").', 'pattern-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pattern_sync_url"><?php esc_html_e( 'Site URL', 'pattern-sync' ); ?></label></th>
				<td>
					<input name="pattern_sync_url" id="pattern_sync_url" type="url" class="regular-text" value="<?php echo esc_attr( $url ); ?>" placeholder="https://othersite.com" required />
					<p class="description"><?php esc_html_e( 'The full URL of the other WordPress site (with Pattern Sync installed).', 'pattern-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pattern_sync_username"><?php esc_html_e( 'Username', 'pattern-sync' ); ?></label></th>
				<td>
					<input name="pattern_sync_username" id="pattern_sync_username" type="text" class="regular-text" value="<?php echo esc_attr( $username ); ?>" required />
					<p class="description"><?php esc_html_e( 'WordPress username for the remote site.', 'pattern-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pattern_sync_app_password"><?php esc_html_e( 'Application Password', 'pattern-sync' ); ?></label></th>
				<td>
					<input name="pattern_sync_app_password" id="pattern_sync_app_password" type="password" class="regular-text" value="" autocomplete="off" <?php echo $connection ? '' : 'required'; ?> />
					<p class="description"><?php esc_html_e( 'Application Password from the remote site (Users → Profile → Application Passwords). Leave blank to keep existing when editing.', 'pattern-sync' ); ?></p>
				</td>
			</tr>
		</table>
		<p class="submit">
			<button type="button" id="pattern-sync-test-connection" class="button"><?php esc_html_e( 'Test connection', 'pattern-sync' ); ?></button>
			<input type="submit" name="pattern_sync_save" class="button button-primary" value="<?php echo esc_attr( $submit_label ); ?>" />
		</p>
	</form>
	<div id="pattern-sync-test-result" role="alert" aria-live="polite" class="notice" style="margin-top: 1em; display: none;"><p></p></div>
</div>

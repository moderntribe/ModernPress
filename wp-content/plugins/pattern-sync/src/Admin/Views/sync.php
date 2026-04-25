<?php declare(strict_types=1);

/**
 * Sync UI: remote and local patterns with Pull/Push.
 * Only syncable (user-created or synced) patterns can be selected; code-registered are shown as locked.
 *
 * @var array{id: string, name: string, url: string, username: string} $connection
 * @var array<string, array<int, array{name: string, title: string, source: string, syncable: bool, origin: string}>> $remote_by_category
 * @var array<string, array<int, array{name: string, title: string, source: string, syncable: bool, origin: string}>> $local_by_category
 * @var string $back_url
 * @var string $refresh_url
 * @var string $form_action
 * @var string $nonce
 * @var array<int, array{type: string, text: string}> $messages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
<?php foreach ( $messages as $msg ) : ?>
	<div class="notice notice-<?php echo esc_attr( $msg['type'] ); ?> is-dismissible"><p><?php echo esc_html( $msg['text'] ); ?></p></div>
<?php endforeach; ?>
	<h1><?php esc_html_e( 'Sync patterns', 'pattern-sync' ); ?></h1>
	<p>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: connection name */
				__( 'Syncing with: %s', 'pattern-sync' ),
				$connection['name']
			)
		);
		?>
		<code><?php echo esc_html( $connection['url'] ); ?></code>
	</p>
	<p>
		<a href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'Back to connections', 'pattern-sync' ); ?></a>
		| <a href="<?php echo esc_url( $refresh_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Refresh patterns list', 'pattern-sync' ); ?></a>
	</p>

	<p class="description" style="margin-bottom: 1rem;"><?php esc_html_e( 'Only user-created or previously synced patterns can be synced. Patterns registered in code (theme/plugin) are shown as Locked.', 'pattern-sync' ); ?></p>
	<div class="pattern-sync-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
		<div class="pattern-sync-remote">
			<h2><?php esc_html_e( 'Remote site patterns', 'pattern-sync' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Select patterns to pull to this site. Only user-created or synced patterns can be selected.', 'pattern-sync' ); ?></p>
			<form method="post" action="<?php echo esc_url( $form_action ); ?>">
				<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="hidden" name="direction" value="pull" />
				<input type="hidden" name="connection_id" value="<?php echo esc_attr( $connection['id'] ); ?>" />
				<?php
				foreach ( $remote_by_category as $cat_label => $patterns ) {
					echo '<fieldset style="margin-bottom: 1em;"><legend><strong>' . esc_html( $cat_label ) . '</strong></legend><ul style="list-style: none; margin: 0;">';
					foreach ( $patterns as $p ) {
						$syncable     = $p['syncable'] ?? false;
						$label        = $p['title'] ?: $p['name'];
						$origin_label = ( $p['origin'] ?? '' ) === 'code' ? ' <span class="pattern-sync-locked" title="' . esc_attr__( 'Registered in code; cannot sync.', 'pattern-sync' ) . '">(' . esc_html__( 'Locked', 'pattern-sync' ) . ')</span>' : ' <span class="pattern-sync-user">(' . esc_html( ( $p['origin'] ?? '' ) === 'user' ? __( 'User-created', 'pattern-sync' ) : __( 'Synced', 'pattern-sync' ) ) . ')</span>';
						if ( $syncable ) {
							echo '<li><label><input type="checkbox" name="pull_patterns[]" value="' . esc_attr( $p['name'] ) . '" /> ' . esc_html( $label ) . $origin_label . ' <code>' . esc_html( $p['name'] ) . '</code></label></li>';
						} else {
							echo '<li><span class="pattern-sync-locked-item">' . esc_html( $label ) . $origin_label . ' <code>' . esc_html( $p['name'] ) . '</code></span></li>';
						}
					}
					echo '</ul></fieldset>';
				}
				if ( array_sum( array_map( 'count', $remote_by_category ) ) === 0 ) {
					echo '<p>' . esc_html__( 'No patterns on remote site.', 'pattern-sync' ) . '</p>';
				}
				?>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Pull selected to this site', 'pattern-sync' ); ?></button></p>
			</form>
		</div>
		<div class="pattern-sync-local">
			<h2><?php esc_html_e( 'This site\'s patterns', 'pattern-sync' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Select patterns to push to the remote site. Only user-created or synced patterns can be selected.', 'pattern-sync' ); ?></p>
			<form method="post" action="<?php echo esc_url( $form_action ); ?>">
				<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="hidden" name="direction" value="push" />
				<input type="hidden" name="connection_id" value="<?php echo esc_attr( $connection['id'] ); ?>" />
				<?php
				foreach ( $local_by_category as $cat_label => $patterns ) {
					echo '<fieldset style="margin-bottom: 1em;"><legend><strong>' . esc_html( $cat_label ) . '</strong></legend><ul style="list-style: none; margin: 0;">';
					foreach ( $patterns as $p ) {
						$syncable     = $p['syncable'] ?? false;
						$label        = $p['title'] ?: $p['name'];
						$origin_label = ( $p['origin'] ?? '' ) === 'code' ? ' <span class="pattern-sync-locked" title="' . esc_attr__( 'Registered in code; cannot sync.', 'pattern-sync' ) . '">(' . esc_html__( 'Locked', 'pattern-sync' ) . ')</span>' : ' <span class="pattern-sync-user">(' . esc_html( ( $p['origin'] ?? '' ) === 'user' ? __( 'User-created', 'pattern-sync' ) : __( 'Synced', 'pattern-sync' ) ) . ')</span>';
						if ( $syncable ) {
							echo '<li><label><input type="checkbox" name="push_patterns[]" value="' . esc_attr( $p['name'] ) . '" /> ' . esc_html( $label ) . $origin_label . ' <code>' . esc_html( $p['name'] ) . '</code></label></li>';
						} else {
							echo '<li><span class="pattern-sync-locked-item">' . esc_html( $label ) . $origin_label . ' <code>' . esc_html( $p['name'] ) . '</code></span></li>';
						}
					}
					echo '</ul></fieldset>';
				}
				if ( array_sum( array_map( 'count', $local_by_category ) ) === 0 ) {
					echo '<p>' . esc_html__( 'No patterns on this site.', 'pattern-sync' ) . '</p>';
				}
				?>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Push selected to remote', 'pattern-sync' ); ?></button></p>
			</form>
		</div>
	</div>
	<style>.pattern-sync-locked-item{ color: #646970; }.pattern-sync-locked{ color: #646970; }</style>
</div>

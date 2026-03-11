<?php declare(strict_types=1);

/**
 * Admin view: Pattern Sync connections list and add form.
 *
 * @var array<int, array{id: string, name: string, url: string, username: string}> $connections
 * @var string $add_form_url
 * @var string $sync_url_template
 * @var array<int, array{type: string, text: string}> $messages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Pattern Sync', 'pattern-sync' ); ?></h1>
	<a href="<?php echo esc_url( $add_form_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add connection', 'pattern-sync' ); ?></a>
	<a href="<?php echo esc_url( admin_url( 'tools.php?page=pattern-sync&action=log' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Sync Log', 'pattern-sync' ); ?></a>
	<hr class="wp-header-end" />

	<?php foreach ( $messages as $msg ) : ?>
		<div class="notice notice-<?php echo esc_attr( $msg['type'] ); ?> is-dismissible">
			<p><?php echo esc_html( $msg['text'] ); ?></p>
		</div>
	<?php endforeach; ?>

	<?php if ( count( $connections ) === 0 ) : ?>
		<p><?php esc_html_e( 'No connections yet. Add a connection to another WordPress site that has Pattern Sync installed.', 'pattern-sync' ); ?></p>
		<p><a href="<?php echo esc_url( $add_form_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add connection', 'pattern-sync' ); ?></a></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Name', 'pattern-sync' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Site URL', 'pattern-sync' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Username', 'pattern-sync' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'pattern-sync' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $connections as $conn ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $conn['name'] ); ?></strong></td>
						<td><code><?php echo esc_html( $conn['url'] ); ?></code></td>
						<td><?php echo esc_html( $conn['username'] ); ?></td>
						<td>
							<a href="<?php echo esc_url( sprintf( $sync_url_template, $conn['id'] ) ); ?>"><?php esc_html_e( 'Sync with this site', 'pattern-sync' ); ?></a>
							|
							<a href="<?php echo esc_url( $add_form_url . '&edit=' . rawurlencode( $conn['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'pattern-sync' ); ?></a>
							|
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pattern_sync_delete&id=' . rawurlencode( $conn['id'] ) ), 'pattern_sync_delete_' . $conn['id'] ) ); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js( __( 'Delete this connection?', 'pattern-sync' ) ); ?>');"><?php esc_html_e( 'Delete', 'pattern-sync' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

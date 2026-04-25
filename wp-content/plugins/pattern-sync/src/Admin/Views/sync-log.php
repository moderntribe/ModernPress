<?php declare(strict_types=1);

/**
 * Sync Log view.
 *
 * @var array<int, array{id: string, connection_id: string, direction: string, user_id: int, timestamp: int, patterns: array, overall_success: bool}> $entries
 * @var array<string, string> $connections  connection_id => name
 * @var string $back_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Sync Log', 'pattern-sync' ); ?></h1>
	<p><a href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'Back to connections', 'pattern-sync' ); ?></a></p>

	<?php if ( $entries === [] ) : ?>
		<p><?php esc_html_e( 'No sync runs yet.', 'pattern-sync' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'When', 'pattern-sync' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Connection', 'pattern-sync' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Direction', 'pattern-sync' ); ?></th>
					<th scope="col"><?php esc_html_e( 'User', 'pattern-sync' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Result', 'pattern-sync' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Patterns', 'pattern-sync' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $entries as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( __( 'Y-m-d H:i:s', 'pattern-sync' ), $entry['timestamp'] ) ); ?></td>
						<td><?php echo esc_html( $connections[ $entry['connection_id'] ] ?? $entry['connection_id'] ); ?></td>
						<td><?php echo esc_html( $entry['direction'] ); ?></td>
						<td><?php
							$user = get_user_by( 'id', $entry['user_id'] );
							echo $user ? esc_html( $user->display_name ) : esc_html( (string) $entry['user_id'] );
						?></td>
						<td><?php echo $entry['overall_success'] ? '<span style="color: green;">' . esc_html__( 'Success', 'pattern-sync' ) . '</span>' : '<span style="color: red;">' . esc_html__( 'Failure', 'pattern-sync' ) . '</span>'; ?></td>
						<td>
							<?php
							$names = array_map( static fn ( $p ) => ( $p['success'] ? '✓ ' : '✗ ' ) . ( $p['name'] ?? '' ), $entry['patterns'] );
							echo esc_html( implode( ', ', $names ) );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

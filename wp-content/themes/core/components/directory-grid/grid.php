<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Directory_Grid_Controller;

/**
 * Inner markup for the directory grid. Shared by the block render and the
 * REST endpoint that swaps results on filter changes.
 *
 * @var array $args
 */

$c = $args['controller'] ?? null;

if ( ! $c instanceof Directory_Grid_Controller ) {
	return;
}

$query = $c->get_query();
?>
<?php if ( $query->have_posts() ) : ?>
	<div class="b-directory-grid__grid">
		<?php while ( $query->have_posts() ) : ?>
			<?php $query->the_post(); ?>
			<?php get_template_part( 'components/cards/post', null, [
				'post_id' => get_the_ID(),
			] ); ?>
		<?php endwhile; ?>
	</div>
	<?php if ( $c->should_show_pagination() ) : ?>
		<nav class="b-directory-grid__pagination" aria-label="<?php esc_attr_e( 'Directory pagination', 'tribe' ); ?>">
			<?php echo $c->get_pagination_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links(). ?>
		</nav>
	<?php endif; ?>
<?php else : ?>
	<p class="b-directory-grid__empty"><?php esc_html_e( 'No results found.', 'tribe' ); ?></p>
<?php endif; ?>

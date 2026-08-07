<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Filter_Bar_Controller;

/**
 * @var array $args
 */

$c = $args['controller'] ?? null;

if ( ! $c instanceof Filter_Bar_Controller ) {
	return;
}
?>
<div class="b-filter-bar__grid">
	<?php get_template_part( 'components/filter-bar/facets', null, [
		'controller' => $c,
		'layout'     => 'top',
	] ); ?>
</div>

<?php declare(strict_types=1);

/**
 * Title: Enhanced Navigation Menu
 * Slug: patterns/enhanced-nav-menu
 * Categories: navigation, menu
 * Description: Menu layout that includes an image, a description, a button, and multiple navigation menus
 * Keywords: menu, navigation, footer, mega nav
 */
?>
<!-- wp:group {"metadata":{"categories":["navigation","menu"],"patternName":"patterns/enhanced-nav-menu","name":"Enhanced Navigation Menu"},"align":"wide","className":"nav-menu__enhanced alignwide","style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"right":"0","left":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group nav-menu__enhanced alignwide" style="padding-right:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"},"layout":{"selfStretch":"fixed","flexSize":"400px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:image {"sizeSlug":"large","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<figure class="wp-block-image size-large" style="margin-top:0;margin-bottom:0"><img src="https://placehold.co/260x40" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"is-style-small","style":{"spacing":{"margin":{"top":"0"}}}} -->
<p class="is-style-small" style="margin-top:0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut vel ante nec diam placerat vulputate. Fusce ut dui sagittis lacus rutrum tristique ut vitae leo.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"0"}}}} -->
<div class="wp-block-buttons" style="margin-top:0"><!-- wp:button {"className":"is-style-default"} -->
<div class="wp-block-button is-style-default"><a class="wp-block-button__link wp-element-button" href="#">some button</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:columns {"isStackedOnMobile":false,"align":"full","className":"alignfull","style":{"layout":{"selfStretch":"fill","flexSize":null},"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-columns is-not-stacked-on-mobile alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:column {"width":"25%","layout":{"type":"default"},"stackingOrder":0} -->
<div class="wp-block-column tribe-has-stacking-order" style="flex-basis:25%;--tribe-stacking-order:0"><!-- wp:paragraph {"className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p class="is-style-default" style="margin-top:0;margin-bottom:0"><strong>Menu Title</strong></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"ref":31,"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"wrap","orientation":"vertical"}} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%","stackingOrder":0} -->
<div class="wp-block-column tribe-has-stacking-order" style="flex-basis:25%;--tribe-stacking-order:0"><!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p style="margin-top:0;margin-bottom:0"><strong>Menu Title</strong></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"ref":32,"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%","stackingOrder":0} -->
<div class="wp-block-column tribe-has-stacking-order" style="flex-basis:25%;--tribe-stacking-order:0"></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%","stackingOrder":0} -->
<div class="wp-block-column tribe-has-stacking-order" style="flex-basis:25%;--tribe-stacking-order:0"></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

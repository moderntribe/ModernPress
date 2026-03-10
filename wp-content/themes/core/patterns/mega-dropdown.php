<?php declare(strict_types=1);

/**
* Title: Mega Dropdown
* Slug: patterns/mega-dropdown
* Categories: navigation, menu
* Description: Menu layout for the masthead that includes an image, a description, a button, and multiple navigation menus
* Keywords: menu, navigation, footer, mega nav
*/
?>
<!-- wp:group {"metadata":{"categories":["navigation","menu"],"patternName":"patterns/mega-dropdown","name":"Mega Dropdown"},"align":"wide","className":"mega-menu-item__dropdown alignwide","style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"right":"0","left":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group mega-menu-item__dropdown alignwide" style="padding-right:0;padding-left:0"><!-- wp:group {"metadata":{"name":"Mega Dropdown Card"},"className":"mega-menu-item__dropdown-card","style":{"spacing":{"blockGap":"var:preset|spacing|30"},"layout":{"selfStretch":"fixed","flexSize":"400px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group mega-menu-item__dropdown-card"><!-- wp:image {"sizeSlug":"large","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<figure class="wp-block-image size-large" style="margin-top:0;margin-bottom:0"><img src="https://placehold.co/184" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"className":"is-style-x-small"} -->
<h3 class="wp-block-heading is-style-x-small">Section Heading</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-small","style":{"spacing":{"margin":{"top":"0"}}}} -->
<p class="is-style-small" style="margin-top:0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut vel ante nec diam placerat vulputate. Fusce ut dui sagittis lacus rutrum tristique ut vitae leo.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"0"}}}} -->
<div class="wp-block-buttons" style="margin-top:0"><!-- wp:button {"className":"is-style-ghost"} -->
<div class="wp-block-button is-style-ghost"><a class="wp-block-button__link wp-element-button" href="#">Menu Item 1 CTA</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:columns {"isStackedOnMobile":false,"metadata":{"name":"Mega Dropdown Columns"},"align":"full","className":"alignfull","style":{"layout":{"selfStretch":"fill","flexSize":null},"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-columns is-not-stacked-on-mobile alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:column {"width":"33.33%","layout":{"type":"default"},"stackingOrder":0} -->
<div class="wp-block-column tribe-has-stacking-order" style="flex-basis:33.33%;--tribe-stacking-order:0"><!-- wp:paragraph {"className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p class="is-style-default" style="margin-top:0;margin-bottom:0"><strong>Menu Title</strong></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"ref":31,"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"wrap","orientation":"vertical"}} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%","stackingOrder":0} -->
<div class="wp-block-column tribe-has-stacking-order" style="flex-basis:33.33%;--tribe-stacking-order:0"><!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p style="margin-top:0;margin-bottom:0"><strong>Menu Title</strong></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"ref":32,"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%","stackingOrder":0} -->
<div class="wp-block-column tribe-has-stacking-order" style="flex-basis:33.33%;--tribe-stacking-order:0"><!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p style="margin-top:0;margin-bottom:0"><strong>Menu Title</strong></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"ref":32,"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

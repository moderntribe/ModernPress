<?php declare(strict_types=1);

/**
 * Title: Post - Left Aligned
 * Slug: patterns/post-left-aligned
 * Categories: templates
 * Description: A left aligned pattern for a single post page
 * Keywords: post, placeholder, left, aligned
 * Block Types: core/post-content
 * Post Types: post, wp_template
 */
?>
<!-- wp:group {"metadata":{"name":"Post Hero"},"align":"full","className":"alignfull","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"neutral-10","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-neutral-10-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","className":"aligngrid"} -->
	<div class="wp-block-columns are-vertically-aligned-center aligngrid"><!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center"><!-- wp:tribe/terms {"taxonomyToUse":"category","onlyPrimaryTerm":true,"hasLinks":true} /-->

			<!-- wp:post-title {"level":1,"style":{"spacing":{"margin":{"top":"var:preset|spacing|10"}}}} /-->

			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"0","bottom":"0"},"margin":{"top":"var:preset|spacing|30","bottom":"0"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
			<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:post-author-name {"textAlign":"center","isLink":true,"style":{"spacing":{"margin":{"bottom":"0","top":"0","left":"0","right":"0"}}}} /-->

				<!-- wp:paragraph -->
				<p>•</p>
				<!-- /wp:paragraph -->

				<!-- wp:post-date {"textAlign":"center","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0","left":"0","right":"0"}}},"fontSize":"10"} /--></div>
			<!-- /wp:group --></div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center"><!-- wp:post-featured-image {"aspectRatio":"4/3"} /--></div>
		<!-- /wp:column --></div>
	<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Post Content"},"align":"full","className":"alignfull","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:columns {"className":"aligngrid","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns aligngrid"><!-- wp:column {"width":"66.66%","layout":{"type":"constrained","justifyContent":"left"}} -->
		<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:paragraph -->
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
			<!-- /wp:paragraph -->

			<!-- wp:separator {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
			<hr class="wp-block-separator has-alpha-channel-opacity" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40)"/>
			<!-- /wp:separator -->

			<!-- wp:paragraph {"className":"is-style-small"} -->
			<p class="is-style-small">Filed under</p>
			<!-- /wp:paragraph -->

			<!-- wp:tribe/terms {"taxonomyToUse":"post_tag","hasLinks":true,"className":"is-style-pills","style":{"spacing":{"margin":{"top":"var:preset|spacing|20"},"padding":{"top":"0"}}}} /-->

			<!-- wp:group {"metadata":{"name":"Post Author (Footer)","categories":["post-parts"],"patternName":"patterns/post-footer-author"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"className":"is-style-small"} -->
				<p class="is-style-small">About the author</p>
				<!-- /wp:paragraph -->

				<!-- wp:post-author {"avatarSize":96,"showBio":true,"isLink":true,"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} /--></div>
			<!-- /wp:group -->

			<!-- wp:group {"metadata":{"name":"Share Post"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"className":"is-style-small"} -->
				<p class="is-style-small">Share this page</p>
				<!-- /wp:paragraph -->

				<!-- wp:outermost/social-sharing {"iconColor":"base-black","iconColorValue":"#000000","size":"has-normal-icon-size","className":"is-style-logos-only","style":{"spacing":{"margin":{"top":"var:preset|spacing|10"},"blockGap":{"left":"var:preset|spacing|20"}}},"layout":{"type":"flex","justifyContent":"left"}} -->
				<ul class="wp-block-outermost-social-sharing has-normal-icon-size has-icon-color is-style-logos-only" style="margin-top:var(--wp--preset--spacing--10)"><!-- wp:outermost/social-sharing-link {"service":"linkedin","label":"Share on LinkedIn"} /-->

					<!-- wp:outermost/social-sharing-link {"service":"x","label":"Share on X"} /-->

					<!-- wp:outermost/social-sharing-link {"service":"facebook"} /-->

					<!-- wp:outermost/social-sharing-link {"service":"telegram"} /-->

					<!-- wp:outermost/social-sharing-link {"service":"whatsapp"} /--></ul>
				<!-- /wp:outermost/social-sharing --></div>
			<!-- /wp:group --></div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"33.33%"} -->
		<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:heading {"className":"t-body-large"} -->
			<h2 class="wp-block-heading t-body-large">Related Posts</h2>
			<!-- /wp:heading -->

			<!-- wp:tribe/related-posts {"layout":"list","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} /--></div>
		<!-- /wp:column --></div>
	<!-- /wp:columns --></div>
<!-- /wp:group -->

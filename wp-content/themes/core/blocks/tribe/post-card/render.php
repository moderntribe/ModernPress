<?php declare(strict_types=1);

/**
 * @var array $attributes
 */

/**
 * $_GET['editorPostId'] is set when the block is used in the editor via context
 * and is not set when the block is used in the front end, so we don't care too
 * much about escaping it. For the FE, it will just be ignored because we're not
 * performing a REST request.
 */
$post_id = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && isset( $_GET['editorPostId'] ) ? intval( $_GET['editorPostId'] ) : get_the_ID();

// return if no post id
if ( ! $post_id ) {
	return;
}

get_template_part( 'components/cards/post', null, [
	'post_id'    => $post_id,
	'attributes' => $attributes,
] );

<?php
/**
 * Learn documentation source map.
 *
 * This file intentionally keeps only minimal routing metadata so the
 * learn.tri.be site remains the source of truth for instructional copy.
 *
 * @package Tribe\AI
 */

return [
	'last_updated' => '2026-04-29',
	'learn_host'   => 'https://learn.tri.be',
	'blocks'       => [
		'modernpress/vertical-tabs' => [
			'slug'      => 'vertical-tabs-block',
			'path'      => '/blocks/vertical-tabs-block/',
			'rest_base' => 'pages',
			'section'   => 'Blocks',
		],
		'modernpress/vertical-tab'  => [
			'slug'      => 'vertical-tabs-block',
			'path'      => '/blocks/vertical-tabs-block/',
			'rest_base' => 'pages',
			'section'   => 'Blocks',
		],
		'tribe/vertical-tabs'       => [
			'slug'      => 'vertical-tabs-block',
			'path'      => '/blocks/vertical-tabs-block/',
			'rest_base' => 'pages',
			'section'   => 'Blocks',
		],
		'tribe/vertical-tab'        => [
			'slug'      => 'vertical-tabs-block',
			'path'      => '/blocks/vertical-tabs-block/',
			'rest_base' => 'pages',
			'section'   => 'Blocks',
		],
		'mt/vertical-tabs'          => [
			'slug'      => 'vertical-tabs-block',
			'path'      => '/blocks/vertical-tabs-block/',
			'rest_base' => 'pages',
			'section'   => 'Blocks',
		],
		'mt/vertical-tab'           => [
			'slug'      => 'vertical-tabs-block',
			'path'      => '/blocks/vertical-tabs-block/',
			'rest_base' => 'pages',
			'section'   => 'Blocks',
		],
	],
	'fallbacks'    => [
		[
			'id'    => 'core-block-editor-fallback',
			'match' => [
				'prefixes' => [ 'core/' ],
			],
			'doc'   => [
				'slug'      => 'anatomy-of-block-editor',
				'path'      => '/wordpress/anatomy-of-block-editor/',
				'rest_base' => 'pages',
				'section'   => 'WordPress',
			],
		],
	],
];

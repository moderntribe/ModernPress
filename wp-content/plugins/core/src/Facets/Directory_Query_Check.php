<?php declare(strict_types=1);

/**
 * ponytail: assert-based self-check for the pure query-building helpers.
 * Run: php wp-content/plugins/core/src/Facets/Directory_Query_Check.php
 */

namespace Tribe\Plugin\Facets;

require_once __DIR__ . '/Directory_Query.php';
require_once __DIR__ . '/Facet_Types.php';

$layout_types = Facet_Types::normalize_layout_types(
	Facet_Types::FANCY_DROPDOWN,
	Facet_Types::RADIO,
	Facet_Types::DROPDOWN
);
assert( Facet_Types::FANCY_DROPDOWN === $layout_types['top_type'] );
assert( Facet_Types::RADIO === $layout_types['sidebar_type'] );
assert( Facet_Types::DROPDOWN === $layout_types['mobile_type'] );

$legacy_layout_types = Facet_Types::normalize_layout_types(
	Facet_Types::FANCY_DROPDOWN,
	'',
	Facet_Types::CHECKBOXES
);
assert( Facet_Types::FANCY_DROPDOWN === $legacy_layout_types['sidebar_type'], 'missing sidebar type falls back to the legacy/top type' );

$facets = [
	[ 'slug' => 'topic', 'taxonomy' => 'category' ],
	[ 'slug' => 'region', 'taxonomy' => 'post_tag' ],
];

$empty = Directory_Query::build_tax_clauses( $facets, [] );
assert( [] === $empty, 'empty selection yields empty tax_query' );

$one = Directory_Query::build_tax_clauses( $facets, [ 'topic' => [ 'news' ] ] );
assert( 1 === count( $one ), 'single facet yields one clause' );
assert( 'category' === $one[0]['taxonomy'] );
assert( [ 'news' ] === $one[0]['terms'] );
assert( ! isset( $one['relation'] ), 'single facet has no relation key' );

$two = Directory_Query::build_tax_clauses( $facets, [
	'topic'  => [ 'news', 'events' ],
	'region' => [ 'west' ],
] );
assert( 'AND' === ( $two['relation'] ?? null ), 'multiple facets use AND' );
assert( 2 === count( array_filter( $two, 'is_array' ) ), 'two taxonomy clauses' );

/**
 * The index path must agree with the tax_query path: AND across facets,
 * OR within a facet. A post matches only when it has a row for every
 * constrained facet, which is what the HAVING count check enforces.
 */
$index_match = static function ( array $rows, array $selections ): array {
	$active = array_filter( $selections, static fn ( array $terms ): bool => [] !== $terms );

	if ( [] === $active ) {
		return array_values( array_unique( array_column( $rows, 'post_id' ) ) );
	}

	$hits = [];

	foreach ( $rows as $row ) {
		$terms = $active[ $row['facet_slug'] ] ?? [];

		if ( in_array( $row['term_slug'], $terms, true ) ) {
			$hits[ $row['post_id'] ][ $row['facet_slug'] ] = true;
		}
	}

	$matched = [];

	foreach ( $hits as $post_id => $facet_hits ) {
		if ( count( $facet_hits ) === count( $active ) ) {
			$matched[] = $post_id;
		}
	}

	return $matched;
};

$rows = [
	[ 'post_id' => 1, 'facet_slug' => 'topic', 'term_slug' => 'news' ],
	[ 'post_id' => 1, 'facet_slug' => 'region', 'term_slug' => 'west' ],
	[ 'post_id' => 2, 'facet_slug' => 'topic', 'term_slug' => 'news' ],
	[ 'post_id' => 3, 'facet_slug' => 'region', 'term_slug' => 'west' ],
];

assert(
	[ 1 ] === $index_match( $rows, [ 'topic' => [ 'news' ], 'region' => [ 'west' ] ] ),
	'AND across facets: only the post matching both facets'
);
assert(
	[ 1, 2 ] === $index_match( $rows, [ 'topic' => [ 'news' ] ] ),
	'single facet returns every post carrying that term'
);
assert(
	[ 1, 2 ] === $index_match( $rows, [ 'topic' => [ 'news', 'events' ] ] ),
	'OR within a facet'
);
assert(
	[] === $index_match( $rows, [ 'topic' => [ 'missing' ] ] ),
	'no matches yields empty set, which callers must turn into post__in [0]'
);

fwrite( STDOUT, "Directory_Query_Check: OK\n" );

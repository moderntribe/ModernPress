<?php declare(strict_types=1);

/**
 * ponytail: assert-based self-check for the pure query-building helpers.
 * Run: php wp-content/plugins/core/src/Facets/Directory_Query_Check.php
 */

namespace Tribe\Plugin\Facets;

require_once __DIR__ . '/Directory_Query.php';
require_once __DIR__ . '/Facet_Index.php';
require_once __DIR__ . '/Facet_Types.php';

$layout_types = Facet_Types::normalize_layout_types(
	Facet_Types::DROPDOWN,
	Facet_Types::CHECKBOXES,
	Facet_Types::DROPDOWN
);
assert( Facet_Types::DROPDOWN === $layout_types['top_type'] );
assert( Facet_Types::CHECKBOXES === $layout_types['sidebar_type'] );
assert( Facet_Types::DROPDOWN === $layout_types['mobile_type'] );

$legacy_layout_types = Facet_Types::normalize_layout_types( Facet_Types::DROPDOWN, '', Facet_Types::CHECKBOXES );
assert( Facet_Types::DROPDOWN === $legacy_layout_types['sidebar_type'], 'missing sidebar type falls back to the legacy/top type' );

$retired_layout_types = Facet_Types::normalize_layout_types( 'radio', 'fancy_dropdown' );
assert( Facet_Types::CHECKBOXES === $retired_layout_types['top_type'], 'retired types fall back to checkboxes' );
assert( Facet_Types::CHECKBOXES === $retired_layout_types['sidebar_type'], 'an unknown sidebar type inherits the top type' );

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
assert( false === $one[0]['include_children'], 'descendants are expanded up front, so tax_query must not add its own' );

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

		if ( ! in_array( $row['term_slug'], $terms, true ) ) {
			continue;
		}

		$hits[ $row['post_id'] ][ $row['facet_slug'] ] = true;
	}

	$matched = [];

	foreach ( $hits as $post_id => $facet_hits ) {
		if ( count( $facet_hits ) !== count( $active ) ) {
			continue;
		}

		$matched[] = $post_id;
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

/**
 * The index's WHERE builder feeds both the page query and the count query, so
 * a placeholder/value mismatch would silently corrupt one of them.
 */
$single = Facet_Index::build_match_clause( [ 'topic' => [ 'news', 'events' ] ], [] );
assert(
	'( ( i.facet_slug = %s AND i.term_slug IN ( %s, %s ) ) )' === $single['where'],
	'terms within a facet OR together'
);
assert( [ 'topic', 'news', 'events' ] === $single['values'], 'facet slug is bound before its terms' );

$typed = Facet_Index::build_match_clause( [ 'topic' => [ 'news' ] ], [ 'post', 'staff' ] );
assert( str_contains( $typed['where'], 'AND i.post_type IN ( %s, %s )' ), 'post types narrow the match' );
assert( [ 'topic', 'news', 'post', 'staff' ] === $typed['values'], 'post types are bound last' );

$multi = Facet_Index::build_match_clause( [ 'topic' => [ 'news' ], 'region' => [ 'west' ] ], [] );
assert( 2 === substr_count( $multi['where'], 'i.facet_slug = %s' ), 'one clause per constrained facet' );
assert(
	substr_count( $multi['where'], '%s' ) === count( $multi['values'] ),
	'every placeholder has exactly one bound value'
);
assert(
	substr_count( $typed['where'], '%s' ) === count( $typed['values'] ),
	'every placeholder has exactly one bound value, post types included'
);

fwrite( STDOUT, "Directory_Query_Check: OK\n" );

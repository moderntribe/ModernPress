<?php

/**
 * Builds or purges the FacetWP index.
 */
class FacetWP_Integration_WP_CLI
{

    /**
     * Index facet data.
     * 
     * ## OPTIONS
     * 
     * [--ids=<ids>]
     * : Index specific post IDs (comma-separated).
     * 
     * [--facets=<facets>]
     * : Index specific facet names (comma-separated).
     */
    function index( $args, $assoc_args ) {
        $index_all = true;

        if ( isset( $assoc_args['ids'] ) ) {
            if ( empty( $assoc_args['ids'] ) ) {
                WP_CLI::error( 'IDs empty.' );
            }

            $ids = preg_replace( '/\s+/', '', $assoc_args['ids'] );
            $ids = explode( ',', $ids );
            $post_ids = array_filter( $ids, 'ctype_digit' );
            $index_all = false;
        }
        else {
            $post_ids = FWP()->indexer->get_post_ids_to_index();
        }

        if ( isset( $assoc_args['facets'] ) ) {
            if ( empty( $assoc_args['facets'] ) ) {
                WP_CLI::error( 'Facets empty.' );
            }

            $facets = [];
            $facet_names = preg_replace( '/\s+/', '', $assoc_args['facets'] );
            $facet_names = explode( ',', $facet_names );
            foreach ( $facet_names as $name ) {
                $facet = FWP()->helper->get_facet_by_name( $name );
                if ( false !== $facet ) {
                    $facets[] = $facet;
                }
            }

            $index_all = false;
        }
        else {
            $facets = FWP()->helper->get_facets();
        }

        $progress = WP_CLI\Utils\make_progress_bar( 'Indexing:', count( $post_ids ) );

        // prep
        if ( $index_all ) {
            FWP()->indexer->manage_temp_table( 'create' );
        }
        else {
            $assoc_args['pre_index'] = true;
            $this->purge( $args, $assoc_args );
        }

        // manually load value modifiers
        FWP()->indexer->load_value_modifiers( $facets );

        // index
        foreach ( $post_ids as $post_id ) {
            FWP()->indexer->index_post( $post_id, $facets );
            $progress->tick();
        }

        // cleanup
        if ( $index_all ) {
            update_option( 'facetwp_last_indexed', time(), 'no' );
            FWP()->indexer->manage_temp_table( 'replace' );
            FWP()->indexer->manage_temp_table( 'delete' );
        }

        $progress->finish();

        WP_CLI::success( 'Indexing complete.' );
    }


    /**
     * Purge facet data.
     * 
     * ## OPTIONS
     * 
     * [--ids=<ids>]
     * : Purge specific post IDs (comma-separated).
     * 
     * [--facets=<facets>]
     * : Purge specific facet names (comma-separated).
     */
    function purge( $args, $assoc_args ) {
        global $wpdb;

        $table = FWP()->indexer->table;

        if ( ! isset( $assoc_args['ids'] ) && ! isset( $assoc_args['facets'] ) ) {
            $sql = "TRUNCATE TABLE $table";
        }
        else {
            $where = [];

            if ( isset( $assoc_args['ids'] ) ) {
                if ( empty( $assoc_args['ids'] ) ) {
                    WP_CLI::error( 'IDs empty.' );
                }
    
                $ids = preg_replace( '/\s+/', '', ',' . $assoc_args['ids'] );
                $ids = explode( ',', $ids );
                $post_ids = array_filter( $ids, 'ctype_digit' );
                $post_ids = implode( "','", $post_ids );
                $where[] = "post_id IN ('$post_ids')";
            }
    
            if ( isset( $assoc_args['facets'] ) ) {
                if ( empty( $assoc_args['facets'] ) ) {
                    WP_CLI::error( 'Facets empty.' );
                }

                $facet_names = preg_replace( '/\s+/', '', $assoc_args['facets'] );
                $facet_names = explode( ',', $facet_names );
                $facet_names = array_map( 'esc_sql', $facet_names );
                $facet_names = implode( "','", $facet_names );
                $where[] = "facet_name IN ('$facet_names')";
            }

            $sql = "DELETE FROM $table WHERE " . implode( ' AND ', $where );
        }

        $wpdb->query( $sql );

        if ( ! isset( $assoc_args['pre_index'] ) ) {
            WP_CLI::success( 'Purge complete.' );
        }
    }
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'facetwp', 'FacetWP_Integration_WP_CLI' );
}

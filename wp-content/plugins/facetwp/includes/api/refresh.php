<?php

add_action( 'rest_api_init', function() {
    register_rest_route( 'facetwp/v1', '/refresh', [
        'methods' => 'POST',
        'callback' => 'facetwp_api_refresh',
        'permission_callback' => '__return_true'
    ] );
});

function facetwp_api_refresh( $request ) {
    $params = $request->get_params();
    $action = $params['action'] ?? '';

    $valid_actions = [
        'facetwp_refresh',
        'facetwp_autocomplete_load'
    ];

    $valid_actions = apply_filters( 'facetwp_api_valid_actions', $valid_actions );

    if ( in_array( $action, $valid_actions ) ) {
        do_action( $action );
    }

    return [];
}

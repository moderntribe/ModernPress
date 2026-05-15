(function($) {

    $().on('facetwp-refresh', function() {
        if (! FWP.loaded) {
            setup_woocommerce();
        }
    });

    function setup_woocommerce() {

        // Intercept WooCommerce pagination
        $().on('click', '.woocommerce-pagination a', function(e) {
            e.preventDefault();
            var matches = $(this).attr('href').match(/\/page\/(\d+)/);
            if (null !== matches) {
                FWP.paged = parseInt(matches[1]);
                FWP.soft_refresh = true;
                FWP.refresh();
            }
        });

        // Disable sort handler
        $('.woocommerce-ordering').attr('onsubmit', 'event.preventDefault()');

        // Intercept WooCommerce sorting
        $().on('change', '.woocommerce-ordering .orderby', function(e) {
            var qs = new URLSearchParams(window.location.search);
            qs.set('orderby', $(this).val());
            history.pushState(null, null, window.location.pathname + '?' + qs.toString());
            FWP.soft_refresh = true;
            FWP.refresh();
        });
    }
})(fUtil);
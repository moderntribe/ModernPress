(function($) {
    $().on('facetwp-loaded', function() {
        $('.edd-no-js').addClass('facetwp-hidden');
        $('a.edd-add-to-cart').addClass('edd-has-js');
    });
})(fUtil);
var FWP_MAP = FWP_MAP || {};

(function($) {

    FWP_MAP.markersArray = [];
    FWP_MAP.markerLookup = {};
    FWP_MAP.is_filtering = false;
    FWP_MAP.is_zooming = false;
    FWP_MAP.map_loaded = false;

    // Get markers for a given post ID
    FWP_MAP.get_post_markers = function(post_id) {
        var output = [];
        if ('undefined' !== typeof FWP_MAP.markerLookup[post_id]) {
            var arrayOfIndexes = FWP_MAP.markerLookup[post_id];
            for (var i = 0; i < arrayOfIndexes.length; i++) {
                var index = FWP_MAP.markerLookup[post_id][i];
                output.push(FWP_MAP.markersArray[index]);
            }
        }
        return output;
    }

    FWP.hooks.addAction('facetwp/refresh/map', function($this, facet_name) {

        var selected_values = [];

        // Set URL values when Enable Map Filtering is enabled
        if ( FWP_MAP.is_filtering && ! FWP.is_popstate && FWP_MAP.map_loaded ) {
            var currentZoom = FWP_MAP.map.getZoom();
            selected_values = FWP_MAP.map.getBounds().toUrlValue().split(',');
            selected_values.push(currentZoom.toString()); // Add zoom level to be used to correct zooms on back button
        }

        // Using back button when Enable Map Filtering is enabled
        else if ( FWP_MAP.is_filtering && FWP.is_popstate === true ) {

            // Get bounds and zoom from URL.
            // Needed because FWP.facets[facet_name] is undefined. Not sure why, seems related to async loading.
            FWP.loadFromHash();

            // Load map from URL as long as there are URL values when using back button
            if ( FWP.facets[facet_name] && FWP.facets[facet_name].length > 0 ) {
                do_refresh_from_url(FWP.facets[facet_name]);
            }

            // On the last back button refresh, when URL is empty, reset Enable Map Filtering button and is_popstate
            // Prevents further complications when starting anew with zooming/panning while Enable Map Filtering is enabled
            else {
                FWP.is_popstate = undefined;
                $('.facetwp-map-filtering').trigger('click');
            }
        }

        FWP.facets[facet_name] = selected_values;
        FWP.frozen_facets[facet_name] = 'hard';

    });

    FWP.hooks.addAction('facetwp/reset', function() {
        $.each(FWP.facet_type, function(type, name) {
            if ('map' === type) {
                FWP.frozen_facets[name] = 'hard';
            }
        });
    });

    FWP.hooks.addFilter('facetwp/selections/map', function(label, params) {
        return FWP_JSON['map']['resetText'];
    });

    function do_refresh() {
        if (FWP_MAP.is_filtering && ! FWP_MAP.is_zooming) {
            FWP.autoload();
        }

        FWP_MAP.is_zooming = false;
    }

    function do_refresh_from_url( selected_values ) {

        const sw = {
            lat: parseFloat(selected_values[0]),
            lng: parseFloat(selected_values[1])
        };
        const ne = {
            lat: parseFloat(selected_values[2]),
            lng: parseFloat(selected_values[3])
        };

        const popstate_bounds = new google.maps.LatLngBounds(sw, ne);

        FWP_MAP.map.fitBounds( popstate_bounds );

        FWP_MAP.is_zooming = true; // Prevent do_refresh()

        // fitBounds should work by itself in theory, but zooms are off for some reason (Google related I think)
        // So we get the zoom from the URL also and set it after the fitBounds
        // setZoom does not work directly because of fitbounds triggers async zooming, so added in a listener
        google.maps.event.addListenerOnce(FWP_MAP.map, 'bounds_changed', function() {
            FWP_MAP.map.setZoom( Number(selected_values[4]) );
        });
    }

    $().on('click', '.facetwp-map-filtering', function() {
        var $this = $(this);

        if ($this.hasClass('enabled')) {
            $this.text(FWP_JSON['map']['filterText']);
            FWP_MAP.is_filtering = false;
            FWP.autoload();
        }
        else {
            $this.text(FWP_JSON['map']['resetText']);
            FWP_MAP.is_filtering = true;
            FWP.autoload();
        }

        $this.toggleClass('enabled');
    });

    FWP_MAP.createIcon = function(icon) { // back compat for icon arg for marker pin
        const imgTag = document.createElement("img");
        if ( 'string' == typeof icon ) {
            imgTag.src = icon;
            return imgTag;
        }
        if ( 'string' == typeof icon.url  ) {
            imgTag.src = icon.url;
            if ( icon.scaledSize ) {
                if ( icon.scaledSize.width ) imgTag.width = icon.scaledSize.width;
                if ( icon.scaledSize.height ) imgTag.height = icon.scaledSize.height;
            }
            return imgTag;
        }
        return false;
    }

    FWP_MAP.init = async function() {
        if ('undefined' === typeof FWP.settings.map || '' === FWP.settings.map) {
            return;
        }

        if ('object' !== typeof google || 'object' !== typeof google.maps) {
            return;
        }

        var config = FWP.settings.map.config;

        const { Map, InfoWindow } = await google.maps.importLibrary("maps");
        const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");

        // back compat for example
        // https://facetwp.com/help-center/facets/facet-types/map/advanced-map-customizations/#example-4-change-the-marker-icon-when-an-infowindow-is-open
        AdvancedMarkerElement.prototype.setIcon = function(icon) {
            if ( !icon ) {
                let pin = new PinElement();
                this.content = pin.element;
            } else {
                newIcon = FWP_MAP.createIcon(icon);
                this.content = newIcon;
            }
        }

        if (! FWP_MAP.map_loaded ) {

            FWP_MAP.map = new Map(document.getElementById('facetwp-map'), FWP.settings.map.init);
            FWP_MAP.infoWindow = new InfoWindow();

            FWP_MAP.map.addListener('dragend', function() {
                do_refresh();
            });

            FWP_MAP.map.addListener('zoom_changed', function() {
                do_refresh();
            });

            window.addEventListener('resize', FWP.helper.debounce(function() {
                var center = FWP_MAP.map.getCenter();
                google.maps.event.trigger(FWP_MAP.map, 'resize');
                FWP_MAP.map.setCenter(center);
            }, 500));

            google.maps.event.addListener(FWP_MAP.map, 'click', function() {
                FWP_MAP.infoWindow.close();
            });

            FWP_MAP.oms = new OverlappingMarkerSpiderfier(FWP_MAP.map, config.spiderfy);

            // If first page load has map URL values
            var selected_values = FWP.facets[FWP_JSON.map.facet_name];
            if (selected_values && selected_values.length > 0) {

                FWP_MAP.is_filtering = true; // Button was already enabled with PHP
                do_refresh_from_url( selected_values );
            }

            FWP_MAP.map_loaded = true;        

        } else if ( 'undefined' !== FWP.is_popstate && false === FWP.is_popstate ) {

            // restore map
            let mapHTML = FWP_MAP.map.getDiv();
            let mapContainer = document.getElementById( 'facetwp-map');
            let mapParent = mapContainer.parentElement;
            mapParent.replaceChild(mapHTML,mapContainer);
            clearOverlays(); // Prevent cluster count issue when using back button and reset

        } else {
            clearOverlays();
        }

        // this needs to re-init on each refresh
        FWP_MAP.bounds = new google.maps.LatLngBounds();
        FWP_MAP.contentCache = {};

        $.each(FWP.settings.map.locations, function(obj, idx) {
            
            const args = { position: obj.position, zIndex : obj.zIndex, title : obj.title }; // advanced marker properties
            args.gmpDraggable = obj.draggable ?? obj.gmpDraggable; // back compat for legacy draggable 

            // setting content in facetwp_map/marker/content short circuits other settings for marker
            let content = FWP.hooks.applyFilters('facetwp_map/marker/content', null, obj, PinElement);

            if ( !content && obj.markerHtml ) { // new argument for creating pin from custom html
            
                content = document.createElement("div");
                content.innerHTML = obj.markerHtml;

            }

            if ( !content && obj.icon ) { // back compat for icon setting for legacy markers
                content = FWP_MAP.createIcon(obj.icon);

            }

            if ( !content ) { // create a new pinv

                const { label = '' } = obj;
                const { glyphHtml = '', pinClass = '', ...pinOptions } = obj.pinOptions || {};

                if ( label ) { // back compat for label setting for legacy markers    
                    pinOptions.glyph = label.text;
                    if ( label.color ) pinOptions.glyphColor = label.color;
                }

                if ( glyphHtml ) { // new argument for HTML in glyph
                    const glyph = document.createElement("div");
                    glyph.innerHTML = glyphHtml;
                    pinOptions.glyph = glyph;
                }

                let pin = new PinElement(pinOptions);

                if ( pinClass ) { // new argument for class name in pin
                    pin.element.classList.add(pinClass);
                }

                content = pin.element;

            }

            args.content = content;

            const marker = new AdvancedMarkerElement(args);
            
            marker.post_id = obj.post_id;
            marker.infoWindowContent = obj.infoWindowContent;
            marker.clickable = 'undefined' !== typeof obj.clickable ? obj.clickable : true;

            var isProgrammaticClick = false;

            google.maps.event.addListener(marker, 'spider_click', function() {

                if ( false == marker.clickable ) {
                    // Custom click handler
                    FWP.hooks.doAction('facetwp_map/marker/click', marker);
                    return;
                }

                if ('undefined' !== typeof marker.infoWindowContent) {
                    FWP_MAP.infoWindow.setContent(marker.infoWindowContent);
                }
                else if ('undefined' === typeof FWP_MAP.contentCache[marker.post_id]) {
                    FWP_MAP.infoWindow.setContent('Loading...');
                    FWP_MAP.infoWindow.open(FWP_MAP.map, marker);

                    return $.post(FWP_JSON.map.ajaxurl, {
                        action: 'facetwp_map_marker_content',
                        facet_name: FWP_JSON.map.facet_name,
                        post_id: marker.post_id
                    }, {
                        dataType: 'text',
                        contentType: 'application/x-www-form-urlencoded',
                        done: (resp) => {
                            FWP_MAP.contentCache[marker.post_id] = resp;
                            FWP_MAP.infoWindow.setContent(resp);
                            FWP.hooks.doAction('facetwp_map/marker/click', marker);

                            // Trigger extra click to reposition marker/infowindow withint viewport
                            // Check if the click event was triggered programmatically, to prevent recursion.
                            if (!isProgrammaticClick) {
                                isProgrammaticClick = true;
                                // Trigger a click programmatically after the marker has been clicked
                                google.maps.event.trigger(marker, 'click');
                                isProgrammaticClick = false; // Reset the flag after the programmatic click
                            }
                        }
                    });
                }
                else {
                    var data = FWP_MAP.contentCache[marker.post_id];
                    FWP_MAP.infoWindow.setContent(data);
                }

                FWP_MAP.infoWindow.open(FWP_MAP.map, marker);

                // Custom click handler
                FWP.hooks.doAction('facetwp_map/marker/click', marker);
            });

            // Custom mouseover handler
            marker.content.addEventListener('mouseenter', function(e) {
                FWP.hooks.doAction('facetwp_map/marker/mouseover', marker);
            });

            // Custom mouseout handler
            marker.content.addEventListener('mouseleave', function(e) {
                FWP.hooks.doAction('facetwp_map/marker/mouseout', marker);
            });

            FWP_MAP.oms.addMarker(marker);
            FWP_MAP.markersArray.push(marker);
            FWP_MAP.bounds.extend(marker.position);

            // Create an object to lookup markers based on post ID
            if ( 'undefined' !== typeof FWP_MAP.markerLookup[obj.post_id]) {
                FWP_MAP.markerLookup[obj.post_id].push(idx);
            }
            else {
                FWP_MAP.markerLookup[obj.post_id] = [idx];
            }

            FWP.hooks.doAction('facetwp_map/marker/added', marker, obj);
        });

        var has_results = FWP.settings.map.locations.length > 0;
        var fit_bounds = FWP.hooks.applyFilters('facetwp_map/fit_bounds', ! FWP_MAP.is_filtering);

        if (fit_bounds && has_results) {
            FWP_MAP.map.fitBounds(FWP_MAP.bounds);
        }
        else if (! FWP_MAP.is_filtering && (0 !== config.default_lat || 0 !== config.default_lng)) {
            FWP_MAP.map.setCenter({
                lat: parseFloat(config.default_lat),
                lng: parseFloat(config.default_lng)
            });
            FWP_MAP.is_zooming = true;
            FWP_MAP.map.setZoom(config.default_zoom);
        }

        if ( 'undefined' != typeof markerClusterer && 'undefined' !== typeof config.cluster ) {
             // default args
            var clusterargs = {
                algorithmOptions: {
                    // backwards compatibility
                    maxZoom: config.cluster.maxZoom, 
                    minPoints: config.cluster.minimumClusterSize 
                },
            };

            if ( false == config.cluster.zoomOnClick ) {
                clusterargs.onClusterClick = false; // backwards compatibility
            }

            clusterargs = Object.assign( FWP.hooks.applyFilters('facetwp_map/clusterer', clusterargs, config.cluster ), { markers: FWP_MAP.markersArray, map: FWP_MAP.map } );

            FWP_MAP.mc = new markerClusterer.MarkerClusterer( clusterargs );
        }

        $().trigger('facetwp-maps-loaded');
    };

    FWP.hooks.addFilter('facetwp_map/clusterer', function (clusterargs, clusterconfig) {

        if (typeof clusterconfig.imagePath !== 'undefined' && clusterconfig.imagePath !== '' && typeof clusterconfig.imageExtension !== 'undefined' && clusterconfig.imageExtension !== '') {

            // Backwards compatible custom cluster icon set with config.cluster.imagePath and config.cluster.imageExtension.    
            var imageRenderer = {

                render({ count, position }, stats) {

                    const sizes = [53, 56, 66, 78, 90];
                    let i = 0;
                    var dv = count;
                    while (dv !== 0) {
                        dv = parseInt(dv / 10, 10);
                        i++;
                    }
                    i = Math.min(i, 5);

                    const size = sizes[i - 1];

                    const imgsrc = clusterconfig.imagePath + i + '.' + clusterconfig.imageExtension;

                    const title = `Cluster of ${count} markers`;

                    const zIndex = 1000000 + count; // There is no equivalent of google.maps.Marker.MAX_ZINDEX. This is the legacy value.

                    const pinImgString = `
                        <div style="position: relative;">
                            <img src="${imgsrc}" width="${size}" height="${size}" alt="clustericon"/>
                            <span class="count" style="position: absolute; left: 0; top: 0; display: block; width: 100%; text-align: center; font-size: 11px; line-height:${size}px; color: #000000; font-weight: bold;">${count}</span>
                        </div>`;

                    const parser = new DOMParser();
                    const pinImg = parser.parseFromString(pinImgString, 'text/html').body.firstChild;

                    const clusterOptions = {
                        map : clusterconfig.map,
                        position,
                        zIndex,
                        title,
                        content: pinImg,
                    };

                    return new google.maps.marker.AdvancedMarkerElement(clusterOptions);
                }
            }
    
            clusterargs.renderer = imageRenderer;
    
        } else if (typeof clusterconfig.styles !== 'undefined' && clusterconfig.styles !== '') {
    
            // Backwards compatible custom cluster icon set with args['config']['cluster']['styles'][].  
            var stylesRenderer = {
    
                render({ count, position }, stats) {
                    let i = 0;
                    var dv = count;
                    while (dv !== 0) {
                        dv = parseInt(dv / 10, 10);
                        i++;
                    }
                    i = Math.min(i, 5);

                    const styles =  clusterconfig.styles[i - 1];
                    const width = styles.width;
                    const height = styles.height;
                    const textColor = styles.textColor ?? '#000000';
                    const textSize = styles.textSize ?? 11;

                    const imgsrc = styles.url;

                    const title = `Cluster of ${count} markers`;

                    const zIndex = 1000000 + count; // There is no equivalent of google.maps.Marker.MAX_ZINDEX. This is the legacy value.

                    const pinImgString = `
                        <div style="position: relative;">
                            <img src="${imgsrc}" width="${width}" height="${height}" alt="clustericon"/>
                            <span class="count" style="position: absolute; left: 0; top: 0; display: block; width: 100%; text-align: center; font-size: ${textSize}px; line-height:${height}px; color: ${textColor}; font-weight: bold;">${count}</span>
                        </div>`;

                    const parser = new DOMParser();
                    const pinImg = parser.parseFromString(pinImgString, 'text/html').body.firstChild;

                    const clusterOptions = {
                        map : clusterconfig.map,
                        position,
                        zIndex,
                        title,
                        content: pinImg,
                    };

                    return new google.maps.marker.AdvancedMarkerElement(clusterOptions);
                }
            }
    
            clusterargs.renderer = stylesRenderer;
        }
    
        return clusterargs;
    
    });

    $().on('facetwp-loaded', function() {
        FWP_MAP.init();
    });

    // Clear markers
    function clearOverlays() {
        FWP_MAP.oms.removeAllMarkers();
        FWP_MAP.markersArray = [];
        FWP_MAP.markerLookup = {};

        // clear clusters
        if ('undefined' !== typeof FWP_MAP.mc) {
            FWP_MAP.mc.clearMarkers();
        }
    }

})(fUtil);

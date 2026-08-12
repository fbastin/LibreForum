<?php

// This script is used to display an OpenStreetMap from within an <iframe>
// in the page. It's a generic tool that can be used for displaying
// all required kinds of maps (editing, viewing and overviews).
//
// The script takes the following parameters for modifying the
// behaviour of the map tool:
//
// type        The type of map. This is one of:
//             - location-editor
//             - map-editor
//             - viewer
//             - plotter
//
// <other>     Other parameters are used for setting the map state
//             (longitude, latitude, zoom, marker, streetview, etc.)
//

// Defaults for the arguments.
$args = array(
    "type"                 => "viewer",
    "reset_latitude"       => 40,
    "reset_longitude"      => -20,
    "reset_zoom"           => 1,
    "reset_type"           => 'roadmap',
    "map_latitude"         => '',
    "map_longitude"        => '',
    "map_zoom"             => '',
    "map_type"             => '',
    "marker_latitude"      => '',
    "marker_longitude"     => '',
    "streetview_latitude"  => '',
    "streetview_longitude" => '',
    "streetview_zoom"      => '',
    "streetview_heading"   => '',
    "streetview_pitch"     => '',
    "geoloc_country"       => '',
    "geoloc_city"          => ''
);

// Grab and merge args from the request.
foreach ($PHORUM['args'] as $k => $v) {
    if (array_key_exists($k, $args) && $v !== '') {
        $args[$k] = $v;
    }
}

// Check if all required arguments are set.
foreach ($args as $k => $v) {
    if (is_null($args[$k])) die("Missing map parameter \"$k\"");
}

// Easy access to the language data.
$lang = $PHORUM["DATA"]["LANG"]["mod_openstreetmap"];

?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style type="text/css">
      html { height: 100% }
      body { height: 100%; margin: 0px; padding: 0px }
      #map { height: 100%; width: 100%; }
    </style>
    <title>OpenStreetMap interface</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

    <script type="text/javascript">
    //<![CDATA[

    // -----------------------------------------------------------------
    // Initialize variables
    // -----------------------------------------------------------------

    var api_language = '<?php print addslashes($lang['geocoding_lang']) ?>';

    var marker = null;

    var geoloc_city    = '<?php print addslashes($args['geoloc_city']) ?>';
    var geoloc_country = '<?php print addslashes($args['geoloc_country']) ?>';

    <?php if ($args['type'] == 'location-editor' ||
              $args['type'] == 'map-editor') { ?>
    var reset_state =
    {
        map_latitude         : <?php print addslashes($args['reset_latitude']) ?>,
        map_longitude        : <?php print addslashes($args['reset_longitude']) ?>,
        map_zoom             : <?php print addslashes($args['reset_zoom']) ?>,
        map_type             : '<?php print addslashes($args['reset_type']) ?>'
    };
<?php } ?>
    var start_state =
    {
        <?php if (isset($args['map_latitude']) &&
                        $args['map_latitude'] !== '') { ?>
        map_latitude         : <?php print addslashes($args['map_latitude']) ?>,
        map_longitude        : <?php print addslashes($args['map_longitude']) ?>,
        map_zoom             : <?php print addslashes($args['map_zoom']) ?>,
        map_type             : '<?php print addslashes($args['map_type']) ?>',
        <?php } ?>

        <?php if (isset($args['marker_latitude']) &&
                        $args['marker_latitude'] !== '') { ?>
        marker_latitude      : <?php print addslashes($args['marker_latitude']) ?>,
        marker_longitude     : <?php print addslashes($args['marker_longitude']) ?>,
        <?php } ?>
    };

<?php if ($args["type"] == 'plotter') { ?>
    var ploticon = L.icon({
        iconUrl: "<?php print htmlspecialchars($PHORUM["http_path"]) .
                     "/mods/openstreetmap/maptool/marker.png" ?>",
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        popupAnchor: [0, -16]
    });
<?php } ?>

    // -----------------------------------------------------------------
    // Initialize the Map
    // -----------------------------------------------------------------

    var map;
<?php if ($args["type"] == 'plotter') { ?>
    var markerClusterGroup;
<?php } ?>

    function initialize()
    {
      // Create the map object.
      map = L.map('map', {
          zoomControl: true,
          scrollWheelZoom: true
      }).setView([40, -20], 1);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      <?php if ($args["type"] == 'plotter') { ?>
      markerClusterGroup = L.markerClusterGroup();
      map.addLayer(markerClusterGroup);
      <?php } ?>

      // Initialize the start position of the map.
      startMap();

<?php if ($args["type"] == 'location-editor' ||
          $args['type'] == 'map-editor') { ?>
      // Setup events for keeping track of edit operations.
      map.on('zoomend', function () {
          raiseMapToolChangeEvent();
      });
      map.on('moveend', function () {
          raiseMapToolChangeEvent();
      });
      // maptypeid_changed not directly applicable in OSM simple setup

  <?php if ($args['type'] == 'location-editor') { ?>
      map.on('click', function (ev) {
          placeEditMarker(ev.latlng);
          lookupGeolocationInfo(ev.latlng);
          raiseMapToolChangeEvent();
      });
  <?php } ?>
<?php } ?>
      raiseGoogleMapReadyEvent();
    }

    // -----------------------------------------------------------------
    // Functions for changing the state of the map
    // -----------------------------------------------------------------

    // Go to the map start position.
    function startMap()
    {
        <?php if ($args['type'] == 'location-editor' ||
                  $args['type'] == 'map-editor') { ?>
        setMapState(reset_state);
        <?php } ?>
        setMapState(start_state);
    }

    <?php if ($args['type'] == 'location-editor' ||
              $args['type'] == 'map-editor') { ?>
    // Go to the map reset position.
    function resetMap()
    {
        setMapState(reset_state);
        geoloc_city    = null;
        geoloc_country = null;
        raiseMapToolChangeEvent(true);
    }
    <?php } ?>

<?php if ($args['type'] == 'location-editor' ||
          $args['type'] == 'map-editor') { ?>
    // Search for a textual location on the map, using Nominatim
    // geocoder to translate the text into map coordinates.
    function searchLocation(description)
    {
        setLoading(true);
        var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(description) + '&accept-language=' + api_language;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                setLoading(false);
                if (data && data.length > 0) {
                    var result = data[0];
                    var point = L.latLng(result.lat, result.lon);
                    map.panTo(point);
                    <?php if ($args["type"] == 'location-editor') { ?>
                    placeEditMarker(point);
                    lookupGeolocationInfo(point);
                    <?php } ?>
                    raiseMapToolChangeEvent();
                } else {
                    alert(document.getElementById('maptool_msg_notfound').innerHTML);
                }
            })
            .catch(error => {
                setLoading(false);
                console.error('Error:', error);
                alert('Geocoding error');
            });
        return false;
    }

  <?php if ($args["type"] == 'location-editor') { ?>
    // Put an editable marker on the map.
    function placeEditMarker(point)
    {
        // Create a marker in case there is no marker yet.
        if (! marker)
        {
            marker = L.marker(point, {
                draggable: true
            }).addTo(map);

            // If the user drags the marker, then fire a change event.
            marker.on('dragend', function () {
                raiseMapToolChangeEvent();
                lookupGeolocationInfo(marker.getLatLng());
            });
        }
        // If we already have a marker, then move it to the new location.
        else {
            marker.setLatLng(point);
        }

        raiseMapToolChangeEvent();
    }

    function lookupGeolocationInfo(point)
    {
        geoloc_city = null;
        geoloc_country = null;

        var url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' + point.lat + '&lon=' + point.lng + '&accept-language=' + api_language;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.address) {
                    geoloc_country = data.address.country || null;
                    geoloc_city = data.address.city || data.address.town || data.address.village || data.address.hamlet || null;
                    raiseMapToolChangeEvent();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
  <?php } ?>
<?php } ?>

<?php if ($args['type'] === 'plotter' || $args['type'] === 'viewer') { ?>
    // Put a view marker on the map.
    function placeViewMarker(point, info)
    {
        // Create the marker.
        var m = L.marker(point, {
            <?php if ($args['type'] === 'plotter') { ?>
            icon      : ploticon,
            <?php } ?>
            draggable : false
        });

        <?php if ($args['type'] === 'plotter') { ?>
        markerClusterGroup.addLayer(m);
        <?php } else { ?>
        m.addTo(map);
        <?php } ?>

        // If info is provided, then setup an info window for the marker.
        if (info) {
            m.bindPopup(info);
        }
        return m;
    }
<?php } ?>

    // -----------------------------------------------------------------
    // Functions for communication with the parent window
    // -----------------------------------------------------------------

    function raiseGoogleMapReadyEvent()
    {
        // Callback to notify the parent that the map is ready.
        if (parent.onGoogleMapReady) {
            parent.onGoogleMapReady(window, map);
        }
    }

    /**
     * Retrieve the current map state.
     *
     * @return object
     *   An object, describing the current state of the map.
     *   This object can be passed to setMapState() to restore
     *   the map state.
     */
    function getMapState()
    {
        var state = { };

        // Compile map state.
        var center = map.getCenter();
        state.map_latitude   = center.lat;
        state.map_longitude  = center.lng;
        state.map_zoom       = map.getZoom();
        state.map_type       = 'roadmap'; // Default for OSM

        // Compile streetview state (Not supported in OSM)
        state.streetview_latitude   = null,
        state.streetview_longitude  = null,
        state.streetview_heading    = null,
        state.streetview_pitch      = null,
        state.streetview_zoom       = null

        // Compile marker state.
        var marker_pos = marker ? marker.getLatLng() : null;
        if (marker && marker_pos) {
            state.marker_latitude   = marker_pos.lat,
            state.marker_longitude  = marker_pos.lng
        } else {
            state.marker_latitude   = null,
            state.marker_longitude  = null
        }

        // Compile geolocation info state.
        state.geoloc_country = geoloc_country;
        state.geoloc_city    = geoloc_city;

        return state;
    }

    /**
     * Set the map state.
     *
     * @param object
     *   An object, describing the current state of the map.
     *   This is an object, as created by getMapState().
     */
    function setMapState(state)
    {
        // Keep track of the point that we want to be visible in the map.
        var focus_point = null;

        // Set the map center and zoom.
        if (state.map_latitude  !== null      &&
            state.map_latitude  !== undefined &&
            state.map_longitude !== null      &&
            state.map_longitude !== undefined) {
            var center = L.latLng(state.map_latitude, state.map_longitude);
            var zoom = (state.map_zoom !== null && state.map_zoom !== undefined) ? state.map_zoom : map.getZoom();
            map.setView(center, zoom);
            focus_point = center;
        }

        // Set the marker state.
        if (state.marker_latitude  !== null      &&
            state.marker_latitude  !== undefined &&
            state.marker_longitude !== null      &&
            state.marker_longitude !== undefined)
        {
            var point = L.latLng(state.marker_latitude, state.marker_longitude);
            <?php if ($args["type"] == 'location-editor') { ?>
            placeEditMarker(point);
            <?php } ?>

            <?php if ($args["type"] == 'viewer') { ?>
            placeViewMarker(point);
            <?php } ?>

            focus_point = point;
        }
        else if (marker) {
            map.removeLayer(marker);
            marker = null;
        }

        // Streetview not supported.
        
        // Make sure the focus point's position is visible.
        if (focus_point) {
            map.panTo(focus_point);
        }
    }

<?php if ($args['type'] == 'location-editor' ||
          $args['type'] == 'map-editor') { ?>
    // Callback to the parent window.
    function raiseMapToolChangeEvent(reset)
    {
        if (parent.onMapToolChange) {
            parent.onMapToolChange(getMapState());
        };
    }
<?php } ?>

<?php if ($args['type'] == 'location-editor' ||
          $args['type'] == 'map-editor') { ?>
    function geoLocationSupported()
    {
        if (navigator.geolocation) return true;
        return false;
    }

    function doGeoLocationCallback(point)
    {
        setLoading(false);
        if (!point) {
            alert(document.getElementById('maptool_msg_notfound').innerHTML);
        } else {
            map.panTo(point);
            <?php if ($args["type"] == 'location-editor') { ?>
            placeEditMarker(point);
            lookupGeolocationInfo(point);
            <?php } ?>
            raiseMapToolChangeEvent();
        }
    }

    function doGeoLocation()
    {
        // Try W3C Geolocation
        if (navigator.geolocation)
        {
            setLoading(true);
            navigator.geolocation.getCurrentPosition(function(position) {
                setLoading(false);
                var point = L.latLng(
                    position.coords.latitude,
                    position.coords.longitude
                );
                doGeoLocationCallback(point);
            }, function() {
                setLoading(false);
                doGeoLocationCallback(null);
            });
        }
        else {
            doGeoLocationCallback(null);
        }
    }

    // A loading mask with some timers to prevent it from becoming
    // too flashy (as in "flashing".)
    var loading_timer = null;
    function setLoading(state)
    {
        if (loading_timer) {
          clearTimeout(loading_timer);
          loading_timer = null;
        }

        var overlay = document.getElementById('loading_overlay');
        if (state) {
            loading_timer = setTimeout(function () {
              overlay.style.display = 'block';
            }, 500);
        } else {
            loading_timer = setTimeout(function () {
              overlay.style.display = 'none';
            }, 500);
        }
    }
<?php } ?>

    // Compatibility shim for plotter.php
    var google = {
        maps: {
            LatLng: function(lat, lng) {
                return L.latLng(lat, lng);
            },
            LatLngBounds: function() {
                var _bounds = null;
                this.extend = function(latlng) {
                    if (!_bounds) {
                        _bounds = L.latLngBounds(latlng, latlng);
                    } else {
                        _bounds.extend(latlng);
                    }
                };
                this.getLeafletBounds = function() {
                    return _bounds;
                };
            }
        }
    };
    var fluster = {
        initialize: function() {
            // Nothing needed here for markerClusterGroup
        }
    };

    //]]>
    </script>
  </head>

  <body onload="initialize()">

    <noscript>
      <div style="border: 1px solid red; padding: 10px">
      <?php print $lang["IncompatibleBrowser"] ?>
      </div>
    </noscript>

    <span id="maptool_msg_notfound" style="display:none">
      <?php print $lang["NoSearchResults"] ?>
    </span>

    <div id="map"></div>

    <div id="loading_overlay"
         style="position: absolute;
                display: none;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                z-index: 1000;
                opacity: 0.20;
                filter: progid:DXImageTransform.Microsoft.Alpha(opacity=20);
                background: #000 url(<?php print $PHORUM["http_path"] ?>/mods/openstreetmap/maptool/loader.png) center center no-repeat"></div>
  </body>

</html>

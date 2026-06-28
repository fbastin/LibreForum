<?php
// This file can be included for showing a editable map, which can be
// used to plot multiple markers.
//
// The viewer.php script implements all that we need.
// That script will exclude the parts that are not needed
// for this plotter script.

include dirname(__FILE__) . '/viewer.php';
?>

<script type="text/javascript">
//<![CDATA[

var markers = [
<?php
  $first = TRUE;
  foreach ($PHORUM['maptool']['plot'] as $marker) {
      if (!$first) {
          print ",\n";
      }
      $first = FALSE;
      print "[{$marker['latitude']}, {$marker['longitude']}, " .
            "'" . addslashes($marker['info']) . "']";
  }
?>
];

function onGoogleMapReady(frame, map)
{
    var latlngs = [];

    for (var i = 0; i < markers.length; i++)
    {
        var m = markers[i];
        var point = frame.L.latLng(m[0], m[1]);
        frame.placeViewMarker(point, m[2]); 
        latlngs.push(point);
    }

    if (latlngs.length > 0) {
        var bounds = frame.L.latLngBounds(latlngs);
        map.fitBounds(bounds);
    }
}
// ]]>
</script>

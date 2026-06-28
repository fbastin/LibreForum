<?php
// This file can be included for showing a editable map on a
// Phorum page. It's not strictly neccessary to use this script
// if an editor is needed, but it's probably the easiest way to
// arrange for one.
//
// The script expects that it is included within an HTML <form>.
// It sets up form fields, which will be automatically updated
// with the state of the map.

if (!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

// Easy access to the language strings. We might have to load the
// language file ourselves, in case this code is included from the
// admin interface.
if (! isset($PHORUM["DATA"]["LANG"]["mod_openstreetmap"])) {
    include dirname(__FILE__) . "/../lang/english.php";
}
$lang = $PHORUM["DATA"]["LANG"]["mod_openstreetmap"];

// Determine width and height for the iframe.
$width  = !empty($PHORUM["maptool"]["width"]) 
        ? $PHORUM["maptool"]["width"] : "100%";
$height = !empty($PHORUM["maptool"]["height"])
        ? $PHORUM["maptool"]["height"] : "400px";

// Generate the URL to use for the map that is loaded in the iframe.
$mapurl = "{$PHORUM["http_path"]}/mods/openstreetmap/maptool/mapframe.php?";
foreach ($PHORUM['maptool'] as $key => $val) {
    $mapurl .= "&" . urlencode($key) . "=" . urlencode($val);
}

if (!isset($PHORUM["maptool"]["edittype"]))
    $PHORUM["maptool"]["edittype"] = "marker";
?>

<!-- A surrounding div for the maptool -->
<div id="maptool">

<!-- Hidden form fields, which represent the state of the map tool -->

<input type="hidden" name="map_latitude"         id="map_latitude"         value="" />
<input type="hidden" name="map_longitude"        id="map_longitude"        value="" />
<input type="hidden" name="map_zoom"             id="map_zoom"             value="" />
<input type="hidden" name="map_type"             id="map_type"             value="" />
<input type="hidden" name="marker_latitude"      id="marker_latitude"      value="" />
<input type="hidden" name="marker_longitude"     id="marker_longitude"     value="" />
<input type="hidden" name="streetview_latitude"  id="streetview_latitude"  value="" />
<input type="hidden" name="streetview_longitude" id="streetview_longitude" value="" />
<input type="hidden" name="streetview_heading"   id="streetview_heading"   value="" />
<input type="hidden" name="streetview_pitch"     id="streetview_pitch"     value="" />
<input type="hidden" name="streetview_zoom"      id="streetview_zoom"      value="" />

<!-- A search form, where the user can search for a locations based -->
<!-- on a textual description of the location -->

<div id="maptool_topbar" style="margin-bottom: 5px">
  <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td width="100%">
        <input type="text" name="search" value="" style="width:95%"/>
      </td>
      <td style="white-space: nowrap">
        <input type="submit" value="<?php print $lang["Search"] ?>"
         onclick="
         var mapframe = document.getElementById('maptool_iframe');
         var mapdoc = mapframe.contentWindow || mapframe.document;
         mapdoc.searchLocation(this.form.search.value);
         return false" />
      </td>
    </tr>
  </table>
</div>

<!-- The Google map is loaded within an iframe. The iframe is used to -->
<!-- be able to run the map code from an HTML5 page, without having -->
<!-- to make the page on which the map is using HTML5 as well. -->

<div id="maptool_map" style="margin-bottom: 5px">
<iframe
  id="maptool_iframe"
  src="<?php print htmlspecialchars($mapurl) ?>"
  width="<?php print htmlspecialchars($width) ?>"
  height="<?php print htmlspecialchars($height) ?>"
  marginwidth="0"
  marginheight="0"
  scrolling="no"
  frameborder="0" ></iframe>
</div>

<!-- Displaying of the map state and a button to clear the active marker -->

<div id="maptool_bottombar">

  <input style="float:right" type="button" name="maptool_clear" onclick="var mapframe = document.getElementById('maptool_iframe'); var mapdoc = mapframe.contentWindow || mapframe.document; mapdoc.resetMap(); return false" value="<?php print $lang["Clear"] ?>"/>

  <div style="font-size: 9px; padding-top: 5px">
    <?php print $lang["Location"] ?>:
    <span id="maptool_location_display" style="font-size: 9px">
      <?php print $lang["NoLocationSet"] ?>
    </span>
  </div>

</div>

<!-- The JavaScript code for the map editor -->
<script type="text/javascript">
//<![CDATA[

// Callback function that is called from the iframe map,
// to pass on a new map state. The map state properties are copied
// to the hidden fields in this editor.
function onMapToolChange(state)
{
    var display    = document.getElementById('maptool_location_display');
    var f_state    = document.getElementById('maptool_state');

    var fields = [
        'map_latitude',
        'map_longitude',
        'map_zoom',
        'map_type',
        'marker_latitude',
        'marker_longitude',
        'streetview_latitude',
        'streetview_longitude',
        'streetview_heading',
        'streetview_pitch',
        'streetview_zoom',
    ];

    for (var i = 0; i < fields.length; i++)
    {
        var name = fields[i];
        var field = document.getElementById(name); 
        field.value = state[name] !== undefined && state[name] !== null
                    ? state[name] : '';
        console.warn("SET " + name + " = " + field.value);
    }
}
//]]>
</script>

</div>

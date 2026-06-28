For running LightBox, some JavaScript libraries and CSS definitions
are needed. This module will automatically load the required files.
However, if you are already loading them yourself from your web site&#x2019;s
template, this might result in conflicts. You can use the options
below to suppress the automatic loading.<br />
<br />
<?php

$PHORUM["mod_embed_images"]["lightbox_noload_prototype"]
    = empty($PHORUM["mod_embed_images"]["lightbox_noload_prototype"]) ? 0 : 1;

$PHORUM["mod_embed_images"]["lightbox_noload_scriptaculous"]
    = empty($PHORUM["mod_embed_images"]["lightbox_noload_scriptaculous"]) ? 0 : 1;

$PHORUM["mod_embed_images"]["lightbox_noload"]
    = empty($PHORUM["mod_embed_images"]["lightbox_noload"]) ? 0 : 1;

print $frm->checkbox("lightbox_noload_prototype", "1", "Do not load the prototype JavaScript library (1.5 or higher)", $PHORUM["mod_embed_images"]["lightbox_noload_prototype"]) . "<br />";

print $frm->checkbox("lightbox_noload_scriptaculous", "1", "Do not load the scriptaculous JavaScript library (module \"effects\" is required)", $PHORUM["mod_embed_images"]["lightbox_noload_scriptaculous"]) . "<br />";

print $frm->checkbox("lightbox_noload", "1", "Do not load the LightBox JavaScript library + CSS definitions", $PHORUM["mod_embed_images"]["lightbox_noload"]) . "<br />";

?>

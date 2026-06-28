<?php
// A simple helper script that will setup initial module
// settings in case one of these settings is missing.

// ----------------------------------------------------------------------
// THIS FILE IS NOT MEANT FOR CHANGING MODULE SETTINGS.
// USE THE MODULE SETTINGS IN THE PHORUM ADMIN FOR THAT,
// UNLESS YOU KNOW WHAT YOU ARE DOING.
// ----------------------------------------------------------------------

if(!defined("PHORUM") && !defined("PHORUM_ADMIN")) return;

if (! isset($GLOBALS["PHORUM"]["mod_openstreetmap"]))
    $GLOBALS["PHORUM"]["mod_openstreetmap"] = array();

if (! isset($GLOBALS["PHORUM"]["mod_openstreetmap"]["latitude"]) ||
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["latitude"] === '')
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["latitude"] = 40;

if (! isset($GLOBALS["PHORUM"]["mod_openstreetmap"]["longitude"]) ||
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["longitude"] === '')
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["longitude"] = -20;

if (! isset($GLOBALS["PHORUM"]["mod_openstreetmap"]["zoom"]) ||
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["zoom"] == '')
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["zoom"] = 1;

if (! isset($GLOBALS["PHORUM"]["mod_openstreetmap"]["type"]) ||
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["type"] == '')
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["type"] = "roadmap";

if (! isset($GLOBALS["PHORUM"]["mod_openstreetmap"]["profile_auto_show"]))
    $GLOBALS["PHORUM"]["mod_openstreetmap"]["profile_auto_show"] = 1;
?>

<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
////////////////////////////////////////////////////////////////////////////////

if ( !defined( "PHORUM" ) ) return;

/**
 * Determines the system's upload limit. This limit is determined by
 * the maximum upload filesize (PHP), the maximum POST request size (PHP)
 * and the maximum database packet size.
 *
 * @return An array containing three elements: the overall system's maximum
 *         upload size, the maximum as imposed by PHP and the maximum as
 *         imposed by the database.
 */
function phorum_get_system_max_upload()
{
    global $PHORUM;

    // Determine limit as imposed by PHP.
    $pms = phorum_phpcfgsize2bytes(ini_get('post_max_size'));
    $umf = phorum_phpcfgsize2bytes(ini_get('upload_max_filesize'));
    $php_limit = ($umf > $pms ? $pms : $umf);

    // Determines the database server's limit for file uploads. This limit
    // is determined by the maximum packet size that the database can handle.
    // We asume that there's a 40% overhead in the packet, so that 60% of
    // the packet can be used for sending an uploaded file to the database.
    $db_limit = phorum_db_maxpacketsize();
    if ($db_limit != NULL) {
        $db_limit = $db_limit * 0.6;
    }

    $limit = $php_limit;
    if ($db_limit && $db_limit < $php_limit) {
        $limit = $db_limit;
    }

    $data = array($limit, $php_limit, $db_limit);

    /*
     * [hook]
     *     system_max_upload
     *
     * [description]
     *     This hook allows a module to control the maximum file size for
     *     a file upload.  Most notable would be file system storage.  It
     *     could ignore the db_limit.
     *
     * [category]
     *     File storage
     *
     * [when]
     *     In
     *     <filename>include/upload_functions.php</filename>,
     *     in the function phorum_get_system_max_upload.
     *
     * [input]
     *     An array containing the default limit, the data layer limit and
     *     the PHP limit
     *
     * [output]
     *     A 3 part array with the limits adjusted as you wish.  The first
     *     element in the array would be the most important.
     *
     * [example]
     *     <hookcode>
     *     function phorum_mod_foo_system_max_upload($data)
     *     {
     *         // ignore the db_limit
     *         $data[0] = $data[2];
     *         return $data;
     *     }
     *     </hookcode>
     */
    if (isset($PHORUM["hooks"]["system_max_upload"])) {
        $data = phorum_hook("system_max_upload", $data);
    }

    return $data;
}

/**
 * Converts the size parameters that can be used in the PHP ini-file
 * (e.g. 1024, 10k, 8M) to a number of bytes.
 *
 * @param The PHP size parameter
 * @return The size parameter, converted to a number of bytes
 */
function phorum_phpcfgsize2bytes($val) {
    $val = trim($val);

    // Une valeur vide n'a pas de dernier caractere : $val[-1] levait un avertissement
    // de lecture hors chaine. ini_get() rend "" pour une directive absente.
    if ($val === '') return 0;

    $last = strtolower($val[strlen($val)-1]);

    // Le suffixe est retire AVANT la multiplication. En PHP 8, "8M" * 1024 leve
    // "A non-numeric value encountered" -- 2 occurrences le 2026-09-14, sur chaque
    // page qui demande la taille maximale d'envoi. Le resultat etait pourtant juste :
    // PHP lisait le 8 et ignorait le M. C'est donc un avertissement pour rien, mais
    // ecrit dans le journal a chaque appel.
    $bytes = in_array($last, array('g', 'm', 'k'), TRUE)
           ? (float) substr($val, 0, -1)
           : (float) $val;

    switch($last) {
       // The 'G' modifier is available since PHP 5.1.0
       // La cascade est conservee : 'g' traverse 'm' puis 'k', donc 1024^3.
       case 'g':
           $bytes *= 1024;
       case 'm':
           $bytes *= 1024;
       case 'k':
           $bytes *= 1024;
    }
    return $bytes;
}


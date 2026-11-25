<?php
// ----------------------------------------------------------------------
// LibreForum — https://github.com/fbastin/LibreForum
// Copyright 2026 Fabian Bastin and the LibreForum contributors
// SPDX-License-Identifier: Apache-2.0
// ----------------------------------------------------------------------
if (!defined("PHORUM")) return;

/**
 * Removes the GPS location from the files uploaded to the forum.
 *
 * WHY. Phones write the place where a photo was taken into the file. On one
 * forum, 159 attachments carried a usable location, 123 of them grouped on 15
 * recurring points of 13 members, none within 300 m of a shooting range: their
 * homes, most likely; 73 sat in public forums, served to any visitor by
 * file.php. Phorum does not touch metadata.
 *
 * WHERE. The file_store hook sees EVERY file stored through the file API
 * (attachment, personal file, avatar) before store_files_on_disk writes it
 * (priority in info.txt). The raw file_data is written to a temporary copy,
 * cleaned with exiftool (no re-encoding: orientation, date and the other
 * metadata stay), then read back.
 *
 * FAILS CLOSED. If the location cannot be removed (exiftool missing, file
 * unreadable), the upload is REFUSED with a message: a refused attachment is
 * better than a published home address.
 *
 * A forum may let members keep all their metadata: a custom profile field
 * named "keep_photo_gps", set to a true value, exempts that member.
 */

// Formats that commonly carry a location: phone photos and videos.
define('STRIP_IMAGE_GPS_EXT', '/\.(jpe?g|jpe|png|webp|heic|heif|avif|tiff?|mp4|m4v|mov|3gp)$/i');

function phorum_mod_strip_image_gps_file_store($file)
{
    if ($file === FALSE || !is_array($file)) return $file;
    if (empty($file['file_data']) || empty($file['filename'])) return $file;
    if (!preg_match(STRIP_IMAGE_GPS_EXT, $file['filename'])) return $file;

    if (strip_image_gps_user_keeps((int)($file['user_id'] ?? 0))) return $file;

    // The extension matters to exiftool for some formats (HEIC, MOV): keep it.
    $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
    $tmp = tempnam(sys_get_temp_dir(), 'phgps');
    if ($tmp === FALSE) return strip_image_gps_refuse();
    $work = $tmp . '.' . $ext;
    @unlink($tmp);

    $ok = FALSE;
    if (file_put_contents($work, $file['file_data']) !== FALSE && strip_image_gps_clean($work)) {
        clearstatcache(TRUE, $work);
        $data = file_get_contents($work);
        if ($data !== FALSE && $data !== '') {
            $file['file_data'] = $data;
            $file['filesize']  = strlen($data);
            $ok = TRUE;
        }
    }
    @unlink($work);

    return $ok ? $file : strip_image_gps_refuse();
}

/** The exiftool program, or NULL when none is found. */
function strip_image_gps_exiftool()
{
    $candidates = array('/usr/bin/exiftool', '/usr/local/bin/exiftool');
    if (!empty($GLOBALS['PHORUM']['mod_strip_image_gps']['exiftool'])) {
        array_unshift($candidates, $GLOBALS['PHORUM']['mod_strip_image_gps']['exiftool']);
    }
    foreach ($candidates as $path) {
        if (is_file($path) && is_executable($path)) return $path;
    }
    return NULL;
}

/** Runs exiftool with a list of arguments (no shell). Returns array(exit code, stdout). */
function strip_image_gps_run(array $args)
{
    $exiftool = strip_image_gps_exiftool();
    if ($exiftool === NULL) return array(-1, '');
    $proc = proc_open(array_merge(array($exiftool), $args),
                      array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    if (!is_resource($proc)) return array(-1, '');
    $out = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return array(proc_close($proc), $out);
}

/**
 * Does the file carry a GPS tag? NULL when this cannot be told (exiftool
 * missing or failing), which the caller must treat as a yes.
 */
function strip_image_gps_has_gps($path)
{
    if (!is_file($path) || strip_image_gps_exiftool() === NULL) return NULL;
    // "-gps*" without a group: EXIF, XMP AND QuickTime. A phone video keeps its
    // location in Keys:GPSCoordinates, which "-gps:all" (EXIF only) misses.
    list($rc, $out) = strip_image_gps_run(array('-q', '-q', '-m', '-a', '-s3', '-gps*', '--', $path));
    if ($rc !== 0 && $rc !== 1) return NULL;
    return trim($out) !== '';
}

/** Removes every GPS tag from $path. TRUE only if the file, read again, carries none. */
function strip_image_gps_clean($path)
{
    $has = strip_image_gps_has_gps($path);
    if ($has === FALSE) return TRUE;      // nothing to do: the file is not rewritten
    if ($has === NULL) return FALSE;

    $base = array('-q', '-q', '-m', '-P', '-overwrite_original', '-gps:all=', '-gps*=');
    strip_image_gps_run(array_merge($base, array('--', $path)));
    // Some files have a damaged EXIF ("No size tag for IFD1:ThumbnailOffset")
    // that exiftool refuses to write: dropping the embedded thumbnail (IFD1) unblocks it.
    if (strip_image_gps_has_gps($path) !== FALSE) {
        strip_image_gps_run(array_merge($base, array('-ifd1:all=', '--', $path)));
    }
    // Last resort, for a corrupt Interop block ("Can't read InteropIFD data"):
    // rewrite the EXIF keeping only orientation, date and camera.
    if (strip_image_gps_has_gps($path) !== FALSE) {
        strip_image_gps_run(array('-q', '-q', '-m', '-P', '-overwrite_original', '-exif:all=',
            '-tagsfromfile', '@', '-Orientation', '-DateTimeOriginal', '-Make', '-Model',
            '--', $path));
    }
    clearstatcache(TRUE, $path);
    return strip_image_gps_has_gps($path) === FALSE;
}

/** Has the member chosen to keep all metadata (profile field "keep_photo_gps")? */
function strip_image_gps_user_keeps($user_id)
{
    if ($user_id <= 0) return FALSE;
    $user = (!empty($GLOBALS['PHORUM']['user']['user_id'])
             && (int)$GLOBALS['PHORUM']['user']['user_id'] === $user_id)
        ? $GLOBALS['PHORUM']['user']
        : (function_exists('phorum_api_user_get') ? phorum_api_user_get($user_id) : NULL);
    return !empty($user['keep_photo_gps']);
}

function strip_image_gps_refuse()
{
    $message = isset($GLOBALS['PHORUM']['DATA']['LANG']['StripGpsRefused'])
             ? $GLOBALS['PHORUM']['DATA']['LANG']['StripGpsRefused']
             : "The GPS location of this file could not be removed, so it was not uploaded. " .
               "Try again, or remove the location on your phone before uploading.";
    return phorum_api_error_set(PHORUM_ERRNO_ERROR, $message);
}
?>

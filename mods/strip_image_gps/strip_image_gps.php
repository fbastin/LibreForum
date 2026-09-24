<?php
if (!defined("PHORUM")) return;

/**
 * Retrait de la position GPS des fichiers envoyés au forum (tireur.org, 2026-09-24).
 *
 * POURQUOI. Le 2026-09-24, 159 pièces jointes du forum portaient une position GPS
 * exploitable, dont 123 regroupées sur 15 points récurrents chez 13 membres, aucun à
 * moins de 300 m d'un stand : le domicile, vraisemblablement. 73 d'entre elles étaient
 * dans des forums publics, servies à tout visiteur par file.php. Phorum ne touche pas
 * aux métadonnées ; Discourse les retire par défaut, phpBB le propose.
 *
 * LA RÈGLE (décision de Fabian). Retirée par défaut ; le membre peut choisir, dans son
 * profil (panneau Confidentialité), de garder TOUTES les métadonnées, position
 * comprise — champ personnalisé `keep_photo_gps`, géré par custom_profile_fields_handler.
 *
 * OÙ. Le crochet file_store voit passer TOUT fichier stocké par l'API — pièce jointe,
 * fichier personnel, avatar — avant que store_files_on_disk ne l'écrive (priorité dans
 * info.txt). On travaille sur `file_data`, la donnée brute : une copie temporaire,
 * nettoyée par strip_image_gps() du site (exiftool, sans réencodage), puis relue.
 *
 * FERMÉ PAR DÉFAUT. Si la position ne peut être retirée (exiftool absent, fichier
 * illisible), l'envoi est REFUSÉ avec un message : une pièce jointe refusée vaut mieux
 * qu'un domicile publié.
 */

// Formats qui portent couramment une position : photos et vidéos de téléphone.
define('STRIP_IMAGE_GPS_EXT', '/\.(jpe?g|jpe|png|webp|heic|heif|avif|tiff?|mp4|m4v|mov|3gp)$/i');

function phorum_mod_strip_image_gps_file_store($file)
{
    if ($file === FALSE || !is_array($file)) return $file;
    if (empty($file['file_data']) || empty($file['filename'])) return $file;
    if (!preg_match(STRIP_IMAGE_GPS_EXT, $file['filename'])) return $file;

    if (strip_image_gps_user_keeps((int)($file['user_id'] ?? 0))) return $file;

    $helper = dirname(__FILE__) . '/../../../includes/image_gps.php';
    if (!function_exists('strip_image_gps') && is_file($helper)) {
        require_once $helper;
    }
    if (!function_exists('strip_image_gps')) {
        return strip_image_gps_refuse();
    }

    // L'extension compte pour exiftool sur certains formats (HEIC, MOV) : on la garde.
    $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
    $tmp = tempnam(sys_get_temp_dir(), 'phgps');
    if ($tmp === FALSE) return strip_image_gps_refuse();
    $work = $tmp . '.' . $ext;
    @unlink($tmp);

    $ok = FALSE;
    if (file_put_contents($work, $file['file_data']) !== FALSE && strip_image_gps($work)) {
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

/** Le membre a-t-il choisi de garder toutes les métadonnées ? */
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
    return phorum_api_error_set(
        PHORUM_ERRNO_ERROR,
        "La position GPS de ce fichier n'a pas pu être retirée ; il n'a pas été envoyé. " .
        "Réessayez, ou retirez la position avec votre téléphone avant l'envoi."
    );
}
?>

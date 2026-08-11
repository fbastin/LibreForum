<?php
if (!defined("PHORUM")) return;

/**
 * Enregistre les champs personnalisés gérés par ce mod s'ils n'existent pas
 * encore (idempotent). Appelé à chaque requête via le hook "common" ; une fois
 * le champ enregistré, ce n'est plus qu'une vérification en mémoire.
 *
 * real_name_privacy : « Garder privé » le vrai nom (1 = privé ; 0/absent =
 * partageable). Décoché par défaut → le vrai nom peut être diffusé.
 */
function phorum_mod_custom_profile_fields_handler_ensure_fields()
{
    if (!function_exists('phorum_api_custom_profile_field_byname')) {
        @include_once('./include/api/custom_profile_fields.php');
    }
    if (function_exists('phorum_api_custom_profile_field_byname') &&
        !phorum_api_custom_profile_field_byname('real_name_privacy')) {
        phorum_api_custom_profile_field_configure(array(
            'id'            => NULL,
            'name'          => 'real_name_privacy',
            'length'        => 1,
            'html_disabled' => TRUE,
            'show_in_admin' => FALSE,
        ));
    }
}

// Hook "common" (s'il est actif) : enregistre tôt, pour que la galerie puisse
// lire le réglage de confidentialité dès que possible.
function phorum_mod_custom_profile_fields_handler_common()
{
    phorum_mod_custom_profile_fields_handler_ensure_fields();
}

function phorum_mod_custom_profile_fields_handler_save($userdata)
{
    // On ne s'occupe que du panneau "user" (Mon Profil)
    if (isset($userdata['panel']) && $userdata['panel'] != "user") {
        return $userdata;
    }

    // Garantit l'existence du champ real_name_privacy avant la sauvegarde
    // (indépendamment du hook "common", qui peut nécessiter une reconstruction
    // du cache des modules pour s'activer).
    phorum_mod_custom_profile_fields_handler_ensure_fields();

    // 1. Validation de la date de naissance
    if (isset($_POST['user_birthday']) && trim($_POST['user_birthday']) !== "") {
        $birthday = trim($_POST['user_birthday']);
        
        // Vérification du format
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $birthday, $matches)) {
            $userdata['error'] = "Le format de la date de naissance doit être AAAA-MM-JJ (ex: 1980-05-15).";
            return $userdata;
        }
        
        $y = (int)$matches[1];
        $m = (int)$matches[2];
        $d = (int)$matches[3];
        
        // Vérification de la validité de la date (ex: pas de 31 février)
        if (!checkdate($m, $d, $y)) {
            $userdata['error'] = "La date de naissance saisie est invalide.";
            return $userdata;
        }
        
        // Vérification que la date n'est pas dans le futur
        if (mktime(0, 0, 0, $m, $d, $y) > time()) {
            $userdata['error'] = "La date de naissance ne peut pas être dans le futur.";
            return $userdata;
        }
        
        $userdata['user_birthday'] = $birthday;
    } else {
        $userdata['user_birthday'] = "";
    }

    // 2. Gestion de la case à cocher "Confidentialité"
    // Si la case n'est pas cochée, PHP ne l'envoie pas dans $_POST.
    if (!isset($_POST['user_birthday_privacy'])) {
        $userdata['user_birthday_privacy'] = 0;
    } else {
        $userdata['user_birthday_privacy'] = 1;
    }

    // 3. Case « Garder privé » le vrai nom (décochée par défaut → partageable).
    $userdata['real_name_privacy'] = isset($_POST['real_name_privacy']) ? 1 : 0;

    return $userdata;
}

/**
 * Hook user_delete — anonymisation RGPD. Déclenché par phorum_api_user_delete()
 * AVANT la suppression effective (l'utilisateur existe encore en base, donc on
 * peut récupérer son pseudo et cibler ses messages par user_id). Purge l'IP des
 * messages publics et anonymise les journaux DokuWiki (.changes).
 */
function phorum_mod_custom_profile_fields_handler_user_delete($user_id)
{
    // Une erreur d'anonymisation ne doit JAMAIS empêcher la suppression du compte.
    try {
        require_once dirname(__FILE__) . '/anonymize.php';
        $username = '';
        if (function_exists('phorum_api_user_get')) {
            $u = phorum_api_user_get((int)$user_id);
            if (!empty($u['username'])) $username = $u['username'];
        }
        if (function_exists('tireur_anonymize_user')) {
            tireur_anonymize_user((int)$user_id, $username, false);
        }
    } catch (\Throwable $e) {
        if (function_exists('tireur_anon_log')) tireur_anon_log('ERREUR hook user_delete #' . (int)$user_id . ' : ' . $e->getMessage());
        error_log('anonymize user_delete #' . (int)$user_id . ' : ' . $e->getMessage());
    }
    return $user_id;   // un hook LibreForum renvoie son argument
}
?>

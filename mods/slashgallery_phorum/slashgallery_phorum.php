<?php
if(!defined("PHORUM")) return;

/**
 * Helper to ensure SlashGallery is loaded
 */
function phorum_mod_slashgallery_phorum_load_library() {
    if (!class_exists('SlashGallery')) {
        require_once __DIR__ . '/../../../libs/SlashGallery/src/SlashGallery.php';
    }
    // Retrait du GPS avant publication (tireur.org, 2026-09-23) : voir
    // includes/image_gps.php du site. Absente, aucune pièce jointe n'est publiée.
    $gps = __DIR__ . '/../../../includes/image_gps.php';
    if (!function_exists('strip_image_gps') && is_file($gps)) {
        require_once $gps;
    }
}

function phorum_mod_slashgallery_phorum_editor_tool_plugin() {
    // Only register the tool if editor_tools mod is active and we are in an editor context
    if (isset($GLOBALS["PHORUM"]["MOD_EDITOR_TOOLS"])) {
        editor_tools_register_tool(
            'slashgallery',
            'Galerie Photos',
            $GLOBALS["PHORUM"]["http_path"] . '/mods/slashgallery_phorum/icon.png',
            'slashgallery_open_selector()'
        );
    }
}

function phorum_mod_slashgallery_phorum_javascript_register($data) {
    $data[] = array(
        "module" => "slashgallery_phorum",
        "source" => "file(mods/slashgallery_phorum/slashgallery_phorum.js)"
    );
    return $data;
}

/**
 * Les fichiers que la galerie indexe deja pour cet auteur : ceux qui portent son
 * etiquette ET son prefixe de nom (convention <auteur>_<horodatage>_<nom>). Les deux,
 * parce qu'une etiquette se pose a la main, et qu'un prefixe seul confondrait « bob » et
 * « bob_smith ». runPython rend le JSON tel quel : une action en echec rend
 * [false, "..."], un tableau qu'il ne faut pas parcourir comme une liste d'images.
 */
function phorum_mod_slashgallery_phorum_owned_files($gallery, $galleryDir, $username) {
    $files = [];
    $images = $gallery->getAllImages($username);
    if (!is_array($images)) return $files;
    $prefix = '/^' . preg_quote($username, '/') . '_\d{8,}_/';
    foreach ($images as $img) {
        if (!is_array($img) || empty($img['path'])) continue;
        $name = basename($img['path']);
        if (preg_match($prefix, $name) && is_file($galleryDir . '/' . $name)) {
            $files[] = $galleryDir . '/' . $name;
        }
    }
    return $files;
}

/**
 * Le fichier de $candidates dont le contenu est identique a $path, ou null. La taille
 * d'abord : on ne hache qu'en cas d'egalite, comme la synchro du wiki.
 */
function phorum_mod_slashgallery_phorum_find_twin($path, array $candidates) {
    clearstatcache(true, $path);
    $size = @filesize($path);
    if ($size === false) return null;
    $hash = null;
    foreach ($candidates as $c) {
        clearstatcache(true, $c);
        if (@filesize($c) !== $size) continue;
        if ($hash === null) $hash = hash_file('sha256', $path);
        if (hash_file('sha256', $c) === $hash) return $c;
    }
    return null;
}

function phorum_mod_slashgallery_phorum_sync_attachments($message, $origmessage = null) {
    global $PHORUM;
    
    if (empty($message['message_id'])) return $message;

    // after_post et after_edit ne passent pas la meme forme de message.
    // after_edit recoit le $dbmessage destine a la base : il n'a pas de
    // « datestamp », et ses pieces jointes vivent sous meta.attachments.
    // Sans cette normalisation, $message['attachments'] est vide a l'edition,
    // donc $current_attachment_filenames aussi, et l'etape 3 plus bas retire de
    // la galerie TOUTES les images du message — silencieusement.
    if (!isset($message['attachments']) && isset($message['meta']['attachments'])) {
        $message['attachments'] = $message['meta']['attachments'];
    }
    if (!isset($message['datestamp']) && isset($origmessage['datestamp'])) {
        $message['datestamp'] = $origmessage['datestamp'];
    }
    if (!isset($message['author']) && isset($origmessage['author'])) {
        $message['author'] = $origmessage['author'];
    }

    // Le datestamp et l'auteur composent le nom de fichier. Sans eux, tous les
    // noms calcules seraient faux : aucune correspondance avec l'existant, donc
    // recopie sous un mauvais nom puis suppression de l'ancien comme orphelin.
    // On prefere ne rien faire.
    if (!isset($message['datestamp']) || !isset($message['author'])) return $message;
    
    phorum_mod_slashgallery_phorum_load_library();
    if (!class_exists('SlashGallery')) return $message;

    // Load Phorum File API if not already loaded
    if (!defined('PHORUM_API_FILE')) {
        $phorum_root = realpath(__DIR__ . '/../..');
        $api_path = $phorum_root . '/include/api/file.php';
        if (file_exists($api_path)) require_once $api_path;
    }
    
    if (!function_exists('phorum_api_file_get')) return $message;

    $galleryDir = __DIR__ . '/../../../uploads/gallery';
    $config = [
        'db_path' => __DIR__ . '/../../../cache/gallery.db',
        'photo_base_dir' => $galleryDir,
        'python_venv' => __DIR__ . '/../../../libs/SlashGallery/venv',
        'base_url' => '/uploads/gallery/'
    ];
    $gallery = new SlashGallery($config);
    $gallery->setSecurityContext(true); // Full access for sync

    $username = strtolower($message['author']);
    $add_time = $message['datestamp'];
    $msg_id = $message['message_id'];

    // 1. Get current attachments on SlashGallery for this message
    $existing_in_gallery = $gallery->getByMessageId($msg_id);
    $existing_filenames = array_map(function($img) { return basename($img['path']); }, $existing_in_gallery);

    $current_attachment_filenames = [];
    $owned = null;   // fichiers de l'auteur deja en galerie, lus au premier besoin

    // 2. Add/Sync current attachments from Phorum
    if (!empty($message['attachments'])) {
        foreach ($message['attachments'] as $attachment) {
            if (!preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $attachment['name'])) continue;

            $newName = preg_replace('/[^a-z0-9\._-]/i', '_', $attachment['name']);
            $finalName = $username . '_' . $add_time . '_' . $newName;
            $current_attachment_filenames[] = $finalName;

            if (!in_array($finalName, $existing_filenames)) {
                $phorum_file = phorum_api_file_get($attachment['file_id']);
                if ($phorum_file && !empty($phorum_file['file_data'])) {
                    $destPath = $galleryDir . '/' . $finalName;
                    // Ecrit a cote sous un nom en point, GPS retire, PUIS compare : c'est
                    // le fichier tel que la galerie le garderait qu'on compare a l'existant.
                    // Le nom garde son extension, dont exiftool a besoin.
                    $tmpPath = $galleryDir . '/.tmp_' . bin2hex(random_bytes(6)) . '_' . $finalName;
                    $written = file_put_contents($tmpPath, $phorum_file['file_data']);
                    if ($written && !(function_exists('strip_image_gps') && strip_image_gps($tmpPath))) {
                        // GPS non retiré : le fichier ne reste pas dans la galerie.
                        $written = false;
                    }
                    // Le meme contenu est deja en galerie pour cet auteur : pas de copie.
                    // Un membre joint le meme GIF d'accueil a chaque nouveau venu depuis
                    // 2016 (186 messages) ; la recopie en faisait une entree de galerie
                    // par message, cinq entre septembre et novembre 2026. Le forum garde
                    // la piece jointe de chaque message : le message ne perd rien.
                    if ($written) {
                        if ($owned === null) {
                            $owned = phorum_mod_slashgallery_phorum_owned_files($gallery, $galleryDir, $username);
                        }
                        if (phorum_mod_slashgallery_phorum_find_twin($tmpPath, $owned) !== null) {
                            $written = false;
                        }
                    }
                    if ($written && !@rename($tmpPath, $destPath)) {
                        $written = false;
                    }
                    if (!$written) {
                        @unlink($tmpPath);
                    } else {
                        $owned[] = $destPath;   // deux fois la meme image dans ce message
                        $gallery->addTag($finalName, 'membre');
                        $gallery->addTag($finalName, $username);
                        $gallery->addTag($finalName, 'post_attachment');
                        $gallery->setPublic($finalName, false);
                        $gallery->setMessageId($finalName, $msg_id);
                        try { $gallery->generateThumbnail($finalName); } catch (Exception $e) {}
                    }
                }
            }
        }
    }

    // 3. Remove orphaned images (if attachment was removed in Phorum)
    foreach ($existing_filenames as $filename) {
        if (!in_array($filename, $current_attachment_filenames)) {
            phorum_mod_slashgallery_phorum_drop_copy($gallery, $filename, []);
        }
    }
    
    return $message;
}

function phorum_mod_slashgallery_phorum_after_post($message) {
    return phorum_mod_slashgallery_phorum_sync_attachments($message);
}

function phorum_mod_slashgallery_phorum_after_edit($message, $origmessage = null) {
    // Phorum passe le message d'origine en second argument depuis la 5.2.15 :
    // c'est la seule source du datestamp a l'edition.
    return phorum_mod_slashgallery_phorum_sync_attachments($message, $origmessage);
}

/** La galerie du site, avec les droits d'administration (toutes ses actions d'ecriture les exigent). */
function phorum_mod_slashgallery_phorum_gallery() {
    phorum_mod_slashgallery_phorum_load_library();
    if (!class_exists('SlashGallery')) return null;
    $gallery = new SlashGallery([
        'db_path' => __DIR__ . '/../../../cache/gallery.db',
        'photo_base_dir' => __DIR__ . '/../../../uploads/gallery',
        'python_venv' => __DIR__ . '/../../../libs/SlashGallery/venv',
        'base_url' => '/uploads/gallery/'
    ]);
    $gallery->setSecurityContext(true);
    return $gallery;
}

/** Les noms des copies liees a ce message. runPython peut rendre [false, "..."] : filtrer. */
function phorum_mod_slashgallery_phorum_copies_of($gallery, $msg_id) {
    $names = [];
    $rows = $gallery->getByMessageId((int)$msg_id);
    if (!is_array($rows)) return $names;
    foreach ($rows as $row) {
        if (is_array($row) && !empty($row['path'])) $names[] = basename($row['path']);
    }
    return $names;
}

/**
 * Un message, autre que ceux de $exclus, insere-t-il ce fichier ? Le selecteur « Galerie »
 * de l'editeur insere l'adresse brute de la photo ; ce sont les motifs de
 * gallery_access.php, qui decide qui la voit. En cas de doute (fichier absent, base
 * muette), on repond oui : garder un fichier de trop vaut mieux que casser un message.
 */
function phorum_mod_slashgallery_phorum_is_inserted($name, array $exclus) {
    if (!function_exists('gallery_like_patterns')) {
        $access = __DIR__ . '/../../../includes/gallery_access.php';
        if (!is_file($access)) return TRUE;
        require_once $access;
    }
    $or = [];
    foreach (gallery_like_patterns($name, false) as $p) {
        $or[] = "body LIKE '" . phorum_db_interact(DB_RETURN_QUOTED, $p) . "'";
    }
    if (!$or) return TRUE;
    $sql = "SELECT COUNT(*) FROM {$GLOBALS['PHORUM']['message_table']} WHERE (" . implode(' OR ', $or) . ")";
    $exclus = array_filter(array_map('intval', $exclus));
    if ($exclus) $sql .= " AND message_id NOT IN (" . implode(',', $exclus) . ")";
    $n = phorum_db_interact(DB_RETURN_VALUE, $sql);
    return $n === NULL || (int)$n > 0;
}

/**
 * Retire de la galerie la copie d'une piece jointe dont le message est supprime, ou que
 * le message n'a plus : fiche, fichier et vignettes. deleteImage() ne retire que la fiche
 * d'une image liee a un message (regle de juin 2026, pour la corbeille de la galerie) :
 * on la delie d'abord, et le fichier part avec. Le forum, lui, a deja retire sa propre
 * piece jointe. Si un autre message insere la photo, elle reste, deliee, comme photo
 * ordinaire de son auteur. Rend 'supprimee' ou 'gardee'.
 */
function phorum_mod_slashgallery_phorum_drop_copy($gallery, $name, array $exclus) {
    $gallery->setMessageId($name, 0);
    if (phorum_mod_slashgallery_phorum_is_inserted($name, $exclus)) return 'gardee';
    $gallery->deleteImage($name);
    return 'supprimee';
}

/**
 * Crochet « delete » : apres la suppression, avec la liste EXACTE des messages supprimes.
 * Il remplace « before_delete », qui ne retirait rien : la galerie y etait ouverte sans
 * droits d'administration, que deleteByMessageId exige (« Admin required »), et la
 * suppression d'un fil n'y recoit qu'une liste vide (moderation.php, PHORUM_DELETE_TREE).
 * Le meme crochet sert a la moderation, au panneau de controle et a l'elagage.
 */
function phorum_mod_slashgallery_phorum_delete($msg_ids) {
    if (!is_array($msg_ids) || !$msg_ids) return $msg_ids;
    $gallery = phorum_mod_slashgallery_phorum_gallery();
    if (!$gallery) return $msg_ids;
    foreach ($msg_ids as $id) {
        foreach (phorum_mod_slashgallery_phorum_copies_of($gallery, $id) as $name) {
            phorum_mod_slashgallery_phorum_drop_copy($gallery, $name, $msg_ids);
        }
    }
    return $msg_ids;
}

/**
 * Crochet « after_merge » : la fusion de deux fils recree chaque message sous un nouveau
 * numero (ancien => nouveau) et y rattache ses pieces jointes, puis supprime l'ancien fil
 * sans passer par « delete ». Les copies suivent leur message.
 */
function phorum_mod_slashgallery_phorum_after_merge($translation) {
    if (!is_array($translation) || !$translation) return $translation;
    $gallery = phorum_mod_slashgallery_phorum_gallery();
    if (!$gallery) return $translation;
    foreach ($translation as $old => $new) {
        foreach (phorum_mod_slashgallery_phorum_copies_of($gallery, $old) as $name) {
            $gallery->setMessageId($name, (int)$new);
        }
    }
    return $translation;
}
?>

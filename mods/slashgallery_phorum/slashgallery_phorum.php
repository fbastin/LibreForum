<?php
if(!defined("PHORUM")) return;

/**
 * Helper to ensure SlashGallery is loaded
 */
function phorum_mod_slashgallery_phorum_load_library() {
    if (!class_exists('SlashGallery')) {
        require_once __DIR__ . '/../../../libs/SlashGallery/src/SlashGallery.php';
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

function phorum_mod_slashgallery_phorum_sync_attachments($message) {
    global $PHORUM;
    
    if (empty($message['message_id'])) return $message;
    
    phorum_mod_slashgallery_phorum_load_library();
    if (!class_exists('SlashGallery')) return $message;

    // Load LibreForum File API if not already loaded
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

    // 2. Add/Sync current attachments from LibreForum
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
                    if (file_put_contents($destPath, $phorum_file['file_data'])) {
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

    // 3. Remove orphaned images (if attachment was removed in LibreForum)
    foreach ($existing_filenames as $filename) {
        if (!in_array($filename, $current_attachment_filenames)) {
            $gallery->deleteImage($filename);
        }
    }
    
    return $message;
}

function phorum_mod_slashgallery_phorum_after_post($message) {
    return phorum_mod_slashgallery_phorum_sync_attachments($message);
}

function phorum_mod_slashgallery_phorum_after_edit($message) {
    return phorum_mod_slashgallery_phorum_sync_attachments($message);
}

function phorum_mod_slashgallery_phorum_before_delete($data) {
    // LibreForum before_delete hook passes an array: 
    // array($handled, $msg_ids, $msgthd_id, $message, $delete_mode)
    if (!is_array($data)) return $data;

    phorum_mod_slashgallery_phorum_load_library();
    if (!class_exists('SlashGallery')) return $data;

    $galleryDir = __DIR__ . '/../../../uploads/gallery';
    $config = [
        'db_path' => __DIR__ . '/../../../cache/gallery.db',
        'photo_base_dir' => $galleryDir,
        'python_venv' => __DIR__ . '/../../../libs/SlashGallery/venv',
        'base_url' => '/uploads/gallery/'
    ];
    $gallery = new SlashGallery($config);

    $msg_ids = $data[1];
    $msgthd_id = $data[2];

    if (!is_array($msg_ids)) {
        $ids = !empty($msgthd_id) ? array($msgthd_id) : array();
    } else {
        $ids = $msg_ids;
    }

    foreach ($ids as $id) {
        if (!empty($id)) {
            $gallery->deleteByMessageId((int)$id);
        }
    }

    return $data;
}
?>

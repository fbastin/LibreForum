<?php

if(!defined("PHORUM")) return;

function social_share_mod_css_register($data) {
    $data['register'][] = array(
        'module' => 'social_share',
        'where'  => 'after',
        'source' => 'file(mods/social_share/social_share.css)'
    );
    return $data;
}

function social_share_mod_thread($message) {
    global $PHORUM;

    // We only share the first message of a thread or if explicitly called for a message
    // Usually {HOOK "social_share" TOPIC} or {HOOK "social_share" MESSAGE}

    $url = phorum_get_url(
        PHORUM_FOREIGN_READ_URL,
        $message['forum_id'], $message['thread'], $message['message_id']
    );

    // Strip auth data from the URL, if available.
    if (isset($_POST[PHORUM_SESSION_LONG_TERM])) {
        $url = preg_replace(
            '!,?' . PHORUM_SESSION_LONG_TERM.'=' .
            urlencode($_POST[PHORUM_SESSION_LONG_TERM]).'!',
            '', $url
        );
    }

    $encoded_url = urlencode($url);
    $subject = $message['subject'];
    $subject = preg_replace("/<img[^>]+\>/i", " ", $subject);
    $encoded_subject = urlencode($subject);

    $target = ($PHORUM['social_share']['link_new_window'] == 1) ? ' target="_blank" rel="noopener"' : '';

    print '<div class="social-share-container">';
    print '<span class="social-share-label">Partager :</span>';

    // Twitter / X
    if (!empty($PHORUM['social_share']['share_twitter'])) {
        print '<a class="social-share-btn ss-twitter" href="https://twitter.com/intent/tweet?text='.$encoded_subject.'&url='.$encoded_url.'"'.$target.' title="Twitter / X"></a>';
    }

    // Facebook
    if (!empty($PHORUM['social_share']['share_facebook'])) {
        print '<a class="social-share-btn ss-facebook" href="https://www.facebook.com/sharer.php?u='.$encoded_url.'"'.$target.' title="Facebook"></a>';
    }

    // WhatsApp
    if (!empty($PHORUM['social_share']['share_whatsapp'])) {
        print '<a class="social-share-btn ss-whatsapp" href="https://api.whatsapp.com/send?text='.$encoded_subject.'%20'.$encoded_url.'"'.$target.' title="WhatsApp"></a>';
    }

    // LinkedIn
    if (!empty($PHORUM['social_share']['share_linkedin'])) {
        print '<a class="social-share-btn ss-linkedin" href="https://www.linkedin.com/sharing/share-offsite/?url='.$encoded_url.'"'.$target.' title="LinkedIn"></a>';
    }

    // Telegram
    if (!empty($PHORUM['social_share']['share_telegram'])) {
        print '<a class="social-share-btn ss-telegram" href="https://t.me/share/url?url='.$encoded_url.'&text='.$encoded_subject.'"'.$target.' title="Telegram"></a>';
    }

    // Pinterest
    if (!empty($PHORUM['social_share']['share_pinterest'])) {
        print '<a class="social-share-btn ss-pinterest" href="https://pinterest.com/pin/create/button/?url='.$encoded_url.'&description='.$encoded_subject.'"'.$target.' title="Pinterest"></a>';
    }

    print '</div>';
}
?>

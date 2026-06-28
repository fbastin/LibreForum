<?php
if (!defined("PHORUM")) return;

// 1. Injecter les champs cachés et le JS dans le formulaire d'inscription
function phorum_mod_autodetect_timezone_reg_form()
{
    ?>
    <input type="hidden" name="detected_tz_offset" id="detected_tz_offset" value="" />
    <input type="hidden" name="detected_is_dst" id="detected_is_dst" value="0" />
    <script type="text/javascript">
    // <![CDATA[
    (function() {
        var today = new Date();
        // getTimezoneOffset retourne les minutes (ex: -120 pour UTC+2)
        // On convertit en heures et on inverse le signe pour correspondre au format PHP/Phorum
        var offsetHours = (today.getTimezoneOffset() / 60) * -1;
        document.getElementById('detected_tz_offset').value = offsetHours;
        
        // Détection rudimentaire de l'heure d'été (DST)
        // Compare le décalage en janvier et en juillet (Hémisphère Nord)
        var jan = new Date(today.getFullYear(), 0, 1);
        var jul = new Date(today.getFullYear(), 6, 1);
        var stdTimezoneOffset = Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
        if (today.getTimezoneOffset() < stdTimezoneOffset) {
            document.getElementById('detected_is_dst').value = "1";
        }
    })();
    // ]]>
    </script>
    <?php
}

// 2. Intercepter les données avant la sauvegarde du profil
function phorum_mod_autodetect_timezone_reg_save($userdata)
{
    if (isset($_POST['detected_tz_offset']) && $_POST['detected_tz_offset'] !== "") {
        $offset = (float) $_POST['detected_tz_offset'];
        $is_dst = isset($_POST['detected_is_dst']) && $_POST['detected_is_dst'] == "1" ? 1 : 0;
        
        // Si DST est actif, on soustrait 1 heure à l'offset de base pour Phorum
        // car Phorum gère "offset de base" + "option DST (+1)"
        if ($is_dst) {
            $offset -= 1.0;
        }

        $userdata['tz_offset'] = $offset;
        $userdata['is_dst'] = $is_dst;
    }
    
    return $userdata;
}
?>

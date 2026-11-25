<?php
if (!defined("PHORUM")) return;

// 1. Add the hidden fields and the script to the registration form
function phorum_mod_autodetect_timezone_reg_form()
{
    ?>
    <input type="hidden" name="detected_tz_offset" id="detected_tz_offset" value="" />
    <input type="hidden" name="detected_is_dst" id="detected_is_dst" value="0" />
    <script type="text/javascript">
    // <![CDATA[
    (function() {
        var today = new Date();
        // getTimezoneOffset() returns minutes (e.g. -120 for UTC+2): convert to
        // hours and invert the sign, as PHP and LibreForum expect
        var offsetHours = (today.getTimezoneOffset() / 60) * -1;
        document.getElementById('detected_tz_offset').value = offsetHours;
        
        // Rough daylight saving time (DST) detection: compares the offsets
        // of January and July (northern hemisphere)
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

// 2. Adjust the data before the profile is saved
function phorum_mod_autodetect_timezone_reg_save($userdata)
{
    if (isset($_POST['detected_tz_offset']) && $_POST['detected_tz_offset'] !== "") {
        $offset = (float) $_POST['detected_tz_offset'];
        $is_dst = isset($_POST['detected_is_dst']) && $_POST['detected_is_dst'] == "1" ? 1 : 0;
        
        // With DST active, one hour is subtracted from the base offset, since
        // LibreForum stores a base offset plus a "DST (+1)" option
        if ($is_dst) {
            $offset -= 1.0;
        }

        $userdata['tz_offset'] = $offset;
        $userdata['is_dst'] = $is_dst;
    }
    
    return $userdata;
}
?>

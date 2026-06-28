<?php
/*  mod_birthdays: Shows a list of users who have birthdays today
    author: Juergen Hansen (mail@jhansen.info)
        based on mod_forumstats by Thomas Seifert (thomas.seifert@mysnip.de)
	modified by LibreForum Maintainer (admin@example.com) for LibreForum
	The modifications include a test for active users and correct the date
	comparisons for leap years.
    license: You are free to use, modify, or redistribute this code so long 
           as I am given credit for the original development somewhere.
                 This code comes with absolutly no warranty.
*/
if(!defined("PHORUM")) return;

function is_leap_year($year)
{
	return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year %400) == 0)));
}

// set defaults
if (!isset($GLOBALS['PHORUM']["mod_birthdays"]["days_check"])) {
    $GLOBALS['PHORUM']["mod_birthdays"]["days_check"] = 30;
}
if (!isset($GLOBALS['PHORUM']["mod_birthdays"]["caching_enabled"])) {
    $GLOBALS['PHORUM']["mod_birthdays"]["caching_enabled"] = 0;
}

function mod_birthdays_sort_birthday($birthdaylist) {
    $sortedBirthdayList = array();
    $n = count($birthdaylist);
    for($i=0; $i < $n; $i++) {
            $min = mod_birthdays_min_birthday($birthdaylist);
            array_push($sortedBirthdayList, $min);
            $wert = array_search($min, $birthdaylist);
            unset($birthdaylist[$wert]);
            if(count($birthdaylist)==0) break;
    }
    return $sortedBirthdayList;
}

function mod_birthdays_min_birthday($birthdaylist) {
    $min_user = null;
    foreach($birthdaylist as $user) {
        if($min_user == null || $user["daysTillBirthday"] < $min_user["daysTillBirthday"])
            $min_user = $user;
    }
    return $min_user;
}

function mod_birthdays_common() {
    global $PHORUM;

    $PHORUM['DATA']['mod_birthdays']['enabled'] = true;

    // Load the module installation code if this was not yet done.
    // The installation code will take care of automatically adding
    // the custom profile field that is needed for this module.
    if (empty($GLOBALS['PHORUM']["mod_birthdays"]["installed"])) {
        include("./mods/birthdays/install.php");
    }
}

// TOCHECK!
function mod_birthdays_before_footer() {
    global $PHORUM;

    $page = phorum_page;
    if (!@in_array($page, $PHORUM["mod_birthdays"]["show_pages"])){
        return;
    }

    $return = mod_birthdays_get_cache_or_db();

    // Setup template data, add appropriate titles
    if($return["count"] ==0  && isset($PHORUM["mod_birthdays"]["hide_box"]) ) {
        if ($PHORUM["mod_birthdays"]["hide_box"]) {
            return;
        } else {
            $BlockContent_None = str_replace("%days%",$PHORUM["mod_birthdays"]["days_check"],$PHORUM['DATA']['LANG']["mod_birthdays"]["BlockContentNone"]);
            $PHORUM["DATA"]["BLOCK_CONTENT"]=
                $BlockContent_None.
                $return["string"];
        }
    } elseif($return["count"] ==1) {
        $BlockContent_One = str_replace("%days%",$PHORUM["mod_birthdays"]["days_check"],$PHORUM['DATA']['LANG']["mod_birthdays"]["BlockContentOne"]);
        $PHORUM["DATA"]["BLOCK_CONTENT"]=
            $BlockContent_One.":<br />".
            $return["string"];
    } elseif($return["count"] >1) {
        $BlockContent_Many = str_replace("%days%",$PHORUM["mod_birthdays"]["days_check"],$PHORUM['DATA']['LANG']["mod_birthdays"]["BlockContentMany"]);
        $BlockContent_Many = str_replace("%birthdays%",$return["count"],$BlockContent_Many);
        $PHORUM["DATA"]["BLOCK_CONTENT"]=
            $BlockContent_Many.":<br />".
            $return["string"];
    } else {
        $BlockContent_None = str_replace("%days%",$PHORUM["mod_birthdays"]["days_check"],$PHORUM['DATA']['LANG']["mod_birthdays"]["BlockContentNone"]);
        $PHORUM["DATA"]["BLOCK_CONTENT"]=
            $BlockContent_None.
            $return["string"];
    }
    $PHORUM["DATA"]["BLOCK_TITLE"] =$PHORUM["DATA"]["LANG"]["mod_birthdays"]["BlockTitle"];

    // Display the data
    include phorum_get_template("stdblock");    
}

function mod_birthdays_GetDaySelectField() {
    global $PHORUM;
    
    if ( isset ($PHORUM["DATA"]["PROFILE"]["mod_birthdays"]["birthday"]) ) {
        $birthday = getDate((int) $PHORUM["DATA"]["PROFILE"]["mod_birthdays"]["birthday"]);
        $tag = $birthday["mday"];
        $monat = $birthday["mon"];
        $jahr = $birthday["year"];
        if($birthday["seconds"] ==0) {
            $tag = -1;
        }
    } else {
        $tag = -1;
    }

    //Build day_selectfield
    $dayselect = '<select name="mod_birthdays_day" size="1" style="width:45px">
        <option value="0"></option>';

// TODO: to modify to select the correct maximum day
   for($i=1;$i<=31;$i++) {
        $dayselect = $dayselect .'<option value="'.$i.'" ';
        if($tag == $i) {$dayselect = $dayselect.'selected="selected"';}
        $dayselect = $dayselect.'>'.$i.'</option>';
    }
    $dayselect = $dayselect .'</select>';
    return  $dayselect;
}

function mod_birthdays_GetMonthSelectField() {
    global $PHORUM;
    
    if ( isset ($PHORUM["DATA"]["PROFILE"]["mod_birthdays"]["birthday"]) ) {
        $birthday = getDate((int) $PHORUM["DATA"]["PROFILE"]["mod_birthdays"]["birthday"]);
        $tag = $birthday["mday"];
        $monat = $birthday["mon"];
        $jahr = $birthday["year"];
        if($birthday["seconds"] ==0) {
            $monat = -1;
        }
    } else {
        $monat = -1;
    }
    
    //Build month_selectfield
    for($i=1;$i<=31;$i++) {
        $selected[$i] = ($i == $monat)  ? ' selected="selected">' : ">";
    }
    $monthselect = '<select name="mod_birthdays_month" size="1" style="width:100px">
        <option value="0" ></option>
        <option value="1"'.$selected[1].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["January"].'</option>
        <option value="2"'.$selected[2].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["February"].'</option>
        <option value="3"'.$selected[3].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["March"].'</option>
        <option value="4"'.$selected[4].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["April"].'</option>
        <option value="5"'.$selected[5].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["May"].'</option>
        <option value="6"'.$selected[6].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["June"].'</option>
        <option value="7"'.$selected[7].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["July"].'</option>
        <option value="8"'.$selected[8].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["August"].'</option>
        <option value="9"'.$selected[9].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["September"].'</option>
        <option value="10"'.$selected[10].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["October"].'</option>
        <option value="11"'.$selected[11].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["November"].'</option>
        <option value="12"'.$selected[12].$PHORUM["DATA"]["LANG"]["mod_birthdays"]["December"].'</option>
    </select>';
    return $monthselect;
}

function mod_birthdays_GetYearSelectField() {
    global $PHORUM;
    
    if ( isset ($PHORUM["DATA"]["PROFILE"]["mod_birthdays"]["birthday"]) ) {
        $birthday = getDate((int) $PHORUM["DATA"]["PROFILE"]["mod_birthdays"]["birthday"]);
        $tag = $birthday["mday"];
        $monat = $birthday["mon"];
        $jahr = $birthday["year"];
        if($birthday["seconds"] ==0) {
            $jahr = -1;
        }
    } else {
        $jahr = -1;
    }

    //Build year_selectfield
    $heute = getdate();
    $yearselect = '<select name="mod_birthdays_year" size="1" style="width:60px">
        <option value="0"></option>';
    for($i=$heute['year']-80;$i<=$heute['year'];$i++) {
        $yearselect = $yearselect .'<option value="'.$i.'" ';
        if($jahr == $i) {$yearselect = $yearselect.'selected="selected"';}
        $yearselect = $yearselect.'>'.$i.'</option>';
    }
    $yearselect = $yearselect .'</select>';
    return $yearselect;
}

// Add an extra birthday option to the control center user settings menu.
// eliminates need to hack template
function mod_birthdays_tpl_cc_usersettings($profile)
{
    global $PHORUM;

    if ( isset($PHORUM["DATA"]["PROFILE"]["mod_birthdays"]["age"]) ) {
        if ( $PHORUM["DATA"]["PROFILE"]["mod_birthdays"]["age"] == 1 ) {
            $agecheck = "checked";
        } else {
            $agecheck = " ";
        }
    } else {
        $agecheck = " ";
    }

    // Generate the template data
    $PHORUM["DATA"]["MOD_BIRTHDAYS_DAY"] = mod_birthdays_GetDaySelectField();
    $PHORUM["DATA"]["MOD_BIRTHDAYS_MONTH"] = mod_birthdays_GetMonthSelectField();
    $PHORUM["DATA"]["MOD_BIRTHDAYS_YEAR"] = mod_birthdays_GetYearSelectField();
    $PHORUM["DATA"]["MOD_BIRTHDAYS_AGE"] = $agecheck;

    // Display the template.
    include(phorum_get_template('birthdays::cc_usersettings'));

    return $profile;
}

function mod_birthdays_cc_save_user ($data) {
    return mod_birthdays_saveData($data);
}

function mod_birthdays_before_register ($data) {
    return mod_birthdays_saveData($data);
}

function mod_birthdays_saveData($data)
{
    global $PHORUM;

    // Setup birthday to be saved in profile
    if (isset($_POST["mod_birthdays_day"]) && $_POST["mod_birthdays_day"] != 0 &&
        isset($_POST["mod_birthdays_month"]) && $_POST["mod_birthdays_month"] != 0 &&
        isset($_POST["mod_birthdays_year"]) && $_POST["mod_birthdays_year"] != 0 ){
        $data["mod_birthdays"]["birthday"] = mktime ( 0,0,1, $_POST["mod_birthdays_month"], $_POST["mod_birthdays_day"], $_POST["mod_birthdays_year"]);
    } else {
        $data["mod_birthdays"]["birthday"] = 0;
    }
    // Setup show-age flag to be saved in profile
    if ( isset($_POST["mod_birthdays_age"]) && $_POST["mod_birthdays_age"] != 0 ) {
        $data["mod_birthdays"]["age"] = 1;
    } else {
        $data["mod_birthdays"]["age"] = 0;
    }
    // Clear cache if it exists so new data will be loaded
    $cachefile = $PHORUM["cache"]."/mod_birthdays.dat";
    if ( file_exists($cachefile) ) {
        @unlink($cachefile);
    }
    return $data;
}

function mod_birthdays_get_cache_or_db() {
    global $PHORUM;

    $cachefile = $PHORUM["cache"]."/mod_birthdays.dat";
    
    $lastedit=0;
    if (file_exists($cachefile)) {
        $lastedit = getdate(filemtime($cachefile));
    }
    $heute = getdate();

    if ( isset($PHORUM["mod_birthdays"]["caching_enabled"]) ) {
      if ($PHORUM["mod_birthdays"]["caching_enabled"]) {
        // If it's not expired, just load it from the cache
	$toto = 0; // used for debugging
        if (  $toto &&   file_exists($cachefile) && ($lastedit["yday"] == $heute["yday"]) ) {
        $fp = fopen($cachefile, 'r');
        $birthdaylist = '';
        while (!feof($fp)) $birthdaylist .= fread($fp, 4096);
            fclose($fp);
            $birthdaylist = unserialize($birthdaylist);
        } else {
            //It is expired, regenerate and save it
            $birthdaylist = mod_birthdays_getlist();
            $fp = fopen($cachefile, 'w');
            fwrite($fp, serialize($birthdaylist));
            fclose($fp);
        }
      } else {
        // In this case caching is off, we'll just load the list from the database
        $birthdaylist = mod_birthdays_getlist();
      }
    } else {
        // In this case caching is off, we'll just load the list from the database
        $birthdaylist = mod_birthdays_getlist();
    }
    $string = "";
    $count = 0;
    foreach($birthdaylist as $user) {
        $count++;
        if($string != "") {
            // separator for user list
            $string = $string ."<br /> ";
        }
        $url = phorum_get_url(PHORUM_PROFILE_URL, $user["user_id"]);
        $string = $string."<a href=\"$url\">".$user['username']."</a> (".$user['age'].")";
    }
    $return["count"] = $count;
    $return["string"] = $string;
    return $return;
}

function mod_birthdays_getlist() {
    global $PHORUM;

    $jetzt = getdate();

    $max_days = 300;

    // Collect all users which have a "mod_birthdays" custom user profile field.
    require_once('./include/api/custom_profile_fields.php');
    $field = phorum_api_custom_profile_field_byname('mod_birthdays');
    if (empty($field)) trigger_error(
        'No custom profile field named "mod_birthdays" available',
        E_USER_ERROR
    );
    $userids = phorum_api_user_search_custom_profile_field(
        $field['id'], '', '*', TRUE
    );

    $userlist = phorum_api_user_get($userids);
    $ubirthday = array();
    $birthdaylist = array();
    foreach($userlist as $user) {
      if ($user["active"] == 1) {
      if ( isset($user["mod_birthdays"]["birthday"]) ) {
        if ((time()-$user["date_last_active"]) <= ($max_days*86400)) {
        $ubirthday = getdate((int) $user["mod_birthdays"]["birthday"]);
        if (($ubirthday["mon"] > $jetzt["mon"]) || ($ubirthday["mon"] == $jetzt["mon"] && ($ubirthday["day"] >= $jetzt["day"])))
//        if($ubirthday["yday"] >= $jetzt["yday"])
            $nextBirthday = mktime(0,0,1,$ubirthday["mon"],$ubirthday["mday"],$jetzt["year"]);
        else
            $nextBirthday = mktime(0,0,1,$ubirthday["mon"],$ubirthday["mday"],$jetzt["year"]+1);
        
        $daysInFuture = $PHORUM["mod_birthdays"]["days_check"];
        $daysTillBirthday = (int) ceil(($nextBirthday - time())/(60*60*24));

if($daysTillBirthday >= 0 && $daysTillBirthday < $daysInFuture && $ubirthday["seconds"]!=0) {

            // Setup Month and Day (leave off year for now for age hiding)
setlocale(LC_TIME, 'fr_FR.UTF8');
$birthdate = strftime($PHORUM["DATA"]["LANG"]["mod_birthdays"]['long_date'],$user["mod_birthdays"]["birthday"]);

            // Add description (today, tomorrow, etc.)
            if($daysTillBirthday == 0) {
                $birthdate2 = $birthdate . " - " . $PHORUM["DATA"]["LANG"]["mod_birthdays"]["Today"] . "!";
		// HOW TO SEND AN EMAIL???
            } elseif ($daysTillBirthday == 1) {
                $birthdate2 = $birthdate . " - " . $PHORUM["DATA"]["LANG"]["mod_birthdays"]["Tomorrow"] . "!";
            } elseif ($daysTillBirthday == 2) {
                $birthdate2 = $birthdate . " - " . $PHORUM["DATA"]["LANG"]["mod_birthdays"]["DayAfter"] . "!";
            } else {
                $future_days = str_replace("%days%",$daysTillBirthday,$PHORUM['DATA']['LANG']["mod_birthdays"]["FutureDays"]);
                $birthdate2 = $birthdate . " - " . $future_days . "!";
            }

            // Add age only if user allows
            if ( isset($user["mod_birthdays"]["age"]) && $user["mod_birthdays"]["age"] == 1 ) {
                $user_age = ($jetzt["year"]-$ubirthday["year"]);
                if($daysTillBirthday == 0) {
                    $today_age = str_replace("%years%",$user_age,$PHORUM['DATA']['LANG']["mod_birthdays"]["TodayAge"]);
                    $user["age"] = $birthdate2 . " - " . $today_age;
                } else {
		    if(($ubirthday["mon"] == 1) && ($jetzt["mon"] > 1)) {
		      $user_age++;
		    }
                    $future_age = str_replace("%years%",$user_age,$PHORUM['DATA']['LANG']["mod_birthdays"]["FutureAge"]);
                    $user["age"] = $birthdate2 . " - " . $future_age;
                }
            } else {
                $user["age"] = $birthdate2;
            }            
          $user["daysTillBirthday"] = $daysTillBirthday;
          array_push($birthdaylist, $user);
        }
      }}
      }
    }
    return mod_birthdays_sort_birthday($birthdaylist);
}
?>

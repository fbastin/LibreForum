<?php

if(!defined("PHORUM_ADMIN")) return;

function phorum_mod_check_module_upgrades_admin_pre($sent_module) {
	if ($sent_module != "mods") {
		return $sent_module;
	}
	global $PHORUM;
	$PHORUM["phorum_mod_check_module_upgrades"][$PHORUM["user"]["user_id"]]["timestamp"] = "";
	$PHORUM["phorum_mod_check_module_upgrades"][$PHORUM["user"]["user_id"]]["upgraded_mods"] = "";
	phorum_db_update_settings(array("phorum_mod_check_module_upgrades"=>$PHORUM["phorum_mod_check_module_upgrades"]));
	
	ob_start();
	include_once "./include/admin/header.php";
	
	include_once "./include/admin/$sent_module.php";
	include_once "./include/admin/footer.php";
	$output = ob_get_contents();
	ob_end_clean();
	
	// Get updates from http://www.phorum.org/modules52.txt
	// Get a list of available mods but ignore core modules
	$core_modules = array("announcements","bbcode","editor_tools","event_logging","html","replace","smileys","smtp_mail","spamhurdles","username_restrictions","mod_tidy");
	$d = dir("./mods");
	while (false !== ($entry = $d->read())) {
		if (in_array($entry,$core_modules)) continue;
		$lines = array();
		if(file_exists("./mods/$entry/info.txt")){
			$lines=file("./mods/$entry/info.txt");
		} elseif(is_file("./mods/$entry") && substr($entry, -4)==".php"){
			$entry=str_replace(".php", "", $entry);
			if (in_array($entry,$core_modules)) continue;
			$data = file_get_contents("./mods/$entry.php");
			if($data = stristr($data, "/* phorum module info")){
				$data = substr($data, 0, strpos($data, "*/"));
				$lines = preg_split('!(\r|\n|\r\n)!', $data);
			}
		}
	    if(count($lines)){
		$modules[$entry]=array();
		foreach($lines as $line){
		    if(strstr($line, ":")){
			$parts=explode(":", trim($line), 2);
			if($parts[0]=="hook"){
			    $modules[$entry]["hooks"][]=trim($parts[1]);
			} else {
			    $modules[$entry][$parts[0]]=trim($parts[1]);
			}
		    }
		}
	     }
	}
	$d->close();
	include('./include/api/http_get.php');
	$fullfile = phorum_api_http_get("http://www.phorum.org/modules52.txt");
	if ($fullfile != NULL) {
		$cmu_flag = false;
		$connection_ok = true;
		foreach ($modules as $module => $fields) {
			if (isset($fields["title"]) && isset($fields["version"])) {
				if (in_array($module,$core_modules)) continue;
				$verstart = 0;
				$finished = false;
				while (!$finished) {
                    $verstart = strpos($fullfile, $fields["title"]."]: [[BR]]",$verstart + 1);
                    if ($verstart == 0) {
                        //$modules[$module]["cmu_status"]="Incompatible Module";
                        $finished = true;
                    } else {
                        if (!is_numeric(substr($fullfile,$verstart-2,1)))                
                            continue;
                    
                        $verstart = strpos($fullfile, "[[BR]]Version: ", $verstart) + 15;
                        $verstop = strpos(substr($fullfile,$verstart,60)," [[BR]]");
                        $online_version = substr($fullfile,$verstart,$verstop);
                        if (version_compare($fields["version"], $online_version, '<')) {
                            $cmu_flag = true;
                            $url_start = "<span style=\"padding: 2px 6px; background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 4px; color: #c0392b; font-size: 11px; font-weight: 500; margin-left: 6px;\">";
                            $url_end = "</span>";
                            $orig_str = '<span class="mod-title">'.$fields["title"]."</span> (version ".$fields["version"].")";
                            $replace_str = $orig_str." - ".$url_start."Upgrade Available ($online_version)".$url_end;
                            $output = str_replace($orig_str, $replace_str, $output);
                        }
                        $finished = true;
                    }
                }
			}
		}
		if ($cmu_flag) {
			$orig_str = "<td valign=\"top\" width=\"100%\">";
			$replace_str = $orig_str."<div style=\"background-color: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-weight: 500; width: 450px; text-align: center;\">Une ou plusieurs extensions ci-dessous ont une version plus récente disponible.</div>";
			$output = str_replace($orig_str, $replace_str, $output);
		}
	} else {
		$connection_ok = false;
	}
	print $output;

	exit;
}

function phorum_mod_check_module_upgrades_before_footer() {
	
	global $PHORUM;
	
	// if the user is not an admin or we are not on the index page there is no need to go further.	
	if (phorum_page != "index" 
		|| empty($PHORUM["user"]["admin"]) 
		|| empty($PHORUM["phorum_mod_check_module_upgrades"]["enable_index_check"])
		) return;
	
	//pre-fill the upgraded mods
	$insert_str = (empty($PHORUM["phorum_mod_check_module_upgrades"][$PHORUM["user"]["user_id"]]["upgraded_mods"])) ? "" : $PHORUM["phorum_mod_check_module_upgrades"][$PHORUM["user"]["user_id"]]["upgraded_mods"];
	
	//check for upgrades only once per day
	if (empty($PHORUM["phorum_mod_check_module_upgrades"][$PHORUM["user"]["user_id"]]["timestamp"]) 
			|| ($PHORUM["phorum_mod_check_module_upgrades"][$PHORUM["user"]["user_id"]]["timestamp"] + 86400) < time()) {
			
		// Get updates from http://www.phorum.org/modules52.txt
		// Get a list of available mods but ignore core modules
		$core_modules = array("announcements","bbcode","editor_tools","event_logging","html","replace","smileys","smtp_mail","spamhurdles","username_restrictions","mod_tidy");
		$d = dir("./mods");
		while (false !== ($entry = $d->read())) {
			if (in_array($entry,$core_modules)) continue;
			$lines = array();
			if(file_exists("./mods/$entry/info.txt")){
				$lines=file("./mods/$entry/info.txt");
			} elseif(is_file("./mods/$entry") && substr($entry, -4)==".php"){
				$entry=str_replace(".php", "", $entry);
				if (in_array($entry,$core_modules)) continue;
				$data = file_get_contents("./mods/$entry.php");
				if($data = stristr($data, "/* phorum module info")){
					$data = substr($data, 0, strpos($data, "*/"));
					$lines = preg_split('!(\r|\n|\r\n)!', $data);
				}
			}
		    if(count($lines)){
			$modules[$entry]=array();
			foreach($lines as $line){
			    if(strstr($line, ":")){
				$parts=explode(":", trim($line), 2);
				if($parts[0]=="hook"){
				    $modules[$entry]["hooks"][]=trim($parts[1]);
				} else {
				    $modules[$entry][$parts[0]]=trim($parts[1]);
				}
			    }
			}
		     }
		}
		$d->close();
		include('./include/api/http_get.php');
		$fullfile = phorum_api_http_get("http://www.phorum.org/modules52.txt");
		$upgraded_mods = "";
		if ($fullfile != NULL) {
			$cmu_flag = false;
			$connection_ok = true;
			foreach ($modules as $module => $fields) {
				if (isset($fields["title"]) && isset($fields["version"])) {
					if (in_array($module,$core_modules)) continue;
					$verstart = strpos($fullfile, $fields["title"]."]: [[BR]]");
					if ($verstart == 0) {
						//$modules[$module]["cmu_status"]="Incompatible Module";
						continue;
					}
					$verstart = strpos($fullfile, "[[BR]]Version: ", $verstart) + 15;
					$verstop = strpos(substr($fullfile,$verstart,60)," [[BR]]");
					$online_version = substr($fullfile,$verstart,$verstop);
						if (version_compare($fields["version"], $online_version, '<')) {
						$upgraded_mods .= (empty($upgraded_mods)) ? $fields["title"] : ", ".str_replace(" ","&nbsp;",$fields["title"]);
					}
				}
			}
			if (!empty($upgraded_mods)) {
				$insert_str = "<div style=\\\"background-color: #FFEEEE; font-weight: bold; border: solid 2px #DD0000; padding: 5px; margin: 0 0 10px 0; text-align: center; \\\">The following mods have a newer version available:<br />$upgraded_mods<\/div>";
			}
			$PHORUM["phorum_mod_check_module_upgrades"][$PHORUM["user"]["user_id"]]["timestamp"] = time();
			$PHORUM["phorum_mod_check_module_upgrades"][$PHORUM["user"]["user_id"]]["upgraded_mods"] = $insert_str;
			phorum_db_update_settings(array("phorum_mod_check_module_upgrades"=>$PHORUM["phorum_mod_check_module_upgrades"]));
		}
	}
	
	//show notification if upgrades are available
	if (!empty($insert_str)) {
		print "<script type=\"text/javascript\">
			//<![CDATA[
			function cmu_insert_div() {
			divs = document.getElementsByTagName('div');
			var phorum_div;
			for (i in divs) {
				if (divs[i].id == 'phorum') phorum_div = divs[i];
				}
			if (phorum_div) {
				phorum_div.innerHTML = \"$insert_str\" + phorum_div.innerHTML;
				} else {
				document.body.innerHTML = \"$insert_str\" + document.body.innerHTML;
				}
			}
			window.onload = cmu_insert_div; 
			//]]>
			</script>";
	}
}

?>

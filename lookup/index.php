<?php
	/**/header("Access-Control-Allow-Origin: *");
	// LOAD DNSWATCH EXTENSION PACK //
	EXT_load("source/dnswatch"); //load all .php files from 'dnswatch/functions'
	
	
	// LOAD NAMESERVER CONFIG //
	require(CMS_path("//config/nameserver.php")); //load config file at 'dnswatch/config/nameserver.php'
	
	
	// GET DOMAIN TO SEARCH FOR (IF IT IS SET) //
	$__domain = (isset($_POST["domain"]) ? base64_decode($_POST["domain"]) : NULL);
	
	
	// CHECK IF SET AND FOR SYNTAX //
	if($__domain !== NULL and preg_match("/^([a-z0-9\-]{1,64}\.){1,16}[a-z0-9]{2,}$/", $__domain)){
		//filter again for security
		$__domain = STR_filter($__domain, "abcdefghijklmnopqrstuvwxyz0123456789-."); //remove all chars in $__domain which are not contained in this list
		
		
		// GET LOOKUP DATA //
		$__data = DNSWATCH_lookup($__domain); //defined in dnswatch/functions/lookup.php
		
		
		// ADD NAMESERVER'S PROVIDER NAMES AND LINK ICONS //
		//reference
		for($q = 0; $q < sizeof($__data["reference"]); $q++){
			$nameserver = $__data["reference"][$q]["nameserver"];
			
			//name
			$provider = $§DNSWATCH_NAMESERVER_REFERENCE[$nameserver]["name"];
			$__data["reference"][$q]["name"] = $provider;
			
			//icon
			$icon = CMS_file("//icon/" . $nameserver . ".svg"); //get a temporary url where the file '/dnswatch/icon/NAMESERVER.svg' can be downloaded
			$__data["reference"][$q]["icon"] = $icon;
		}
		
		//search
		for($q = 0; $q < sizeof($__data["search"]); $q++){
			$nameserver = $__data["search"][$q]["nameserver"];
			
			//name
			$provider = $§DNSWATCH_NAMESERVER[$nameserver]["name"];
			$__data["search"][$q]["name"] = $provider;
			
			//icon
			$icon = CMS_file("//icon/" . $nameserver . ".svg"); //get a temporary url where the file '/dnswatch/icon/NAMESERVER.svg' can be downloaded
			$__data["search"][$q]["icon"] = $icon;
		}
		
		
		// CREATE RESPONSE //
		$Response = ["type" => "success", "data" => $__data];
		
	} else {
		$Response = ["type" => "error", "error" => "malformed_domain"];
	}
	
	
	// RESPOND WITH JSON //
	echo(json_encode($Response));
?>

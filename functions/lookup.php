<?php
	/*
		CALLBACK: Get blockage information for DOMAIN
			- if recent cached result: use that
			- otherwise: do a new lookup
	*/
	function DNSWATCH_lookup($DOMAIN){
		require(CMS_path("//config/nameserver.php")); //load config file at 'dnswatch/config/nameserver.php'
		
		
		// CHECK CACHE //
		$__data = DNSWATCH_LOOKUP_cache($DOMAIN);
		
		
		// IF THE CACHE DOESN'T HAVE SOMETHING IN STOCK FOR US, DO A NEW LOOKUP //
		if($__data === false){
			//do new lookup
			$__data = DNSWATCH_LOOKUP_new($DOMAIN);
		}
		
		
		// RESPOND WITH DATA //
		return($__data);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*
		Do a new actual lookup for DOMAIN and store results in cache
	*/
	function DNSWATCH_LOOKUP_new($DOMAIN){
		require(CMS_path("//config/lookup.php")); //load config file at 'dnswatch/config/lookup.php'
		require(CMS_path("//config/nameserver.php")); //load config file at 'dnswatch/config/nameserver.php'
		
		
		// SET UP SESSION //
		//get session-uid
		$__suid = id64(16); //get base64 id of length 16
		
		//get tmp dir path
		$__tmp_dir_path = $§DNSWATCH_LOOKUP_TMP_DIR . "/" . "dnswatch_dnslookup_" . ID64_raw($__suid); //ID64_raw: remove leading '~' from id
		
		//create dir
		mkdir($__tmp_dir_path);
		
		
		
		
		
		///////////////////////////////////////////
		// E X E C U T E   D N S   L O O K U P S //
		///////////////////////////////////////////
		
		// CLEAR SOME STUFF //
		$__used_nameserver_address = [];
		$__hostcmd_string = "";
		
		
		// GET CERTAIN AMOUNT OF RANDOM REFERENCE-SERVERS //
		$__reference_random = [];
		for($q = 0; $q < $§DNSWATCH_LOOKUP_REFERENCE_COUNT; $q++){
			//get random starting offset in available-reference list
			$rand = rand(0, sizeof($§DNSWATCH_NAMESERVER_REFERENCE) - 1);
			
			//from this point add first unused nameserver
			for($w = 0; $w < sizeof($§DNSWATCH_NAMESERVER_REFERENCE); $w++){
				//get index with offset
				$index = ($rand + $w) % sizeof($§DNSWATCH_NAMESERVER_REFERENCE);
				
				$this_reference = array_keys($§DNSWATCH_NAMESERVER_REFERENCE)[$index];
				
				//used?
				if(!in_array($this_reference, $__reference_random)){
					//add to list
					$__reference_random[] = $this_reference;
					
					//stop searching
					$w = sizeof($§DNSWATCH_NAMESERVER_REFERENCE);
				}
			}
		}
		
		
		// ADD COMMANDS FOR REFERENCES //
		for($q = 0; $q < sizeof($__reference_random); $q++){
			$reference = $__reference_random[$q];
			
			//get random nameserver address
			$nameserver_address = $§DNSWATCH_NAMESERVER_REFERENCE[$reference]["address"];
			$rand = rand(0, sizeof($nameserver_address) - 1);
			$nameserver_address = $nameserver_address[$rand];
			
			//remember used address for later
			$__used_nameserver_address[$reference] = $nameserver_address;
			
			//maybe prepend space
			if(strlen($__hostcmd_string) > 0){
				$__hostcmd_string .= " ";
			}
			
			//add host-cmd
			$__hostcmd_string .= "\"host " . $DOMAIN . " " . $nameserver_address . " > " . $__tmp_dir_path . "/" . $reference . " 2>&1\"";
		}
		
		
		// ADD COMMANDS FOR SEARCH //
		for($q = 0; $q < sizeof($§DNSWATCH_NAMESERVER); $q++){
			$this_nameserver = array_keys($§DNSWATCH_NAMESERVER)[$q];
			
			//get random nameserver address
			$nameserver_address = $§DNSWATCH_NAMESERVER[$this_nameserver]["address"];
			$rand = rand(0, sizeof($nameserver_address) - 1);
			$nameserver_address = $nameserver_address[$rand];
			
			//remember used address for later
			$__used_nameserver_address[$this_nameserver] = $nameserver_address;
			
			//maybe prepend space
			if(strlen($__hostcmd_string) > 0){
				$__hostcmd_string .= " ";
			}
			
			//add host-cmd
			$__hostcmd_string .= "\"host " . $DOMAIN . " " . $nameserver_address . " > " . $__tmp_dir_path . "/" . $this_nameserver . " 2>&1\"";
		}
		
		
		// PLACE INTO 'PARALLEL' COMMAND //
		$cmd = "parallel ::: " . $__hostcmd_string . " > /dev/null 2>&1";
		
		
		// EXECUTE COMMAND //
		shell_exec($cmd);
		
		
		// GET CMD-RESPONSES //
		$indir = indir($__tmp_dir_path); //get dir contents but without '.' and '..'
		
		//read every tmp nameserver file
		$__nameserver_response = [];
		for($q = 0; $q < sizeof($indir); $q++){
			$this_nameserver = $indir[$q];
			
			//read file
			$response = FILE_read($__tmp_dir_path . "/" . $this_nameserver); //basically a file_get_contents()
			
			//add to nameserver response-list
			$__nameserver_response[$this_nameserver] = $response;
		}
		
		
		
		
		
		/////////////////////////////////////////
		// P A R S E   D N S - R E S P O N S E //
		/////////////////////////////////////////
		
		for($q = 0; $q < sizeof($__nameserver_response); $q++){
			$this_response = array_keys($__nameserver_response)[$q];
			$response = $__nameserver_response[$this_response];
			
			$__lookup[$this_response] = DNSWATCH_LOOKUP_parse($response);
		}
		
		
		
		
		
		/////////////////////////////////////////
		// C O M P A R E   R E F E R E N C E S //
		/////////////////////////////////////////
		
		// RESET SOME STUFF //
		$__randomized_response = NULL;
		
		
		// KICK OUT REFERENCES WITHOUT RESULTS //
		$__useable_references = [];
		for($q = 0; $q < sizeof($__reference_random); $q++){
			$reference = $__reference_random[$q];
			
			if($__lookup[$reference] !== NULL and sizeof($__lookup[$reference]) > 0){
				$__useable_references[] = $reference;
			}
		}
		
		
		// CHECK IF WE HAVE ENOUGH USEABLE REFERENCES //
		$__found = (sizeof($__useable_references) >= $§DNSWATCH_LOOKUP_REFERENCE_USEABLE_MIN);
		
		
		// COMPARE FOUND RESULTS FROM REFERENCES (TO CHECK IF THE DOMAIN USES RANDOMIZED RASPONSES) //
		if($__found){
			//compare all other entries with the first one
			$compare_with = $__useable_references[0];
			
			//start negative and switch to positive if found
			$__randomized_response = false;
			
			//compare
			for($q = 1; $q < sizeof($__useable_references); $q++){
				$this_reference = $__useable_references[$q];
				
				//check $compare_with with this reference
				if($__lookup[$compare_with] != $__lookup[$this_reference]){
					//found another response => this domain uses randomized responses
					$__randomized_response = true;
					
					//stop searching
					$q = sizeof($__useable_references);
				}
			}
		}
		
		
		
		
		
		/////////////////////////////////////////
		// I N T E R P R E T   D N S   D A T A //
		/////////////////////////////////////////
		
		// START WITH EMPTY ARRAY //
		$__data = [];
		
		
		// ADD SOME FLAGS //
		//did the references find something?
		$__data["found"] = (sizeof($__useable_references) >= $§DNSWATCH_LOOKUP_REFERENCE_USEABLE_MIN);
		
		//does this domain use randomized responses?
		$__data["randomized_response"] = $__randomized_response;
		
		
		// ADD REFERENCES //
		$__data["reference"] = [];
		for($q = 0; $q < sizeof($__reference_random); $q++){
			$this_reference = $__reference_random[$q];
			$reference = $__lookup[$this_reference];
			
			//get provider's used nameserver-address
			$address = $__used_nameserver_address[$this_reference];
			
			
			// CHECK IF THE NAMESERVER WAS OFFLINE //
			if($reference === NULL){
				$status = NULL;
			} else {
				//check if the reference has found entries
				$status = ($reference !== NULL and sizeof($reference) > 0);
			}
			
			//add to output-data
			$__data["reference"][] = ["nameserver" => $this_reference, "address" => $address, "status" => $status];
		}
		
		
		if($__found){
			// INTERPRET SEARCHES //
			//start with empty buffer
			$__search = [];
			
			//check if search should get compared or only checked for patterns
			if(!$__randomized_response){
				// CHECK ALL SEARCH RESULTS WITH REFERENCE //
				//compare with the first useable reference
				$compare_with = $__useable_references[0];
				$compare_with_lookup = $__lookup[$compare_with];
				
				//compare
				for($q = 0; $q < sizeof($§DNSWATCH_NAMESERVER); $q++){
					$this_search = array_keys($§DNSWATCH_NAMESERVER)[$q];
					$search = $__lookup[$this_search];
					
					// CHECK IF THIS NAMESERVER WAS OFFLINE //
					if($search === NULL){
						$status = NULL;
					} else {
						//compare results with reference
						$status = ($search === $compare_with_lookup);
					}
					
					//maybe try to guess the cause
					if($status === false){
						$cause = DNSWATCH_LOOKUP_cause_guess($search);
					} else {
						$cause = NULL;
					}
					
					//save for later
					$__search[$this_search] = ["status" => $status, "cause" => $cause];
				}
				
				
			} else {
				// CHECK SEARCH RESULTS FOR CAUSE-PATTERNS //
				for($q = 0; $q < sizeof($§DNSWATCH_NAMESERVER); $q++){
					$this_search = array_keys($§DNSWATCH_NAMESERVER)[$q];
					$search = $__lookup[$this_search];
					
					// CHECK IF THIS NAMESERVER WAS OFFLINE //
					if($search === NULL){
						$status = NULL;
						$cause = NULL;
						
					} else {
						//search for cause-patterns
						$cause = DNSWATCH_LOOKUP_cause_guess($search);
						
						//status is just if we didn't find a cause
						$status = ($cause === NULL);
					}
					
					//save for later
					$__search[$this_search] = ["status" => $status, "cause" => $cause];
				}
			}
			
			
			// ADD SEARCHES //
			$__data["search"] = [];
			for($q = 0; $q < sizeof($__search); $q++){
				$this_search = array_keys($__search)[$q];
				$search = $__search[$this_search];
				
				//get provider's used nameserver-address
				$address = $__used_nameserver_address[$this_search];
				
				//add to output-data
				$__data["search"][] = ["nameserver" => $this_search, "address" => $address, "status" => $search["status"], "cause" => $search["cause"]];
			}
		}
		
		
		
		
		
		
		
		
		
		
		
		
		
		// SAVE IN CACHE //
		DNSWATCH_LOOKUP_cache_store($DOMAIN, $__data);
		
		
		// CLEAN UP SESSION //
		PATH_unlink($__tmp_dir_path); //unlink whole dir recursively with files in it
		
		
		// RETURN LOOKUP DATA //
		return($__data);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*
		Search cache for DOMAIN
			- if found: return cached data
			- else: return 'false'
	*/
	function DNSWATCH_LOOKUP_cache($DOMAIN){
		require(CMS_path("//config/cache.php")); //load config file at 'dnswatch/config/cache.php'
		
		
		// GET CLEANED UP CACHE //
		$__cache = DNSWATCH_LOOKUP_cache_cleanup();
		
		
		// LOOK FOR AN ENTRY //
		if(isset($__cache[$DOMAIN])){
			//return entry's data
			return($__cache[$DOMAIN]["data"]);
			
		} else {
			//nothing found
			return(false);
		}
	}
	
	/*
		Store DATA for DOMAIN in the cache
	*/
	function DNSWATCH_LOOKUP_cache_store($DOMAIN, $DATA){
		require(CMS_path("//config/cache.php")); //load config file at 'dnswatch/config/cache.php'
		
		
		// STORE //
		//get time
		$__time = time();
		
		//save
		DAT_set($§DNSWATCH_CACHE_DAT, $DOMAIN, ["time" => $__time, "data" => $DATA]); //store into database called $§DNSWATCH_CACHE_DAT (filesystem-path) at $DOMAIN (db internal path)
		
		
		// RETURN //
		return(true);
	}
	
	/*
		Do cleanup of cache (maybe with given CACHE data)
	*/
	function DNSWATCH_LOOKUP_cache_cleanup($CACHE = NULL){
		require(CMS_path("//config/cache.php")); //load config file at 'dnswatch/config/cache.php'
		
		
		// MAYBE READ CACHE BY OURSELVES //
		if($CACHE === NULL){
			$CACHE = dat($§DNSWATCH_CACHE_DAT); //get whole database called $§DNSWATCH_CACHE_DAT (filesystem-path)
		}
		
		
		// CHECK ALL CACHED ENTRIES //
		//get time
		$__time = time();
		
		//new cache starts empty, valid entries will get added
		$__cache = [];
		
		//check
		for($q = 0; $q < sizeof($CACHE); $q++){
			$this_entry = array_keys($CACHE)[$q];
			$entry = $CACHE[$this_entry];
			
			//check if still valid
			if($entry["time"] + $§DNSWATCH_CACHE_DURATION >= $__time){
				$__cache[$this_entry] = $entry;
			}
		}
		
		
		// MAYBE SAVE NEW CACHE //
		if($__cache !== $CACHE){
			DAT_set($§DNSWATCH_CACHE_DAT, "/", $__cache); //store into database called $§DNSWATCH_CACHE_DAT (filesystem-path) at / (db internal path)
		}
		
		
		// RETURN NEW CACHE //
		return($__cache);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*
		Parse an output string from 'host' command
	*/
	function DNSWATCH_LOOKUP_parse($RESPONSE){
		// GET LINES //
		$line = explode("\n", $RESPONSE);
		
		
		// PARSE LINE BY LINE //
		$__data = [];
		for($q = 0; $q < sizeof($line); $q++){
			$this_line = $line[$q];
			
			//nameserver not reachable
			if(preg_match("/^;;\sconnection\stimed\sout;\sno\sservers\scould\sbe\sreached$/", $this_line)){
				$__data = NULL;
				break;
			}
			if(preg_match("/^host:\scouldn't\sget\saddress\sfor\s'[a-z0-9.\-]+':\snot\sfound$/", $this_line)){
				$__data = NULL;
				break;
			}
			if(preg_match("/^Host\s[a-z0-9.\-]+\snot\sfound:\s5\(REFUSED\)$/", $this_line)){
				$__data = NULL;
				break;
			}
			
			//a / ipv4
			if(preg_match("/^[a-z0-9.\-]+\shas\saddress\s([0-9]{1,3}\.){3}[0-9]{1,3}$/", $this_line)){
				preg_match("/(?<=has\saddress\s)([0-9]{1,3}\.){3}[0-9]{1,3}$/", $this_line, $a);
				$a = $a[0];
				
				$__data[] = ["type" => "a", "a" => $a];
			}
			
			//aaaa / ipv6
			if(preg_match("/^[a-z0-9.\-]+\shas\sIPv6\saddress\s([0-9a-f]{0,4}:)+[0-9a-f]{0,4}$/", $this_line)){
				preg_match("/(?<=has\sIPv6\saddress\s)([0-9a-f]{0,4}:)+[0-9a-f]{0,4}$/", $this_line, $aaaa);
				$aaaa = $aaaa[0];
				
				$__data[] = ["type" => "aaaa", "aaaa" => $aaaa];
			}
			
			//mx / mailserver
			if(preg_match("/^[a-z0-9.\-]+\smail\sis\shandled\sby\s[0-9]+\s[a-z0-9.\-]+$/", $this_line)){
				preg_match("/(?<=mail\sis\shandled\sby\s)[0-9]+/", $this_line, $mx_prio);
				$mx_prio = (int)$mx_prio[0];
				
				preg_match("/(?<=mail\sis\shandled\sby\s" . $mx_prio . "\s)[a-z0-9.\-]+$/", $this_line, $mx_host);
				$mx_host = $mx_host[0];
				
				$__data[] = ["type" => "mx", "prio" => $mx_prio, "host" => $mx_host];
			}
			
			//cname
			if(preg_match("/^[a-z0-9.\-]+\sis\san\salias\sfor\s[a-z0-9.\-]+$/", $this_line)){
				preg_match("/(?<=is\san\salias\sfor\s)[a-z0-9.\-]+$/", $this_line, $cname);
				$cname = $cname[0];
				
				$__data[] = ["type" => "cname", "cname" => $cname];
			}
		}
		
		
		// RETURN //
		return($__data);
	}
	
	/*
		Try to guess the cause of a blockage (by checking for predifened patterns)
	*/
	function DNSWATCH_LOOKUP_cause_guess($SEARCH){
		require(CMS_path("//config/lookup.php")); //load config file at 'dnswatch/config/lookup.php'
		
		
		// COMPARE ALL POSSIBLE CAUSES... (MATCH FIRST) //
		for($a = 0; $a < sizeof($§DNSWATCH_LOOKUP_CAUSE_GUESS); $a++){
			$this_cause = array_keys($§DNSWATCH_LOOKUP_CAUSE_GUESS)[$a];
			
			//start positive and fail on negative found
			$cAUSE_FOUND = true;
			
			//...and all of this cause's entries (match all)
			for($s = 0; $s < sizeof($§DNSWATCH_LOOKUP_CAUSE_GUESS[$this_cause]); $s++){
				$this_cause_entry = $§DNSWATCH_LOOKUP_CAUSE_GUESS[$this_cause][$s];
				
				
				// SEARCH IN RESULT'S ENTRIES FOR THIS CAUSE-ENTRY //
				if(!in_array($this_cause_entry, $SEARCH)){
					//fail entire test for this cause
					$cAUSE_FOUND = false;
					
					//stop checking this case
					$s = sizeof($§DNSWATCH_LOOKUP_CAUSE_GUESS[$this_cause]);
				}
			}
			
			
			// CHECK IF THIS CAUSE'S CONDITIONS ARE MET //
			if($cAUSE_FOUND === true){
				//found a cause!
				return($this_cause);
			}
		}
		
		
		// DIDN'T FIND A CAUSE //
		return(NULL);
	}
?>
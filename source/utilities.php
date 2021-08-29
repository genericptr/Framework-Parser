<?php
	$sample_memory_time_start = 0;
	
	function user_directory () {
		$user = exec("whoami");
		return "/Users/$user";
	}

	function expand_tilde_path ($path) {
		return str_replace("~", user_directory(), $path);
	}
	
	// expands @ in $path to the directory of the script
	// known as the "root"
	function expand_root_path ($path) {
		return str_replace("@", $_SERVER["PWD"], $path);
	}
	
	// utility for calling expand_root_path() and expand_tilde_path()
	function expand_path ($path) {
		$path = expand_tilde_path($path);
		$path = expand_root_path($path);
		return $path;
	}

  // converts a comma separated list string into an array
	function comma_separated_list (?string $string): ?array {
		if ($string) {
			$string = str_replace(' ', '', $string);
			return explode(',', $string);
		} else {
			return null;
		}
	}
	
	function trim_suffix($subject, $suffix) {
		if (preg_match("/$suffix$/", $subject)) {
			return substr($subject, 0, -strlen($suffix));  
		} else {
			return $subject;
		}
	}
	
	function trim_prefix($subject, $prefix) {
		if (preg_match("/^$prefix/", $subject)) {
			return substr($subject, strlen($prefix), strlen($subject));
		} else {
			return $subject;
		}
	}
	
	function file_extension($file_name) {
	  return substr(strrchr($file_name,'.'),1);
	}

	function remove_file_extension ($file_name) {  
		$ext = strrchr($file_name, '.');  
		if($ext !== false) {  
			$file_name = substr($file_name, 0, -strlen($ext));  
		}  
		return $file_name;  
	}

	function basename_with_extension ($path, $extension) {
		$path = basename($path);
		$path = remove_file_extension($path);
		return $path.".$extension";
	}

	function basename_without_extension ($path) {
		$path = basename($path);
		return remove_file_extension($path);
	}
	
	function directory_contents ($directory, $include_directories = false, $recursive = false, $filter = null) {
		$contents = array();
		if ($handle = @opendir($directory)) {
			while (($file = readdir($handle)) !== false) {
				if (($file != '.') && ($file != '..') && ($file[0] != '.')) {
					$path = "$directory/$file";
					if (is_dir($path)) {
						if ($include_directories) $contents[] = $path;
						if ($recursive) $contents = array_merge($contents, directory_contents_recursive($path, $include_directories, $recursive, $filter));
					} else {
						if ($filter) {
							if (preg_match($filter, $file)) $contents[] = $path;
						} else {
							$contents[] = $path;
						}
					}
				}
			}
			closedir($handle);
		}
		return $contents;
	}
	
	function directory_file_count () {
		$contents = directory_contents($directory, true);
		return count($contents);
	}
	
	function str_has_prefix ($string, $prefix) {
	 $length = strlen($prefix);
	 return (substr($string, 0, $length) === $prefix);
	}

	function str_has_suffix ($string, $suffix) {
	  $length = strlen($suffix);
	  $start  = $length * -1; //negative
	  return (substr($string, $start) === $suffix);
	}
	
	function sample_memory_usage ($wait_seconds = 1) {
		global $sample_memory_time_start;
		
		$time = microtime_float() - $sample_memory_time_start;
		
		if ($time > $wait_seconds) {
			print(bytes_human_readable(memory_get_peak_usage(true))."\n");
			$sample_memory_time_start = microtime_float();
			return true;
		}
	}	
		
	function str_replace_word ($needle, $replacement, $haystack) {
			// convert needle to regex list if it's an array
			if (is_array($needle))
				$needle = implode("|", $needle);
	    $pattern = "/\b($needle)\b/";
	    $haystack = preg_replace($pattern, $replacement, $haystack);
	    return $haystack;
	}

	function istr_replace_word ($needle, $replacement, $haystack) {
			// convert needle to regex list if it's an array
			if (is_array($needle))
				$needle = implode("|", $needle);
	    $pattern = "/\b($needle)\b/i";
	    $haystack = preg_replace($pattern, $replacement, $haystack);
	    return $haystack;
	}
	
	function strabbreviate ($str) {
		for ($i=0; $i < strlen($str); $i++) { 
			$c = $str[$i];
			if (ctype_upper($c)) {
				$abbrv .= $c;
				if (strlen($abbrv) == 2) return $abbrv;
			}
		}
		
		return strtoupper(substr($str, 0, 2));
	}
	
	function microtime_float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	function str_remove_white_space ($string) {
		$string = str_replace("\n", "", $string);
		$string = str_replace("\t", "", $string);
		$string = str_replace(" ", "", $string);
		return $string;
	}
	
	function str_remove_lines ($string) {
		$string = str_replace("\n", "", $string);
		return $string;
	}

	function make_white_space (int $len, string $char = " "): string {
		$string = "";
		for ($i=0; $i < $len; $i++) { 
			$string .= $char;
		}
		return $string;
	}
	
	function preg_replace_balanced(string $pattern, string $subject, bool $match_all = false, int $capture = 0): string {
		$offset = 0;
		while (true) {
			if (preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, $offset)) {
				// get the correct capture
				$match = $matches[$capture];
				// count newlines
				$newlines = substr_count($match[0], "\n");
				$start = $match[1];
				$len = strlen($match[0]);
				// build a replacement string which matches the correct length of the
				// pattern which was found
				$replacement = make_white_space($len);
				$replacement .= make_white_space($newlines, " \n");
				$subject = substr_replace($subject, $replacement, $start, $len);
				$offset = $start + $len;
				if (!$match_all) return $subject;
			} else {
				return $subject;
			}
		}

		return $subject;
	}

	function preg_replace_all ($pattern, $replacement, $subject) {
		if (!$pattern) return $subject;
		
		while (true) {
			$subject = preg_replace($pattern, $replacement, $subject, -1, $count);
			if ($count == 0) break;
		}
		return $subject;
	}
	
	function bytes_human_readable ($bytes, $decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}
	
	// in_array varient for string arrays and case-insensitive comparing
	function in_array_str ($value, array $array) {
		foreach ($array as $array_value) {
			if (strcasecmp($value, $array_value) == 0) return true;
		}
	}
		
	
	function is_offset_anchored_right ($offset, $string, $white_space) {
		for ($i=$offset; $i < strlen($string); $i++) { 
			if (($string[$i] == "\n") || ($string[$i] == " ") || ($string[$i] == "	")) {
				if ($string[$i] == "\n") {
					return true;
				} else {
					if (!$whitespace) break;
				}
			} else {
				break;
			}
		}
	}

	function is_offset_anchored_left ($offset, $string, $white_space) {
		for ($i=$offset - 1; $i >= 0; $i--) { 
			if (($string[$i] == "\n") || ($string[$i] == " ") || ($string[$i] == "	")) {
				if ($string[$i] == "\n") {
					return true;
				} else {
					if (!$white_space) break;
				}
			} else {
				break;
			}
		}

		return false;
	}
	
	function show_string_offset ($offset, $string) {
		if (!is_array($offset)) $offset = array($offset);

		for ($i=0; $i < strlen($string); $i++) { 
			print($string[$i]);

			if (in_array($i, $offset)) print("<--- $i ");
		}
	}
	
	function line_at_offset ($string, $offset) {
		
		if ($string[$offset] == "\n") $offset--;
		
		$end = -1;
		for ($i=$offset; $i < strlen($string); $i++) { 
			if ($string[$i] == "\n") {
				$end = $i;
				break;
			}
		}

		$start = -1;
		for ($i=$offset; $i >= 0; $i--) { 
			if ($string[$i] == "\n") {
				$start = $i;
				break;
			}
		}
		
		$length = $end - $start;
		print("$start/$length\n");
		
		if (($start != -1) && ($end != -1)) {
			return substr($contents, 1, 100);
		} else {
			return "NULL STRING";
		}
	}
	

?>
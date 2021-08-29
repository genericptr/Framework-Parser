<?php

require_once("source/utilities.php");

function find_frameworks_with_objective_c ($directory, $framework = null) {
	if (!file_exists($directory)) die("'$directory' doesn't exist.\n");
	$frameworks = array();
	$contents = directory_contents($directory, true);
	foreach ($contents as $path) {
		$name = basename($path);
		if (is_dir($path)) {
			// recurse frameworks
			if (file_extension($name) == "framework") {
				$frameworks = array_merge($frameworks, find_frameworks_with_objective_c($path, $name));
			} else {
				$frameworks = array_merge($frameworks, find_frameworks_with_objective_c($path, $framework));
			}
		} else {
			// find objective-c in headers
			if ((file_extension($name) == "h") && ($framework)) {
				$lines = file($path);
				foreach ($lines as $line) {
					if (preg_match("/@(protocol|interface)+/", $line)) {
						// print("+ $framework ($name): $line");
						// determine if the framework has an umbrella header
						$umbrella = dirname($path)."/".basename_without_extension($framework).".h";
						if (file_exists($umbrella)) {
							$frameworks[] = $framework;
						} else {
							// print("framework '$framework' has no umbrella header at '$umbrella'.\n");
						}
						return $frameworks;
					}
				}
			}
		}
	}
	
	return array_unique($frameworks);
}

function find_all_frameworks($sdk_path = '') {

	// search using xcrun
	if (!$sdk_path)
		$sdk_path = exec("xcrun --sdk macosx --show-sdk-path");

	// $platform = "/Contents/Developer/Platforms/MacOSX.platform";
	// $sdk = "MacOSX$version.sdk";
	// $path = "$xcode/$platform/Developer/SDKs/$sdk";
	print("Searching for frameworks in '$sdk_path'...\n");
	$frameworks = find_frameworks_with_objective_c($sdk_path);
	// $base = "$path/System/Library/Frameworks";
	$names = array();
	foreach ($frameworks as $name) {
		// TODO: can we determine if the framework will not be found?
		// if (!file_exists("$base/$name")) {
		// 	print("not found $name\n");
		// }
		$names[] = basename_without_extension($name);
	}
	// print("found ".count($frameworks)." frameworks.\n");
	sort($names);
	return $names;
}

// /Applications/Xcode.app/Contents/Developer/Platforms/MacOSX.platform/Developer/SDKs/MacOSX11.3.sdk

$names = find_all_frameworks();

print_r($names);

?>
<?php

require_once("source/utilities.php");

$version = explode('.', PHP_VERSION);
if ($version[0] != 8) {
	die("⚠️ Requires PHP 8.x\n");
}

/*
 * Safely copy files or directories
 */
function copy_file(string $src, string $dst): void {
	if (!file_exists($src)) die("$src doesn't exist.\n");
	exec("cp -r \"$src\" \"$dst\" ");
}

/*
 * Merge old SDKs into new SDK (for CocoaAll)
 */
function merge_sdks(string $old_sdk_path, string $new_sdk_path): array {
	$old_sdk_path = expand_path($old_sdk_path);
	$new_sdk_path = expand_path($new_sdk_path);

	if (!file_exists($old_sdk_path)) die("⚠️ '$old_sdk_path' doesn't exist.\n");
	if (!file_exists($new_sdk_path)) die("⚠️ '$new_sdk_path' doesn't exist.\n");

	$new_files = array();
	$contents = directory_contents($old_sdk_path, true);
	foreach ($contents as $path) {
		$name = basename($path);
		$name_clean = basename_without_extension($name);
		$extension = file_extension($name);

		// find a mathching .pas and folder which isn't in the new sdk
		if ($extension == 'pas' && 
			file_exists($old_sdk_path."/$name_clean") && 
			file_exists($old_sdk_path."/DefinedClasses$name_clean.pas") && 
			!file_exists($new_sdk_path."/$name_clean.pas")) {
			// print("found $name\n");
			$new_files[] = $name_clean;
		}
		
	}

	foreach ($new_files as $name) {
		copy_file("$old_sdk_path/$name.pas", "$new_sdk_path/$name.pas");
		copy_file("$old_sdk_path/DefinedClasses$name.pas", "$new_sdk_path/DefinedClasses$name.pas");
		copy_file("$old_sdk_path/$name", "$new_sdk_path");
	}

	return $new_files;
}

// change_working_directory('~/Developer/ObjectivePascal');

$files = merge_sdks('~/Developer/fpc-git/packages/cocoaint/src', '~/Desktop/CocoaAll');

if (count($files) > 0) {
	foreach ($files as $file) {
		print("\${FPC} $file.pas \${OPTS}\n");
	}
} else {
	print("Nothing to merge!\n");
}

?>
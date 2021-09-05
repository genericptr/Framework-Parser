<?php

require_once("source/utilities.php");
require_once("source/errors.php");

$version = explode('.', PHP_VERSION);
if ($version[0] != 8) {
	die("⚠️ Requires PHP 8.x\n");
}

/*
 * Post-fix hack to add protocol hints to "id" which were
 * removed by accident by generic type cleaning.
 */
function fix_protocols(string $src_sdk_path, string $dst_sdk_path): int {
	$src_sdk_path = expand_path($src_sdk_path);
	$dst_sdk_path = expand_path($dst_sdk_path);

	if (!file_exists($src_sdk_path)) die("⚠️ '$src_sdk_path' doesn't exist.\n");
	if (!file_exists($dst_sdk_path)) die("⚠️ '$dst_sdk_path' doesn't exist.\n");

	$new_files = array();
	$contents = directory_contents($src_sdk_path, true, true);
	$total_changes = 0;

	foreach ($contents as $path) {
		// only consider includes
		if (file_extension($path) != 'inc') continue;

		$lines = file($path);
		$changes = array();

		for ($i=0; $i < count($lines); $i++) { 
			if (preg_match_all('/(\w+): (\b\w+Protocol\b)/', $lines[$i], $matches, PREG_OFFSET_CAPTURE)) {

				$match_count = count($matches[0]);
				$base = str_replace($src_sdk_path, '', $path);
				print("✅ ".$base.": $i ($match_count matches)\n");
				// print_r($matches);

				// find all matches
				for ($j=0; $j < $match_count; $j++) { 
					$name = $matches[1][$j][0];
					$type = $matches[2][$j][0];
					// print("🔥 $name: $type\n");
					// add the change
					if (!isset($changes[$i])) $changes[$i] = array();
					$changes[$i][] = array(
						'pattern' => "$name: id",
						'replace' => "$name: $type"
					);
				}
			}
		}

		// search for matches on the destination
		if (count($changes) > 0) {
			$base = str_replace($src_sdk_path, '', $path);
			$dst_path = $dst_sdk_path.$base;
			if (!file_exists($dst_path)) die("⚠️ '$dst_path' doesn't exist.\n");

			$dst_lines = file($dst_path);
			$dst_text = '';
			for ($j=0; $j < count($dst_lines); $j++) { 
				$line = $dst_lines[$j];
				// apply changes on the line
				if (isset($changes[$j])) {
					foreach ($changes[$j] as $change) {
						// $line = str_replace($change['pattern'], ansi_string(ANSI_BACK_RED, $change['replace']), $line);
						$line = str_replace($change['pattern'], $change['replace'], $line);
					}
				}
				$dst_text .= $line;
			}
			// print($dst_text);
			file_put_contents($dst_path, $dst_text);
			$total_changes++;
		}
	}

	return $total_changes;
}

$total_changes = fix_protocols('~/Desktop/MacOS_11_0', '~/Desktop/MacOS_11_0_old');
print("🔥 $total_changes files were changed.\n");

?>
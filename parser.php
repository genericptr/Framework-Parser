<?php
require("source/framework_loader.php");

// Returns script user input as array
function script_input () {
	$input = array();
	
	foreach($GLOBALS["argv"] as $key => $value) {
		$value = trim($value, "\n");
		$value = ltrim($value, "-");
		$pair = explode("=", $value);
		if (count($pair) == 2) {
			$pair[1] = trim($pair[1], "\"");
			$line_array = explode("\n", $pair[1]);
			if (count($line_array) > 1) {
				$input[$pair[0]] = $line_array;
			} else {
				$input[$pair[0]] = $pair[1];
			}
		} else { // Single values (i.e. -run)
			$input[$value] = true;
		}
	}
	
	return $input;
}

function load_input_command ($command, $input) {
	if (!file_exists($command)) die("The command \"$command\" can not be found.");
	$lines = file($command);
	foreach ($lines as $line) {
		$GLOBALS["argv"][] = $line;
	}
	return script_input();
}

// show error if PWD is doesn't contain us
if (!file_exists($_SERVER["PWD"]."/parser.php")) {
	die("Fatal: The current working directory doesn't contain parser.php. Please \"cd\" to the directory containing parser.php and try again.\n");
}

// get the command line options
if (count($GLOBALS["argv"]) > 1) {
	$input = script_input();
	
	// load input from command file
	if ($input[PARSER_OPTION_COMMAND]) {
		$command = $input[PARSER_OPTION_COMMAND];
		$command = expand_tilde_path($command);		
		$command = expand_root_path($command);				
		$input = load_input_command($command, $input);
	}
	
} else {
	die("No options specified.\n");
}

// split arguments to arrays
if ($input[PARSER_OPTION_FRAMEWORKS]) $input[PARSER_OPTION_FRAMEWORKS] = preg_split("/\s*,\s*/", $input[PARSER_OPTION_FRAMEWORKS]);
if ($input[PARSER_OPTION_ONLY]) $input[PARSER_OPTION_ONLY] = preg_split("/\s*,\s*/", $input[PARSER_OPTION_ONLY]);
if ($input[PARSER_OPTION_IGNORE]) $input[PARSER_OPTION_IGNORE] = preg_split("/\s*,\s*/", $input[PARSER_OPTION_IGNORE]);
if ($input[PARSER_OPTION_SEARCH_PATHS]) $input[PARSER_OPTION_SEARCH_PATHS] = preg_split("/\s*,\s*/", $input[PARSER_OPTION_SEARCH_PATHS]);
if ($input[PARSER_OPTION_GROUP_FRAMEWORKS]) $input[PARSER_OPTION_GROUP_FRAMEWORKS] = preg_split("/\s*,\s*/", $input[PARSER_OPTION_GROUP_FRAMEWORKS]);

// -header overrides
if ($input[PARSER_OPTION_HEADER]) {
	$input[PARSER_OPTION_ALL] = false;
}

// -dryrun overrides
if ($input[PARSER_OPTION_DRY_RUN]) {
	$input[PARSER_OPTION_SHOW] = false;
} 

// -batch overrides
if ($input[PARSER_OPTION_BATCH]) {
	$input[PARSER_OPTION_SDK] = null; 
	$input[PARSER_OPTION_AUTOLOAD_IMPORTED_FRAMEWORKS] = false; 
	$input[PARSER_OPTION_SHOW] = false; 
	$input[PARSER_OPTION_ALL] = true;
}

// -dir overrides
if ($input[PARSER_OPTION_DIRECTORY]) {
	$input[PARSER_OPTION_HEADER] = false;
	$input[PARSER_OPTION_ALL] = true;
	$input[PARSER_OPTION_AUTOLOAD_IMPORTED_FRAMEWORKS] = false; 
	$input[PARSER_OPTION_BATCH] = false; 
}

// -unit overrides
if ($input[PARSER_OPTION_UNIT]) {
	$input[PARSER_OPTION_BUILD_SKELETONS] = false;
	$input[PARSER_OPTION_CLASS_DEFINITIONS] = false;
}

// -all overrides
if ($input[PARSER_OPTION_ALL]) {
	$input[PARSER_OPTION_HEADER] = false;
}

// -autoload_imported_frameworks requires -build_skeletons
if ($input[PARSER_OPTION_AUTOLOAD_IMPORTED_FRAMEWORKS]) {
	$input[PARSER_OPTION_BUILD_SKELETONS] = true;
}

if ($input[PARSER_OPTION_PLAIN_C]) {
	// override the root unit with the c version
	$template_root_unit = "@/templates/c-unit-template.txt";
}

// enable extra message
if ($input[PARSER_OPTION_VERBOSE]) {
}

// remove false/null values
$input = array_filter($input);

// set the global command line options
$command_line_options = $input;

// clean the output directory
if (is_parser_option_enabled(PARSER_OPTION_CLEAN)) {
	clean_output_directory(get_parser_option_path(PARSER_OPTION_CLEAN));
	die;
}

// create framework loader
$loader = new FrameworkLoader();

// batch process or process single header
if (is_parser_option_enabled(PARSER_OPTION_ALL)) {
	$loader->process_batch(get_parser_option(PARSER_OPTION_IGNORE), get_parser_option(PARSER_OPTION_ONLY));
	
	// apply patch to output directory
	if (is_parser_option_enabled(PARSER_OPTION_PATCH)) {
		$loader->apply_patch(get_parser_option_path(PARSER_OPTION_PATCH));
	}
	
}

if (is_parser_option_enabled(PARSER_OPTION_HEADER)) {
	$loader->process_header(get_parser_option(PARSER_OPTION_HEADER));
}

// ??? testing!
if (is_parser_option_enabled(PARSER_OPTION_DIRECTORY)) {
	die("test!");
//	$loader->process_directory(get_parser_option(PARSER_OPTION_DIRECTORY));
}

?>
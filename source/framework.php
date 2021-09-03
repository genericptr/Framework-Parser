<?php

require_once("errors.php");
require_once("memory_manager.php");
require_once("templates.php");

define("FRAMEWORK_BASE_DIRECTORY", 1);
define("FRAMEWORK_OUTPUT_DIRECTORY", 2);

/**
* Defines a single loaded framework
*/
class Framework extends MemoryManager {
		
	/**
	 * Inheritable Properties
	 */
	
	public $ignore_types = array();
	public $ignore_headers = array();
	public $external_macros = array();
	public $inline_macros = array();
	public $replace_types = array();
	public $declared_types = array();							// additional types which are declared in the framework and should not create opaque types
	public $pointer_types = array();							// available pointer equivalents by type
	public $availability_macros = array();				// availability macros by pattern => replacement pattern
	public $replacement_patterns = array();				// replacement patterns by pattern => replacement pattern
	public $define_replacements = array();				// patterns to replace #define values
	public $implicit_pointers = array();					// types which are implicit pointers
	public $uses = array();
	public $support_frameworks = array();
	public $imports = array();
	public $include_imported_frameworks = false;
	public $settings = array();										// raw array of settings from xml node
	
	// *** DEPRECATED ***
	// use <replacement_patterns> which is more powerful
	// and dupliates the functionality of <remove_macros>
	public $remove_macros = array();							// macros to strip from source (using preg_replace)
	
	/**
	 * Other
	 */
	
	public $auto_loaded = false;									// the framework was auto-loaded
	public $loadable = true;
	public $dependencies = 0;											// count of dependencies on other frameworks (like reference counted memory for the symbol table)
	public $finalized = false;										// the framework has been finalized and removed from the symbol table
	public $header_paths = array();								// array of header paths in the framework
	
	/**
	 * Private (static properties)
	 */
	
	private $name; 								// name of the framework (as defined in frameworks.xml)	
	private $parent;
	private $root; 
	private $path;
	private $umbrella;
	
	/**
	 * Bundle directories
	 */
	
	private $directory_frameworks = "Frameworks";
	private $directory_headers = "Headers";
	
	/**
	 * Private
	 */
	
	private $print = true;
	private $finalize = true;
	private $imported_frameworks = array();							// frameworks that were declared in #import directives across all headers
	private $imported_frameworks_loaded = null;
	private $headers = array();													// array of parsed Header objects
	private $raw_directory = false;											// the framework is a raw directory of header files
	/**
	 * Accessors
	 */
	
	public function set_raw_directory ($path) {
		$this->set_path($path);
		$this->set_headers_directory("");
		$this->set_umbrella_header("");
		$this->raw_directory = true;
	}	
	
	public function set_print ($print) {
		$this->print = $print;
	}
	
	public function set_finalize ($finalize) {
		$this->finalize = $finalize;
	}
	
	public function set_name ($name) {
		$this->name = $name;
	}
	
	public function set_parent ($parent) {
		$this->parent = $parent;
	}
	
	public function set_root ($root) {
		$this->root = $root;
	}

	public function set_umbrella_header ($name) {
		$this->umbrella = $name;
	}
	
	public function set_headers_directory ($name) {
		$this->directory_headers = $name;
	}

	// sets the full path of the framework
	public function set_path ($path) {
		$this->path = $path;
	}

	// sets the director the framework resides in
	public function set_directory ($directory) {
		$this->path = $directory."/".$this->name.".framework";
	}
	
	public function get_headers () {
		return $this->headers;
	}
		
	// returns an array of imported frameworks that have been defined
	public function get_imported_frameworks () {
		
		if (!$this->imported_frameworks_loaded) {
			$this->imported_frameworks_loaded = array();
			foreach ($this->imported_frameworks as $name) {
				if ($framework = FrameworkLoader::loader()->find_framework($name)) {
					if ($framework != $this) $this->imported_frameworks_loaded[] = $framework;
				}
			}
		}
		
		return $this->imported_frameworks_loaded;
	}
	
	// returns an array of frameworks that were imported
	// but not defined in the framework loader
	public function get_undefined_imported_frameworks () {
		$results = array();
		
		foreach ($this->imported_frameworks as $name) {
			if (!FrameworkLoader::loader()->is_framework_defined($name)) {
				$results[] = $name;
			}
		}
		
		return $results;
	}
	
	// returns an array of private framework (names) in the 
	// framework bundle
	public function get_private_frameworks () {
		$frameworks = array();
		$path = $this->get_path()."/".$this->directory_frameworks;
		if (file_exists($path)) {
			if ($handle = @opendir($path)) {
				while (($file = readdir($handle))) {
					if (preg_match("/\.framework$/", $file)) $frameworks[] = remove_file_extension($file);
				}
				closedir($handle);
			}
		}
		return $frameworks;
	}
	
	// returns true if the framework has been imported via #import/include
	public function is_framework_imported (Framework $framework) {
		foreach ($this->imported_frameworks as $name) {
			if (strcasecmp($framework->get_name(), $name) == 0) return true;
		}
	}
	
	public function is_found () {
		return file_exists($this->path);
	}
	
	public function has_valid_headers () {
		return file_exists($this->get_headers_directory());
	}
	
	public function has_valid_output () {
		return file_exists($this->get_output_directory());
	}
	
	public function is_base () {
		return ($this->name == BASE_FRAMEWORK);
	}
	
	// static frameworks are used for harvesting xml entries
	// but can not be loaded directly
	public function is_static () {
		return ($this->settings["static"]);
	}
	
	// returns true if the framework is disabled for batch
	// parsing (specified with <disabled>)
	public function is_disabled () {
		return ($this->settings["disabled"]);
	}
			
	// returns true if the framework can print in batch processing
	public function can_print () {
		return $this->print;
	}
	
	// returns true if the framework can finalize its symbols
	public function can_finalize () {
		return $this->finalize;
	}
		
	public function get_parent () {
		return $this->parent;
	}
	
	public function get_name () {
		return $this->name;
	}
			
	// returns the path of the framework file where is was loaded from
	public function get_path () {
		return $this->path;
	}
	
	// returns the name of the directory used for printing
	public function get_root_directory () {
		return dirname($this->get_root());
	}
	
	// returns the output directory for the framework
	public function get_output_directory ($directory = FRAMEWORK_OUTPUT_DIRECTORY) {
		if ($directory == FRAMEWORK_BASE_DIRECTORY) {
			return rtrim(get_parser_option_path(PARSER_OPTION_OUTPUT), "/");
		} else {
			return rtrim(get_parser_option_path(PARSER_OPTION_OUTPUT), "/").$this->get_root_directory();
		}
	}
	
	// returns the partial path of the root unit
	public function get_root () {
		return $this->root;
	}
	
	// returns the root unit name without extension
	// i.e. /AppKit/Sources.inc returns "Sources"
	public function get_root_name () {
		return basename_without_extension($this->root);
	}
	
	public function get_umbrella_header () {
		return $this->umbrella;
	}
		
	// returns the SDK the framework is in
	public function get_sdk () {
		if (preg_match("/\/([a-zA-Z0-9.]+)\.sdk/", $this->path, $captures)) {
			return $captures[1];
		}
	}	
		
	// returns the path of a private framework inside the framework bundle
	// which has been specified by name (without .framework extension)
	public function get_private_framework_path ($name) {
		$path = $this->get_path()."/$this->directory_frameworks/$name.framework";
		if (file_exists($path)) return $path;			
	}
	
	// returns the path to the headers directory
	public function get_headers_directory () {
		if ($this->directory_headers) {
			return $this->path."/".$this->directory_headers;
		} else {
			return $this->path;
		}
	}

	/**
	 * Modifying
	 */

	public function add_header (Header $header) {
		$this->headers[] = &$header;
	}
		
	public function add_imported_framework ($name) {
		
		// don't import ourself
		if ($this->name == $name) return;
		
		// reload imported frameworks
		if ($this->imported_frameworks_loaded) $this->imported_frameworks_loaded = null;
		
		if (!in_array($name, $this->imported_frameworks)) $this->imported_frameworks[] = $name;
	}

	// dynamically add external macros during parse time
	// note: parsers must update their patterns for effects
	// to take place or wait until a new header is parsed
	public function add_external_macro ($name) {
		if (!in_array($name, $this->external_macros)) $this->external_macros[] = $name;
	}

	/**
	 * Skeletons
	 */
	
	private function add_additional_uses (array $frameworks, array $uses) {
		
		// add imported frameworks
		foreach ($this->get_imported_frameworks() as $framework) {
			if ($this->include_imported_frameworks) {
				if ($framework->has_valid_output()) array_unshift($uses, $framework->get_name());
			}
		}

		// we need to add formal declarations first and the actual
		// unit after so classes can override their formal declaration
		foreach ($this->support_frameworks as $name) array_unshift($uses, $name);
		foreach ($frameworks as $framework) array_unshift($uses, TEMPLATE_FILE_DEFINED_CLASSES.$framework->get_name());
		foreach ($this->support_frameworks as $name) array_unshift($uses, TEMPLATE_FILE_DEFINED_CLASSES.$name);
			
		//print_r(array_unique($uses));
		return array_unique($uses);
	}
	
	private function build_root_unit () {
		global $template_root_unit;
		global $template_common_types;
		global $template_common_macros;
		global $template_availability_macros;

		// load the template string
		if (is_parser_option_enabled(PARSER_OPTION_TEMPLATE)) {
			$template = file_get_contents(get_parser_option_path(PARSER_OPTION_TEMPLATE));
		} else {
			$template = file_get_contents(expand_path($template_root_unit));
		}
				
		// if -all_units is enabled use the template from the parameter
		// or the default template is none is specified
		if (is_parser_option_enabled(PARSER_OPTION_ALL_UNITS)) {
			if (get_parser_option(PARSER_OPTION_ALL_UNITS) != 1) {
				$template = file_get_contents(get_parser_option_path(PARSER_OPTION_ALL_UNITS));
			} else {
				$template = file_get_contents(expand_path($template_root_unit));
			}
		}
		
		// group template
		$template = str_replace(TEMPLATE_KEY_GROUP, strtoupper(get_parser_option(PARSER_OPTION_GROUP)), $template);
		
		// group options
		$group_frameworks_dependent = FrameworkLoader::loader()->get_group_frameworks(array(GROUP_FRAMEWORK_OPTION_INDEPENDENT, GROUP_FRAMEWORK_OPTION_USES));
		$group_frameworks_included = FrameworkLoader::loader()->get_group_frameworks(GROUP_FRAMEWORK_OPTION_USES);
		$group_name = get_parser_option(PARSER_OPTION_GROUP);
				
		// group conflict
		// if the framework is included in the group unit then show an error
		// so the unit can't be added twice
		if (in_array($this->name, $group_frameworks_dependent)) {
			$string = "{\$ifdef ".strtoupper($group_name)."}\n";
			$string .= "{\$fatal \"$this->name can't be used because -d".strtoupper($group_name)." has been declared.\"}\n";
			$string .= "{\$endif}\n";
		} else {
			if (FrameworkLoader::loader()->is_group_framework_option_enabled($this->name, array(GROUP_FRAMEWORK_OPTION_USES, GROUP_FRAMEWORK_OPTION_INDEPENDENT))) {
				$string = "\n{\$define GROUP_INDEPENDENT}\n";
			} else {
				$string = "";
			}
		}
		$template = str_replace(TEMPLATE_KEY_GROUP_CONFLICT, $string, $template);
		
		// build list of required frameworks
		// ??? we need a list of these in frameworks.xml - <required_frameworks>
		$frameworks = array();
		$frameworks[] = $this;
				
		// replace template macros
		$template = str_replace(TEMPLATE_KEY_NAME, $this->name, $template);
		$template = str_replace(TEMPLATE_KEY_NAME_UPPER_CASE, strtoupper($this->name), $template);

		// common types/macros
		$template = str_replace(TEMPLATE_KEY_AVAILABILITY_MACROS, $template_availability_macros, $template);
		$template = str_replace(TEMPLATE_KEY_COMMON_TYPES, $template_common_types, $template);
		$template = str_replace(TEMPLATE_KEY_COMMON_MACROS, $template_common_macros, $template);

		// $linkframeworks
		$string = "";
		foreach ($frameworks as $framework) $string .= "{\$linkframework ".$framework->get_name()."}\n";
		$template = str_replace(TEMPLATE_KEY_LINK_FRAMEWORK, trim($string), $template);
		
		// uses
		$uses = $this->add_additional_uses($frameworks, $this->uses);
				
		$template = str_replace(TEMPLATE_KEY_USES, implode(", ", $uses), $template);
		
		// uses (minimal with out extra uses from XML definition, used for MacOSAll compatibility)
		$uses = $this->add_additional_uses($frameworks, array());
		$template = str_replace(TEMPLATE_KEY_USES_MINIMAL, implode(", ", $uses), $template);
		
		// group uses
		// when the group unit switch is enabled (like -dCOCOAALL) we filter out units from the group unit
		// (as defined in -group_frameworks) and add the group unit name
		$uses = $this->add_additional_uses($frameworks, $this->uses);
		$uses[] = $group_name;
		$group_units = array_diff($uses, $group_frameworks_included);
		$template = str_replace(TEMPLATE_KEY_USES_GROUP, implode(", ", $group_units), $template);
		
		// group uses (for units like CocoaAll/iPhoneAll)
		$uses = $this->add_additional_uses($frameworks, array());
		$uses[] = $group_name;
		$group_units = array_diff($uses, $group_frameworks_included);		
		$template = str_replace(TEMPLATE_KEY_USES_MINIMAL_GROUP, implode(", ", $group_units), $template);
		
		// includes
		$string = "";
		foreach ($frameworks as $framework) {
			$include_directory = trim($framework->get_root_directory(), "/");
			$string .= "{\$include ".$include_directory."/".$framework->get_root_name().".inc}\n";
		}
		$template = str_replace(TEMPLATE_KEY_INCLUDE, trim($string), $template);
		
		// undefined types
		$string = "";
		foreach ($frameworks as $framework) {
			$include_directory = trim($framework->get_root_directory(), "/");
			$string .= "{\$include ".$include_directory."/".TEMPLATE_FILE_UNDEFINED_TYPES.".inc}\n";
		}
		$template = str_replace(TEMPLATE_KEY_UNDEFINED_TYPES, trim($string), $template);

		// inline functions
		$string = "";
		foreach ($frameworks as $framework) {
			$include_directory = trim($framework->get_root_directory(), "/");
			$string .= "{\$include ".$include_directory."/".TEMPLATE_FILE_INLINE_FUNCTIONS.".inc}\n";
		}
		$template = str_replace(TEMPLATE_KEY_INLINE_FUNCTIONS, trim($string), $template);
		
		//print($template);
		return $template;
	}
	
	// utility method to write a file to the frameworks output
	private function make_file ($base_directory, $name, $contents, $safe_mode = false) {
		$path = $this->get_output_directory($base_directory)."/".$name;
		if ($safe_mode) {
			if (!file_exists($path)) {
				file_put_contents($path, $contents);
				ErrorReporting::errors()->add_message("Wrote $path");
			} else {
				ErrorReporting::errors()->add_note(basename($path)." already exists.");
			}
		} else {
			file_put_contents($path, $contents);
		}
	}
		
	// analyzes the frameworks headers from the umbrella and other sources
	public function load_headers ($directory) {
		ErrorReporting::errors()->add_message("Loading headers from $directory.");
		$headers = directory_contents($directory, false, false, "/\.h$/i");
		if (count($headers) == 0) ErrorReporting::errors()->add_note("No headers were found.");
		return $headers;
	}	
				
	// analyzes the frameworks headers from the umbrella and other sources
	public function analyze_headers () {
		ErrorReporting::errors()->add_message("Analyzing headers in ".$this->get_name().".");
		$headers = array();
		
		// no umbrella defined, bail!
		//if (!$this->get_umbrella_header()) {
		//	ErrorReporting::errors()->add_note("Framework ".$this->get_name()." has no <umbrella> header defined so the skeleton can't be built.");
		//	return;
		//}
		
		// load headers from directory
		if ($this->raw_directory) {
			$headers = $this->load_headers($this->get_path());
		}
		
		// sort the the umbrella header to build root include
		if ($this->get_umbrella_header()) {
			$path = $this->get_headers_directory()."/".$this->get_umbrella_header();
			if (file_exists($path)) {
				$sorter = new HeaderSorter($path, $this);
				$headers = $sorter->sort();

				// build headers array with full path
				//foreach ($sorted_headers as $header) {
				//	$header_path = dirname($path)."/".$header;
				//	if (file_exists($header_path)) $headers[] = $header_path;
				//}
				
				// add the umbrella to the start of the array
				// if it wasn't added yet
				if (!in_array($path, $headers)) array_unshift($headers, $path);
			} else {
				// there is no umbrella header, use explicit headers
				ErrorReporting::errors()->add_note("The framework \"$this->name\" has no umbrella header (\"$path\").");
			}
		}
	
		// build root include using manually defined <import> headers
		if ($this->imports) {
			foreach ($this->imports as $name) {
				$path = $this->get_headers_directory()."/".trim($name, "/");
				if (file_exists($path)) {
					// we don't use the header sorter but we need to 
					// get all imported frameworks
					HeaderSorter::harvest_imports($path, $this);
					$headers[] = $path;
				} else {
					ErrorReporting::errors()->add_note("The import \"$name\" can't be found in the framework $this->name.");
				}
			}
		}
				
		// remove duplicate values
		$headers = array_unique($headers);

		// set the frameworks header paths for the framework loader
		$this->header_paths = $headers;
		
		// report errors
		if (!$headers) {
			if ($this->auto_loaded) {
				ErrorReporting::errors()->add_warning("The framework \"$this->name\" can not find any headers in the umbrella \"$this->umbrella\".");
			} else {
				ErrorReporting::errors()->add_fatal("The framework \"$this->name\" can not find any headers in the umbrella \"$this->umbrella\".");
			}
		}
	}

	// builds the root include from the umbrella header
	// specified with the <umbrella> key in frameworks.xml
	public function build_skeleton () {
		if ($this->header_paths) {
			ErrorReporting::errors()->add_message("Building skeleton for ".$this->get_name().".");

			if (!is_parser_option_enabled(PARSER_OPTION_DRY_RUN) && $this->can_print()) {
				$lines = array();

				// define a macro we can use to determine if the
				// framework has been loaded in other places
				$lines[] = "{\$define FRAMEWORK_LOADED_".strtoupper($this->get_name())."}";

				// here we need to determine which headers are duplicates
				// and rename them uniquely then make ifdef's so they can be changed on the 
				// command line using -d
				
				// checking the <imports> paths will help if you can determine partial paths like /ES1/gl.h
				// by prefixing the header name with the path: ES1_gl.inc
				foreach ($this->header_paths as $header) {
					if (!$this->ignore_header(basename($header))) $lines[] = "{\$include ".basename($header, ".h").".inc}";
				}

				// NOTE: bug in fpc requires an extra line at the end of the file
				// or the last include will trigger an error
				$lines[] = "";

				// make output directory
				$directory = $this->get_output_directory();
				@mkdir($directory);

				if (file_exists($directory)) {

					// print root include
					$contents = implode("\n", $lines);
					$this->make_file(FRAMEWORK_BASE_DIRECTORY, $this->get_root(), $contents, is_parser_option_enabled(PARSER_OPTION_SAFE_WRITE));

					// framework support files
					$this->make_file(FRAMEWORK_OUTPUT_DIRECTORY, TEMPLATE_FILE_UNDEFINED_TYPES.".inc", "", true);
					$this->make_file(FRAMEWORK_OUTPUT_DIRECTORY, TEMPLATE_FILE_INLINE_FUNCTIONS.".inc", "", true);

					// build root unit
					if ((!is_parser_option_enabled(PARSER_OPTION_GROUP)) || (is_parser_option_enabled(PARSER_OPTION_GROUP) && is_parser_option_enabled(PARSER_OPTION_ALL_UNITS))) {
						$contents = $this->build_root_unit();
						$this->make_file(FRAMEWORK_BASE_DIRECTORY, $this->get_name().".pas", $contents, is_parser_option_enabled(PARSER_OPTION_SAFE_WRITE));
					}

					// base support files
					$this->make_file(FRAMEWORK_BASE_DIRECTORY, TEMPLATE_FILE_UNDEFINED_TYPES.".inc", "", true);
				}
			}
		} else {
			if (is_parser_option_enabled(PARSER_OPTION_VERBOSE)) ErrorReporting::errors()->add_note("The skeleton for \"".$this->get_name()."\" could not be built because header path is null.");
		}

	}

	/**
	 * Methods
	 */
	
	// return if the type is has been defined as known pointer
	public function is_type_defined_pointer ($type) {
		return (in_array($type, $this->pointer_types));
	}
	
	// returns true if the type should be ignored
	public function ignore_type ($type) {
		foreach ($this->ignore_types as $_type) {
			if (preg_match("/\b$_type\b/i", $type)) {
				//print("--- ignore $type from $_type\n");
				return true;
			}
		}
		//return (in_array($type, $this->ignore_types));
	}
			
	// returns true if the header should be ignored (not parsed)
	public function ignore_header ($name) {
		return (in_array($name, $this->ignore_headers));
	}	
	
	// applies a define replacement to the value and returns
	// true if the value was replaced
	public function apply_define_replacement ($value) {		
		foreach ($this->define_replacements as $pattern => $replacement) {
			if (preg_match($pattern, $value)) {
				return preg_replace($pattern, $replacement, $value);
			}
		}
	}
		
	public function replace_type ($type) {
		foreach ($this->replace_types as $objc_type => $replace_type) {
			
			// if the type begins with / we will treat it as a regular expression
			if ($objc_type[0] == "/") {
				if (preg_match($objc_type, $type)) return $replace_type;
				continue;
			}
			
			// perform some magic by replacing spaces with regex patterns
			// so spaces don't affect any of the types
			$objc_type = str_replace(" ", "\s+", $objc_type);

			if (preg_match("/^$objc_type$/i", $type)) return $replace_type;
		}
		
		return $type;
	}	
		
	/**
	 * Loading
	 */	
			
	// inherits values from the specified framework
	private function inherit_from (Framework $framework, $base) {

		if (is_parser_option_enabled(PARSER_OPTION_VERBOSE)) {
			ErrorReporting::errors()->add_note("    ".$this->get_name()." inherits from ".$framework->get_name(), ErrorReporting::NO_PREFIX);
		}

		// use the parent if the framework doesn't specify a key which overrides the parent
		// these values can't be loaded if the framework is static
		if (!$this->is_static()) {
			if (($framework->settings["root"]) && (!$this->root)) $this->root = $framework->settings["root"];
			if (($framework->settings["path"]) && (!$this->path)) $this->path = $framework->settings["path"];
			if (($framework->settings["umbrella"]) && (!$this->umbrella)) $this->umbrella = $framework->settings["umbrella"];
		}
		
		// the most recent value always overrides the previous
		if ($framework->settings["include_imported_frameworks"]) $this->include_imported_frameworks = $framework->settings["include_imported_frameworks"];
				
		// merge parent values into current framework
		$this->external_macros = array_merge($this->external_macros, $framework->external_macros);
		$this->inline_macros = array_merge($this->inline_macros, $framework->inline_macros);
		$this->ignore_types = array_merge($this->ignore_types, $framework->ignore_types);
		$this->replace_types = array_merge($this->replace_types, $framework->replace_types);		
		$this->declared_types = array_merge($this->declared_types, $framework->declared_types);		
		$this->ignore_headers = array_merge($this->ignore_headers, $framework->ignore_headers);	
		$this->implicit_pointers = array_merge($this->implicit_pointers, $framework->implicit_pointers);	
		$this->remove_macros = array_merge($this->remove_macros, $framework->remove_macros);		
		$this->pointer_types = array_merge($this->pointer_types, $framework->pointer_types);		
		$this->availability_macros = array_merge($this->availability_macros, $framework->availability_macros);
		$this->replacement_patterns = array_merge($this->replacement_patterns, $framework->replacement_patterns);
		$this->define_replacements = array_merge($this->define_replacements, $framework->define_replacements);
		$this->uses = array_merge($this->uses, $framework->uses);		
		$this->imports = array_merge($this->imports, $framework->imports);		
		$this->support_frameworks = array_merge($this->support_frameworks, $framework->support_frameworks);		

		// recurse into framework
		if ($framework->get_parent()) {
			if ($parent = FrameworkLoader::loader()->find_framework($framework->get_parent())) $this->inherit_from($parent, false);
		}
	}
	
	// applies macros for the framework to the string
	public function apply_macros ($string) {
		$string = str_replace(FRAMEWORK_XML_MACRO_NAME, $this->name, $string);
		$string = str_replace(FRAMEWORK_XML_MACRO_NAME_LOWER_CASE, strtolower($this->name), $string);
		$string = str_replace(FRAMEWORK_XML_MACRO_NAME_UPPER_CASE, strtoupper($this->name), $string);
		$string = str_replace(FRAMEWORK_XML_MACRO_NAME_ABBREVIATION, strabbreviate($this->name), $string);
		$string = str_replace(FRAMEWORK_XML_MACRO_NAMES_PREGEX, "(".strabbreviate($this->name)."|".strtoupper($this->name).")+", $string);
		
		if (get_parser_option(PARSER_OPTION_SDK)) $string = str_replace(FRAMEWORK_XML_MACRO_SDK, get_parser_option(PARSER_OPTION_SDK), $string);
		
		return $string;
	}
		
	// loads framework settings to instance variables
	public function load ($xml_path) {
		
		// inherit from the parent
		if (($this->parent) && (!$this->is_base())) {
			if ($parent = FrameworkLoader::loader()->find_framework($this->parent)) {
				$this->inherit_from($parent, true);
			} else {
				ErrorReporting::errors()->add_fatal("The framework definition for \"$this->name\" (defined in ".basename($xml_path).") can not inherit from \"$this->parent\" because the framework has not been defined yet.");
			}
		}
		
		// replace xml macros in all (string) settings
		if (!$this->is_static()) {
			foreach ($this->settings as $key => $value) {
				if (!is_string($value)) continue;
				$this->settings[$key] = $this->apply_macros($value);
			}
		}

		// apply options from framework definition
		if ($this->settings["external_macros"]) $this->external_macros = array_merge($this->external_macros, preg_split("/\s*,\s*/", $this->settings["external_macros"]));
		if ($this->settings["inline_macros"]) $this->inline_macros = array_merge($this->inline_macros, preg_split("/\s*,\s*/", $this->settings["inline_macros"]));
		if ($this->settings["ignore_types"]) $this->ignore_types = array_merge($this->ignore_types, preg_split("/\s*,\s*/", $this->settings["ignore_types"]));
		if ($this->settings["declared_types"]) $this->declared_types = array_merge($this->declared_types, preg_split("/\s*,\s*/", $this->settings["declared_types"]));
		if ($this->settings["ignore_headers"]) $this->ignore_headers = array_merge($this->ignore_headers, preg_split("/\s*,\s*/", $this->settings["ignore_headers"]));
		if ($this->settings["implicit_pointers"]) $this->implicit_pointers = array_merge($this->implicit_pointers, preg_split("/\s*,\s*/", $this->settings["implicit_pointers"]));
		if ($this->settings["uses"]) $this->uses = array_merge($this->uses, preg_split("/\s*,\s*/", $this->settings["uses"]));
		if ($this->settings["imports"]) $this->imports = array_merge($this->imports, preg_split("/\s*,\s*/", $this->settings["imports"]));
		if ($this->settings["support_frameworks"]) $this->support_frameworks = array_merge($this->support_frameworks, preg_split("/\s*,\s*/", $this->settings["support_frameworks"]));
						
		if ($this->settings["remove_macros"]) {
			foreach ($this->settings["remove_macros"]->value as $value) {
				if (!in_array($value, $this->remove_macros)) $this->remove_macros[] = (string)$value;
			}
		}
		
		if ($this->settings["replace_types"]) {
			foreach ($this->settings["replace_types"]->value as $type) {
				$pair = preg_split("/\s*=\s*/", (string)$type);
				$this->replace_types[$pair[0]] = $pair[1];
			}
		}
		
		if ($this->settings["pointer_types"]) {
			foreach ($this->settings["pointer_types"]->value as $type) {
				$pair = preg_split("/\s*=\s*/", (string)$type);
				$this->pointer_types[$pair[0]] = $pair[1];
			}
		}
		
		/*
			make a ReplacePattern class which
			has the pattern, replacement and function
			plus some functions to apply. 
			
			availality could have a subclass since it has special needs
			that are added into HeaderParser but would be better reflected here
			
			remove macros can be put into this system also until
			they removed from the API
		*/
		
		if ($this->settings["availability_macros"]) {
			foreach ($this->settings["availability_macros"]->macro as $macro) {
				$this->availability_macros[(string)$macro->pattern] = (string)$macro->replacement;
			}
		}
		
		if ($this->settings["replacement_patterns"]) {
			foreach ($this->settings["replacement_patterns"]->pattern as $pattern) {
				$this->replacement_patterns[(string)$pattern->pattern] = (string)$pattern->replacement;
			}
		}
		
		if ($this->settings["define_replacements"]) {
			foreach ($this->settings["define_replacements"]->define as $define) {
				$this->define_replacements[(string)$define->pattern] = (string)$define->replacement;
			}
		}
				
		// apply macros to non-static properties
		if (!$this->is_static()) {
			foreach ($this->ignore_types as $key => $value) $this->ignore_types[$key] = $this->apply_macros($value);
			foreach ($this->remove_macros as $key => $value) $this->remove_macros[$key] = $this->apply_macros($value);
			foreach ($this->replace_types as $key => $value) $this->replace_types[$key] = $this->apply_macros($value);
			foreach ($this->external_macros as $key => $value) $this->external_macros[$key] = $this->apply_macros($value);
			foreach ($this->inline_macros as $key => $value) $this->inline_macros[$key] = $this->apply_macros($value);
			
			// replace keys and values for replacement patterns
			foreach ($this->replacement_patterns as $key => $value) {
				$old_keys[] = $key;
				$new_key = $this->apply_macros($key);
				$this->replacement_patterns[$new_key] = $this->apply_macros($value);
				// remove the old key if it changed
				if ($new_key != $key)
					unset($this->replacement_patterns[$key]);
			}

			$this->root = $this->apply_macros($this->root);
			$this->path = $this->apply_macros($this->path);
			$this->umbrella = $this->apply_macros($this->umbrella);
		}

		// make values unique
		$this->external_macros = array_unique($this->external_macros);
		$this->inline_macros = array_unique($this->inline_macros);
		$this->ignore_types = array_unique($this->ignore_types);
		$this->declared_types = array_unique($this->declared_types);
		$this->ignore_headers = array_unique($this->ignore_headers);
		$this->implicit_pointers = array_unique($this->implicit_pointers);
		$this->remove_macros = array_unique($this->remove_macros);
		$this->uses = array_unique($this->uses);
		$this->imports = array_unique($this->imports);
		$this->support_frameworks = array_unique($this->support_frameworks);

		// filter null/empty values
		$this->external_macros = array_filter($this->external_macros);
		$this->inline_macros = array_filter($this->inline_macros);
		$this->ignore_types = array_filter($this->ignore_types);
		$this->declared_types = array_filter($this->declared_types);
		$this->ignore_headers = array_filter($this->ignore_headers);
		$this->implicit_pointers = array_filter($this->implicit_pointers);
		$this->remove_macros = array_filter($this->remove_macros);
		$this->uses = array_filter($this->uses);
		$this->imports = array_filter($this->imports);
		$this->support_frameworks = array_filter($this->support_frameworks);
		
		// reverse values so top-level frameworks take precedence
		$this->external_macros = array_reverse($this->external_macros);
		$this->inline_macros = array_reverse($this->inline_macros);
		$this->remove_macros = array_reverse($this->remove_macros);
		
		// add support frameworks as imported
		foreach ($this->support_frameworks as $name) {
			$this->add_imported_framework($name);
		}

		// add types from the framework to the symbol table
		// so they aren't included as opaque
		foreach ($this->replace_types as $key => $type) {
			SymbolTable::table()->add_declared_type($type);
		}
		
		foreach ($this->pointer_types as $key => $type) {
			SymbolTable::table()->add_declared_type($type);
		}
		
		foreach ($this->declared_types as $type) {
			SymbolTable::table()->add_declared_type($type);
		}
		
		foreach ($this->implicit_pointers as $type) {
			SymbolTable::table()->add_implicit_pointer($type);
		}
		
		
		//print_r($this);
	}
	
	// prints some info about the framework for debugging
	public function print_info () {
		print($this->name."\n");
		print("  root: ".$this->root."\n");
		print("  parent: ".$this->parent."\n");
		print("  path: ".$this->path."\n");
		print("  umbrella: ".$this->umbrella."\n");
		if (count($this->imported_frameworks) > 0) {
			print("  imported frameworks:\n");
			foreach ($this->imported_frameworks as $framework) print("    $framework\n");	
		}		
		
		//print_r($this->uses);
	}
	
	/**
	 * Constructors
	 */
	
	// creates a new "dummy" framework with empty values (used for processing single files outside frameworks)
	public static function dummy () {
		return $framework = new Framework(null);
	}
	
	// creates a clone (non-static) of $frameworks and loads the framework
	public static function clone_existing ($name, Framework $framework) {
		$clone = clone $framework;
		$clone->set_name($name);
		$clone->set_parent($framework->get_name());
		
		// make non-static and remove empty values
		// from existing framework so they can be replaced
		// after being loaded
		$clone->settings["static"] = null;
		$clone->settings = array_filter($clone->settings);
		
		// replace values with defaults from base that affect the
		// framework location/headers etc...
		$root = FrameworkLoader::loader()->retrieve_framework_xml_entry("root", BASE_FRAMEWORK, $clone);
		$umbrella = FrameworkLoader::loader()->retrieve_framework_xml_entry("umbrella", BASE_FRAMEWORK, $clone);
		$clone->set_root($root);
		$clone->set_umbrella_header($umbrella);

		$clone->load("__null__");
		
		return $clone;
	}
			
	// makes a framework for a private framework within the bundle
	// which is cloned from the calling frameworks parent
	public static function make_private ($name, Framework $framework) {
		
		// clone the parent of the base framework
		if ($parent = FrameworkLoader::loader()->find_framework($framework->get_parent())) {
			$clone = Framework::clone_existing($name, $parent);
			if ($path = $framework->get_private_framework_path($name)) {
				$clone->set_path($path);
				return $clone;
			}
		}
	}
	
	public function __toString() {
		return $this->name;
	}
	
	public function __free() {
		parent::__free();
		
		MemoryManager::free_array($this->headers);
	}
		
	// $xml = <framework> SimpleXMLElement
	// $header_path = optional path to framework headers
	public function __construct (SimpleXMLElement $xml = null)  {
		
		if ($xml) {
			$this->name = (string) $xml->name;
			$this->parent = (string) $xml->parent;
			$this->root = (string) $xml->root;
			$this->path = (string) $xml->path;
			$this->umbrella = (string) $xml->umbrella;
			
			// static settings
			$this->settings["root"] = (string) $xml->root;
			$this->settings["umbrella"] = (string) $xml->umbrella;
			
			// inheritable settings
			$this->settings["external_macros"] = (string) $xml->external_macros;
			$this->settings["inline_macros"] = (string) $xml->inline_macros;
			$this->settings["ignore_types"] = (string) $xml->ignore_types;
			$this->settings["declared_types"] = (string) $xml->declared_types;
			$this->settings["ignore_headers"] = (string) $xml->ignore_headers;
			$this->settings["implicit_pointers"] = (string) $xml->implicit_pointers;
			$this->settings["uses"] = (string) $xml->uses;
			$this->settings["imports"] = (string) $xml->imports;
			$this->settings["support_frameworks"] = (string) $xml->support_frameworks;
			$this->settings["replace_types"] = $xml->replace_types;
			$this->settings["pointer_types"] = $xml->pointer_types;
			$this->settings["remove_macros"] = $xml->remove_macros;
			$this->settings["availability_macros"] = $xml->availability_macros;
			$this->settings["replacement_patterns"] = $xml->replacement_patterns;
			$this->settings["define_replacements"] = $xml->define_replacements;
			$this->settings["static"] = (string)$xml->static;
			$this->settings["disabled"] = (string)$xml->disabled;
			$this->settings["include_imported_frameworks"] = (bool)$xml->include_imported_frameworks;

			// settings that don't allow line breaks but may require whitespace
			$this->settings["external_macros"] = str_replace("\n", "", trim($this->settings["external_macros"]));
			$this->settings["inline_macros"] = str_replace("\n", "", trim($this->settings["inline_macros"]));
			
			// settings that don't require any whitespace (arrays of words)
			$this->settings["declared_types"] = str_remove_white_space($this->settings["declared_types"]);
			$this->settings["ignore_types"] = str_remove_white_space($this->settings["ignore_types"]);
			$this->settings["ignore_headers"] = str_remove_white_space($this->settings["ignore_headers"]);
			$this->settings["implicit_pointers"] = str_remove_white_space($this->settings["implicit_pointers"]);
			$this->settings["uses"] = str_remove_white_space($this->settings["uses"]);
			$this->settings["imports"] = str_remove_white_space($this->settings["imports"]);
			$this->settings["support_frameworks"] = str_remove_white_space($this->settings["support_frameworks"]);
			
			//print($this->name.":\n");
			//print_r($this->settings);
			
		} else {
			$this->name = "dummy";
		}
		
	}

}
		

?>
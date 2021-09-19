<?php

require_once("errors.php");
require_once("framework.php");
require_once("header.php");
require_once("settings.php");
require_once("language_utilities.php");
require_once("framework_sorter.php");
require_once("memory_manager.php");

// deletes all parser made files from the directory
// this method is used for sharing batch skeleton frameworks
function clean_output_directory ($directory) {
	if (!file_exists($directory)) ErrorReporting::errors()->add_fatal("The directory \"$directory\" can not be found.");
	
	// main pascal units
	$units = directory_contents($directory, false, false, "/^\w+\.pas$/");

	// source folders
	$source_folders = directory_contents($directory, true, false);
	$support_includes = array();
	foreach ($source_folders as $source_folder) {
		if (!is_dir($source_folder)) continue;
		
		//$name = basename_without_extension($path);
		//$name = strtolower($name);
		//$source_folder = $directory."/".$name;
		
		// sanity check
		//if (!file_exists($source_folder)) continue;
		//if (!is_dir($source_folder)) continue;
		
		// add folder
		$source_folders[] = $source_folder;
		
		// source folder contents
		$files = directory_contents($source_folder, false, false);
		foreach ($files as $path) {
			// only delete support includes that are empty
			if (preg_match("/^(".TEMPLATE_FILE_UNDEFINED_TYPES."|".TEMPLATE_FILE_INLINE_FUNCTIONS.")+\.inc$/", basename($path))) {
				if (filesize($path) == 0) $support_includes[] = $path;
			} else {
				$support_includes[] = $path;
			}
		}
		
	}
	//print_r($source_folders);
	//print_r($support_includes);
	
	foreach ($support_includes as $path) unlink($path);
	foreach ($source_folders as $path) @rmdir($path);
	
	// build commands
	$build_commands = directory_contents($directory, false, false, "/^build-\w+\.command$/");
	foreach ($build_commands as $path) unlink($path);
	
	// defined class units
	$defined_class_units = directory_contents($directory, false, false, "/^".TEMPLATE_FILE_DEFINED_CLASSES."\w+\.pas$/");
	foreach ($defined_class_units as $path) unlink($path);
	
	// delete main units
	foreach ($units as $path) {
		if (file_exists($path)) unlink($path);
	}
	
	// delete root level support includes
	$path = $directory."/".TEMPLATE_FILE_UNDEFINED_TYPES.".inc";
	if (filesize($path) == 0) unlink($path);

	print(basename($directory)." has been successfully cleaned.\n");
}


/**
* Loads frameworks for parsing
*/
class FrameworkLoader {
	
	/**
	 * Private
	 */
	private static $instance;
	
	private $frameworks_defined = array();				// array of all defined frameworks (from load_frameworks_xml)
	private $frameworks_loaded = array();					// array of all loaded frameworks (from parameter in __construct)
	private $frameworks_unloadable = array();			// array of frameworks that are unloadable
	private $output;															// output directory for framewoek
	
	/**
	 * Patches
	 */
	
	public function apply_patch ($patch) {
		if (file_exists($patch)) {
			ErrorReporting::errors()->add_message("Applying patch '$patch' to ".basename($this->output));	
						
			$cwd = dirname($this->output);
			chdir($cwd);

			// copy the patch file into the output directory
			$path = $cwd."/".basename($patch);
			copy($patch, $path);

			// apply the patch
			$command = "patch -p0 < ".basename($patch)."";
			system($command);

			// delete the patch file
			if (file_exists($path)) unlink($path);
			
		} else {
			ErrorReporting::errors()->add_fatal("The patch ".basename($patch)." can not be found.");	
		}
	}
	
	/**
	 * Parsing
	 */
			
	// Process a stand-alone header by direct (outside of any framework)
	// this method can be used to bypass frameworks for simple testing but
	// expect results to have errors since the supporting framework types aren't
	// available
	//
	// $path = absolute path to the .h header file
	public function process_header ($path, $finalize_framework = true) {
		global $system_framework_directories;
		
		// break the path into 2 components
		if ($parts = explode(":", $path)) {
			if (count($parts) != 2) {
				ErrorReporting::errors()->add_fatal("The header path \"$path\" must have a framework prefix such as \"AppKit:/path/to/header.h\".");
			}

			$original_path = $parts[1];
			$framework = $this->find_framework($parts[0]);

			if (!$framework) {
				$name = readline("The framework \"$parts[0]\" is not defined. What framework would you like to define from?\n");
				if ($base = $this->find_framework($name)) {
					$framework = Framework::clone_existing($parts[0], $base);
					$this->add_defined_framework($framework);
					//$framework->print_info();
					$parts[0] = $framework->get_name();
				} else {
					ErrorReporting::errors()->add_fatal("The framework \"$name\" has not been defined.");
				}
			} else {
				// get the path component
				$path = expand_tilde_path($parts[1]);
				
				// explict path failed, use search paths
				if (!file_exists($path)) {
					foreach ($this->get_search_paths() as $search_path) {
						$framework_path = $search_path."/".$framework->get_name().".framework";
						if (file_exists($framework_path)) {
							$framework->set_path($framework_path);
							if (MESSAGE_FRAMEWORKS_RESOLVED) print("$framework-> was resolved to $framework_path\n");
							$path = $framework->get_headers_directory()."/".$parts[1];
							if (file_exists($path)) break;
						}
					}
				}
			
				// if the header still can't be found search in paths
				// note: in this case search paths will refer to a directory
				// with header files and not a framework directory
				if (!file_exists($path)) {
					$path = $this->find_header($parts[1]);
				}
							
			}
		}
		
		// report the header can't be found
		if (!file_exists($path)) {
			if (!$path) $path = $original_path;
			ErrorReporting::errors()->add_fatal("The header at \"$path\" can't be found for processing.");
		}
		
		// add the framework to the loaded array so it
		// can be freed in finalize_framework()
		if (!in_array($framework, $this->frameworks_loaded))
			$this->frameworks_loaded[] = &$framework;
				
		// make header
		$header = new Header($path, $framework);
		
		// process header
		ErrorReporting::errors()->add_message("Processing header ".$header->get_name()." (".$framework->get_name().")...");	
		$header->parse();
		
		// finalize
		if ($finalize_framework)
			$this->finalize_framework($framework);

		// return the framework used to process the header
		return $framework;
	}
	
	public function process_directory ($directory) {
		if ($parts = explode(FRAMEWORK_BASE_SEPARATOR, $directory)) {
			$files = directory_contents($parts[1]);
			$paths = array();
			foreach ($files as $file) {
				$paths[] = $file;
			}

			$framework = $this->find_framework($parts[0]);
			$framework->header_paths = $paths;

			// TODO: do a preprocess phase to find #defines and seach #includes
			// $framework->analyze_headers();

			if (!in_array($framework, $this->frameworks_loaded))
				$this->frameworks_loaded[] = &$framework;

			if (get_parser_option(PARSER_OPTION_BUILD_SKELETONS)) $framework->build_skeleton();
			
			//get_parser_option(PARSER_OPTION_GROUP)
			$this->build_group_unit($parts[0], $this->frameworks_loaded, null);

		} else {
			ErrorReporting::errors()->add_fatal("The directory \"$directory\" must have a framework prefix such as \"AppKit:/path/to/directory\".");
		}
	}

	// process an array of header paths
	public function process_headers ($paths) {
		$frameworks = [];
		foreach ($paths as $path) {
			$frameworks[] = &$this->process_header($path, false);
		}
		$frameworks = array_unique($frameworks);
		foreach ($frameworks as $framework) {
			$this->finalize_framework($framework);
		}
	}

	// process all loaded frameworks in batch
	public function process_batch ($ignore = null, $only = null) {
		$time_start = microtime_float();
		$processed_count = 0;
		$header_count = 0;
		$framework_count = count($this->frameworks_loaded);

		// there are no frameworks loaded to process
		if (!$this->frameworks_loaded) return;
				
		foreach ($this->frameworks_loaded as $framework) {
			
			// ignore framework names also
			if (@in_array($framework->get_name().".framework", $ignore)) continue;
			
			$processed_count += 1;
			ErrorReporting::errors()->add_message("• Parsing ".$framework->get_name()." ($processed_count/$framework_count)...");
			
			// iterate each line of the root include for headers
			foreach ($framework->header_paths as $path) {
				
				// the framework has ignored the header
				if ($framework->ignore_header(basename($path))) {
					ErrorReporting::errors()->add_note("The header is \"".basename($path)."\" is being ignored.");
					continue;
				}
				
				// parse the header if valid
				if (file_exists($path)) {
					$name = basename($path);
					
					// main header
					$header = null;
					if ($only) {
						if (@in_array($name, $only)) {
							$header = new Header($path, $framework);
							$header->parse();
							$header_count += 1;
						}
					} elseif (@!in_array($name, $ignore)) {
						$header = new Header($path, $framework);
						$header->parse();
						$header_count += 1;
					}
										
				} elseif ($name) {
					ErrorReporting::errors()->add_warning("The header \"$path\" could not be found.");
				}
			}
			
			// free framework
			$this->finalize_framework($framework);
		}
				
		// benchmarks 
		$end_time = microtime_float() - $time_start;
		if ($end_time > 60) {
			$end_time = $end_time / 60;
			ErrorReporting::errors()->add_message("Processed $header_count header(s) from $framework_count framework(s) in ".$end_time." minute(s).");
		} else {
			ErrorReporting::errors()->add_message("Processed $header_count header(s) from $framework_count framework(s) in ".$end_time." second(s).");
		}
		
		ErrorReporting::errors()->add_message("Peak memory usage: ".bytes_human_readable(memory_get_peak_usage(true)).".");
	}

	/**
	 * Printing
	 */
			
	// print global stuff (a unit containing all classes as
	// anonymous external classes, so they can be used before they are
	// declared)
	private function print_class_definitions(Framework $framework) {
		
		// build the list from the framework
		SymbolTable::table()->build_formal_declarations($framework, $defined, $declared, $all);
		
		// these classes have been defined in the framework
		$output = new Output($this->output."/".TEMPLATE_FILE_DEFINED_CLASSES.$framework->get_name().".pas", false);		
		$output->writeln(0, "{\$mode delphi}");
		$output->writeln(0, "{\$modeswitch objectivec1}");
		$output->writeln(0, "{\$modeswitch cvar}");
		$output->writeln(0, "{\$packrecords c}");
		$output->writeln(0, "");

		$output->writeln(0, "unit ".TEMPLATE_FILE_DEFINED_CLASSES.$framework->get_name().";");
		$output->writeln(0, "interface");
		
		// defined
		if ((count($defined["classes"]) > 0) || (count($defined["protocols"]) > 0)) {
			$output->writeln(0, "");
			$output->writeln(0, "type");
	    foreach ($defined["classes"] as $class) $output->writeln(1, $class." = objcclass external;\n");
	    foreach ($defined["protocols"] as $protocol) $output->writeln(1, $protocol." = objcprotocol external name '".trim_suffix($protocol, PROTOCOL_SUFFIX)."';\n");
		}

		// declared
		if ((count($declared["classes"]) > 0) || (count($declared["protocols"]) > 0)) {
			$output->writeln(0, "");
			if (count($declared) > 0) $output->writeln(0, "type");
	    foreach ($declared["classes"] as $class) $output->writeln(1, $class." = objcclass external;\n");
	    foreach ($declared["protocols"] as $protocol) $output->writeln(1, $protocol." = objcprotocol external name '".trim_suffix($protocol, PROTOCOL_SUFFIX)."';\n");
		}

		$output->writeln(0, "");
		$output->writeln(0, "implementation");
		$output->writeln(0, "end.");
		$output->close();
  }
	
	private function build_all_units_build_command ($frameworks) {
		$name = "build-all.command";
		$path = $this->output."/".$name;

		// build command for PPC/Mac OS frameworks
		$contents = <<<TEMPLATE
#!/bin/bash
DIR=\$(dirname \$0)

TEMPLATE;
		
		// print each framework
		foreach ($frameworks as $framework) {
			$contents .= "sh \"\$DIR/build-".strtolower($framework->get_name()).".command\"\n";
		}
		
		// write the template to file
		file_put_contents($path, $contents);
		
		// chmod the file so it's executable
		if (file_exists($path)) exec("chmod +x \"$path\"");
	}
	
	
	// prints a .command build script for the framework (for debugging)
	private function print_build_command ($unit_name) {
		global $template_build_command_x86;
		global $template_build_command_i386;
		global $template_build_command_arm;
		global $template_build_command_ppc;
		
		$command = get_parser_option(PARSER_OPTION_BUILD_COMMANDS);
		$parts = explode("/", $command);
		
		if ($parts[0] == "x86") $template = $template_build_command_x86;
		if ($parts[0] == "i386") $template = $template_build_command_i386;
		if ($parts[0] == "arm") $template = $template_build_command_arm;
		if ($parts[0] == "ppc") $template = $template_build_command_ppc;
		
		$version = $parts[1];
		
		$template = str_replace("[VERSION]", $version, $template);
		$template = str_replace("[UNIT]", $unit_name, $template);
		
		$name = "build-".strtolower($unit_name).".command";
		$path = $this->output."/".$name;
		
		// write the template to file
		file_put_contents($path, $template);
		
		// chmod the file so it's executable
		if (file_exists($path)) exec("chmod +x \"$path\"");
		
		return $path;
	}
	
	/**
	 * Debugging
	 */
	
	// prints all loaded frameworks and the frameworks they import
	private function print_framework_imports () {
		foreach ($this->frameworks_loaded as $framework) {
			print($framework->get_name()."\n");
			foreach ($framework->get_imported_frameworks() as $import) {
				print("  ".$import->get_name()."\n");
			}
		}
	}
	
	// prints table of all frameworks dependencies (for memory debugging)
	private function print_framework_dependencies () {
		print("Framework dependencies:\n");
		foreach ($this->frameworks_loaded as $framework) {
			print("  + ".$framework->get_name()." = ".$framework->dependencies."\n");
		}
	}
	
	/**
	 * Public Utilities
	 */
	
	// returns a framework that was defined for $name
	public function find_framework ($name) {
				
		// remove framework option characters
		$name = trim($name, FRAMEWORK_OPTION_DISABLE_PRINTING.FRAMEWORK_OPTION_DISABLE_FINALIZING);
		$name = strtolower($name);
		
		foreach ($this->frameworks_defined as $framework) {
			if (strcasecmp($framework->get_name(), $name) == 0) return $framework;
		}
	}
	
	// returns true if the framework has been defined in frameworks.xml
	public function is_framework_defined ($name) {
		foreach ($this->frameworks_defined as $framework) {
			if ($name == $framework->get_name()) return true;
		}
	}
	
	// returns true if the framework has been loaded
	public function is_framework_loaded ($name) {
		foreach ($this->frameworks_loaded as $framework) {
			if ($name == $framework->get_name()) return true;
		}
	}
	
	public function is_framework_unloadable ($name) {
		foreach ($this->frameworks_unloadable as $framework) {
			if ($name == $framework->get_name()) return true;
		}
	}
		
	// returns an xml entry in any defined framework
	// $inherit_to (optional) = behaves as if this framework has inherited from the named
	// 													framework and applies macros accordingly
	public function retrieve_framework_xml_entry ($key, $framework_name, Framework $inherit_to = null) {
		if ($framework = $this->find_framework($framework_name)) {
			$entry = $framework->settings[$key];
			if ($inherit_to) {
				$entry = $inherit_to->apply_macros($entry);
			}
			return $entry;
		}
	}
	
	// returns an array of group frameworks without option prefixes
	// $filter = array of options to filter (according to $include)
	// $include = if true the filter will return only the frameworks that match the options
	//						as oppose to false which will exclude the options
	public function get_group_frameworks ($filter = null, $include = false) {
		$frameworks = get_parser_option(PARSER_OPTION_GROUP_FRAMEWORKS);
		$group_frameworks = array();
		
		if (!$frameworks) return array();
		
		if (!is_array($filter) && ($filter)) $filter = array($filter);
		
		foreach ($frameworks as $framework) {
			
			// apply filter for options
			if ($filter) {
				if ($include) {
					if (!in_array($framework[0], $filter)) continue;
				} else {
					if (in_array($framework[0], $filter)) continue;
				}
			}
			
			$group_frameworks[] = trim($framework, GROUP_FRAMEWORK_OPTION_USES.GROUP_FRAMEWORK_OPTION_INDEPENDENT);
		}
		
		return $group_frameworks;
	}
	
	// returns true if the group framework option is enabled for the specified framework
	public function is_group_framework_option_enabled ($framework, $options) {
		$frameworks = get_parser_option(PARSER_OPTION_GROUP_FRAMEWORKS);
		
		if (!$frameworks) return false;
		
		if (!is_array($options)) $options = array($options);
		
		foreach ($frameworks as $_framework) {
			$name = trim($_framework, GROUP_FRAMEWORK_OPTION_USES.GROUP_FRAMEWORK_OPTION_INDEPENDENT);
			if ((in_array($_framework[0], $options)) && ($name == $framework)) return true;
		}
	}
	
	/**
	 * Private Utilities
	 */
	
	// returns the directory used to find frameworks depending on parser options
	private function get_input_directory () {
		
		// use SDK search paths
		if (is_parser_option_enabled(PARSER_OPTION_SDK)) {
			return framework_directory_for_sdk(get_parser_option(PARSER_OPTION_SDK), get_parser_option(PARSER_OPTION_XCODE));
		}
		
		// use path specified in batch
		if (is_parser_option_enabled(PARSER_OPTION_BATCH)) {
			$parts = explode(":", get_parser_option_path(PARSER_OPTION_BATCH));
			if (count($parts) == 2) {
				$path = $parts[1];
				$path = expand_tilde_path($path);
				$path = expand_root_path($path);
				
				// expand special xcode macro
				if (is_parser_option_enabled(PARSER_OPTION_XCODE)) $path = str_replace(FRAMEWORK_XML_MACRO_XCODE, get_parser_option_path(PARSER_OPTION_XCODE), $path);
				
				return $path;
			}
		}
		
		// use the first search path (other search paths will searched later in $this->resolve_framework)
		if (is_parser_option_enabled(PARSER_OPTION_SEARCH_PATHS)) {
			foreach (get_parser_option(PARSER_OPTION_SEARCH_PATHS) as $key => $path) {
				$path = expand_tilde_path($path);
				$path = expand_root_path($path);
				return $path;
			}
		}
		
		return null;
	}
	
	// returns an array of paths to search for frameworks
	private function get_search_paths () {
		global $system_framework_directories;
		$paths = array();
		
		// add search paths from command line
		if (is_parser_option_enabled(PARSER_OPTION_SEARCH_PATHS)) {
			foreach (get_parser_option(PARSER_OPTION_SEARCH_PATHS) as $key => $path) {
				$path = expand_tilde_path($path);
				$path = expand_root_path($path);
				
				$paths[] = $path;
			}
		}
		
		// add default input directory
		if ($this->get_input_directory()) {
			$paths[] = $this->get_input_directory();
		}
		
		// add system framework paths as last resort
		$paths = array_merge($paths, $system_framework_directories);
		
		return array_unique($paths);
	}
	
	private function add_defined_framework (Framework $framework) {
		if (!$this->is_framework_defined($framework->get_name())) $this->frameworks_defined[] = &$framework;
	}
	
	private function add_unloadable_framework (Framework $framework) {
		if (!$this->is_framework_unloadable($framework->get_name())) $this->frameworks_unloadable[] = &$framework;
	}
	
	// returns true if the parser specified to ignore the framework
	// via command line options
	private function ignore_framework ($name) {
		$files = get_parser_option(PARSER_OPTION_IGNORE);
		if ($files) {
			foreach ($files as $file) {
				if (strtolower("$name.framework") == strtolower($file)) return true;
			}
		}
	}
	
	/**
	 * Private
	 */
	
	private function copy_skeleton ($skeleton) {
		if (!file_exists($this->output)) {
			ErrorReporting::errors()->add_note("Copied skeleton to $this->output\n");
			$command = "/bin/cp -R \"$skeleton\" \"$this->output\"";
			exec($command);
		}
	}
		
	private function build_group_unit ($group, $frameworks, $filter = null) {
		global $template_group_unit;
		global $template_common_types;
		global $template_common_macros;
		global $template_availability_macros;
		
		// load the template string
		if (is_parser_option_enabled(PARSER_OPTION_TEMPLATE)) {
			$template = file_get_contents(get_parser_option_path(PARSER_OPTION_TEMPLATE));
		} else {
			$template = file_get_contents(expand_path($template_group_unit));
		}
		
		// group template
		$template = str_replace(TEMPLATE_KEY_GROUP, strtoupper(get_parser_option(PARSER_OPTION_GROUP)), $template);
						
		// replace template macros
		$template = str_replace(TEMPLATE_KEY_NAME, $group, $template);
		$template = str_replace(TEMPLATE_KEY_NAME_UPPER_CASE, strtoupper($group), $template);

		// common types/macros
		$template = str_replace(TEMPLATE_KEY_AVAILABILITY_MACROS, $template_availability_macros, $template);
		$template = str_replace(TEMPLATE_KEY_COMMON_TYPES, $template_common_types, $template);
		$template = str_replace(TEMPLATE_KEY_COMMON_MACROS, $template_common_macros, $template);
		
		// $linkframeworks
		$string = "";
		foreach ($frameworks as $framework) {
			if (!$framework->has_valid_output()) continue;
			if ($filter && !in_array($framework->get_name(), $filter)) continue;
			$string .= "{\$linkframework ".$framework->get_name()."}\n";
		}
		$template = str_replace(TEMPLATE_KEY_LINK_FRAMEWORK, trim($string), $template);
		
		// uses
		$uses = array();
		foreach ($frameworks as $framework) {
			if (!$framework->has_valid_output()) continue;
			if ($filter && !in_array($framework->get_name(), $filter)) continue;
			$uses[] = TEMPLATE_FILE_DEFINED_CLASSES.$framework->get_name();
		}
		
		// add group framework uses
		foreach ($this->get_group_frameworks(GROUP_FRAMEWORK_OPTION_USES, true) as $framework) {
			$uses[] = $framework;
		}
		
		$template = str_replace(TEMPLATE_KEY_USES, implode(", ", $uses), $template);
		
		// define a macro we can use to determine if the
		// framework has been loaded in other places
		$macros = array();
		foreach ($frameworks as $framework) {
			if (!$framework->has_valid_output()) continue;
			if ($filter && !in_array($framework->get_name(), $filter)) continue;
			$macros[] = "{\$define FRAMEWORK_LOADED_".strtoupper($framework->get_name())."}";
		}
		$template = str_replace(TEMPLATE_KEY_LOADED_MACROS, implode("\n", $macros), $template);
	
		// includes
		$string = "";
		foreach ($frameworks as $framework) {
			if (!$framework->has_valid_output()) continue;
			if ($filter && !in_array($framework->get_name(), $filter)) continue;
			$include_directory = trim($framework->get_root_directory(), "/");
			$string .= "{\$include ".$include_directory."/".$framework->get_root_name().".inc}\n";
		}
		$template = str_replace(TEMPLATE_KEY_INCLUDE, trim($string), $template);
		
		// undefined types
		$string = "";
		foreach ($frameworks as $framework) {
			if (!$framework->has_valid_output()) continue;
			if ($filter && !in_array($framework->get_name(), $filter)) continue;
			$include_directory = trim($framework->get_root_directory(), "/");
			$string .= "{\$include ".$include_directory."/".TEMPLATE_FILE_UNDEFINED_TYPES.".inc}\n";
		}
		$template = str_replace(TEMPLATE_KEY_UNDEFINED_TYPES, trim($string), $template);

		// inline functions
		$string = "";
		foreach ($frameworks as $framework) {
			if (!$framework->has_valid_output()) continue;
			if ($filter && !in_array($framework->get_name(), $filter)) continue;
			$include_directory = trim($framework->get_root_directory(), "/");
			$string .= "{\$include ".$include_directory."/".TEMPLATE_FILE_INLINE_FUNCTIONS.".inc}\n";
		}
		$template = str_replace(TEMPLATE_KEY_INLINE_FUNCTIONS, trim($string), $template);
		
		// write file
		$path = $this->output."/$group.pas";
		if (is_parser_option_enabled(PARSER_OPTION_SAFE_WRITE)) {
			if (!file_exists($path)) {
				file_put_contents($path, $template);
			} else {
				ErrorReporting::errors()->add_note(basename($path)." already exists.");
			}
		} else {
			file_put_contents($path, $template);
		}
		
		// make build commands
		if (is_parser_option_enabled(PARSER_OPTION_BUILD_COMMANDS)) {
			$this->print_build_command($group);
			if (is_parser_option_enabled(PARSER_OPTION_ALL_UNITS)) $this->build_all_units_build_command($frameworks);
		}
		
	}
	
	private function conclude_framework (Framework $framework) {
				
		if (MESSAGE_PEAK_MEMORY_LIMITS) {
			ErrorReporting::errors()->add_message("Finalizing \"$framework\" (".bytes_human_readable(memory_get_peak_usage(true)).")...");
		} else {
			ErrorReporting::errors()->add_message("Finalizing \"$framework\"...");
		}		
				
		// print global class definitions if enabled
		if (($framework->can_print()) &&(is_parser_option_enabled(PARSER_OPTION_CLASS_DEFINITIONS)) && (!is_parser_option_enabled(PARSER_OPTION_SHOW)) && (!is_parser_option_enabled(PARSER_OPTION_DRY_RUN))) {
			ErrorReporting::errors()->add_message("  Building formal declarations...");
			$this->print_class_definitions($framework);
		}
		
		SymbolTable::table()->finalize_symbols($framework);
				
		ErrorReporting::errors()->add_message("  Resolving defined symbol conflicts...");
		SymbolTable::table()->resolve_defined_symbol_conflicts($framework);
		
		ErrorReporting::errors()->add_message("  Resolving header dependancies...");
		foreach ($framework->get_headers() as $header) {
			SymbolTable::table()->resolve_dependancies($header);
		}
		
		ErrorReporting::errors()->add_message("  Adopting class protocols...");
		SymbolTable::table()->adopt_class_protocols($framework);
		
		// print the header output
		// specify null absolute path parameter so the header will print to the default location
		if ((!is_parser_option_enabled(PARSER_OPTION_DRY_RUN)) && ($framework->can_print())) {
			foreach ($framework->get_headers() as $header) {
				if (is_parser_option_enabled(PARSER_OPTION_UNIT)) {
					$header->print_unit($this->output, is_parser_option_enabled(PARSER_OPTION_SHOW));
				} else {						
					$header->print_output($this->output, false, is_parser_option_enabled(PARSER_OPTION_SHOW));
				}
			}
		}
				
		// print opaque types if enabled
		if ((is_parser_option_enabled(PARSER_OPTION_OPAQUE_TYPES)) && (!is_parser_option_enabled(PARSER_OPTION_DRY_RUN)) && (!is_parser_option_enabled(PARSER_OPTION_SHOW))) {
			ErrorReporting::errors()->add_warning("  Opaque types are disabled for development.");
			//SymbolTable::table()->print_opaque_types($this->output);
		}
		
		$framework->finalized = true;
	}
	
	private function finalize_framework (Framework $free) {
		
		// decrement our own dependcy
		$free->dependencies -= 1;
		
		// decrement dependencies on all frameworks that import
		// the framework being freed
		foreach ($free->get_imported_frameworks() as $import) {
			$import->dependencies -= 1;
		}
		
		//$this->print_framework_dependencies();
		
		// conclude frameworks that have no dependencies left
		foreach ($this->frameworks_loaded as $framework) {
			if (($framework->dependencies <= 0) && (!$framework->finalized)) {
				$this->conclude_framework($framework);
			}
		}
		
		// unload finalized frameworks
		foreach ($this->frameworks_loaded as $framework) {
			if ($framework->finalized) {
				// remove from the symbol table
				ErrorReporting::errors()->add_message("  Removing $framework from symbol table.");
				SymbolTable::table()->remove_framework($framework);

				// free framework in framework loader
				$this->unload_framework($framework);
			}
		}
		
	}
	
	private function unload_framework (Framework $framework) {
		foreach ($this->frameworks_loaded as $key => $value) {
			if ($value->get_name() == $framework->get_name()) MemoryManager::free_array_value($this->frameworks_loaded, $key);
		}
		
		foreach ($this->frameworks_defined as $key => $value) {
			if ($value->get_name() == $framework->get_name()) MemoryManager::free_array_value($this->frameworks_defined, $key);
		}
	}		
	
	// sorts loaded frameworks by dependency so that frameworks don't
	// parse before another framework that imports it
	// this is needed mainly for implicit pointers because they are not
	// resolved when types are finalized (this could be changed in the future...)
	private function sort_loaded_frameworks () {
		ErrorReporting::errors()->add_message("Sorting ".count($this->frameworks_loaded)." loaded frameworks...");
		
		// build dependencies array for each loaded framework
		// this array tells us how many dependencies on imported
		// frameworks exist so we can free frameworks from the symbol
		// table when frameworks are now longer needed for support
		$dependencies = array();
		foreach ($this->frameworks_loaded as $a) {
			$dependencies[$a->get_name()] = 1;
			foreach ($this->frameworks_loaded as $b) {
				foreach ($b->get_imported_frameworks() as $c) {
					if ($a->get_name() == $c->get_name()) {
						$dependencies[$a->get_name()] += 1;
					}
				}
			}
		}
		arsort($dependencies);
		
		// sort frameworks for order
		$sorter = new FrameworkSorter($this->frameworks_loaded);
		$order = $sorter->sort();
		
		// rebuild the loaded frameworks in the sorted order
		$frameworks = array();
		foreach ($order as $name) {
			$framework = $this->find_framework($name);
			$framework->dependencies = $dependencies[$name];
			$frameworks[] = $framework;
		}
		
		$this->frameworks_loaded = $frameworks;
	}
				
	// loads all frameworks in the directory
	private function load_frameworks_in_directory ($directory, Framework $base, $only = null, $ignore = null, $private_frameworks = true) {
		$frameworks = array();
		
		if ($handle = @opendir($directory)) {
			while (($file = readdir($handle)) !== false) {
				if (($file != '.') && ($file != '..') && ($file[0] != '.')) {
					
					$path = "$directory/$file";
					$name = basename($file, ".framework");
					
					// recurse into private frameworks
					if (preg_match("/\.framework$/i", $file)) {
						if (file_exists($path."/Frameworks") && ($private_frameworks)) {
							$frameworks = array_merge($frameworks, $this->load_frameworks_in_directory($path."/Frameworks", $base, $only, $ignore, true));
						}
					} else {
						// ignore non-framework files
						continue;
					}
					
					// filter based on only/ignore frameworks
					if ($only) {
						if (!in_array($file, $only)) continue;								
					} elseif ($ignore) {
						if (in_array($file, $ignore)) continue;								
					}
					
					if ($framework = $this->find_framework($name)) {
						
						// the framework is disabled for batch parsing
						if ($framework->is_disabled()) continue;
						
						// override headers directory with batch directory
						$framework->set_directory($directory);
						$framework->auto_loaded = true;
						
						$frameworks[] = $name;
					} else {
						
						// clone the base framework and add to definitions
						$framework = Framework::clone_existing($name, $base);
						
						// override headers directory with batch directory
						$framework->set_directory($directory);
						$framework->auto_loaded = true;
						
						// define the cloned framework so it can be loaded
						$this->add_defined_framework($framework);
						
						$frameworks[] = $name;
					}
					
				}
			}
			closedir($handle);
		}
		
		return $frameworks;
	}
		
	// finds a header in the search paths
	private function find_header ($name) {
		foreach ($this->get_search_paths() as $path) {
			$contents = directory_contents($path);
			foreach ($contents as $file_path) {
				if ($name == basename($file_path)) return $file_path;
			}
		}
	}
		
	// resolves a frameworks path by searching in available paths
	private function resolve_framework (Framework $framework, $directory = null) {

		if (!$directory) {
			// resolve in each search path
			foreach ($this->get_search_paths() as $path) $this->resolve_framework($framework, $path);
		} else {
			// iterate the directory
			if ($handle = @opendir($directory)) {
				while (($file = readdir($handle))) {
					
					if ($file[0] == ".") continue;
					if ($framework->is_found()) return;
					
					$path = "$directory/$file";
					
					if (is_dir($path)) {
						if ($file == $framework->get_name().".framework") {
							if (MESSAGE_FRAMEWORKS_RESOLVED) print("$framework-> was resolved to $path\n");
							$framework->set_path($path);
							return;
						}
						
						// recurse into directory
						$this->resolve_framework($framework, $path);
					}
					
				}
				closedir($handle);
			}
		}
	}
	
	private function load_batch_frameworks ($command, $only = null, $ignore = null) {
		$frameworks = array();
		$parts = explode(":", $command);
		
		if (count($parts) == 2) {
			$batch_directory = $parts[1];
			$batch_framework = $parts[0];
			
			// expand special xcode macro
			if (is_parser_option_enabled(PARSER_OPTION_XCODE)) $batch_directory = str_replace(FRAMEWORK_XML_MACRO_XCODE, get_parser_option_path(PARSER_OPTION_XCODE), $batch_directory);
			
			// get base framework
			$base = $this->find_framework($batch_framework);
			if (!$base) ErrorReporting::errors()->add_fatal("The batch base framework \"$batch_framework\" has not been defined.");
			
			// batch directory is missing
			if (!file_exists($batch_directory)) ErrorReporting::errors()->add_fatal("The batch directory at \"".$batch_directory."\" does not exist.");
			
			// load frameworks in directory
			$frameworks = $this->load_frameworks_in_directory($batch_directory, $base, $only, $ignore, true);
			
		} else {
			// missing base framework
			ErrorReporting::errors()->add_fatal("-batch must specify a base framework using a suffix like appkit: to be valid.");
		}
		
		ErrorReporting::errors()->add_note(count($frameworks)." frameworks have been defined from batch directory.");

		//print_r($frameworks);
		return $frameworks;
	}
	
	private function autoload_imported_frameworks ($base_framework) {
		
		// load private frameworks
		foreach ($base_framework->get_private_frameworks() as $private_framework) {		
			if ($this->is_framework_loaded($private_framework)) continue;
			if ($this->ignore_framework($private_framework)) continue;
			
			// if the private framework is already defined use that 
			// definition and set the framework path
			if ($framework = FrameworkLoader::loader()->find_framework($private_framework)) {
				if ($path = $base_framework->get_private_framework_path($private_framework)) {
					$framework->set_path($path);
					$this->load_framework($framework, $base_framework);
				} else {
					$framework->free();
				}
				continue;
			}
			
			// make a private clone from the base framework
			if ($framework = Framework::make_private($private_framework, $base_framework)) {
				$this->load_framework($framework, $base_framework);
			}
		}
		
		// resolve imported frameworks that have not been defined
		if ($undefined_frameworks = $base_framework->get_undefined_imported_frameworks()) {
			foreach ($undefined_frameworks as $name) {
				if ($this->is_framework_defined($name)) continue;
				if ($this->ignore_framework($name)) continue;
				
				$framework = Framework::clone_existing($name, $base_framework);
				$framework->set_path(null);
				
				$this->resolve_framework($framework);
				
				if ($framework->is_found()) {	
					//$framework->print_info();
					$framework->auto_loaded = true;	
					$this->add_defined_framework($framework);
				} else {
					$framework->free();
				}
			}
		}
		
		// load imported frameworks that have been defined
		foreach ($base_framework->get_imported_frameworks() as $imported_framework) {
			if ($this->is_framework_loaded($imported_framework->get_name())) continue;
			
			$this->load_framework($imported_framework, $base_framework);
		}
	}
			
	private function load_framework (Framework $framework, $loaded_from_framework = null) {

		// the framework is unloadable
		if ($this->is_framework_unloadable($framework)) return;
		
		// the framework is being ignored
		if ($this->ignore_framework($framework->get_name())) return;
				
		// static frameworks can't be loaded
		if ($framework->is_static()) {
			ErrorReporting::errors()->add_fatal("The framework \"".$framework->get_name()."\" is static and can not be loaded.");
		}
				
		// if the framework is not found then set the directory to the 
		// input directory 
		if (!$framework->is_found()) {
			$framework->set_directory($this->get_input_directory());
		}
		
		// attempt to resolve the frameworks path from the search directory
		// if the framework is still not found
		if (!$framework->is_found()) {
			
			$this->resolve_framework($framework);
						
			// the directory can not be found anywhere
			if (!$framework->is_found()) {
				ErrorReporting::errors()->add_warning("The framework \"$framework\" can not be found at \"".$framework->get_path()."\".");
				$this->add_unloadable_framework($framework);
				return false;
			}
		}
				
		// the framework has no valid headers, don't load
		if (!$framework->has_valid_headers()) {
			ErrorReporting::errors()->add_note("The framework \"$framework\" can not be loaded because it has no headers.");
			$this->add_unloadable_framework($framework);
			return false;
		}
						
		if ($loaded_from_framework) {
			$framework->auto_loaded = true;
			array_unshift($this->frameworks_loaded, $framework);
			if (MESSAGE_FRAMEWORKS_LOADED) ErrorReporting::errors()->add_note("The framework \"".$framework->get_name()."\" has been auto loaded from ".$loaded_from_framework->get_name().".");
		} else {
			$this->frameworks_loaded[] = &$framework;
			if (MESSAGE_FRAMEWORKS_LOADED) ErrorReporting::errors()->add_note("The framework \"".$framework->get_name()."\" has been loaded.");
		}
		
		// analyze framework headers
		$framework->analyze_headers();
		
		// load imported frameworks
		if (get_parser_option(PARSER_OPTION_AUTOLOAD_IMPORTED_FRAMEWORKS)) {
			$this->autoload_imported_frameworks($framework);
		}
		
		//$framework->print_info();
		//print_r($framework);
		//die;
	}
		
	/**
	 * Framework XML definitions
	 */	
	
	// loads parser settings from the XML file
	private function load_frameworks_xml () {
		
		// load all xml paths
		$paths = array();
		$paths = array_merge($paths, get_framework_definitions());

		// add additional XML files after the default definitions
		if (is_parser_option_enabled(PARSER_OPTION_FRAMEWORKS_XML)) {
			$framework_xml_paths = preg_split("/\s*,\s*/", get_parser_option(PARSER_OPTION_FRAMEWORKS_XML));
			foreach ($framework_xml_paths as $path) {
				$paths[] = expand_path($path);
			}
		}
		
		// add iOS/MacOS frameworks definitions for helper switch
		if (is_parser_option_enabled(PARSER_OPTION_MACOS)) $paths[] = "@/defs/macos.xml";
		if (is_parser_option_enabled(PARSER_OPTION_IOS)) $paths[] = "@/defs/ios.xml";
				
		// load each xml file
		foreach ($paths as $path) {
			
			// replace root directory
			$path = expand_root_path($path);
			$path = expand_tilde_path($path);
			
			// missing file
			if (!file_exists($path)) {
				ErrorReporting::errors()->add_fatal("The framework definition \"$path\" could not be found.");
			}
			
			if ($xml = new SimpleXMLElement(file_get_contents($path))) {
				foreach ($xml as $node) {	

					// allocate new framework with xml settings
					$framework = new Framework($node);
					$framework->load($path);
					
					if ($this->is_framework_defined($framework)) ErrorReporting::errors()->add_fatal("The framework \"".$framework->get_name()."\" was defined twice in $path.");
					
					if (is_parser_option_enabled(PARSER_OPTION_VERBOSE)) ErrorReporting::errors()->add_note("The framework \"".$framework->get_name()."\" was defined.");
					
					// append to array of frameworks
					$this->add_defined_framework($framework);
				}
			} else {
				ErrorReporting::errors()->add_fatal("The framework definition \"$path\" could not be loaded.");
			}
		}
		
		// report which frameworks have been defined
		//foreach ($this->frameworks_defined as $framework) {
		//	ErrorReporting::errors()->add_note("The framework \"".$framework->get_name()."\" has been defined.");
		//}
	}
		
	private function verify_command_line ()  {
		// verify template paths
		global $template_root_unit;
		global $template_group_unit;
		
		if (!file_exists(expand_path($template_root_unit))) ErrorReporting::errors()->add_fatal("The template file at \"$template_root_unit\" can't be found.");
		if (!file_exists(expand_path($template_group_unit))) ErrorReporting::errors()->add_fatal("The template file at \"$template_group_unit\" can't be found.");
				
		// verify search paths
		if (is_parser_option_enabled(PARSER_OPTION_SEARCH_PATHS)) {
			foreach (get_parser_option_path(PARSER_OPTION_SEARCH_PATHS) as $path) {
				if (!file_exists($path)) ErrorReporting::errors()->add_fatal("The search path \"$path\" does not exist.");
			}
		}
		
		// verify xcode path
		if (is_parser_option_enabled(PARSER_OPTION_XCODE)) {
			if (!file_exists(get_parser_option_path(PARSER_OPTION_XCODE))) ErrorReporting::errors()->add_fatal("Xcode could not be found at \"".get_parser_option_path(PARSER_OPTION_XCODE)."\".");
		}
		
		// verify sdk path
		if (is_parser_option_enabled(PARSER_OPTION_SDK)) {
			if (!framework_directory_for_sdk(get_parser_option_path(PARSER_OPTION_SDK), get_parser_option(PARSER_OPTION_XCODE))) ErrorReporting::errors()->add_fatal("The SDK \"".get_parser_option_path(PARSER_OPTION_SDK)."\" could not be found.");
		}
		
		// verify template path
		if (is_parser_option_enabled(PARSER_OPTION_TEMPLATE)) {
			if (!file_exists(get_parser_option_path(PARSER_OPTION_TEMPLATE))) ErrorReporting::errors()->add_fatal("The template \"".get_parser_option_path(PARSER_OPTION_TEMPLATE)."\" could not be found.");
		}
		
		// verify skeleton path
		if (is_parser_option_enabled(PARSER_OPTION_SKELETON)) {
			if (!file_exists(get_parser_option_path(PARSER_OPTION_SKELETON))) ErrorReporting::errors()->add_fatal("The skeleton \"".get_parser_option_path(PARSER_OPTION_SKELETON)."\" could not be found.");
		}
		
		// verify patch path
		if (is_parser_option_enabled(PARSER_OPTION_PATCH)) {
			if (!file_exists(get_parser_option_path(PARSER_OPTION_PATCH))) ErrorReporting::errors()->add_fatal("The patch \"".get_parser_option_path(PARSER_OPTION_PATCH)."\" could not be found.");
		}
		
		// verify build command
		if (is_parser_option_enabled(PARSER_OPTION_BUILD_COMMANDS)) {
			if (!preg_match("/^(x86|i386|ppc|arm)+\/(\d+|\.)*$/", get_parser_option_path(PARSER_OPTION_BUILD_COMMANDS))) ErrorReporting::errors()->add_fatal("The build command option is invalid (use a format such as i386/2.6.0). Architectures: x84, i386, ppc, arm");
		}
		
		// verify os helpers
		if (is_parser_option_enabled(PARSER_OPTION_MACOS) && is_parser_option_enabled(PARSER_OPTION_IOS)) {
			ErrorReporting::errors()->add_fatal("Please choose only one option depending on the SDK: -macos or -ios.");
		}
		
		// verify all units template (if specified)
		if (is_parser_option_enabled(PARSER_OPTION_ALL_UNITS) && (get_parser_option_path(PARSER_OPTION_ALL_UNITS) != 1)) {
			if (!file_exists(get_parser_option_path(PARSER_OPTION_ALL_UNITS))) ErrorReporting::errors()->add_fatal("The template specified for -".PARSER_OPTION_ALL_UNITS." \"".get_parser_option_path(PARSER_OPTION_ALL_UNITS)."\" could not be found.");
		}
		
		// verify search paths unless they are implicit in the command
		if ((count($this->get_search_paths()) == 0) && (!is_parser_option_enabled(PARSER_OPTION_HEADER)) && (!is_parser_option_enabled(PARSER_OPTION_DIRECTORY))) {
			ErrorReporting::errors()->add_fatal("No framework search paths can be found.");
		}
		
	}
	
	// loads all frameworks that were specified on the command line via -frameworks and -directory
	private function load_command_line_frameworks ($frameworks)  {

		if (!$frameworks) $frameworks = array();
		
		// add requested frameworks from command line
		foreach ($frameworks as $name) {
			
			// load framework from base framework
			$parts = explode(FRAMEWORK_BASE_SEPARATOR, $name);
			if (count($parts) == 2) {
				$name = $parts[1];
				if ($base = $this->find_framework($parts[0])) {
					$framework = Framework::clone_existing($name, $base);
					$this->add_defined_framework($framework);
				} else {
					ErrorReporting::errors()->add_fatal("The base framework \"".$parts[0]."\" can not be found.");
				}
			}
			
			// the framework is not defined so define
			// from the default framework if it was specified
			if ((!$this->is_framework_defined($name)) && (is_parser_option_enabled(PARSER_OPTION_DEFAULT_FRAMEWORK))) {
				if ($default = $this->find_framework(get_parser_option(PARSER_OPTION_DEFAULT_FRAMEWORK))) {
					$framework = Framework::clone_existing($name, $default);
					$this->add_defined_framework($framework);
				} else {
					ErrorReporting::errors()->add_fatal("The default framework \"".get_parser_option(PARSER_OPTION_DEFAULT_FRAMEWORK)."\" can not be found.");
				}
			}
			
			// load defined frameworks
			if ($framework = $this->find_framework($name)) {
									
				// disable printing
				if (strstr($name, FRAMEWORK_OPTION_DISABLE_PRINTING)) $framework->set_print(false);

				// disable finalizing
				if (strstr($name, FRAMEWORK_OPTION_DISABLE_FINALIZING)) $framework->set_finalize(false);
				
				$this->load_framework($framework);
			} else {
				ErrorReporting::errors()->add_fatal("The requested framework \"$name\" could not be found in any defined frameworks. Please check the name or add an additional definition using -frameworks_xml.");
			}
			
		}
			
		// add frameworks from raw directory
		if (is_parser_option_enabled(PARSER_OPTION_DIRECTORY)) {
			$directory = get_parser_option(PARSER_OPTION_DIRECTORY);
			$name = basename($directory);
			$parts = explode(FRAMEWORK_BASE_SEPARATOR, $directory);
			$path = expand_path($parts[1]);

			if ($base = $this->find_framework($parts[0])) {
				$framework = Framework::clone_existing($name, $base);

				// override the framework path with the directory
				$framework->set_raw_directory($path);
				
				$this->add_defined_framework($framework);

				$this->load_framework($framework);
			} else {
				ErrorReporting::errors()->add_fatal("The base framework \"".$parts[0]."\" can not be found.");
			}
		}	
		
	}
	
	/**
	 * Class Methods
	 */
	
	public static function loader () {
		return self::$instance;
	}
			
	/**
	 * Magic Methods
	 */
	
	public function __construct ()  {
		
		// set the singleton instance
		self::$instance = &$this;
		
		// set a high memory limit to cope with potentially
		// massive symbol tables generated
		ini_set("memory_limit", PARSER_MEMORY_LIMIT);
				
		// set output directory
		$this->output = get_parser_option_path(PARSER_OPTION_OUTPUT);
		
		// verify command line options before proceeding
		$this->verify_command_line();
		
		// if -show is not enabled manage output
		if (!is_parser_option_enabled(PARSER_OPTION_SHOW)) {
			
			// output path wasn't set
			if (!$this->output) {
				ErrorReporting::errors()->add_fatal("You must specify -".PARSER_OPTION_OUTPUT.".");
			} else {
			
				// copy skeleton
				if (is_parser_option_enabled(PARSER_OPTION_SKELETON)) {
					$this->copy_skeleton(get_parser_option_path(PARSER_OPTION_SKELETON));
				}
						
				// create the output directory if it doesn't exist
				if (!file_exists($this->output)) {
					if (!@mkdir($this->output)) {
						ErrorReporting::errors()->add_fatal("The output directory \"$this->output\" can't be created.");
					}
				}
			}
		}

		// load defined frameworks from xml
		$this->load_frameworks_xml();
		
		// load batch frameworks and override -frameworks
		if (is_parser_option_enabled(PARSER_OPTION_BATCH)) {
			$frameworks = $this->load_batch_frameworks(get_parser_option(PARSER_OPTION_BATCH), get_parser_option(PARSER_OPTION_ONLY), get_parser_option(PARSER_OPTION_IGNORE));
			if (count($frameworks) == 0) {
				ErrorReporting::errors()->add_note("No frameworks to batch parse using the current options.");
			}
			
			// -only is only used to filter frameworks in batch mode
			// so we disable it now so all headers can compile
			clear_parser_option(PARSER_OPTION_ONLY);
		} else {
			$frameworks = get_parser_option(PARSER_OPTION_FRAMEWORKS);
		}

		// load framework from umbrella
		// if (is_parser_option_enabled(PARSER_OPTION_UMBRELLA)) {
		// 	$umbrella = get_parser_option_path(PARSER_OPTION_UMBRELLA);
		// 	$name = basename_without_extension($umbrella);
		// 	$name = ucfirst($name);
			
		// 	ErrorReporting::errors()->add_message("• Loading framework \"$name\" from ".basename($umbrella).".");
			
		// 	$framework = Framework::clone_existing($name, $this->find_framework("base"));
		// 	$framework->set_umbrella_header(basename($umbrella));
		// 	$framework->set_path(dirname($umbrella));
		// 	$framework->set_headers_directory(null);
		// 	$this->add_defined_framework($framework);

		// 	if (is_parser_option_enabled(PARSER_OPTION_DIRECTORY)) {
		// 		$this->frameworks_loaded[] = $framework;
		// 	}
		// }
		
		// add defined frameworks
		if ($frameworks) {
			
			// show a list of new frameworks search paths
			if (is_parser_option_enabled(PARSER_OPTION_FRAMEWORK_DIFFS)) {
				
				$paths = $this->get_search_paths();
				$names = array();
				
				foreach ($paths as $path) {
					$files = scandir($path);
					$files = array_diff($files, array(".", ".."));

					foreach ($files as $name) {
						$names[] = remove_file_extension($name);
					}
					
					$names = array_unique($names);
				}

				// print list of differences
				$new = array_diff($names, $frameworks);
				print("Different frameworks:\n\n");
				foreach ($new as $name) {
					print("$name\n");
				}
				
				die;
			}
			
			$this->load_command_line_frameworks($frameworks);
			
			$this->sort_loaded_frameworks();

			// perform post-sorting actions on loaded frameworks
			foreach ($this->frameworks_loaded as $framework) {
				
				// build skeletons
				if (get_parser_option(PARSER_OPTION_BUILD_SKELETONS)) $framework->build_skeleton();
				
				// print build command
				if (is_parser_option_enabled(PARSER_OPTION_BUILD_COMMANDS) && (!is_parser_option_enabled(PARSER_OPTION_GROUP) || is_parser_option_enabled(PARSER_OPTION_ALL_UNITS))) {
					$this->print_build_command($framework->get_name());
				}
				
			}
			
			// build group unit
			if (is_parser_option_enabled(PARSER_OPTION_GROUP)) {
				if (is_parser_option_enabled(PARSER_OPTION_GROUP_FRAMEWORKS)) {
					$this->build_group_unit(get_parser_option(PARSER_OPTION_GROUP), $this->frameworks_loaded, $this->get_group_frameworks(array(GROUP_FRAMEWORK_OPTION_USES)));
				} else {
					$this->build_group_unit(get_parser_option(PARSER_OPTION_GROUP), $this->frameworks_loaded, null);
				}
			}
			
		}
				
	}

}
		

?>
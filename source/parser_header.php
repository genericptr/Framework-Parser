<?php

require_once("symbol_table.php");
require_once("scope.php");
require_once("header.php");
require_once("errors.php");
require_once("language_utilities.php");

/**
* Header parser
*/

class HeaderParser extends MemoryManager {
	
	protected $header;					// reference to the current header being parsed
	protected $symbols;					// reference to the global symbol table
	
	private $modules = array();	// array of modules added to the parser
	
	// base pattern for source scope
	// the source scope doesn't actually process any patterns
	// so we declare outside of a module and add the scope manually
	// before parsing anything
	private $pattern_source = array(	"id" => 100, 
																		"scope" => SCOPE_SOURCE, 
																		"modules" => array(	MODULE_MACRO, MODULE_CLASS_FORWARD, MODULE_CATEGORY, MODULE_CLASS, MODULE_PROTOCOL, MODULE_STRUCT,
																												MODULE_TYPEDEF, MODULE_ENUM, MODULE_DEFINE, MODULE_VARIABLE, MODULE_CONSTANT, MODULE_FUNCTION,
																												),
																		);
	
	// white space will be ignored at the _start_ of offsets so 
	// patterns must attempt to match it.
	private $ignore_white_space = true;				
	
	// Debugging
	private $show_pattern_notes = false;			// prints notes on pattern parsing
	private $show_pattern_contents = false;		// prints the content of pattern/end scopes
	private $show_matching_notes = false;
	private $show_scope_tree = false;
	private $show_metrics = false;
	private $show_completeness = false;
	
	private $metrics_patterns = array();
	private $metrics_modules = array();
	private $metrics_pattern_count = array();
	private $metrics_processing = 0;

	// pattern kinds which identify the results from pattern matching
	const PATTERN_RANGE_START = "start";
	const PATTERN_RANGE_END = "end";
	const PATTERN_SINGLE = "single";
	
	/**
	 * Accessors
	 */
	
	
	// adds patterns from a helper parser inheriting its patterns
	public function add_module (HeaderParserModule $module) {
		$this->modules[$module->identifier] = $module;
	}
	
	/**
	 * Utilities
	 */
	
	private function apply_replacement_patterns ($contents) {
		foreach ($this->header->framework->replacement_patterns as $key => $value) {
			$contents = preg_replace_all($key, $value, $contents);
		}
		return $contents;
	}
	
	private function replace_availability_macros ($contents, &$macros) {
		$macros = array();
		$found = true;
		
		// loop until no patterns are matched
		while ($found) {
			$found = false;
			$offset = 0;
			
			// iterate each macro in the framework
			foreach ($this->header->framework->availability_macros as $key => $value) {
				while (preg_match($key, $contents, $captures, PREG_OFFSET_CAPTURE, $offset)) {

					// get the start/end offset and macro captured
					$macro = $captures[0][0];
					$start = (int)$captures[0][1];
					$length = strlen($captures[0][0]);
					$offset = $start + $length;

					// determine the entire line range of the macro so that is matches
					// any scope which is on the same line
					$line_end = INVALID_OFFSET;
					for ($i=$start; $i < strlen($contents); $i++) { 
						if ($contents[$i] == "\n") {
							$line_end = $i;
							break;
						}
					}

					$line_start = INVALID_OFFSET;
					for ($i=$start; $i >= 0; $i--) { 
						if ($contents[$i] == "\n") {
							$line_start = $i;
							break;
						}
					}
					
					// replace macro using pattern
					$replacement = preg_replace($key, $value, $macro);
					
					// remove tabs and line breaks
					$replacement = str_replace("\n", "", $replacement);
					$replacement = str_replace("\t", " ", $replacement);

					// insert a placeholder of whitespace the same size of the
					// macro string that was captured from the contents
					$place_holder = "";
					for ($i=0; $i < strlen($macro); $i++) $place_holder .= " ";
					$contents = substr_replace($contents, $place_holder, $start, $length);

					// add to array if the line range is valid
					if (($line_start != INVALID_OFFSET) && ($line_end != INVALID_OFFSET)) {
						$macros[] = array("start" => $line_start, "end" => $line_end, "length" => $length, "name" => $replacement);
					}
					$found = true;
				}
			}
		}
		
		//print_r($macros);
		//print($contents);
		
		return $contents;
	}
	
	// returns preprocessed contents of the header "cleaned" of things the parser can't or
	// doesn't want to handle (like comments) 
	private function get_header_contents ($file) {
		$contents = file_get_contents($file);

		// remove comments
		# $contents = preg_replace_balanced("/\/\*(\*(?!\/)|[^*])*\*\//s", $contents, true);
		# $contents = preg_replace_balanced("/\/\/(.*?)[\n]+/s", $contents, true);
		$contents = preg_replace("/\/\*(.*?)\*\//s", "", $contents);
		$contents = preg_replace("/\/\/(.*?)[\n]+/s", "\n", $contents);

		// remove all macros
		$contents = preg_replace_all($this->header->framework->remove_macros, "", $contents);
		
		// apply replacement patterns for the framework
		$contents = $this->apply_replacement_patterns($contents);

		// file_put_contents("/Users/ryanjoseph/Desktop/changes_10_15.h", $contents);

		//die($contents);
		return $contents;
	}
			
	/**
	 * Control methods
	 */
	
	private function match_pattern_single (array $pattern, $contents, Scope $scope, $offset) {
		
		if (in_array($pattern[PATTERN_KEY_IDENTIFIER], $scope->failed_patterns)) {
			return null;
		}
		
		$this->metrics_pattern_count[$pattern["scope"]] += 1;
		//print("   attempt pattern ".$pattern["scope"]."\n");
		
		if (preg_match($pattern[PATTERN_KEY_PATTERN], $contents, $captures, PREG_OFFSET_CAPTURE, $offset)) {
			
			if ($pattern[PATTERN_KEY_BREAK]) {
				if (preg_match($pattern[PATTERN_KEY_BREAK], $captures[0][0])) return null;
			}
						
			// prevent pattern from consuming whitespace
			$end_string = $captures[0][0];
			$end_string = rtrim($end_string);
			
			$start = (int)$captures[0][1];
			$end = (int)$captures[0][1] + strlen($end_string);
			$offset = $end;
			$base = $start;
			foreach ($captures as $capture) $results[] = $capture[0];
			
			return array("kind" => self::PATTERN_SINGLE, "base" => $base, "offset" => $offset, "results" => $results, "pattern" => $pattern, "start" => $start, "end" => $end, "length" => 0);
		} else {
			//print("   * pattern failed ".$pattern["scope"]." ".$pattern[PATTERN_KEY_IDENTIFIER]." at $offset\n");
			$scope->failed_patterns[] = $pattern[PATTERN_KEY_IDENTIFIER];
			
			return null;
		}
	}		
			
	private function match_pattern_range (array $pattern, $contents, Scope $scope, $offset) {
		$start = INVALID_OFFSET;
		$end = INVALID_OFFSET;
		//print("   attempt start ".$pattern["scope"]." at $offset\n");
		
		// find start range
		// if the pattern has failed previously ignore this part
		if (!in_array($pattern[PATTERN_KEY_IDENTIFIER], $scope->failed_patterns)) {
		
			$this->metrics_pattern_count[$pattern["scope"]] += 1;
		
			// match both ranges so we can decide which pattern comes first
			if (preg_match($pattern[PATTERN_KEY_START], $contents, $start_captures, PREG_OFFSET_CAPTURE, $offset)) {
				$start = (int)$start_captures[0][1];
				//print("   found start ".$pattern["scope"]." at $offset\n");
			
				if ($pattern[PATTERN_KEY_BREAK]) {
					if (preg_match($pattern[PATTERN_KEY_BREAK], $start_captures[0][0])) $start = INVALID_OFFSET;
				}
			
			} else {
				//print("   * start failed ".$pattern["scope"]." ".$pattern[PATTERN_KEY_IDENTIFIER]." at $offset\n");
				$scope->failed_patterns[] = $pattern[PATTERN_KEY_IDENTIFIER];
			
				$start = INVALID_OFFSET;
			}
		
		} else {
			$start = INVALID_OFFSET;
		}
		
		// find end range
		// first check if the scope has a persistent end already matched
		// and compare against that value instead of re-matching
		if ($scope->persistent_end) {
			$end = $scope->persistent_end["end"];
			$end_captures = $scope->persistent_end["captures"];
			$end_pattern = $scope->persistent_end["pattern"];
		} else {
		
			if (($scope->pattern) && ($scope->name != SCOPE_SOURCE)) {
				if ($scope->pattern[PATTERN_KEY_TERMINATE_FROM_START]) {
					$end_offset = $scope->start_from;
				} else {
					$end_offset = $offset;
				}
				$end_pattern = $scope->pattern;
			} else {
				$end_offset = $offset;
				$end_pattern = $pattern;
			}

			// only attempt to match end ranges if there is an open scope to match it
			if (($scope->is_within($end_pattern[PATTERN_KEY_SCOPE])) || ($scope->conforms_to($end_pattern[PATTERN_KEY_SCOPE]))) {
			
				//print("    FIND END for ".$end_pattern["scope"]."\n");
				$this->metrics_pattern_count[$end_pattern["scope"]] += 1;	
				//print("   attempt end ".$end_pattern["scope"]."at $end_offset\n");
			
				if (preg_match($end_pattern[PATTERN_KEY_END], $contents, $end_captures, PREG_OFFSET_CAPTURE, $end_offset)) {
					$end = (int)$end_captures[0][1];

					if ($pattern[PATTERN_KEY_BREAK]) {
						if (preg_match($end_pattern[PATTERN_KEY_BREAK], $end_captures[0][0])) $end = INVALID_OFFSET;
					}
				
				}
			}
		
		}
		
		// both ranges failed to produce results, bail
		if (($start == INVALID_OFFSET) && ($end == INVALID_OFFSET)) {
			return null;
		}
	
		// determine which pattern should take precedence by comparing location
		if ((($start < $end) && ($start > INVALID_OFFSET)) || ($end == INVALID_OFFSET)) {
			$length = strlen($start_captures[0][0]);
			$start = (int)$start_captures[0][1];
			$offset = $start;
			$base = $start;
			foreach ($start_captures as $capture) $results[] = $capture[0];
		
			return array("kind" => self::PATTERN_RANGE_START, "base" => $base, "offset" => $offset, "results" => $results, "pattern" => $pattern, "start" => $start, "length" => $length);
		} elseif ($end > INVALID_OFFSET) {
		
			if ($scope->persistent_end) {
				return $scope->persistent_end;
			}
		
			$length = strlen($end_captures[0][0]);
			$start = (int)$end_captures[0][1];
			$end = $start + $length;
			$offset = $end;
			$base = $start;
			foreach ($end_captures as $capture) $results[] = $capture[0];
		
			$result = array("kind" => self::PATTERN_RANGE_END, "base" => $base, "offset" => $offset, "results" => $results, "captures" => $end_captures, "pattern" => $end_pattern, "start" => $start, "end" => $end, "length" => 0);
		
			if ((!$scope->persistent_end) && ($end != INVALID_OFFSET)) {
				if ($this->show_matching_notes) print("   } found persistent end for ".$end_pattern["scope"]." at $offset\n");
				$scope->persistent_end = $result;
			}
		
			return $result;
		}
	
		// nothing was matched
		return null;
	}
		
	private function find_patterns_for_scope ($scope) {
		if ($scope->pattern) {
			$patterns = array();
			
			// merge all module patterns by named modules in the scopes pattern
			if ($modules = $scope->pattern[PATTERN_KEY_MODULES]) {
				
				// inherit modules from super scope
				if (($modules[0] == MODULE_SUPER_SCOPE) && ($scope->get_super_scope())) {
					
					// search upwards until the modules from the super scope
					// contain modules other than MODULE_SUPER_SCOPE, which
					// suggests a nested scope using MODULE_SUPER_SCOPE
					$super = $scope->get_super_scope();
					while ($super) {
						$modules = $super->pattern[PATTERN_KEY_MODULES];
						if ($modules[0] == MODULE_SUPER_SCOPE) {
							$super = $super->get_super_scope();
							continue;
						}
						$module = $scope->pattern[PATTERN_KEY_MODULE]->identifier;
						break;
					}
					
					// prepend the current module to the beginning of the array
					// so super scopes don't override it
					unset($modules[array_search($module, $modules)]);
					array_unshift($modules, $module);
				}
				
				//print_r($modules);
				
				foreach ($modules as $name) {
					if ($module = $this->modules[$name]) $patterns = array_merge($patterns, $module->get_patterns());
				}
			}	else {
				ErrorReporting::errors()->add_fatal("Scope $scope->name has no modules defined.");
			}
			
			return $patterns;
		}
	}
	
	private function process_next_scope ($contents, &$current_scope, &$offset) {
		$current_offset = INVALID_OFFSET;
		$current_result = null;
		$current_length = 0;
		$current_pattern = null;
		
		$matches = array();
		$conflicts = array();
		
		// ignore whitespace by incrementing offset until
		// non-whitespace is found
		if ($this->ignore_white_space) {
			for ($i=$offset; $i < strlen($contents); $i++) { 
				if (!ctype_space($contents[$i])) {
					break;
				} else {
					$offset += 1;
				}
			}
		}
		
		if ($this->show_matching_notes) print("- scope ".$current_scope->name." at $offset\n");
		
		// issue warning there is no scope
		if (!$current_scope) {
			ErrorReporting::errors()->add_fatal("There is no scope defined for offset $offset.");
		}
		
		// find patterns which are specified by the current scope
		$patterns = $this->find_patterns_for_scope($current_scope);
		if ($this->show_matching_notes) print("found ".count($patterns)." patterns for scope at $offset\n");
		
		// iterate all patterns to decide which one takes precedence for the offset
		foreach ($patterns as $pattern) {
				
			// ask the module to accept the scope before attempting any pattern matching
			if (!$pattern["module"]->accept_scope($current_scope)) continue;
									
			// match the pattern based on the kind (ranged or single)
			if (($pattern["start"]) && ($pattern["end"])) {
				$time_start = microtime_float();
				$result = $this->match_pattern_range($pattern, $contents, $current_scope, $offset);			
				$this->metrics_patterns[$pattern["scope"]] += microtime_float() - $time_start;
								
			} else {
				$time_start = microtime_float();
				$result = $this->match_pattern_single($pattern, $contents, $current_scope, $offset);
				$this->metrics_patterns[$pattern["scope"]] += microtime_float() - $time_start;
			}
			
			// valid result
			if ($result) {
				
				// match_pattern_range may have changed the pattern so use that pattern
				$pattern = $result["pattern"];
				
				if ($this->show_matching_notes) print("  matched ".$pattern["scope"]." (".$result["kind"].") at ".$result["offset"]." in ".$current_scope->name."\n");
				
				// ask the module if it will accept the pattern results
				// if modules don't want to allow to scopes to be added or opened they need to
				// reject the results here 
				if (($result["kind"] == self::PATTERN_RANGE_START) || ($result["kind"] == self::PATTERN_SINGLE)) {
					if (!$pattern["module"]->accept_pattern_results($pattern["id"], $result["results"])) {
						//$offset = $result["offset"] + $result["length"];
						if ($this->show_matching_notes) print("  ^ ".$pattern["scope"]." refused pattern results > $offset\n");

						//die("fatal: don't use accept_pattern_results until the for-loop skipping bug is fixed.");
						//reset($patterns);
						continue;
					}
				} 
				
				// keep track of matches and conflicts for debugging
				foreach ($matches as $match) {
					if (($match["offset"] == $result["offset"]) && ($match["scope"] != $pattern["scope"])) $conflicts[] = array("scope" => $pattern["scope"], "offset" => $result["offset"], "with" => $match["scope"]);
				}
				$matches[] = array("scope" => $pattern["scope"], "offset" => $result["offset"]);

				// determine if the offset takes precedence
				// and if so increment the search
				$accept = false;

				// the result is before the current offset
				if ($result["offset"] < $current_offset) $accept = true;

				// in the case of conflicts single patterns take precedence over start patterns
				// because they won't move past the current offset and consume the start pattern
				// next iteration
				if (($result["offset"] == $current_offset) && (($current_result["kind"] == self::PATTERN_RANGE_START) && ($result["kind"] == self::PATTERN_SINGLE))) {
					$accept = true;
				}
								
				// the current offset is invalid accept anything
				if ($current_offset == INVALID_OFFSET) $accept = true;

				if ($accept) {
					$current_offset = $result["offset"];
					$current_length = $result["length"];
					$current_base = $result["base"];
					$current_result = $result;
					$current_pattern = $pattern;
					
					// the start offset matched the current offset exactly which means
					// it will block the search and return the results.
					// this feature only works when $ignore_white_space is enabled
					// because patterns can be guaranteed match against characters
					// instead of arbitrary whitespace
					if (($result["start"] == $offset) && ($this->ignore_white_space)) {
						//print("*** pattern ".$pattern["scope"]." first offset\n");
						break;
					}
					
				}
			
			}
			
		}
				
		// process the result
		if ($current_result) {			
			$kind = $current_result["kind"];
			$pattern = $current_result["pattern"];
			$pattern_results = $current_result["results"];
			$start = $current_result["start"];
			$end = $current_result["end"];
						
			// report errors and debugging info
			if (!$pattern["scope"]) ErrorReporting::errors()->add_fatal("Scope at $start/$end has no pattern.");
			
			if ($this->show_matching_notes) print("> advance to $start/$end with ".$pattern["scope"]." (".$pattern["id"].")\n");
			
			// ??? these are buggy and printing duplicate conflicts!
			if ($this->show_pattern_notes) {
				foreach ($conflicts as $conflict) print("  !!! conflict ".$conflict["scope"]." with ".$conflict["with"]." at ".$conflict["offset"]."\n");
			}
			
			// single scope
			if ($kind == self::PATTERN_SINGLE) {
				$class_name = Scope::find_class_for_scope($pattern["scope"]);
				
				$scope = new $class_name($start, $end, $pattern["scope"], substr($contents, $start, $end - $start), $this->header);
				$scope->results = $pattern_results;
				$scope->pattern = $pattern;
				$scope->trim_range_white_space();
				
				$current_scope->add_sub_scope($scope);

				$time_start = microtime_float();
				$pattern["module"]->process_scope($pattern["id"], $scope);
				$this->metrics_modules[$pattern["scope"]] += microtime_float() - $time_start;
				
				// debugging notes
				if ($this->show_pattern_notes) print("# pattern (".$pattern["id"].") ".$scope->name." at $scope->start/$scope->end\n");
				if ($this->show_pattern_contents) print("\n    ".trim($scope->contents, " 	")."\n\n");
			}

			// start scope
			if ($kind == self::PATTERN_RANGE_START) {
				$class_name = Scope::find_class_for_scope($pattern["scope"]);
				
				$scope = new $class_name($start, 0, $pattern["scope"], 0, $this->header);
				$scope->start_results = $pattern_results;
				$scope->pattern = $pattern;

				$current_scope->add_sub_scope($scope);
				$current_scope = $scope;
								
				// debugging notes
				if ($this->show_pattern_notes) print("+ start ".$current_scope->name." (".$pattern["id"].") at ".$current_scope->start."\n");
				if ($this->show_pattern_contents) print("\n    ".substr($contents, $current_offset, $current_length)."\n\n");
				
			}
		
			// end scope
			if ($kind == self::PATTERN_RANGE_END) {

				// terminate scope recursion and increment next end result
				// this only applies to PATTERN_KEY_TERMINATE_FROM_START enabled patterns
				$super_scopes = $current_scope->find_super_scopes_flat();
				foreach ($super_scopes as $scope) {
					if ($scope->name == $current_scope->name) {
						if ($this->show_pattern_notes) print("* recursion in ".$current_scope->name." ended at ".$end."\n");
						if ($scope->pattern[PATTERN_KEY_TERMINATE_FROM_START]) {
							$scope->start_from = $end;
						}
						break;
					}
				}
							
				// check to make sure the module was specified in the pattern
				// if it wasn't the probably means the start scope was not defined (unbalanced scope)
				if ($pattern["module"]) {
					$current_scope->end = $end;
					$current_scope->contents = substr($contents, $current_scope->start, $current_scope->end - $current_scope->start);
					$current_scope->end_results = $pattern_results;
					$current_scope->trim_range_white_space();

					// debugging notes
					if ($this->show_pattern_notes) print("= end ".$current_scope->name." at ".$current_scope->start."/".$current_scope->end."\n");
					if ($this->show_pattern_contents) print("\n".trim($current_scope->contents, "\n")."\n\n");

					// let the module process results
					$time_start = microtime_float();
					$pattern["module"]->process_scope($pattern["id"], $current_scope);
					$this->metrics_modules[$pattern["scope"]] += microtime_float() - $time_start;

					// return the super scope since the scope terminated
					if ($current_scope->get_super_scope()) {
						$current_scope = $current_scope->get_super_scope();
						
						// NOTE: this is actually not effectent to rematch the end each time
						// a sub pattern terminates but I wasn't able to get it nested
						// scopes working without reseting persistent_end here
						$current_scope->persistent_end = null;
						
						if ($this->show_pattern_notes) print("\ changed to super scope ".$current_scope->name." at ".$current_scope->start."/".$current_scope->end."\n");
					}
					
					
				} else {
					ErrorReporting::errors()->add_note("The scope ".$current_scope->name." at $offset terminated unbalanced.");
				}
			}
						
			// return true and increment offset
			if ($current_pattern[PATTERN_KEY_LOCATION_OFFSET]) {
				$offset = $current_offset;
			} else {
				$offset = $current_offset + $current_length;
			}
			
			return true;
		}
		
	}
	
	public function print_metrics () {
		
		if ($this->show_metrics) {
			print("+-----------------------------------------------------------------+\n");
			print("| Metrics for ".$this->header->get_name().":\n");
			print("|\n");
			
			// patterns
			arsort($this->metrics_patterns);
			print("|   • patterns (by scope):\n");
			foreach ($this->metrics_patterns as $name => $time) {
				print("|     $name: $time seconds (".$this->metrics_pattern_count[$name]." attempts).\n");
			}
			
			// processing
			arsort($this->metrics_modules);
			print("|   • processing (by scope):\n");
			foreach ($this->metrics_modules as $name => $time) {
				print("|     $name: $time seconds.\n");
			}
			
			print("|\n");
			print("|  total processing: $this->metrics_processing seconds.\n");
			print("+-----------------------------------------------------------------+\n\n");
			
		}
	}
			
	/**
	 * Main Loop
	 */
	
	private function process_contents ($contents) {
		$offset = 0;
		$time_start = microtime_float();
		
		// make the root scope with source as the base for the entire content string
		$root = new Scope(0, strlen($contents) - 1, SCOPE_SOURCE, $contents, $this->header);
		$root->pattern = $this->pattern_source;
		$current = $root;
		
		// process all until no scopes can be found
		while (true) {
			if (!$this->process_next_scope($contents, $current, $offset)) break;	
			
			// print completeness percent for seeing performance
			if ($this->show_completeness) {
				$percent = ($offset / strlen($contents)) * 100;
				$percent = substr($percent, 0, strpos($percent, "."));  
				if ($percent != $complete) {
					print("$percent%");	
					$complete = $percent;
				}
			}
		}
		
		if ($this->show_scope_tree) $root->print_tree();
		
		// print metrics
		$this->metrics_processing = microtime_float() - $time_start;
		$this->print_metrics();
		
		return $root;
	}

	private function main_loop () {
		$contents = $this->get_header_contents($this->header->get_path());

		// prepare inherited patterns
		foreach ($this->modules as $module) $module->prepare($contents);
				
		// replace availability macros then set the array to the
		// current header to symbols can access them
		$contents = $this->replace_availability_macros($contents, $macros);
		$this->header->set_availability_macros($macros);

		// process
		$root = $this->process_contents($contents);
		
		// conclude inherited patterns
		foreach ($this->modules as $module) $module->conclude();
		
		return $root;
	}	
		
	// Enter the main parser loop
	public function parse () {
		return $this->main_loop();
	}
	
	protected function __free() {
		parent::__free();
		
		$this->header->free();
		unset($this->header);
		
		MemoryManager::free_array($this->modules);
	}
	
	function __construct(Header $header) {	
		if (!$header->exists()) {
			ErrorReporting::errors()->add_fatal("The header ".$header->get_name()." can't be found.");
		}
			
		$this->header = &$header;
		$this->symbols = &SymbolTable::table();
	}
}
		

?>
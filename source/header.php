<?php

require_once("errors.php");
require_once("output.php");
require_once("symbol_table.php");

require_once("parser_struct.php");
require_once("parser_typedef.php");
require_once("parser_enum.php");
require_once("parser_function.php");
require_once("parser_variable.php");
require_once("parser_constant.php");
require_once("parser_define.php");
require_once("parser_macro.php");
require_once("parser_field.php");
require_once("parser_property.php");
require_once("parser_category.php");
require_once("parser_protocol.php");
require_once("parser_class.php");

/**
 * Output helper for headers
 */

class HeaderOutput extends Output {
	private $header;
	private $enable_single_include_sections = false;				// sections can only be included once
	public $enable_header_sections = true;									// sections can only be included once
	
	// prints a symbol macro section open or closed
	public function print_section ($kind, $open) {
		if (!$this->enable_header_sections) return;
		
		if ($open) {
			$macro = strtoupper($this->header->get_actual_name());
			
			$this->writeln(0, "{\$ifdef $kind}");
			if ($this->enable_single_include_sections) {
				$this->writeln(0, "{\$ifndef $macro"."_$kind}");
				$this->writeln(0, "{\$define $macro"."_$kind}");
			}
		} else {
			if ($this->enable_single_include_sections) {
				$this->writeln(0, "{\$endif}");
			}
			$this->writeln(0, "{\$endif}");
			$this->writeln(0, "");
		}
	}
	
	function __construct($header, $path, $show) {	
		parent::__construct($path, $show);
		
		$this->header = &$header;
	}
	
}

/**
* Defines a single loaded header
*/
class Header extends MemoryManager {
	
	/**
	 * Public
	 */
	
	public $framework;										// reference to the framework the header was loaded from
	public $anonymous_struct_count = 0;		// count of anonymous (unnamed) structures in the header
	public $untitled_categories = 0;

	/**
	 * Private
	 */
	
	public $path;								// path to the header
	private $name;							// the full C-name of the header
	private $symbols;						// reference to the symbol table

	private $actual_name;				// actual name of the header without extention
	public $partial_path;			  // partial path containg the framework name and output file (AppKit/NSWindow)
															// this is used so headers can be uniquely referred to via the command line
	
	private $availability_macros = array();
	private $root_scope;
	private $parser;
	
	/**
	 * Accessors
	 */
	
	public function set_availability_macros ($macros) {
		$this->availability_macros = $macros;
	}
	
	public function belongs_to_framework (Framework $framework) {
		return ($this->framework->get_name() == $framework->get_name());
	}
	
	// returns the availability macro which is at the line contained in the range
	public function find_availability_macro ($start, $end) {
		foreach ($this->availability_macros as $macro) {
			
			// macro is within the scope range
			if (($macro["start"] >= $start) && ($macro["end"] <= $end)) {
				$name = $macro["name"];
				break;
			}
			
			// macro is after scope range
			if (($start >= $macro["start"]) && ($end <= $macro["end"])) {
				$name = $macro["name"];
				break;
			}
		}

		// escape single quote in deprecated statments
		if (preg_match("/^deprecated\s+'(.*)'/", $name, $matches)) {
			$contents = str_replace("'", "''", $matches[1]);
			$name = "deprecated '$contents';";
		}

		return $name;
	}

	public function get_actual_name () {
		return $this->actual_name;
	}

	public function get_name () {
		return $this->name;
	}
	
	// returns the header name with a given extension
	public function get_name_with_extension ($extension) {
		return $this->actual_name.".".$extension;
	}

	public function get_path () {
		return $this->path;
	}

	// returns the name (string) of the header which is displayed at
	// the top of the pascal output unit
	public function get_display_name () {
		if (is_parser_option_enabled(PARSER_OPTION_VERBOSE)) {
			return $this->path;
		} else {
			return $this->name;
		}
	}

	// returns the partial path by appending extension
	public function get_partial_path ($extension) {
		return $this->partial_path.".".$extension;
	}

	public function exists () {
		return file_exists($this->path);
	}	
		
	/**
	 * Methods
	 */
		
	public function parse () {
		
		if (MESSAGE_PEAK_MEMORY_LIMITS) {
			ErrorReporting::errors()->add_message("Parsing ".$this->get_display_name()." (".bytes_human_readable(memory_get_peak_usage(true)).")...");
		} else {
			ErrorReporting::errors()->add_message("Parsing ".$this->get_display_name()."...");
		}
											
		// execute relevant header parsers
		$this->parser = new HeaderParser($this);
		
		// create modules
		$module_macro = new HeaderMacroParser(MODULE_MACRO, $this);
		$module_class = new HeaderClassParser(MODULE_CLASS, $this);
		$module_class_forward = new HeaderClassForwardParser(MODULE_CLASS_FORWARD, $this);
		$module_class_section = new HeaderClassSectionParser(MODULE_CLASS_SECTION, $this);
		$module_protocol = new HeaderProtocolParser(MODULE_PROTOCOL, $this);
		$module_category = new HeaderCategoryParser(MODULE_CATEGORY, $this);
		$module_method = new HeaderMethodParser(MODULE_METHOD, $this);
		$module_property = new HeaderPropertyParser(MODULE_PROPERTY, $this);
		$module_ivar = new HeaderIVarParser(MODULE_IVAR, $this);
		$module_struct = new HeaderStructParser(MODULE_STRUCT, $this);
		$module_field = new HeaderFieldParser(MODULE_FIELD, $this);
		$module_enum = new HeaderEnumParser(MODULE_ENUM, $this);
		$module_enum_field = new HeaderEnumFieldParser(MODULE_FIELD_ENUM, $this);
		$module_typedef = new HeaderTypedefParser(MODULE_TYPEDEF, $this);
		$module_function = new HeaderFunctionParser(MODULE_FUNCTION, $this);
		$module_variable = new HeaderVariableParser(MODULE_VARIABLE, $this);
		$module_define = new HeaderDefineParser(MODULE_DEFINE, $this);
		$module_constant = new HeaderConstantParser(MODULE_CONSTANT, $this);

		// add modules to parser
		$this->parser->add_module($module_macro);
		$this->parser->add_module($module_class);
		$this->parser->add_module($module_class_section);
		$this->parser->add_module($module_class_forward);
		$this->parser->add_module($module_protocol);
		$this->parser->add_module($module_category);
		$this->parser->add_module($module_method);
		$this->parser->add_module($module_property);
		$this->parser->add_module($module_ivar);
		$this->parser->add_module($module_struct);
		$this->parser->add_module($module_field);
		$this->parser->add_module($module_enum);
		$this->parser->add_module($module_enum_field);
		$this->parser->add_module($module_typedef);
		$this->parser->add_module($module_function);
		$this->parser->add_module($module_constant);
		$this->parser->add_module($module_variable);
		$this->parser->add_module($module_define);

		// run the parser!
		$this->root_scope = $this->parser->parse();
	}	
	
	// prints the header as a stand-alone unit
	public function print_unit ($path, $show = false) {
		
		// get the units output path
		$path = rtrim($path, "/")."/".$this->get_name_with_extension("pas");

		if ($show) {
			ErrorReporting::errors()->add_message("  Printing ".basename($path));
		} else {
			ErrorReporting::errors()->add_message("  Printing '$path'");
		}
		
		// load the output file
		$output = new HeaderOutput($this, $path, $show);
		
		// disable if/def sections for stand alone units
		$output->enable_header_sections = false;
		
		$output->writeln(0, "{\$mode objfpc}");
		$output->writeln(0, "{\$modeswitch objectivec2}");

		$output->writeln(0, "unit ".basename_without_extension($path).";");
		$output->writeln(0, "interface");
		$output->writeln(0, "uses");
		$output->writeln(1, "CTypes, MacOSAll, CocoaAll;");
		
		// print other types from master symbol table
		$this->symbols->print_inline_array_types($this, $output);
		$this->symbols->print_nested_class_types($this, $output);
		$this->symbols->print_callbacks($this, $output);
		$this->symbols->print_class_pointers($this, $output);
		
		// print root scopes symbol table
		$this->root_scope->print_symbol_table($output);
		
		$output->writeln(0);
		$output->writeln(0, "implementation");
		$output->writeln(0, "end.");
		
		$output->close();
	}	
	
	// prints the headers symbol table to output
	public function print_output ($path, $absolute_path = false, $show = false) {
		
		// no root scope was parsed, bail
		if (!$this->root_scope) return;		
			
		// get the original path to the umbrella unit
		$umbrella = "$path/".$this->framework->get_name().".pas";

		// decide which path to print output to
		if (!$absolute_path) {
			$path = rtrim($path, "/")."/".$this->get_partial_path("inc");
		} else {
			$path = rtrim($path, "/")."/".$this->get_name_with_extension("inc");
		}
		
		// test if the directory we're printing to exists
		if (!$show) {
			$dir = dirname($path);
			if (!file_exists($dir)) {
				if (!@mkdir($dir)) ErrorReporting::errors()->add_fatal("The output directory \"$dir\" can't be created.");
			}
		}

		// in safe mode only print files if the file doesn't already exist or the modification
		// date is the same as the original date of the umbrella unit
		if (is_parser_option_enabled(PARSER_OPTION_SAFE_WRITE) && file_exists($path)) {
			if (filemtime($path) > filemtime($umbrella)) {
				ErrorReporting::errors()->add_note(basename($path)." has been changed, ignoring.");
				return;
			}
		} 

		ErrorReporting::errors()->add_message("  Printing ".basename($path));
		
		// load the output file
		$output = new HeaderOutput($this, $path, $show);
		
		// build the header message with some information about the header
		$sdk = $this->framework->get_sdk();
		
		// NOTE: this is making diff'ing messy so it's being removed for now
		$sdk = null;
	
		if ($sdk) {
			$header_message = "{ Parsed from ".ucfirst($this->framework->get_name()).".framework ($sdk) ".$this->get_display_name()." }";
		} else {
			$header_message = "{ Parsed from ".ucfirst($this->framework->get_name()).".framework ".$this->get_display_name()." }";
		}
		$output->writeln(0, $header_message);
		
		// print created on date
		// NOTE: this is making diff'ing messy so it's being removed for now
		$show_date = false;
		if ($show_date) {
			$date = @date("D M j G:i:s Y");
			$output->writeln(0, "{ Created on $date }");
			$output->writeln();
		}

		// print other types from master symbol table
		$this->symbols->print_inline_array_types($this, $output);
		$this->symbols->print_nested_class_types($this, $output);
		$this->symbols->print_callbacks($this, $output);
		$this->symbols->print_class_pointers($this, $output);
		
		// print root scopes symbol table
		$this->root_scope->print_symbol_table($output);

		// synchronize modification date with umbrella unit
		if (file_exists($umbrella))
			touch($path, filemtime($umbrella));
	}	
				
	/**
	 * Constructors
	 */
	
	public function __toString() {
		return $this->name;
	}
	
	protected function __free() {
		parent::__free();
		
		if ($this->root_scope) {
			$this->root_scope->free();
			unset($this->root_scope);
		}
		
		$this->parser->free();
		unset($this->parser);
	}
		
	function __construct ($path, Framework $framework)  {
		
		if (!$framework) {
			ErrorReporting::errors()->add_exception("The framework is invalid.");
		}
		
		$this->path = $path;
		$this->name = basename($path);
		$this->framework = &$framework;
		
		// add header to the framework
		$this->framework->add_header($this);
		
		// get the plain name without extension
		$this->actual_name = substr($this->name, 0, (strripos($this->name, ".")));	
		$this->partial_path = $this->framework->get_root_directory()."/$this->actual_name";
		
		$this->symbols = &SymbolTable::table();
	}

}
		

?>
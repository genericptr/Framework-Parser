<?php

require_once("parser_module.php");
require_once("errors.php");
require_once("language_utilities.php");
require_once("utilities.php");

// function source types
// these control the way HeaderFunctionParser::build_function_pointer (and others) builds the source string
define("FUNCTION_SOURCE_TYPE_EXTERNAL", 1);		// procedure name; cdecl; external;
define("FUNCTION_SOURCE_TYPE_FIELD", 2);		// name: procedure; cdel;
define("FUNCTION_SOURCE_TYPE_TYPE", 3);			// name = procedure; cdecl;
define("FUNCTION_SOURCE_TYPE_CBLOCK", 4);		// name = reference to procedure; cdecl; cblock;

define("FUNCTION_TYPE_PROCEDURE", 1);
define("FUNCTION_TYPE_FUNCTION", 2);

define("NO_FUNCTION_NAME", null);

class FunctionSymbol extends Symbol {
	public $kind;
	public $return_type;
	public $source_type;
	public $parameters;
	public $varargs;
	public $is_callback;
	
	public function get_section () {
		return HEADER_SECTION_FUNCTIONS;
	}
	
	public function build_source ($indent = 0) {
		
		// add function kind
		if ($this->kind == FUNCTION_TYPE_PROCEDURE)
			$source = "procedure ";
		else
			$source = "function ";

		// add function name based on source type
		// if $name is null then the caller should add the name to start of the string
		if ($this->name != "") {
			switch ($this->source_type) {
				case FUNCTION_SOURCE_TYPE_EXTERNAL: {
					$source = indent_string($indent).$source.$this->name;
					break;
				}
				case FUNCTION_SOURCE_TYPE_FIELD: {
					$source = indent_string($indent).$this->name.": ".$source;
					break;
				}
				case FUNCTION_SOURCE_TYPE_TYPE:
				case FUNCTION_SOURCE_TYPE_CBLOCK: {
					$source = indent_string($indent).$this->name." = " .$source;
					break;
				}
				default: {
					ErrorReporting::errors()->add_exception("Building source with invalid source type.");
					break;
				}
			}
		}
		
		// add cblock prefix
		if ($this->source_type == FUNCTION_SOURCE_TYPE_CBLOCK) {
			$source = indent_string($indent)."reference to $source";
		}

		// add parameters
		if ($this->parameters) $source .= "(".implode("; ", $this->parameters).")";
		
		// add return type
		if ($this->kind == FUNCTION_TYPE_PROCEDURE)
			$source .= ";";
		else
			$source .= ": ".$this->return_type.";";
	 
		// variable arguments
		if ($this->varargs) $source .= " varargs;";
	
		// add cblock modifier
		if ($this->source_type == FUNCTION_SOURCE_TYPE_CBLOCK) {
			$source .= " ".CBLOCK_CALLING_MODIFIER.";";
		}

		// add calling convention modifier
		$source .= " ".EXTERNAL_FUNCTION_CALLING_MODIFIER.";";

		// add external modifier
	  if ($this->source_type == FUNCTION_SOURCE_TYPE_EXTERNAL) $source .= " external;";
		
		// insert deprecated macro
		$source .= $this->insert_deprecated_macro(true);
		
		$this->source = $source;
	}
	
}

/**
* Parser for external functions
*/
class HeaderFunctionParser extends HeaderParserModule {
		
	private $pattern_function_external = array(	"id" => 1, 
												"scope" => SCOPE_FUNCTION, 
												"pattern" => "/(\$1)\s*([^;]+)\s*\(([^);]+)\)\s*;/is",
												);
	
	private $pattern_function_inline = array(	"id" => 2, 
												"scope" => SCOPE_FUNCTION_INLINE, 
												"start" => "/(\$1)\s*([^;]+)\s*\(([^);]+)\)\s*\{/is",
												"end" => "/\}/",
												// we have to add a module for the parser to terminate the 
												// scope but this should be optional.
												"modules" => array(MODULE_MACRO),
												);
	
	private $pattern_function_plain_c = array(	"id" => 3, 
												"scope" => SCOPE_FUNCTION, 
												"pattern" => "/([^;]+)\s*\(([^);]+)\)\s*;/is",
												);

	// unnamed parameter function (with possible named callback)
	// (NSInteger (*)(int, void *))
	// (NSInteger (*name)(int, void *))
	private $pregex_parameter_callback_unnamed = "/^\s*\(([^(]+)\s*\((\*|\^)\s*(\w+)*\)\s*\((.*?)\)\)\s*,/";	
	
	// unnamed parameter function without parenthesis
	// NSInteger (*name)(int, void *)
	// NSInteger (*)(int, void *)
	private $pregex_parameter_callback_unnamed_no_parenthesis = "/^\s*([^(]+)\s*\((\*|\^)\s*(\w+)*\)\s*\((.*?)\)\s*,/";	
	
	// generic parameter
	private $pregex_parameter_generic = "/^\s*\w+(.*?),/";	
	
	// variable arguments parameter
	private $pregex_parameter_varags = "/^\s*\.{3},/";	
		
	/**
	 * Class Methods
	 */
	
	// returns a c-function declaration source string from the parameters
	// use this method outside of the parser for function processing
		
	// same as build_external_function except used for function pointers
	// $name can be NULL.
	// $source_type is only required if $name is specified
	public static function build_function_pointer (&$header, $return_type, $name, $parameters, $source_type) {
		$function_parser = new HeaderFunctionParser(MODULE_FUNCTION, $header);
		$function = $function_parser->parse_function_declaration($name, $return_type, $parameters, $source_type);
		$function->build_source(0);
		
		$function_parser->free();
		unset($function_parser);
		
		return $function->source;
	}
	
	// same as build_function_pointer except it returns a FunctionSymbol
	public static function build_function_pointer_symbol (&$header, $return_type, $name, $parameters, $source_type) {
		$function_parser = new HeaderFunctionParser(MODULE_FUNCTION, $header);
		$function = $function_parser->parse_function_declaration($name, $return_type, $parameters, $source_type);
		
		$function_parser->free();
		unset($function_parser);
		
		return $function;
	}	
	
	// adds a function callback type with name $name, and returns
	// the name of the callback type
	public static function add_callback(&$header, $name, &$function_pointer, &$callback = null) {
		$callback = new TypedefSymbol($header);
		$callback->name = ucwords($header->get_actual_name()).ucwords($name).CALLBACK_SUFFIX;
		$callback->is_callback = true;

		// the function pointer can be a symbol or a string
		if ($function_pointer instanceof FunctionSymbol) {
			$callback->function = &$function_pointer;
			$callback->type = null;
		} else {
			$callback->type = $function_pointer;
		}

		// the type has already been declared, return the name
		// but don't add the symbol to the table
		if (SymbolTable::table()->is_type_declared($callback->name)) return $callback->name;
		
		// add as typedef to the symbol table
		SymbolTable::table()->add_symbol($callback);

		// add the callback type as an implicit pointer
		SymbolTable::table()->add_implicit_pointer($callback->name);

		return $callback->name;
	}
	
	/**
	 * Methods
	 */
		
	private function convert_generic_parameter ($param, &$index, FunctionSymbol &$function) {
		// the parameter is marked const in c so we need to 
		// make note before it's removed in extract_name_type_pair()
		if (preg_match("/^\s*const/", $param)) $is_const = true;
		
		// extract the name/type pair or auto-index if the 
		// pair is invalid (an inline callback)
		if (!extract_name_type_pair($param, $name, $type)) {
			$type = trim($param);
			$name = UNDEFINED_PARAMETER_NAME_PREFIX.$index;
		}

		// format array from pair type
		format_array_pair($name, $type, $array, "", $this->header);

		// format c type
		$type = format_c_type($type, $this->header);
							
		// protect reserved keywords
		if (is_keyword_reserved($name)) protect_keyword($name);
		
		// protect against pairs with the same name as the type (boolean: boolean)
		//if (preg_match("/\b$name\b/", $type)) protect_keyword($name);
		
		// there is an extraneous * modifier left in
		// from a rare case of removing const keywords
		// but we can safely just trim it away I think
		if ($name[0] == "*") {
			$name = trim($name, "*");
		}
		
		// use the var parameter look up table for
		// compatbility with universal headers
		if (is_var_parameter($function->name, $index)) {
			$name = "var $name";
			revert_generic_pointer($type);
		}
		
		// make the function dependent on the type
		$function->add_dependency($type);
		SymbolTable::table()->add_used_type($type);
				
		// return the parameter string
		return "$name: $type";
	}	
	
	private function convert_function_parameter (FunctionSymbol $function, $name, $kind, $return_type, $params, &$count) {
		
		// auto-increment name if none was specified
		if (!$name) {
			$count ++;
			$name = UNDEFINED_PARAMETER_NAME_PREFIX.$count;
		}
		
		// block callback
		if ($kind == "^") {
			return "$name: ".OPAQUE_BLOCK_TYPE;
		}
		
		// function callback
		if ($kind == "*") {

			// build the function pointer from inline pointer type
			$function_pointer = HeaderFunctionParser::build_function_pointer($this->header, $return_type, NO_FUNCTION_NAME, $params, FUNCTION_SOURCE_TYPE_TYPE);

			// add the function pointer as a callback
			$callback_name = ucwords($function->name).ucwords($name);
			$type = HeaderFunctionParser::add_callback($this->header, $callback_name, $function_pointer);

			return "$name: $type";
		}
	
	}	
				
	// Converts a C parameter string to Pascal
	private function convert_parameters (FunctionSymbol $function, $string) {
		// print("convert params for $function->name -> $string\n");
		
		// remove line breaks in parameters
		$string = str_remove_lines($string);
		$string = clean_objc_generics($string);

		// void or empty parameters, return an empty string
		if ((trim($string) == "void")  || (trim($string) == "")) return null;
		
		$contents = $string;
		$parameters = array();
		$final = false;
		$index = 0;
		
		// iterate all the parameter string matching
		// all possible parameter patterns
		while ($contents) {
						
			if (preg_match($this->pregex_parameter_callback_unnamed, $contents, $captures, PREG_OFFSET_CAPTURE)) {
				$parameters[] = $this->convert_function_parameter($function, $captures[3][0], $captures[2][0], $captures[1][0], $captures[4][0], $count);
				$contents = substr($contents, strlen($captures[0][0]));
				continue;
			}
			
			if (preg_match($this->pregex_parameter_callback_unnamed_no_parenthesis, $contents, $captures, PREG_OFFSET_CAPTURE)) {
				$parameters[] = $this->convert_function_parameter($function, $captures[3][0], $captures[2][0], $captures[1][0], $captures[4][0], $count);
				$contents = substr($contents, strlen($captures[0][0]));
				continue;
			}
			
			if (preg_match($this->pregex_parameter_generic, $contents, $captures, PREG_OFFSET_CAPTURE)) {
				$index++;
				$part = trim($captures[0][0], ", ");
				$parameters[] = $this->convert_generic_parameter($part, $index, $function);
				$contents = substr($contents, strlen($captures[0][0]));
				continue;
			}
			
			if (preg_match($this->pregex_parameter_varags, $contents, $captures, PREG_OFFSET_CAPTURE)) {
				$function->varargs = true;
				$contents = substr($contents, strlen($captures[0][0]));
				continue;
			}
			
			// all patterns failed but the string is not empty
			// which means the last paremter was found but 
			// has no comma to match our patterns
			if (($contents) && (!$final)) {
				$contents .= ",";
				$final = true;
				continue;
			}
			
			// no patterns matched
			break;
		}
		
		return $parameters;
	}		
		
			
	private function parse_function_declaration ($name, $return_type, $parameters, $source_type) {
		$function = new FunctionSymbol($this->header);
		$function->name = $name;
		$function->source_type = $source_type;
		$return_type = clean_objc_generics($return_type);
		$return_type = format_c_type($return_type, $this->header);
		$function->return_type = $return_type;

		global_namespace_protect_keyword($function->name);
		
		// get function kind
		if (trim($return_type) == PROCEDURE_RETURN_TYPE) {
			$function->kind = FUNCTION_TYPE_PROCEDURE;
		} else {
			$function->kind = FUNCTION_TYPE_FUNCTION;
		}
		
		// convert parameters to pascal string
		$function->parameters = $this->convert_parameters($function, $parameters);
			  
	  return $function;
	}	
	
	// extracts name/return type from pattern result string
	private function extract_name_return_type ($string, &$name, &$return_type) {
		$string = clean_objc_generics($string);
		if (preg_match("/^(.*?)(\w+)$/s", trim($string), $captures)) {
			$name = $captures[2];
			$return_type = trim($captures[1]);
			return true;
		} else {
			$name = null;
			$return_type = null;
			return false;
		}
	}
					
	private function parse_function ($results, $plain_c = false) {
		if ($plain_c) {
			$this->extract_name_return_type($results[1], $name, $return_type);
			return $this->parse_function_declaration($name, $return_type, $results[2], FUNCTION_SOURCE_TYPE_EXTERNAL);
		} else {
			$this->extract_name_return_type($results[2], $name, $return_type);
			return $this->parse_function_declaration($name, $return_type, $results[3], FUNCTION_SOURCE_TYPE_EXTERNAL);
		}
	}
							
	public function process_scope ($id, Scope $scope) {
		parent::process_scope($id, $scope);
		
		// print("got function ($id) at $scope->start/$scope->end\n");
		// print($scope->contents."\n");
		// print_r($scope->results);

		switch ($id) {
			
			// external functions
			case 1: {
				if ($function = $this->parse_function($scope->results)) {
					$function->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$this->symbols->add_symbol($function);
					$scope->set_symbol($function);
				}
				break;
			}
			
			// inline functions
			case 2: {
				$this->extract_name_return_type($scope->start_results[2], $name, $return_type);
				
				// invalid name
				if (!$name) break;
				
				if (MESSAGE_INLINE_FUNCTIONS) ErrorReporting::errors()->add_note("An inline function $name is being ignored (parse by hand).");
				break;
			}
			
			// plain-c functions
			case 3: {
				if ($function = $this->parse_function($scope->results, true)) {
					$function->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$this->symbols->add_symbol($function);
					$scope->set_symbol($function);
				}
				break;
			}
			
		}
	}		
		
	public function prepare (&$contents) {
		$offset = 0;
		
		// format extern c blocks
		if (is_parser_option_enabled(PARSER_OPTION_EXTERNC)) {
			$blocks = array();
			
			$offset = 0;			
			while (preg_match("/[\n]+\s*(\w+)\s+(\w+)\(([^(]+)\)([^#{;]+);/is", $contents, $captures, PREG_OFFSET_CAPTURE, $offset)) {
				//print_r($captures);
				$contents = substr_replace($contents, "extern ", $captures[0][1] + 1, 0);

				//ErrorReporting::errors()->add_message("- Added extern to function \"$name\" from ".basename($umbrella).".");

				// get the start/end offset and macro captured
				$result = $captures[0][0];
				$start = (int)$captures[0][1];
				$length = strlen($captures[0][0]);
				$offset = $start + $length;
			}
			//print($contents);
		}
		
	}
	
		
	public function init () {
		parent::init();
		
		// add external string macros
		$this->pattern_function_external["\$1"] = implode("|", $this->header->framework->external_macros);
		$this->pattern_function_inline["\$1"] = implode("|", $this->header->framework->inline_macros);
		
		$this->add_pattern($this->pattern_function_inline);
		$this->add_pattern($this->pattern_function_external);
		
		if (is_parser_option_enabled(PARSER_OPTION_PLAIN_C)) $this->add_pattern($this->pattern_function_plain_c);
	}

}

/**
 * Var param table utilities
 * 
 * For compatibility with the universal headers
 * we need to parse the Pascal units to determine which
 * functions have "var" parameters because we can not 
 * duplicate this functionality in the PHP parser
 */

function print_param_functions ($file) {
	$lines = file($file);
	$results = array();
	
	foreach ($lines as $line) {
		$params = null;
		$function = null;
		
		// remove comments
		$line = preg_replace("/{.*}/", "", $line);
		
		// callbacks
		if (preg_match("/(\w+)\s*=\s*(procedure|function)+\s*\((.*)\)/i", $line, $captures)) {
			$function = $captures[1];
			$params = $captures[3];
		}
		
		// functions
		if (preg_match("/(procedure|function)+\s+(\w+)\s*\((.*)\)/i", $line, $captures)) {
			$function = $captures[2];
			$params = $captures[3];
		}
		
		// parse params
		if ($params) {
			$params = preg_split("/\s*;\s*/", $params);
			
			foreach ($params as $key => $param) {
				if (preg_match("/var\s+\w+\s*:\s*\w+/", $param)) {
					$param_index = $key + 1;
					if (!$results[$function]) $results[$function] = array();
					$results[$function][] = $param_index;
				}
			}
		}
		
	}
	
	// print results
	foreach ($results as $function => $params) {
		$param_string = null;
		foreach ($params as $value) $param_string .= "|$value";
		print("$function$param_string\n");
	}
}

function print_param_functions_directory ($directory) {
	$files = directory_contents($directory);
	foreach ($files as $file) {
		print_param_functions($file);
	}
}

function load_var_params_table ($file) {
	if (!file_exists($file)) return array();
	$table = array();
	$lines = file($file);
	foreach ($lines as $line) {
		$parts = explode("|", $line);
		$function = array_shift($parts);
		$table[$function] = array();
		foreach ($parts as $part) $table[$function][] = $part;
	}
	return $table;
}

function is_var_parameter($function, $index) {
	global $var_params_table;
	return @in_array($index, $var_params_table[$function]);
}

$var_params_table = load_var_params_table(expand_root_path("@/support/var_params_table.txt"));

//print_param_functions_directory("/Developer/FreePascalCompiler/2.6.0/Source/packages/univint/src");

?>
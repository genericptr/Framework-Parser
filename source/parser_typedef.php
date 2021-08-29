<?php

require_once("parser_module.php");
require_once("language_utilities.php");

/**
 * Typedef symbol
 */
class InlineArrayTypedefSymbol extends TypedefSymbol {
	
	function __construct(Header $header, $elements) {
		parent::__construct($header);
		
		$this->array_elements = $elements;
		$this->is_array = true;
	}
	
}


/**
 * Typedef symbol
 */
class TypedefSymbol extends Symbol {
	public $type;
	public $array_elements = 0;
	public $implicit_type_pointer = false;
	
	public $is_array = false;
	public $is_pointer = false;
	public $is_block = false;
	public $is_struct = false;
	public $is_union = false;
	public $is_enum = false;
	public $is_callback = false;
	
	public static function make_pointer ($name, $type, Header $header) {
		$symbol = new TypedefSymbol($header);
		$symbol->name = $name;
		$symbol->type = $type;
		$symbol->is_pointer = true;
		return $symbol;
	}
	
	public static function make_simple ($name, $type, Header $header) {
		$symbol = new TypedefSymbol($header);
		$symbol->name = $name;
		$symbol->type = $type;
		return $symbol;
	}
	
	public function get_section () {
		return HEADER_SECTION_TYPES;
	}
	
	public function get_base_indent () {
		return 1;
	}
	
	public function get_block_header () {
		return "type";
	}
	
	// compares the symbol against the typedef for 
	// equality. note that we can't simply compare names
	// because 
	public function compare (Symbol $symbol) {
		
		// literal same symbol
		if ($this->uuid == $symbol->uuid) return true;
		
		if (!is_a($symbol, "TypedefSymbol")) return false;
		if ($this->name != $symbol->name) return false;
		if ($this->is_struct != $symbol->is_struct) return false;
		if ($this->is_union != $symbol->is_union) return false;
		if ($this->is_enum != $symbol->is_enum) return false;
		
		return true;
	}
	
	public function finalize () {
		if ($this->is_free()) return;
		
		// if the type has not been defined as either a typedef or struct we assume 
		// the type is opaque to protect against missing types later
		if ($this->is_struct || $this->is_union) {
			$symbol = SymbolTable::table()->find_symbol($this->type, "StructSymbol", ANY_HEADER, $this->framework, SEARCH_IMPORTED_FRAMEWORKS);
			if (!$symbol) {
				if (MESSAGE_OPAQUE_TYPEDEFS) ErrorReporting::errors()->add_message("  $this->name is being declared as opaque");
				$this->type = OPAQUE_TYPEDEF_TYPE;
				$this->is_pointer = false;
			} else {
				
				if ($this->name == $symbol->name) {
					if (MESSAGE_OPAQUE_TYPEDEFS) ErrorReporting::errors()->add_message("  $this->name defines a struct of the same name");
					$this->remove_from_scope();
					$this->remove_from_tables();
				}
			}
		}
		
	}
	
	public function build_source ($indent = 0) {
		$name = $this->name;
		$type = $this->type;
		
		// make pointer
		if ($this->is_pointer) $type = "^$type";
		
		// make array
		if ($this->is_array) {
			$type = $this->type;
			$array_count = $this->array_elements - 1;
			if ($array_count < 0) $array_count = 0;
			
			if (!$this->is_pointer) {
				$type = "array[0..$array_count] of $type";
			} else {
				// make the type a pointer of the new array type
				$type = "^".$name;
				$name = $name."Pointer";
			}
			
		}
		
		// build the final line from parts
		$type = trim($type, ";");
		$source = "$name = $type".$this->insert_deprecated_macro().";\n";
		
		$this->source = indent_string($indent).$source;
	}
}

/**
* Typedef parser
*/
class HeaderTypedefParser extends HeaderParserModule {
		
	// generic single-line typedef which will match most kinds except for
	// nested constructs like enums, structs, unions etc...
	// $1 = contents
	private $pattern_typedef_generic = array(	"id" => 1, 
																						"scope" => SCOPE_TYPE, 
																						"pattern" => "/(typedef)\s+(.*?)\s*;/i",
																						);
	
	// typedef function pointer type (definition only)
	// example: typedef void NSUncaughtExceptionHandler(NSException *exception);
	private $pattern_typedef_function_type = array(	"id" => 2, 
																									"scope" => SCOPE_TYPE, 
																									"pattern" => "/(typedef)\s+([^;]+)\s*\((.*?)\)\s*;/is",
																									);
	
	// typedef function pointer with pointer
	// example: typedef NSPoint (*_NSPositionOfGlyphMethod)(NSFont *obj, SEL sel, NSGlyph cur, NSGlyph prev, BOOL *isNominal);
	private $pattern_typedef_function_pointer = array(	"id" => 3, 
																											"scope" => SCOPE_TYPE, 
																											"pattern" => "/(typedef)\s+([^;]+)\s*\(([^;]+)\)\s*\((.*?)\)\s*;/is",
																											);
	
	/**
	 * Methods
	 */
				
	// formats typedef as pointers to the type
	// note, we can't use format_c_type() here because it 
	// will encode types as pointer by suffixing
	private function format_typedef_pointer (TypedefSymbol &$typedef) {
		// check to see if the name is a pointer
		if (preg_match("/^\*/", $typedef->name)) {
			$typedef->name = trim($typedef->name, "*");
			$typedef->is_pointer = true;
			
			// set the type as an implicit pointer
			$this->symbols->add_implicit_pointer($typedef->name);
			
			// if the type has been defined in the framework then 
			// don't set as pointer because it would be redudant
			if ($this->header->framework->is_type_defined_pointer($typedef->type)) {
				$typedef->implicit_type_pointer = true;
				$typedef->is_pointer = false;
			}
		}
	}
	
	// formats typedef for arrays
	private function format_typedef_array (TypedefSymbol &$typedef) {
		// check to make sure name is an array
		if (preg_match("/(.*)\[\s*(\d+)\s*\]$/", $typedef->name, $captures)) {
			$typedef->name = $captures[1];
			$typedef->array_elements = $captures[2];
			$typedef->is_array = true;
		}
	}
	
			
	// parses a typedef by contents which is all text between "typedef" and ";"
	private function parse_typedef ($contents, Scope $scope) {	
		$list = array();
		$typedefs = array();
		
		// the type is a struct 
		if (preg_match("/\b(struct)\b/", $contents)) $is_struct = true;
		
		// the type is a union 
		if (preg_match("/\b(union)\b/", $contents)) $is_union = true;

		// the type is a enum 
		if (preg_match("/\b(enum)\b/", $contents)) $is_enum = true;
		
		// remove unused keywords that confuse parsing
		$contents = replace_unused_keywords($contents);
				
		// move * in pointers directly before the name to make splitting easier
		// we don't them before names so format_c_type won't encode types as pointers
		// and gives the typedef parser a chance to create the type for the pointer
		$contents = preg_replace("/\*{1}\s*(\w+)/", " *$1", $contents);
		
		// move , in type lists directly after the word
		// $contents = preg_replace("/(\*)*(\w+)\s*,{1}/", "$1$2, " , $contents);
		//print($contents."\n");
		
		// move [] directly after word and remove spaces inside brackets
		$contents = preg_replace("/(\w+)\s*\[\s*(\w+)*\s*\]/", " $1[$2]" , $contents);
		
		// split the contents into parts divided by white space

		// TODO: seems I thought typedefs could be a list but that's not true
		$type = "";
		if (preg_match("/(\w+$)/", $contents, $matches, PREG_OFFSET_CAPTURE)) {
			$list[] .= $matches[1][0];
			$type = substr($contents, 0, $matches[1][1]);
			$type = format_c_type($type, $this->header);
		}
		
		/*

		$parts = preg_split("/\s+(?=(\w+$))/", $contents);
		// print("$contents\n");
		// print_r($parts);

		// the name is the last indentifier
		$list[] = array_pop($parts);
		
		// pop off additional names in list
		foreach ($parts as $key => $value) {
			if (preg_match("/([*]*\w+),$/", $value, $captures)) {
				$list[] = $captures[1];
				unset($parts[$key]);
			}
		}
		print_r($list);
		
		// rebuild the type from the remaining parts
		$type = implode(" ", $parts);
		$type = format_c_type($type, $this->header);
		*/

		// make a typedef for each available name
		foreach ($list as $name) {
			$typedef = new TypedefSymbol($this->header);
			$typedef->name = $name;
			$typedef->type = $type;
			$typedef->is_struct = $is_struct;
			$typedef->is_enum = $is_enum;
			$typedef->is_union = $is_union;

			// set the type for enums
			if ($typedef->is_enum) $typedef->type = TYPDEF_ENUM_TYPE;			
						
			// format for extra information
			$this->format_typedef_array($typedef);
			$this->format_typedef_pointer($typedef);

			// the identifier has already been declared
			if (!$typedef->verify_scope_availability($scope->get_super_scope())) continue;

			// add dependency on type
			$typedef->add_dependency($typedef->type);
					
			// add to array
			$typedefs[] = $typedef;
			
			// if the type is a pointer to an array
			// we need to add the array type also
			if (($typedef->is_pointer) && ($typedef->is_array)) {
				$symbol = new TypedefSymbol($this->header);
				$symbol->name = $typedef->name;
				$symbol->type = $typedef->type;
				$symbol->is_array = true;
				$symbol->array_elements = $typedef->array_elements;
				$typedef->add_dependent($symbol);
				$typedefs[] = $symbol;
			}
			
			// add a pointer for the type which may be needed
			// for type casting for var-parameters
			if ((!$typedef->is_pointer) && (!$typedef->implicit_type_pointer)) {
				$symbol = new TypedefSymbol($this->header);
				$symbol->name = $typedef->name.POINTER_SUFFIX;
				$symbol->type = $typedef->name;
				$symbol->is_pointer = true;
				$typedef->add_dependent($symbol);
				
				$this->symbols->add_implicit_pointer($symbol->name);
				$typedefs[] = $symbol;
			}
						
		}
			
		// set the type was used
		foreach ($typedefs as $typedef) {
			$this->symbols->add_used_type($typedef->type);
		}	
						
		return $typedefs;
	}		
			
	// parses a typedef function type or pointer pattern
	private function parse_typedef_function ($name, $return_type, $parameters, Scope $scope) {	
		$typedef = new TypedefSymbol($this->header);
		
		// if not return type was specified attempt to extract name/type
		// from the name
		if (!$return_type) {
			extract_name_type_pair($name, $name, $return_type);
		}
		
		// blocks
		if (preg_match("/^\^/", $name)) $typedef->is_block = true;
		
		// remove * from names since pointers to function pointers all interpreted
		// as plain function pointers
		$name = preg_replace("/^\*/", "", $name);

		// build the function pointer type without name
		if (!$typedef->is_block) {
			$function = HeaderFunctionParser::build_function_pointer_symbol($this->header, $return_type, $name, $parameters, FUNCTION_SOURCE_TYPE_TYPE);
			
			// build source without name
			$function->name = null;
			$function->build_source(0);
			$type = $function->source;
			
			// add dependencies from function pointer
			$typedef->dependencies = array_merge($typedef->dependencies, $function->dependencies);
			
			// add the type as an implicit pointer
			SymbolTable::table()->add_implicit_pointer($name);
		} else {
			$name = trim($name, "^ ");
			// TODO: use "reference to" syntax for cblocks (make Cblocks function parser method)
			// print("name: $name\n");
			// print("return: $return_type\n");
			// print("parameters: $parameters\n");
			// die;
			$type = OPAQUE_BLOCK_TYPE;
		}
				
		$typedef->name = $name;
		$typedef->type = $type;

		return $typedef;
	}		
		
	public function process_scope ($id, Scope $scope) {
		parent::process_scope($id, $scope);

		// print("** got typedef at $scope->start/$scope->end\n");
		// print($scope->contents."\n");
		// print_r($scope->results);

		switch ($id) {
			
			// generic typedefs
			case 1: {
				if ($typedefs = $this->parse_typedef($scope->results[2], $scope)) {
					foreach ($typedefs as $typedef) {
						$typedef->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
						$this->symbols->add_symbol($typedef);
					}
					$scope->set_symbol($typedefs[0]);
				}
				
				break;
			}
			
			// function pointers
			case 2: {
				if ($typedef = $this->parse_typedef_function($scope->results[2], null, $scope->results[3], $scope)) {
					$typedef->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$this->symbols->add_symbol($typedef);
					$scope->set_symbol($typedef);
				}
				
				break;
			}
			
			// function pointers
			case 3: {
				if ($typedef = $this->parse_typedef_function($scope->results[3], $scope->results[2], $scope->results[4], $scope)) {
					$typedef->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$this->symbols->add_symbol($typedef);
					$scope->set_symbol($typedef);
				}
				
				break;
			}

		}

	}
		
		
	public function init () {
		parent::init();
		
		$this->name = "typedef";

		// NOTE: this order is important for precedence
		$this->add_pattern($this->pattern_typedef_function_pointer);
		$this->add_pattern($this->pattern_typedef_function_type);
		$this->add_pattern($this->pattern_typedef_generic);
	}		

}
		

?>
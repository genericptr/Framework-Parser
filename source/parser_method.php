<?php

require_once("parser_header.php");
require_once("parser_method.php");
require_once("symbol.php");

/**
 * Method symbol
 */
class MethodSymbol extends Symbol {
	public $parameters = array();		// array of ParameterPair
	public $prefix;									// method prefix (- or +)		
	public $is_class;								// if true the method is class level
	public $return_type;						// return type (or HeaderMethodParser::RETURN_TYPE_VOID for procedures)
	public $objective_c_source;			// raw objective-c source from header
	public $is_procedure;						// the method is a procedure (in pascal)
	public $selector;								// objective-c selector
	public $varargs;								// the method has multiple parameters (varargs)
	
	public $class = null;						// ClassSymbol the method was added to
	
	/**
	 * Methods
	 */
	
	// protect all parameter names in the classes namespace
	private function protect_parameter_names (ClassSymbol $class) {
		foreach ($this->parameters as $pair) {
			$class->protect_keyword($pair->name);
		}
	}

	public function finalize (ClassSymbol $class) {
		
		$this->protect_parameter_names($class);
		$class->protect_method($this);
		$class->namespace->add_keyword($this->name);	

		// replace generic paramter names in class
		for ($i=0; $i < count($this->parameters); $i++) { 
			$pair = &$this->parameters[$i];

			if ($class->has_generic_params()) {
				$pair->type = $class->replace_generic_params($pair->type);
			}

			// replace generic params in callback pointer string
			// note: at this point we only have the raw string because it was
			// already generated before we had class information so we need to use
			// a word replace
			if ($class->has_generic_params() && $pair->get_callback_symbol($symbol, $this->header)) {
				$symbol->type = $class->replace_generic_params($symbol->type);
			}
		}

		if ($class->has_generic_params()) {
			$this->return_type = $class->replace_generic_params($this->return_type);
		}

		// TODO: if a param is a function pointer we need to replace generic params in the exported definition
	}
	
	// invoked after a method is added to a class
	public function added_to_class (ClassSymbol $class) {
		$this->class = $class;
	}
		
	public function build_source ($indent = 0) {
		
		$source = '';

		// add class method
		if ($this->is_class) $source .= "class ";
		
		// add routine type
		if ($this->is_procedure) {
			$source .= "procedure ";
		} else {
			$source .= "function ";
		}
		
		// add name
		$source .= $this->name;
		
		// add parameters
		$parameters = '';
		if ($this->parameters) {
			foreach ($this->parameters as $param) {
				$parameters .= $param->name.": ".$param->type."; ";
			}
			$parameters = trim($parameters, "; ");
			$source .= " (".$parameters.")";
		}
		
		// return type
		if (!$this->is_procedure) {
			$source .= ": ".$this->return_type;
		}
		
		// variable arguments
		if ($this->varargs) {
			$source .= "; varargs";
		}
		
		// message identifier
		$source .= "; message '".$this->selector."';";
						
		// add deprecated macro
		if ($this->deprecated_macro) $source .= " ".$this->deprecated_macro;
		
		$this->source = indent_string($indent).$source."\n";
	}
	
}

/**
 * Parameter pair (name with type)
 */
class ParameterPair {
	public $name;								// parameter name
	public $type;								// parameter type
	public $label;							// optional label
	
	public $source; 						// plain c source
	public $post_declaration;		// any source following the standard name/type
	
	public $varargs = false;		// the paramter is variable arguments
	public $protocol_hint;			// the protocol hint <NSProtocol> for the paramter
	public $modifiers;					// optional pascal modifiers for the paramater (like var)
	public $function_pointer;		// pascal function pointer source for inline functions pointers

	public function get_callback_symbol(&$symbol, Header $header): bool  {
		if ($this->function_pointer) {
			$symbol = SymbolTable::table()->find_symbol($this->type, ANY_CLASS, $header);
			return true;
		} else {
			return false;
		}
	}

	function __construct($name = '', $type = '', $label = '') {
		$this->name = $name;
		$this->type = $type;
		$this->label = $label;
	}
}


/**
* Method parser
*/
class HeaderMethodParser extends HeaderParserModule {
		
	private $pattern_method = array(	"id" => 1, 
																		"scope" => SCOPE_METHOD, 
																		"pattern" => "/(-|\+)+\s*\(([^)]*)\)\s*([^;]*)\s*[^:;]*;/is",
																		);

	private $pattern_method_cblock_return = array(	"id" => 2, 
																									"scope" => SCOPE_METHOD, 
																									"pattern" => "/(-|\+)+\s*\(\s*((\w+)\s*[*]*\s*)\(\s*\^\s*\)\s*\((.*?)\s*\)\s*\)\s*([^;]*)\s*[^:;]*;/is",
																								);

	// NSInteger (*)(id, id, void *)
	private $pregex_callback_parameter = "/((\w+)\s*[*]*\s*)\(\s*\*\s*\)\s*\((.*?)\)/";
	
	// void (^)(id obj, NSUInteger idx, BOOL *stop)
	private $pregex_block_parameter = "/((\w+)\s*[*]*\s*)\(\s*\^\s*(?:\w+)*\)\s*\((.*)\)/";
	
	// label:(NSString*)name
	private $pregex_selector = "/(?P<label>\w+)\s*:\s*(\((?P<type>.*?)\)|)\s*(?P<name>\w+)/";

	// ,...
	private $pregex_varargs = "/,\s*\.{3}\s*$/";
	private $pregex_procedure = "/^\s*(IBAction|void)\s*$/i";
	
	/**
	 * Constants
	 */
	
	const CLASS_METHOD_PREFIX = "+";
					
	/**
	 * Private
	 */
						
	// build pascal method name from objective-c selector
	private function build_method_name (MethodSymbol &$method) {
		
		if ($method->parameters) {
			foreach ($method->parameters as $param) {
				$name .= METHOD_SELECETOR_SEPARATOR.$param->label;
			}
			$method->name = ltrim($name, METHOD_SELECETOR_SEPARATOR);
		}

		reserved_namespace_protect_keyword($method->name);
	} 
	
	// build objective-c selector for message keyword
	public static function build_selector (MethodSymbol &$method) {
		
		if ($method->parameters) {
			$selector = '';
			foreach ($method->parameters as $param) { 
				$selector .= $param->label.OBJC_SELECETOR_SEPARATOR;
			}
		} else {
			$selector = $method->name;
		}
		
		$method->selector = $selector;
	}
	
	private function parse_return_type ($return_type, MethodSymbol &$method) {
		$return_type = clean_objc_generics($return_type);
		$return_type = replace_remote_messaging_modifiers($return_type, $null);	
		$return_type = replace_garbage_collector_hints($return_type, $null);
		
		if (preg_match($this->pregex_procedure, $return_type)) {
			$method->is_procedure = true;
		}
		
		$return_type = format_c_type($return_type, $this->header);
		
		return $return_type;
	}
	
	private function parse_parameter_pair (ParameterPair &$pair, MethodSymbol $method) {
		$pair->type = clean_objc_generics($pair->type);

		// format array from pair type
		format_array_type($pair->type, $array, ucfirst($method->name), $this->header);
			
		// format source
		$pair->source = trim($pair->source);
		$pair->type = format_c_type($pair->type, $this->header);

		// inline block
		if (preg_match($this->pregex_block_parameter, $pair->type, $captures)) {

			// the parameter could be a nested block but I'm too lazy to fix that now
			// so we just capture it and return an opaque type
			if (preg_match($this->pregex_block_parameter, $captures[3], $captures_nested)) {
				$captures[3] = OPAQUE_BLOCK_TYPE;
			}

			$pair->function_pointer = HeaderFunctionParser::build_function_pointer($this->header, $captures[2], NO_FUNCTION_NAME, $captures[3], FUNCTION_SOURCE_TYPE_CBLOCK);
			$pair->type = HeaderFunctionParser::add_callback($this->header, $method->name, $pair->function_pointer);
		}

		// inline callback
		if (preg_match($this->pregex_callback_parameter, $pair->type, $captures)) {
			
			// build the function pointer from inline pointer type
			$pair->function_pointer = HeaderFunctionParser::build_function_pointer($this->header, $captures[2], NO_FUNCTION_NAME, $captures[3], FUNCTION_SOURCE_TYPE_TYPE);
			
			// add the function pointer as a callback
			$callback_name = ucwords($method->name).ucwords($pair->name);
			$pair->type = HeaderFunctionParser::add_callback($this->header, $callback_name, $pair->function_pointer);
		} 
		
		// protect against pairs with the same name as the type (boolean: boolean)
		if (preg_match("!\b$pair->name\b!i", $pair->type)) protect_keyword($pair->name);
		
		// protect in reserved namespace
		reserved_namespace_protect_keyword($pair->name);
					
		// parameter without type defaults to id in objective-c
		if (!$pair->type) $pair->type = DEFAULT_PARAMATER_TYPE;

		// parameters can have duplicate names in objective-c
		// but we need to label them uniquely for pascal
		foreach ($method->parameters as $param) {
			if ($param->name == $pair->name) {
				$index = count($method->parameters) + 1;
				$pair->name = $pair->name.$index;
				break;
			}
		}
	}
			
	private function process_method ($source, $prefix, $return_type, $parameters) {
		
		// build method symbol
		$method = new MethodSymbol($this->header);
		
		$method->objective_c_source = $source;
		$method->prefix = $prefix;

		// if the return type is an array this mean it's a cblock and we need to process it later
		if (!is_array($return_type)) {
			$method->return_type = $this->parse_return_type($return_type, $method);
		}

		// parse parameters
	 	if (preg_match_all($this->pregex_selector, $parameters, $captures)) {
			// print_r($captures);
			for ($i=0; $i < count($captures[0]); $i++) { 
				$pair = new ParameterPair();
				$pair->name = $captures["name"][$i];
				$pair->type = $captures["type"][$i];
				$pair->label = $captures["label"][$i];
				$pair->source = $captures[0][$i];
				// print_r($pair);

				// name is the first label
				if (count($method->parameters) == 0) $method->name = trim($pair->label);
				
				// parse parameter pair further
				$this->parse_parameter_pair($pair, $method);
				
				// add a used type for the parameter type
				SymbolTable::table()->add_used_type($pair->type);
				
				// add to method parameter array
				$method->parameters[] = $pair;
			}
			
			// has multiple parameters
			if (preg_match($this->pregex_varargs, $parameters)) $method->varargs = true;
			
		} else {
			// no parameters found, use the only word as the name
			$method->name = trim($parameters);
		}
				
		if ($method->prefix == self::CLASS_METHOD_PREFIX) {
			$method->is_class = true;
		}
		
		// build the objective-c selector from parameters
		$this->build_selector($method);
		
		// build the pascal method name
		$this->build_method_name($method);
			
		// process cblock return types
		if (is_array($return_type)) {
			$function_pointer = HeaderFunctionParser::build_function_pointer($this->header, $return_type['return'], NO_FUNCTION_NAME, $return_type['parameters'], FUNCTION_SOURCE_TYPE_CBLOCK);
			$method->return_type = HeaderFunctionParser::add_callback($this->header, $method->name, $function_pointer);
		}

		return $method;
	}
							
	function process_scope ($id, Scope $scope) {
		parent::process_scope($id, $scope);

		// print("✅ got method ($id) at $scope->start/$scope->end\n");
		// print($scope->contents."\n");
		// print_r($scope->results);

		switch ($id) {
			// normal method
			case 1: {
				$results = $scope->results;
				if ($method = $this->process_method($results[0], $results[1], $results[2], $results[3])) {
					$method->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$scope->set_symbol($method);
				}
				break;
			}
			// method with cblock return type
			case 2: {
				$results = $scope->results;

				// rebuild the cblock for the return type
				$cblock = array(
					'return' => $results[2], 
					'parameters' => $results[4]
				);

				if ($method = $this->process_method($results[0], $results[1], $cblock, $results[5])) {
					$method->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$scope->set_symbol($method);
				}

				break;
			}
		}

	}
	
	public function init () {
		parent::init();

		$this->name = "method";

		$this->add_pattern($this->pattern_method_cblock_return);
		$this->add_pattern($this->pattern_method);
	}

}
		

?>
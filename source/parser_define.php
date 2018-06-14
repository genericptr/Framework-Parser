<?php

require_once("parser_module.php");

/**
 * Define Symbol
 */
class DefineSymbol extends Symbol {
	public $value;
	public $bitwise_or;
	
	public function get_section () {
		return HEADER_SECTION_TYPES;
	}
	
	public function get_base_indent () {
		return 1;
	}
	
	public function get_block_header () {
		return "const";
	}
	
	public function can_become_constant () {
		return true;
	}
		
	// returns true if the value is a word
	public function is_value_word () {
		if ($this->value) {
			return @preg_match("/^[A-Za-z_]+/", $this->value);
		} else {
			return false;
		}
	}
	
	public function build_source ($indent = 0) {
		//$source = indent_string($indent)."const\n";
		//$indent += 1;
		
		if ($this->bitwise_or) {
			foreach ($this->value as $part) $value .= $part." + ";
			$value = trim($value, "+ ");
		} else {
			$value = $this->value;
		}
		
		$source .= indent_string($indent).$this->name." = $value".$this->insert_deprecated_macro().";";
		
		$this->source = $source."\n";
	}
}

/**
* Define Parser
*/
class HeaderDefineParser extends HeaderParserModule {
		
	// Patterns
	
	// basic define pattern of #define name value
	// $1 = name
	// $2 = value
	private $pattern_define = array(	"id" => 1, 
																		"scope" => SCOPE_CONST, 
																		"pattern" => "/#\s*define\s+(\w+)\s+([^\n]+)\s*[\n]/i",																		
																		);
	
	// function like define with parameter list
	// #define <identifier>(<parameter list>) <replacement token list>
	private $pattern_define_function = array(	"id" => 2, 
																						"scope" => SCOPE_CONST, 
																						//"pattern" => "/#\s*define\s+(\w+)\s*\(([^)]+)\)\s+([^\n]+)\s*[\n]/i",
																						"pattern" => "/#\s*define\s+(\w+)\s*\((.*?)\)\s+(.*?)\s*[\n]/i",
																						);
	
	// external macro of any kind
	private $pattern_define_external_macro = array(	"id" => 3, 
																									"scope" => SCOPE_NULL, 
																									"pattern" => "/#\s*define\s+(\w+)\s+extern\s*[\n]/i",
																									);
	
	// define which specifies a macro like {$define FOO}
	private $pattern_define_macro = array(	"id" => 4, 
																					"scope" => SCOPE_CONST, 
																					"pattern" => "/#\s*define\s+(\w+)\s*[\n]/i",																		
																					);
		
	/**
	 * Methods
	 */
			
	private function parse_define ($name, $value, $pattern, Scope $scope) {		
		
		// apply define replacements and return
		if ($replaced_value = $this->header->framework->apply_define_replacement($value)) {
			$define = new DefineSymbol($this->header);		
			$define->name = $name;
			$define->value = $replaced_value;
			return $define;
		}
		
		// filter word(word) patterns which will 
		// be converted to single words in convert_assigned_value()
		if (preg_match("/\w+\s*\(.*?\)/", $value)) {
			return;
		}
		
		// filter (word)word patterns which will 
		// be converted to single words in convert_assigned_value()
		if (preg_match("/\(.*?\)\s*\w+/", $value)) {
			return;
		}
		
		$define = new DefineSymbol($this->header);		
		$define->name = $name;
		$define->value = convert_assigned_value($value, $define->bitwise_or);
		
		// the identifier has already been declared
		if (!$define->verify_scope_availability($scope->get_super_scope())) return null;		
						
		// names can't the define same type in pascal
		if ($define->name == $define->value) {
			$define->free();
			return null;
		}
		
		// the converted value is not valid pascal, refuse the define
		if (!is_pascal_value_format($define->value)) {
			$define->free();
			return null;
		}
		
		// refuse to define ignored types
		if ($this->header->framework->ignore_type($define->value)) {
			$define->free();
			return null;
		}
		
		// add dependency for alpha words values so we get moved
		// after them if the type exists in the same header
		if ($define->is_value_word()) {
			$define->add_dependency($define->value);
		}
		
		return $define;
	}						
							
	function process_scope ($id, Scope $scope) {
		//print("got define $id at $scope->start/$scope->end\n");
		//print($scope->contents."\n");
		//print_r($scope->results);
		
		switch ($id) {
			case 1: {		
				if ($define = $this->parse_define($scope->results[1], $scope->results[2], $id, $scope)) {
					$define->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$scope->set_symbol($define);
					$this->symbols->add_symbol($define);	
				}
				break;
			}
			case 2: {
				// ignore macro functions until we have a better plan for them...
				break;
			}
			case 3: {
				$name = $scope->results[1];
				$this->header->framework->add_external_macro($name);
				if (MESSAGE_DEFINE_EXTERNAL_MACRO) ErrorReporting::errors()->add_message("  $name is being defined as an external macro in \"".$this->header->framework->get_name()."\"");
				break;
			}
			
			case 4: {
				// ignore until we make {$define} symbols
				break;
			}
			
		}
	}
		
	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_define_macro);
		$this->add_pattern($this->pattern_define_external_macro);
		$this->add_pattern($this->pattern_define);
		$this->add_pattern($this->pattern_define_function);
	}		

}
		

?>
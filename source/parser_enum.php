<?php

require_once("parser_module.php");
require_once("parser_typedef.php");

/**
 * Enumeration field symbol
 */
class EnumFieldSymbol extends Symbol {
	public $value = null;
	public $bitwise_or = false;
		
	public function can_become_constant () {
		return true;
	}
		
	public function build_source ($indent) {
		if ($this->bitwise_or) {
			foreach ($this->value as $part) $value .= $part." + ";
			$value = trim($value, "+ ");
		} else {
			$value = $this->value;
		}
		
		$source = indent_string($indent).$this->name." = $value".$this->insert_deprecated_macro().";\n";

		$this->source = $source;
	}
	
}

/**
 * Enumeration field parser
 */
class HeaderEnumFieldParser extends HeaderParserModule {

	private $pattern_enum_field_string = array(	"id" => 1, 
																							"scope" => SCOPE_FIELD_CONST, 
																							"pattern" => "/(\w+)\s*=\s*('.*?')[,]*/i",
																							"break" => "/^\s*\}/",
																							);
																				
	private $pattern_enum_field_generic = array(	"id" => 2, 
																								"scope" => SCOPE_FIELD_CONST, 
																								"pattern" => "/(\w+)\s*(=\s*([^,;}#]+))*(,)*/i",
																								"break" => "/^\s*\}/",
																								);
	
	public function accept_scope (Scope $scope) {
		return ($scope->conforms_to(SCOPE_BLOCK_CONST) || ($scope->is_within(SCOPE_BLOCK_CONST)));
	}
	
	public function process_field ($name, $value, Scope $scope) {
		$field = new EnumFieldSymbol($this->header);
		$field->name = $name;
		
		// the identifier has already been declared
		if (!$field->verify_scope_availability($scope->get_super_scope())) return;		
		
		$value = trim($value);
		$field->value = convert_assigned_value($value, $field->bitwise_or);
		
		// replace 4-character strings with FourCharCode
		// ??? I think this requires macpas mode which won't work with objective-pascal
		// so it's disabled until we seperate the frameworks as "carbon" or "cocoa"
		//$field->value = preg_replace("/'(.{4})'/i", FOUR_CHAR_CODE_REPLACE_PATTERN, $field->value);
	
		// set availability macro
		$field->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
				
		return $field;
	}
	
	public function process_scope ($id, Scope $scope) {
		//print("got enum field at $scope->start/$scope->end\n");
		//print($scope->contents."\n");
		//print_r($scope->results);
		
		switch ($id) {
			
			// string field
			case 1: {				
				if ($field = $this->process_field($scope->results[1], $scope->results[2], $scope)) $scope->set_symbol($field);
				break;
			}
			
			// generic field
			case 2: {				
				if ($field = $this->process_field($scope->results[1], $scope->results[3], $scope)) $scope->set_symbol($field);
				break;
			}
			
		}

	}

	public function init () {
		parent::init();
		$this->add_pattern($this->pattern_enum_field_string);
		$this->add_pattern($this->pattern_enum_field_generic);
	}		
	
}

/**
 * Enumeration block symbol
 */
class EnumBlockSymbol extends Symbol {
	public $is_typedef = false;
	
	public function get_section () {
		return HEADER_SECTION_TYPES;
	}
	
	public function is_declarable () {
		return false;
	}
		
	public function build_source ($indent) {
		$source .= indent_string($indent)."const\n";
		$source .= $this->scope->get_sub_scope_symbol_source($indent + 1);
		
		$this->source = $source;
	}

}

/**
* Enumeration parser
*/
class HeaderEnumParser extends HeaderParserModule {
		
	// Patterns
	private $pattern_enum = array(	"id" => 1, 
									"scope" => SCOPE_BLOCK_CONST, 
									"start" => "/(typedef)*\s*enum\s*(\w+)*\s*\{/i",
									"end" => "/\}(.*?);/is",
									"modules" => array(MODULE_MACRO, MODULE_FIELD_ENUM),
									// fields can consume the enum end so terminate from start
									PATTERN_KEY_TERMINATE_FROM_START => true,
									);

	private $pattern_enum_with_colon = array(	"id" => 2, 
												"scope" => SCOPE_BLOCK_CONST, 
												"start" => "/(typedef)*\s*enum\s*:\s*(\w+)\s*\{/i",
												"end" => "/\}(.*?);/is",
												"modules" => array(MODULE_MACRO, MODULE_FIELD_ENUM),
												// fields can consume the enum end so terminate from start
												PATTERN_KEY_TERMINATE_FROM_START => true,
												);
	private $pattern_enum_with_type = array(	"id" => 3, 
												"scope" => SCOPE_BLOCK_CONST, 
												"start" => "/typedef\s+enum\s*(\w+)\s*:\s*(\w+)\s*\{/i",
												"end" => "/\}(.*?);/is",
												"modules" => array(MODULE_MACRO, MODULE_FIELD_ENUM),
												// fields can consume the enum end so terminate from start
												PATTERN_KEY_TERMINATE_FROM_START => true,
												);
				
	/**
	 * Methods
	 */
			
	// auto-increments the fields of an enum if needed
	private function auto_increment_fields (Scope $scope) {
		$auto_increment = 0;
		//$field_count = 0;
		$fields = $scope->find_sub_scopes(SCOPE_FIELD_CONST, true);
		
		foreach ($fields as $field) {
			
			// get the base auto-increment value
			if (ctype_digit($field->value)) {
				$auto_increment = (int)$field->value + 1;
				continue;
			}
			
			// the field doesn't have a value and must be auto-incremented
			if (!$field->value) {
				$field->value = $auto_increment;
				$auto_increment++;
			}
			
			//$field_count++;
		}
		
	}
								
	public function process_scope ($id, Scope $scope) {
		//print("got enum block $id at $scope->start/$scope->end\n");
		//print($scope->contents."\n");
		
		$enum = new EnumBlockSymbol($this->header);
		
		// enum is a typedef
		if ($scope->start_results[1]) {
			$enum->is_typedef = true;
			
			// the name of typedef appears at the end of the {} brackets and before the ;
			$enum->name = $scope->end_results[1];
			
			// enums with types declared after colons (pattern #2) use
			// the defined type instead of TYPDEF_ENUM_TYPE
			if (($id == 2) || ($id == 3)) $enum->type = $scope->start_results[2];
						
		} elseif ($id == 1) {
			// if the enum is not a typedef try to get the name from the first
			// word after the enum keyword (which may not exist)
			// usually this syntax must be used in conjunction with the enum keyword
			// but in Pascal the keyword is implicit so we just keep the name
			if ($scope->start_results[2]) $enum->name = $scope->start_results[2];
		}
		$enum->name = trim($enum->name);
		
		// auto-increment the fields of the enum if needed
		$this->auto_increment_fields($scope);
		
		// the enum was declared as a typedef so we need to add the type
		// to the symbol table
		if (($enum->is_typedef) && ($enum->name)) {
			$typedef = new TypedefSymbol($this->header);
			$typedef->name = $enum->name;
			if (!$enum->type) {
				$typedef->type = TYPDEF_ENUM_TYPE;
			} else {
				$typedef->type = $enum->type;
			}
			$this->symbols->add_symbol($typedef);
			
			$enum->add_dependent($typedef);
		}
		
		$this->symbols->add_symbol($enum);
		$scope->set_symbol($enum);
	}
			
	public function init () {
		parent::init();
		$this->add_pattern($this->pattern_enum);
		$this->add_pattern($this->pattern_enum_with_colon);
		$this->add_pattern($this->pattern_enum_with_type);
	}		

}
		

?>
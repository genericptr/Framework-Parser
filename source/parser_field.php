<?php

require_once("parser_module.php");
require_once("language_utilities.php");
require_once("utilities.php");

/**
 * Field symbol
 */

class FieldSymbol extends Symbol {
	public $type;
	public $array_elements = array();
	public $bit_field = null;
	public $garbage_collector_hint = null;
	public $names = array();
	private $class = null;

	private function is_multi_dimensional_array() {
		return (bool)(count($this->array_dimensions) > 1);
	}
	
	public function contains_name ($name) {
		foreach ($this->names as $field_name) {
			if (strcasecmp($field_name, $name) == 0) return true;
		}
	}
	
	public function replace_name ($old_name, $new_name) {
		foreach ($this->names as $key => $field_name) {
			if (strcasecmp($field_name, $old_name) == 0) {
				$this->names[$key] = $new_name;
			}
		}
	}
	
	public function added_to_class (ClassSymbol $class) {
		$this->class = $class;
	}

	public function build_source ($indent = 0) {
		
		$name = implode(", ", $this->names);
		$source = $name.":";

		// replace generic params
		if ($this->class && $this->class->has_generic_params())
			$this->type = $this->class->replace_generic_params($this->type);

		// make bit field 
		// as far as I know bit fields can not be arrays so we handle them separately
		if ($this->bit_field) {
			if ($this->bit_field > 1) {
				$source .= " 0..((1 shl $this->bit_field)-1)";
			} else {
				$source .= " 0..$this->bit_field";
			}
		} else {
			// array type
			if (count($this->array_elements) > 0) {
				$source .= build_array_elements_source($this->array_elements, $this->type);
			} else {
				$source .= " $this->type";
			}
		}
		
		// add garbage collector hint
		//if ($this->garbage_collector_hint) $source .= " { $this->garbage_collector_hint }";

		// terminate
		$source .= $this->insert_deprecated_macro().";";

		$this->source = indent_string($indent).$source."\n";
	}
}

/**
 * Callback symbol
 */

class CallbackSymbol extends FieldSymbol {
	public $function_pointer;
	public $is_block = false;
	
	public function build_source ($indent = 0) {
		if ($this->is_block) {
			$this->source = indent_string($indent).$this->name.": ".OPAQUE_BLOCK_TYPE.";\n";
		} else {
			$this->function_pointer->deprecated_macro = $this->deprecated_macro;
			$this->function_pointer->build_source($indent);
			$this->source = $this->function_pointer->source."\n";
		}
	}
}

/**
* Field parser contains base methods for the struct and instance variable parser
*/
class HeaderFieldParser extends HeaderParserModule {
	
	private $pattern_field_generic = array(	"id" => 1, 
																					"scope" => SCOPE_FIELD, 
																					"pattern" => "/([^{\n\s,:]+)\s+([a-zA-Z0-9_,*\s<>]+)\s*(.*?)(;|})/i",
																					"break" => "/^\s*\}/",
																					);
																					
	private $pattern_field_callback = array(	"id" => 2, 
																						"scope" => SCOPE_FIELD,
																						"pattern" => "/(.*?)\s*\(\s*([*^])\s*(\w+)?\s*\)\s*\(([^)]*)\)\s*;/i",
																						"break" => "/^\s*\}/",
																						);
																	
	public function accept_scope (Scope $scope) {
		
		// fields only accept structs and ivar blocks but the macro module will 
		// apply fields scopes so we need to prevent them from processing here
		//return $scope->conforms_to(array(SCOPE_IVAR_BLOCK, SCOPE_RECORD, SCOPE_MACRO));		
		return ($scope->conforms_to(array(SCOPE_IVAR_BLOCK, SCOPE_RECORD)) || ($scope->is_within(array(SCOPE_IVAR_BLOCK, SCOPE_RECORD))));
	}
	
	
	private function process_callback_field (Scope $scope) {
		$field = new CallbackSymbol($this->header);
		$field->name = global_namespace_protect_keyword($scope->results[3]);
		$field->contents = str_remove_lines($scope->contents);
		
		if ($scope->results[2] == "^") {
			$field->is_block = true;
		} else {
			$field->function_pointer = HeaderFunctionParser::build_function_pointer_symbol($this->header, $scope->results[1], $scope->results[3], $scope->results[4], FUNCTION_SOURCE_TYPE_FIELD);
		}
		
		return $field;
	}
		
	private function process_generic_field (Scope $scope) {		
		$field = new FieldSymbol($this->header);
		$contents = trim($scope->contents, ";");
			
		extract_name_type_list($contents, $list, $type);
		
		foreach ($list as $key => $value) {
			format_name_type_pair($value, $type, $this->header, true);
			$list[$key] = reserved_namespace_protect_keyword($value);
		}
		
		$field->type = $type;
		$field->name = implode(", ", $list);
		$field->names = $list;
		
		$field->contents = $scope->contents;
		
		// bit field
		if (preg_match("/:\s*(\d+)\s*$/", $scope->results[3], $captures)) {
			$field->bit_field = (int)$captures[1];
		}
		
		// inline array
		// NOTE: if the field is a list of value with arrays 
		// like valueA[1], valueB[10]
		// this will fail! fix this in the future...
		if (find_array_elements($scope->results[3], $elements)) {
			$field->array_elements = $elements;
			foreach ($field->names as $key => $name) {
				$field->names[$key] = replace_array_brackets($name);
			}
		}
		
		// we have a mal-formed field with no type
		// reject the type and issue a note
		if (!$field->type) {
			ErrorReporting::errors()->add_note("The type $field->name has a mal-formed type and will be ignored.");
			return null;
		}
		
		// add the type as being used
		SymbolTable::table()->add_used_type($field->type, 0);
		
		return $field;
	}
	
	public function process_scope ($id, Scope $scope) {
		// print("+ got field $id in ".$scope->get_super_scope()->name."\n");
		// print($scope->contents."\n");
		// print_r($scope->results);
		
		switch ($id) {
			
			case 1: {		
				if ($field = $this->process_generic_field($scope)) {
					$field->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$scope->set_symbol($field);
				}
				break;
			}
			
			case 2: {		
				if ($field = $this->process_callback_field($scope)) {
					$field->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
					$scope->set_symbol($field);
				}
				break;
			}
			
		}
	}
	
	
	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_field_callback);
		$this->add_pattern($this->pattern_field_generic);
	}		
	
					
}
		

?>
<?php

require_once("parser_module.php");
require_once("parser_header.php");
require_once("parser_function.php");
require_once("language_utilities.php");

class StructSymbol extends Symbol {
	public $typedef = false;				// record was declared a typedef
	public $const = false;					// record is a constant (not used)
	public $inline = false;					// record is an inline field
	public $scope = null;						// record scope which contains fields/sub scopes
	public $bit_packed = false;			// record has bit aligned fields so must be packed
	public $union = false;					// record is a union
	public $array_elements = null;	// record is an array field
	
	private $bit_record_count = 0;
	
	public function add_pointer ($name) {
		
		// inline struct can't have pointers
		if ($this->inline) return;
		
		$symbol = TypedefSymbol::make_pointer($name.POINTER_SUFFIX, $this->name, $this->header);
		$symbol->scope = $this->scope;
		
		if ($this->add_dependent($symbol)) {
			SymbolTable::table()->add_implicit_pointer($symbol->name);
			SymbolTable::table()->add_symbol($symbol);
		} else {
			$symbol->free();
		}
	}
	
	public function add_alias ($name) {
		
		// inline struct can't have aliases, but the name after the struct declration
		// is the field name (the name before it is the type name, which we don't care
		// about)
		if ($this->inline) {
			$this->name = $name;
			return;
		}
			
		$symbol = TypedefSymbol::make_simple($name, $this->name, $this->header);
		$symbol->scope = $this->scope;
		
		if ($this->add_dependent($symbol)) {
			SymbolTable::table()->add_symbol($symbol);
			$this->add_pointer($name);
		} else {
			$symbol->free();
		}
		
	}
	
	public function get_section () {
		return HEADER_SECTION_TYPES;
	}
	
	public function get_base_indent () {
		return 1;
	}
	
	public function always_print_header () {
		return true;
	}
	
	public function get_block_header () {
		return "type";
	}
	
	// if the struct has defined a single alias use
	// this as the preferred name, which will be used
	// in the framework
	public function get_preferred_alias_name () {
		if ($this->aliases && count($this->aliases) == 1) {
			return $this->aliases[0];
		} else {
			return $this->name;
		}
	}
	
	public function get_fields () {
		// ??? we want to recurse into macros but not within sub-structs
		// how can we do this??
		return $this->scope->find_sub_scopes(SCOPE_FIELD, true, true);
	}
		
	public function finalize () {
				
		$fields = $this->get_fields();
		foreach ($fields as $field) {
			
			// add dependencies for the field type
			$this->add_dependency($field->type);
			
			// protect field names
			foreach ($field->names as $key => $value) {
				if ($this->namespace->is_protected($field->names[$key])) {
					protect_keyword($field->names[$key]);
					continue;
				}
				$this->namespace->add_keyword($field->names[$key]);
			}
			
		}
	}
	
	public function build_source ($indent = 0) {
						
		// begin record
		if (!$this->bit_packed) {
			if ($this->inline) {
				// array of record
				if ($this->array_elements) {
					$elements = build_array_elements_source($this->array_elements, RECORD_KEYWORD);
					$source .= indent_string($indent).$this->name.": $elements\n";
				} else {
					// plain field
					$source .= indent_string($indent).$this->name.": ".RECORD_KEYWORD."\n";
				}
			} else {
				$source .= indent_string($indent).$this->name." = ".RECORD_KEYWORD."\n";
			}
		} else {
			if ($this->inline) {
				$source .= indent_string($indent).$this->name.": ".BIT_PACKED_RECORD_KEYWORD."\n";
			} else {
				$source .= indent_string($indent).$this->name." = ".BIT_PACKED_RECORD_KEYWORD."\n";
			}
			$source .= indent_string($indent + 1)."case byte of\n";
			$source .= indent_string($indent + 2)."0: (_anonBitField_".$this->name.$this->bit_record_count.": cuint);\n";
			$source .= indent_string($indent + 2)."1: (\n";
			
			$this->bit_record_count ++;
		}
		
		// unions
		if ($this->union) {
			$source .= indent_string($indent + 1)."case longint of\n";
			$scopes = $this->scope->get_sub_scopes();
			$index = 0;
			foreach ($scopes as $scope) {
				
				if ($scope->name == SCOPE_RECORD) {
					$source .= indent_string($indent + 2)."$index: (\n".$scope->get_symbol_source($indent + 3);
					$source .= indent_string($indent + 2).");\n";
				}
				
				if ($scope->name == SCOPE_MACRO) {
					$source .= indent_string($indent + 2).$scope->symbol->get_start_line()."\n";
					$source .= indent_string($indent + 2)."$index: (\n";
					$source .= $scope->symbol->scope->get_sub_scope_symbol_source($indent + 3);
					$source .= indent_string($indent + 2).");\n";
					$source .= indent_string($indent + 2).$scope->symbol->get_end_line()."\n";
				}
				
				if ($scope->name == SCOPE_FIELD) {
					$source .= indent_string($indent + 2)."$index: (".trim($scope->get_symbol_source(), "\n;").");\n";
				}
				
				$index++;
			}
		} else {	
			// basic struct
			if (!$this->bit_packed) {
				$source .= $this->scope->get_sub_scope_symbol_source($indent + 1);
			} else {
				$source .= $this->scope->get_sub_scope_symbol_source($indent + 3);
			}
		}
		if (!$this->bit_packed) {
			$source .= indent_string($indent)."end;\n";
		} else {
			$source .= indent_string($indent + 2).");\n";
			$source .= indent_string($indent + 1)."end;\n";
		}
				
		$this->source = $source;
	}
	
}

/**
* Struct parser
*/
class HeaderStructParser extends HeaderParserModule {
		
	// Patterns
	private $pattern_struct = array(	"id" => 1, 
																		"scope" => SCOPE_RECORD, 
																		"start" => "/(typedef)*\s*(const)*\s*(struct|union)+\s+(\w*)\s*\{/i",
																		"end" => "/\}(.*?);/is",
																		"modules" => array(MODULE_MACRO, MODULE_FIELD, MODULE_STRUCT),
																		// fields can consume the struct end so terminate from start
																		PATTERN_KEY_TERMINATE_FROM_START => true,
																		);
				
	private function process_post_declaration (&$struct, $post_declaration) {
		
		$post_declaration = replace_unused_keywords($post_declaration);
		$post_declaration = trim($post_declaration);
		
		// after removing keyword or trimming white space the
		// string is empty so bail
		if (!$post_declaration) return;
		
		// explode into fields array
		$post_declaration = preg_replace("!\s+!", "", $post_declaration);
		$fields = explode(",", $post_declaration);
		
		// the struct name has not been defined yet, attempt to get it from post_declaration information
		// by using the first field from the post declaration array
		if (!$struct->name) {
			$struct->name = $fields[0];
			
			// remove the first field since it's being used as the name
			array_shift($fields);
		}
		
		// find array elements of the struct
		// NOTE: currently this only works for structs with single fields
		if (find_array_elements($struct->name, $elements)) {
			$struct->array_elements = $elements;
			$struct->name = replace_array_brackets($struct->name);
		}
				
		// iterate the fields and add additional typedefs to the array
		foreach ($fields as $field) {
				
			// the alias name is the same as the struct
			// which is implicit in pascal and will cause
			// duplicate identifiers so ignore it
			if ($field == $struct->name) continue;		
					
			// convert type to a pointer
			if (preg_match("!^\*!", $field)) {
				$field = preg_replace("!^\*!", "", $field);
				
				// the struct always gets default pointer
				// so don't add a duplicate name also
				if ($field == $struct->name) continue;		
				
				$struct->add_pointer($field);
			} else {
				
				// if the alias captured a typedef keyword strip it out here
				$field = preg_replace("/^typedef/i", "", $field);
					
				$struct->add_alias($field);
			}
		}
	}	
	
	private function process_struct (Scope $scope) {				
				
		$struct = new StructSymbol($this->header);
		$struct->name = $scope->start_results[4];
		$struct->scope = $scope;

		// the identifier has already been declared
		// ??? we're having conflicts with typedefs so we need to think
		// about this more until the order can be understood better
		// this step should probably happend during finalizing since typedefs
		// perform important tasks there which affect how structs will behave
		//if (!$struct->verify_scope_availability($scope->get_super_scope())) return null;		

		// note the struct is actually a union
		if (strtolower($scope->start_results[3]) == "union") $struct->union = true;
		
		// the scope was declared in an instance variable block or struct
		// so it needs to be a inline field
		if ($scope->is_within(array(SCOPE_IVAR_BLOCK, SCOPE_RECORD))) $struct->inline = true;
		
		// determine if the record contains bit aligned fields and must be packed
		$fields = $scope->find_sub_scopes(SCOPE_FIELD, true, true);
		foreach ($fields as $field) {
			if ($field->bit_field) {
				$struct->bit_packed = true;
				break;
			}
		}
		
		if ($scope->start_results[1]) $struct->typedef = true;
		if ($scope->start_results[2]) $struct->const = true;
						
		// parse post declaration text from end results
		if ($scope->end_results[1]) $this->process_post_declaration($struct, $scope->end_results[1]);
		
		// assign anonymous name since 
		// struct without names are valid in records
		if (!$struct->name) {
			$struct->name = "_anonStruct_".$this->header->get_actual_name().$this->header->anonymous_struct_count;
			$this->header->anonymous_struct_count += 1;
		}
				
		// add a default pointer for the preferred alias name
		// which will be used when referencing the type by pointer
	 	$struct->add_pointer($struct->get_preferred_alias_name());
		
		// add to symbol table
		if (!$struct->inline) $this->symbols->add_symbol($struct);
		
		return $struct;
	}
	
	public function process_scope ($id, Scope $scope) {
		// print("✅ got struct ($id) at $scope->start/$scope->end\n");
		// print($scope->contents."\n");
		// print_r($scope->results);
		
		switch ($id) {
			case 1: {
				if ($struct = $this->process_struct($scope)) {
					$scope->set_symbol($struct);
				}
				break;
			}
		}
	}
					
	public function init () {
		parent::init();
		
		$this->add_pattern($this->pattern_struct);
	}		
	
}
		

?>
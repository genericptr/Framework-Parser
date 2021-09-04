<?php

require_once("parser_header.php");
require_once("parser_method.php");
require_once("symbol.php");

/**
 * Method symbol
 */
class PropertySymbol extends MethodSymbol {
	public $type;
	public $remote_messaging_modifier;
	public $garbage_collector_hint;
	public $list = array();
	public $function = null;	// FunctionSymbol for properties with inline functions pointers
	public $callback = null;  // TypedefSymbol for properties which created implicit callback types
	public $setter = null;
	public $getter = null;

	private $attributes = array();

	public function set_getter ($name) {
		$this->getter = $name;
	}
	
	public function set_setter ($name) {
		$this->setter = $name;
	}
	
	public function add_attribute ($attribute) {
		$this->attributes[] = strtolower($attribute);
	}
	
	public function is_read_only () {
		return in_array("readonly", $this->attributes);
	}

	public function is_class () {
		return in_array("class", $this->attributes);
	}
	
	public function get_getter () {
		if ($this->getter) {
			return $this->getter;
		} else {
			return $this->name;
		}
	}
	
	public function get_setter () {
		if ($this->setter) {
			return $this->setter;
		} else {
			return "set".ucwords($this->name);
		}
	}
	
	public function added_to_class (ClassSymbol $class) {
		// if the property has callback typedef then notify that we are
		// being added to a class so we can clean generic parameters
		if ($this->callback) {
			$this->callback->added_to_class($class);
		}
	}
	
	public function get_accessor_methods(ClassSymbol $class) {
		$methods = array();

		// getter
		$getter_method = new MethodSymbol($this->header);
		$getter_method->parameters = null;
		$getter_method->return_type = $this->type;
		$getter_method->name = $this->get_getter();
		$getter_method->deprecated_macro = $this->deprecated_macro;
		$getter_method->is_class = $this->is_class();
		HeaderMethodParser::build_selector($getter_method);
		$class->namespace->protect_keyword($getter_method->name);

		$methods[] = $getter_method;

		// setter
		if (!$this->is_read_only()) {
			$setter_method = new MethodSymbol($this->header);
			$setter_method->name = $this->get_setter();
			$setter_method->is_class = $this->is_class();
			$setter_method->is_procedure = true;
			$setter_method->parameters = array(
				new ParameterPair('newValue', $this->type, $setter_method->name)
			);
			$setter_method->return_type = PROCEDURE_RETURN_TYPE;
			$setter_method->deprecated_macro = $this->deprecated_macro;

			HeaderMethodParser::build_selector($setter_method);
			$class->namespace->protect_keyword($setter_method->name);

			$methods[] = $setter_method;
		}

		return $methods;
	}

	public function finalize (ClassSymbol $class) {
		
		// properties only need to be protected in the current class namespace
		// because the selectors are always the same even for duplicates in
		// super classes, which are permitted to overwrite in external objcclass's
		
		// replace generic paremeter in property type
		if ($class->has_generic_params()) {
			$this->type = $class->replace_generic_params($this->type);
		}

		// protect getter
		$getter = $this->get_getter();

		$class->namespace->protect_keyword($getter);
		$class->namespace->add_keyword($getter);
		global_namespace_protect_keyword($getter);
		$this->getter = $getter;

		// protect setter
		if (!$this->is_read_only()) {
			$setter = $this->get_setter();
			$class->namespace->protect_keyword($setter);
			$class->namespace->add_keyword($setter);
			global_namespace_protect_keyword($setter);
			$this->setter = $setter;
		}
		
		// finalize depedents
		foreach ($this->dependents as $property) {
			$property->finalize($class);
		}

	}
	
	public function build_source ($indent = 0) {
		$source = '';

		// add setter
		if (!$this->is_read_only()) {
			$name = $this->get_setter();
			$message = trim($name, KEYWORD_PROTECTION_SUFFIX);
			$type = $this->type;
			$class_prefix = $this->is_class() ? "class " : "";
			$source = indent_string($indent).$class_prefix."procedure $name(newValue: $type); message '$message:';";
			if ($this->deprecated_macro) $source .= " ".$this->deprecated_macro;
			$source .= "\n";
		}

		// add getter
		$name = $this->get_getter();
		$message = trim($name, KEYWORD_PROTECTION_SUFFIX);
		$type = $this->type;
		$class_prefix = $this->is_class() ? "class " : "";
		$source .= indent_string($indent).$class_prefix."function $name: $type; message '$message';";
		if ($this->deprecated_macro) $source .= " ".$this->deprecated_macro;
		
		// add dependents
		if (count($this->dependents) > 0) {
			$source .= "\n";
			$source .= $this->get_dependent_source($indent);
			$source = trim($source, "\n");
		}
		
		$this->source = $source."\n";
	}
		
}


/**
* Property parser
*/
class HeaderPropertyParser extends HeaderParserModule {
		
	private $pattern_property = array(	"id" => 1, 
																			"scope" => SCOPE_PROPERTY, 
																			"pattern" => "/@property\s*(\((.*?)\))*\s*(.*?);/is",
																			);
	
	// the property is a function pointer with potential
	// nested function pointer parameters
	//
	// NSUInteger (*hashFunction)(const void *item, NSUInteger (*size)(const void *item))
	// void (^terminationHandler)(NSTask *)
	private $pregex_function_pointer = "/(.*?)\s*\(\s*([\^*]+)\s*(\w+)\s*\)\s*\((.*)\)/";
	
	private function process_property_attributes (PropertySymbol $property, $attributes) {
		$parts = explode(",", $attributes);
		
		foreach ($parts as $part) {
			$part = trim($part);

			// split by = to get assigned accessor names
			if (preg_match("/(\w+)\s*=\s*(\w+)/", $part, $captures)) {

				if ($captures[1] == strtolower("getter")) $property->set_getter($captures[2]);
				if ($captures[1] == strtolower("setter")) $property->set_setter($captures[2]);
				
			} else {
				// add attribute to property for later use
				$property->add_attribute($part);
			}
		}
	}
	
	private function process_property_name_type (PropertySymbol $property, $contents) {
		$contents = replace_remote_messaging_modifiers($contents, $property->remote_messaging_modifier);	
		$contents = replace_garbage_collector_hints($contents, $property->garbage_collector_hint);

		// separate conforms to hints from property name so the identifiers don't
		// get merged in extract_name_type_list
		// example: id<AVPlayerItemOutputPullDelegate>delegate;
		$contents = preg_replace("/(\w+\s*<[^>]+>)(\w+)/", " $1 $2" , $contents);
		
		// extract property name list and type from contents
		extract_name_type_list($contents, $property->list, $property->type);
		
		// use the first name as the base then remove it
		// so it's not added again
		$property->name = $property->list[0];
		array_shift($property->list);
		format_name_type_pair($property->name, $property->type, $this->header);
	}
	
	function process_scope ($id, Scope $scope) {
		// print("✅ got property ($id) in ".$scope->get_super_scope()->name."\n");
		// print($scope->contents."\n");
		// print_r($scope->results);

		parent::process_scope($id, $scope);
		
		$property = new PropertySymbol($this->header);
		
		$attributes = $scope->results[2];
		$name_and_type = $scope->results[3];
		$name_and_type = clean_objc_generics($name_and_type);

		$property->deprecated_macro = $this->header->find_availability_macro($scope->start, $scope->end);
		
		if (preg_match($this->pregex_function_pointer, $name_and_type, $captures)) {
			
			// get the name and parse attributes
			$property->name = $captures[3];
			$property->selector = $captures[3];
			$this->process_property_attributes($property, $name_and_type);

			if ($captures[2] == "*") {
				// build the function pointer from inline pointer type
				$function_pointer = HeaderFunctionParser::build_function_pointer_symbol($this->header, $captures[1], NO_FUNCTION_NAME, $captures[4], FUNCTION_SOURCE_TYPE_TYPE);
				$function_pointer->build_source(0);

				// add the function pointer as a callback
				$callback_name = ucwords($property->name);
				$property->function = &$function_pointer;
				$property->type = HeaderFunctionParser::add_callback($this->header, $callback_name, $function_pointer, $property->callback);
			}
			
			// the function pointer is a cblock
			if ($captures[2] == "^") {
				$function_pointer = HeaderFunctionParser::build_function_pointer_symbol($this->header, $captures[1], NO_FUNCTION_NAME, $captures[4], FUNCTION_SOURCE_TYPE_CBLOCK);

				$function_pointer->build_source(0);
				$property->function = &$function_pointer;
				$property->type = HeaderFunctionParser::add_callback($this->header, $property->name, $function_pointer, $property->callback);
			}
			
			// print some debug info
			if (get_verbosity() > 1) {
				$debug = array("name" => $property->name,
											 "selector" => $property->selector,
											 "type" => $property->type,
											 "return-type" => $captures[1],
											 "parameters" => $captures[4]
											);
				print_r($debug);
			}

		} else {
			$this->process_property_attributes($property, $attributes);
			$this->process_property_name_type($property, $name_and_type);
		}
		
		// add additional properties from names list
		// as dependents of the base property
		foreach ($property->list as $name) {
			$dependent = clone $property;
			$dependent->name = $name;
			$dependent->dependents = array();
			
			format_name_type_pair($dependent->name, $dependent->type, $this->header);

			$property->add_dependent($dependent);
		}
		
		// add the base property to the scope
		$scope->set_symbol($property);
	}
	
	public function init () {
		parent::init();

		$this->name = "property";

		$this->add_pattern($this->pattern_property);
	}		

}
		

?>
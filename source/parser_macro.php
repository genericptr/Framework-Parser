<?php

require_once("parser_header.php");
require_once("language_utilities.php");

// pascal supported macros directives
define("MACRO_KIND_IF", "if");
define("MACRO_KIND_IF_DEF", "ifdef");
define("MACRO_KIND_IF_NOT_DEF", "ifndef");
define("MACRO_KIND_ELSE", "else");
define("MACRO_KIND_ELSE_IF", "elseif");

// macro source building options
define("MACRO_BUILD_SOURCE_NOT_EMPTY", "true");
define("MACRO_BUILD_SOURCE_EMPTY", "false");
define("MACRO_BUILD_SOURCE_PRINT_HEADERS", "true");
define("MACRO_BUILD_SOURCE_DONT_PRINT_HEADERS", "false");

class MacroSymbol extends Symbol {
	public $pair = false;
	public $kind;
	
	public function get_start_line () {
		return "{\$$this->kind $this->name}";
	}
	
	public function get_end_line () {
		return "{\$endif}";
	}
	
	// ignore types can include macros but we always want to
	// print these so override is_printable() and return true
	public function is_printable () {
		return true;
	}
	
	// returns true if the macro is within a valid scope
	// which checks for {$else} macros if it is within
	// a valid MacroSymbol scope or always returns true
	// for paired macros.
	//
	// this method is basically a hack or prevent against 
	// a bug in NSURL.h but unbalanced macros could happen
	// elsewhere given the way they can be placed across scopes
	private function is_scope_valid () {
		if (!$this->pair) {
			if ($super = $this->scope->get_super_scope()->find_super_scope(SCOPE_MACRO)) {
				return ($super->symbol != null);
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	public function get_line () {
		if (($this->kind == MACRO_KIND_ELSE) || (!$this->name)) {
			return "{\$$this->kind}";
		} else {
			return "{\$$this->kind $this->name}";
		}
	}
	
	// returns true if the macro contains non-macro
	// printable symbols
	public function contains_printable_symbols () {
		$symbols = $this->scope->get_sub_scopes_flat(true);
		foreach ($symbols as $symbol) {
			if ((!is_a($symbol, "MacroSymbol")) && ($symbol->is_printable())) return true;
		}
	}
			
	public function build_source ($indent) {
		
		if ($this->pair) {
			$source .= indent_string($indent).$this->get_start_line()."\n";
			$source .= $this->scope->get_sub_scope_symbol_source($indent);
			$source .= indent_string($indent).$this->get_end_line()."\n";
		} else {
			if ($this->is_scope_valid()) $source .= indent_string($indent).$this->get_line()."\n";
		}
		
		$this->source = $source;
	}
	
	// builds the macro source filtering only the symbol types
	// specified by $filter (array of single type)
	public function build_source_and_filter ($indent, $filter, $not_empty = MACRO_BUILD_SOURCE_NOT_EMPTY, $print_headers = MACRO_BUILD_SOURCE_DONT_PRINT_HEADERS) {
		
		if ($this->pair) {
			$sub_source = $this->scope->get_sub_scope_symbol_source($indent, $filter, $print_headers);
			if ((($sub_source) && ($not_empty)) || (!$not_empty)) {
				$source .= indent_string($indent).$this->get_start_line()."\n";
				$source .= $sub_source;
				$source .= indent_string($indent).$this->get_end_line()."\n";
			}
		} else {
			if ($this->is_scope_valid()) $source .= indent_string($indent).$this->get_line()."\n";
		}
		
		$this->source = $source;
	}
	
}

define("MACRO_TOKEN_WORD", 1);
define("MACRO_TOKEN_NUMBER", 2);
define("MACRO_TOKEN_SYMBOL", 3);
define("MACRO_TOKEN_COMPARATOR", 4);
define("MACRO_TOKEN_OPERATOR", 5);
define("MACRO_TOKEN_WHITE_SPACE", 6);
define("MACRO_TOKEN_HEXADECIMAL", 7);
define("MACRO_TOKEN_GROUP", 8);
define("MACRO_TOKEN_KEYWORD", 9);
define("MACRO_TOKEN_UNKNOWN", -1);

class HeaderMacroParser extends HeaderParserModule {
		
	// Patterns
	private $pattern_macro_ifdef = array(	"id" => 1, 
																				"scope" => SCOPE_MACRO, 
																				"start" => "/#\s*\b(ifndef|ifdef|if)\b\s*([^\n]+)/i",
																				"end" => "/#\s*endif/i",
																				"modules" => array(MODULE_SUPER_SCOPE),
																				);

	private $pattern_macro_elsif = array(	"id" => 2, 
																				"scope" => SCOPE_MACRO, 
																				"pattern" => "/#\s*\b(elif)\b\s*([^\n]+)/i",
																				);

	private $pattern_macro_else = array(	"id" => 3, 
																				"scope" => SCOPE_MACRO, 
																				"pattern" => "/#\s*\b(else)\b/i",
																				);
	
	private $token_operators = array("||", "&&", "!");
	private $token_comparators = array("<", ">", "<=", ">=", "=", "==");
	private $token_keywords = array("defined");
	
	private function get_token_for_string ($string) {
		
		// operator
		if (in_array($string, $this->token_operators)) {
			return MACRO_TOKEN_OPERATOR;
		}

		// comparator
		if (in_array($string, $this->token_comparators)) {
			return MACRO_TOKEN_COMPARATOR;
		}
		
		// keywords
		if (in_array($string, $this->token_keywords)) {
			return MACRO_TOKEN_KEYWORD;
		}

		// group
		if (preg_match("/[()]+/", $string)) {
			return MACRO_TOKEN_GROUP;
		}
		
		// number
		if (preg_match("/^[-]*[0-9]+(\.[0-9]+)*(U|UL|LL|L)*$/", $string)) {
			return MACRO_TOKEN_NUMBER;
		}

		// hexadecimal
		if (preg_match("/^0x\w+(U|UL|LL|L)*$/", $string)) {
			return MACRO_TOKEN_HEXADECIMAL;
		}
		
		// word
		if (preg_match("/[a-zA-Z_]+/", $string)) {
			return MACRO_TOKEN_WORD;
		}		
				
		return null;
	}
		
	private function get_character_token ($c) {
		
		if (preg_match("/\w+/", $c)) {
			return MACRO_TOKEN_WORD;
		} elseif (preg_match("/\s+/", $c)) {
			return MACRO_TOKEN_WHITE_SPACE;
		} elseif (preg_match("/[<>=!]+/", $c)) {
			return MACRO_TOKEN_COMPARATOR;
		}	elseif (preg_match("/[|&]+/", $c)) {
			return MACRO_TOKEN_OPERATOR;
		} elseif (preg_match("/[()]+/", $c)) {
			return MACRO_TOKEN_GROUP;
		} else {
			return MACRO_TOKEN_SYMBOL;
		}
	}
		
	private function tokenize_macro ($value) {
		$word = null;
		$class = null;
		$tokens = array();
		
		// wrap expressions in parenthesis
		// NOTE: this makes for some messy code and could be done
		// much cleaner by wrapping comparisons directly in the tokens
		$value = preg_replace("/(\w+\s*[<>=]+\s*\w+)/", "($1)", $value);
		
		// remove function macros that are not defined
		// in $this->token_keywords
		if (preg_match_all("/(\w+)\((.*?)\)/", $value, $captures)) {
			foreach ($captures[1] as $key => $function) {
				$parameters = $captures[2][$key];
				if (!in_array($function, $this->token_keywords)) {
					$value = preg_replace("/($function)\s*\($parameters\)/", "$1", $value);
				}
			}
		}
		
		// walk the string for tokens
		for ($i=0; $i < strlen($value); $i++) { 
			$c = $value[$i];
			
			// white space
			if (ctype_space($c)) {
				
				// ignore white space
				//$tokens[] = array("token" => MACRO_TOKEN_WHITE_SPACE);
				
				// get token for word
				if ($token = $this->get_token_for_string($word)) {
					$tokens[] = array("token" => $token, "value" => $word);
				}
				
				$word = null;
				continue;
			}
			
			// check if character class changed
			$next = $this->get_character_token($c);
			if ($next != $class) {
				if ($class) {
					
					// get token for word
					if ($token = $this->get_token_for_string($word)) {
						$tokens[] = array("token" => $token, "value" => $word);
					}
					
					// reset word
					$word = null;
				}
				
				$class = $next;
			}
			
			// append word
			$word .= $c;
		}
		
		// get final token
		if ($word) {
			if ($token = $this->get_token_for_string($word)) {
				$tokens[] = array("token" => $token, "value" => $word);
			}
			$word = null;
		}
		
		//print_r($tokens);
		return $tokens;
	}
	
	private function process_tokens ($tokens) {
		
		// no tokens were found, export a null macro
		// which is never defined
		if (!$tokens) {
			return "defined(__NULL_MACRO__)";
		}
		
		for ($i=0; $i < count($tokens); $i++) { 
			$token = $tokens[$i];
			
			if ($token["token"] == MACRO_TOKEN_GROUP) $macro .= $token["value"];
			
			if ($token["token"] == MACRO_TOKEN_WORD) {
				
				// determine if the word is part of comparator
				// and wrap in defined() or not
				for ($ii=$i-1; $ii < count($tokens); $ii++) { 
					if ($tokens[$ii]["token"] == MACRO_TOKEN_OPERATOR) break;
					if ($tokens[$ii]["token"] == MACRO_TOKEN_COMPARATOR) {
						$comparator = true;
						break;
					}
				}
				
				if ($comparator) {
					$macro .= $token["value"];
					$comparator = false;
				} else {
					$macro .= "defined(".$token["value"].")";
				}	
			}
			
			if ($token["token"] == MACRO_TOKEN_NUMBER) {
				$macro .= convert_integer_value($token["value"]);
			}

			if ($token["token"] == MACRO_TOKEN_HEXADECIMAL) {
				$macro .= convert_integer_value($token["value"]);
			}
			
			if ($token["token"] == MACRO_TOKEN_COMPARATOR) {
				if ($token["value"] == "!=") $token["value"] = "<>";
				if ($token["value"] == "==") $token["value"] = "=";
				
				$macro .= " ".$token["value"]." ";
			}
			
			if ($token["token"] == MACRO_TOKEN_OPERATOR) {
				if ($token["value"] == "||") $token["value"] = "or";
				if ($token["value"] == "&&") $token["value"] = "and";
				if ($token["value"] == "!") $token["value"] = "not";
				
				$macro .= " ".$token["value"]." ";
			}
			
		}
		
		// strip spaces around parenthesis
		$macro = preg_replace("/\(\s*(\w+)/", "($1", $macro);
		$macro = preg_replace("/(\w+)\s*\)/", "$1)", $macro);
		
		// make only space between words
		$macro = preg_replace("/(\w+)\s{2}(\w+)/", "$1 $2", $macro);
				
		//print("$macro\n");
		return $macro;
	}
	
	private function process_macro ($name, $kind, $pair, Scope $scope) {
		$macro = new MacroSymbol($this->header);
		$macro->name = $name;
		$macro->kind = strtolower($kind);
		$macro->pair = $pair;

		// convert c macros to pascal
		if ($macro->kind == "elif") $macro->kind = MACRO_KIND_ELSE_IF;
		
		// tokenize #if/#elif macros with complex syntax
		if (($macro->kind == MACRO_KIND_IF) || ($macro->kind == MACRO_KIND_ELSE_IF)) {
			$tokens = $this->tokenize_macro($name);
			$macro->name = $this->process_tokens($tokens);
		}
		
		return $macro;
	}
	
				
	function process_scope ($id, Scope $scope) {
		//print("+ got macro $id in ".$scope->get_super_scope()->name."\n");
		//print($scope->contents."\n");
						
		switch ($id) {
			
			case 1: {		
				if ($macro = $this->process_macro($scope->start_results[2], $scope->start_results[1], true, $scope)) {
					$scope->set_symbol($macro);
				}
				break;
			}
			
			case 2: {		
				if ($macro = $this->process_macro($scope->results[2], $scope->results[1], false, $scope)) {
					$scope->set_symbol($macro);
				}
				break;
			}
			
			case 3: {		
				if ($macro = $this->process_macro(null, $scope->results[1], false, $scope)) {
					$macro->name = MACRO_KIND_ELSE;
					$macro->kind = MACRO_KIND_ELSE;
					$scope->set_symbol($macro);
					
					// for the purpose of finding unique identifiers by scope
					// we need to the reload the super scope uuid so that
					// subsequent symbols in the macro appear to be in a
					// different scope
					$scope->get_super_scope()->reload_uuid();
				}
				break;
			}
			
		}
	}
				
	public function init () {
		parent::init();

		$this->add_pattern($this->pattern_macro_ifdef);
		$this->add_pattern($this->pattern_macro_elsif);
		$this->add_pattern($this->pattern_macro_else);
	}		
	
}
		

?>
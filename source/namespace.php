<?php

require_once("language_utilities.php");

class KeywordNamespace {
	private $keywords = array();
	
	public function add_keyword ($keyword) {
		if (!in_array($keyword, $this->keywords)) $this->keywords[] = strtolower($keyword);
	}
	
	public function remove_keyword ($keyword) {
		foreach ($this->keywords as $key => $value) {
			if (strcasecmp($value, $keyword) == 0) {
				unset($this->keywords[$key]);
			}
		}
	}
	
	public function protect_keyword (&$keyword) {
		while ($this->is_protected($keyword)) protect_keyword($keyword);
		return $keyword;
	}
	
	public function protect_keyword_return_word ($keyword, &$io_word) {
		$io_word = $keyword;
		while ($this->is_protected($io_word)) protect_keyword($io_word);
		return $io_word;
	}
	
	public function is_protected ($keyword) {
		if (in_array(strtolower($keyword), $this->keywords)) return true;
	}
	
	public function print_keywords () {
		foreach ($this->keywords as $keyword) {
			print("  $keyword\n");
		}
	}
	
	function __construct() {
	}
	
}

?>
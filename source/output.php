<?php

require_once("errors.php");
require_once("syntax_color.php");

/**
* Class for managing output printing
*/
class Output {
	
	/**
	 * Private
	 */
	
	private $handle;
	private $show = false;
	private $last_line;
	private $syntax_coloring = true;
	private $full_text;

	public function show($line) {
		if ($this->syntax_coloring) {
			$this->full_text .= $line;
			return;
		}
		print($line);
	}

	// writes a single line to output with indentation
	// $indent = integer of how many levels of indentation to prepend to $string
	public function writeln ($indent = 0, $string = "", $coalesce_white_space = false) {
		$string = rtrim($string, "\n");
		$indent_string = "";

		for ($i=0; $i < $indent; $i++) { 
			$indent_string .= "  ";
		}
		
		$line = "$indent_string$string\n";
		
		// if the last line was white space also ignore 
		// therefore "coalescing" multiple white space lines
		if ($coalesce_white_space) {
			if (ctype_space($line) && ctype_space($this->last_line)) return;
		}
		
		// write to file handle
		if (($this->handle) && (!$this->show)) fwrite($this->handle, $line);
		
		// print to stdout
		if ($this->show) $this->show($line);
		
		$this->last_line = $line;
	}
	
	// close the output file
	public function close () {
		if ($this->show && $this->syntax_coloring) {
			$syntax = new SyntaxColor();
			$syntax->process($this->full_text);
			print("\n");
		}
		if ($this->handle) fclose($this->handle);
	}
	
	function __construct($path, $show) {
		$this->show = $show;
		
		// open the file handle to $path
		if (($path) && (!$show)) {
			if (!$this->handle = fopen($path, "w+")) {
				ErrorReporting::errors()->add_fatal("The output file couldn't be opened at \"$path\".");
			}
		}	
	}

}

?>
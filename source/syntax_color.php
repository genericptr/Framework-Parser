<?php

require_once("errors.php");

class SyntaxPattern {
	
	public $pattern;
	public $colors;

	function __construct(string $pattern, array $colors) {
		$this->pattern = $pattern;
		$this->colors = $colors;
	}
}

/*
 * Simple syntax colorizer
 */

class SyntaxColor {
	
	/*
	 * Private
	 */
	
	private $patterns;

	/*
	 * Syntax Pattenrns
	 */

	private const RESERVED_WORDS = 'absolute|and|array|asm|begin|case|const|constructor|destructor|div|do|downto|else|end|file|for|function|goto|if|implementation|in|inherited|inline|interface|label|mod|nil|not|object|of|operator|or|packed|procedure|program|record|reintroduce|repeat|self|set|shl|shr|string|then|to|type|unit|until|uses|var|while|with|as|class|dispinterface|except|exports|finalization|finally|initialization|is|library|on|out|packed|property|raise|resourcestring|threadvar';
	
	private const OBJC_KEYWORDS = 'objcclass|objcprotocol|objcategory|objcbool';
	private const CLASS_SECTIONS = 'strict|public|published|protected|private|required|optional';

	private const FUNCTION_MODIFIERS = 'external|message|cdecl|cblock';

	private const TYPES_ORDINAL = 'integer|shortint|smallint|longint|longword|int64|byte|word|cardinal|qword|boolean|bytebool|wordbool|longbool|qwordbool|char|boolean16|boolean32|boolean64|variant';
	private const TYPES_REAL = 'real|single|double|extended|comp|currency';
	private const TYPES_CHARACTER = 'string|shortstring|ansistring|widestring|unicodestring|resourcestring|ansichar|widechar|unicodechar|pchar';
	private const TYPES_OTHER = 'file|pointer|array|fixed';
	private const TYPES_OBJC = 'id|objcbool';

	/*
	 * Methods
	 */

	private function apply_range(string $text, int &$offset): void {
		/*
		 This algorithm is going to be inherently slow as it is required
		 to search all patterns first and then sort to find the first (lowest offset)
		 in order to prevent skipping over ranges of text that may contain other patterns.
		 */
		$found_match = false;
		$all_matches = null;
		foreach ($this->patterns as $pattern) {
			if (preg_match($pattern->pattern, $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
				// print("🥕 $offset\n");
				// print_r($matches);
				$all_matches[] = array('matches' => $matches, 'pattern' => $pattern);
				$found_match = true;
			}
		}

		// nothing was found so assume we've reached end of file
		if (!$found_match) {
			// print(substr($text, $offset, strlen($text) - $offset));
			$offset = PHP_INT_MAX;
			return;
		}

		// sort results by lowest offest
		usort($all_matches, function($left, $right) {
			$left_offset = $left['matches'][0][1];
			$right_offset = $right['matches'][0][1];
			if ($left_offset == $right_offset) return 0;
			return ($left_offset < $right_offset) ? -1 : 1;
		});

		// process the best match
		$matches = $all_matches[0];

		// print text up to the match
		$full_match = $matches['matches'][0];
		print(substr($text, $offset, $full_match[1] - $offset));
		

		// index 0 is the full pattern match which we always skip
		$last_offset = $full_match[1];

		for ($i=1; $i < count($matches['matches']); $i++) {
			$match = $matches['matches'][$i];
			$pattern = $matches['pattern'];

			$match_offset = $match[1];
			$match_len = strlen($match[0]);
			
			// nothing found
			if ($match_offset == -1) {
				continue;
			}

			// add text in between last match
			if ($match_offset > $last_offset) {
				print(substr($text, $last_offset, $match_offset - $last_offset));
			}
			$last_offset = $match_offset + $match_len;

			// print colored string
			$colors = is_array($pattern->colors[$i]) ? $pattern->colors[$i] : array($pattern->colors[$i]);
			print(ansi_string($colors, substr($text, $match_offset, $match_len)));
		}

		// move offset past match
		$offset = $full_match[1] + strlen($full_match[0]);

		// print trailing characters from last pattern
		if ($last_offset < $offset) {
			print(substr($text, $last_offset, $offset - $last_offset));
		}
	}

	public function process(string $text): void {
		$offset = 0;
		// print($text);
		// print("=============================================\n");
		while ($offset < strlen($text)) {
			$this->apply_range($text, $offset);
		}
	}
	
	function __construct() {

		// styles
		$styles = array(
			'keywords' => ANSI_FORE_MAGENTA,
			'types' => ANSI_FORE_CYAN,
			'methods' => ANSI_FORE_YELLOW,
			'defs' => array(ANSI_FORE_CYAN, ANSI_STYLE_BOLD),
		);

		// comments
		$patterns[] = new SyntaxPattern("/(\{[^}]+\})/", array(1 => ANSI_FORE_GREEN));

		// strings
		$patterns[] = new SyntaxPattern("/('[^']+')/", array(1 => ANSI_FORE_RED));

		// methods
		$patterns[] = new SyntaxPattern("/(class\s+)*(procedure|function)+\s+(\w+)/", array(
			1 => $styles['keywords'],
			2 => $styles['keywords'],
			3 => $styles['methods']
		));

		// objc type definitions
		$patterns[] = new SyntaxPattern("/(\w+)\s*=\s*(objcclass|objcprotocol|objcategory|objcbool)+/", array(
			1 => $styles['defs'],
			2 => $styles['keywords'],
		));

		// function pointer type
		$patterns[] = new SyntaxPattern("/(\w+)\s*=\s*(reference\s+to\s*)*(procedure|function)+/", array(
			1 => $styles['defs'],
			2 => $styles['keywords'],
			3 => $styles['keywords'],
		));

		// pascal type definitions
		$patterns[] = new SyntaxPattern("/(\w+)\s*=\s*((?:\^\s*)*\w+)\s*;/", array(
			1 => $styles['defs'],
			2 => $styles['types'],
		));

		// types
		$types = array(SyntaxColor::TYPES_ORDINAL,
									 SyntaxColor::TYPES_REAL,
									 SyntaxColor::TYPES_CHARACTER,
									 SyntaxColor::TYPES_OTHER,
									 SyntaxColor::TYPES_OBJC);
		$types = implode('|', $types);
		$patterns[] = new SyntaxPattern("/\b($types)+\b/i", array(1 => $styles['types']));

		// keywords
		$keywords = array(SyntaxColor::RESERVED_WORDS,
											SyntaxColor::OBJC_KEYWORDS,
											SyntaxColor::FUNCTION_MODIFIERS,
											SyntaxColor::CLASS_SECTIONS);
		$keywords = implode('|', $keywords);
		$patterns[] = new SyntaxPattern("/\b($keywords)+\b/i", array(1 => $styles['keywords']));

		$this->patterns = $patterns;
	}

}

?>
<?php

/**
* Singleton class for managing error reporting
*
*   Usage:
*   ErrorReporting::errors()->add_warning("some warning");
*
*/

const ANSI_FORE_BLACK           = 30;
const ANSI_FORE_RED             = 31;
const ANSI_FORE_GREEN           = 32;
const ANSI_FORE_YELLOW          = 33;
const ANSI_FORE_BLUE            = 34;
const ANSI_FORE_MAGENTA         = 35;
const ANSI_FORE_CYAN            = 36;
const ANSI_FORE_WHITE           = 37;
const ANSI_FORE_RESET           = 39;

const ANSI_BACK_BLACK           = 40;
const ANSI_BACK_RED             = 41;
const ANSI_BACK_GREEN           = 42;
const ANSI_BACK_YELLOW          = 43;
const ANSI_BACK_BLUE            = 44;
const ANSI_BACK_MAGENTA         = 45;
const ANSI_BACK_CYAN            = 46;
const ANSI_BACK_WHITE           = 47;
const ANSI_BACK_RESET           = 49;

const ANSI_STYLE_OFF            = 0;
const ANSI_STYLE_BOLD           = 1;
const ANSI_STYLE_ITALIC         = 2;
const ANSI_STYLE_UNDERLINE      = 3; 
const ANSI_STYLE_BLINK          = 4;
const ANSI_STYLE_INVERSE        = 5;
const ANSI_STYLE_HIDDEN         = 6;

class ErrorReporting {
  
  private static $instance;
  
  private $enable_messages = true;
  private $enable_notes = true;
  private $enable_warnings = true;
  private $enable_errors = true;
  private $enable_fatals = true;
  private $enable_exceptions = true;
  private $enable_colors = true;

  public const NO_PREFIX = false;

  /**
   * Class Methods
   */
  
  // returns the global error reporting instance
  public static function errors () {
    if (!isset(self::$instance)) {
      $class_name = __CLASS__;
      self::$instance = new $class_name;
    }
    
    return self::$instance;
  }
  
  /**
   * Parser Error Reporting
   * Used in parser output but doesn't affect the program
   */
  public function print_color (int $color, string $message) {
    echo ansi_string(array($color), $message) . "\n";
    // passthru('echo "\033[1;'.$color.'m'.addslashes($message).'\033[0m"');
  }

  // messages are plain text inserted to the console with no suggestions
  public function add_message ($message) {
    if (!$this->enable_messages) return;
    print($message."\n");
  }

  // notes are reminders of information which may not be important
  // but users would like to know
  public function add_note ($message, $add_prefix = true) {
    if (!$this->enable_notes) return;
    
    if ($add_prefix) {
      $prefix = "* Note:";
    } else {
      $prefix = "";
    }
    if ($this->enable_colors) {
      $this->print_color(ANSI_FORE_GREEN, $prefix." ".$message);
    } else {
      print($prefix." ".$message."\n");
    }
  }

  // warnings are potentially problematic issues that are not severe enough to stop the parser
  public function add_warning ($message) {
    if (!$this->enable_warnings) return;
    if ($this->enable_colors) {
      $this->print_color(ANSI_FORE_YELLOW, "* Warning: ".$message);
    } else {
      print("* Warning: ".$message."\n");
    }
  }
  
  // errors are will likely causing parsing to fail but not not severe enough to stop the parser
  public function add_error ($message) {
    if (!$this->enable_errors) return;
    if ($this->enable_colors) {
      $this->print_color(ANSI_FORE_RED, "* Error: ".$message);
    } else {
      print("* Error: ".$message."\n");
    }
  }
  
  // fatal errors are guaranteed to crash or make the parser fail
  // after adding an error of this level the parser will stop 
  public function add_fatal ($message) {
    if (!$this->enable_fatals) return;
    if (is_parser_option_enabled(PARSER_OPTION_VERBOSE)) {
      debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    }
    // die("Fatal: ".$message."\n");
    if ($this->enable_colors) {
      $this->print_color(ANSI_FORE_RED, "* Fatal: ".$message);
    } else {
      print("* Fatal: ".$message."\n");
    }
    die;
  }
  
  /**
   * Debugging
   */
  
  // adds a plain exception in development code (used for debugging only, replace with add_fatal for production)
  public function add_exception ($message) {
    if (!$this->enable_exceptions) return;
        
    throw new Exception($message);
  }
  
  private function __construct() {    
  }

}

function ansi_string(array $colors, string $string): string {
  $ansi_str = "";
  foreach ($colors as $attr) $ansi_str .= "\033[" . $attr . "m";
  $ansi_str .= $string . "\033[0m";
  return $ansi_str;
}

function print_color ($code, $message) {
  ErrorReporting::errors()->print_color($code, $message);
}


?>
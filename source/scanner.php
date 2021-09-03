<?php

const TOKEN_UNKNOWN = 0;
const TOKEN_ID = 1;
const TOKEN_CHAR = 2;
const TOKEN_EOF = 1000;

class Scanner {
  private $string;
  private $offset = 0;
  public $pattern = '';

  /*
   * Private Methods
   */

  private function get_char(): string {
    return $this->string[$this->offset];
  }

  private function read_word(): string {
    if (preg_match('/([a-z]+)/i', $this->string, $matches, 0, $this->offset)) {
      $this->pattern = $matches[1];
      $this->offset += strlen($this->pattern);
      return $this->pattern;
    } else {
      // TODO: assert
      return '';
    }
  }

  private function read_char(): string {
    $this->offset += 1;
    return $this->get_char();
  }

  /*
   * Public Methods
   */

  public function skip_space() {
    while (preg_match('/\s/', $this->string, $matches, 0, $this->offset)) {
      $this->read_char();
    }
  }

  public function consume(string $token) {
    if ($this->token == $token) {
      $this->read_token();
    } else {
      if ($this->token == TOKEN_ID) {
        throw new Exception("Got \"$this->pattern\", expected \"$token\".\n");
      } else {
        throw new Exception("Got \"$this->token\", expected \"$token\".\n");
      }
    }
  }

  public function try_consume(string $token, &$pattern = null): bool {
    $pattern = $this->pattern;
    if ($this->token == $token) {
      $this->read_token();
      return true;
    } else {
      return false;
    }
  }

  public function read_token(): string {
    while ($this->offset < strlen($this->string)) {
      $c = $this->get_char();
      
      if (preg_match('/[a-z]/i', $c)) {
        $this->read_word();
        $this->token = TOKEN_ID;
        return $this->token;
      } else if (preg_match('/\s/', $c)) {
        $this->read_char();
      } else {
        switch ($c) {
          case '<':
          case '>':
          case '*':
          case ',':
          case '(':
          case ')':
            $this->token = $c;
            $this->read_char();
            return $this->token;
          default:
            $this->token = TOKEN_UNKNOWN;
            return $this->token;
        }
      }
    }
    return TOKEN_EOF;
  }

  public function parse() {

  }

  function __construct(string $string) {
    $this->string = $string;
    $this->read_token();
    $this->parse();
  }
}

?>
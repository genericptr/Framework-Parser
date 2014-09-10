<?php

// PHP's garbage collecting system has a fatal flaw that makes objects leak
// memory when there are circular relationships between objects that reference
// each other as properties. For example if class A references class B, which in
// turn references class A simply destroying A will leak memory since __destroy()
// will never be called.
// 
// For this reason we need to introduce a little helper class that lets us
// "free" the class and unset references to child objects thus allowing the
// the parent object to be destroyed. There is a dangerous chance of calling
// free() on an object which destroys some properties we may need later (read on).
//
// The whole system is however tragically fragile and prone to error
// so unless we implement a total reference counted memory system across
// all classes or remove references to objects in classes using a lookup 
// table (bad for CPU) we need to just be careful when free() is called
// and properties are unset in __free().

class MemoryManager {
	private $did_free = false;
	private $is_freeing = false;
	
	/**
	 * Accessors
	 */
	
	public function is_free () {
		return $this->did_free;
	}
	
	/**
	 * Class Methods
	 */
	
	// release a value from an array with the key
	public static function free_array_value (&$array, $key) {
		if (!is_array($array)) return;
		$array[$key]->free();
		unset($array[$key]);
	}
	
	// releases an entire array and dereferences values
	// that are subclasses of MemoryManager
	public static function free_array (&$array) {
		if (!is_array($array)) return;
		foreach ($array as $key => $value) {
			if (is_a($array[$key], "MemoryManager")) $array[$key]->free();
			unset($array[$key]);
		}
	}
	
	/**
	 * Methods
	 */
	
	// frees a classes properties from circular references
	// this method can only be called once at which point
	// the classes instance variables may become invalid
	public function free() {
		// prevent double-frees
		if ($this->did_free) return;
		if ($this->is_freeing) return;
		$this->is_freeing = true;
		$this->__free();
		$this->is_freeing = false;
		$this->did_free = true;
	}

	/**
	 * Magic Methods
	 */
	
	// override and return null to disable printing
	// of memory debugging for the class
	protected function __debugString() {
		$name = (string)$this;
		if ($name != get_class($this)) {
			return get_class($this)." \"$name\"";
		} else {
			return $name;
		}
	}
	
	// override to unset references to objects
	// always invoke parent::__free()
	// --- never call directly, use free() instead ---
	protected function __free() {
		//print("  [*] free ".$this->__debugString()."\n");			
	}
		
	// override to set real string value (for MESSAGE_MEMORY_DEBUGGING notes)
	public function __toString() {
		return get_class($this);
	}
	
	// always invoke parent::__destruct() in subclasses
	// so the root method gets invoked which may provide important
	// functionality in the future
	public function __destruct() {
		$this->free();
		if ((MESSAGE_MEMORY_DEBUGGING) && ($this->__debugString())) print("  [-] ".$this->__debugString()." is destroyed.\n");			
	}

}

?>
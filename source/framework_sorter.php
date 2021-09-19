<?php

require_once("errors.php");
require_once("framework.php");

// The framework sorter is used to sort frameworks and headers
// for dependencies so that headers are declared in the proper order
// in the master include files

/**
 * Tree Sorter
 */

// a semi-modular class to sort a hierarchical tree by "deepest children first order"

class SorterNode {
	private $children = array();
	private $parent = null;
		
	// must override to set unique name of node
	public function get_name () {
		return null;
	}
	
	public function get_value () {
		return $this->get_name();
	}
	
	public function get_parent () {
		return $this->parent;
	}
	
	public function get_children () {
		return $this->children;
	}
	
	public function add_child (SorterNode $node) {
		$node->set_parent($this);
		$this->children[] = &$node;
	}
	
	public function set_parent (SorterNode $node) {
		$this->parent = &$node;
	}
	
	public function remove_from_parent () {
		$this->parent->remove_child($this);
	}
	
	public function remove_child (SorterNode $node) {
		foreach ($this->children as $key => $child) {
			if ($child->get_name() == $node->get_name()) {
				unset($this->children[$key]);
			}
		}
	}
	
	public function remove_all ($node) {
		foreach ($this->children as $child) {
			if ($child->get_name() == $node->get_name()) {
				$child->remove_from_parent();
			} else {
				$child->remove_all($node);
			}
		}
	}
	
	public function find_child ($name) {
		foreach ($this->children as $child) {
			if ($child->get_name() == $name) return $child;
		}
	}
	
	// finds the deepest child with no children
	public function find_deepest () {
		foreach ($this->children as $child) {
			if (count($child->get_children()) == 0) {
				return $child;
			} else {
				return $child->find_deepest();
			}
		}
	}
	
	public function find_parent ($name) {
		if ($this->parent) {
			if ($this->parent->get_name() == $name) {
				return true;
			} else 
				return $this->parent->find_parent($name);
		} else {
			return false;
		}
	}
	
	public function print_tree ($level = 0) {
		foreach ($this->children as $child) {
			if ($level == 0) {
				print("+ ".$child->get_name()."\n");
			} else {
				print(indent_string($level + 1).$child->get_name()."\n");
			}
			$child->print_tree($level + 2);
		}
	}	
	
	public function print_parents () {
		$parent = $this->parent;
		while ($parent) {
			$parents[] = $parent;
			$parent = $parent->parent;
		}
		$parents = array_reverse($parents);
		print(implode(" > ", $parents)."\n");
	}	
	
	public function __toString() {
		return $this->get_name();
	}
	
	// override to populate the tree with nodes
	public function build_tree () {
	}	
	
}

class TreeSorter {
	protected $root;
	
	protected function can_add_node (SorterNode $node) {
		return true;
	}
	
	public function sort ()  {
		$order = array();

		// loop until no "deep" nodes are found
		while (true) {
			if ($node = $this->root->find_deepest()) {
				if ($this->can_add_node($node)) $order[] = $node->get_value();
				$this->root->remove_all($node);
			} else {
				break;
			}
		}

		return $order;
	}

}

/**
 * Header Sorter
 */

class HeaderNode extends SorterNode {
	public $framework;
	private $path;

	public function get_name () {
		return basename($this->path);
	}
	
	public function get_value () {
		return $this->path;
	}
	
	private function add_header ($path) {
		$name = basename($path);
		
		// add the child if it's not adding into itself or
		// one if its parents, which would cause an infinite loop
		if ((!$this->find_parent($name)) && ($this->get_name() != $name)) {
			$child = new HeaderNode($path, $this->framework);
			$this->add_child($child);
			$child->build_tree();
		}
	}
	
	public function build_tree () {
		$lines = file($this->path);
		$directory = dirname($this->path);
		
		foreach ($lines as $line) {
			$line = trim($line);
			
			// single header import/includes
			if (preg_match("/#(import|include)\s*\"(.*)\"/i", $line, $captures)) {
				$path = $directory."/".$captures[2];
				if (file_exists($path)) {
					$this->add_header($path);
				}
			}			
						
			// find imports/includes
			if (preg_match("/#(import|include)\s*<(.*)>/i", $line, $captures)) {
				//print("    import $captures[2]\n");
				
				// add imported framework that was specified in path
				// #include <QuartzCore/../Frameworks/CoreImage.framework/Headers/CoreImage.h>
				if (preg_match("/.*?\/Frameworks\/(\w+)\.framework/", $captures[2], $path_captures)) {
					$this->framework->add_imported_framework($path_captures[1]);
				}
				
				// add imported framework as first path component
				// #import <Foundation/NSObject.h>
				$parts = explode("/", $captures[2]);
				if (count($parts) > 1) {
					$this->framework->add_imported_framework($parts[0]);
				}
				
				$path = $directory."/".basename($captures[2]);
				
				// if the header exists in the current directory add to array
				if (file_exists($path)) {
					$this->add_header($path);
				}
			}
		}
	}		
		
	public function __construct ($path, Framework $framework)  {	
		$this->path = $path;
		$this->framework = &$framework;
	}
	
}

class HeaderSorter extends TreeSorter {
	
	// class method to utilitize the header sorter to harvest
	// imported frameworks from a header (specified at $path)
	public static function harvest_imports ($path, Framework $framework) {
		$node = new HeaderNode($path, $framework);
		$node->build_tree();
	}
	
	public function __construct ($root, Framework $framework)  {
		$this->root = new HeaderNode($root, $framework);
		$this->root->build_tree();
	}
}

/**
 * Framework Sorter
 */

class FrameworkNode extends SorterNode {
	private $framework;
	private $sorter;

	public function get_name () {
		return $this->framework->get_name();
	}
		
	public function build_tree ($level = 0) {		
		//sample_memory_usage();
		
		foreach ($this->framework->get_imported_frameworks() as $framework) {
			$name = $framework->get_name();
			
			// the framework has already been sorted
			if (in_array($name, $this->sorter->master)) {
				$child = new FrameworkNode($framework, $this->sorter);
				$this->add_child($child);
				continue;
			}
			
			// add the child if it's not adding into itself or
			// one if its parents, which would cause an infinite loop
			if ((!$this->find_parent($name)) && ($this->get_name() != $name)) {
				$child = new FrameworkNode($framework, $this->sorter);
				$this->add_child($child);
				$child->build_tree($level + 1);
				$this->sorter->master[] = $name;
			}
		}
	}		
	
	public function __construct (Framework $framework, FrameworkSorter $sorter)  {	
		$this->framework = &$framework;
		$this->sorter = &$sorter;
	}
	
}

class FrameworkSorter extends TreeSorter {		
	public $master = array();
	private $frameworks;
	
	protected function can_add_node (SorterNode $node) {
		// filter nodes that aren't in the original array of frameworks
		return (in_array($node->get_name(), $this->frameworks));
	}
	
	public function __construct (array $frameworks)  {	
		
		// for the tree sorter to work we need to merge all
		// frameworks into a base framework which will manage
		// them as imported frameworks, although this isn't really the case
		$base = new Framework();
		foreach ($frameworks as $framework) $base->add_imported_framework($framework->get_name());
		
		// build array of framework names for filtering in can_add_node()
		foreach ($frameworks as $framework) $this->frameworks[] = $framework->get_name();
				
		// make a new node with the base framework and sort
		$this->root = new FrameworkNode($base, $this);
		$this->root->build_tree();
	}
	
	
}	

?>
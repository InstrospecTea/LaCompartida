<?php

abstract class AbstractTwigExtension extends Twig_Extension {

	function getFilters() {
			//Reflection para indexar automaticamente los métodos.
		$class = new ReflectionClass(get_class($this));
		$classMethods = $class->getMethods();
		$filters = array();

		foreach ($classMethods as $method) {
			//new \Twig_SimpleFilter('strftime', array($this, 'strfTime')),
			if (preg_match('/^ext[A-Z].+$/', $method->name)) {
				$name = preg_replace('/^ext([A-Z].+)$/e', 'strtolower(\\1)', $method->name);
				$filters[] = new \Twig_SimpleFilter($name, array($this, $method->name));
			}
		}
		return $filters;
	}

}

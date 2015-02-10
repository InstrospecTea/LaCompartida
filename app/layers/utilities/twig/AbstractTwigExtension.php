<?php

/**
 * Clase abstracta para el manejo de extensiones
 */
abstract class AbstractTwigExtension extends Twig_Extension {

	/**
	 * Retorna una lista de filtros según como se declaren los métodos en las clases que extiendan AbstractTwigExtension
	 *
	 * Los métodos que se convertirán en filtros deben seguir la siguiente nomenclatura
	 * Prefijo "ext": extNombreMetodo
	 * ej: extLipsum
	 *
	 * @return array
	 */
	function getFilters() {
		// Reflection para indexar automaticamente los métodos.
		$class = new ReflectionClass(get_class($this));
		$classMethods = $class->getMethods();
		$filters = array();

		foreach ($classMethods as $method) {
			if (preg_match('/^ext[A-Z].+$/', $method->name)) {
				$name = preg_replace('/^ext([A-Z].+)$/e', 'strtolower(\\1)', $method->name);

				// new \Twig_SimpleFunction('lipsum', array($this, 'extLipsum'))
				$filters[] = new \Twig_SimpleFilter($name, array($this, $method->name));
			}
		}

		return $filters;
	}

}

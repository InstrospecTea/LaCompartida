<?php

/**
 * Clase abstracta para el manejo de extensiones
 */
abstract class AbstractTwigExtension extends Twig_Extension {

	/**
	 * Retorna una lista de filtros seg�n como se declaren los m�todos en las clases que extiendan AbstractTwigExtension
	 *
	 * Los m�todos que se convertir�n en filtros deben seguir la siguiente nomenclatura
	 * Prefijo "ext": extNombreMetodo
	 * ej: extLipsum
	 *
	 * @return array
	 */
	function getFilters() {
		// Reflection para indexar automaticamente los m�todos.
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

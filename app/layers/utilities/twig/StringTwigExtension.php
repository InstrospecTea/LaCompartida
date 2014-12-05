<?php

class StringTwigExtension extends AbstractTwigExtension {

	public function getName() {
		return 'TtbStringExtension';
	}

	/**
	 * Convierte el primer caracter de una cadena a mayúsculas (http://php.net/ucfirst)
	 *
	 * {{'text'|ucfirst}}
	 *
	 * @param $s string
	 * @return string
	 */
	public function extUcFirst($s) {
		return ucfirst($s);
	}

}

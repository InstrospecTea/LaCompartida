<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Created by vladzur.
 * Date: 30-10-15
 * Time: 10:14 AM
 *
 * Adaptación para el formato LEDES de Counselink
 */
class Counselink extends Ledes{

	/**
	 * Número de decimales a mostrar
	 * @var int
	 */
	protected $decimales = 2;

	/**
	 * Counselink constructor.
	 * @param $Sesion
	 */
	public function __construct($Sesion) {
		$this->sesion = $Sesion;
	}

}

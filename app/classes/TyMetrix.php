<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Created by vladzur.
 * Date: 30-10-15
 * Time: 10:14 AM
 *
 * Adaptaci�n para el formato LEDES de TyMetrix
 */
class TyMetrix extends Ledes{

	/**
	 * N�mero de decimales a mostrar
	 * @var int
	 */
	protected $decimales = 2;

	/**
	 * TyMetrix constructor.
	 * @param $Sesion
	 */
	public function __construct($Sesion) {
		$this->sesion = $Sesion;
	}

}

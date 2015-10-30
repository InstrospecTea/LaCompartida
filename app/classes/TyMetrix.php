<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Created by vladzur.
 * Date: 30-10-15
 * Time: 10:14 AM
 *
 * Adaptación para el formato LEDES de TyMetrix
 */
class TyMetrix extends Ledes{

	/**
	 * TyMetrix constructor.
	 * @param $Sesion
	 */
	public function __construct($Sesion) {
		$this->sesion = $Sesion;
	}

}

<?php
/**
 * Created by PhpStorm.
 * User: dochoaj
 * Date: 12/2/14
 * Time: 5:33 PM
 */

interface ITimeEntriesInvestment extends BaseReport{

	/**
     * Establece la agrupación que tomará el reporte para desplegarse.
     * @param string$agrupation Puede ser 'Client' o 'User'. Por defecto es 'User'.
     * @return mixed
	 */
	function agrupationBy($agrupation = 'User');

} 
<?php

/**
 *
 * @author matias.orellana
 */
interface SimpleReport_Writer_IWriter {
	/**
	 * Save PHPExcel to file
	 *
	 * @param 	string 		$pFilename
	 * @throws 	Exception
	 */
	public function save($pFilename = null);

}

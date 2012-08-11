<?php

/**	PHPExcel root directory */
if (!defined('SIMPLEREPORT_ROOT')) {
	/**
	 * @ignore
	 */
	define('SIMPLEREPORT_ROOT', dirname(__FILE__) . '/../');
	require(SIMPLEREPORT_ROOT . 'SimpleReport/Autoloader.php');
}

/**
 * SimpleReport_IOFactory
 */
class SimpleReport_IOFactory
{
	/**
	 * Search locations
	 *
	 * @var	array
	 * @access	private
	 * @static
	 */
	private static $_searchLocations = array(
		array( 'type' => 'IWriter', 'path' => 'Writer/{0}.php', 'class' => 'SimpleReport_Writer_{0}' ),
		array( 'type' => 'IReader', 'path' => 'Reader/{0}.php', 'class' => 'SimpleReport_Reader_{0}' )
	);

    /**
     *	Private constructor for PHPExcel_IOFactory
     */
    private function __construct() { }

	/**
	 * Create SimpleReport_Writer_IWriter
	 *
	 * @static
	 * @access	public
	 * @param	SimpleReport $simpleReport
	 * @param	string  $writerType	Example: Excel
	 * @return	SimpleReport_Writer_IWriter
	 * @throws	Exception
	 */
	public static function createWriter(SimpleReport $simpleReport, $writerType = '') {
		// Search type
		$searchType = 'IWriter';

		// Include class
		foreach (self::$_searchLocations as $searchLocation) {
			if ($searchLocation['type'] == $searchType) {
				$className = str_replace('{0}', $writerType, $searchLocation['class']);
				$classFile = str_replace('{0}', $writerType, $searchLocation['path']);
				
				$instance = new $className($simpleReport);
				if ($instance !== NULL) {
					return $instance;
				}
			}
		}

		// Nothing found...
		throw new Exception("No $searchType found for type $writerType");
	}	//	function createWriter()

}

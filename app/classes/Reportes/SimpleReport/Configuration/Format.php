<?php
/**
 * Description of Format
 *
 * @author matias.orellana
 */
abstract class SimpleReport_Configuration_Format {

	/**
	 * @return array
	 */
	public static $formats = array(
		'date',
		'number',
		'text',
		'time',
		'title'
	);

	public static function AllowedFormats() {
		return array_keys(self::$formats);
	}

}
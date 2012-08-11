<?php

/**
 * Description of SimpleReport_Configuration
 *
 * @author matias.orellana
 */
class SimpleReport_Configuration {
	/**
	 * @type array
	 */
	public $columns;

	/**
	 * @type string
	 */
	public $title;

	function __construct() {
		$this->columns = array();
		$this->title = 'Reporte';
	}

	function AddColumn(SimpleReport_Configuration_Column $col) {
		$this->columns[$col->field] = $col;
	}

	function SetTitle($title) {
		$this->title = $title;
	}
	
	function VisibleColumns() {
		$visible_columns = array();
		
		foreach ($this->columns as $column) {
			if ($column->visible) {
				$visible_columns[] = $column;
			}
		}
		
		return $visible_columns;
	}

	public static function LoadFromJson($json) {
		$json = json_decode($json, true);

		$config = new SimpleReport_Configuration();

		foreach ($json as $json_column) {
			$column = new SimpleReport_Configuration_Column();
			$column->Field($json_column['field'])
				->Title($json_column['title'])
				->Order($json_column['order'])
				->Format($json_column['format'])
				->Visible($json_column['visible']);
			
			if (array_key_exists('sort', $json_column)) {
				$column->Sort($json_column['sort']);
			}

			$config->AddColumn($column);
		}

		$config = self::SortByOrder($config);
		
		return $config;
	}
	
	public static function SortByOrder(SimpleReport_Configuration $configuration) {
		uasort($configuration->columns, 'sort_by_order');
		return $configuration;
	}
}

function sort_by_order($a, $b) {
	$a = $a->order;
	$b = $b->order;
	
	if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

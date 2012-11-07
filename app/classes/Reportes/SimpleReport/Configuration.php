<?php

/**
 * Description of SimpleReport_Configuration
 *
 * @author matias.orellana
 */
class SimpleReport_Configuration {
	/**
	 * @type SimpleReport_Configuration_Column[]
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

	/**
	 *
	 * @return SimpleReport_Configuration_Column[]
	 */
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
		return self::LoadFromArray(json_decode($json, true));
	}

	public static function LoadFromArray($columns) {
		$config = new SimpleReport_Configuration();

		foreach ($columns as $idx => $column) {
			$config_column = new SimpleReport_Configuration_Column();
			$config_column->Field($column['field'])
				->Title(array_key_exists('title', $column) ? $column['title'] : $column['field'])
				->Order(array_key_exists('order', $column) ? $column['order'] : $idx)
				->Format(array_key_exists('format', $column) ? $column['format'] : 'text')
				->Visible(array_key_exists('visible', $column) ? $column['visible'] : true);

			if (array_key_exists('sort', $column)) {
				$config_column->Sort($column['sort']);
			}
			if (array_key_exists('group', $column)) {
				$config_column->Group($column['group']);
			}
			if (array_key_exists('extras', $column)) {
				$config_column->Extras($column['extras']);
			}

			$config->AddColumn($config_column);
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

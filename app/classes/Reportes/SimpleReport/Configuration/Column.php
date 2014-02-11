<?php

/**
 * Description of Column
 *
 * @author matias.orellana
 */
class SimpleReport_Configuration_Column {

	public $field;
	public $title;
	public $order;
	public $visible;
	public $format;
	public $sort;
	public $extras;
	public $group;

	function __construct() {
		$this->field = '';
		$this->name = '';
		$this->title = '';
		$this->order = 0;
		$this->visible = true;
		$this->format = 'text';
		$this->sort = '';
	}

	public function Field($field) {
		$this->field = $field;
		return $this;
	}

	public function Name($name) {
		$this->name = $name;
		return $this;
	}

	public function Title($title) {
		$this->title = mb_detect_encoding($title, 'UTF-8', true) != 'UTF-8' ? utf8_encode($title) : $title;
		return $this;
	}

	public function Order($order) {
		$this->order = $order;
		return $this;
	}

	public function Visible($visible = true) {
		$this->visible = $visible;
		return $this;
	}

	public function Sort($sort) {
		$this->sort = $sort;
		return $this;
	}

	public function Extras($extras) {
		$this->extras = $extras;
		return $this;
	}

	public function Group($group) {
		$this->group = $group;
		return $this;
	}

	public function Format($format = 'text') {
		if (in_array($format, SimpleReport_Configuration_Format::AllowedFormats())) {
			$this->format = $format;
		}
		return $this;
	}

}

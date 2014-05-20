<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

class Form {

	public $Utiles;
	public $Html;

	public function __construct() {
		$this->Utiles = new \TTB\Utiles();
		$this->Html = new \TTB\Html();
	}

	/**
	 * Construye un select a partir de un arreglo
	 * @param type $name
	 * @param type $options
	 * @param type $selected
	 * @param type $attrs
	 * @return type
	 */
	public function select($name, $options, $selected = null, $attrs = null) {
		if (empty($attrs['name'])) {
			$attrs['name'] = $name;
		}
		if (empty($attrs['id'])) {
			$attrs['id'] = $this->Utiles->pascalize($attrs['name']);
		}
		$html_options = '';
		if (!isset($attrs['empty']) || $attrs['empty'] !== false) {
			$html_options .= $this->Html->tag('option', $attrs['empty'], array('value' => ''), true);
		}
		unset($attrs['empty']);
		$html_options .= $this->options($options, $selected);

		$select = $this->Html->tag('select', $html_options, $attrs);
		return $select;
	}

	/**
	 * Devuelve un string con tags option a partir de un Array
	 * @param type $options
	 * @param type $selected
	 * @return type
	 */
	public function options($options, $selected) {
		$html = '';
		foreach ($options as $value => $text) {
			if (is_array($text)) {
				$html_options = $this->options($text);
				$html .= $this->Html->tag('optgroup', $html_options, array('label' => $value));
			} else {
				$op_attr = array(
					'value' => $value
				);
				if ("$value" == "$selected") {
					$op_attr['selected'] = 'selected';
				}
				$html .= $this->Html->tag('option', $text, $op_attr);
			}
		}
		return $html;
	}

}
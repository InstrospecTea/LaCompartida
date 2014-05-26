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

	/**
	 *
	 * @param type $text
	 * @param type $for
	 * @return type
	 */
	public function label($text, $for = null) {
		$attrs = array();
		if (!empty($for)) {
			$attrs['for'] = $for;
		}
		return $this->Html->tag('label', $text, $attrs);
	}
	/**
	 *
	 * @param type $name
	 * @param type $value
	 * @param type $selected
	 * @param type $attrs
	 */
	public function radio($name, $value, $selected = false, $attrs = null) {
		$attrs = (Array) $attrs + array('type' => 'radio', 'value' => $value, 'label' => true);
		$label = null;

		if ($attrs['label'] === true) {
			$label = $this->Utiles->humanize($name);
		} else if ($attrs['label'] !== false) {
			$label = $attrs['label'];
		}
		unset($attrs['label'], $attrs['checked']);
		if (empty($attrs['name'])) {
			$attrs['name'] = $name;
		}
		if (empty($attrs['id'])) {
			$attrs['id'] = $this->Utiles->pascalize($attrs['name']);
		}
		if ($value === $selected) {
			$attrs['checked'] = 'checked';
		}
		$radio = $this->Html->tag('input', null, $attrs, true);
		return empty($label) ? $radio : $this->label($radio . $label);
	}

	/**
	 *
	 * @param type $name
	 * @param type $options
	 * @param type $selected
	 * @param type $attrs
	 * @param type $container
	 * @param type $container_attrs
	 * @return type
	 */
	public function radio_group($name, $options, $selected = null, $attrs = null, $container = 'div', $container_attrs = null) {
		$html = '';
		$x = 1;
		foreach ((Array) $options as $value => $label) {
			$attrs = array('label' => true) + (Array) $attrs;
			if ($attrs['label'] === false) {
				$value = $label;
			} else if ($attrs['label'] === true) {
				$attrs['label'] = $label;
			}
			$attrs['id'] = $this->Utiles->pascalize($name . $x);
			$html .= $this->radio($name, $value, $selected, $attrs);
			++$x;
		}
		if ($container !== false) {
			$html = $this->Html->tag($container, $html, (Array) $container_attrs);
		}
		return $html;
	}

}
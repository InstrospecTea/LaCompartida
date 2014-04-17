<?php

namespace TTB;

require_once dirname(__FILE__) . '/../conf.php';

use \Conf;

require_once Conf::ServerDir() . '/../fw/classes/Html.php';

class Html extends \Html {

	/**
	 * Construye un tag html
	 */
	public static function tag($tag = 'div', $content = '', $attributes = null, $closed = false) {
		$html = '';

		$attributes = is_array($attributes) ? self::attributes($attributes) : $attributes;

		if ($closed) {
			$html = sprintf('<%s%s />', $tag, $attributes);
		} else {
			$html = sprintf('<%s%s>%s</%s>', $tag, $attributes, $content, $tag);
		}

		return $html;
	}

	/**
	 * Crea string de atributos HTML a partir de un Array
	 * @param type $attributes
	 * @return string
	 */
	public function attributes($attributes) {
		$html = '';
		if (is_array($attributes)) {
			foreach ($attributes as $name => $value) {
				$html .= sprintf(' %s="%s"', $name, $value);
			}
		} else {
			$html = $attributes;
		}
		return $html;
	}

	/**
	 * Crea un select HTML con opciones Todos, SI y NO
	 * @param type $name
	 * @param string $selected
	 * @param string $opciones
	 * @return string
	 */
	public static function SelectSiNo($name, $selected = '', $opciones = '') {
		$array = array('SI' => __('SI'), 'NO' => __('NO'));
		return parent::SelectArrayDecente($array, $name, $selected = '', $opciones = '', 'Todos', '60');
	}

}


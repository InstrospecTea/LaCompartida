<?php

namespace TTB;

require_once dirname(__FILE__) . '/../conf.php';

use \Conf;

require_once Conf::ServerDir() . '/../fw/classes/Html.php';

class Html extends \Html {

	/**
	 * Construye un tag html
	 * @param type $tag
	 * @param type $content
	 * @param type $attributes
	 * @param type $closed
	 * @return type
	 */
	public function tag($tag = 'div', $content = '', $attributes = null, $closed = false) {
		$html = '';

		$attributes = is_array($attributes) ? $this->attributes($attributes) : $attributes;

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
				if ($value === true) {
					$value = $name;
				} else if ($value === false) {
					$value = '';
				}
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
		return parent::SelectArrayDecente($array, $name, $selected, $opciones, 'Todos', '60');
	}

	public function link($text, $url, $attrs = '') {
		$_attrs = array(
			'href' => $url
		);
		$attrs = array_merge($_attrs, (array) $attrs);

		if (empty($attrs['title']) && $attrs['title'] !== false) {
			$attrs['title'] = $text;
		}
		return $this->tag('a', $text, $attrs);
	}

	/**
	 *
	 * @param type $script_block
	 */
	public function script_block($script_block, $attrs = array()) {
		return $this->tag('script', $script_block, array_merge(array('type' => 'text/javascript'), (array) $attrs));
	}

	/**
	 *
	 * @param type $file
	 */
	public function script($file) {
		return $this->tag('script', '', array('type' => 'text/javascript', 'src' => $file));
	}

}


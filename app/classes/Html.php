<?php

namespace TTB;

require_once dirname(__FILE__) . '/../conf.php';

use \Conf;

require_once Conf::ServerDir() . '/../fw/classes/Html.php';

class Html extends \Html {

	protected $jsPath = '//static.thetimebilling.com/js/';
	protected $cssPath = '//static.thetimebilling.com/css/';

	public function div($text, $attrs = null) {
		return $this->tag('div', $text, $attrs);
	}
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

		$attributes = $this->attributes($attributes);

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
					continue;
				}
				$html .= sprintf(' %s="%s"', $name, $value);
			}
		} else if (!is_null($attributes)) {
			$html = $attributes;
		}
		return $html;
	}

	public function img($image, $attributes = null) {
		$attr = array_merge(array('src' => $image), (array) $attributes);
		return $this->tag('img', '', $attr, true);
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

	/**
	 *
	 * @param type $text
	 * @param type $url
	 * @param type $attrs
	 * @return type
	 */
	public function link($text, $url, $attrs = null) {
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
	public function script_block($script_block, $attrs = null) {
		return $this->tag('script', $script_block, array_merge(array('type' => 'text/javascript'), (array) $attrs));
	}

	/**
	 * Devuelve tag script con src a archivo JS
	 * @param type $file
	 * @param type $attrs
	 * @return string
	 */
	public function script($file, $attrs = null) {
		if (is_array($file)) {
			$html = '';
			foreach ($file as $f) {
				$html .= $this->script($f, $attrs);
			}
			return $html;
		}
		$_attrs = array_merge(array('type' => 'text/javascript', 'src' => $this->path($file, 'js')), (array) $attrs);
		return $this->tag('script', '', $_attrs);
	}

	/**
	 * Devuelve link con href a archivo CSS
	 * @param type $file
	 * @param type $attrs
	 * @return string
	 */
	public function css($file, Array $attrs = array()) {
		$_attrs = array_merge(array('type' => 'text/css', 'rel' => 'stylesheet', 'src' => $this->path($file, 'css')), $attrs);
		return $this->tag('link', '', $_attrs, true);
	}

	/**
	 * Devuelve ruta del archivo indicado
	 * @param type $file
	 * @param type $type
	 * @return type
	 */
	protected function path($file, $type) {
		if (preg_match('/^(\/|https?:\/\/)/', $file)) {
			return $file;
		}
		$filename = preg_match("/\.{$type}$|\.{$type}\?/", $file) ? $file : "{$file}.{$type}";
		return $this->{"{$type}Path"} . $filename;
	}

}

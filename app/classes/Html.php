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

	public function form(array $attrs = array(), $closed = false) {
		$attrs = $this->attributes($attrs);
		$html = !$closed ? sprintf("<form%s>\n", $attrs) : "</form>\n";

		return $html;
	}

	/**
	 * Construye un tag html
	 * @param string $tag
	 * @param string $content
	 * @param array|string $attributes
	 * @param bool $closed
	 * @return string
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
	 * @param array|string $attributes
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
	 * @param string $text
	 * @param string $url
	 * @param array $attrs
	 * @return string
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
	 * @param string $script_block
	 * @param array $attrs
	 * @return string
	 */
	public function script_block($script_block, $attrs = null) {
		return $this->tag('script', $script_block, array_merge(array('type' => 'text/javascript'), (array) $attrs)) . "\n";
	}

	/**
	 * Devuelve tag script con src a archivo JS
	 * @param string|array $file
	 * @param array $attrs
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
		return $this->tag('script', '', $_attrs) . "\n";
	}

	/**
	 * Devuelve link con href a archivo CSS
	 * @param string $file
	 * @param array|string $attrs
	 * @return string
	 */
	public function css($file, Array $attrs = array()) {
		$_attrs = array_merge(array('type' => 'text/css', 'rel' => 'stylesheet', 'href' => $this->path($file, 'css')), $attrs);
		return $this->tag('link', '', $_attrs, true) . "\n";
	}

	/**
	 *
	 * Devuelve una alerta html
	 * @param atring $alert
	 * @param string $type success, info, danger, error o vacio
	 * @param array $attrs
	 * @return string
	 */
	public function alert($alert, $type = '', Array $attrs = array()) {
		$extra_class = '';
		if (!empty($attrs['class'])) {
			$extra_class = $attrs['class'];
			unset($attrs['class']);
		}
		if (!empty($type)) {
			$type = "alert-$type";
		}
		$_attrs = array_merge(
			array('class' => trim("alert $type $extra_class")),
			(array) $attrs
		);
		return $this->tag('div', $alert, $_attrs, false);
	}

	/**
	 * Devuelve ruta del archivo indicado
	 * @param string $file
	 * @param string $type
	 * @return string
	 */
	protected function path($file, $type) {
		if (preg_match('/^(\/|https?:\/\/)/', $file)) {
			return $file;
		}
		$filename = preg_match("/\.{$type}$|\.{$type}\?/", $file) ? $file : "{$file}.{$type}";
		return $this->{"{$type}Path"} . $filename;
	}

	/**
	 * Devuelve un calendario. Si la fecha está en blanco se desplegara la fecha actual.
	 * @param string $input_name
	 * @param string $value
	 * @param int $size
	 * @param string $clase
	 * @param boolean $blank
	 * @return string
	 */
	public static function PrintCalendar($input_name, $value, $size = 12, $clase = 'fechadiff', $blank = false) {
		if ($value == '') {
			if ($blank) {
				return "<input type='text' id='" . $input_name . "' name='" . $input_name . "' class='" . $clase . "' value='' size='" . $size . "' />";
			} else {
				return "<input type='text' id='" . $input_name . "' name='" . $input_name . "' class='" . $clase . "' value='" . date('d-m-Y') . "' size='" . $size . "' />";
			}
		} else {
			return "<input type='text' id='" . $input_name . "' name='" . $input_name . "' class='" . $clase . "' value='" . $value . "' size='" . $size . "' />";
		}
	}
}

<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

class Form {

	public $Utiles;
	public $Html;
	protected $scripts = array();
	protected $image_path = '//static.thetimebilling.com/images/';

	public function __construct() {
		$this->Utiles = new \TTB\Utiles();
		$this->Html = new \TTB\Html();
	}

	/**
	 * Crea elemento select a partir de un Array
	 * @param type $name
	 * @param type $options
	 * @param type $selected
	 * @param Array $attrs
	 * @return type
	 */
	public function select($name, $options, $selected = null, Array $attrs = array()) {
		$_attrs = $attrs + array('empty' => '');
		if (empty($_attrs['name']) && !empty($name)) {
			$_attrs['name'] = $name;
		}
		if (empty($_attrs['id']) && !empty($name)) {
			$_attrs['id'] = $name;
		}
		$html_options = '';
		if ($_attrs['empty'] !== false) {
			$html_options .= $this->Html->tag('option', $_attrs['empty'], array('value' => ''));
		}
		unset($_attrs['empty']);
		$html_options .= $this->options($options, $selected);

		$select = $this->Html->tag('select', $html_options, $_attrs);
		return $select;
	}

	/**
	 * Crea un string con tags option a partir de un Array
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
					$op_attr['selected'] = true;
				}
				$html .= $this->Html->tag('option', $text, $op_attr);
			}
		}
		return $html;
	}

	/**
	 * Devuelve elemento label
	 * @param type $text
	 * @param type $for
	 * @param array $attrs
	 * @return type
	 */
	public function label($text, $for = null, Array $attrs = array()) {
		if (!empty($for)) {
			$_attrs = array_merge(array('for' => $for), $attrs);
		}
		return $this->Html->tag('label', $text, $_attrs);
	}

	/**
	 * Crea un elemento input
	 * @param type $name
	 * @param type $value
	 * @param Array $attrs
	 * @return type
	 */
	public function input($name, $value, Array $attrs = array()) {
		$attrs = array_merge(array('type' => 'text', 'value' => $value, 'label' => true, 'name' => null), $attrs);
		$label = null;

		if ($attrs['type'] == 'hidden') {
			$attrs['label'] = false;
		}
		if ($attrs['label'] === true) {
			$label = $this->Utiles->humanize($name);
		} else if ($attrs['label'] !== false) {
			$label = $attrs['label'];
		}
		if (empty($attrs['name']) && !empty($name)) {
			$attrs['name'] = $name;
		}
		if (empty($attrs['id']) && !empty($name)) {
			$attrs['id'] = $name;
		}
		unset($attrs['label']);
		$input = $this->Html->tag('input', null, $attrs, true);
		return empty($label) ? $input : $this->label($label, $attrs['name']) . $input;
	}

	/**
	 * Crea elemento input con una atiqueta antes del mismo.
	 * @param type $name
	 * @param type $prepend
	 * @param type $value
	 * @param array $attrs
	 * @return type
	 */
	public function input_prepend($name, $prepend, $value, Array $attrs = array()) {
		$_attrs = array_merge(array('label' => false), $attrs);
		return $this->Html->tag('span', $prepend, array('class' => 'input_prepend')) . $this->input($name, $value, $_attrs);
	}

	/**
	 * Crea elemento input con una atiqueta despues del mismo.
	 * @param type $name
	 * @param type $append
	 * @param type $value
	 * @param array $attrs
	 * @return type
	 */
	public function input_append($name, $append, $value, Array $attrs = array()) {
		$_attrs = array_merge(array('label' => false), $attrs);
		return $this->input($name, $value, $_attrs) . $this->Html->tag('span', $append, array('class' => 'input_append'));
	}

	/**
	 * Crea elemento input type=hidden
	 * @param type $name
	 * @param type $value
	 * @param array $attrs
	 * @return type
	 */
	public function hidden($name, $value, Array $attrs = array()) {
		$attrs = array_merge($attrs, array('type' => 'hidden'));
		return $this->input($name, $value, $attrs);
	}

	/**
	 * Devuelve elemento checkbox
	 * @param type $name
	 * @param type $value
	 * @param type $checked
	 * @param Array $attrs
	 */
	public function checkbox($name, $value, $checked = false, Array $attrs = array()) {
		$attrs = $attrs + array('type' => 'checkbox', 'value' => $value, 'label' => true);
		$label = null;

		if ($attrs['label'] === true) {
			$label = $this->Utiles->humanize($name);
		} else if ($attrs['label'] !== false) {
			$label = $attrs['label'];
		}
		unset($attrs['label']);
		if (empty($attrs['name']) && !empty($name)) {
			$attrs['name'] = $name;
		}
		if (empty($attrs['id']) && !empty($name)) {
			$attrs['id'] = $name;
		}
		$attrs['checked'] = $checked;
		$radio = $this->Html->tag('input', null, $attrs, true);
		return empty($label) ? $radio : $this->label($radio . $label);
	}

	/**
	 * Devuelve elemento radio
	 * @param type $name
	 * @param type $value
	 * @param type $checked
	 * @param Array $attrs
	 */
	public function radio($name, $value, $checked = false, Array $attrs = array()) {
		$attrs = $attrs + array('type' => 'radio', 'value' => $value, 'label' => true);
		$label = null;

		if ($attrs['label'] === true) {
			$label = $this->Utiles->humanize($name);
		} else if ($attrs['label'] !== false) {
			$label = $attrs['label'];
		}
		unset($attrs['label']);
		if (empty($attrs['name']) && !empty($name)) {
			$attrs['name'] = $name;
		}
		if (empty($attrs['id']) && !empty($name)) {
			$attrs['id'] = $name;
		}
		$attrs['checked'] = $checked;

		$radio = $this->Html->tag('input', null, $attrs, true);
		return empty($label) ? $radio : $this->label($radio . $label);
	}

	/**
	 *
	 * @param type $name
	 * @param type $options Array value => label, label puede ser un Array donde sus valores indican los atributos
	 * @param type $selected
	 * @param type $container
	 * @param type $container_attrs
	 * @return type
	 */
	public function radio_group($name, $options, $selected, $container = 'div', Array $container_attrs = array()) {
		$html = '';
		$x = 1;
		foreach ((Array) $options as $value => $label) {
			$_attrs = array();
			if (is_array($label)) {
				$_attrs = $label;
				$label = empty($_attrs['label']) ? $this->Utiles->pascalize($name) : $_attrs['label'];
				unset($_attrs['label']);
			}
			$attrs = array('label' => true) + $_attrs;
			if ($attrs['label'] === false) {
				$value = $label;
			} else if ($attrs['label'] === true) {
				$attrs['label'] = $label;
			}
			$attrs['id'] = "{$name}_{$x}";
			$html .= $this->radio($name, $value, $value == $selected, $attrs);
			++$x;
		}
		if ($container !== false) {
			$html = $this->Html->tag($container, $html, $container_attrs);
		}
		return $html;
	}

	/**
	 *
	 * @param type $options Array name => label, label puede ser un Array donde sus valores indican los atributos
	 * @param type $selected
	 * @param type $container
	 * @param type $container_attrs
	 * @return type
	 */
	public function checkbox_group($options, Array $checkeds = array(), $container = 'div', Array $container_attrs = array()) {
		$html = '';
		$x = 1;
		foreach ((Array) $options as $name => $label) {
			$_attrs = array();
			if (is_array($label)) {
				$_attrs = $label;
				$label = empty($_attrs['label']) ? $this->Utiles->pascalize($name) : $_attrs['label'];
				unset($_attrs['label']);
			}
			$attrs = array('label' => true) + $_attrs;
			if ($attrs['label'] === true) {
				$attrs['label'] = $label;
			}
			$attrs['id'] = "{$name}_{$x}";
			$html .= $this->checkbox($name, 1, in_array($name, $checkeds), $attrs);
			++$x;
		}
		if ($container !== false) {
			$html = $this->Html->tag($container, $html,  $container_attrs);
		}
		return $html;
	}

	/**
	 * Agraga boton con icono de TTB
	 * @param type $text
	 * @param type $icon
	 * @param Array $attrs
	 * @return type
	 */
	public function icon_button($text, $icon, Array $attrs = array()) {
		$_attrs = array(
			'tag' => 'a'
		);
		$attrs = array_merge($_attrs, $attrs);
		$attrs['icon'] = $icon;
		return $this->button($text, $attrs);
	}

	/**
	 * Agraga boton submit con icono de TTB
	 * @param type $text
	 * @param type $icon
	 * @param Array $attrs
	 * @return type
	 */
	public function icon_submit($text, $icon, Array $attrs = array()) {
		$_attrs = array(
			'tag' => 'a'
		);
		$attrs = array_merge($_attrs, $attrs);
		$attrs['icon'] = $icon;
		return $this->submit($text, $attrs);
	}

	/**
	 * Agraga boton estandar de TTB
	 * @param type $text
	 * @param Array $attrs
	 * @return type
	 */
	public function button($text, Array $attrs = array()) {
		$_attrs = array(
			'tag' => 'a',
			'role' => 'button',
			'aria-disabled' => 'false',
			'class' => 'btn ui-button ui-widget ui-state-default ui-corner-all form-btn'
		);
		if (isset($attrs['class'])) {
			$_attrs['class'] .= " {$attrs['class']}";
			unset($attrs['class']);
		}
		$attrs = array_merge($_attrs, $attrs);

		$tag = $attrs['tag'];
		unset($attrs['tag']);
		if ($tag === 'a') {
			$attrs['href'] = 'javascript:void(0)';
			if (!isset($attrs['title']) || $attrs['title'] !== false) {
				$attrs['title'] = $text;
			}
		}
		$span_icon = '';
		if (!empty($attrs['icon'])) {
			$span_icon = $this->Html->tag('span', null, array('class' => "ui-button-icon-primary ui-icon {$attrs['icon']}"));
			$attrs['class'] .= ' ui-button-text-icon-primary';
			unset($attrs['icon']);
		} else {
			$attrs['class'] .= ' ui-button-text-only';
		}
		$span_text = $this->Html->tag('span', $text, array('class' => 'ui-button-text'));
		$this->scripts[] = 'button';
		return $this->Html->tag($tag, $span_icon . $span_text, $attrs);
	}

	/**
	 * Crea boton submit
	 * @param type $text
	 * @param array $attrs
	 * @return type
	 */
	public function submit($text, Array $attrs = array()) {
		$attrs['onclick'] = isset($attrs['onclick']) ? $attrs['onclick'] : '';
		$attrs['onclick'] .= ";jQuery(this).closest('form').submit();";
		return $this->button($text, $attrs);
	}

	/**
	 * Crea in link que contiene una imagen
	 * @param type $image
	 * @param type $link
	 * @param array $attrs
	 * @return type
	 */
	public function image_link($image, $link, Array $attrs = array()) {
		$image = $this->Html->img("{$this->image_path}{$image}");
		$_attrs = array(
			'href' => $link === false ? 'javascript:void(0)' : $link
		);
		$attrs = array_merge($_attrs, $attrs);
		return $this->Html->tag('a', $image, $attrs);
	}

	/**
	 * Genera una etiqueta para mostrar ayuda
	 * @param type $tooltip
	 * @param type $text
	 */
	public function help($tooltip, $text = null) {
		if (empty($text)) {
			$text = $this->Html->tag('span', '', array('class' => 'ui-icon ui-icon-help'));;
		}
		echo $this->Html->tag('span', $text, array('title' => $tooltip, 'class' => 'help'));
	}

	/**
	 * Crea label con class error para jQuery validator
	 * @param type $for
	 * @param array $attrs
	 */
	public function error_label($for, Array $attrs = array()) {
		$_attrs = array_merge(array('class' => 'error', 'style' => 'display:none'), $attrs);
		echo $this->label('', $for, $_attrs);
	}

	/**
	 *
	 * @return type
	 */
	public function script() {
		$scripts = array_unique($this->scripts);
		$script_block = '';
		foreach ($scripts as $script) {
			if (method_exists($this, "{$script}_script")) {
				$script_block .= $this->{"{$script}_script"}() . "\n";
			}
		}
		return $this->Html->script_block($script_block);
	}

	private function button_script() {
		$script = <<<SCRIPT
			jQuery('.form-btn').on('mouseover', function() {
				jQuery(this).addClass('ui-state-hover');
			});
			jQuery('.form-btn').on('mouseout', function() {
				jQuery(this).removeClass('ui-state-hover');
			});
SCRIPT;
		return $script;
	}

}

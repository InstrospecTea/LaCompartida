<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

class Form {

	public $Utiles;
	public $Html;
	protected $scripts = array();

	public function __construct() {
		$this->Utiles = new \TTB\Utiles();
		$this->Html = new \TTB\Html();
	}

	/**
	 * Construye un select a partir de un Array
	 * @param type $name
	 * @param type $options
	 * @param type $selected
	 * @param type $attrs
	 * @return type
	 */
	public function select($name, $options, $selected = null, $attrs = null) {
		$_attrs = (Array) $attrs + array('empty' => '');
		if (empty($_attrs['name'])) {
			$_attrs['name'] = $name;
		}
		if (empty($_attrs['id'])) {
			$_attrs['id'] = $this->Utiles->pascalize($_attrs['name']);
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
	 * @return type
	 */
	public function label($text, $for = null, $attrs = null) {
		$_attrs = (Array) $attrs;
		if (!empty($for)) {
			$_attrs['for'] = $for;
		}
		return $this->Html->tag('label', $text, $_attrs);
	}

	/**
	 *
	 * @param type $name
	 * @param type $value
	 * @param type $attrs
	 * @return type
	 */
	public function input($name, $value, $attrs = null) {
		$attrs = (Array) $attrs + array('type' => 'text', 'value' => $value, 'label' => true, 'name' => null);
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
		if (empty($attrs['id']) && !empty($attrs['name'])) {
			$attrs['id'] = $this->Utiles->pascalize($attrs['name']);
		}
		unset($attrs['label']);
		$input = $this->Html->tag('input', null, $attrs, true);
		return empty($label) ? $input : $this->label($label, $attrs['name']) . $input;
	}

	/**
	 * Devuelve elemento checkbox
	 * @param type $name
	 * @param type $value
	 * @param type $checked
	 * @param type $attrs
	 */
	public function checkbox($name, $value, $checked = false, $attrs = null) {
		$attrs = (Array) $attrs + array('type' => 'checkbox', 'value' => $value, 'label' => true);
		$label = null;

		if ($attrs['label'] === true) {
			$label = $this->Utiles->humanize($name);
		} else if ($attrs['label'] !== false) {
			$label = $attrs['label'];
		}
		unset($attrs['label']);
		if (empty($attrs['name'])) {
			$attrs['name'] = $name;
		}
		if (empty($attrs['id'])) {
			$attrs['id'] = $this->Utiles->pascalize($attrs['name']);
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
	 * @param type $attrs
	 */
	public function radio($name, $value, $checked = false, $attrs = null) {
		$attrs = (Array) $attrs + array('type' => 'radio', 'value' => $value, 'label' => true);
		$label = null;

		if ($attrs['label'] === true) {
			$label = $this->Utiles->humanize($name);
		} else if ($attrs['label'] !== false) {
			$label = $attrs['label'];
		}
		unset($attrs['label']);
		if (empty($attrs['name'])) {
			$attrs['name'] = $name;
		}
		if (empty($attrs['id'])) {
			$attrs['id'] = $this->Utiles->pascalize($attrs['name']);
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
	public function radio_group($name, $options, $selected, $container = 'div', $container_attrs = null) {
		$html = '';
		$x = 1;
		foreach ((Array) $options as $value => $label) {
			$_attrs = array();
			if (is_array($label)) {
				$_attrs = $label;
				$label = empty($_attrs['label']) ? $this->Utiles->pascalize($name) : $_attrs['label'];
				unset($_attrs['label']);
			}
			$attrs = array('label' => true) + (Array) $_attrs;
			if ($attrs['label'] === false) {
				$value = $label;
			} else if ($attrs['label'] === true) {
				$attrs['label'] = $label;
			}
			$attrs['id'] = $this->Utiles->pascalize($name . $x);
			$html .= $this->radio($name, $value, $value == $selected, $attrs);
			++$x;
		}
		if ($container !== false) {
			$html = $this->Html->tag($container, $html, (Array) $container_attrs);
		}
		return $html;
	}

	/**
	 * Agraga boton con icono de TTB
	 * @param type $text
	 * @param type $icon
	 * @param type $attrs
	 * @return type
	 */
	public function icon_button($text, $icon, $attrs = null) {
		$_attrs = array(
			'tag' => 'a'
		);
		$attrs = array_merge($_attrs, (array) $attrs);
		$attrs['icon'] = $icon;
		return $this->button($text, $attrs);
	}

	/**
	 * Agraga boton submit con icono de TTB
	 * @param type $text
	 * @param type $icon
	 * @param type $attrs
	 * @return type
	 */
	public function icon_submit($text, $icon, $attrs = null) {
		$_attrs = array(
			'tag' => 'a'
		);
		$attrs = array_merge($_attrs, (array) $attrs);
		$attrs['icon'] = $icon;
		return $this->submit($text, $attrs);
	}

	/**
	 * Agraga boton estandar de TTB
	 * @param type $text
	 * @param type $attrs
	 * @return type
	 */
	public function button($text, $attrs = null) {
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
		$attrs = array_merge($_attrs, (array) $attrs);

		$tag = $attrs['tag'];
		unset($attrs['tag']);
		if ($tag === 'a') {
			$attrs['href'] = 'javascript:void(0)';
			if (empty($attrs['title']) && $attrs['title'] !== false) {
				$attrs['title'] = $text;
			}
		}
		$span_icon = '';
		if ($attrs['icon']) {
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

	public function submit($text, $attrs = null) {
		$attrs['onclick'] = isset($attrs['onclick']) ? $attrs['onclick'] : '';
		$attrs['onclick'] .= ";jQuery(this).closest('form').submit();";
		return $this->button($text, $attrs);
	}

	/**
	 *
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

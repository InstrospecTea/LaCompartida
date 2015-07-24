<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

/**
 * Conjunto de helpers para crear input select
 * con funcionalidad adicional
 */
class FormSelectHelper {

	public $Utiles;
	public $Html;

	public function __construct() {
		$this->Form = new Form;
		$this->Utiles = new \TTB\Utiles();
		$this->Html = new \TTB\Html();
	}

	/**
	 * Construye un selector ajax
	 *
	 * Consiste en un elemento <select> cuyas opciones
	 * se cargan vía Ajax en un determinado endpoint
	 *
	 * @param string $name Es el nombre del elemento HTML
	 * @param string $selected Es el valor por defecto al presentarlo
	 * @param array $attrs Son los atributos del elemento HTML Ej. id, class, type
	 * @param array $options Son opciones entre las que se encuentran
	 *                       * onChange: código js a ejecutar cuando cambia el elemento
	 *                       * source: string url del endpoint donde se obtiene la data
	 *                       * onSource: código js a ejecutar antes del request de data
	 *                                   aquí puede modificarse la URL de data
	 *                       * onLoad: código js a ejecutar cuando termina de cargarse la data
	 *                       * multiple: en caso que el selector sea múltiple (usa choosen.js)
	 * @param array $default_list Son los datos por default para generar elementos <option />
	 *
	 * @return string HTML con el selector <select />
	 */
	public function ajax_select($name, $selected, $attrs = array(), $options = array(), $default_list = array()) {
		$id = empty($attrs['id']) ? $name : $attrs['id'];
		$attrs['empty'] = 'Cargando...';
		$output = $this->Form->select($name, $default_list, $selected, $attrs);
		$output .= $this->Html->script_block($this->scripts($id, $selected, $options));
		return $output;
	}

	/**
	 * Construye un listado de checkboxes
	 *
	 * Simula un multiselect se cargan vía Ajax en un determinado endpoint
	 *
	 * @param string $name Es el nombre del elemento HTML
	 * @param array $list Son los datos por default para generar elementos <option />
	 * @param array $selected Son los valores seleccionados por default
	 * @param array $attrs Son los atributos del elemento HTML Ej. id, class, type
	 * @param array $options Son opciones entre las que se encuentran
	 *                       * onChange: código js a ejecutar cuando cambia el elemento
	 *                       * source: string url del endpoint donde se obtiene la data
	 *                       * onSource: código js a ejecutar antes del request de data
	 *                                   aquí puede modificarse la URL de data
	 *                       * onLoad: código js a ejecutar cuando termina de cargarse la data
	 *                       * autoload: establece si se carga al iniciar o a petición
	 *
	 * @return string HTML con todos los checkboxes o con el wrapper a la espera de la carga
	 */
	public function checkboxes($name, $list = array(), $selected = array(), $attrs = array(), $options = array()) {
		$container_name = $name . '_container';
		$id = empty($attrs['id']) ? $name : $attrs['id'];
		$container_attrs = array('name' => $container_name, 'id' => $container_name);
		$output = $this->Html->tag('div', '', $container_attrs);
		$output .= $this->Html->script_block($this->checkboxes_scripts($name, $container_name, $selected, $options));
		return $output;
	}

	private function checkboxes_scripts($name, $container_name, $selected, $options = array()) {
		$onChange = $options['onChange'] ? $options['onChange'] : '';
		$onLoad = $options['onLoad'] ? $options['onLoad'] : '';
		$onSource = $options['onSource'] ? $options['onSource'] : '';
		$source = $options['source'] ? $options['source'] : '';
		$autoload = $options['autoload'] === false ? 'false' : 'true';
		$selected_values = json_encode($selected);
		$check_id = "{$name}[]";
		$script = <<<SCRIPT
			if (typeof FormSelectHelper == 'undefined') {
				var FormSelectHelper = {}
			}
			jQuery(document).ready(function() {
				var data_$name = [];
				var selected_$name = {};
				var selected_values_$name = $selected_values;
				FormSelectHelper.reload_$name = function(callback) {
					var source = "$source";
					$onSource;
					jQuery.post(source, {}, function(data) {
						data_$name = data;
						jQuery('#{$container_name}').empty();
						var line = 0;
						data_$name.forEach (function(data, index) {
							var id = data_{$name}[index].id || index;
							var input = jQuery('<input/>').attr('id', '$check_id').attr('name', '$check_id').attr('type', 'checkbox').val(id);
							var option = jQuery('<label/>').text(data_{$name}[index].glosa || data_{$name}[index]).prepend(input);
							option.addClass('column_2');

							if (jQuery.inArray(id, selected_values_$name) >= 0) {
								input.attr('checked', 'checked')
								var checked = true;
								selected_$name = data_{$name}[index];
								$onChange
							}
							jQuery('#{$container_name}').append(option);
							line++;
							if (line == 3) {
								jQuery('#{$container_name}').append(jQuery('<br/>'));
								line = 0;
							}
						});
						$onLoad
						if (callback) {
							callback()
						}
					}, "json");
				};
				function getArrayIndexForKey(arr, key, val){
						for(var i = 0; i < arr.length; i++){
								if(arr[i][key] == val)
										return i;
						}
						return -1;
				};
				jQuery('#{$container_name}').delegate('[name="$check_id"]', 'change', function () {
					var checked = this.checked
					var id = jQuery(this).val();
					var index = getArrayIndexForKey(data_{$name}, 'id', id);
					selected_$name = data_{$name}[index];
					$onChange
				});
				if ($autoload) {
					FormSelectHelper.reload_$name();
				}
			});
SCRIPT;
		return $script;
	}

	private function scripts($name, $selected, $options = array()) {
		$onChange = $options['onChange'] ? $options['onChange'] : '';
		$onLoad = $options['onLoad'] ? $options['onLoad'] : '';
		$onSource = $options['onSource'] ? $options['onSource'] : '';
		$source = $options['source'] ? $options['source'] : '';
		$extra_script = $options['multiple'] ? "jQuery('#{$name}').chosen()" : '';
		$selected_name = $options['selectedName'] ? $options['selectedName'] : 'selected_' . $name;
		$script = <<<SCRIPT
			var data_$name = [];
			var $selected_name = {};
			if (typeof FormSelectHelper == 'undefined') {
				var FormSelectHelper = {}
			}
			jQuery(document).ready(function() {
				FormSelectHelper.reload_$name = function() {
					var source = "$source";
					var exists_selected = jQuery("#{$name}").val();
					FormSelectHelper.original_$name = '$selected';
					$onSource;
					jQuery.post(source, {}, function(data) {
						data_$name = data;
						jQuery('#{$name}').empty().append(jQuery('<option/>'));

						if (!jQuery.isEmptyObject(data)) {
							data_$name.forEach(function(data, index) {
								var id = data_{$name}[index].id || index;
								var option = jQuery('<option/>').val(id).text(data_{$name}[index].glosa || data_{$name}[index]);
								if ('$selected' == id || exists_selected == id) {
									option.attr('selected', 'selected')
									$selected_name = data_{$name}[index];
								}
								jQuery('#{$name}').append(option);
							});
						}

						if ($selected_name) {
							$onChange
						}

						$extra_script;
					}, 'json');
				}
				jQuery('#{$name}').change(function() {
					var key = null;

					i = jQuery('#{$name} option:selected').val();
					first = data_{$name}[Object.keys(data_{$name})[0]]

					if (data_{$name}[i] && !first.hasOwnProperty('id')) {
						$selected_name = data_{$name}[i];
					} else {
						for(j in data_{$name}) {
							if (data_{$name}[j].id && data_{$name}[j].id == i) {
								key = j;
							}
						}
					}
					$selected_name = data_{$name}[key];
					$onChange
				});
				FormSelectHelper.reload_$name();
			});
SCRIPT;
		return $script;
	}

}

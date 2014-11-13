<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

class FormSelectHelper {

  public $Utiles;
  public $Html;
  
  public function __construct() {
    $this->Form = new Form;
    $this->Utiles = new \TTB\Utiles();
    $this->Html = new \TTB\Html();
  }

  /**
   * Construye un autocompletador simple
   * @param type $name
   * @param type $value
   * @param type $attrs
   * @param type $options
   * @return type
   */
  public function ajax_select($name, $selected, $attrs = array(), $options = array(), $default_list = array()) {
    $id = empty($attrs['id']) ? $this->Utiles->pascalize($name) : $attrs['id'];
    $attrs['empty'] = 'Cargando...';
    $output = $this->Form->select($name, $default_list, $selected, $attrs);
    $output .= $this->Html->script_block($this->scripts($id, $selected, $options));
    return $output;
  }

   /**
   * Construye un autocompletador simple
   * @param type $name
   * @param type $value
   * @param type $attrs
   * @param type $options
   * @return type
   */
  public function multi_select($name, $selected = array(), $attrs = array(), $options = array(), $default_list = array()) {
    $attrs['multiple'] = 'multiple';
    $options['multiple'] = true;
    $output = $this->ajax_select($name, $selected, $attrs, $options, $default_list = array());
    return $output;
  }

  /**
   * Construye un listado de checkboxes, simula multiselect
   * @param type $name
   * @param type $value
   * @param type $attrs
   * @param type $options
   * @return type
   */
  public function checkboxes($name, $list = array(), $selected = array(), $attrs = array(), $options = array()) {
    $container_name = $name . '_container';
    $id = empty($attrs['id']) ? $this->Utiles->pascalize($name) : $attrs['id'];
    $container_attrs = array('name' => $container_name, 'id' => $container_name);
    $output = $this->Form->checkbox_group($name . '[]', $list, $selected, 'div', $container_attrs);
    $output .= $this->Html->script_block($this->checkboxes_scripts($name, $container_name, $selected, $options));
    return $output;
  }

  private function checkboxes_scripts($name, $container_name, $selected, $options = array()) {
    $onChange = $options['onChange'] ? $options['onChange'] : '';
    $onLoad =  $options['onLoad'] ? $options['onLoad'] : '';
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
        FormSelectHelper.reload_$name = function() {
          var source = "$source";
          $onSource;
          jQuery.post(source, {}, 
            function(data) {
              data_$name = data;
              jQuery('#{$container_name}').empty();
              var line = 0;
              for (key in data_$name) {
                var input = jQuery('<input/>').attr('id', '$check_id').attr('name', '$check_id').attr('type', 'checkbox').val(key);
                var option = jQuery('<label/>').text(data_{$name}[key].glosa || data_{$name}[key]).prepend(input);
                option.addClass('column_2');
                if (jQuery.inArray(key, selected_values_$name) >= 0) {
                  input.attr('checked', 'checked')
                  var checked = true
                  selected_$name = data_{$name}[key];
                  $onChange
                }
                jQuery('#{$container_name}').append(option);
                line++;
                if (line == 3) {
                  jQuery('#{$container_name}').append(jQuery('<br/>'));
                  line = 0;
                }
              }
              $onLoad
            }, 
          "json");
        }
        jQuery('#{$container_name}').delegate('[name="$check_id"]', 'change', function () {
          var checked = this.checked
          var key = jQuery(this).val();
          selected_$name = data_{$name}[key];
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
    $onLoad =  $options['onLoad'] ? $options['onLoad'] : '';
    $onSource = $options['onSource'] ? $options['onSource'] : '';
    $source = $options['source'] ? $options['source'] : '';
    $extra_script = $options['multiple'] ? "jQuery('#{$name}').chosen()" : '';
    $script = <<<SCRIPT
      if (typeof FormSelectHelper == 'undefined') {
        var FormSelectHelper = {}
      }
      jQuery(document).ready(function() {
        var data_$name = [];
        var selected_$name = {};
        FormSelectHelper.reload_$name = function() {
          var source = "$source";
          $onSource;
          jQuery.post(source, {}, 
            function(data) {
              data_$name = data;
              jQuery('#{$name}').empty().append(jQuery('<option/>'));
              for (key in data_$name) {
                var option = jQuery('<option/>').val(key).text(data_{$name}[key].glosa || data_{$name}[key]);
                if ('$selected' == key) {
                  option.attr('selected', 'selected')
                  selected_$name = data_{$name}[key];
                }
                jQuery('#{$name}').append(option);
              }
              if (selected_$name) {
                $onChange
              }
              $extra_script;
            }, 
          "json");
        }
        jQuery('#{$name}').change(function() {
          key = jQuery('#{$name} option:selected').val();
          selected_$name = data_{$name}[key];
          $onChange
        });
        FormSelectHelper.reload_$name();
      });
SCRIPT;
    return $script;
  }

}

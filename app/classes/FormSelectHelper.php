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
    $output = $this->Form->select($id, $default_list, $selected, $attrs);
    $output .= $this->Html->script_block($this->scripts($id, $selected, $options));
    return $output;
  }

  private function scripts($name, $selected, $options = array()) {
    $onChange = $options['onChange'] ? $options['onChange'] : '';
    $onLoad =  $options['onLoad'] ? $options['onLoad'] : '';
    $source = $options['source'] ? $options['source'] : '';
    $script = <<<SCRIPT
      jQuery(document).ready(function() {
        var data_$name = [];
        var selected_$name = {};
        jQuery.post("{$source}", {}, 
          function(data) {
            data_$name = data;
            jQuery('#{$name}').empty().append(jQuery('<option/>'));
            for (key in data_$name) {
              var option = jQuery('<option/>').val(key).text(data_{$name}[key].glosa);
              if ('$selected' == key) {
                option.attr('selected', 'selected')
                selected_$name = data_{$name}[key];
              }
              jQuery('#{$name}').append(option);
            }
            $onLoad
          }, 
        "json");
        jQuery('#{$name}').change(function() {
          key = jQuery('#{$name} option:selected').val();
          selected_$name = data_{$name}[key];
          $onChange
        });
      });
SCRIPT;
    return $script;
  }

}

<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

class FormAutocompleteHelper {

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
  public function simple_complete($name, $value, $attrs = null, $options = array()) {
    $attrs = is_null($attrs) ? array() : $attrs;
    $attrs['data-autoselect'] = '1';
    $id = empty($attrs['id']) ? $this->Utiles->pascalize($name) : $attrs['id'];
    $output = $this->Form->input($name, $value, $attrs);
    $output .= $this->Html->script_block($this->scripts($id, $options));

    return $output;
  }
 
  private function scripts($name, $options = array()) {
    $onChange = $options['onChange'] ? $options['onChange'] : '';
    $onSource = $options['onSource'] ? $options['onSource'] : '';
    $onSelect = $options['onSelect'] ? $options['onSelect'] : '';
    $source = $options['source'] ? $options['source'] : '';
    $minLength = $options['minLength'] ? $options['minLength'] : '1';
    $script = <<<SCRIPT
      jQuery(document).ready(function() {
        jQueryUI.done(function() {
          jQuery('#{$name}').autocomplete({
            source: function(request, response) {
              source = "$source";
              $onSource;
              jQuery.post(source, {term: request.term}, 
                function(data) {
                  response(data);
                }, 
              "json");
            },
            minLength: $minLength,
            select: function(event, ui) {
              jQuery('#{$name}').val(ui.item.value);
              $onSelect;
            },
            change: function (event, ui) {
              if (!ui.item && !jQuery('#{$name}').val().length) {
                $onChange;
              }
            }
          });
        }).fail(function(e){ console.log(e)});
      });
SCRIPT;
    return $script;
  }

}

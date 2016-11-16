<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

 /**
  * Esta clase prove helpers para crear autocompletadores
  * en formularios HTML
  */
class FormAutocompleteHelper {

  public $Html;

  public function __construct() {
    $this->Form = new Form;
    $this->Html = new \TTB\Html();
  }

  /**
   * Construye un autocompletador simple
   *
   * Que consiste en un input text que al escribir
   * presenta un listado de opciones seleccionables
   *
   * @param string $name Es el nombre del elemento HTML
   * @param string $value Es el valor por defecto al presentarlo
   * @param array $attrs Son los atributos del elemento HTML Ej. id, class, type
   * @param array $options Son opciones entre las que se encuentran
   *                       * onChange: string de código js a ejecutar cuando cambia el elemento
   *                       * onSource: string de código js a ejecutar antes del request de data
   *                       * onSelect: string de código js a ejecutar cuando usuario selecciona
   *                       * source: string url del endpoint donde se obtiene la data
   *                       * minLenght: mínimo de caracteres necesarios para autocompeltar
   *
   * @return string
   */
  public function simple_complete($name, $value, $attrs = null, $options = array()) {
    $attrs = is_null($attrs) ? array() : $attrs;
    $attrs['data-autoselect'] = '1';
    $id = empty($attrs['id']) ? Utiles::pascalize($name) : $attrs['id'];
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

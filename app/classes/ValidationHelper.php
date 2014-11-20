<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

class ValidationHelper {

  public $Utiles;
  public $Html;
  protected $scripts = array();
  protected $validations = array();

  public function __construct($Sesion, $options) {
    $this->Utiles = new \TTB\Utiles();
    $this->Html = new \TTB\Html();
    $this->validations = array();
    $this->options = $options;
  }

  public function validate() {
    if ($this->options && $this->options['disableServer'] == true) {
      return true;
    }
    foreach ($this->validations as $key => $validation) {
      if ($this->validationSkipped($key)) {
        continue;
      }
      if (!is_null($validation['server']))  {
        $validation['server']($validation['value']);
      }
    }
    return false;
  }
  
  public function getClientValidationsScripts() {
    if ($this->options && $this->options['disableClient'] == true) {
      return;
    }
    $validations_script = "";
    $validateClientScript = $this->options['validateClient'] ? $this->options['validateClient'] : 'true';
    foreach ($this->validations as $key => $validation) {
      if ($this->validationSkipped($key)) {
        continue;
      }
      if (!is_null($validation['client'])) {
        $original_script = $validation['client']($key);
        $validation_script = <<<SCRIPT
        if ($validateClientScript) {
          $original_script;
        }
SCRIPT;
        $validations_script .= $validation_script;
      }
    }
    return $validations_script;
  }

  public function registerValidation($field, $validation) {
    $this->validations[$field] = $validation;
  }

  public function validationSkipped($key) {
    if ($this->options && $this->options['skipped'] && !empty($this->options['skipped'])) {
      $skipped = $this->options['skipped'];
      return (in_array($key, $skipped));
    } else {
      return false;
    }
  }
}

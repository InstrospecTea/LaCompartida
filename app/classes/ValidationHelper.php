<?php

require_once dirname(__FILE__) . '/../../app/conf.php';

 /**
  * Helper para correr validaciones en el front Legacy
  * 
  * Permite definir validaciones de servidor y cliente de manera 
  * que se puedan unificar y reutilizar sobre todo en interfaces
  * que sirven como partials para otras.
  * 
  */
class ValidationHelper {

  public $Utiles;
  public $Html;
  protected $scripts = array();
  protected $validations = array();

  /**
   * Constructor
   * @param object $Sesion Sesión activa
   * @param array $options opciones de configuración:
   *                       * skipped: array con la lista de atributos que no se validan
   *                       * disableServer: boolean establece si se deshabilitan las 
   *                                         validaciones del lado del servidor
   *                       * disableClient: boolean establece si se deshabilitan las 
   *                                         validaciones del lado del servidor
   *                       * validateClient: string código js que se ejecuta antes de cada validación
   */
  public function __construct($Sesion, $options) {
    $this->Utiles = new \TTB\Utiles();
    $this->Html = new \TTB\Html();
    $this->validations = array();
    $this->options = $options;
  }

  /**
   * Registra una nueva validación
   * @param  string $field      nombre del campo a validar, debe coincidir con el elemento del form
   * @param  array $validation  establece las reglas de valicación
   *                            * server: function Lamda con la validación del lado del servidor
   *                            * value: object Valor o variable que contiene el valor en el servidor
   *                            * client: string Código JS validación del lado del cliente
   * <code>
   *  $options = array(
   *    
   *  )
   * </code>
   * 
   * @return void
   */
  public function registerValidation($field, $validation) {
    $this->validations[$field] = $validation;
  }

  /**
   * verifica si una validación debe ser saltada
   * 
   * @param  string $key [description]
   * @return [type]      [description]
   */
  public function validationSkipped($key) {
    if ($this->options && $this->options['skipped'] && !empty($this->options['skipped'])) {
      $skipped = $this->options['skipped'];
      return (in_array($key, $skipped));
    } else {
      return false;
    }
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

}

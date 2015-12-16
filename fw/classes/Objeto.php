<?php

require_once dirname(__FILE__) . '/../classes/Utiles.php';

class Objeto {

	// Sesion PHP
	var $sesion = null;
	// Arreglo con los valores de los campos
	var $fields = null;
	var $editable_fields = array();
	var $extra_fields = array();
	// Arreglo que indica los campos con cambios
	var $changes = null;
	// String con el último error
	var $error = '';
	var $tabla = '';
	var $campo_id = '';
	var $campo_glosa = '';
	// Si es true, guarda la fecha de creacion y de modificación
	var $guardar_fecha = true;
	// Arreglo que contiene los keys o nombres de campo que no se deben editar
	var $no_editar = array();
	// Para loggin
	var $logear = null;
	var $log_field = null;

	var $fields_allow_null = array();

	// Diccionario donde se guardaran las keys de los valores cacheados
	private static $___cache = array();

	/**
	 * Todas las clases debe heredar de la clase objeto. La clase objeto tiene los metodos base para leer/escribir en la base de datos.
	 * @param objet $sesion       Variable sesión
	 * @param string $fields      Campos precargados
	 * @param string $params      Deprecated?
	 * @param string $tabla       Nombre de la tabla
	 * @param string $campo_id    PK de la tabla
	 * @param string $campo_glosa Nombre de PK
	 */
	public function __construct($sesion, $fields = '', $params = '', $tabla = '', $campo_id = '', $campo_glosa = '') {
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->tabla = $tabla;
		$this->campo_id = $campo_id;
		$this->campo_glosa = $campo_glosa;

		$this->setFieldsAllowNull();
	}

	/**
	 * set field in true if allow null
	 */
	public function setFieldsAllowNull() {
		// describe table
		$rs = mysql_query("DESC {$this->tabla}", $this->sesion->dbh);
		while ($field = mysql_fetch_assoc($rs)) {
			$this->fields_allow_null[$field['Field']] = $field['Null'] == 'YES';
		}
	}

	/**
	 * Edita el valor de un campo
	 * @param string  $field campo de la tabla
	 * @param mix  $value valor que se asignará
	 * @param boolean $log_field si es verdadero entonces se guarda el historial del cambio
	 */
	public function Edit($field, $value, $log_field = false) {
		if ((isset($this->log_update) && $this->log_update == true) || $log_field == true) {
			if (isset($this->fields[$field]) && $this->fields[$field] != $value) {
				if (($value != 'NULL' || ($this->fields[$field]) != '')) {
					if ((empty($this->fields[$field])) == false || empty($value) == false) {
						$this->logear[$field] = true;
						$this->valor_antiguo[$field] = $this->fields[$field];
					}
				}
			}
		}

		$this->fields[$field] = $value;
		$this->changes[$field] = true;
	}

	/**
	 * Carga un registro en particular
	 * @param int $id PK de la tabla
	 * @param array | string $fields campos que se quieren cargar en la consulta
	 * @return boolean verdadero si se pudo ejecutar correctamente la query, de lo contrario retorna falso
	 */
	public function Load($id, $fields = null) {
		if (empty($fields)) {
			// TODO: describe de la tabla para obtener los campos
			$select = '*';
		} else {
			$select = is_array($fields) ? implode(',', $fields) : $fields;
		}

		$query = "SELECT {$select} FROM {$this->tabla} WHERE {$this->campo_id} = '{$id}'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if ($this->fields = mysql_fetch_assoc($resp)) {
			return true;
		}

		$this->error = 'No existe el objeto buscado en la base de datos';
		return false;
	}

	/**
	 * La funcion Write chequea que el objeto se pueda escribir al llamar a la funcion Check()
	 * @return boolean retorna verdadero si se pudo guardar el registro, de lo contrario es falso
	 */
	public function Write() {
		setlocale(LC_NUMERIC, 'en_US');

		$this->setFieldsAllowNull();

		$this->error = '';
		// Prepare de la clase
		$this->Prepare();

		// Revisar que este todo ok para guardar
		if (!$this->Check()) {
			return false;
		}

		$query_log = "INSERT INTO log_db SET
			id_field =:idfield,
			titulo_tabla =:titulotabla,
			campo_tabla =:campotabla,
			fecha = NOW(),
			usuario = :idusuario,
			valor_antiguo = :valorantiguo,
			valor_nuevo =:valornuevo";
		$logstatement = $this->sesion->pdodbh->prepare($query_log);

		$valores = array();

		// Es un UPDATE o INSERT
		$es_update = $this->Loaded();

		if ($es_update) {
			$query = "UPDATE {$this->tabla} SET ";
			if ($this->guardar_fecha) {
				$valores[] = 'fecha_modificacion = NOW()';
			}
		} else {
			$query = "INSERT INTO {$this->tabla} SET ";
			if ($this->guardar_fecha) {
				$valores[] = 'fecha_creacion = NOW()';
			}
		}

		// Preparar los valores
		foreach ($this->fields as $key => $value) {
			if ($this->changes[$key]) {
				$do_update = true;

				if (strtoupper($value) === 'NULL' || is_null($value)) {
					// if the given value is null, and the field in the data base not allow a null value, then assing the default value by data base
					if ($this->fields_allow_null[$key]) {
						$valores[] = "{$key} = NULL";
					} else {
						$valores[] = "{$key} = ''";
					}
				} else {
					$value = mysql_real_escape_string($value);
					$valores[] = "{$key} = '{$value}'";
				}

				// log data on update
				if ($es_update && $this->logear[$key]) {
					try {
						$logstatement->bindParam(':idfield', $this->fields[$this->campo_id], PDO::PARAM_INT);
						$logstatement->bindParam(':titulotabla', $this->tabla, PDO::PARAM_STR);
						$logstatement->bindParam(':campotabla', $key, PDO::PARAM_STR);
						$logstatement->bindParam(':idusuario', intval($this->sesion->usuario->fields['id_usuario']), PDO::PARAM_INT);
						$valorantiguo = (string) $this->valor_antiguo[$key];
						$valornuevo = (string) $value;
						$logstatement->bindParam(':valorantiguo', $valorantiguo, PDO::PARAM_STR);
						$logstatement->bindParam(':valornuevo', $valornuevo, PDO::PARAM_STR);
						$logstatement->execute();
					} catch (PDOException $e) {
						if ($this->sesion->usuario->fields['rut'] == '99511620') {
							$Slim = Slim::getInstance('default', true);
							$arrayPDOException = array(
								'File' => $e->getFile(),
								'Line' => $e->getLine(),
								'Mensaje' => $e->getMessage(),
								'Query' => $query_log,
								'Trace' => json_encode($e->getTrace()),
								'Parametros' => json_encode($logstatement)
							);
							$Slim->view()->setData($arrayPDOException);
							$Slim->applyHook('hook_error_sql');
						}

						Utiles::errorSQL($query_log, '', '', NULL, '', $e);
						return false;
					}

					$this->logear[$key] = false;
				}
			}
		}

		$query .= implode(', ', $valores);

		if ($this->Loaded()) {
			$query .= " WHERE {$this->campo_id} = '{$this->fields[$this->campo_id]}'";
		}

		// Si no hay nada que actualizar, no hago nada
		if ($es_update && !$do_update) {
			return true;
		}

		try {
			$this->sesion->pdodbh->query($query);

			if (!$es_update) {
				$insertid = $this->sesion->pdodbh->lastInsertId();
			}
		} catch (PDOException $e) {
			if ($this->sesion->usuario->fields['rut'] == '99511620') {
				$Slim = Slim::getInstance('default', true);
				$arrayPDOException = array(
					'File' => $e->getFile(),
					'Line' => $e->getLine(),
					'Mensaje' => $e->getMessage(),
					'Query' => $query,
					'Trace' => json_encode($e->getTrace()),
					'Parametros' => json_encode($arrayparamsdebug)
				);

				$Slim->view()->setData($arrayPDOException);
				$Slim->applyHook('hook_error_sql');
			}

			Utiles::errorSQL($query, '', '', NULL, '', $e);
			return false;
		}

		if (!$es_update) {
			$this->fields[$this->campo_id] = $insertid;

			if ($this->log_update == true) {
				try {
					$logstatement->bindParam(':idfield', $this->fields[$this->campo_id], PDO::PARAM_INT);
					$logstatement->bindParam(':titulotabla', $this->tabla, PDO::PARAM_STR);
					$logstatement->bindParam(':campotabla', $this->campo_id, PDO::PARAM_STR);
					$logstatement->bindParam(':idusuario', $this->sesion->usuario->fields['id_usuario'], PDO::PARAM_INT);
					$logstatement->bindValue(':valorantiguo', ' ', PDO::PARAM_STR);
					$logstatement->bindValue(':valornuevo', 'Creado con id: ' . $this->fields[$this->campo_id], PDO::PARAM_STR);

					$logstatement->execute();
				} catch (PDOException $e) {
					if ($this->sesion->usuario->fields['rut'] == '99511620') {
						$Slim = Slim::getInstance('default', true);
						$arrayPDOException = array(
							'File' => $e->getFile(),
							'Line' => $e->getLine(),
							'Mensaje' => $e->getMessage(),
							'Query' => $query_log,
							'Trace' => json_encode($e->getTrace()),
							'Parametros' => json_encode($logstatement)
						);

						$Slim->view()->setData($arrayPDOException);
						$Slim->applyHook('hook_error_sql');
					}

					Utiles::errorSQL($query_log, '', '', NULL, '', $e);
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Verifica si el registro está cargado
	 * @return boolean retorna verdadero si el registro está cargado, de lo contrario retorna falso
	 */
	public function Loaded() {
		if ($this->fields[$this->campo_id]) {
			return true;
		}

		return false;
	}

	/**
	 * Por defecto retona verdadero (no chequea nada al escribir)
	 * Esta funcion debe ser sustituida en la clase que hereda
	 */
	public function Check() {
		return true;
	}

	/**
	 * Por defecto retona verdadero (no chequea nada al eliminar)
	 * Esta funcion debe ser sustituida en la clase que hereda
	 */
	public function CheckDelete() {
		return true;
	}

	/**
	 * Prepara los valores para ser usados en el Write
	 * Este método debe ser sobreescrito por la clase que hereda
	 */
	public function Prepare() {

	}

	/**
	 * Completa el objeto con los valores que vengan en $parametros
	 *
	 * @param array $parametros entrega los campos y valores del objeto campo => valor
	 * @param boolean $edicion indica si se marcan los $parametros para edición
	 */
	public function Fill($parametros, $edicion = false) {
		foreach ($parametros as $campo => $valor) {
			if (in_array($campo, $this->editable_fields)) {
				$this->fields[$campo] = $valor;

				if ($edicion) {
					$this->Edit($campo, $valor);
				}
			} else {
				$this->extra_fields[$campo] = $valor;
			}
		}
	}

	/**
	 * Esta funcion edita todos los campos que le llegan a la pagina como parametro
	 */
	public function EditarTodos() {
		foreach ($_GET as $key => $value) {
			if (!in_array($key, $this->no_editar))
				$this->Edit($key, $value);
		}

		foreach ($_POST as $key => $value) {
			if (!in_array($key, $this->no_editar)) {
				$this->Edit($key, $value);
			}
		}
	}

	/**
	 * Edita todos los campos menos los que se manden como parametros a NoEditar()
	 * @param string $valor
	 */
	public function NoEditar($valor) {
		array_push($this->no_editar, $valor);
	}

	/**
	 * Funcion que elimina un elemento. Primero chequea que la funcion check delete retorne verdadero
	 * @return boolean
	 */
	public function Delete($id = null) {
		if (!$this->CheckDelete()) {
			return false;
		}

		if ($this->Loaded()) {
			$query = "DELETE FROM {$this->tabla} WHERE {$this->campo_id} = '{$this->fields[$this->campo_id]}'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		} else {
			return false;
		}

		return true;
	}

	/**
	 * Crear una con consulta dado varios parametros para entregar un listado.
	 *
	 * @param type $query_extra Para pasar instrucciones SQL despues del "FROM table"
	 * @param string $fields Para indicar mas columnas a traer desde las tablas
	 * @param type $fetch_by_id Verdadero, indica que el array de retorno sera contruido con el id del registro como indice de los elementos del array
	 * @return mixed Array del resultado de la consulta SQL
	 * @throws Exception
	 */
	public function Listar($query_extra = '', $fields = false, $fetch_by_id = true)
	{
		if (empty($this->campo_id) || empty($this->campo_glosa)) {
			throw new Exception("Imposible Listar {$this->tabla}");
		}

		if (preg_match('/[\(\.]/', $this->campo_glosa)) {
			$glosa = $this->campo_glosa;
		} else {
			$glosa = "{$this->tabla}.{$this->campo_glosa}";
		}

		if ($fields) {
			$fields = ',' . $fields;
		}

		$query = "SELECT
				  {$this->tabla}.{$this->campo_id} AS id,
				  {$glosa} AS glosa {$fields}
			  FROM {$this->tabla}
			  {$query_extra}";

		$qr = $this->sesion->pdodbh->query($query);
		$result = array();

		$statement = $qr->fetchAll(PDO::FETCH_ASSOC);
		foreach ($statement as $row) {
			if ($fetch_by_id) {
				$result[$row['id']] = $row['glosa'];
			} else {
				$result[] = $row;
			}
		}

		return $result;
	}

	/**
	* Realiza caching de algún objeto en una variable estática
	* transversal al proceso que está corriendo. Con esto se evitar realizar
	* múltiples queries innecesarias
	* @param string $key llave bajo la cual se guarda el valor cacheado
	* @param mixed $value valor a guardar
	* @return el valor guardado para la key
	*/
	public function setCache($key, $value){
		return self::$___cache[ $this->getKey($key) ] = $value;
	}

	/**
	* Recupera desde una variable privada estatica, el valor de una key
	* que quiso cachearse
	* @param string $key llave bajo la cual se encuentra guardado el valor cacheado
	* @return el valor guardado bajo la $key o null en caso de no existir
	*/
	public function getCache( $key ){
		if( array_key_exists($this->getKey($key), self::$___cache) ) {
			// error_log("key {$this->getKey($key)} from cache :D");
			return self::$___cache[ $this->getKey($key) ];
		} else {
			// error_log("key {$this->getKey($key)} NOT from cache D:");
			return null;
		}
	}

	/**
	* Genera las keys para hacer el caching de los valores. Se le prefija
	* el nombre de la clase que lo está utilizanod, con el fin de evitar colisiones
	* entre clases
	* @param string $key valor de la key
	* @return string valor para key, prefijado con el nombre de la clase concreta
	*/
	private function getKey( $key ) {
		$key = sprintf("%s::%s", get_class($this), $key);
		return $key;
	}

}

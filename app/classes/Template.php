<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

/**
 * Clase para manejar las solicitudes de adelanto
 */
class Template extends Objeto {

	/**
	 * Define los campos de la solicitud de adelanto permitidos para llenar
	 * 
	 * @var array 
	 */
	private $campos = array(
		'id_template',
		'glosa_template',
		'tipo',
		'documento'
	);

	/**
	 * Define los tipos posibles de un template
	 * 
	 * @var array('FACTURA','NOTA_DEBITO','NOTA_CREDITO','BOLETA','NOTA_COBRO',
	 * 'CARTA_COBRO','SOLICITUD_ADELANTO','RECIBO_PAGO')
	 */
	private static $tipos = array(
		'FACTURA', 
		'NOTA_DEBITO', 
		'NOTA_CREDITO', 
		'BOLETA', 
		'NOTA_COBRO', 
		'CARTA_COBRO', 
		'SOLICITUD_ADELANTO', 
		'RECIBO_PAGO'
	);

	/**
	 * Constructor de la clase para sobreescribir los default de la clase Objeto
	 * 
	 * @param Sesion $Sesion
	 * @param type $fields
	 * @param type $params
	 */
	function Template(Sesion $Sesion, $fields = '', $params = '') {
		$this->tabla = 'template';
		$this->campo_id = 'id_template';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->editable_fields = $this->campos;
	}

	/**
	 * @return array tipos posibles de un template
	 */
	public static function GetTipos() {
		return self::$tipos;
	}

	/**
	 * 
	 * @param string $tipo solo valores permitidos de tipos de template
	 * @return boolean
	 */
	public static function GetAll(Sesion $Sesion, $tipo = '', $limit = '') {
		if (!isset($tipo) || !in_array($tipo, self::$tipos)) {
			return false;
		}
		
		$where = "WHERE tipo = '$tipo'";
		$order = "ORDER BY id_template";
		$query = "SELECT id_template, glosa_template FROM template $where $order $limit";
		
		return $Sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);;
		
//		$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
//		return mysql_fetch_assoc($resp);
	}

	/**
	 * 
	 * @param string $tipo solo valores permitidos de tipos de template
	 * @return boolean
	 */
	public static function GetFirst(Sesion $Sesion, $tipo = '') {
		return array_pop(self::GetAll($Sesion, $tipo, 'LIMIT 1'));
	}
	
	/**
	 * 
	 * @return PHPWord_Template
	 */
	public function LoadDocumento() {
		// 1. Cargar el BLOB a un temporal
		$temp = tempnam(sys_get_temp_dir(), Conf::dbUser() . '_' . time());
		file_put_contents($temp, $this->fields['documento']);
		
		// 2. Cargar el temporal a PHP Word
		require_once Conf::ServerDir() . '/classes/Word/PHPWord.php';
		
		$PHPWord = new PHPWord();
		$doc = $PHPWord->loadTemplate($temp);
		
		// 3. Borrar el BLOB temporal
		unlink($temp);
		
		return $doc;
	}
	
	/**
	 * Descarga el template con los datos modificados
	 * @param string $filename nombre del archivo
	 * @param string $data arreglo del tipo $llave => $valor para modificar
	 * @return boolean
	 */
	public function Download($filename, $data = '') {
		if (!is_array($data) || !$this->Loaded()) {
			return false;
		}
		
		$doc = $this->LoadDocumento();
		
		// 3. Cambiar todos los valores que tiene el template
		$tags = $doc->GetTags();
		
		foreach ($tags as $tag) {
			// Limpiar el valor y buscarlo en el arreglo de datos
			$partes = explode('.', $tag);
			$valor = $data;
			foreach ($partes as $parte) {
				$valor = $valor[$parte];
			}
			$doc->setValue($tag, $valor);
		}
		
				
		if ($filename == '') {
			$filename = 'Temp' . time() . '.docx';
		}
		
		// 5. Enviar
		$doc->export($filename);
	}
	
	public function SearchQuery() {
		$query = "SELECT SQL_CALC_FOUND_ROWS * FROM template";

		$where = '1';

//		if (!empty($this->extra_fields['fecha_desde'])) {
//			$where .= " AND sa.fecha >= '" . Utiles::fecha2sql($this->extra_fields['fecha_desde']) . "' ";
//		}
//		if (!empty($this->extra_fields['fecha_hasta'])) {
//			$where .= " AND sa.fecha <= '" . Utiles::fecha2sql($this->extra_fields['fecha_hasta']) . "' ";
//		}

		$query .= " WHERE $where";
		
		return $query;
	}
	
	/**
	 * Permite guardar el documento con su archivo correspondiente
	 * 
	 * @param $_FILE $file
	 * @return boolean si el template se guardo correctamente con documento o no
	 */
	public function Upload($file) {
		$fileName = $file['name'];
		$tmpName  = $file['tmp_name'];
		$fileSize = $file['size'];
		$fileType = $file['type'];

		$fp      = fopen($tmpName, 'r');
		$content = fread($fp, filesize($tmpName));
		//$content = addslashes($content); OMITO ESTO POR QUE LO HACE Objeto->Write
		fclose($fp);
		
		$this->Edit('documento', $content);

		return $this->Write();
	}
}
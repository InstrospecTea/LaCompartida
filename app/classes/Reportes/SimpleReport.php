<?php

require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

/** PHPExcel root directory */
if (!defined('SIMPLEREPORT_ROOT')) {
	define('SIMPLEREPORT_ROOT', dirname(__FILE__) . '/');
	require(SIMPLEREPORT_ROOT . 'SimpleReport/Autoloader.php');
}

/**
 * Clase para manejar todos los reportes de listados simples, por pantalla y excel
 */
class SimpleReport extends Objeto {
	/**
	 * Define los campos del reporte permitidos para llenar
	 *
	 * @var array
	 */
	private $campos = array(
		'id',
		'configuracion',
		'id_usuario',
		'id_moneda'
	);

	/**
	 * Define los tipos posibles de reportes excel
	 *
	 * @var array('SOLICITUD_ADELANTO',
		'FACTURAS',
		'FACTURAS_PAGOS',
		'HORAS',
		'TRAMITES',
		'ADELANTOS',
		'GASTOS',
		'ASUNTOS',
		'CLIENTES',
		'USUARIOS')
	 */
	private static $tipos = array(
		'SOLICITUDES_ADELANTO',
		'FACTURAS',
		'FACTURAS_PAGOS',
		'HORAS',
		'TRAMITES',
		'ADELANTOS',
		'GASTOS',
		'ASUNTOS',
		'CLIENTES',
		'USUARIOS'
	);

	/**
	 * @var UsuarioExt
	 */
	public $Usuario;

	/**
	 * @var SimpleReport_Configuration
	 */
	public $Config;

	/**
	 * @var array
	 */
	public $results;

	/**
	 * Constructor de la clase para sobreescribir los default de la clase Objeto
	 *
	 * @param Sesion $Sesion
	 * @param type $fields
	 * @param type $params
	 */
	function __construct(Sesion $Sesion, $fields = '') {
		$this->tabla = 'reporte_listado';
		$this->campo_id = 'id';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->editable_fields = $this->campos;
	}

	public function LoadWithType($type) {
		$wheres = "tipo = '$type'";
		$query = "SELECT * FROM {$this->tabla} WHERE $wheres LIMIT 1";

		try {
			$this->fields = $this->sesion->pdodbh->query($query)->fetch(PDO::FETCH_ASSOC);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function GetAll($tipo = '') {

		$where = array();

		$where[] = "id_usuario IS NULL";

		if (!empty($tipo)) {
			if (!in_array($tipo, self::$tipos)) {
				throw new Exception('Error: Por favor seleccionar un tipo válido');
			}

			$where[] = "tipo = '$tipo'";
		}

		// Obtener la configuración por defecto
		$query = "SELECT * FROM reporte_listado WHERE " . implode(" AND ", $where);

		return $this->sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function GetConfiguration($tipo) {
		$resultado = $this->GetAll($tipo);

		$configuracion = $resultado[0]['configuracion'];

		if (empty($configuracion)) {
			$configuracion = $resultado[0]['configuracion_original'];
		}

		return $configuracion;
	}

	public function LoadConfig(SimpleReport_Configuration $config) {
		$this->Config = $config;
		return $this->Config;
	}

	public function LoadConfigFromJson($json) {
		return $this->LoadConfig(SimpleReport_Configuration::LoadFromJson($json));
	}
	
	public function LoadConfigFromArray($config) {
		return $this->LoadConfig(SimpleReport_Configuration::LoadFromArray($config));
	}

	public function LoadResults($results) {
		$this->results = $results;
	}

	public function RunReport() {
		return $this->results;
	}

}

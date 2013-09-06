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
		'SOLICITUDES_ADELANTO' => 'SolicitudAdelanto',
		'FACTURAS' => 'Factura',
		'FACTURAS_PAGOS' => 'FacturaPago',
		'GASTOS' => 'Gasto',
		'CLIENTES' => 'Cliente',
		'ASUNTOS' => 'Asunto',
		'RETRIBUCIONES_ENCABEZADO' => 'Retribuciones',
		'RETRIBUCIONES_DETALLE' => 'Retribuciones.configuracion_subreporte',
		'RETRIBUCIONES_RESUMEN_ENCABEZADO' => 'RetribucionesResumen',
		'RETRIBUCIONES_RESUMEN_DETALLE' => 'RetribucionesResumen.configuracion_subreporte',
		'REPORTE_SALDO_CLIENTES' => 'ReportesEspecificos.configuracion_saldo_clientes',
		'REPORTE_SALDO_CLIENTES_RESUMEN' => 'ReportesEspecificos.configuracion_saldo_clientes_resumen',
		'FACTURA_PRODUCCION' => 'FacturaProduccion',
//		'FACTURA_COBRANZA' => 'FacturaProduccion.configuracion_cobranza',
//		'FACTURA_COBRANZA_APLICADA' => 'FacturaProduccion.configuracion_cobranza_aplicada'
//		'TRAMITES' => 'Tramite',
//		'ADELANTOS' => '',
//		'USUARIOS' => 'Usuario'
	);
	public $filters = array();

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
	private $base_config;

	/**
	 * @var array
	 */
	public $results;

	public $regional_format = array('date_format' => '%d/%m/%Y', 'thousands_separator' => '.', 'decimal_separator' => ',');

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

	public function AddSubReport($subreport) {
		$this->SubReport = $subreport;
	}

	public function SetCustomFormat($custom_format) {
		$this->custom_format = $custom_format;
	}

	public function LoadWithType($type) {
		$wheres = "tipo = '$type'";
		$query = "SELECT * FROM {$this->tabla} WHERE $wheres LIMIT 1";

		try {
			$this->fields = $this->sesion->pdodbh->query($query)->fetch(PDO::FETCH_ASSOC);

			// Si no encuentra en la base de datos quiere decir que es una nueva configuración personalizada
			if (!$this->fields) {
				$this->Edit('tipo', $type);
			}

			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function GetAllConfigurations() {
		$configuraciones = array();
		foreach (array_keys(self::$tipos) as $tipo) {
			$configuraciones[$tipo] = $this->LoadConfiguration($tipo);
		}
		return $configuraciones;
	}

	public function SetBaseConfig($config) {
		$this->base_config = $config;
	}

	private function GetBaseConfig($tipo) {
		if (!empty($this->base_config)) {
			return $this->base_config;
		}

		if (!isset(self::$tipos[$tipo])) {
			throw new Exception('Error: Por favor seleccionar un tipo válido');
		}

		list($clase, $configuracion) = explode('.', self::$tipos[$tipo]);
		$archivo_clase = Conf::ServerDir() . '/classes/' . $clase . '.php';

		if (!$configuracion) {
			$configuracion = 'configuracion_reporte';
		}

		require_once $archivo_clase;
		return $clase::${$configuracion};
	}

	public function GetClass($tipo) {
		if (!empty($this->base_config)) {
			return $this->base_config;
		}

		if (!isset(self::$tipos[$tipo])) {
			throw new Exception('Error: Por favor seleccionar un tipo válido');
		}

		list($clase, $configuracion) = explode('.', self::$tipos[$tipo]);
		$archivo_clase = Conf::ServerDir() . '/classes/' . $clase . '.php';

		if (!$configuracion) {
			$configuracion = 'configuracion_reporte';
		}

		require_once $archivo_clase;

		return $clase;
	}


	public function LoadDBTipos(){
		$query = "SELECT distinct Tipo FROM reporte_listado";
		$Statement = $this->sesion->pdodbh->prepare($query);
		$Statement->execute();
		while ($tipo = $Statement->fetch(PDO::FETCH_OBJ)) {
			array_push($tipo->Tipo, null);
		}
		return $resultado;
	}

	public function LoadConfiguration($tipo) {
		// Cargar la configuracion base
		$config = $this->GetBaseConfig($tipo);

		// Normalizar titulos
		foreach ($config as $key => $item) {
			$config[$key]['extras']['original_title'] = $config[$key]['title'] =
				utf8_encode(isset($item['title']) ? __($item['title']) : $item['field']);
		}

		// Buscar en la base de datos si hay una configuración personalizada
		$query = "SELECT configuracion FROM reporte_listado WHERE tipo = '$tipo'";
		$resultado = $this->sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);

		// Realizar un merge entre la configuración base y la personalizada
		if (!empty($resultado)) {
			$custom = json_decode($resultado[0]['configuracion'], true);
			if (!empty($custom)) {
				$custom_assoc = array();
				foreach ($custom as $item) {
					$custom_assoc[$item['field']] = $item;
				}
				foreach ($config as $key => $item) {
					if (isset($custom_assoc[$item['field']])) {
						if (isset($custom_assoc[$item['field']]['extras'])) {
							$custom_assoc[$item['field']]['extras'] += $item['extras'];
						}
						$config[$key] = $custom_assoc[$item['field']] + $item;
					}
				}
			}
		}

		return $this->LoadConfigFromArray($config);
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

	public function SetFilters($filters) {
		$this->filters = $filters;
	}

	public function SetRegionalFormat($format) {
		$this->regional_format = $format;
	}

	public function RunReport($values = null) {
		if (isset($values) && !empty($values)) {
			$result_array = $this->results;
			foreach ($values as $value) {
				$result_array = $result_array[$value];
			}
			return $result_array;
		} else {
			return $this->results;
		}
	}

}

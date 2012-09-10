<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/classes/Contrato.php';
require_once Conf::ServerDir() . '/classes/Cliente.php';
require_once Conf::ServerDir() . '/classes/UsuarioExt.php';
require_once Conf::ServerDir() . '/classes/Template.php';

/**
 * Clase para manejar las solicitudes de adelanto
 */
class SolicitudAdelanto extends Objeto {

	/**
	 * Define los campos de la solicitud de adelanto permitidos para llenar
	 *
	 * @var array
	 */
	private $campos = array(
		'id_solicitud_adelanto',
		'codigo_cliente',
		'monto',
		'id_moneda',
		'fecha',
		'descripcion',
		'estado',
		'id_usuario_solicitante',
		'id_usuario_ingreso',
		'id_contrato',
		'id_template'
	);

	/**
	 * Define los estados posibles de una solicitud de adelanto
	 *
	 * @var array('CREADO', 'SOLICITADO', 'DEPOSITADO')
	 */
	private static $estados = array(
		'CREADO',
		'SOLICITADO',
		'DEPOSITADO'
	);

	/**
	 * @var Cliente
	 */
	public $Cliente;

	/**
	 * @var UsuarioExt
	 */
	public $Solicitante;

	/**
	 * @var Contrato
	 */
	public $Contrato;

	/**
	 * Constructor de la clase para sobreescribir los default de la clase Objeto
	 *
	 * @param Sesion $Sesion
	 * @param type $fields
	 * @param type $params
	 */
	function SolicitudAdelanto(Sesion $Sesion, $fields = '', $params = '') {
		$this->tabla = 'solicitud_adelanto';
		$this->campo_id = 'id_solicitud_adelanto';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->editable_fields = $this->campos;
	}

	/**
	 * Lazy load del cliente relacionado
	 * @return Cliente
	 */
	function Cliente() {
		if (!$this->Loaded()) {
			return null;
		}
		if (!isset($this->Cliente)) {
			$this->Cliente = new Cliente($this->sesion);
			$this->Cliente->LoadByCodigo($this->fields['codigo_cliente']);
		}

		return $this->Cliente;
	}

	/**
	 * Lazy load del usuario solicitante
	 * @return UsuarioExt
	 */
	function Solicitante() {
		if (!$this->Loaded()) {
			return null;
		}
		if (!isset($this->Solicitante)) {
			$this->Solicitante = new UsuarioExt($this->sesion);
			$this->Solicitante->LoadId($this->fields['id_usuario_solicitante']);
		}

		return $this->Solicitante;
	}

	/**
	 * Lazy load del contrato
	 * @return UsuarioExt
	 */
	function Contrato() {
		if (!$this->Loaded()) {
			return null;
		}
		if (!isset($this->Contrato)) {
			$this->Contrato = new Contrato($this->sesion);
			if (isset($this->fields['id_contrato'])) {
				$this->Contrato->Load($this->fields['id_contrato']);
			}
			if (isset($this->extra_fields['codigo_asunto'])) {
				$this->Contrato->LoadByCodigoAsunto($this->extra_fields['codigo_asunto']);
			}
		}

		return $this->Contrato;
	}

	function SearchQuery() {
		$query = "SELECT SQL_CALC_FOUND_ROWS
					sa.id_solicitud_adelanto,
					sa.fecha,
					sa.descripcion,
					sa.monto,
					sa.id_moneda,
					sa.estado,
					sa.id_template,
					sa.id_contrato,
					c.codigo_cliente,
					c.glosa_cliente,
					CONCAT(u.apellido1, ', ', u.nombre) AS username,
					COUNT(d.id_documento) AS cantidad_adelantos,
					-1 * SUM(d.monto) AS monto_adelantos,
					-1 * SUM(d.saldo_pago) AS saldo_adelantos,
					(sa.monto + SUM(d.monto)) AS saldo_solicitud_adelanto,
					IFNULL(GROUP_CONCAT(a.codigo_asunto SEPARATOR ';'), 'Todos') AS codigos_asunto,
					IFNULL(GROUP_CONCAT(a.glosa_asunto SEPARATOR ';'), 'Todos') AS glosas_asunto
				FROM solicitud_adelanto sa
				LEFT JOIN cliente c ON c.codigo_cliente = sa.codigo_cliente
				LEFT JOIN usuario u ON u.id_usuario = sa.id_usuario_solicitante
				LEFT JOIN documento d ON d.id_solicitud_adelanto = sa.id_solicitud_adelanto
				LEFT JOIN asunto a ON a.id_contrato = sa.id_contrato";

		$wheres = array();

		if (!empty($this->fields['id_solicitud_adelanto'])) {
			$wheres[] = "sa.id_solicitud_adelanto = '{$this->fields['id_solicitud_adelanto']}'";
		}

		if (!empty($this->fields['codigo_cliente'])) {
			$wheres[] = "sa.codigo_cliente = '{$this->fields['codigo_cliente']}'";
		}

		if (!empty($this->fields['estado'])) {
			$wheres[] = "sa.estado = '{$this->fields['estado']}'";
		}

		if (!empty($this->fields['id_contrato'])) {
			$wheres[] = "sa.id_contrato = '{$this->fields['id_contrato']}'";
		}

		if (!empty($this->extra_fields['fecha_desde'])) {
			$wheres[] = "sa.fecha >= '" . Utiles::fecha2sql($this->extra_fields['fecha_desde']) . "'";
		}
		if (!empty($this->extra_fields['fecha_hasta'])) {
			$wheres[] = "sa.fecha <= '" . Utiles::fecha2sql($this->extra_fields['fecha_hasta']) . "'";
		}

		if (!empty($this->extra_fields['codigo_asunto'])) {
			$wheres[] = "a.codigo_asunto = '{$this->extra_fields['codigo_asunto']}'";
		}

		if (count($joins) > 0) {
			$query .= " " . implode(' ', $joins);
		}

		if (count($wheres) > 0) {
			$query .= " WHERE " . implode(' AND ', $wheres);
		}
		$query .= " GROUP BY sa.id_solicitud_adelanto";

		return $query;
	}

	/**
	 * @return array estados posibles de una solicitud de adelanto
	 */
	public static function GetEstados() {
		return self::$estados;
	}

	/**
	 * Prepara el objeto para ser guardado, corregir fechas, definir defaults, etc
	 */
	public function Prepare() {
		// Formateo de fecha para correcto almacenamiento
		if (isset($this->fields['fecha'])) {
			$this->fields['fecha'] = Utiles::fecha2sql($this->fields['fecha']);
		}

		// Estado por defecto cuando se está creando
		if (!isset($this->fields['estado']) && !$this->Loaded()) {
			$this->Edit('estado', 'CREADO');
		}

		// Template por defecto cuando no venga
		if (!isset($this->fields['id_template'])) {
			$tpl = Template::GetFirst($this->sesion, 'SOLICITUD_ADELANTO');
			$this->Edit('id_template', $tpl['id_template']);
		}

		if(empty($this->fields['id_template'])){
			$this->fields['id_template'] = 'NULL';
		}
		if(empty($this->fields['id_contrato'])){
			$this->fields['id_contrato'] = 'NULL';
		}
	}

	/**
	 * Implementar las mismas validaciones que se realizan en la vista, a nivel de código
	 *
	 * @return boolean Si todo anda ok, sino deja una variable en $_SESSION['errores']
	 * con las cosas que fallaron
	 */
	public function Check() {
		$errores = array();

		// monto
		if (!is_numeric($this->fields['monto'])) {
			$errores[] = __('Debe ingresar un monto');
		}

		// moneda
		if ($this->fields['id_moneda'] == '') {
			$errores[] = __('Debe seleccionar una moneda');
		}

		// cliente
		if ($this->fields['codigo_cliente'] == '') {
			$errores[] = __('Debe seleccionar un cliente');
		}

		// descripcion
		if ($this->fields['descripcion'] == '') {
			$errores[] = __('Debe ingresar una descripción');
		}

		// estado
		if ($this->fields['estado'] == '') {
			$errores[] = __('Debe ingresar un estado');
		}
		if (!in_array($this->fields['estado'], self::$estados)) {
			$errores[] = __('Debe ingresar un estado válido');
		}

		// fecha
		$fecha = $this->fields['fecha'];
		if ($fecha == '') {
			$errores[] = __('Debe seleccionar una fecha');
		}
		if (date('Y-m-d', strtotime($fecha)) != $fecha) {
			$errores[] = __('Debe seleccionar una fecha válida');
		}

		// template
//		if ($this->fields['id_template'] == '' || $this->fields['id_template'] == 0) {
//			$errores[] = __('Se produjo un error al asociar un template');
//		}

		$this->error = $errores;

		return empty($this->error);
	}

	/**
	 * @return boolean si todo anda ok para poder eliminar el registro
	 */
	public function CheckDelete() {
		return true;
	}

	/**
	 * Envia un correo al solicitante indicando que su solicitud ya está disponible
	 */
	public function NotificarSolicitante() {
		if ($this->Loaded() && $this->fields['estado'] == 'DEPOSITADO') {

			$usuario = UsuarioExt::GetUsuarios($this->sesion, " WHERE id_usuario = {$this->fields['id_usuario_solicitante']}");

			$correo_solicitante = array(
				array(
					'mail' => trim($usuario['email']),
					'nombre' => $usuario['nombre']
				)
			);
			$subject = "Solicitud de adelanto disponible";
			$body = <<<BODY
Estimado/a {$usuario['nombre']},

La solicitud de adelanto N° {$this->fields['id_solicitud_adelanto']},
se encuentra disponible para su retiro.
BODY;

			$utiles = new Utiles;
			return $utiles->EnviarMail($this->sesion, $correo_solicitante, $subject, $body);
		}

		return false;
	}

	/**
	 * Descarga el Word con el template de la Carta de Solicitud de Adelantos
	 */
	public function DownloadWord() {
		$this->Load($this->fields['id_solicitud_adelanto']);

		$template = new Template($this->sesion);
		$template->Load($this->fields['id_template']);

		$datos = $this->FillTemplate();

		$template->Download("SolicitudAdelanto_{$this->fields['id_solicitud_adelanto']}.docx", $datos);
	}

	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadExcel() {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);

		$this->extra_fields['excel_config'] = $SimpleReport->GetConfiguration('SOLICITUDES_ADELANTO');

		// Load config from json
		if (!isset($this->extra_fields['excel_config'])) {
			// Cargar json del estudio
		} else {
			$SimpleReport->LoadConfigFromJson($this->extra_fields['excel_config']);
		}

		$query = $this->SearchQuery();
		$statement = $this->sesion->pdodbh->prepare($query);
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save('Solicitudes_Adelanto');
	}

	public function FillTemplate() {
		if (!$this->Loaded()) {
			return '';
		}

		$datos = array(
			'FechaCreacion' => array(
				'Raw' => $this->fields['fecha'],
				'EnPalabras' => (setlocale(LC_ALL, 'spanish')) ? strftime('%d de %B de %Y', strtotime($this->fields['fecha'])) : '',
				'EnIngles' => (setlocale(LC_ALL, 'english')) ? strftime('%B %d, %Y', strtotime($this->fields['fecha'])) : '',
			),
			'Descripcion' => $this->fields['descripcion'],
			'MontoSolicitado' => UtilesApp::PrintFormatoMoneda(&$this->sesion, $this->fields['monto'], $this->fields['id_moneda']),
			'Cliente' => $this->Cliente()->FillTemplate(),
			'Solicitante' => $this->Solicitante()->FillTemplate()
		);

		return $datos;
	}
}
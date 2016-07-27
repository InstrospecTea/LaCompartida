<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once('Numbers/Words.php');

class NotaCobro extends NotaCobroResumenProfesional {

	// Twig, the flexible, fast, and secure template language for PHP
	protected $twig;
	protected $template_data;
	protected $detalle_en_asuntos = FALSE;
	protected $hitos = FALSE;
	protected $resumen_cap = null;

	var $asuntos = array();
	var $x_resultados = array();

	/**
	 * @var Sesion
	 */
	var $sesion = null;
	var $carta_tabla = 'cobro_rtf';
	var $carta_id = 'id_formato';
	var $carta_formato = 'cobro_template';
	var $siguiente = array();

	function __construct($sesion, $fields = "", $params = "") {
		parent::__construct($sesion, $fields);
		$this->x_resultados = array();
		$valorsinespacio = '&nbsp;';
		if (Conf::GetConf($this->sesion, 'ValorSinEspacio')) {
			$valorsinespacio = '';
		}
		$this->espacio = $valorsinespacio;
	}

	function NuevoRegistro() {
		return array(
			'descripcion' => 'Nueva nota de cobro',
			'html_header' => '',
			'html_pie' => '',
			'cobro_template' => '',
			'cobro_css' => '',
			'pdf_encabezado_imagen' => '',
			'pdf_encabezado_texto' => ''
		);
	}

	public function ObtenerCarta($id = null) {
		if (empty($id)) {
			return $this->NuevoRegistro() + array('secciones' => array(key($this->secciones) => ''));
		}

		$query = "SELECT * FROM {$this->carta_tabla} WHERE {$this->carta_id} = '$id'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$carta = mysql_fetch_assoc($resp);
		$parser = new TemplateParser($carta[$this->carta_formato]);
		$carta['secciones'] = $parser->tags;
		return $carta;
	}

	function GuardarCarta($data) {
		if (isset($data['secciones'])) {
			$formato = '';
			foreach ($data['secciones'] as $seccion => $html) {
				$formato .= "\n###$seccion###\n$html\n";
			}
			unset($data['secciones']);
			$data[$this->carta_formato] = $formato;
		} else {
			$data[$this->carta_formato] = $data['formato'];
		}
		$Carta = new Objeto($this->sesion, array(), '', $this->carta_tabla, $this->carta_id);
		$Carta->guardar_fecha = false;
		$Carta->editable_fields = array_keys($data);
		$Carta->Fill($data, true);
		if ($Carta->Write()) {
			return $Carta->fields[$this->carta_id];
		}
		return false;
	}

	function PrevisualizarDocumento($data, $id_cobro) {
		$formato = $data['formato'];
		$html = $this->ReemplazarHTML($formato, $id_cobro);
		$doc = new DocGenerator($html, $data['cobro_css'], $this->fields['opc_papel'], $this->fields['opc_ver_numpag'], 'PORTRAIT', $data['margen_superior'], $data['margen_derecho'], $data['margen_inferior'], $data['margen_izquierdo'], $this->fields['estado']);
		libxml_use_internal_errors(true);
		$doc->output('previsualizacion_carta.doc');
		exit;
	}

	function PrevisualizarDocumentoHtml($formato_html, $id_cobro) {
		return $this->ReemplazarHTML($formato_html, $id_cobro);
	}

	function PrevisualizarValores($id_cobro) {
		$html = '';
		$secciones = array(key($this->secciones));
		foreach ($this->secciones as $subsecciones) {
			$secciones = array_merge($secciones, array_keys($subsecciones));
		}
		foreach ($secciones as $seccion) {
			$html .= "\n\n###$seccion###\n<tr><th colspan=3>$seccion</th></tr>\n\n";

			if (isset($this->diccionario[$seccion])) {
				foreach ($this->diccionario[$seccion] as $tag => $desc_tag) {
					$html .= '<tr><td>' . str_replace('%', '&#37;', $tag) . "</td><td>$tag</td><td>" . str_replace('%', '&#37;', $desc_tag) . "</td></tr>\n";
				}
			}
			if (isset($this->secciones[$seccion])) {
				foreach (array_keys($this->secciones[$seccion]) as $subseccion) {
					$html .= "\n%$subseccion%\n";
				}
			}
		}
		return '<table border="1">' . $this->ReemplazarHTML($html, $id_cobro) . '</table>';
	}

	function StrToNumber($str) {
		$legalChars = "%[^0-9\-\ ]%";
		$str = preg_replace($legalChars, "", $str);
		return number_format((float) $str, 0, ',', '.');
	}

	function ReemplazarHTML($html, $id_cobro) {
		$parser = new TemplateParser($html);

		if (empty($id_cobro)) {
			$query = 'SELECT id_cobro FROM cobro ORDER BY id_cobro DESC LIMIT 1';
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_cobro) = mysql_fetch_array($resp);
		}
		$this->Load($id_cobro);
		return $this->GenerarEjemplo($parser);
	}

	function GenerarEjemplo($parser) {
		return $this->GeneraHTMLCobro(false, $parser, 2);
	}

	function ParametrosGeneracion() {

		// Para mostrar un resumen de horas de cada profesional al principio del documento.
		global $resumen_profesional_id_usuario;
		global $resumen_profesional_nombre;
		global $resumen_profesional_hrs_trabajadas;
		global $resumen_profesional_hrs_retainer;
		global $resumen_profesional_hrs_descontadas;
		global $resumen_profesional_hh;
		global $resumen_profesional_valor_hh;
		global $resumen_profesional_categoria;
		global $resumen_profesional_id_categoria;
		global $resumen_profesionales;
		$resumen_profesional_id_usuario = array();
		$resumen_profesional_nombre = array();
		$resumen_profesional_hrs_trabajadas = array();
		$resumen_profesional_hrs_retainer = array();
		$resumen_profesional_hrs_descontadas = array();
		$resumen_profesional_hh = array();
		$resumen_profesional_valor_hh = array();
		$resumen_profesional_categoria = array();
		$resumen_profesional_id_categoria = array();
		$resumen_profesionales = array();

		global $contrato;
		$contrato = new Contrato($this->sesion);
		$contrato->Load($this->fields['id_contrato']);

		global $x_detalle_profesional;
		global $x_resumen_profesional;
		global $x_factor_ajuste;
		list( $x_detalle_profesional, $x_resumen_profesional, $x_factor_ajuste ) = $this->DetalleProfesional();
		global $x_resultados;
		$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro']);
		$this->x_resultados = $x_resultados;

		global $x_cobro_gastos;
		$x_cobro_gastos = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);

		$lang = $this->fields['codigo_idioma'];

		$cliente = new Cliente($this->sesion);
		$cliente->LoadByCodigo($this->fields['codigo_cliente']);

		global $cobro_moneda;
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		global $moneda_total;
		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		$tipo_cambio_moneda_total = $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
		if ($tipo_cambio_moneda_total == 0) {
			$tipo_cambio_moneda_total = 1;
		}

		$idioma = new Objeto($this->sesion, '', '', 'prm_idioma', 'codigo_idioma');
		$idioma->Load($lang);

		// Moneda
		$moneda = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda->Load($this->fields['id_moneda']);

		$moneda_base = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_base->Load($this->fields['id_moneda_base']);

		//Moneda cliente
		$moneda_cli = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_cli->Load($cliente->fields['id_moneda']);
		$moneda_cliente_cambio = $cobro_moneda->moneda[$cliente->fields['id_moneda']]['tipo_cambio'];

		if ($this->fields['codigo_idioma'] == 'es') {
			setlocale(LC_ALL, "es_ES");
		} else if ($this->fields['codigo_idioma'] == 'en') {
			setlocale(LC_ALL, 'en_US.UTF-8');
		}

		return compact('moneda_cliente_cambio', 'moneda_cli', 'lang', 'html2', 'idioma', 'cliente', 'moneda', 'moneda_base', 'trabajo', 'profesionales', 'gasto', 'totales', 'tipo_cambio_moneda_total', 'asunto');
	}

	function GeneraHTMLCobro($masivo = false, $formato = '', $funcion = '', $mostrar_asuntos_cobrables_sin_horas = FALSE) {
		global $masi;
		$masi = $masivo;

		$parametros = $this->ParametrosGeneracion();
		extract($parametros);

		//Usa el segundo formato de nota de cobro
		//solo si lo tiene definido en el conf y solo tiene gastos

		$solo_gastos = true;

		for ($k = 0; $k < count($this->asuntos); $k++) {
			$asunto = new Asunto($this->sesion);
			$asunto->LoadByCodigo($this->asuntos[$k]);
			$query = "SELECT SUM(TIME_TO_SEC(duracion))
						FROM trabajo AS t2
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
						WHERE t2.cobrable = 1
							AND t2.codigo_asunto='" . $asunto->fields['codigo_asunto'] . "'
							AND cobro.id_cobro='" . $this->fields['id_cobro'] . "'";

			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($total_monto_trabajado) = mysql_fetch_array($resp);
			if ($total_monto_trabajado > 0 || $this->fields['monto_subtotal'] > 0) {
				$solo_gastos = false;
			}
		}

		if (Conf::GetConf($this->sesion, 'CSSSoloGastos')) {
			if ($solo_gastos && Conf::GetConf($this->sesion, 'CSSSoloGastos')) {
				$formato = 2;
			}
		}

		$templateData_carta = UtilesApp::TemplateCarta($this->sesion, $this->fields['id_carta']);
		$cssData = UtilesApp::TemplateCartaCSS($this->sesion, $this->fields['id_carta']);
		$parser_carta = new TemplateParser($templateData_carta);

		if (is_numeric($formato) || $formato == '') {
			// buscar formato, de no existir buscar el primero
			$CobroRtf = new CobroRtf($this->sesion);
			$CobroRtf->Load($formato);

			if (!$CobroRtf->Loaded()) {
				if ($CobroRtf->loadFirst()) {
					$formato = $CobroRtf->fields['id_formato'];
				}
			}

			$templateData = UtilesApp::TemplateCobro($this->sesion, $formato);
			$cssData .= UtilesApp::CSSCobro($this->sesion, $formato);
			$parser = new TemplateParser($templateData);
		} else {
			$parser = $formato;
		}

		/*
		 * $this->fields['modalidad_calculo'] == 1, hacer calculo de forma nueva con la funcion ProcesaCobroIdMoneda
		 * $this->fields['modalidad_calculo'] == 0, hacer calculo de forma antigua
		 */

		if (empty($funcion)) {
			$funcion = $this->fields['modalidad_calculo'] == 1 ? 2 : 1;
		}

		$generador = 'GenerarDocumento' . ($funcion == 2 ? '2' : '');

		$nuevomodulofactura = Conf::GetConf($this->sesion, 'NuevoModuloFactura');
		$facturasRS = $this->FacturasDelContrato($this->sesion, $nuevomodulofactura);
		$totalescontrato = $this->TotalesDelContrato($facturasRS, $nuevomodulofactura, $this->fields['id_cobro']);

		$this->set_detalle_en_asuntos(FALSE);
		$this->querys_detalle_en_asuntos();

		return $this->$generador($parser, 'INFORME', $parser_carta, $moneda_cliente_cambio, $moneda_cli, $lang, $html2, $idioma, $cliente, $moneda, $moneda_base, $trabajo, $profesionales, $gasto, $totales, $tipo_cambio_moneda_total, $asunto, $mostrar_asuntos_cobrables_sin_horas);
	}

	/**
	 *
	 * Función para determinar si se debe mostrar o no los asuntos cobrados sin horas,
	 * esto se muestra sólo cuando no hay detalle acerca de algún otro asunto.
	 * Las querys determinan si los asuntos tienen información a ser mostrada, en caso
	 * que así sea no se muestran los asuntos cobrables sin hora, en caso contrario sí.
	 *
	 */
	private function querys_detalle_en_asuntos() {
		$criteria = new Criteria($this->sesion);
		$criteria->add_select('COUNT(*)', 'total')
				->add_from('tramite')
				->add_restriction(CriteriaRestriction::equals('id_cobro', $this->fields['id_cobro']))
				->add_restriction(CriteriaRestriction::in('codigo_asunto', $this->asuntos));

		try {
			$result = $criteria->run();
			$this->set_detalle_en_asuntos($result[0]['total'] == 0 ? FALSE : TRUE);
		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}

		$criteria = new Criteria($this->sesion);
		$criteria->add_select('COUNT(*)', 'total')
				->add_from('trabajo')
				->add_restriction(CriteriaRestriction::equals('id_cobro', $this->fields['id_cobro']))
				->add_restriction(CriteriaRestriction::equals('id_tramite', 0))
				->add_restriction(CriteriaRestriction::in('codigo_asunto', $this->asuntos));

		try {
			$result = $criteria->run();
			$this->set_detalle_en_asuntos(($result[0]['total'] == 0 ? FALSE : TRUE) || $this->get_detalle_en_asuntos());
		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}

		$criteria = new Criteria($this->sesion);
		$criteria->add_select('COUNT(*)', 'total')
				->add_from('cta_corriente')
				->add_restriction(CriteriaRestriction::equals('id_cobro', $this->fields['id_cobro']))
				->add_restriction(CriteriaRestriction::in('codigo_asunto', $this->asuntos));

		try {
			$result = $criteria->run();
			$this->set_detalle_en_asuntos(($result[0]['total'] == 0 ? FALSE : TRUE) || $this->get_detalle_en_asuntos());
		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}
	}

	/**
	 *
	 * Setea valor a variable $detalle_en_asuntos
	 *
	 * @param $detalle_en_asunto valor a setear en variable
	 */
	public function set_detalle_en_asuntos($detalle_en_asuntos) {
		$this->detalle_en_asuntos = $detalle_en_asuntos;
	}

	/**
	 *
	 * @return valor variable $detalle_en_asunto
	 */
	public function get_detalle_en_asuntos() {
		return $this->detalle_en_asuntos;
	}

	public function iniciales($nombre_encargado) {

		$trozos = explode(' ', $nombre_encargado);
		$cadena = '';

		foreach ($trozos as $nombre) {
			$cadena .= strtoupper(substr($nombre, 0, 1));
		}

		return $cadena;
	}

	function GenerarSeccionCliente($htmlplantilla, $idioma, $moneda, $asunto) {

		global $contrato;
		global $cobro_moneda;
		global $lang;

		$this->FillTemplateData($idioma, $moneda);
		$htmlplantilla = $this->RenderTemplate($htmlplantilla);

		$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);

		if (array_key_exists('codigo_contrato', $contrato->fields)) {
			$htmlplantilla = str_replace('%glosa_codigo_contrato%', __('Código') . ' ' . __('Contrato'), $htmlplantilla);
			$htmlplantilla = str_replace('%codigo_contrato%', $contrato->fields['codigo_contrato'], $htmlplantilla);
			$htmlplantilla = str_replace('%glosa_contrato%', $contrato->fields['glosa_contrato'], $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%glosa_codigo_contrato%', '', $htmlplantilla);
			$htmlplantilla = str_replace('%codigo_contrato%', '', $htmlplantilla);
			$htmlplantilla = str_replace('%glosa_contrato%', '', $htmlplantilla);
		}

		#se carga el primer asunto del cobro (solo usar con clientes que usan un contrato por cada asunto)
		$asunto = new Asunto($this->sesion);
		$asunto->LoadByCodigo($this->asuntos[0]);
		$asuntos = $asunto->fields['glosa_asunto'];
		$i = 1;

		while ($this->asuntos[$i]) {
			$asunto_extra = new Asunto($this->sesion);
			$asunto_extra->LoadByCodigo($this->asuntos[$i]);
			$asuntos .= ', ' . $asunto_extra->fields['glosa_asunto'];
			$i++;
		}

		$htmlplantilla = str_replace('%materia%', __('Materia'), $htmlplantilla);
		$htmlplantilla = str_replace('%factura_emitida_a%', __('Factura emitida a'), $htmlplantilla);
		$htmlplantilla = str_replace('%glosa_asunto_sin_codigo%', $asunto->fields['glosa_asunto'], $htmlplantilla);
		$htmlplantilla = str_replace('%valor_codigo_asunto%', $asunto->fields['codigo_asunto'], $htmlplantilla);
		$htmlplantilla = str_replace('%glosa_asuntos_sin_codigo%', $asuntos, $htmlplantilla);

		$htmlplantilla = str_replace('%glosa_numero_cobro%', __($this->fields['codigo_idioma'] . '_numero_cobro'), $htmlplantilla);
		$htmlplantilla = str_replace('%numero_cobro%', $this->fields['id_cobro'], $htmlplantilla);

		$htmlplantilla = str_replace('%servicios_prestados%', __('POR SERVICIOS PROFESIONALES PRESTADOS'), $htmlplantilla);
		$htmlplantilla = str_replace('%a%', __('A'), $htmlplantilla);
		$htmlplantilla = str_replace('%a_min%', empty($contrato->fields['contacto']) ? '' : __('a'), $htmlplantilla);
		$htmlplantilla = str_replace('%glosa_codigo_postal%', __('Código Postal'), $htmlplantilla);
		$htmlplantilla = str_replace('%codigo_postal%', $contrato->fields['factura_codigopostal'], $htmlplantilla);
		$htmlplantilla = str_replace('%cliente%', __('Cliente'), $htmlplantilla);
		$htmlplantilla = str_replace('%nota_cargo%', __('Nota de Cargo'), $htmlplantilla);
		$htmlplantilla = str_replace('%asunto%', __('Asunto'), $htmlplantilla);

		$query = "SELECT glosa_cliente FROM cliente
					WHERE codigo_cliente='" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($glosa_cliente) = mysql_fetch_array($resp);

		$htmlplantilla = str_replace('%nombre_cliente%', $glosa_cliente, $htmlplantilla);
		$htmlplantilla = str_replace('%factura_razon_social_o_nombre_cliente%', ( isset($contrato->fields['factura_razon_social']) && $contrato->fields['factura_razon_social'] != '') ? $contrato->fields['factura_razon_social'] : $glosa_cliente, $htmlplantilla);

		if ($this->fields['codigo_idioma'] == 'es') {
			$htmlplantilla = str_replace('%fecha_liquidacion%', __('Fecha Liquidación'), $htmlplantilla);
			$htmlplantilla = str_replace('%cliente_corporativo%', __('Cliente Corporativo'), $htmlplantilla);
			$htmlplantilla = str_replace('%id_asunto%', __('ID Asunto'), $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%fecha_liquidacion%', __('Invoice Date'), $htmlplantilla);
			$htmlplantilla = str_replace('%cliente_corporativo%', __('Corporate Customer '), $htmlplantilla);
			$htmlplantilla = str_replace('%id_asunto%', __('Matter ID'), $htmlplantilla);
		}

		$htmlplantilla = str_replace('%factura%', __('Factura'), $htmlplantilla);
		$htmlplantilla = str_replace('%factura_mayuscula%', __('FACTURA'), $htmlplantilla);
		$htmlplantilla = str_replace('%codigo_cliente%', $contrato->fields['codigo_cliente'], $htmlplantilla);
		$htmlplantilla = str_replace('%fecha_emision%', ($this->fields['fecha_emision'] == '0000-00-00' || $this->fields['fecha_emision'] == '' || $this->fields['fecha_emision'] == NULL ) ? '&nbsp;' : Utiles::sql2fecha($this->fields['fecha_emision'], $idioma->fields['formato_fecha']), $htmlplantilla);
		$htmlplantilla = str_replace('%glosa_cliente%', $contrato->fields['factura_razon_social'], $htmlplantilla);
		$htmlplantilla = str_replace('%direccion%', __('Dirección'), $htmlplantilla);
		$htmlplantilla = str_replace('%valor_direccion%', nl2br($contrato->fields['factura_direccion']), $htmlplantilla);
		$htmlplantilla = str_replace('%valor_direccion_uc%', nl2br(ucwords(strtolower($contrato->fields['factura_direccion']))), $htmlplantilla);
		$direccion = explode('//', $contrato->fields['direccion_contacto']);
		$htmlplantilla = str_replace('%direccion_carta%', nl2br($direccion[0]), $htmlplantilla);
		$htmlplantilla = str_replace('%rut%', __('RUT'), $htmlplantilla);
		$htmlplantilla = str_replace('%rut_minuscula%', __('Rut'), $htmlplantilla);

		if ($contrato->fields['rut'] != '0' || $contrato->fields['rut'] != '') {
			$rut_split = explode('-', $contrato->fields['rut']);
		}

		$htmlplantilla = str_replace('%valor_rut_sin_formato%', $contrato->fields['rut'], $htmlplantilla);
		$htmlplantilla = str_replace('%valor_rut%', $rut_split[0] ? $this->StrToNumber($rut_split[0]) . "-" . $rut_split[1] : __(''), $htmlplantilla);
		$htmlplantilla = str_replace('%giro_factura%', __('Giro'), $htmlplantilla);
		$htmlplantilla = str_replace('%giro_factura_valor%', $contrato->fields['factura_giro'], $htmlplantilla);
		$htmlplantilla = str_replace('%contacto%', empty($contrato->fields['contacto']) ? '' : __('Contacto'), $htmlplantilla);
		$htmlplantilla = str_replace('%atencion%', empty($contrato->fields['contacto']) ? '' : __('Atención'), $htmlplantilla);

		if (Conf::GetConf($this->sesion, 'TituloContacto')) {
			$htmlplantilla = str_replace('%valor_contacto%', empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'] . ' ' . $contrato->fields['apellido_contacto'], $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%valor_contacto%', empty($contrato->fields['contacto']) ? '' : $contrato->fields['contacto'], $htmlplantilla);
		}

		$htmlplantilla = str_replace('%atte%', empty($contrato->fields['contacto']) ? '' : '(' . __('Atte') . ')', $htmlplantilla);
		$htmlplantilla = str_replace('%telefono%', empty($contrato->fields['fono_contacto']) ? '' : __('Teléfono'), $htmlplantilla);
		$htmlplantilla = str_replace('%valor_telefono%', empty($contrato->fields['fono_contacto']) ? '' : $contrato->fields['fono_contacto'], $htmlplantilla);

		if (Conf::GetConf($this->sesion, 'NuevoModuloFactura')) {
			$query = "SELECT CAST( GROUP_CONCAT( numero ) AS CHAR ) AS numeros
									FROM factura
									WHERE id_cobro ='{$this->fields['id_cobro']}'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($numero_factura) = mysql_fetch_array($resp);

			if (!$numero_factura) {
				$numero_factura = '';
			}
			$htmlplantilla = str_replace('%numero_factura%', $numero_factura, $htmlplantilla);
		} else if (Conf::GetConf($this->sesion, 'PermitirFactura')) {
			$htmlplantilla = str_replace('%numero_factura%', $this->fields['documento'], $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%numero_factura%', '', $htmlplantilla);
		}

		$htmlplantilla = str_replace('%documentos_tributarios%', $this->fields['documento'], $htmlplantilla);

		$htmlplantilla = str_replace('%liquidacion%', __('Liquidación'), $htmlplantilla);
		$htmlplantilla = str_replace('%solo_num_factura%', $this->fields['id_cobro'], $htmlplantilla);
		$htmlplantilla = str_replace('%ciudad_cliente%', $contrato->fields['factura_ciudad'], $htmlplantilla);
		$htmlplantilla = str_replace('%comuna_cliente%', $contrato->fields['factura_comuna'], $htmlplantilla);

		if ($lang == 'es') {
			$fecha_lang = Conf::GetConf($this->sesion, 'CiudadEstudio') . ', ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
		} else {
			$fecha_lang = Conf::GetConf($this->sesion, 'CiudadEstudio') . ' (' . Conf::GetConf($this->sesion, 'PaisEstudio') . '), ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
		}

		if ($lang == "en") {
			$ciudad_fecha_ingles = Conf::GetConf($this->sesion, 'CiudadEstudio') . ' ' . date('F d, Y');
			$fecha_segun_lenguaje = date('F d, Y');
		} else {
			$ciudad_fecha_ingles = Conf::GetConf($this->sesion, 'CiudadEstudio') . ' ' . ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
			$fecha_segun_lenguaje = ucfirst(Utiles::sql3fecha(date('Y-m-d'), '%e de %B de %Y'));
		}

		$htmlplantilla = str_replace('%fecha_especial%', $fecha_lang, $htmlplantilla);
		$htmlplantilla = str_replace('%ciudad_fecha_ingles%', $ciudad_fecha_ingles, $htmlplantilla);
		$htmlplantilla = str_replace('%fecha_segun_lenguaje%', $fecha_segun_lenguaje, $htmlplantilla);
		$htmlplantilla = str_replace('%fecha_slash%', date('d/m/Y'), $htmlplantilla);
		$htmlplantilla = str_replace('%numero_cobro%', $this->fields['id_cobro'], $htmlplantilla);

		if ($contrato->fields['id_pais'] > 0) {
			$query = "SELECT nombre FROM prm_pais
										WHERE id_pais=" . $contrato->fields['id_pais'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($nombre_pais) = mysql_fetch_array($resp);
			$htmlplantilla = str_replace('%nombre_pais%', $nombre_pais, $htmlplantilla);
			$htmlplantilla = str_replace('%nombre_pais_mayuscula%', strtoupper($nombre_pais), $htmlplantilla);
		} else {
			$htmlplantilla = str_replace('%nombre_pais%', '', $htmlplantilla);
			$htmlplantilla = str_replace('%nombre_pais_mayuscula%', '', $htmlplantilla);
		}

		return $htmlplantilla;
	}

	function GenerarSeccionDetallePago($html, $idioma) {

		global $cobro_moneda;

		$fila = $html;
		$monto_total = (float) $x_resultados['monto_cobro_original_con_iva'][$this->fields['opc_moneda_total']];
		$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];

		//Pagos
		if ($this->TienePagosAdelantos()) {
			$query = "
					SELECT doc_pago.glosa_documento, (neteo_documento.valor_cobro_honorarios + neteo_documento.valor_cobro_gastos) * -1 AS monto, doc_pago.fecha, prm_moneda.tipo_cambio
					FROM documento AS doc_pago
					JOIN neteo_documento ON doc_pago.id_documento = neteo_documento.id_documento_pago
					JOIN documento AS doc ON doc.id_documento = neteo_documento.id_documento_cobro
					JOIN prm_moneda ON prm_moneda.id_moneda = doc_pago.id_moneda
					WHERE doc.id_cobro = " . $this->fields['id_cobro'];
			$pagos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			while ($pago = mysql_fetch_assoc($pagos)) {
				$fila_adelanto_ = str_replace('%descripcion%', substr($pago['glosa_documento'], 0, 30 + strpos(' ', substr($pago['glosa_documento'], 30, 50))) . ' (' . $pago['fecha'] . ')', $html);
				$monto_pago = $pago['monto'];
				$monto_pago_simbolo = $moneda['simbolo'] . $this->espacio . number_format($monto_pago, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$fila_adelanto_ = str_replace('%saldo_pago%', $monto_pago_simbolo, $fila_adelanto_);

				$saldo += (float) $monto_pago;
				$fila_adelantos .= $fila_adelanto_;
			}
			$monto_total += (float) $saldo;
			$monto_total_simbolo = $moneda['simbolo'] . $this->espacio . number_format($monto_total, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
			$fila_adelantos .= '<tr class="tr_total"><td>' . __('Saldo del cobro') . '</td><td align="right">' . $monto_total_simbolo . '</td></tr>';
		}

		//Deuda
		$query = "
				SELECT SUM(( documento.saldo_honorarios + documento.saldo_gastos ) * cm1.tipo_cambio / cm2.tipo_cambio) AS saldo_total_cobro
				FROM documento
				LEFT JOIN cobro ON cobro.id_cobro = documento.id_cobro
				LEFT JOIN cobro_moneda as cm1 ON cm1.id_cobro = documento.id_cobro AND cm1.id_moneda = documento.id_moneda
				LEFT JOIN cobro_moneda as cm2 ON cm2.id_cobro = '" . $this->fields['id_cobro'] . "' AND cm2.id_moneda = '" . $this->fields['opc_moneda_total'] . "'
				WHERE documento.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
				AND documento.es_adelanto <> 1 AND documento.tipo_doc = 'N'
				AND (documento.saldo_honorarios + documento.saldo_gastos) > 0
				AND documento.id_cobro <> " . $this->fields['id_cobro'] . "
				AND cobro.estado NOT IN ('PAGADO', 'INCOBRABLE', 'CREADO', 'EN REVISION')";
		$saldo_total_cobro = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$saldo_total_cobro = mysql_fetch_assoc($saldo_total_cobro);
		$saldo_total_cobro = (float) $saldo_total_cobro['saldo_total_cobro'];
		$saldo_total_cobro_simbolo = $moneda['simbolo'] . $this->espacio . number_format($saldo_total_cobro, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$fila_adelantos .= '<tr class="tr_total"><td>' . __('Saldo anterior') . '</td><td align="right">' . $saldo_total_cobro_simbolo . '</td></tr>';

		$saldo_adeudado = $moneda['simbolo'] . $this->espacio . number_format($monto_total + $saldo_total_cobro, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$fila_adelantos .= '<tr class="tr_total"><td>' . __('Saldo total adeudado') . '</td><td align="right">' . $saldo_adeudado . '</td></tr>';

		return $fila_adelantos;
	}

	function GenerarSeccionDetallePagoContrato($html, $idioma) {
		global $cobro_moneda, $x_resultados;
		/**
		 * Etiquetas
		 * %documentos_de_pago% pagos sin contar adelantos
		 * %documentos_de_adelanto% pagos por concepto de adelantos
		 * %pagos_liquidacion% la suma de los dos anteriores
		 * %blank_line% inserta una fila en blanco para ayudar a diagramar
		 *
		 * %saldo_del_cobro% El total facturado menos los pagos que se hayan hecho
		 * %saldo_anterior% La suma de los %saldo_del_cobro% de OTRAS liquidaciones que pertenezcan al mismo contrato
		 * %saldo_total_adeudado% La suma %saldo_del_cobro% de todas las liquiedaciones que pertenezcan al contrato incluida la actual
		 *
		 * %saldo_total_cobro_sinfactura% Homólogo de %saldo_del_cobro% pero considera el monto total de la liquidación, en vez de lo facturado
		 * %saldo_otras_liquidaciones_sinfactura% La suma de los saldos de OTRAS liquidaciones que pertenezcan al mismo contrato
		 * %saldo_contrato_sinfactura% La suma de los saldos de OTRAS liquidaciones que pertenezcan al mismo contrato + %saldo_total_cobro_sinfactura%
		 *
		 * %adelantos_sin_asignar% adelantos del mismo cliente no asignados, restringidos al presente contrato (o sin restricción de contrato cuando estamos en un cobro del contrato por defecto para este cliente)
		 * */
		$fila = $html;
		$fila_adelantos = "";
		$htmltemporal = $html;

		$moneda = $cobro_moneda->moneda[$this->fields['opc_moneda_total']];
		$moneda_base = Utiles::MonedaBase($this->sesion);

		$seccion_detalle_pago_contrato = $this->DetallePagoContrato($this->sesion, $this->fields['id_cobro']);

		$montoadelantosinasignar = $seccion_detalle_pago_contrato['montoadelantosinasignar'];
		$saldo = $seccion_detalle_pago_contrato['saldo'];
		$saldo_adelantos = $seccion_detalle_pago_contrato['saldo_adelantos'];
		$saldo_pagos = $seccion_detalle_pago_contrato['saldo_pagos'];
		$fila_adelantos = $seccion_detalle_pago_contrato['fila_adelantos'];
		$monto_total_cobro = $seccion_detalle_pago_contrato['monto_total_cobro'];
		$saldo_total_cobro = $seccion_detalle_pago_contrato['saldo_total_cobro'];
		$saldo_total_cobro_sinfactura = $seccion_detalle_pago_contrato['saldo_total_cobro_sinfactura'];
		$moneda_saldo = $cobro_moneda->moneda[$seccion_detalle_pago_contrato['moneda_saldo']];

		$saldo_otras_liquidaciones = UtilesApp::CambiarMoneda(
						$seccion_detalle_pago_contrato['saldo_otras_liquidaciones'], $moneda_saldo['tipo_cambio'], $moneda_saldo['cifras_decimales'], $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']
		);

		$saldo_total_adeudado = UtilesApp::CambiarMoneda(
						$seccion_detalle_pago_contrato['saldo_contrato'], $moneda_saldo['tipo_cambio'], $moneda_saldo['cifras_decimales'], $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']
		);

		$saldo_otras_liquidaciones_sinfactura = UtilesApp::CambiarMoneda(
						$seccion_detalle_pago_contrato['saldo_otras_liquidaciones_sinfactura'], $moneda_saldo['tipo_cambio'], $moneda_saldo['cifras_decimales'], $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']
		);

		$saldo_contrato_sinfactura = UtilesApp::CambiarMoneda(
						$seccion_detalle_pago_contrato['saldo_contrato_sinfactura'], $moneda_saldo['tipo_cambio'], $moneda_saldo['cifras_decimales'], $x_resultados['tipo_cambio_opc_moneda_total'], $x_resultados['cifras_decimales_opc_moneda_total']
		);

		$documentos_de_pago .=number_format($saldo_pagos, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$documentos_de_adelanto .=number_format($saldo_adelantos, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$pagos_liquidacion .=number_format($saldo_pagos + $saldo_adelantos, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		$saldo_total_cobro = number_format($saldo_total_cobro, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$saldo_otras_liquidaciones = number_format($saldo_otras_liquidaciones, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$saldo_total_adeudado = number_format($saldo_total_adeudado, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		$saldo_total_cobro_sinfactura = number_format($saldo_total_cobro_sinfactura, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$saldo_otras_liquidaciones_sinfactura = number_format($saldo_otras_liquidaciones_sinfactura, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$saldo_contrato_sinfactura = number_format($saldo_contrato_sinfactura, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		$htmltemporal = str_replace('%documentos_de_pago%', '<tr class="tr_total"><td>' . __('Pagos Realizados') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $documentos_de_pago . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%documentos_de_adelanto%', '<tr class="tr_total"><td>' . __('Adelantos Utilizados') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $documentos_de_adelanto . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%pagos_liquidacion%', '<tr><td>' . __($this->fields['codigo_idioma'] . '_pagos_liquidacion') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $pagos_liquidacion . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%saldo_del_cobro%', '<tr class="tr_total"><td>' . __('Saldo del cobro') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_total_cobro . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%blank_line%', "<tr><td> </td><td> </td></tr>", $htmltemporal);
		$htmltemporal = str_replace('%saldo_anterior%', '<tr><td>' . __('Saldo anterior') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_otras_liquidaciones . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%saldo_total_adeudado%', '<tr class="tr_total"><td>' . __('Saldo total adeudado') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_total_adeudado . '</td></tr>', $htmltemporal);

		$htmltemporal = str_replace('%saldo_total_cobro_sinfactura%', '<tr class="tr_total"><td>' . __('Saldo del cobro') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_total_cobro_sinfactura . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%saldo_otras_liquidaciones_sinfactura%', '<tr><td>' . __('Saldo anterior') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_otras_liquidaciones_sinfactura . '</td></tr>', $htmltemporal);
		$htmltemporal = str_replace('%saldo_contrato_sinfactura%', '<tr class="tr_total"><td>' . __('Saldo total adeudado') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $saldo_contrato_sinfactura . '</td></tr>', $htmltemporal);

		if ($montoadelantosinasignar > 0) {
			$montoadelantosinasignar = number_format($montoadelantosinasignar, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
			$htmltemporal = str_replace('%adelantos_sin_asignar%', '<tr><td>' . __($this->fields['codigo_idioma'] . '_adelantos_sin_asignar') . '</td><td align="right">' . $moneda['simbolo'] . $this->espacio . $montoadelantosinasignar . '</td></tr>', $htmltemporal);
		} else {
			$htmltemporal = str_replace('%adelantos_sin_asignar%', '', $htmltemporal);
		}

		return $htmltemporal;
	}

	public function ObtenerDetalleModalidad($campos, $moneda, $idioma) {
		$detalle_modalidad = $campos['forma_cobro'] == 'TASA' ? '' : __('POR') . ' ' . $moneda['simbolo'] . $this->espacio . number_format($campos['monto_contrato'], $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		if (($campos['forma_cobro'] == 'RETAINER' || $campos['forma_cobro'] == 'PROPORCIONAL') and $campos['retainer_horas'] != '') {
			$detalle_modalidad .= '<br>' . sprintf(__('Hasta') . ' %s ' . __('Horas'), $campos['retainer_horas']);
		}

		return $detalle_modalidad;
	}

	public function GeneraCobrosMasivos($cobros, $imprimir_cartas, $agrupar_cartas, $id_formato = null, $mostrar_asuntos_cobrables_sin_horas = FALSE) {
		global $_LANG;
		$carta_multiple = null;

		set_time_limit(300);

		$NotaCobro = new NotaCobro($this->sesion);

		$orientacion_papel = Conf::GetConf($this->sesion, 'OrientacionPapelPorDefecto');

		if (empty($orientacion_papel) || !in_array($orientacion_papel, array('PORTRAIT', 'LANDSCAPE'))) {
			$orientacion_papel = 'PORTRAIT';
		}

		if ($agrupar_cartas) {
			$Carta = new Carta($this->sesion);

			// Se asigna el identificador del la carta múltiple
			if ($Carta->LoadByDescripcion('MULTIPLE')) {
				$carta_multiple = $Carta->fields['id_carta'];
			}

			$totales_cobros = array();
			$primer_cliente = '';

			foreach ($cobros as $cobro) {
				if (!is_numeric($cobro)) {
					$cobro = $cobro['id_cobro'];
				}

				if (!$NotaCobro->Load($cobro)) {
					continue;
				}

				$NotaCobro->LoadAsuntos();
				$NotaCobro->ParametrosGeneracion();

				$totales_cobros[$NotaCobro->fields['codigo_cliente']][$cobro]['totales'] = $NotaCobro->x_resultados;
				$totales_cobros[$NotaCobro->fields['codigo_cliente']][$cobro]['campos'] = $NotaCobro->fields;
				$totales_cobros[$NotaCobro->fields['codigo_cliente']][$cobro]['asuntos'] = $NotaCobro->asuntos;
			}
		}

		foreach ($cobros as $cobro) {
			if (!is_numeric($cobro)) {
				$cobro = $cobro['id_cobro'];
			}

			if (!$NotaCobro->Load($cobro)) {
				continue;
			}

			if ($imprimir_cartas) {
				if (!$NotaCobro->fields['id_carta']) {
					$NotaCobro->fields['id_carta'] = 1;
					$NotaCobro->fields['opc_ver_carta'] = 1;
				}
			} else {
				$NotaCobro->fields['id_carta'] = null;
			}

			if ($agrupar_cartas) {
				$codigo_cliente = $NotaCobro->fields['codigo_cliente'];

				if ($codigo_cliente != $primer_cliente) {
					$primer_cliente = $codigo_cliente;

					// solo si existe una carta MULTIPLE se sobre escribe el identificador de la carta
					if (!is_null($carta_multiple)) {
						$NotaCobro->fields['id_carta'] = $carta_multiple;
					}

					$NotaCobro->fields['opc_ver_carta'] = 1;
					$NotaCobro->DetalleLiquidaciones = $totales_cobros[$codigo_cliente];
				}
			}

			$lang = $NotaCobro->fields['codigo_idioma'];

			// Limpia $_LANG para que no se choquen los LANGS entre liquidaciones
			$_LANG = array_merge($_LANG, UtilesApp::LoadLang($lang, true));

			if (empty($id_formato)) {
				$id_formato = $NotaCobro->fields['id_formato'];
			}

			$NotaCobro->LoadAsuntos();
			$html = $NotaCobro->GeneraHTMLCobro(true, $id_formato, NULL, $mostrar_asuntos_cobrables_sin_horas);

			if (empty($html)) {
				continue;
			}

			$cssData = UtilesApp::TemplateCartaCSS($this->sesion, $NotaCobro->fields['id_carta']);
			list($docm_top, $docm_right, $docm_bottom, $docm_left, $docm_header, $docm_footer) = UtilesApp::ObtenerMargenesCarta($this->sesion, $NotaCobro->fields['id_carta']);

			if ($html) {
				$cssData .= UtilesApp::CSSCobro($this->sesion);

				if (is_object($doc)) {
					$doc->newSession($html);
				} else {
					$doc = new DocGenerator(
						$html,
						$cssData,
						$NotaCobro->fields['opc_papel'],
						$NotaCobro->fields['opc_ver_numpag'],
						$orientacion_papel,
						$docm_top,
						$docm_right,
						$docm_bottom,
						$docm_left,
						$NotaCobro->fields['estado'],
						$id_formato,
						'',
						$docm_header,
						$docm_footer,
						$lang,
						$this->sesion
					);
				}

				$doc->chunkedOutput("cobro_masivo.doc");
			}
		}

		$doc->endChunkedOutput("cobro_masivo.doc");
	}

	/**
	 * Realiza el render del template HTML con Twig y los datos disponibles
	 * para reemplazar en $template
	 *
	 * @param array Valores posibles para reemplazar en el template HTML
	 * @return string
	 */
	protected function RenderTemplate($template) {

		if (!$this->twig) {
			$loader = new Twig_Loader_String();
			$this->twig = new Twig_Environment($loader);
			$this->twig->setCharset('ISO-8859-1');
			$this->twig->addExtension(new DateTwigExtension());
		}
		return $this->twig->render($template, $this->template_data);
	}

	/**
	 * Llena los datos a utilizar en el RenderTemplate, se llama desde
	 * GenerarDocumento* y GenerarCarta
	 *
	 * @param array Fields del idioma
	 * @param array Fields de la moneda
	 */
	protected function FillTemplateData($idioma, $moneda) {
		$Contrato = new Contrato($this->sesion);
		$Contrato->Load($this->fields['id_contrato']);
		$this->template_data['Contrato'] = $Contrato->fields;

		$this->template_data['Cobro'] = $this->fields;
		$this->template_data['UsuarioActual'] = $this->sesion->usuario->fields;
		$this->template_data['Idioma'] = $idioma->fields;
		$this->template_data['Moneda'] = $moneda->fields;

		$CobroPendiente = new CobroPendiente($this->sesion);
		$this->template_data['CobroPendiente'] = $CobroPendiente->LoadFirstByIdCobro($this->template_data['Cobro']['id_cobro']);
	}
}

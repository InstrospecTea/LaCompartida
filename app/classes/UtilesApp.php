<?php

//Clase UtilesApp
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/DocumentoMoneda.php';
require_once Conf::ServerDir() . '/classes/Autocompletador.php';
require_once Conf::ServerDir() . '/classes/InputId.php';

class UtilesApp extends Utiles {

	/**
	 *
	 * @param object $sesion
	 * @param string $conf
	 * @return string
	 *  Ahora comprueba si existe el array $sesion->arrayconf para llenarlo una sola vez y consultar de él de ahí en adelante.
	 * Si no, intenta usar memcache
	 * Tiene fallback al código antiguo por si
	 */
	public static function GetConf(Sesion $Sesion, $conf) {
		return Conf::GetConf($Sesion, $conf);
	}

	/**
	 *
	 * @param object $sesion
	 * @param string $conf
	 *  Escribe el valor de un config en formato JS.
	 */
	public static function GetConfJs($sesion, $conf) {
		$v = Conf::GetConf($sesion, $conf);
		if (is_numeric($v)) {
			echo "var $conf = $v;\n";
		} else {
			echo "var $conf = '$v';\n";
	}
	}

	public static function GetSimboloMonedaBase($sesion) {
		$querypreparar = "select simbolo from prm_moneda where moneda_base=1 limit 0,1";
		$query = $sesion->pdodbh->prepare($querypreparar);
		$query->execute();
		$result = $query->fetch(PDO::FETCH_LAZY);
		return $result['simbolo'];
	}

	public static function CampoCliente($sesion, $codigo_cliente = null, $codigo_cliente_secundario = null, $codigo_asunto = null, $codigo_asunto_secundario = null,$mas_recientes = false, $width = 320, $oncambio = '',$cargar_selectores=true) {
		echo InputId::Javascript($sesion);
		if (UtilesApp::GetConf($sesion, 'TipoSelectCliente') == 'autocompletador') {
			echo Autocompletador::CSS();
				if ($oncambio=='') {
					$oncambio="CargarGlosaCliente();";
				} elseif (substr($oncambio,0,1)=='+') {
					$oncambio="CargarGlosaCliente(); $oncambio";
				}
			echo Autocompletador::Javascript($sesion,$cargar_selectores,$oncambio);

			if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
				echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente, $codigo_cliente_secundario,$mas_recientes , $width   );
			} else {
				echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente, null,$mas_recientes , $width  );
			}
			
		} else {
			if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
				if ($oncambio=='') {
					$oncambio="CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);";
				} elseif (substr($oncambio,0,1)=='+') {
					$oncambio.="CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);";
				}
				echo InputId::Imprimir($sesion, "cliente", "codigo_cliente_secundario", "glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario, "  ", $oncambio, $width, $codigo_asunto_secundario);
			} else {
				if ($oncambio=='') {
					$oncambio="CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);";
				} elseif (substr($oncambio,0,1)=='+') {
					$oncambio.="CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);";
			}
				echo InputId::Imprimir($sesion, "cliente", "codigo_cliente", "glosa_cliente", "codigo_cliente", $codigo_cliente, "", $oncambio, $width, $codigo_asunto);
		}
	}
	}

	public static function CampoAsunto($sesion, $codigo_cliente = null, $codigo_cliente_secundario = null, $codigo_asunto = null, $codigo_asunto_secundario = null, $width=320, $oncambio='') {
			if ($oncambio=='') {
					$oncambio="CargarSelectCliente(this.value);";
				} elseif (substr($oncambio,0,1)=='+') {
					$oncambio.="CargarSelectCliente(this.value);";
				}

		if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
			echo InputId::Imprimir($sesion, "asunto", "codigo_asunto_secundario", "glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario, "", $oncambio, $width, $codigo_cliente_secundario);
		} else {
			echo InputId::Imprimir($sesion, "asunto", "codigo_asunto", "glosa_asunto", "codigo_asunto", $codigo_asunto, "", $oncambio, $width, $codigo_cliente);
		}
	}
	
	public static function FiltroAsuntoContrato($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, $id_contrato = '', $width = 320) {
		?>
		<tr>
			<td align="right">
				<?php echo __('Asuntos'); ?>
			</td>
			<td colspan="3" align="left" id="td_selector_contrato">
				<?php self::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, $width, $oncambio = "CargarSelectCliente(this.value);CargarContrato(this.value)"); ?>
				<input type="hidden" name="id_contrato" id="id_contrato" value="<?php echo $id_contrato; ?>" />
				<script type="text/javascript">
					function CargarContrato(asunto) {
						var ajax_url = root_dir + '/app/interfaces/ajax/ajax_gastos.php?opc=contratoasunto&codigo_asunto=' + asunto;
						jQuery.getJSON(ajax_url,function(data) {
							if (data) {
								jQuery('#id_contrato').val(data.id_contrato);
								jQuery('#codigo_contrato').val(data.codigo_contrato);
							}
						});
					}
				</script>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td colspan="3">
				<em>
					<?php echo __('Si Ud. selecciona el') . ' ' . __('asunto') . ', ' . __('se considerarán los') . ' ' . __('asuntos') . ' ' . __('que se cobrarán en la misma carta.'); ?>
				</em>
			</td>
		</tr>
		<?php
		if($Slim=Slim::getInstance('default',true)){
			global $id_contrato, $Contrato;
			$Contrato = new Contrato($sesion);
			$Slim->applyHook('hook_filtros_asunto_contrato');
		}
	}

	public static function ComparaEstadoCobro($sesion, $estadoactual, $operador = '=', $estado) {
		//Pregunta si $estadoactual es un estado previo a $estado en el cobro o factura
		$estado = strtoupper($estado);
		$estadoactual = strtoupper($estadoactual);
		$queryorden = "SELECT if(pec2.orden $operador pec1.orden,1,0) respuesta FROM
			`prm_estado_cobro` pec1 ,`prm_estado_cobro` pec2
			WHERE pec1.codigo_estado_cobro='$estado'
			and pec2.codigo_estado_cobro='$estadoactual' ";
		$queryrespuesta = mysql_query($queryorden, $sesion->dbh);
		if (!queryrespuesta)
			return false;
		list($respuesta) = mysql_fetch_array($queryrespuesta);
		if ($respuesta == 1)
			return true;
		return false;
	}

	public static function EstadoPosteriorA($sesion, $tabla, $estado) {

	}

	public static function ExisteCampo($campo, $tabla, $sesion=null) {


		$queryexisten = "show columns  from $tabla like '$campo'";

		$existencampos = $sesion->pdodbh->query($queryexisten);

		$nextcolumn=$existencampos->fetchAll(PDO::FETCH_COLUMN,0);

		if (empty($nextcolumn)) {
			return false;
		} else {
			return true;
		}
	}

	public static function ExisteIndex($nombre, $tabla, $sesion=null) {

		$ExisteIndex = $sesion->pdodbh->query("SHOW INDEX FROM   $tabla where key_name = '$nombre'");

			if (!$ExisteIndex) {
			return false;
		} else {
			$sesion->debug(json_encode($ExisteIndex));
			return true;
		}
	}

	public static function ExisteLlaveForanea($tabla_columna, $tablar_columnar, $sesion=null) {

		if (!DEFINED('DBNAME'))
			define('DBNAME', Conf::dbName());
		$tabla_columna = explode('.', $tabla_columna);
		$tablar_columnar = explode('.', $tablar_columnar);

		$queryllave = "SELECT constraint_name
		FROM information_schema.KEY_COLUMN_USAGE
		WHERE REFERENCED_TABLE_SCHEMA = '" . DBNAME . "'
		AND REFERENCED_TABLE_NAME='$tablar_columnar[0]'
		AND table_name='$tabla_columna[0]'
		AND referenced_column_name ='$tablar_columnar[1]'
		AND column_name='$tabla_columna[1]'";
		$sesion->debug($queryllave);
		$ExisteLlaveForanea = $sesion->pdodbh->query($queryllave);
		$nombrellaveforanea=$ExisteLlaveForanea->fetch();
		$ExisteLlaveForanea->closeCursor();

		if (!$ExisteLlaveForanea) {
			return false;
		} else {
			return $nombrellaveforanea[0];
		}
	}

	public static function cuentaregistros($tabla, $sesion, $sesion=null) {

		$registros = mysql_query("select count(*)  from $tabla",  $sesion->dbh);
		if (!$registros):
			return 0;
		elseif ($cantidad = mysql_fetch_field($registros)):
			return $cantidad;
		endif;
		return 0;
	}

	public static function printRadio($idbuttonset = '', $valores, $actual, $ancho = '') {
		echo "<div " . ($ancho ? $ancho : '' ) . " id='$idbuttonset'>";
		foreach ($valores as $valor):
			echo '<span ><input ' . ($valor[0] == $actual ? 'checked="checked" ' : '') . ' id="' . $idbuttonset . '_' . $valor[0] . '" type="radio" value="" name="' . $idbuttonset . '"  /><label for="' . $idbuttonset . '_' . $valor[0] . '">' . $valor[1] . '</label>';
		endforeach;
		echo "</div>";
	}

	#obtener el formato de la fecha segun un query, o el seteado en el idioma por defecto

	function ObtenerFormatoFecha($sesion, $query = "") {
		if (strlen($query) > 0) { //si tiene query para intentar obtener el idioma según asunto, cobro, u otro ejecutamos query
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			if (mysql_num_rows($resp) > 0) {
				list($formato) = mysql_fetch_array($resp);
			} else { //si la clase (objeto, cobro, asunto, etc, no tiene idioma asociado, buscamos el formato del idioma por defecto)
				$query_idioma_defecto = "SELECT pi.formato_fecha FROM prm_idioma pi WHERE pi.codigo_idioma = (SELECT LOWER(valor_opcion) FROM configuracion WHERE glosa_opcion = 'Idioma' )  ";
				$resp = mysql_query($query_idioma_defecto, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				list($formato) = mysql_fetch_array($resp);
			}
		} else { //si no buscamos el idioma por defecto.
			$query_idioma_defecto = "SELECT pi.formato_fecha FROM prm_idioma pi WHERE pi.codigo_idioma = (SELECT LOWER(valor_opcion) FROM configuracion WHERE glosa_opcion = 'Idioma' )  ";
			$resp = mysql_query($query_idioma_defecto, $sesion->dbh) or Utiles::errorSQL($query_idioma_defecto, __FILE__, __LINE__, $sesion->dbh);
			list($formato) = mysql_fetch_array($resp);
		}
		return ($formato);
	}

	/**
	 * Obtiene los margenes para ser utilizados en la carta de cobro
	 * @param objeto $sesion
	 * @param int $id_carta
	 * @return array $margenes
	 */
	function ObtenerMargenesCarta($sesion, $id_carta) {
		$margenes = array();
		$query = "SELECT margen_superior, margen_derecho, margen_inferior, margen_izquierdo, margen_encabezado, margen_pie_de_pagina FROM carta WHERE id_carta ='$id_carta' LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (mysql_num_rows($resp) > 0) {
			$margenes = mysql_fetch_array($resp);
		} else {
			$margenes = array(1.5, 2, 2, 2, 0.88, 0.88);
		}
		return $margenes;
	}

	function ObtenerMargenesFactura($sesion, $id_template) {
		$margenes = array();
		if (UtilesApp::ExisteCampo('papel', 'factura_rtf', $sesion)) {
			$query = "SELECT margen_superior, margen_derecho, margen_inferior, margen_izquierdo, margen_encabezado, margen_pie_de_pagina, papel FROM factura_rtf WHERE id_tipo ='$id_template' LIMIT 1";
		} else {
			mysql_query("ALTER TABLE  `factura_rtf` ADD  `papel` VARCHAR( 32 ) NOT NULL DEFAULT  'LETTER'", $sesion->dbh);
			$query = "SELECT margen_superior, margen_derecho, margen_inferior, margen_izquierdo, margen_encabezado, margen_pie_de_pagina FROM factura_rtf WHERE id_tipo ='$id_template' LIMIT 1";
		}
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (mysql_num_rows($resp) > 0) {
			$margenes = mysql_fetch_array($resp);
		} else {
			$margenes = array(1.5, 2, 2, 2, 0.88, 0.88);
		}
		return $margenes;
	}

	####################### Formato carta #############################

	function TemplateCarta(&$sesion, $id_carta = 1) {
		$query = "SELECT formato FROM carta WHERE id_carta='$id_carta'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function TemplateCartaCSS(&$sesion, $id_carta = 1) {
		$query = "SELECT formato_css FROM carta WHERE id_carta='$id_carta'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	####################### Formato carta factura ######################

	public static function TemplateFactura(&$sesion, $id_factura_formato = 1) {
		$query = "SELECT factura_template FROM factura_rtf WHERE id_factura_formato='$id_factura_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	public static function TemplateFacturaCSS(&$sesion, $id_factura_formato = 1) {
		$query = "SELECT factura_css FROM factura_rtf WHERE id_factura_formato='$id_factura_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	public static function TemplateFacturaXML(&$sesion, $id_factura_formato = 1) {
		$existexml = mysql_num_rows(mysql_query("SHOW COLUMNS FROM factura_rtf like 'factura_template_xml'", $sesion->dbh));
		if ($existexml) {
			$query = "SELECT factura_template_xml FROM factura_rtf WHERE id_factura_formato='$id_factura_formato'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			if (list($format) = mysql_fetch_array($resp))
				return $format;
		} else {
			return '';
		}
	}

	public static function TemplateBitXML(&$sesion, $id_factura_formato = 1) {
		$existebitxml = mysql_num_rows(mysql_query("SHOW COLUMNS FROM factura_rtf like 'usaxml'", $sesion->dbh));
		if ($existebitxml) {
			$query = "SELECT usaxml FROM factura_rtf WHERE id_factura_formato='$id_factura_formato'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			if (list($resultado) = mysql_fetch_array($resp))
				return $resultado;
		} else {
			return 0;
		}
	}

	####################### Formato carta factura pago ######################

	function TemplateFacturaPago(&$sesion, $id_factura_pago_formato = 1) {
		$query = "SELECT factura_pago_template FROM factura_pago_rtf WHERE id_formato='$id_factura_pago_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function TemplateFacturaPagoCSS(&$sesion, $id_factura_pago_formato = 1) {
		$query = "SELECT factura_pago_css FROM factura_pago_rtf WHERE id_formato='$id_factura_pago_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	###################### Formato cobro #######################

	function TemplateCobro(&$sesion, $id_formato = 1) {
		$query = "SELECT cobro_template FROM cobro_rtf WHERE id_formato='$id_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function CSSCobro(&$sesion, $id_formato = 1) {
		$query = "SELECT cobro_css FROM cobro_rtf WHERE id_formato='$id_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function FormatoCobroFila($sesion, $id_formato = 1) {
		$query = "SELECT formato_cobro_fila FROM cobro_rtf WHERE id_formato = '$id_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function FormatoCobroAsunto($sesion, $id_formato = 1) {
		$query = "SELECT formato_cobro_asunto FROM cobro_rtf WHERE id_formato = '$id_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function FormatoCobroFilaProf($sesion, $id_formato = 1) {
		$query = "SELECT formato_cobro_fila_prof FROM cobro_rtf WHERE id_formato = '$id_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function FormatoCobro($sesion, $id_formato = 1) {
		$query = "SELECT formato_cobro FROM cobro_rtf WHERE id_formato = '$id_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function FormatoCobroFilaGastos($sesion, $id_formato = 1) {
		$query = "SELECT formato_cobro_fila_gasto FROM cobro_rtf WHERE id_formato = '$id_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function FormatoCobroFilaMovimientos($sesion, $id_formato = 1) {
		$query = "SELECT formato_cobro_fila_movimiento FROM cobro_rtf WHERE id_formato = '$id_formato'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (list($format) = mysql_fetch_array($resp))
			return $format;
	}

	function GenerarSemana($id_usuario, $semana) {
		global $sesion;

		if (!$id_usuario)
			$id_usuario = $sesion->usuario->fields['id_usuario'];
		#$id_usuario2 = $sesion->usuario->fields[id_usuario];
		if ($semana == "")
			$semana2 = "CURRENT_DATE()";
		else
			$semana2 = "'$semana'";

		$query = "SELECT *, TIME_TO_SEC(duracion)/90 as alto, DAYOFWEEK(fecha) AS dia_semana
					 FROM trabajo WHERE
						id_usuario = $id_usuario
						AND (
								WEEK(fecha,1) = WEEK($semana2,1)
							)
						ORDER BY fecha,id_trabajo";

		$lista = new ListaTrabajos($sesion, "", $query);

		$dias = array("Lunes", "Martes", "Miécoles", "Jueves", "Viernes", "Sábado", "Domingo");


		echo("<br /><br /><strong>Haga clic en algún trabajo para modificarlo</strong><br /><br />");

		echo("<table style='width:500px'>");
		echo("<tr>");
		for ($i = 0; $i < 7; $i++) {
			echo("
				<td style='width: 100px; border: 1px solid black; text-align:center;'>
					$dias[$i]
				</td>
				");
		}
		echo("</tr>");
		echo("<tr>");
		$dia_anterior = 2;
		for ($i = 0; $i < $lista->num; $i++) {
			$asunto = new Asunto($sesion);
			if ($i == 0)
				echo("<td style='width: 100px'>");

			$img_dir = Conf::ImgDir();

			$alto = $lista->Get($i)->fields[alto] . "px";
			$cod_asunto = $lista->Get($i)->fields[codigo_asunto];
			$dia_semana = $lista->Get($i)->fields[dia_semana];
			if ($dia_semana == 1)
				$dia_semana = 8;
		}
	}

	function DiferenciaDbAplicacionEnSegundos(&$sesion) {
		$query = "SELECT NOW()";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($ahora) = mysql_fetch_array($resp);

		$ahora_db_timestamp = strtotime($ahora);
		$ahora_app_timestamp = strtotime(date("Y-m-d H:i:s"));

		$diferencia = $ahora_app_timestamp - $ahora_db_timestamp;

		return $diferencia;
	}

	/*
		Reemplaza , por . para numero
	 */

	function PonerPuntoNumeros($valor) {
		if (strlen($valor) > 0) {
			$valor = str_replace(',', '.', $valor);
			return $valor;
		}
	}

	function CantidadDiasHabiles($fecha_ini, $fecha_fin) {
		$fecha1 = strtotime($fecha_ini);
		$fecha2 = strtotime($fecha_fin);

		$cont = 0;
		//echo date("d-m-Y",$fecha);
		while ($fecha1 <= $fecha2) {
			if (date("N", $fecha1) < 6)
				$cont++;
			$fecha1 = mktime(0, 0, 0, date('m', $fecha1), date('d', $fecha1) + 1, date('Y', $fecha1));
		}
		return $cont;
	}

	/*
		HTML 2 (F)PDF
	 */

	function Html2Pdf($html) {
		echo($html);
		exit();

		/*
			require_once dirname(__FILE__).'/../libs/html2fpdf/html2fpdf.php';
			$pdf = new HTML2FPDF('P','mm','A4');
			$pdf->DisableTags();
			$pdf->DisplayPreferences('');
			$pdf->SetAuthor( 'Lemontech SA.' );
			$pdf->SetCreator( 'Lemontech SA.' );
			$pdf->SetTitle( 'Informe periódico' );
			$pdf->SetSubject("Lemontech SA.");
			$pdf->SetDisplayMode('fullpage', 'continuous'); #'real'
			$pdf->PageNo();
			$pdf->AddPage();
			$pdf->UseCSS(true);
			$pdf->WriteHTML($html);
			$pdf->Close();
			$pdf->Output('informe_periodico.pdf', 'D');
		 */
	}

	/*
		La cuenta corriente funciona sólo restando de los ingresos para gastos,
		todos los montos_descontados(monto real en pesos) de cada gasto ingresado
	 */

	function TotalCuentaCorriente(&$sesion, $where = '1',$cobrable=1,$array=false) {

		return Gasto::TotalCuentaCorriente($sesion, $where ,$cobrable,$array);


	}

	/*
		La cuenta del cliente funciona sólo sumando los montos asociados al cliente
	 */

	function TotalCuentaCliente(&$sesion, $codigo_cliente = '') {
		$where = 1;
		if ($codigo_cliente != '')
			$where .= " AND codigo_cliente = '$codigo_cliente' ";

		$query = "SELECT SUM(monto*tipo_cambio)
							FROM mvto_cta_corriente JOIN prm_moneda on prm_moneda.id_moneda =mvto_cta_corriente.id_moneda
							WHERE $where";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($saldo) = mysql_fetch_array($resp);
		return $saldo;
	}

	//Funcion que transforma Time de mysql en tiempo x,xx
	function Time2Decimal($duracion) {
		if (!empty($duracion)) {
			list($h, $m, $s) = explode(':', $duracion);
			$minutos = ($h * 60) + $m;
			return number_format($minutos / 60, 1, ',', '.');
		}
		return '0,0';
	}

	//Funcion que transforma de tiempo x,xx o x.xx en Time mysql
	function Decimal2Time($duracion) {
		$duracion = str_replace(',', '.', $duracion);
		$minutos = round($duracion * 60);
		$h = floor($minutos / 60);
		$m = floor($minutos % 60);
		return date('H:i:s', mktime($h, $m, 0, 0, 0, 0));
	}

	//Función que revisa contraseñas de web services
	function VerificarPasswordWebServices($usuario, $password) {
		if ($usuario == Conf::UsuarioWS())
			if ($password == Conf::PasswordWS())
				return true;
		return false;
	}

	// Transforma el formato de las horas, aproximando para que 23.999 sea 24:00 en vez de 23:00.
	function Hora2HoraMinuto($hora) {
		$h = (int) $hora;
		$m = round(60 * ($hora - $h));
		if ($m == 60) {
			++$h;
			$m = 0;
		}
		return sprintf("$h:%02d", $m);
	}

	// En Excel los tiempos se guardan como números donde 1 equivale a 24 horas.
	function tiempoExcelASQL($tiempo, $ingresado_via_decimales = false) {
		$tiempo = str_replace(',', '.', $tiempo);
		if ($ingresado_via_decimales) {
			$h = (int) ($tiempo);
			$m = round(($tiempo - $h) * 60);
		} else {
			$h = (int) ($tiempo * 24);
			$m = round(($tiempo * 24 - $h) * 60);
		}

		// Esta comprobación es necesaria porque la aproximación puede dejar 60 minutos y MySQL no los soporta.
		if ($m == 60) {
			$m = 0;
			++$h;
		}
		return sprintf("%02d:%02d:00", $h, $m);
	}

	function generarFacturaPDF($id_factura, $sesion) {
		require_once Conf::ServerDir() . '/../app/fpdf/fpdf.php';

		$query = "SELECT fecha,
						cliente,
						RUT_cliente,
						direccion_cliente,
						honorarios,
						gastos,
						subtotal,
						iva,
						total,
						descripcion,
						numeracion_papel_desde,
						numeracion_papel_hasta,
						numeracion_computador_desde,
						numeracion_computador_hasta,
						id_moneda,
						id_factura_padre,
						id_documento_legal,
						id_documento_legal_motivo,
						numero
					FROM factura
					WHERE id_factura='$id_factura'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		if (!list($fecha, $cliente, $RUT_cliente, $direccion_cliente, $honorarios, $gastos, $subtotal, $iva, $total, $descripcion, $numeracion_papel_desde, $numeracion_papel_hasta, $numeracion_computador_desde, $numeracion_computador_hasta, $id_moneda, $id_factura_padre, $id_documento_legal, $motivo_documento_legal, $numero_factura) = mysql_fetch_array($resp)) {
			echo "<html><head><title>Error</title></head><body><p>No se encuentra la factura $numero_factura.</p></body></html>";
			return;
		}

		$query_tipo_doc = "SELECT id_documento_legal, glosa FROM prm_documento_legal WHERE id_documento_legal = '" . $id_documento_legal . "'";
		$resp_tipo_doc = mysql_query($query_tipo_doc, $sesion->dbh) or Utiles::errorSQL($query_tipo_doc, __FILE__, __LINE__, $sesion->dbh);
		list($id_documento_legal_tipo, $tipo_documento_legal) = mysql_fetch_array($resp_tipo_doc);

		if (!$tipo_documento_legal)
			$pagina->FatalError('Error al cargar el tipo de Documento Legal');

		// P: hoja vertical
		// mm: todo se mide en milímetros
		// Letter: formato de hoja
		$pdf = new FPDF('P', 'mm', 'Letter');

		// Dimensiones de una hoja tamaño carta.
		$ancho = 216;
		$alto = 279;

		$pdf->SetTitle($tipo_documento_legal . " " . $numero_factura);

		// La orientación y formato de la página son los mismos que del documento
		$pdf->AddPage();

		// Definimos el tipo de letra para todo el documento.
		$pdf->SetFont('Arial', '', 12);

		// Definir los parámetros para el formato de moneda
		$simbolo_moneda = Utiles::glosa($sesion, $id_moneda, 'simbolo', 'prm_moneda', 'id_moneda');
		$cifras_decimales = Utiles::glosa($sesion, $id_moneda, 'cifras_decimales', 'prm_moneda', 'id_moneda');

		// Escribimos el contenido
		// Fecha
		$pdf->SetXY(52, 66);
		$pdf->Write(4, ucfirst(Utiles::sql3fecha(date($fecha), '%B')) . date(' j, Y', strtotime($fecha)));

		// Nombre cliente
		$pdf->SetXY(52, 77);
		$pdf->Write(4, $cliente);

		// RUT (NIT) cliente
		$pdf->SetXY(52, 86);
		$pdf->Write(4, $RUT_cliente);

		// Dirección cliente
		// Cambia el margen para que aparezca alineado si ocupa más de una línea.
		$pdf->SetLeftMargin(52);
		$pdf->SetXY(52, 93);
		$pdf->Write(4, $direccion_cliente);

		$pdf->SetLeftMargin(25);

		// Gastos, están antes que los honorarios porque ocupan solo 1 línea, mientras que los honorarios pueden ocupar muchas.
		if ($gastos > 0) {
			$pdf->SetXY(25, 119);
			$pdf->Write(4, 'Gastos Reembolsables');
			$pdf->SetXY(165, 119);
			$pdf->Cell(20, 4, $simbolo_moneda . ' ' . number_format($gastos, $cifras_decimales, ',', '.'), 0, 0, 'R');
		}

		if ($id_factura_padre > 0) {
			$query_tipo_doc = "select
					m.id_documento_legal,
					m.glosa,
					f.numero
					from factura f
					join prm_documento_legal m on m.id_documento_legal = f.id_documento_legal
					where f.id_factura = $id_factura_padre";
			$resp_tipo_doc = mysql_query($query_tipo_doc, $sesion->dbh) or Utiles::errorSQL($query_tipo_doc, __FILE__, __LINE__, $sesion->dbh);
			list($id_factura_padre, $glosa_tipo_doc, $numero_factura_padre) = mysql_fetch_array($resp_tipo_doc);

			$query_motivo_doc = "SELECT
						m.id_documento_legal_motivo,
						m.glosa
					FROM factura f
					JOIN prm_documento_legal_motivo m ON m.id_documento_legal_motivo = f.id_documento_legal_motivo
					where f.numero = $numero_factura";
			$resp_motivo_doc = mysql_query($query_motivo_doc, $sesion->dbh) or Utiles::errorSQL($query_motivo_doc, __FILE__, __LINE__, $sesion->dbh);
			list($id_documento_legal_motivo, $glosa_motivo) = mysql_fetch_array($resp_motivo_doc);


			$motivo_documento_legal = "$tipo_documento_legal creado para $glosa_motivo la $glosa_tipo_doc numero $numero_factura_padre";
			$motivo_documento_legal = strtolower($motivo_documento_legal);
			$motivo_documento_legal = ucfirst($motivo_documento_legal);
			// Motivo documento legal (detalle)
			$pdf->SetRightMargin($ancho - 150);
			$pdf->SetXY(25, 115);
			$pdf->Write(4, $motivo_documento_legal);
		}

		// Descripción (detalle)
		$pdf->SetRightMargin($ancho - 150);
		$pdf->SetXY(25, 127);
		$pdf->Write(4, $descripcion);

		// Honorarios
		$pdf->SetXY(165, 127);
		$pdf->Cell(20, 4, $simbolo_moneda . ' ' . number_format($honorarios, $cifras_decimales, ',', '.'), 0, 0, 'R');

		// Información bancaria
		if (method_exists('Conf', 'GetConf')) {
			$pdf->SetXY(25, 180);
			$pdf->Write(4, __('Información Bancaria') . ":\n" . Conf::GetConf($sesion, 'InformacionBancaria'));
		} else if (method_exists('Conf', 'InformacionBancaria')) {
			$pdf->SetXY(25, 180);
			$pdf->Write(4, __('Información Bancaria') . ":\n" . Conf::InformacionBancaria());
		}

		// Subtotal
		$pdf->SetXY(165, 212);
		$pdf->Cell(20, 4, $simbolo_moneda . ' ' . number_format($subtotal, $cifras_decimales, ',', '.'), 0, 0, 'R');
		// IVA
		$pdf->SetXY(165, 220);
		$pdf->Cell(20, 4, $simbolo_moneda . ' ' . number_format($iva, $cifras_decimales, ',', '.'), 0, 0, 'R');
		// Total
		$pdf->SetXY(165, 228);
		$pdf->Cell(20, 4, $simbolo_moneda . ' ' . number_format($total, $cifras_decimales, ',', '.'), 0, 0, 'R');

		$pdf->Output();
	}

	// Se asume que no existen feriados, los días hábiles son de lunes a viernes.
	// Las posibilidades de segundo día hábil son M2, W2, J2, V2, L4, M4 y M3.
	public static function esSegundoDiaHabilDelMes() {
		$dia = date('N'); // día entre 1 y 7
		switch (date('j')) {
			case 2:
				if ($dia > 1 && $dia < 6)
					return true;
				break;
			case 3:
				if ($dia == 2)
					return true;
				break;
			case 4:
				if ($dia < 3)
					return true;
		}
		return false;
	}

	function ArregloMeses() {
		$meses = array();
		$meses[1] = "Enero";
		$meses[2] = "Febrero";
		$meses[3] = "Marzo";
		$meses[4] = "Abril";
		$meses[5] = "Mayo";
		$meses[6] = "Junio";
		$meses[7] = "Julio";
		$meses[8] = "Agosto";
		$meses[9] = "Septiembre";
		$meses[10] = "Octubre";
		$meses[11] = "Noviembre";
		$meses[12] = "Diciembre";

		return $meses;
	}

	// Se asume que no existen feriados, los días hábiles son de lunes a viernes.
	public static function esUltimoDiaHabilDelMes($timestamp = '') {
		if ($timestamp == '') {
			$dia_semana = date('N'); // día entre 1 y 7
			$dia_mes = date('j');  // día entre 1 y 31
			$mes = date('n');   // mes entre 1 y 12
		} else {
			$dia_semana = date('N', $timestamp);
			$dia_mes = date('j', $timestamp);
			$mes = date('n', $timestamp);
		}
		$largoMes = array(31, 28 + date('L'), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		if ($dia_mes == $largoMes[$mes - 1] && $dia_semana < 6)
			return true;
		if (($dia_mes == $largoMes[$mes - 1] - 1 || $dia_mes == $largoMes[date('n') - 1] - 2) && $dia_semana == 5)
			return true;
		return false;
	}

	// Asumiendo que el logo no cambia durante la ejecución, podemos tener precalculada su altura para no tener que leer el archivo cada vez.
	var $altura_logo_excel;

	function AlturaLogoExcel() {
		global $sesion;
		if (isset($altura_logo_excel))
			return $altura_logo_excel;
		// Este código está basado en SpreadsheetExcelWriter de PearPHP.
		//FFF se pasa el path del logo a la DB $bitmap = Conf::LogoExcel();

		$bitmap = UtilesApp::GetConf($sesion, 'LogoExcel');
		if(!$bitmap){
			return 0;
		}
		// Open file.
		$bmp_fd = @fopen($bitmap, "rb");
		if (!$bmp_fd)
			return 0;//throw new Exception("Couldn't import $bitmap");
		// Slurp the file into a string.
		$data = fread($bmp_fd, filesize($bitmap));
		// Check that the file is big enough to be a bitmap.
		if (strlen($data) <= 0x36)
			return 0;//throw new Exception("$bitmap doesn't contain enough data.\n");
		// The first 2 bytes are used to identify the bitmap.
		$identity = unpack("A2ident", $data);
		if ($identity['ident'] != "BM")
			return 0;//throw new Exception("$bitmap doesn't appear to be a valid bitmap image.\n");
		// Remove bitmap data.
		$data = substr($data, 18);
		// Read the bitmap width and height.
		$width_and_height = unpack("V2", substr($data, 0, 8));
		$altura_logo_excel = $width_and_height[2];

		// Devolvemos 3/4 de la altura para convertirla de pixeles a puntos.
		return .75 * $altura_logo_excel;
	}

	//Imprime el menú
	public static function PrintMenuDisenoNuevojQuery($sesion, $url_actual) {
		$actual = explode("?", $url_actual);
		$url_actual = $actual[0];
		$bitmodfactura = UtilesApp::GetConf($sesion, 'NuevoModuloFactura');
		switch ($url_actual) {
			case '/app/interfaces/agregar_tarifa.php': $url_actual = '/app/interfaces/agregar_tarifa.php?id_tarifa_edicion=1';
				break;
			case '/app/interfaces/tarifas_tramites.php': $url_actual = '/app/interfaces/tarifas_tramites.php?id_tramite_tarifa_edicion=1';
				break;
			case '/app/interfaces/agregar_cliente.php': $url_actual = '/app/interfaces/clientes.php';
				break;
			case '/app/interfaces/agregar_asunto.php': $url_actual = '/app/interfaces/asuntos.php';
				break;
			case '/app/usuarios/usuario_paso2.php': $url_actual = '/app/usuarios/usuario_paso1.php';
				break;
			case '/app/interfaces/reportes_asuntos.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/resumen_cliente.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_facturacion_pendiente.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_cobros_por_area.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_resumen_cobranza.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_morosidad.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/resumen_abogado.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reportes_usuarios.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reportes_horas.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/olap.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reporte_avanzado.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reporte_financiero.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reporte_costos.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_participacion_abogado.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reporte_consolidado.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/fw/tablas/agregar_campo.php': $url_actual = '/fw/tablas/mantencion_tablas.php';
				break;
		}
		$lista_menu_permiso = Html::ListaMenuPermiso($sesion);
		$query = "SELECT codigo_padre FROM menu WHERE url='$url_actual'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($codigo) = mysql_fetch_array($resp);
		$menu_html = "<!-- Menu Section--> \n";
		$menu_html .= <<<HTML
				<div id="droplinetabs1" class="droplinetabs"><ul>
HTML;

		if (!$bitmodfactura)
			$bitmodfactura = '0';

		$query = "SELECT * from menu WHERE tipo=1 and codigo in ('$lista_menu_permiso') and bitmodfactura<=$bitmodfactura  ORDER BY orden"; //Tipo=1 significa menu principal
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		for ($i = 0; $row = mysql_fetch_assoc($resp); $i++) {
			$glosa_menu = __($row['glosa']);
			if ($codigo == $row['codigo']) {
				$active = 'active=true';
				$estilo_con_margin = 'style="margin:0 4px 0 10px;"';
				$estilo = 'style="color:#FFFFFF; align:center;"';
			} else {
				$estilo_con_margin = 'style="margin: 0 4px 0 10px;"';
				$active = 'active=false';
				$estilo = 'style="align:center;"';
			}
			//Ahora imprimo los sub-menu
			$query = "SELECT * from menu WHERE tipo=0 and codigo in ('$lista_menu_permiso') and codigo_padre='${row['codigo']}' and bitmodfactura<=$bitmodfactura ORDER BY orden";
			//Tipo=0 significa menu secundario
			$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			$root_dir = Conf::RootDir();
			for ($j = 0; $row2 = mysql_fetch_assoc($resp2); $j++) {
				$glosa_submenu = __($row2['glosa']);
				$codigo_submenu = $row2['codigo'];
				if ($codigo_submenu == 'MPDF' && !self::GetConf($sesion, 'MostrarMenuMantencionPDF') ||
					$codigo_submenu == 'SOL_AD' && !self::GetConf($sesion, 'UsarModuloSolicitudAdelantos')) {
					//era mas fácil escribir el filtro de esta forma
				} else {
					if ($j == 0 && $i == 0) {
						$menu_html .= <<<HTML
											<li $active><div id="top_tap_$i"><a href="$root_dir${row2['url']}" class="a_color_activo" $estilo_con_margin>
																				<!--[if IE]><b class="spiffy">
																				<b class="spiffy1"><b class="color_activo" style="width:97px;"></b></b>
																				<b class="spiffy2"><b class="color_activo" style="width:101px;"></b></b>
																				<b class="spiffy3 color_activo" style="width:102px;"></b>
																				<b class="spiffy4 color_activo" style="width:103px;"></b>
																				<b class="spiffy5 color_activo" style="width:103px;"></b></b>
																				<div class="spiffyfg"><![endif]--><span>$glosa_menu</span><!--[if IE]>
																				</div></b><![endif]--></a></div>
											<ul $active style="display: none;" class="top">
HTML;
					} else if ($j == 0) {
						$menu_html .= <<<HTML
											<li $active><div id="top_tap_$i"><a href="$root_dir${row2['url']}" class="a_color_activo" $estilo>
																				<!--[if IE]><b class="spiffy">
																				<b class="spiffy1"><b class="color_activo" style="width:62px;"></b></b>
																				<b class="spiffy2"><b class="color_activo" style="width:66px;"></b></b>
																				<b class="spiffy3 color_activo" style="width:67px;"></b>
																				<b class="spiffy4 color_activo" style="width:68px;"></b>
																				<b class="spiffy5 color_activo" style="width:68px;"></b></b>
																				<div class="spiffyfg"><![endif]--><span>$glosa_menu</span><!--[if IE]>
																				</div></b><![endif]--></a></div>
											<ul $active style="display: none;" class="top">
HTML;
					}
					$menu_html .= <<<HTML
									<li><a class="corner_round" href="$root_dir${row2['url']}" $estilo>$glosa_submenu</a></li>
HTML;
				}
			}
			$menu_html .= <<<HTML
								</ul></li>
HTML;
		}
		$menu_html .= <<<HTML
				</ul></div><div id="fd_menu_grey" class="barra_fija"><ul active=true>
HTML;
		$query = "SELECT * FROM menu WHERE codigo_padre='$codigo' AND tipo=0 AND codigo in ('$lista_menu_permiso') ORDER BY orden";
		$resp3 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		for ($j = 0; $row3 = mysql_fetch_assoc($resp3); $j++) {
			$glosa_submenu = __($row3['glosa']);
			$codigo_submenu = $row3['codigo'];
			if (!self::GetConf($sesion, 'MostrarMenuMantencionPDF') && $codigo_submenu == 'MPDF') {
				//era mas fácil escribir el filtro de esta forma
			} else {
				if ($url_actual == $row3['url']) {
					$activo_adentro_ie = 'style="text-decoration: underline;"';
					$activo_adentro_otros = 'style="background: #119011;-webkit-border-radius: 5px; -ms-border-radius: 5px;-moz-border-radius: 5px;-khtml-border-radius: 5px;border-radius: 5px;"';
				} else {
					$activo_adentro_ie = '';
					$activo_adentro_otros = '';
				}
				$menu_html .= <<<HTML
									<!--[if IE]><li><a href="$root_dir${row3['url']}" $activo_adentro_ie><span>$glosa_submenu</span></a></li><![endif]-->
									<!--[if !IE]><!--><li><a href="$root_dir${row3['url']}" $activo_adentro_otros><span>$glosa_submenu</span></a></li><!--<![endif]-->
HTML;
			}
		}
		$menu_html .= <<<HTML
			</ul>
HTML;
		if (isset($vinculo_ayuda))
		$menu_html .= $vinculo_ayuda;
		$menu_html .= <<<HTML
		 </div>
HTML;

		$menu_html.="<!-- End Menu Section--> \n";
		return $menu_html;
	}

	/**    Returns the offset from the origin timezone to the remote timezone, in seconds.
	 *    @param $remote_tz;
	 *    @param $origin_tz; If null the servers current timezone is used as the origin.
	 *    @return int;
	 */
	public static function get_timezone_offset($remote_tz, $origin_tz = null) {
		if ($origin_tz === null) {
			if (!is_string($origin_tz = date_default_timezone_get())) {
				return false; // A UTC timestamp was returned -- bail out!
			}
		}
		$origin_dtz = new DateTimeZone($origin_tz);
		$remote_dtz = new DateTimeZone($remote_tz);
		$origin_dt = new DateTime("now", $origin_dtz);
		$remote_dt = new DateTime("now", $remote_dtz);
		$offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
		return $offset;
	}

	public static function get_utc_offset($tz = 'America/Santiago') {
		$offset = self::get_timezone_offset($tz, 'UTC') / 3600;
		switch ($tz) {
			case 'America/Bogota': $offset = -5;
				break;
			case 'America/Santiago': $offset = -3;
				break;
		}
		return $offset;
	}

	public static function get_offset_os_utc() {
		return self::get_timezone_offset('UTC', @date("T")) / 3600;
	}

	//Calcula cambio de moneda
	function CambiarMoneda($monto_ini, $tipo_cambio1 = 1, $decimales1 = 0, $tipo_cambio2 = 1, $decimales2 = 0, $conv_string = true) {
		if ($monto_ini == NULL || $monto_ini == '' || !is_numeric($monto_ini)) {
			$monto_ini = (double) 0;
		}
		//FFF: no se debe redondear antes de hacer la división, la siguiente linea generaba un error:
		$monto_ini = number_format($monto_ini, $decimales1, ".", "");

		if ($tipo_cambio1 == $tipo_cambio2) {// si no es el mismo tipo de moneda, que haga el calculo
			$monto_fin = $monto_ini;
		} else  if (empty($tipo_cambio2) || ($tipo_cambio2 == 0)) {
			$monto_fin = $monto_ini;
		} else {// sino, mantener el monto
			$monto_fin = ($monto_ini * $tipo_cambio1) / $tipo_cambio2;
		}

		// Retorno de monto con decimales
		$resultado = round($monto_fin, $decimales2);
		if ($conv_string)
			(string) $resultado = number_format($resultado, $decimales2, ".", "");
		return $resultado;
	}

	function ProcesaCobroIdMoneda($sesion, $id_cobro, $arr_monto = array(), $id_moneda = 0, $carga_documento = true, $soloegreso = false) {

		// Se llama a la funcion que procesa los gatos

		if ($soloegreso) {
			$arr_datos_gastos = UtilesApp::ProcesaGastosCobro($sesion, $id_cobro, array('listar_detalle'), true);
		} else {
			$arr_datos_gastos = UtilesApp::ProcesaGastosCobro($sesion, $id_cobro);
		}
		//ARRAY DE MONTOS A CALCULAR POR DEFECTO
		if (count($arr_monto) == 0) {
			$arr_monto = array();
			$arr_monto['cobro'][0] = 'monto';
			$arr_monto['cobro'][1] = 'monto_subtotal'; //--> honorarios: monto_subtotal(moneda_tarifa)-descuento(moneda_tarifa)
			$arr_monto['cobro'][2] = 'monto_trabajos'; //-->monto Trabajo: monto_trabajos(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
			$arr_monto['cobro'][3] = 'monto_tramites'; //-->monto tramites: monto_tramites(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
			$arr_monto['cobro'][4] = 'monto_contrato'; //-->monto contrato: monto_contrato(id_moneda_monto)
			$arr_monto['cobro'][5] = 'subtotal_gastos'; //--> gastos: subtotal_gastos(moneda_total)
			$arr_monto['cobro'][6] = 'impuesto'; //-->iva honorarios: impuesto(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
			$arr_monto['cobro'][7] = 'impuesto_gastos'; //-->iva gastos: impuesto_gastos(moneda_total) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
			$arr_monto['cobro'][8] = 'descuento'; //--> descuento: descuento(moneda_tarifa)
			$arr_monto['cobro'][9] = 'monto_gastos'; //--> descuento: descuento(moneda_tarifa)
			$arr_monto['cobro'][10] = 'monto_thh'; //--> descuento: descuento(moneda_tarifa)$arr_monto[0]='monto';
			$arr_monto['cobro'][11] = 'saldo_honorarios'; // no se usa en cobro
			$arr_monto['cobro'][12] = 'saldo_gastos'; // no se usa en cobro

			$arr_monto['documento'][0] = 'honorarios';
			$arr_monto['documento'][1] = 'subtotal_honorarios'; //--> honorarios: monto_subtotal(moneda_tarifa)-descuento(moneda_tarifa)
			$arr_monto['documento'][2] = 'monto_trabajos'; //-->monto Trabajo: monto_trabajos(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
			$arr_monto['documento'][3] = 'monto_tramites'; //-->monto tramites: monto_tramites(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
			$arr_monto['documento'][4] = 'monto_contrato'; // no se usa en documento
			$arr_monto['documento'][5] = 'subtotal_gastos'; //--> gastos: subtotal_gastos(moneda_total)
			$arr_monto['documento'][6] = 'impuesto'; //-->iva honorarios: impuesto(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
			$arr_monto['documento'][7] = 'impuesto_gastos'; //-->iva gastos: impuesto_gastos(moneda_total) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
			$arr_monto['documento'][8] = 'descuento_honorarios'; //--> descuento: descuento(moneda_tarifa)
			$arr_monto['documento'][9] = 'subtotal_sin_descuento';
			$arr_monto['documento'][10] = 'gastos'; //--> descuento: descuento(moneda_tarifa)
			$arr_monto['documento'][11] = 'monto_thh'; //--> descuento: descuento(moneda_tarifa)$arr_monto[0]='monto';
			$arr_monto['documento'][12] = 'saldo_honorarios';
			$arr_monto['documento'][13] = 'saldo_gastos';
			$arr_monto['documento'][14] = 'subtotal_gastos_sin_impuesto';
			$arr_monto['documento'][15] = 'subtotal_gastos_con_impuesto';
		}

		/*
		 * $datos_cobros si es cobrado,
		 * se carga con los datos de la tabla documentos
		 * si no, se carga con los datos de la tabla cobro
		 */
		$campo = array();
		$campo['cobro']['id_moneda'] = 'id_moneda';
		$campo['cobro']['id_moneda_monto'] = 'id_moneda_monto';
		$campo['cobro']['opc_moneda_total'] = 'opc_moneda_total';
		$campo['cobro']['descuento'] = 'descuento';
		$campo['cobro']['forma_cobro'] = 'forma_cobro';
		$campo['cobro']['impuesto_gastos'] = 'impuesto_gastos';
		$campo['cobro']['monto'] = 'monto';
		$campo['cobro']['monto_contrato'] = 'monto_contrato';
		$campo['cobro']['monto_gastos'] = 'monto_gastos';
		$campo['cobro']['monto_subtotal'] = 'monto_subtotal';
		$campo['cobro']['monto_trabajos'] = 'monto_trabajos';
		$campo['cobro']['monto_tramites'] = 'monto_tramites';
		$campo['cobro']['retainer_horas'] = 'retainer_horas';
		$campo['cobro']['subtotal_gastos'] = 'subtotal_gastos';
		$campo['cobro']['total_minutos'] = 'total_minutos';

		$campo['documento']['id_moneda'] = 'id_moneda';
		$campo['documento']['id_moneda_monto'] = 'id_moneda';
		$campo['documento']['opc_moneda_total'] = 'id_moneda';
		$campo['documento']['descuento'] = 'descuento_honorarios';
		$campo['documento']['forma_cobro'] = 'forma_cobro';
		$campo['documento']['impuesto_gastos'] = 'impuesto_gastos';
		$campo['documento']['monto'] = 'honorarios';
		$campo['documento']['monto_contrato'] = 'monto_contrato';
		$campo['documento']['monto_gastos'] = 'gastos';
		$campo['documento']['monto_subtotal'] = 'subtotal_honorarios';
		$campo['documento']['monto_trabajos'] = 'monto_trabajos';
		$campo['documento']['monto_tramites'] = 'monto_tramites';
		$campo['documento']['subtotal_sin_descuento'] = 'subtotal_sin_descuento';
		$campo['documento']['retainer_horas'] = 'retainer_horas';
		$campo['documento']['subtotal_gastos'] = 'subtotal_gastos';
		$campo['documento']['total_minutos'] = 'total_minutos';
		$campo['documento']['subtotal_gastos_sin_impuesto'] = 'subtotal_gastos_sin_impuesto';

		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);

		$xtabla = 'cobro';
		if ($cobro->EsCobrado() && $carga_documento) {
			//Existe el documento?
			$datos_cobro = new Documento($sesion);
			$datos_cobro->LoadByCobro($id_cobro);
			if ($datos_cobro->Loaded()) {
				$xtabla = 'documento';
				//$cobro_moneda = new DocumentoMoneda($sesion);
				//$cobro_moneda->LoadByCobro($id_cobro);
				$cobro_moneda = new CobroMoneda($sesion); // Cuando DocumentoMoneda esta averiguado hay que cambiar $cobro_moneda a un objeto Documento Moneda.
				$cobro_moneda->Load($id_cobro);
			}
		}
		if ($xtabla == 'cobro') {
			$datos_cobro = $cobro;
			$datos_cobro->Load($id_cobro);
			$cobro_moneda = new CobroMoneda($sesion);
			$cobro_moneda->Load($id_cobro);
		}
		/*		 * *
		 * INSTANCIAMOS LOS OBJETOS Y VARIABLES A UTILIZAR
		 * */
		$query = "SELECT id_moneda FROM prm_moneda ORDER BY id_moneda ASC";
		$lista_monedas = new ListaMonedas($sesion, '', $query);
		$opc_moneda_total = $datos_cobro->fields['opc_moneda_total'];
		$moneda_a_comparar_montos = "";
		$arr_resultado['forma_cobro'] = $cobro->fields['forma_cobro'];
		$arr_resultado['tabla'] = $xtabla;

		$arr_resultado['id_moneda'] = $datos_cobro->fields[$campo[$xtabla]['id_moneda']];
		$arr_resultado['id_moneda_monto'] = $datos_cobro->fields[$campo[$xtabla]['id_moneda_monto']];
		$arr_resultado['opc_moneda_total'] = $datos_cobro->fields[$campo[$xtabla]['opc_moneda_total']];
		$arr_resultado['id_cobro'] = $id_cobro;

		$arr_resultado['tipo_cambio_id_moneda'] = $cobro_moneda->moneda[$arr_resultado['id_moneda']]['tipo_cambio'];
		$arr_resultado['tipo_cambio_id_moneda_monto'] = $cobro_moneda->moneda[$arr_resultado['id_moneda_monto']]['tipo_cambio'];
		$arr_resultado['tipo_cambio_opc_moneda_total'] = $cobro_moneda->moneda[$arr_resultado['opc_moneda_total']]['tipo_cambio'];

		$arr_resultado['cifras_decimales_id_moneda'] = $cobro_moneda->moneda[$arr_resultado['id_moneda']]['cifras_decimales'];
		$arr_resultado['cifras_decimales_id_moneda_monto'] = $cobro_moneda->moneda[$arr_resultado['id_moneda_monto']]['cifras_decimales'];
		$arr_resultado['cifras_decimales_opc_moneda_total'] = $cobro_moneda->moneda[$arr_resultado['opc_moneda_total']]['cifras_decimales'];


		/*		 * *
		 * CALCULO FORMAS DE PAGO - INICIO
		 * */
		// CONDICION PARA FLAT FEE
		$hacer_calculo_normal = 0;
		$monto_subtotal_cap = UtilesApp::CambiarMoneda($datos_cobro->fields[$campo[$xtabla]['monto_subtotal']]//monto_moneda_l
						, $arr_resultado['tipo_cambio_id_moneda']//tipo de cambio ini
						, $arr_resultado['cifras_decimales_id_moneda']//decimales ini
						, $arr_resultado['tipo_cambio_id_moneda_monto']//tipo de cambio fin
						, $arr_resultado['cifras_decimales_id_moneda_monto']//decimales fin
		);
		$decimales_completos = 6;
		if (($arr_resultado['id_moneda'] != $arr_resultado['id_moneda_monto']) && ($arr_resultado['id_moneda_monto'] == $arr_resultado['opc_moneda_total'])) {
			if (($cobro->fields['forma_cobro'] == 'FLAT FEE')
					&& (empty($datos_cobro->fields[$campo[$xtabla]['descuento']]) || ($datos_cobro->fields[$campo[$xtabla]['descuento']] == 0))) {



				for ($i = 0; $i < $lista_monedas->num; $i++) {
					$id_moneda_obj = $lista_monedas->Get($i);
					$id_moneda_actual = $id_moneda_obj->fields['id_moneda'];
					$cifras_decimales_actual = $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales'];

					for ($a = 0; $a < count($arr_monto[$xtabla]); $a++) {
						if (($arr_monto[$xtabla][$a] == 'impuesto_gastos') || ($arr_monto[$xtabla][$a] == 'subtotal_gastos') || ($arr_monto[$xtabla][$a] == 'monto_gastos')) {
							$id_moneda_original = $arr_resultado['opc_moneda_total'];
						} else if ($arr_monto[$xtabla][$a] == 'monto_contrato') {
							$id_moneda_original = $arr_resultado['id_moneda_monto'];
						} else {
							$id_moneda_original = $arr_resultado['id_moneda'];
						}
						$arr_resultado[$arr_monto[$xtabla][$a]][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]//monto_moneda_l
										, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
										, $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales'] //decimales ini
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										, $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales'] //$cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
						);
						if ($arr_monto[$xtabla][$a] == $campo[$xtabla]['monto_subtotal']) {
							$arr_resultado['monto_subtotal_completo'][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]
										, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
										, $decimales_completos
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										, $decimales_completos
							);
					}
					}

					$arr_resultado['monto_subtotal_completo'][$id_moneda_actual] = UtilesApp::CambiarMoneda(
									UtilesApp::CambiarMoneda($cobro->fields[$campo[$xtabla]['monto_contrato']]//monto_moneda_l
											, $arr_resultado['tipo_cambio_id_moneda_monto']//tipo de cambio ini
											, $decimales_completos
											, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
											, $decimales_completos
									)
									+ $arr_resultado[$campo[$xtabla]['monto_tramites']][$id_moneda_actual], '', $decimales_completos, '', $decimales_completos);

					$arr_resultado[$campo[$xtabla]['monto_subtotal']][$id_moneda_actual] = UtilesApp::CambiarMoneda(
									UtilesApp::CambiarMoneda($cobro->fields[$campo[$xtabla]['monto_contrato']]//monto_moneda_l
											, $arr_resultado['tipo_cambio_id_moneda_monto']//tipo de cambio ini
											,  $arr_resultado['cifras_decimales_id_moneda_monto']//decimales ini
											, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
											, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
									)
									+ $arr_resultado[$campo[$xtabla]['monto_tramites']][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
					$arr_resultado['monto_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda(
									UtilesApp::CambiarMoneda($cobro->fields[$campo[$xtabla]['monto_contrato']]//monto_moneda_l
											, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio']//tipo de cambio ini
											, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales']//decimales ini
											, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
											, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
									)
									- $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual]
									+ $arr_resultado[$campo[$xtabla]['monto_tramites']][$id_moneda_actual], ''
									,  $cifras_decimales_actual
									, ''
									, $cifras_decimales_actual);
					$arr_resultado[$campo[$xtabla]['monto_trabajos']][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields[$campo[$xtabla]['monto_contrato']]//monto_moneda_l
									, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['monto_thh'][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh']//monto_moneda_l
										, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['monto_thh_estandar'][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh_estandar']//monto_moneda_l
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									,  $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['impuesto'][$id_moneda_actual] = UtilesApp::CambiarMoneda(($arr_resultado['monto_subtotal_completo'][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual])*($cobro->fields['porcentaje_impuesto'] / 100), '', $decimales_completos, '', $cifras_decimales_actual);
					$arr_resultado['saldo_honorarios'][$id_moneda_actual] =
							$arr_resultado[$campo[$xtabla]['monto']][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado[$campo[$xtabla]['monto_subtotal']][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual] + $arr_resultado['impuesto'][$id_moneda_actual], '', $decimales_completos, '', $decimales_completos);
				}
				$hacer_calculo_normal++;
			}
			// CONDICION PARA RETAINER Y PROPORCIONAL
			if ((($cobro->fields['forma_cobro'] == 'RETAINER') || ($cobro->fields['forma_cobro'] == 'PROPORCIONAL'))
					&& (60 * ($cobro->fields['retainer_horas']) >= $cobro->fields['total_minutos'])
					&& (empty($datos_cobro->fields[$campo[$xtabla]['descuento']]) || ($datos_cobro->fields[$campo[$xtabla]['descuento']] == 0))) {
				for ($i = 0; $i < $lista_monedas->num; $i++) {
					$id_moneda_obj = $lista_monedas->Get($i);
					$id_moneda_actual = $id_moneda_obj->fields['id_moneda'];
					$cifras_decimales_actual = $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales'];

					for ($a = 0; $a < count($arr_monto[$xtabla]); $a++) {
						if (($arr_monto[$xtabla][$a] == 'impuesto_gastos') || ($arr_monto[$xtabla][$a] == 'subtotal_gastos') || ($arr_monto[$xtabla][$a] == 'monto_gastos')) {
							$id_moneda_original = $arr_resultado['opc_moneda_total'];
						} else if ($arr_monto[$xtabla][$a] == 'monto_contrato') {
							$id_moneda_original = $arr_resultado['id_moneda_monto'];
						} else {
							$id_moneda_original = $arr_resultado['id_moneda'];
						}
						$arr_resultado[$arr_monto[$xtabla][$a]][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]//monto_moneda_l
										, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
										, $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
						);
						if ($arr_monto[$xtabla][$a] == $campo[$xtabla]['monto_subtotal']) {
							$arr_resultado['monto_subtotal_completo'][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]
										, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
										, $decimales_completos
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										, $decimales_completos
							);
					}
					}
					//if( round($cobro->fields[$campo[$xtabla]['monto_contrato']]/100) >= round($arr_resultado[$campo[$xtabla]['monto_trabajos']][$cobro->fields['id_moneda_monto']]/100) ) {
					if ($arr_resultado['tipo_cambio_id_moneda_monto'] > $arr_resultado['tipo_cambio_id_moneda']) {
						$moneda_a_comparar_montos = "id_moneda_monto";
					} else {
						$moneda_a_comparar_montos = "id_moneda";
					}
					if (round($arr_resultado[$campo[$xtabla]['monto_contrato']][$cobro->fields[$moneda_a_comparar_montos]]) >= round($arr_resultado[$campo[$xtabla]['monto_trabajos']][$cobro->fields[$moneda_a_comparar_montos]])) {
						$arr_resultado['monto_subtotal_completo'][$id_moneda_actual] = UtilesApp::CambiarMoneda(
										UtilesApp::CambiarMoneda($cobro->fields['monto_contrato']//monto_moneda_l
												, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio']//tipo de cambio ini
												, $decimales_completos
												, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
												, $decimales_completos
										)
										+ $arr_resultado[$campo[$xtabla]['monto_tramites']][$id_moneda_actual], '', $decimales_completos, '', $decimales_completos);

						$arr_resultado[$campo[$xtabla]['monto_subtotal']][$id_moneda_actual] = UtilesApp::CambiarMoneda(
										UtilesApp::CambiarMoneda($cobro->fields['monto_contrato']//monto_moneda_l
												, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio']//tipo de cambio ini
												, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales']//decimales ini
												, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
												, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
										)
										+ $arr_resultado[$campo[$xtabla]['monto_tramites']][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);


						$arr_resultado['monto_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda(
										UtilesApp::CambiarMoneda($cobro->fields['monto_contrato']//monto_moneda_l
													, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio']//tipo de cambio ini
												, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales']//decimales ini
												, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
												, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
										)
										- $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual]
										+ $arr_resultado[$campo[$xtabla]['monto_tramites']][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);


						$arr_resultado[$campo[$xtabla]['monto_trabajos']][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_contrato']//monto_moneda_l
										, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio']//tipo de cambio ini
										, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales']//decimales ini
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
						);
					} else {
						$arr_resultado['monto_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado[$campo[$xtabla]['monto_subtotal']][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
					}
					$arr_resultado['monto_thh'][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh']//monto_moneda_l
								, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['monto_thh_estandar'][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh_estandar']//monto_moneda_l
								, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);



					$arr_resultado['impuesto'][$id_moneda_actual] = UtilesApp::CambiarMoneda(($arr_resultado['monto_subtotal_completo'][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual]) * ($cobro->fields['porcentaje_impuesto'] / 100), $arr_resultado['tipo_cambio_opc_moneda_total']//tipo de cambio ini
									, $decimales_completos
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cifras_decimales_actual);
					$arr_resultado['monto'][$id_moneda_actual] = UtilesApp::CambiarMoneda(
							$arr_resultado[$campo[$xtabla]['monto_subtotal']][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual] + $arr_resultado['impuesto'][$id_moneda_actual]
									, $arr_resultado['tipo_cambio_opc_moneda_total']//tipo de cambio ini
									,  $cifras_decimales_actual
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']); //decimales fin)
					$arr_resultado['saldo_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto'][$id_moneda_actual]
									, $arr_resultado['tipo_cambio_opc_moneda_total']//tipo de cambio ini
									, $cifras_decimales_actual
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']); //decimales fin)
				}
				$hacer_calculo_normal++;
			}
			// CONDICION PARA CAP
			if (($cobro->fields['forma_cobro'] == 'CAP')
					&& ($cobro->TotalCobrosCap($id_cobro) == 0)
					&& ($datos_cobro->fields[$campo[$xtabla]['monto_contrato']] <= $monto_subtotal_cap)) {
				for ($i = 0; $i < $lista_monedas->num; $i++) {
					$id_moneda_obj = $lista_monedas->Get($i);
					$id_moneda_actual = $id_moneda_obj->fields['id_moneda'];
					$cifras_decimales_actual = $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales'];

					for ($a = 0; $a < count($arr_monto[$xtabla]); $a++) {
						if (($arr_monto[$xtabla][$a] == 'impuesto_gastos') || ($arr_monto[$xtabla][$a] == 'subtotal_gastos') || ($arr_monto[$xtabla][$a] == 'monto_gastos')) {
							$id_moneda_original = $arr_resultado['opc_moneda_total'];
						} else if ($arr_monto[$xtabla][$a] == 'monto_contrato') {
							$id_moneda_original = $arr_resultado['id_moneda_monto'];
						} else {
							$id_moneda_original = $arr_resultado['id_moneda'];
						}
						$arr_resultado[$arr_monto[$xtabla][$a]][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]//monto_moneda_l
										, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
										, $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
						);
						if ($arr_monto[$xtabla][$a] == $campo[$xtabla]['monto_subtotal']) {
							$arr_resultado['monto_subtotal_completo'][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]
										, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
										, $decimales_completos
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										, $decimales_completos
							);
					}
					}
					$arr_resultado['monto_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda(
									UtilesApp::CambiarMoneda($cobro->fields['monto_contrato']//monto_moneda_l
												, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['tipo_cambio']//tipo de cambio ini
											, $cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales']//decimales ini
											, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
											, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
									)
									+ $arr_resultado[$campo[$xtabla]['monto_tramites']][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
					;
					$arr_resultado['monto_thh'][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh']//monto_moneda_l
								, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['monto_thh_estandar'][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh_estandar']//monto_moneda_l
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['impuesto'][$id_moneda_actual] = UtilesApp::CambiarMoneda(
									($arr_resultado['monto_subtotal_completo'][$id_moneda_actual] - $arr_resultado['descuento'][$id_moneda_actual]) * ($cobro->fields['porcentaje_impuesto'] / 100)
									, $arr_resultado['tipo_cambio_opc_moneda_total']//tipo de cambio ini
									, $decimales_completos
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['monto'][$id_moneda_actual] = UtilesApp::CambiarMoneda(
							$arr_resultado['monto_subtotal'][$id_moneda_actual] - $arr_resultado['descuento'][$id_moneda_actual] + $arr_resultado['impuesto'][$id_moneda_actual]
									, $arr_resultado['tipo_cambio_opc_moneda_total']//tipo de cambio ini
									, $arr_resultado['cifras_decimales_opc_moneda_total']//deciimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['saldo_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto'][$id_moneda_actual]
									, $arr_resultado['tipo_cambio_opc_moneda_total']//tipo de cambio ini
									, $arr_resultado['cifras_decimales_opc_moneda_total']//deciimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
				}
				$hacer_calculo_normal++;
			}/*
				for($e=0;$e<$lista_monedas->num;$e++)
				{
				$id_moneda_obj = $lista_monedas->Get($e);
				$id_moneda_actual = $id_moneda_obj->fields['id_moneda'];
				$arr_resultado['monto'][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$campo[$xtabla]['monto']]//monto_moneda_l
				,$arr_resultado['tipo_cambio_id_moneda']//tipo de cambio ini
				,$arr_resultado['cifras_decimales_id_moneda']//decimales ini
				,$arr_resultado['tipo_cambio_opc_moneda_total']//tipo de cambio fin
				,$arr_resultado['cifras_decimales_opc_moneda_total']//decimales fin
				);
				} */
		}
		/*		 * *
		 * CALCULO COBRO NORMAL
		 * */


		if ($hacer_calculo_normal == 0) {
			/*			 * *
			 * SI NO SE INDICO EL CALCULO DEL/LOS MONTO/S EN UNA MONEDA ESPECIFICA,
			 * SE CALCULA PARA TODAS LAS MONEDAS
			 * */



			if ($id_moneda == 0) {
				for ($e = 0; $e < $lista_monedas->num; $e++) {
					$id_moneda_obj = $lista_monedas->Get($e);
					$id_moneda_actual = $id_moneda_obj->fields['id_moneda'];
					$cifras_decimales_actual = $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales'];

					/*					 * *
					 * FOR PARA CALCULAR LOS MONTOS SOLICITADOS,
					 * SI NO SE INDICO ALGUN MONTO EN PARTICULAR,
					 * SE CALCULAN LOS MONOS INGRESADOS POR DEFECTOS
					 * */

					for ($a = 0; $a < count($arr_monto[$xtabla]); $a++) {
						if (($arr_monto[$xtabla][$a] == 'impuesto_gastos') || ($arr_monto[$xtabla][$a] == 'subtotal_gastos') || ($arr_monto[$xtabla][$a] == 'monto_gastos')) {
							$id_moneda_original = $arr_resultado['opc_moneda_total'];
						} else if ($arr_monto[$xtabla][$a] == 'monto_contrato') {
							$id_moneda_original = $arr_resultado['id_moneda_monto'];
						} else {
							$id_moneda_original = $arr_resultado['id_moneda'];
						}
						$arr_resultado[$arr_monto[$xtabla][$a]][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]//monto_moneda_l
										, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
										, $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										,  $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
						);
						if ($arr_monto[$xtabla][$a] == $campo[$xtabla]['monto_subtotal']) {
							$arr_resultado['monto_subtotal_completo'][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]
										, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
																																						, $decimales_completos
										, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
										, $decimales_completos
							);
					}
					}



					$monto_trabajo_con_descuento = $cobro->TotalCobrosCap($id_cobro) + $cobro->fields[$campo[$xtabla]['monto_trabajo']] - $datos_cobro->fields[$campo[$xtabla]['descuento']];
					$arr_resultado['monto_trabajo_con_descuento'][$id_moneda_actual] = UtilesApp::CambiarMoneda($monto_trabajo_con_descuento//monto_moneda_l
									, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['monto_thh'][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh']//monto_moneda_l
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
									,  $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);

					$arr_resultado['monto_thh_estandar'][$id_moneda_actual] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh_estandar']//monto_moneda_l
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					if ($xtabla == 'cobro') {
						$valor_monto_honorarios = $arr_resultado['monto_subtotal'][$id_moneda_actual] - $arr_resultado['descuento'][$id_moneda_actual];
					}
					if ($xtabla == 'documento') {
						$valor_monto_honorarios = $arr_resultado['subtotal_sin_descuento'][$id_moneda_actual];
					}
				/*	$arr_resultado['monto_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($valor_monto_honorarios
									, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
									,  $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
									,  $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
					);
					$arr_resultado['impuesto'][$id_moneda_actual] = ( $arr_resultado[$campo[$xtabla]['monto_subtotal']][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual]) * ($cobro->fields['porcentaje_impuesto'] / 100);

				//	$arr_resultado['monto'][$id_moneda_actual] = $arr_resultado[$campo[$xtabla]['monto_subtotal']][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual] + $arr_resultado['impuesto'][$id_moneda_actual]	;

				$arr_resultado['monto'][$id_moneda] = UtilesApp::CambiarMoneda($arr_resultado['monto_subtotal'][$id_moneda_actual] - $arr_resultado['descuento'][$id_moneda_actual] + $arr_resultado['impuesto'][$id_moneda_actual]
								, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio ini
								,  $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$id_moneda]['cifras_decimales']//decimales fin
					);

						$arr_resultado['saldo_honorarios'][$id_moneda_actual] = $arr_resultado['monto'][$id_moneda_actual]; */


				$arr_resultado['monto_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($valor_monto_honorarios, '', $cifras_decimales_actual, '', $cifras_decimales_actual);
					$arr_resultado['impuesto'][$id_moneda_actual] = UtilesApp::CambiarMoneda(($arr_resultado['monto_subtotal_completo'][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual]) * ($cobro->fields['porcentaje_impuesto'] / 100), '', $decimales_completos, '', $cifras_decimales_actual);
					$arr_resultado['monto'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado[$campo[$xtabla]['monto_subtotal']][$id_moneda_actual] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda_actual] + $arr_resultado['impuesto'][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
					$arr_resultado['saldo_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto'][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
				}
			}
			/*			 * *
			 * CALCULO DE LOS MONTOS PARA UNA MONEDA ESPECIFICA
			 * */ else {
				$cifras_decimales_actual = $cobro_moneda->moneda[$id_moneda]['cifras_decimales'];
				for ($a = 0; $a < count($arr_monto[$xtabla]); $a++) {
					if (($arr_monto[$xtabla][$a] == 'impuesto_gastos') || ($arr_monto[$xtabla][$a] == 'subtotal_gastos') || ($arr_monto[$xtabla][$a] == 'monto_gastos')) {
						$id_moneda_original = $arr_resultado['opc_moneda_total'];
					} else if ($arr_monto[$xtabla][$a] == 'monto_contrato') {
						$id_moneda_original = $arr_resultado['id_moneda_monto'];
					} else {
						$id_moneda_original = $arr_resultado['id_moneda'];
					}
					$arr_resultado[$arr_monto['cobro'][$a]][$id_moneda] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]//monto_moneda_l
									, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
									, $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
									, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
									, $cobro_moneda->moneda[$id_moneda]['cifras_decimales']//decimales fin
					);
					if ($arr_monto[$xtabla][$a] == $campo[$xtabla]['monto_subtotal']) {
						$arr_resultado['monto_subtotal_completo'][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields[$arr_monto[$xtabla][$a]]
									, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
									, $decimales_completos
									, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
									, $decimales_completos
						);
				}
				}

				$arr_resultado['monto_honorarios'][$id_moneda] = UtilesApp::CambiarMoneda($suma_monto_honorario_moneda_tarifa//monto_moneda_l
								, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
								,  $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$id_moneda]['cifras_decimales']//decimales fin
				);
				$arr_resultado['monto_thh'][$id_moneda] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh']//monto_moneda_l
									, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
								, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$id_moneda]['cifras_decimales']//decimales fin
				);
				$arr_resultado['monto_thh_estandar'][$id_moneda] = UtilesApp::CambiarMoneda($cobro->fields['monto_thh_estandar']//monto_moneda_l
								, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
								, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$id_moneda]['cifras_decimales']//decimales fin
				);
				$arr_resultado['impuesto'][$id_moneda] = UtilesApp::CambiarMoneda(($arr_resultado['monto_subtotal_completo'][$id_moneda] - $arr_resultado[$campo[$xtabla]['descuento']][$id_moneda]) * ($cobro->fields['porcentaje_impuesto'] / 100)
								, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
								, $decimales_completos
								, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$id_moneda]['cifras_decimales']//decimales fin
				);
				$arr_resultado['monto'][$id_moneda] = UtilesApp::CambiarMoneda($arr_resultado['monto_subtotal'][$id_moneda_actual] - $arr_resultado['descuento'][$id_moneda_actual] + $arr_resultado['impuesto'][$id_moneda_actual]
								, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
								,  $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$id_moneda]['cifras_decimales']//decimales fin
				);
				$arr_resultado['saldo_honorarios'][$id_moneda] = UtilesApp::CambiarMoneda($arr_resultado['monto'][$id_moneda_actual]
								, $cobro_moneda->moneda[$id_moneda_original]['tipo_cambio']//tipo de cambio ini
								, $cobro_moneda->moneda[$id_moneda_original]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$id_moneda]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$id_moneda]['cifras_decimales']//decimales fin
				);
			}
		}
		/**
		 * Calculos comunos que no dependen a la forma de cobro.
		 * Principalmente se trata de totales y impuestos.
		 */
		$arr_resultado['gastos'] = array();
		$arr_resultado['tipo_cambio'] = array();
		for ($e = 0; $e < $lista_monedas->num; $e++) {
			$id_moneda_obj = $lista_monedas->Get($e);
			$id_moneda_actual = $id_moneda_obj->fields['id_moneda'];

			$arr_resultado['tipo_cambio'][$id_moneda_actual] = $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio'];

			$cifras_decimales_actual = $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales'];
			//$arr_resultado['impuesto_gastos'][$id_moneda_actual]							=	UtilesApp::CambiarMoneda($arr_resultado['subtotal_gastos'][$id_moneda_actual]*($cobro->fields['porcentaje_impuesto_gastos']/100),'',$cifras_decimales_actual,'',$cifras_decimales_actual);
			$arr_resultado['impuesto_gastos'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_datos_gastos['gasto_impuesto']//monto_moneda_l
							, $cobro_moneda->moneda[$arr_resultado['opc_moneda_total']]['tipo_cambio']//tipo de cambio ini
							, $cobro_moneda->moneda[$arr_resultado['opc_moneda_total']]['cifras_decimales']//decimales ini
							, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
							, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
			);

			foreach ($arr_datos_gastos as $campo => $valor) {
				if (is_array($valor))
					continue;
				$arr_resultado['gastos'][$campo][$id_moneda_actual] = UtilesApp::CambiarMoneda($valor//monto_moneda_l
								, $cobro_moneda->moneda[$arr_resultado['opc_moneda_total']]['tipo_cambio']//tipo de cambio ini
								, $cobro_moneda->moneda[$arr_resultado['opc_moneda_total']]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']//decimales fin
				);
			}

			if ($xtabla == 'cobro') {
				$arr_resultado['subtotal_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto_subtotal'][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
				$arr_resultado['descuento_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['descuento'][$id_moneda_actual]
								, ''
								, $cifras_decimales_actual
								, ''
								, $cifras_decimales_actual
				);
				$arr_resultado['saldo_gastos'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto_gastos'][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
			} else if ($xtabla == 'documento') {
				$arr_resultado['monto_subtotal'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['subtotal_honorarios'][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
				$arr_resultado['descuento'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['descuento_honorarios'][$id_moneda_actual]
								, ''
								, $cifras_decimales_actual
								, '', $cifras_decimales_actual);
				$arr_resultado['saldo_honorarios'][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields['saldo_honorarios'], $cobro_moneda->moneda[$datos_cobro->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$datos_cobro->fields['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio'], $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']);
				$arr_resultado['saldo_gastos'][$id_moneda_actual] = UtilesApp::CambiarMoneda($datos_cobro->fields['saldo_gastos'], $cobro_moneda->moneda[$datos_cobro->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$datos_cobro->fields['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$id_moneda_actual]['tipo_cambio'], $cobro_moneda->moneda[$id_moneda_actual]['cifras_decimales']);
				$arr_resultado['subtotal_gastos'][$id_moneda_actual] = $arr_resultado['subtotal_gastos'][$id_moneda_actual] + $arr_resultado['subtotal_gastos_sin_impuesto'][$id_moneda_actual];
			}

			$arr_resultado['monto_gastos'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['subtotal_gastos'][$id_moneda_actual] + $arr_resultado['impuesto_gastos'][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
			$arr_resultado['saldo_gastos'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['saldo_gastos'][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);
			$arr_resultado['monto_iva'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['impuesto'][$id_moneda_actual] + $arr_resultado['impuesto_gastos'][$id_moneda_actual], '', $cifras_decimales_actual, '', $cifras_decimales_actual);

			$arr_resultado['monto_iva_hh'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['impuesto'][$id_moneda_actual], '', 6, '', $cifras_decimales_actual);
			$arr_resultado['monto_iva_gastos'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['impuesto_gastos'][$id_moneda_actual], '', 6, '', $cifras_decimales_actual);

			$arr_resultado['monto_total_cobro'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto'][$id_moneda_actual] + $arr_resultado['monto_gastos'][$id_moneda_actual], '', 6, '', $cifras_decimales_actual); //monto_total_cobro: monto(moneda_tarifa)+monto_gastos(moneda_total)
			$arr_resultado['monto_total_cobro_thh'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto_thh'][$id_moneda_actual] + $arr_resultado['monto_gastos'][$id_moneda_actual], '', 6, '', $cifras_decimales_actual); //monto_total_cobro: monto(moneda_tarifa)+monto_gastos(moneda_total)
			$arr_resultado['monto_cobro_original'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto_honorarios'][$id_moneda_actual] + $arr_resultado['subtotal_gastos'][$id_moneda_actual], '', 6, '', $cifras_decimales_actual);
			$arr_resultado['monto_cobro_original_con_iva'][$id_moneda_actual] = UtilesApp::CambiarMoneda($arr_resultado['monto_honorarios'][$id_moneda_actual] + $arr_resultado['subtotal_gastos'][$id_moneda_actual] + $arr_resultado['monto_iva'][$id_moneda_actual], '', 6, '', $cifras_decimales_actual);
		}
		return $arr_resultado;
	}

	/* Replica el calculo de cobro_total_gastos y cobro_base_gastos en GuardarCobro */
	/* opc: 'listar_detalle' entrega listado de gastos */

	public static function ProcesaGastosCobro($sesion, $id_cobro, $opc = array('listar_detalle'), $soloegreso = false) {
		#GASTOS del Cobro
		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);

		//Tipo de cambios del cobro de (cobro_moneda)
		$cobro_moneda = new CobroMoneda($sesion);
		$cobro_moneda->Load($id_cobro);

		$moneda_base = Utiles::MonedaBase($sesion);

		$query = "SELECT SQL_CALC_FOUND_ROWS
					cta_corriente.id_movimiento,
					cta_corriente.descripcion,
					prm_proveedor.id_proveedor as id_proveedor,
					prm_proveedor.glosa as glosa_proveedor,
					usuario.username as id_usuario,
					usuario.username as username,
					cta_corriente.fecha,
					cta_corriente.id_moneda,
					cta_corriente.egreso,
					cta_corriente.monto_cobrable,
					cta_corriente.ingreso,
					cta_corriente.id_movimiento,
					cta_corriente.codigo_asunto,
					cta_corriente.con_impuesto,
					cta_corriente.numero_documento,
					prm_cta_corriente_tipo.glosa AS tipo_gasto,
					IF(descripcion like 'Saldo aprovisionado%','SI','NO') as es_liquido_provision
				FROM cta_corriente
				LEFT JOIN asunto USING(codigo_asunto)
				LEFT JOIN prm_cta_corriente_tipo ON cta_corriente.id_cta_corriente_tipo = prm_cta_corriente_tipo.id_cta_corriente_tipo
				LEFT JOIN prm_proveedor ON cta_corriente.id_proveedor = prm_proveedor.id_proveedor
				LEFT JOIN usuario ON cta_corriente.id_usuario_orden = usuario.id_usuario
				WHERE cta_corriente.id_cobro='$id_cobro'";

		$query .= $soloegreso ? ' AND egreso > 0 ' : ' AND (egreso > 0 OR ingreso > 0) ';

		$query .= "AND cta_corriente.incluir_en_cobro = 'SI'
							AND cta_corriente.cobrable = 1
							ORDER BY cta_corriente.fecha ASC";

		$lista_gastos = new ListaGastos($sesion, '', $query);

		$cobro_total_gasto = 0;
		$cobro_base_gastos = 0;
		$subtotal_gastos_con_impuestos = 0;
		$subtotal_gastos_sin_impuestos = 0;
		$subtotal_gastos_solo_provision = 0;
		$subtotal_gastos_sin_provision = 0;

		$lista = array();

		for ($v = 0; $v < $lista_gastos->num; $v++) {
			$gasto = $lista_gastos->Get($v);
			$suma_a_base = 0;
			$suma_a_original = 0;
			$suma_a_total = 0;
			$suma_total_impuesto = 0;
			$suma_fila = 0;
			//cobro_base_gastos en moneda base
			if ($gasto->fields['egreso'] > 0) {
				//$suma_a_total += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
				$suma_a_total += UtilesApp::CambiarMoneda($gasto->fields['monto_cobrable']//monto_moneda_l
								, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
								, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales']//decimales fin
				);
				$suma_a_original += $gasto->fields['monto_cobrable'];
				$suma_a_base += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $moneda_base['tipo_cambio']; #revisar 15-05-09
				$suma_fila = UtilesApp::CambiarMoneda($gasto->fields['monto_cobrable']//monto_moneda_l
								, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
								, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales']//decimales fin
				);
			} elseif ($gasto->fields['ingreso'] > 0) {
				$suma_a_total -= UtilesApp::CambiarMoneda($gasto->fields['monto_cobrable']//monto_moneda_l
								, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
								, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales']//decimales fin
				);
				$suma_a_original -= $gasto->fields['monto_cobrable'];
				$suma_a_base -= $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $moneda_base['tipo_cambio']; #revisar 15-05-09
				$suma_fila = (-1) * UtilesApp::CambiarMoneda($gasto->fields['monto_cobrable']//monto_moneda_l
								, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']//tipo de cambio ini
								, $cobro_moneda->moneda[$gasto->fields['id_moneda']]['cifras_decimales']//decimales ini
								, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']//tipo de cambio fin
								, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales']//decimales fin
				);
			}

			if ($gasto->fields['con_impuesto'] == 'SI') {
				$suma_total_impuesto = $suma_a_total * ($cobro->fields['porcentaje_impuesto_gastos'] / 100);
				$subtotal_gastos_con_impuestos += $suma_fila;
			} else {
				$subtotal_gastos_sin_impuestos += $suma_fila;
			}

			if ($gasto->fields['es_liquido_provision'] == 'SI') {
				$subtotal_gastos_solo_provision += $suma_fila;
			} else {
				$subtotal_gastos_sin_provision += $suma_fila;
			}

			$cobro_base_gastos += $suma_a_base;
			$cobro_total_gasto += $suma_a_total;

			$cobro_total_gasto_impuestos += $suma_total_impuesto;

			if (in_array('listar_detalle', $opc)) {
				$id_gasto = $gasto->fields['id_movimiento'];
				$lista[$v]['id_movimiento'] = $id_gasto;
				$lista[$v]['monto_total'] = $suma_a_total;
				$lista[$v]['monto_base'] = $suma_a_base;
				$lista[$v]['monto_original'] = $suma_a_original;
				$lista[$v]['con_impuesto'] = $gasto->fields['con_impuesto'];
				$lista[$v]['monto_total_impuesto'] = $suma_total_impuesto;
				$lista[$v]['monto_total_mas_impuesto'] = $suma_total_impuesto + $suma_a_total;
				$lista[$v]['descripcion'] = $gasto->fields['descripcion'];
				$lista[$v]['id_proveedor'] = $gasto->fields['id_proveedor'];
				$lista[$v]['glosa_proveedor'] = $gasto->fields['glosa_proveedor'];
				$lista[$v]['id_usuario'] = $gasto->fields['id_usuario'];
				$lista[$v]['username'] = $gasto->fields['username'];
				$lista[$v]['codigo_asunto'] = $gasto->fields['codigo_asunto'];
				$lista[$v]['id_moneda'] = $gasto->fields['id_moneda'];
				$lista[$v]['fecha'] = $gasto->fields['fecha'];
				$lista[$v]['numero_documento'] = $gasto->fields['numero_documento'];
				$lista[$v]['tipo_gasto'] = $gasto->fields['tipo_gasto'];
				$lista[$v]['es_liquido_provision'] = $gasto->fields['es_liquido_provision'];
			}
		}
		$resultados = array(
			'gasto_total' => $cobro_total_gasto,
			'gasto_base' => $cobro_base_gastos,
			'gasto_impuesto' => $cobro_total_gasto_impuestos,
			'gasto_total_con_impuesto' => $cobro_total_gasto + $cobro_total_gasto_impuestos,
			'gasto_detalle' => $lista,
			'subtotal_gastos_con_impuestos' => $subtotal_gastos_con_impuestos,
			'subtotal_gastos_sin_impuestos' => $subtotal_gastos_sin_impuestos,
			'subtotal_gastos_solo_provision' => $subtotal_gastos_solo_provision,
			'subtotal_gastos_sin_provision' => $subtotal_gastos_sin_provision,
			'subtotal_gastos_diff_con_sin_provision' => $subtotal_gastos_solo_provision + $subtotal_gastos_sin_provision);
		return $resultados;
	}

	function obtener_navegador() {
		$iexp = $_SERVER[HTTP_USER_AGENT];
		if (strstr($iexp, "MSIE")) {
			$xnavegador_usado = 'IE';
		}
		if (strstr($iexp, "mozilla")) {
			$xnavegador_usado = 'FIREFOX';
		}
		return $xnavegador_usado;
	}

	function glosaHora2Minuto($glosa_hora) {
		list($xhh, $xmm) = split(":", $glosa_hora);
		//validar  hora y min que senan positivos
		$m = (int) $xmm;
		$h = (int) $xhh;
		if ($m < 0) {
			$m = $m * (-1);
		}
		if ($h < 0) {
			$h = $h * (-1);
		}
		//pasar la hora a minutos
		$horaEnMim = $h * 60;
		//sumo los minutos
		$total_min = $horaEnMim + $m;
		//retorno suma
		return $total_min;
	}

	function PrintMenuDisenoNuevoPrototype($sesion, $url_actual) {
		$actual = split('\?', $url_actual);
		$url_actual = $actual[0];
		$bitmodfactura = UtilesApp::GetConf($sesion, 'NuevoModuloFactura');
		switch ($url_actual) {
			case '/app/interfaces/agregar_tarifa.php': $url_actual = '/app/interfaces/agregar_tarifa.php?id_tarifa_edicion=1';
				break;
			case '/app/interfaces/tarifas_tramites.php': $url_actual = '/app/interfaces/tarifas_tramites.php?id_tramite_tarifa_edicion=1';
				break;
			case '/app/interfaces/agregar_cliente.php': $url_actual = '/app/interfaces/clientes.php';
				break;
			case '/app/interfaces/agregar_asunto.php': $url_actual = '/app/interfaces/asuntos.php';
				break;
			case '/app/usuarios/usuario_paso2.php': $url_actual = '/app/usuarios/usuario_paso1.php';
				break;
			case '/app/interfaces/reportes_asuntos.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/resumen_cliente.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_facturacion_pendiente.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_cobros_por_area.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_resumen_cobranza.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_morosidad.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/resumen_abogado.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reportes_usuarios.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reportes_horas.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/olap.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reporte_avanzado.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reporte_financiero.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reporte_costos.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/planillas/planilla_participacion_abogado.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/app/interfaces/reporte_consolidado.php': $url_actual = '/app/interfaces/reportes_especificos.php';
				break;
			case '/fw/tablas/agregar_campo.php': $url_actual = '/fw/tablas/mantencion_tablas.php';
				break;
		}
		$lista_menu_permiso = Html::ListaMenuPermiso($sesion);
		$query = "SELECT codigo_padre FROM menu WHERE url='$url_actual'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($codigo) = mysql_fetch_array($resp);
		$menu_html = "<!-- Menu Section--> \n";
		$menu_html .= <<<HTML
				<div id="droplinetabs1" class="droplinetabs"><ul>
HTML;
		if (!$bitmodfactura)
			$bitmodfactura = '0';
		$query = "SELECT * from menu WHERE tipo=1 and codigo in ('$lista_menu_permiso')  and bitmodfactura<=$bitmodfactura ORDER BY orden"; //Tipo=1 significa menu principal
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		for ($i = 0; $row = mysql_fetch_assoc($resp); $i++) {
			$glosa_menu = $row['glosa'];
			if ($codigo == $row['codigo']) {
				$active = 'active=true';
				$estilo_con_margin = 'style="margin:0 4px 0 10px;"';
				$estilo = 'style="color:#FFFFFF; align:center;"';
				if (UtilesApp::obtener_navegador() == 'IE') {
					$active = 'active=false';
					$estilo_con_margin = 'style="margin:0 4px 0 10px;"';
					$estilo = 'style="color:#FFFFFF; align:center;"';
				}
			} else {
				$estilo_con_margin = 'style="margin: 0 4px 0 10px;"';
				$active = 'active=false';
				$estilo = 'style="align:center;"';
			}
			//Ahora imprimo los sub-menu
			$query = "SELECT * from menu WHERE tipo=0 and codigo in ('$lista_menu_permiso') and codigo_padre='${row['codigo']}'  and bitmodfactura<=$bitmodfactura  ORDER BY orden";
			//Tipo=0 significa menu secundario
			$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			$root_dir = Conf::RootDir();
			for ($j = 0; $row2 = mysql_fetch_assoc($resp2); $j++) {
				if ($j == 0 && $i == 0) {
					$menu_html .= <<<HTML
											<li $active><div id="top_tap_$i"><a href="$root_dir${row2['url']}" class="a_color_activo" $estilo_con_margin>
																				<!--[if IE]><b class="spiffy">
																				<b class="spiffy1"><b class="color_activo" style="width:97px;"></b></b>
																				<b class="spiffy2"><b class="color_activo" style="width:101px;"></b></b>
																				<b class="spiffy3 color_activo" style="width:102px;"></b>
																				<b class="spiffy4 color_activo" style="width:103px;"></b>
																				<b class="spiffy5 color_activo" style="width:103px;"></b></b>
																				<div class="spiffyfg"><![endif]--><span>${row['glosa']}</span><!--[if IE]>
																				</div></b><![endif]--></a></div>
											<ul $active style="display: none;" class="top">
HTML;
				} else if ($j == 0) {
					$menu_html .= <<<HTML
											<li $active><div id="top_tap_$i"><a href="$root_dir${row2['url']}" class="a_color_activo" $estilo>
																				<!--[if IE]><b class="spiffy">
																				<b class="spiffy1"><b class="color_activo" style="width:62px;"></b></b>
																				<b class="spiffy2"><b class="color_activo" style="width:66px;"></b></b>
																				<b class="spiffy3 color_activo" style="width:67px;"></b>
																				<b class="spiffy4 color_activo" style="width:68px;"></b>
																				<b class="spiffy5 color_activo" style="width:68px;"></b></b>
																				<div class="spiffyfg"><![endif]--><span>${row['glosa']}</span><!--[if IE]>
																				</div></b><![endif]--></a></div>
											<ul $active style="display: none;" class="top">
HTML;
				}
				$menu_html .= <<<HTML
									<li><a class="corner_round" href="$root_dir${row2['url']}" $estilo>${row2['glosa']}</a></li>
HTML;
			}
			$menu_html .= <<<HTML
								</ul></li>
HTML;
		}
		$menu_html .= <<<HTML
				</ul></div><div id="fd_menu_grey" class="barra_fija"><ul active=true>
HTML;
		$query = "SELECT * FROM menu WHERE codigo_padre='$codigo' AND tipo=0 AND codigo in ('$lista_menu_permiso') ORDER BY orden";
		$resp3 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		for ($j = 0; $row3 = mysql_fetch_assoc($resp3); $j++) {
			if ($url_actual == $row3['url']) {
				$activo_adentro_ie = 'style="text-decoration: underline;"';
				$activo_adentro_otros = 'style="background: #119011;-webkit-border-radius: 5px; -ms-border-radius: 5px;-moz-border-radius: 5px;-khtml-border-radius: 5px;border-radius: 5px;"';
			} else {
				$activo_adentro_ie = '';
				$activo_adentro_otros = '';
			}
			$menu_html .= <<<HTML
									<!--[if IE]><li><a href="$root_dir${row3['url']}" $activo_adentro_ie><span>${row3['glosa']}</span></a></li><![endif]-->
									<!--[if !IE]><!--><li><a href="$root_dir${row3['url']}" $activo_adentro_otros><span>${row3['glosa']}</span></a></li><!--<![endif]-->
HTML;
		}
		$menu_html .= <<<HTML
			</ul>
HTML;
		$menu_html .= $vinculo_ayuda;
		$menu_html .= <<<HTML
		 </div>
HTML;

		$menu_html.="<!-- End Menu Section--> \n";
		return $menu_html;
	}

	public static function PrintFormatoMoneda(&$Sesion, $monto, $id_moneda) {
		extract(array_pop(Moneda::GetMonedas($Sesion, $id_moneda)));
		return "$simbolo " . number_format($monto, $cifras_decimales, ',', '.');
	}

	public static function ObtenerFormatoIdioma($sesion) {
		$query_idioma = "SELECT formato_fecha as date_format, separador_decimales as decimal_separator, separador_miles as thousands_separator
			FROM prm_idioma WHERE codigo_idioma = (SELECT LOWER(valor_opcion) FROM configuracion WHERE glosa_opcion = 'Idioma')";
		$result = $sesion->pdodbh->query($query_idioma)->fetchAll(PDO::FETCH_ASSOC);
		if(!empty($result)){
			return $result[0];
		}
		return array('date_format' => '%d/%m/%Y', 'thousands_separator' => '.', 'decimal_separator' => ',');
	}

	/**
	 * Convierte cada llave-valor en UTF-8 cuando corresponda, el parámetro
	 * $encode permite realizar la acción inversa
	 * @param mixed $data Arreglo o string a modificar
	 * @param boolean $encode encode (true) o decode (false)
	 * @return mixed
	 */
	public static function utf8izar($data, $encode = true) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				// Previene doble codificación
				unset($data[$key]);
				$key = self::utf8izar($key, $encode);
				$data[$key] = self::utf8izar($value, $encode);
			}
		} else if (is_string($data)) {
			// ^ = XOR = or exclusivo = true && false || false && true
			if (mb_detect_encoding($data, 'UTF-8', true) == 'UTF-8' ^ $encode) {
				$data = $encode ? utf8_encode($data) : utf8_decode($data);
			}
		}
		return $data;
	}

	public static function ArregloMonedas($sesion) {
		$query = "SELECT
								prm_moneda.id_moneda,
								prm_moneda.tipo_cambio,
								prm_moneda.cifras_decimales,
								prm_moneda.glosa_moneda,
								prm_moneda.glosa_moneda_plural,
								prm_moneda.simbolo
							FROM prm_moneda";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		while (list($id_moneda, $tipo_cambio, $cifras_decimales, $glosa_moneda, $glosa_moneda_plural, $simbolo) = mysql_fetch_array($resp)) {
			$moneda[$id_moneda]['tipo_cambio'] = $tipo_cambio;
			$moneda[$id_moneda]['glosa_moneda'] = $glosa_moneda;
			$moneda[$id_moneda]['glosa_moneda_plural'] = $glosa_moneda_plural;
			$moneda[$id_moneda]['cifras_decimales'] = $cifras_decimales;
			$moneda[$id_moneda]['simbolo'] = $simbolo;
		}
		return $moneda;
	}

	/**
	 * Validate an email address.
	 * Provide email address (raw input)
	 * Returns true if the email address has the email
	 * address format and the domain exists.
	 */
	public static function isValidEmail($email) {
		$email = trim($email);
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		} else {
			$domain = substr($email, $atIndex + 1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else if ($domainLen < 1 || $domainLen > 255) {
				// domain part length exceeded
				$isValid = false;
			} else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
				// local part starts or ends with '.'
				$isValid = false;
			} else if (preg_match('/\\.\\./', $local)) {
				// local part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
				// character not valid in domain part
				$isValid = false;
			} else if (preg_match('/\\.\\./', $domain)) {
				// domain part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
				// character not valid in local part unless
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
					$isValid = false;
				}
			}
			if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
				// domain not found in DNS
				$isValid = false;
			}
		}
		return $isValid;
	}

	public static function CorreoAreaComercial(array $userdata,$cant_visitas=0) {
		$correos = array();
					$correo = array( 'mail' => 'areacomercial@lemontech.cl', 'nombre' => 'Equipo Time Tracking' );
					array_push($correos,$correo);
					$subject = 'Demo1: Visitante repetetivo.';
					$body = 'El visitante, <br><br>Nombre:       '.$userdata['nombre'].'
																						<br>Apellido1:    '.$userdata['apellido1'].'
																						<br>Apellido2:    '.$userdata['apellido2'].' 
																						<br>Empresa:      '.$userdata['empresa'].'
																						<br>Telefono:     '.$userdata['telefono'].'
																						<br>Mail:         '.$userdata['email'].' 
																						<br>País:         '.$userdata['pais'];
					if($cant_visitas>0) $body.="<br><br>ya ha ingresado $cant_visitas veces al sistema demo.";
					return Utiles::EnviarMail($sesion,$correos,$subject,$body,false);
	}
	public static  function CrearUsuario($sesion,array $userdata,$id_visitante=false) {
		$query = "SELECT id_notificacion_tt FROM usuario ORDER BY id_notificacion_tt DESC LIMIT 1";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($id_notificacion) = mysql_fetch_array( $resp ); 

					$query = "SELECT id_usuario FROM usuario ORDER BY id_usuario DESC LIMIT 1";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list( $id_usuario ) = mysql_fetch_array($resp);
					$id_usuario++;

					$usuario = new Usuario($sesion);
					$usuario->Edit('id_usuario',$id_usuario);
					$usuario->Edit('rut',$userdata['rut']);
					$usuario->Edit('email',$userdata['email']);
					$usuario->Edit('nombre',$userdata['nombre']);
					$usuario->Edit('apellido1',$userdata['apellido1']);
					$usuario->Edit('apellido2',$userdata['apellido2']);
                    $usuario->Edit('username',$userdata['nombre'].' '.$userdata['apellido1']);
					$usuario->Edit('password',md5('12345'));
					if($id_visitante) $usuario->Edit('id_visitante',$id_visitante);
					$usuario->Edit('id_notificacion_tt',$id_notificacion);
					$usuario->Write();

					$query = "INSERT INTO usuario_permiso( id_usuario, codigo_permiso ) 
												VALUES ( ".$id_usuario.", 'ADM' ),
															 ( ".$id_usuario.", 'ALL' ),
															 ( ".$id_usuario.", 'COB' ),
															 ( ".$id_usuario.", 'DAT' ),
															 ( ".$id_usuario.", 'OFI' ),
															 ( ".$id_usuario.", 'PRO' ),
															 ( ".$id_usuario.", 'REP' ),
															 ( ".$id_usuario.", 'REV' )";
					return mysql_query($query,$sesion->dbh);
}

}

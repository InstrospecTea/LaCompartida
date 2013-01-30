<?php
require_once dirname(dirname(__FILE__)) . '/conf.php';


$sesion = new Sesion(array('REP'));
//Revisa el Conf si esta permitido
if (method_exists('Conf', 'GetConf')) {
	if (!Conf::GetConf($sesion, 'ReportesAvanzados')) {
		header("location: reportes_especificos.php");
	}
} else if (method_exists('Conf', 'ReportesAvanzados')) {
	if (!Conf::ReportesAvanzados()) {
		header("location: reportes_especificos.php");
	}
}
else
	header("location: reportes_especificos.php");
$pagina = new Pagina($sesion);


$mis_reportes = array();
$mis_reportes_glosa = array();
$mis_reportes_envio = array();
$mis_reportes_segun = array();

if ($opc == 'eliminar_reporte') {
	$query = "DELETE FROM usuario_reporte  WHERE id_usuario = '" . $sesion->usuario->fields['id_usuario'] . "' AND reporte =  '" . mysql_real_escape_string($eliminado_reporte) . "';";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
}

if ($opc == 'nuevo_reporte') {
	$nuevo_reporte_envio = intval($nuevo_reporte_envio);
	$nuevo_reporte_segun = mysql_real_escape_string($nuevo_reporte_segun);
	$id_reporte_editado = intval($id_reporte_editado);
	if ($id_reporte_editado) {
		$query = "UPDATE usuario_reporte SET reporte= '" . mysql_real_escape_string($nuevo_reporte) . "',glosa='" . mysql_real_escape_string($nombre_reporte) . "' ,segun= '" . $nuevo_reporte_segun . "',envio=" . $nuevo_reporte_envio . " WHERE id_reporte = '" . $id_reporte_editado . "';";
	} else {
		$query = "INSERT INTO usuario_reporte (id_usuario,reporte,glosa,segun,envio) VALUES ('" . $sesion->usuario->fields['id_usuario'] . "' , '" . mysql_real_escape_string($nuevo_reporte) . "' , '" . mysql_real_escape_string($nombre_reporte) . "','" . $nuevo_reporte_segun . "'," . $nuevo_reporte_envio . " );";
	}
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
}

$query_mis_reportes = "SELECT reporte, glosa, segun, envio, id_reporte FROM usuario_reporte WHERE id_usuario = '" . $sesion->usuario->fields['id_usuario'] . "'";
$resp_mis_reportes = mysql_query($query_mis_reportes, $sesion->dbh) or Utiles::errorSQL($query_mis_reportes, __FILE__, __LINE__, $sesion->dbh);
while (list($reporte_encontrado, $nombre_reporte_encontrado, $segun_reporte_encontrado, $envio_reporte_encontrado, $id_reporte_encontrado) = mysql_fetch_array($resp_mis_reportes)) {
	$mis_reportes[] = $reporte_encontrado;
	$mis_reportes_glosa[] = $nombre_reporte_encontrado;
	$mis_reportes_envio[] = $envio_reporte_encontrado;
	$mis_reportes_segun[] = $segun_reporte_encontrado;
	$mis_reportes_id[] = $id_reporte_encontrado;
}




/* REPORTE AVANZADO. ESTA PANTALLA SOLO TIENE INPUTS DEL USUARIO. SUBMIT LLAMA AL TIPO DE REPORTE SELECCIONADO */
$pagina->titulo = __('Resumen actividades profesionales');

$tipos_de_dato = array();
$tipos_de_dato[] = 'horas_trabajadas';
$tipos_de_dato[] = 'horas_cobrables';
$tipos_de_dato[] = 'horas_no_cobrables';
$tipos_de_dato[] = 'horas_castigadas';
$tipos_de_dato[] = 'horas_visibles';
$tipos_de_dato[] = 'horas_cobradas';
$tipos_de_dato[] = 'horas_por_cobrar';
$tipos_de_dato[] = 'horas_pagadas';
$tipos_de_dato[] = 'horas_por_pagar';
$tipos_de_dato[] = 'horas_incobrables';
$tipos_de_dato[] = 'valor_cobrado';
$tipos_de_dato[] = 'valor_por_cobrar';
$tipos_de_dato[] = 'valor_pagado';
$tipos_de_dato[] = 'valor_por_pagar';
$tipos_de_dato[] = 'valor_incobrable';
$tipos_de_dato[] = 'rentabilidad';
$tipos_de_dato[] = 'valor_hora';
$tipos_de_dato[] = 'diferencia_valor_estandar';
$tipos_de_dato[] = 'valor_estandar';

$tipos_de_dato[] = 'valor_trabajado_estandar';
$tipos_de_dato[] = 'rentabilidad_base';
$tipos_de_dato[] = 'costo';
$tipos_de_dato[] = 'costo_hh';
if ($debug == 1) {
	$tipos_de_dato[] = 'valor_pagado_parcial';
	$tipos_de_dato[] = 'valor_por_pagar_parcial';
}

$estados_cobro = array("CREADO",
	"EMITIDO",
	"EN REVISION",
	"ENVIADO AL CLIENTE",
	"FACTURADO",
	"INCOBRABLE",
	"PAGADO",
	"PAGO PARCIAL");

$agrupadores = array(
	'glosa_cliente',
	'codigo_asunto',
	'glosa_asunto_con_codigo',
	'profesional',
	'estado',
	'id_cobro',
	'forma_cobro',
	'tipo_asunto',
	'area_asunto',
	'categoria_usuario',
	'area_usuario',
	'fecha_emision',
	'glosa_grupo_cliente',
	'id_usuario_responsable',
	'mes_reporte',
	'dia_reporte',
	'mes_emision',
	'grupo_o_cliente'
);
if (Conf::GetConf($sesion, 'EncargadoSecundario')) {
	$agrupadores[] = 'id_usuario_secundario';
}
if ($debug == 1) {
	$agrupadores[] = 'id_trabajo';
	$agrupadores[] = 'dia_corte';
	$agrupadores[] = 'dia_emision';
	$agrupadores[] = 'id_contrato';
}

$glosa_dato['codigo_asunto'] = "Código " . __('Asunto');

$glosa_dato['horas_trabajadas'] = "Total de Horas Trabajadas";
$glosa_dato['horas_cobrables'] = __("Total de Horas Trabajadas en asuntos Cobrables");
$glosa_dato['horas_no_cobrables'] = __("Total de Horas Trabajadas en asuntos no Cobrables");
$glosa_dato['horas_castigadas'] = __("Diferencia de Horas Cobrables con las Horas que ve el cliente en nota de Cobro");
$glosa_dato['horas_visibles'] = __("Horas que ve el Cliente en nota de cobro (tras revisión)");
$glosa_dato['horas_cobradas'] = __("Horas Visibles en Cobros que ya fueron Emitidos");
$glosa_dato['horas_por_cobrar'] = "Horas Visibles que aún no se Emiten al Cliente";
$glosa_dato['horas_pagadas'] = __("Horas Cobradas en Cobros con estado Pagado");
$glosa_dato['horas_por_pagar'] = __("Horas Cobradas que aún no han sido pagadas");
$glosa_dato['horas_incobrables'] = __("Horas en Cobros Incobrables");
$glosa_dato['valor_por_cobrar'] = __("Valor monetario estimado que corresponde a cada Profesional en horas por cobrar");
$glosa_dato['valor_cobrado'] = __("Valor monetario que corresponde a cada Profesional, en un Cobro ya Emitido");
$glosa_dato['valor_incobrable'] = __("Valor monetario que corresponde a cada Profesional, en un Cobro Incobrable");
$glosa_dato['valor_pagado'] = __("Valor Cobrado que ha sido Pagado");
$glosa_dato['valor_por_pagar'] = __("Valor Cobrado que aún no ha sido pagado");
$glosa_dato['rentabilidad'] = __("Valor Cobrado / Valor Estándar");
$glosa_dato['valor_hora'] = __("Valor Cobrado / Horas Cobradas");
$glosa_dato['diferencia_valor_estandar'] = __("Valor Cobrado - Valor Estándar");
$glosa_dato['valor_estandar'] = __("Valor Cobrado, si se hubiera usado THH Estándar");
$glosa_dato['valor_trabajado_estandar'] = __("Horas Trabajadas por THH Estándar, para todo Trabajo");
$glosa_dato['rentabilidad_base'] = __("Valor Cobrado / Valor Trabajado Estándar");
$glosa_dato['costo'] = __("Costo para la firma, por concepto de sueldos");
$glosa_dato['costo_hh'] = __("Costo HH para la firma, por concepto de sueldos");
$glosa_boton['planilla'] = "Despliega una Planilla con deglose por cada Agrupador elegido.";
$glosa_boton['excel'] = "Genera la Planilla como un Documento Excel.";
$glosa_boton['tabla'] = "Genera un Documento Excel con una tabla cruzada.";
$glosa_boton['barra'] = "Despliega un Gráfico de Barras, usando el primer Agrupador.";
$glosa_boton['torta'] = "Despliega un Gráfico de Torta, usando el primer Agrupador.";
$glosa_boton['dispersion'] = "Despliega un Gráfico de Dispersión, usando el primer Agrupador.";

$tipos_moneda = Reporte::tiposMoneda();

/* Calculos de fechas */
$hoy = date("Y-m-d");
if (!$fecha_anio)
	$fecha_anio = date('Y');
if (!$fecha_mes)
	$fecha_mes = date('m');

$fecha_ultimo_dia = date('t', mktime(0, 0, 0, $fecha_mes, 5, $fecha_anio));

/* Genero fecha_ini,fecha_fin para la semana pasada y el mes pasado. */
$week = date('W');
$year = date('Y');
$lastweek = $week - 1;
if ($lastweek == 0) {
	$lastweek = 52;
	$year--;
}
$lastweek = sprintf("%02d", $lastweek);
$semana_pasada = "'" . date('d-m-Y', strtotime("$year" . "W$lastweek" . "1")) . "','" . date('d-m-Y', strtotime("$year" . "W$lastweek" . "7")) . "'";


$last_month = strtotime("-" . (date('j')) . " day");
$mes_pasado = "'01" . date('-m-Y', $last_month) . "','" . date('t-m-Y', $last_month) . "'";

$actual = "'01-01-" . date('Y') . "','" . date('d-m-Y') . "'";

function celda($nombre) {
	global $tipo_dato;
	global $tipo_dato_comparado;
	global $comparar;
	global $glosa_dato;
	echo "<td id=\"" . $nombre . "\"rowspan=2 align=\"center\" class=";
	if ($tipo_dato == $nombre || (!isset($tipo_dato) && $nombre == 'horas_trabajadas' ))
		echo "boton_presionado";
	else if ($tipo_dato_comparado == $nombre && $comparar)
		echo "boton_comparar";
	else
		echo "boton_normal";
	echo " style=\"height:25px; font-size: 11px; vertical-align: middle; cursor:pointer; \"";
	echo "onclick= TipoDato('" . $nombre . "')";
	echo " title= \"" . __($glosa_dato[$nombre]) . "\"";
	echo " > " . __($nombre) . "</td>";
}

function celda_disabled($nombre) {
	echo "<td id=\"" . $nombre . "\"rowspan=2 align=\"center\" class= boton_disabled style=\"height:25px; font-size: 11px; vertical-align: middle;\"";
	echo " title= \"" . __($glosa_dato[$nombre]) . "\"";
	echo " > " . __($nombre) . "</td>";
}

function borde_abajo($colspan = 1) {
	echo "<td";
	if ($colspan != 1)
		echo " colspan=" . $colspan;
	echo " style=\"width:10px; font-size: 3px; border-bottom-style: dotted; border-width: 1px; \"> &nbsp; </td>";
}

function borde_derecha() {
	echo "<td rowspan=3 style=\"font-size: 3px; width:10px; border-right-style: dotted; border-width: 1px; \"> &nbsp; </td>";
}

function nada($numero = 1) {

	echo "<td colspan=\"$numero\" style=\"font-size: 3px; width:10px; height:7px; \"> &nbsp; </td>";
}

function titulo_proporcionalidad() {
	echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
	echo "<div id='titulo_proporcionalidad' style =\" height:25px; font-size: 14px;  display:inline;\" >";
	echo "&nbsp;&nbsp;" . __('Proporcionalidad') . ":</div>";
	echo "</td>";
}

function select_proporcionalidad() {
	global $proporcionalidad;
	$o1 = 'selected';
	$o2 = '';
	if ($proporcionalidad == 'cliente') {
		$o1 = '';
		$o2 = 'selected';
	}
	echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
	echo "<div id='select_proporcionalidad' style =\" height:25px; font-size: 14px;  display:inline;\" >";
	echo "&nbsp;&nbsp;<select name='proporcionalidad'>";
	echo "<option value='estandar' " . $o1 . ">" . __('Estándar') . "</option>";
	echo "<option value='cliente' " . $o2 . ">" . __('Cliente') . "</option>";
	echo "</select></td>";
}

function visible_moneda($s, $select = '') {
	global $tipo_dato;
	echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
	echo "<div id='moneda" . $select . "' style =\" height:25px; font-size: 14px; ";
	if (in_array($tipo_dato, array('valor_cobrado', 'valor_por_cobrar', 'valor_pagado', 'valor_por_pagar', 'valor_trabajado_estandar', 'costo', 'costo_hh')))
		echo " display:inline;\" >";
	else
		echo " display:none;\" >";
	echo $s . "</div>";

	echo "<div id='anti_moneda" . $select . "' style =\" ";
	if (!in_array($tipo_dato, array('valor_cobrado', 'valor_por_cobrar', 'valor_pagado', 'valor_por_pagar', 'valor_trabajado_estandar', 'costo', 'costo_hh')))
		echo " display:inline;\" >";
	else
		echo " display:none;\" >";
	echo "&nbsp; </div>";

	echo "</td>";
}

function moneda() {
	visible_moneda(__('Moneda') . ':');
}

function select_moneda() {
	global $sesion;
	global $id_moneda;
	if ($id_moneda)
		$moneda = $id_moneda;
	else
		$moneda = Moneda::GetMonedaReportesAvanzados($sesion);
	visible_moneda(Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $moneda, '', '', "60"), '_select');
}

function tinta2() {
	global $comparar;
	echo "<td rowspan=3 align=\"center\" style=\"vertical-align: middle; width:70px; height: 20px; \"> ";
	echo "<table id = \"tipo_tinta\" ";
	if (!$comparar)
		echo " style =\" display:none; \" ";
	else
		echo " ";
	echo ">";
	echo "<tr>";
	echo "<td> <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"rojo\" checked=\"checked\" > </td>";
	echo "<td style= \"background-color: red;\" >&nbsp;&nbsp;&nbsp;&nbsp;</td>";
	echo "<td> <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"azul\"> </td>";
	echo "<td style= \"background-color: blue;\" > &nbsp;&nbsp;&nbsp;&nbsp;</td>";
	echo "</tr>";
	echo "</table>";
	echo "&nbsp; </td>";
}

function tinta() {
	global $comparar;
	echo "<td rowspan=3 align=\"center\" style=\"vertical-align: middle; width:100px; height: 20px; \"> ";
	echo "<span id= \"tipo_tinta\" style =\" width: 100px; ";
	if (!$comparar)
		echo " display:none; ";
	echo " \" >";
	echo "<input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"rojo\" checked=\"checked\" >";
	echo "<span style= \"background-color: red;\" >&nbsp;&nbsp;&nbsp;&nbsp;</span>";
	echo " <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"azul\"> ";
	echo "<span style= \"background-color: blue;\" >&nbsp;&nbsp;&nbsp;&nbsp;</span> </span>";
	echo "&nbsp; </td>";
}

if (!isset($numero_agrupadores))
	$numero_agrupadores = 1;
if (!$popup) {
	$pagina->PrintTop($popup);
	/* Si se eligió fecha con el selector [MES] [AÑO] (o viene default), se cambia a lo indicado por este. */
	if (!$filtros_check && ($fecha_corta != 'anual' && $fecha_corta != 'semanal' && $fecha_corta != 'mensual')) {
		$fecha_m = '' . $fecha_mes;

		$fecha_fin = $fecha_ultimo_dia . "-" . $fecha_m . "-" . $fecha_anio;
		$fecha_ini = "01-" . $fecha_m . "-" . $fecha_anio;
	}
	?>
	<style>

		TD.boton_normal { width:100px;border: solid 2px #e0ffe0; background-color: #e0ffe0; }

		TD.boton_presionado { border: solid 2px red; background-color: #e0ffe0; }

		TD.boton_comparar { border: solid 2px blue; background-color: #e0ffe0; }

		TD.boton_disabled { border: solid 2px #e5e5e5; background-color: #e5e5e5; color:#444444;}

		TD.borde_rojo { border: solid 1px red; }

		TD.borde_azul { border: solid 1px blue; }

		TD.borde_blanco { border: solid 1px white; }

		input.btn{ margin:3px;}


		.visible{display:'block';}
		.invisible{display: none;}

		.editar_reporte{ padding:4px; margin-left: 10px; margin-right: 10px;}

	</style>

	<script type="text/javascript">

		function Agrupadores(num)
		{
			var numero_agrupadores = parseInt($('numero_agrupadores').value);
			numero_agrupadores += num;
			if(numero_agrupadores < 1)
				numero_agrupadores = 1;
			if(numero_agrupadores > 6)
				numero_agrupadores = 6;
			$('numero_agrupadores').value = numero_agrupadores;
			for(var i =0; i < 6; i++)
			{
				var selector = $('span_agrupador_'+i);
				if(i<numero_agrupadores)
					selector.show();
				else
					selector.hide();
			}
			//if(numero_agrupadores == 1)
			//	$('menos_agrupadores').hide();
			//else
			//	$('menos_agrupadores').show();

			//if(numero_agrupadores == 6)
			//$('mas_agrupadores').hide();
			//else
			//	$('mas_agrupadores').show();
			RevisarTabla();
			ActualizarNuevoReporte();
		}

		//Al cambiar un agrupador, en los agrupadores siguientes, el valor previo se hace disponible y el valor nuevo se indispone.
		function CambiarAgrupador(num)
		{
			var selector = $('agrupador_'+num);
			var selector_previo = $('agrupador_valor_previo_'+num);

			//Los selectores siguientes
			for(var i = num + 1; i < 6; i++)
			{
				var selector_siguiente = $('agrupador_'+i);
				//se indispone lo nuevo
				for(var j = 0; j < selector_siguiente.length; j++)
				{
					if(selector_siguiente.options[j].text == selector.options[selector.selectedIndex].text)
					{
						selector_siguiente.options[j]=null;
						CambiarAgrupador(i);	
					}
				}
				//y se dispone lo viejo, SOLO si no resultó elegido en uno anterior
				var valor = selector_previo.value;
				var txt = selector_previo.options[selector_previo.selectedIndex].text;
				var elegido_en_anterior = false;
				for(var k = i; k >= 0; k--)
				{
					var anterior = $('agrupador_'+k);
					if(anterior.options[anterior.selectedIndex].text == txt)
						elegido_en_anterior = true;
				}
				if(!elegido_en_anterior)
				{
					opc = new Option(txt,valor);
					selector_siguiente.options[selector_siguiente.options.length] = opc;
				}
			}
			//ahora selector_previo debe guardar el dato nuevo, para el proximo cambio
			selector_previo.options[0] = null;
			valor = selector.value;
			txt = selector.options[selector.selectedIndex].text;
			opc = new Option(txt,valor);
			selector_previo.options[0] = opc;	

			ActualizarNuevoReporte();
		}
		function iframelista() {
			jQuery('.divloading').remove();
			jQuery('#planilla').show();
		}
		function ResizeIframe(width, height)
		{
			currentfr = document.getElementById('planilla'); 
			currentfr.height = height+'px'; // currentfr.Document.body.scrollHeight;
			currentfr.width = width+'px'; // currentfr.Document.body.scrollHeight;

		}

		function Generar(form, valor)
		{
			form.opc.value = valor;

			form.vista.value = $('agrupador_0').value;
			for(i=1;i<$('numero_agrupadores').value;i++)
			{
				form.vista.value += '-'+$('agrupador_'+i).value;
			}
			if(valor == 'pdf')
			{
				form.action = 'html_to_pdf.php?frequire=reporte_avanzado.php&popup=1';
			}
			else if(valor == 'excel')
			{
				form.action = 'planillas/planilla_reporte_avanzado.php';
			}
			else if(valor == 'tabla')
			{
				form.action = 'planillas/planilla_reporte_avanzado_tabla.php';
			}
			else
				form.action = 'reporte_avanzado.php';
			form.submit();
		}

		function Rangos(obj, form)
		{
			/*var td_show = $('periodo_rango');
		var td_hide = $('periodo');

		if(obj.checked)
		{
			td_hide.style['display'] = 'none';
			td_show.style['display'] = '';
		}
		else
		{
			td_hide.style['display'] = '';
			td_show.style['display'] = 'none';
		}*/
		}

		/*Al activar la Comparación, debo hacer cosas Visibles y cambiar valores*/
		function Comparar()
		{
			var comparar = $('comparar');
			var tipo_de_dato = document.getElementById('tipo_dato');
			var tipo_de_dato_comparado = document.getElementById('tipo_dato_comparado');
			var tinta = document.getElementById('tipo_tinta');
			var vs = document.getElementById('vs');
			//var dispersion = document.getElementById('dispersion');
			var td_dato_comparado = document.getElementById('td_dato_comparado');
			var td_dato = document.getElementById('td_dato');
		

			//Si el valor comparado es igual al principal, debo cambiarlo:
			if(tipo_de_dato_comparado.selectedIndex == tipo_de_dato.selectedIndex)
			{
				if(tipo_de_dato_comparado.selectedIndex == 0)
					tipo_de_dato_comparado.selectedIndex = 1;
				else
					tipo_de_dato_comparado.selectedIndex = 0;
			}

			if (jQuery('#comparar').is(':checked'))
			{
			 
				jQuery('#dispersion ,#tipo_dato_comparado, #td_dato_comparado, #vs, #tipo_tinta').show();
			 
				jQuery('#td_dato').removeClass('borde_blanco').addClass('borde_rojo');
				jQuery('#td_dato_comparado').addClass('borde_azul');
				jQuery('#'+jQuery('#tipo_dato_comparado').val()).addClass('boton_comparar');
				
			} 	else	{
			 
			 
		 
				jQuery('#dispersion, #tipo_dato_comparado, #vs, #tipo_tinta').hide();
				 
				jQuery('#td_dato').removeClass('borde_rojo').addClass('borde_blanco');
				jQuery('#td_dato_comparado').removeClass('borde_azul');
				jQuery('#'+jQuery('#tipo_dato_comparado').val()).removeClass('boton_comparar');
			}
			
		 	
			RevisarTabla();
	
			RevisarMoneda();
			RevisarCircular();
			ActualizarNuevoReporte();
		}

		//Sincroniza los selectores de Campo de Fecha Visibles e Invisibles
		function SincronizarCampoFecha()
		{
			var filtros_check = $('filtros_check');
			var campo_fecha = document.formulario.campo_fecha;


			ActualizarNuevoReporte();
		}

		//Muestra Categoría y Area
		function Categorias(obj, form)
		{
			var td_show = $('area_categoria');
			if(obj.checked)
				td_show.style['display'] = 'inline';
			else
				td_show.style['display'] = 'none';
		}

		//Revisa visibilidad de la Moneda
		function RevisarMoneda()
		{
			var tipo_de_dato = document.getElementById('tipo_dato');
			var tipo_de_dato_comparado = document.getElementById('tipo_dato_comparado');
			var comparar = $('comparar');

			if(
					tipo_de_dato.value in
					{'costo':'', 'costo_hh':'', 'valor_pagado':'','valor_cobrado':'','valor_por_cobrar':'','valor_por_pagar':'','valor_incobrable':'','valor_hora':'','diferencia_valor_estandar':'','valor_trabajado_estandar':''}
						||
						(comparar.checked && tipo_de_dato_comparado.value in
					{'costo':'', 'costo_hh':'', 'valor_pagado':'','valor_cobrado':'','valor_por_cobrar':'','valor_por_pagar':'','valor_incobrable':'','valor_hora':'','diferencia_valor_estandar':'','valor_trabajado_estandar':''}
				)
			) {
				Monedas(true);
			} else {
				Monedas(false);
			}
		}

		//Revisa la visibilidad del botón de gráfico circular.
		function RevisarCircular()
		{
			var tipo_de_dato = document.getElementById('tipo_dato');
			var tipo_de_dato_comparado = document.getElementById('tipo_dato_comparado');
			var comparar = $('comparar');
			var circular = $('circular');

			if(!comparar.checked)
			{
				if(
				tipo_de_dato.value in
				{'rentabilidad':'','valor_hora':'','diferencia_valor_estandar':'','horas_castigadas':'','rentabilidad_base':''}
			)
					circular.style['display'] = 'none';
				else
					circular.style['display'] = '';
			}
			else
				circular.style['display'] = 'none';
		}

		function RevisarTabla()
		{
			var comparar = $('comparar');
			var vista = $('vista');
			var tabla = $('tabla');

			if(!comparar.checked && $('numero_agrupadores').value==2)
			{
				tabla.style['display'] = '';
			}
			else
				tabla.style['display'] = 'none';
		}

		function SelectValueSet(SelectName, Value)
		{
			SelectObject = $(SelectName);
			for(index = 0; 
			index < SelectObject.length; 
			index++) {
				if(SelectObject[index].value == Value)
					SelectObject.selectedIndex = index;
			}
		}
		/*Traduce los codigos utilizados para mostrarlos al usuario*/
		function Traductor(s)
		{
			switch(s)
			{
	<?php foreach ($tipos_de_dato as $td)
		echo "case '" . $td . "': return '" . __($td) . "'; " ?>
	<?php foreach ($agrupadores as $td)
		echo "case '" . $td . "': return '" . __($td) . "'; " ?>
					default: return s;
					}
				}

				/*Carga lo elegido en el deglose del nuevo reporte*/
				function ActualizarNuevoReporte()
				{
					var s = Traductor($('tipo_dato').value);
					if($('comparar').checked == true)
						s += ' vs. '+Traductor($('tipo_dato_comparado').value);
		

					$('tipos_datos_nuevo_reporte').innerHTML = s;

					s = '';
					var numero_agrupadores = parseInt($('numero_agrupadores').value);
					for(i = 0; i < numero_agrupadores; i++)
					{
						if(i != 0 && i != 3)
							s += ' - ';
						s += Traductor($('agrupador_'+i).value);
						if(i==2)
							s += '<br />';
					}
					$('agrupadores_nuevo_reporte').innerHTML = s;
		
					s = "<i>Puede seleccionar 'Semana pasada',<br /> 'Mes pasado' o 'Año en curso'.</i>";
					var fecha_corta = Form.getInputs('formulario','radio','fecha_corta').find(function(radio) { return radio.checked; }).value;

					if(fecha_corta == 'semanal')
						s = "<?php echo __('Semana pasada') ?>";
		
					if(fecha_corta == 'mensual')
						s = "<?php echo __('Mes pasado') ?>";

					if(fecha_corta == 'anual')
						s = "<?php echo __('Año en curso') ?>";

					$('periodo_nuevo_reporte').innerHTML = s;

					var campo_fecha =  document.formulario.campo_fecha;
					if(campo_fecha[0].checked)
						s = '<?php echo __("Trabajo") ?>';
					else if(campo_fecha[1].checked)
						s = '<?php echo __("Corte") ?>';
					else
						s = '<?php echo __("Emisión") ?>';

					$('segun_nuevo_reporte').innerHTML = s;

				}

				/*Carga los datos del reporte elegido en los selectores*/
				function CargarReporte()
				{
					var reporte = jQuery('#mis_reportes').val();
					if(reporte == "0")
					{
						jQuery('#span_eliminar_reporte, #span_editar_reporte').hide();
			
						return 0;
					}
					jQuery('#span_eliminar_reporte, #span_editar_reporte').show(); 

					/*Se añade 'envio'*/
					var separa = reporte.split('*');
					reporte = separa[0];
					var envio = separa[1];
					var segun = separa[2];

					var elementos = reporte.split('.');
					var datos = elementos[0].split(',');
					var agrupadores = elementos[1].split(',');

					SelectValueSet('tipo_dato',datos[0]);
					if(datos.size() == 2)
					{
						SelectValueSet('tipo_dato_comparado',datos[1]);
						$('comparar').checked = true;
					}
					else
						$('comparar').checked = false;		
					Comparar();
		
					Agrupadores( agrupadores.size() - parseInt($('numero_agrupadores').value));
		
					for(i = 0; i < agrupadores.size(); i++)
					{
						SelectValueSet('agrupador_'+i,agrupadores[i]);
						CambiarAgrupador(i);
					}

					if(segun == 'trabajo')
					{
			 
						jQuery('#campo_fecha_trabajo').click();
					}
					else if(segun == 'cobro')
					{
						jQuery('#campo_fecha_cobro').click();
			 
					}
					else
					{
		 
						jQuery('#campo_fecha_emision').click();
					}

					if(elementos.size()==3)
					{
						var periodo = elementos[2];
						if(periodo=='semanal')
						{
							jQuery('#fecha_corta_semana').click();
						}
						else if(periodo=='mensual')
						{
							jQuery('#fecha_corta_mes').click();
						}
						else if(periodo=='anual')
						{
							jQuery('#fecha_corta_anual').click();
						}
					}	
					jQuery('#eliminado_reporte').val(reporte);
				}
				/*Submitea la form para que genere un reporte segun lo elegido.*/
				function GenerarReporte()
				{
					var s = $('tipo_dato').value;
					if($('comparar').checked == true)
						s += ','+$('tipo_dato_comparado').value;
					s += '.';
					var numero_agrupadores = parseInt($('numero_agrupadores').value);
					for(i = 0; i < numero_agrupadores; i++)
					{
						if(i != 0)
							s += ',';
						s += $('agrupador_'+i).value;
					}
		
					var fecha_corta = Form.getInputs('formulario','radio','fecha_corta').find(function(radio) { return radio.checked; }).value;
		
					if(fecha_corta == 'semanal' || fecha_corta == 'mensual' || fecha_corta == 'anual')
						s += '.'+fecha_corta;

					var reporte_envio = 0;
					if(fecha_corta == 'semanal')
						reporte_envio = $('select_reporte_envio_semana').value;
					if(fecha_corta == 'mensual')
						reporte_envio = $('select_reporte_envio_mes').value;
					if(fecha_corta == 'anual')
						reporte_envio = $('select_reporte_envio_mes').value;

					$('nuevo_reporte_envio').value = reporte_envio;
		
					var campo_fecha =  document.formulario.campo_fecha;
					if(campo_fecha[0].checked)
						$('nuevo_reporte_segun').value = 'trabajo';
					else if(campo_fecha[1].checked)
						$('nuevo_reporte_segun').value = 'cobro';
					else
						$('nuevo_reporte_segun').value = 'emision';

					$('nuevo_reporte').value = s;
					$('formulario').opc.value = 'nuevo_reporte';


					$('formulario').submit();	
				}

				function EliminarReporte()
				{
					$('formulario').opc.value = 'eliminar_reporte';
					$('formulario').submit();	
				}

				function NuevoReporte()
				{
					$('div_nuevo_reporte').show();
					$('label_nuevo_reporte').show();
					$('label_editar_reporte').hide();
					$('nombre_reporte').value='';
					$('id_reporte_editado').value = 0;
					ActualizarNuevoReporte();
				}

				function EditarReporte()
				{
					var mis_reportes = $('mis_reportes');
					var texto = mis_reportes.selectedIndex >= 0 ? mis_reportes.options[mis_reportes.selectedIndex].innerHTML : undefined;
					var id_reporte = mis_reportes.selectedIndex >= 0 ? mis_reportes.options[mis_reportes.selectedIndex].getAttribute("data-id_reporte") : 0;

					var envio_reporte = mis_reportes.selectedIndex >= 0 ? 
						mis_reportes.options[mis_reportes.selectedIndex].getAttribute("data-envio_reporte") : 0;

					texto = texto.split('&nbsp;');
					$('nombre_reporte').value = texto[2];
					$('id_reporte_editado').value = id_reporte;


					$('div_nuevo_reporte').show();
					$('label_nuevo_reporte').hide();
					$('label_editar_reporte').show();
					ActualizarNuevoReporte();

					if(envio_reporte)
					{
						var fecha_corta = Form.getInputs('formulario','radio','fecha_corta').find(function(radio) { return radio.checked; }).value;
						if(fecha_corta == 'semanal')
							$('select_reporte_envio_semana').selectedIndex = envio_reporte;
						else 
							$('select_reporte_envio_mes').selectedIndex=envio_reporte;
					}
				}


				function SeleccionarSemana()
				{
					ActualizarPeriodo(<?php echo $semana_pasada ?>);
					$('reporte_envio_semana').show();
					$('reporte_envio_mes').hide();
					$('reporte_envio_selector').hide();
					ActualizarNuevoReporte();
				}
				function SeleccionarMes()
				{
					ActualizarPeriodo(<?php echo $mes_pasado ?>);
					$('reporte_envio_mes').show();
					$('reporte_envio_semana').hide();
					$('reporte_envio_selector').hide();
					ActualizarNuevoReporte();
				}
				function SeleccionarSelector()
				{
		 
					jQuery( "#fecha_ini, #fecha_fin" ).datepicker( "setDate", '01-'+jQuery('#fecha_mes').val()+'-'+jQuery('#fecha_anio').val() );
					jQuery( "#fecha_fin" ).datepicker( "setDate",new Date(jQuery('#fecha_anio').val(), jQuery('#fecha_mes').val(), 0) );
		
					$('reporte_envio_selector').show();
					$('reporte_envio_semana').hide();
					$('reporte_envio_mes').hide();
					ActualizarNuevoReporte();
				}
				function SeleccionarAnual()
				{
					ActualizarPeriodo(<?php echo $actual; ?>);
					$('reporte_envio_selector').hide();
					$('reporte_envio_semana').hide();
					$('reporte_envio_mes').show();
					ActualizarNuevoReporte();
				}


				//Hace visible o invisible el input de Moneda.
				function Monedas(visible)
				{
					var div_moneda = $('moneda');
					var div_anti_moneda = $('anti_moneda');
					var div_moneda_select = $('moneda_select');
					var div_anti_moneda_select = $('anti_moneda_select');

					if(visible)
					{
						div_moneda.style['display'] = 'inline';
						div_moneda_select.style['display'] = 'inline';
						div_anti_moneda.style['display'] = 'none';
						div_anti_moneda_select.style['display'] = 'none';
					}
					else
					{
						div_moneda.style['display'] = 'none';
						div_moneda_select.style['display'] = 'none';
						div_anti_moneda.style['display'] = 'inline';
						div_anti_moneda_select.style['display'] = 'inline';
					}
				}

				//Hace visible o invisible por ID [Para Inputs con + y -]
				function MostrarOculto(ID)
				{
					var table_full = $("full_"+ID);
					var table_mini = $("mini_"+ID);
					var check = $(ID+"_check");
					var img = $(ID+"_img");

					if(table_full.style['display']!="none")
					{
						table_full.style['display'] = "none";
						table_mini.style['display'] = "";
						check.checked = false;
						img.innerHTML = "<img src='../templates/default/img/mas.gif' border='0' title='Desplegar'>";
					}
					else
					{
						table_full.style['display'] = "";
						table_mini.style['display'] = "none";
						check.checked = true;
						img.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
					}
				}

				function MostrarLimite(visible)
				{
					var limite_check = $('limite_check');
					var limite_checkbox = $("limite_checkbox");
					if(visible)
					{
						limite_check.style['display'] = 'inline';
					}
					else
					{
						limite_checkbox.checked = false;
						limite_check.style['display'] = 'none';
					}
				}
				function ActualizarPeriodo(fi,ff)
				{
					jQuery('#fecha_ini').val(fi).datepicker( "option", "dateFormat", "dd-mm-yy" );	
					jQuery('#fecha_fin').val(ff).datepicker( "option", "dateFormat", "dd-mm-yy" );
		             

				}

				/*Setea el Tipo de Dato, marcando la selección, haciendo visible la moneda y el gráfico circular*/
				function TipoDato(valor)
				{
					var td_col = document.getElementById(valor);
					var tipo_de_dato = document.getElementById('tipo_dato');
					var tipo_de_dato_comparado = document.getElementById('tipo_dato_comparado');
					var comparar = document.getElementById('comparar');
					var tintas = document.getElementsByName('tinta');

					for(var i=0; i< tintas.length; i++)
					{
						if(tintas[i].checked)
							var tinta = tintas[i].value;
					}

					if(!comparar.checked || (comparar.checked && tinta=='rojo' && valor!= tipo_de_dato_comparado.value))
					{
						td_col.className = 'boton_presionado';

	<?php
	foreach ($tipos_de_dato as $key => $t_d) {
		echo " if(valor == '" . $t_d . "' ){ ";
		echo " tipo_de_dato.selectedIndex = " . $key . "; \n";
		echo "} else {td_col= document.getElementById('" . $t_d . "'); if(td_col.className=='boton_presionado')td_col.className = 'boton_normal'; }\n";
	}
	?>
						}
						else if(valor != tipo_de_dato.value)
						{
							td_col.className = 'boton_comparar';
	<?php
	foreach ($tipos_de_dato as $key => $t_d) {
		echo " if(valor == '" . $t_d . "' ){ ";
		echo " tipo_de_dato_comparado.selectedIndex = " . $key . "; \n";
		echo "} else {td_col= document.getElementById('" . $t_d . "'); if(td_col.className=='boton_comparar') td_col.className = 'boton_normal'; }\n";
	}
	?>
							}
							RevisarMoneda();
							RevisarCircular();
							ActualizarNuevoReporte();
						}

	</script>
	<?php
}
?>
<form method=post name=formulario action="" id=formulario autocomplete='off'>
	<input type=hidden name=opc id=opc value='print'>
	<input type=hidden name=debug id=debug value='<?php echo $debug ?>'>
<?php
if (!$popup) {
	?>
		<!-- Calendario DIV -->
		<div id="calendar-container" style="width:221px; position:absolute; display:none;">
			<div class="floating" id="calendar"></div>
		</div>
		<!-- Fin calendario DIV -->

		<!-- MIS REPORTES -->
		<table width="90%"><tr><td align="center">

					<fieldset width="100%" class="border_plomo tb_base" align="center"><legend><?php echo __('Mis Reportes') ?></legend>
						<div>

							<div style="float:right" align=right>
								<input type=button value="<?php echo __('Nuevo Reporte') ?>" onclick="NuevoReporte()"  />
							</div>

							<div>
								<select name="mis_reportes_elegido" id='mis_reportes'  >
									<option value="0"><?php echo __('Seleccione Reporte...') ?></option>
		<?php
		$estilo_eliminar_reporte = 'style="visibility:hidden"';
		if (empty($mis_reportes)) {
			echo '<option value="0">-- ' . __('No se han agregado reportes') . '. --</option>';
		} else {
			$j = 1;
			foreach ($mis_reportes as $indice_reporte => $mi_reporte) {
				$elementos = explode('.', $mi_reporte);
				$mis_datos = explode(',', $elementos[0]);
				$mis_agrupadores = explode(',', $elementos[1]);

				foreach ($mis_datos as $i => $mi_dato)
					$mis_datos[$i] = __($mi_dato);
				foreach ($mis_agrupadores as $i => $mi_agrupador)
					$mis_agrupadores[$i] = __($mi_agrupador);

				$selected_mi_reporte = '';
				if ($mi_reporte == $nuevo_reporte || $mis_reportes_elegido == $mi_reporte) {
					$selected_mi_reporte = 'selected';
					$estilo_eliminar_reporte = '';
				}
				$glosa = $mis_reportes_glosa[$indice_reporte];
				if (!$glosa)
					$glosa = implode(' vs. ', $mis_datos) . ' : ' . implode(' - ', $mis_agrupadores);


				$mi_reporte_envio = $mis_reportes_envio[$indice_reporte];
				$mi_reporte_segun = $mis_reportes_segun[$indice_reporte];
				$mi_reporte_id = $mis_reportes_id[$indice_reporte];
				//mi_reporte : tipo_dato(s),agrupador(es),periodo
				//mi_reporte_envio : dia (semana o mes)
				$string_mi_reporte = $mi_reporte . '*' . $mi_reporte_envio . '*' . $mi_reporte_segun;

				$num = $j < 10 ? '0' . $j : $j;
				echo '<option ' . $selected_mi_reporte . ' value="' . $string_mi_reporte . '" id="mi_reporte_' . $mi_reporte . '" data-id_reporte="' . $mi_reporte_id . '" data-envio_reporte="' . $mi_reporte_envio . '" >' . $num . ' )&nbsp;&nbsp;' . $glosa . "</option>";
				$j++;
			}
		}
		?>
								</select>
								<span id="span_editar_reporte" <?php echo $estilo_eliminar_reporte ?> >&nbsp;<a style='color:#009900' href="javascript:void(0)" onclick="EditarReporte();"><?php echo __('Editar') ?></a></span>&nbsp;
								<span id="span_eliminar_reporte" <?php echo $estilo_eliminar_reporte ?> >&nbsp;<a style='color:#CC1111' href="javascript:void(0)" onclick="EliminarReporte();"><?php echo __('Eliminar') ?></a></span>
								<input type=hidden name='nuevo_reporte' id='nuevo_reporte' />
								<input type=hidden name='nuevo_reporte_envio' id='nuevo_reporte_envio' />
								<input type=hidden name='nuevo_reporte_segun' id='nuevo_reporte_segun' />
								<input type=hidden name='eliminado_reporte' id='eliminado_reporte' />
							</div>

							<div id="div_nuevo_reporte" style="display:none; position:absolute; left:30%; top:150px; background-color:white;">
								<div align=left  class='editar_reporte' >
									<fieldset class="border_plomo tb_base">
										<legend>
											<span id='label_nuevo_reporte'><b><?php echo __('Nuevo Reporte') ?></b></span>
											<span id='label_editar_reporte'><b><?php echo __('Editar Reporte') ?></b></span>
										</legend>
										<table>
											<tbody>
												<tr>
													<td align=right><?php echo __("Nombre") ?>:</td>
													<td>
														<input type="text" name="nombre_reporte" id="nombre_reporte"/>
														<input type="hidden" name="id_reporte_editado" id="id_reporte_editado" value="0"/>
													</td>
												</tr>
												<tr>
													<td align=right><?php echo __("Tipos de Datos") ?>:</td>
													<td><span id="tipos_datos_nuevo_reporte"></span></td>
												</tr>
												<tr>
													<td align=right><?php echo __("Agrupar por") ?>:</td>
													<td><span id="agrupadores_nuevo_reporte"></span></td>
												</tr>
												<tr>
													<td  align=right><?php echo __("Periodo") ?>:</td>
													<td><span id="periodo_nuevo_reporte"></span></td>
												</tr>
												<tr>
													<td  align=right><?php echo __("Según") ?>:</td>
													<td><span id="segun_nuevo_reporte"></span></td>
												</tr>
												<tr id = 'reporte_envio'>
													<td align=right><?php echo __('Enviar cada') ?>:</td>
													<td>
														<span id='reporte_envio_selector' style="<?php echo $fecha_corta == 'selector' || !$fecha_corta ? '' : 'display:none;' ?>" ><i><?php echo __("Debe seleccionar un periodo de reporte") ?>.</i></span>
														<span id='reporte_envio_semana' style="<?php echo $fecha_corta == 'semanal' ? '' : 'display:none;' ?>">
															<select name='reporte_envio_semana' id='select_reporte_envio_semana'>
																<option value='0'><?php echo __('No enviar') ?></option>
																<option value='1'><?php echo __('Lunes') ?></option>
																<option value='2'><?php echo __('Martes') ?></option>
																<option value='3'><?php echo __('Miércoles') ?></option>
																<option value='4'><?php echo __('Jueves') ?></option>
																<option value='5'><?php echo __('Viernes') ?></option>
																<option value='6'><?php echo __('Sábado') ?></option>
																<option value='7'><?php echo __('Domingo') ?></option>
															</select>
														</span>
														<span id='reporte_envio_mes' style="<?php echo $fecha_corta == 'mensual' || $fecha_corta == 'anual' ? '' : 'display:none;' ?>">
															<select name='reporte_envio_mes' id='select_reporte_envio_mes'>
																<option value='0'><?php echo __('No enviar') ?></option>
	<?php
	for ($i = 1; $i <= 30; $i++) {
		echo "<option value='" . $i . "'>";
		echo __('día') . " " . str_pad($i, 2, '0', STR_PAD_LEFT);
		echo " " . __('del mes') . "</option>";
	}
	?>
															</select>
														</span>
														<br>
													</td>
												</tr>
												<tr>
													<td  align=center colspan=2>
														<input type="button" class="btn" value="<?php echo __('Guardar') ?>" onclick="GenerarReporte();" />
														<input type="button" class="btn" value="<?php echo __('Cancelar') ?>" onclick="$('div_nuevo_reporte').hide();$('nombre_reporte').value='';">
													</td>
												</tr>
											</tbody>
										</table>
									</fieldset>
								</div>
							</div>
						</div>


					</fieldset>


					<!-- SELECTOR DE FILTROS -->
					<fieldset width="100%" class="border_plomo tb_base" align="center">
						<legend id="fullfiltrostoggle" style="cursor:pointer">
							<span id="filtros_img"><img src= "<?php echo Conf::ImgDir() ?><?php echo $filtros_check ? '/menos.gif' : '/mas.gif' ?>" border="0" ></span>
																<?php echo __('Filtros') ?>
						</legend>
						<input type="checkbox" name="filtros_check" id="filtros_check" value="1" <?php echo $filtros_check ? 'checked' : '' ?> style="display:none;" />
						<center>
							<table id="mini_filtros"   style=" width:95%; <?php echo $filtros_check ? 'display:none' : '' ?> " cellpadding="0" cellspacing="3" >
								<tr valign=top>
									<td style="width:470px;"  rowspan="6">
										<div id="filtrosimple">
											<div id="profesional" style="float:left;display:inline-block;"><b><?php echo __('Profesional') ?>:</b><br/>
	<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]", $usuarios, "", "Todos", "200"); ?>	 
											</div>
											<div id="cliente" style="float:right;padding-right:10px;display:inline-block;">

												<b><?php echo __('Cliente') ?>:</b><br/>
	<?php echo Html::SelectQuery($sesion, "SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientes[]", $clientes, "", "Todos", "200"); ?>
											</div></div>


										<!-- SELECTOR FILTROS EXPANDIDO -->
	<?php
	$largo_select = 6;

	if (Conf::GetConf($sesion, 'ReportesAvanzados_FiltrosExtra')) {
		$filtros_extra = true;
		$largo_select = 11;
	}
	?>

										<div id="full_filtros" style="<?php echo $filtros_check ? '' : 'display:none;' ?> ">



											<table >
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_clientes" id="check_clientes" value="1" onchange="$$('.cliente_full').invoke('toggle')" <?php echo $check_clientes ? 'checked' : '' ?> />
														<label for="check_clientes">
															<b><?php echo __('Clientes') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'cliente_full' style='width:200px;<?php echo $check_clientes ? "display:none;" : "" ?>'>
															<label for="check_clientes" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'cliente_full' style="<?php echo $check_clientes ? "" : "display:none;" ?>">
										<?php echo Html::SelectQuery($sesion, "SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientesF[]", $clientesF, "class=\"selectMultiple\" multiple size=" . $largo_select . " ", "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_profesionales" id="check_profesionales" value="1" onchange="$$('.prof_full').invoke('toggle')" <?php echo $check_profesionales ? 'checked' : '' ?> />
														<label for="check_profesionales">
															<b><?php echo __('Profesionales') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'prof_full' style='width:200px;<?php echo $check_profesionales ? "display:none;" : "" ?>'>
															<label for="check_profesionales" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'prof_full' style="<?php echo $check_profesionales ? "" : "display:none;" ?>">
	<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuariosF[]", $usuariosF, "class=\"selectMultiple\" multiple size=" . $largo_select . " ", "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_encargados" id="check_encargados" value="1" onchange="$$('.encargados_full').invoke('toggle')" <?php echo $check_encargados ? 'checked' : '' ?> />
														<label for="check_encargados">
															<b><?php echo __('Encargado Comercial') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'encargados_full' style='width:200px;<?php echo $check_encargados ? "display:none;" : "" ?>'>
															<label for="check_encargados" style="cursor:pointer;" ><hr></label>
														</div>
														<div class = 'encargados_full' style="<?php echo $check_encargados ? "" : "display:none;" ?>" >
	<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "encargados[]", $encargados, "class=\"selectMultiple\" multiple size=" . $largo_select . " ", "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_area_prof" id="check_area_prof" value="1" onchange="$$('.area_prof_full').invoke('toggle')" <?php echo $check_area_prof ? 'checked' : '' ?> />
														<label for="check_area_prof">
															<b><?php echo __('Área Profesional') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'area_prof_full' style='width:200px;<?php echo $check_area_prof ? "display:none;" : "" ?>'>
															<label for="check_area_prof" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'area_prof_full' style="<?php echo $check_area_prof ? "" : "display:none;" ?>">
	<?php echo Html::SelectQuery($sesion, "SELECT id, glosa FROM prm_area_usuario ORDER BY glosa", "areas[]", $areas, 'class="selectMultiple" multiple="multiple" size="4" ', "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_cat_prof" id="check_cat_prof" value="1" onchange="$$('.cat_prof_full').invoke('toggle')" <?php echo $check_cat_prof ? 'checked' : '' ?> />
														<label for="check_cat_prof">
															<b><?php echo __('Categoría Profesional') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'cat_prof_full' style='width:200px;<?php echo $check_cat_prof ? "display:none;" : "" ?>'>
															<label for="check_cat_prof" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'cat_prof_full' style="<?php echo $check_cat_prof ? "" : "display:none;" ?>">
	<?php echo Html::SelectQuery($sesion, "SELECT id_categoria_usuario, glosa_categoria FROM prm_categoria_usuario ORDER BY glosa_categoria", "categorias[]", $categorias, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_area_asunto" id="check_area_asunto" value="1" onchange="$$('.area_asunto_full').invoke('toggle')" <?php echo $check_area_asunto ? 'checked' : '' ?> />
														<label for="check_area_asunto">
															<b><?php echo __('Área de Asunto') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'area_asunto_full' style='width:200px;<?php echo $check_area_asunto ? "display:none;" : "" ?>'>
															<label for="check_area_asunto" style="cursor:pointer"><hr></label>
														</div>
														<div class = 'area_asunto_full' style="<?php echo $check_area_asunto ? "" : "display:none;" ?>" >
	<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_area_proyecto", "areas_asunto[]", $areas_asunto, "class=\"selectMultiple\" multiple size=5 ", "", "200"); ?>
														</div>
													</td>
												</tr>
	<?php if ($filtros_extra) { ?>
													<tr valign=top>
														<td align=right>
															<input type="checkbox" name="check_tipo_asunto" id="check_tipo_asunto" value="1" onchange="$$('.tipo_asunto_full').invoke('toggle')" <?php echo $check_tipo_asunto ? 'checked' : '' ?> />
															<label for="check_tipo_asunto">
																<b><?php echo __('Tipo de Asunto') ?>:&nbsp;&nbsp;</b>
															</label>
														</td>
														<td align=left>
															<div class = 'tipo_asunto_full' style='width:200px;<?php echo $check_tipo_asunto ? "display:none;" : "" ?>'>
																<label for="check_tipo_asunto" style="cursor:pointer;" ><hr></label>
															</div>
															<div class = 'tipo_asunto_full' style="<?php echo $check_tipo_asunto ? "" : "display:none;" ?>" >
		<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_proyecto", "tipos_asunto[]", $tipos_asunto, "class=\"selectMultiple\" multiple size=5 ", "", "200"); ?>
															</div>
														</td>
													</tr>


	<?php } ?>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_estado_cobro" id="check_estado_cobro" value="1" onchange="$$('.estado_cobro_full').invoke('toggle')" <?php echo $check_estado_cobro ? 'checked' : '' ?> />
														<label for="check_estado_cobro">
															<b><?php echo __('Estado de Cobro') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'estado_cobro_full' style='width:200px;<?php echo $check_estado_cobro ? "display:none;" : "" ?>'>
															<label for="check_estado_cobro" style="cursor:pointer;" ><hr></label>
														</div>
														<div class = 'estado_cobro_full' style="<?php echo $check_estado_cobro ? "" : "display:none;" ?>" >
															<select name='estado_cobro[]' id='estado_cobro[]' class="SelectMultiple" multiple size=8 style="width:200px" />
	<?php foreach ($estados_cobro as $ec) {
		?>
																<option value="<?php echo $ec ?>" <?php if ($estado_cobro) if (in_array($ec, $estado_cobro)) echo "selected"; ?> ><?php echo __($ec) ?></option>
	<?php } ?>
															</select>
														</div>
													</td>
												</tr>
												<tr valign=top>
													<td align=right>
														<input type="checkbox" name="check_moneda_contrato" id="check_moneda_contrato" value="1" onchange="$$('.moneda_contrato_full').invoke('toggle')" <?php echo $check_moneda_contrato ? 'checked' : '' ?> />
														<label for="check_moneda_contrato">
															<b><?php echo __('Moneda del Contrato') ?>:&nbsp;&nbsp;</b>
														</label>
													</td>
													<td align=left>
														<div class = 'moneda_contrato_full' style='width:200px;<?php echo $check_moneda_contrato ? "display:none;" : "" ?>'>
															<label for="check_moneda_contrato" style="cursor:pointer;" ><hr></label>
														</div>
														<div class = 'moneda_contrato_full' style="<?php echo $check_moneda_contrato ? "" : "display:none;" ?>" >
	<?php echo Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda", "moneda_contrato[]", $moneda_contrato, "class=\"selectMultiple\" multiple size=5 ", "", "200"); ?>
														</div>
													</td>
												</tr>
											</table>

										</div>

									</td>
									<td align=center colspan=2>
										<b><?php echo __('Periodo') ?>:</b>
									</td>
									<td align=center colspan=2 >
										<b><?php echo __('Según') ?>:</b>
									</td>
								</tr>
								<td align=right>
									<input type="radio" name="fecha_corta" id="fecha_corta_semana" value="semanal" <?php if ($fecha_corta == 'semanal') echo 'checked="checked"'; ?> onclick ="SeleccionarSemana()" />
								</td>
								<td align=left>
									<label for="fecha_corta_semana"><?php echo __("Semana pasada") ?></label>
								</td>

	<?php
	$explica_periodo_trabajo = 'Incluye todo Trabajo con fecha en el Periodo';
	$explica_periodo_cobro = 'Sólo considera Trabajos en Cobros con fecha de corte en el Periodo';
	$explica_periodo_emision = 'Sólo considera Trabajos en Cobros con fecha de emisión en el Periodo';
	?>

								<td align=right>
									<span title="<?php echo __($explica_periodo_trabajo) ?>">
										<input type="radio" name="campo_fecha" id="campo_fecha_trabajo" value="trabajo"
	<?php if ($campo_fecha == 'trabajo' || $campo_fecha == '') echo 'checked="checked"'; ?>
											   onclick ="SincronizarCampoFecha()" />
									</span>
								</td>
								<td align=left>
									<label for="campo_fecha_trabajo"  title="<?php echo __($explica_periodo_trabajo) ?>"><?php echo __("Trabajo") ?></label>
								</td>
								</tr>

								<tr>
									<td align=right>
										<input type="radio" name="fecha_corta" id="fecha_corta_mes" value="mensual" <?php if ($fecha_corta == 'mensual') echo 'checked="checked"'; ?> onclick ="SeleccionarMes()" />
									</td>
									<td align=left>
										<label for="fecha_corta_mes"><?php echo __("Mes pasado") ?></label>
									</td>



									<td align=right>
										<span title="<?php echo __($explica_periodo_cobro) ?>"><input type="radio" name="campo_fecha" id="campo_fecha_cobro" value="cobro"
								<?php if ($campo_fecha == 'cobro')
									echo 'checked="checked"';
								?>
																									 onclick ="SincronizarCampoFecha()" />
										</span>
									</td>
									<td align=left>
										<label for="campo_fecha_cobro" title="<?php echo __($explica_periodo_cobro) ?>"><?php echo __("Corte") ?></label>
									</td>
								</tr>
								<tr>
									<td align=right>
										<input type="radio" name="fecha_corta" id="fecha_corta_anual" value="anual" <?php if ($fecha_corta == 'anual') echo 'checked="checked"' ?>  onclick ="SeleccionarAnual()" />
									</td>
									<td align=left>
										<label for="fecha_corta_anual"><?php echo __("Año en curso") ?></label>
									</td>
									<td align=right>
										<span title="<?php echo __($explica_periodo_emision) ?>"><input type="radio" name="campo_fecha" id="campo_fecha_emision" value="emision"
	<?php if ($campo_fecha == 'emision')
		echo 'checked="checked"';
	?>
																									   onclick ="SincronizarCampoFecha()" />
										</span>
									</td>
									<td align=left>
										<label for="campo_fecha_emision" title="<?php echo __($explica_periodo_emision) ?>"><?php echo __("Emisión") ?></label>
									</td>
								</tr>
								<tr>
									<td align=right>
										<input type="radio" name="fecha_corta" id="fecha_corta_selector" value="selector" onclick ="SeleccionarSelector()" <?php if ($fecha_corta == 'selector' || !$fecha_corta) echo 'checked="checked"'; ?> />
									</td>
									<td align=left colspan=3>
										<span onclick="jQuery('#fecha_corta_selector').click()">
											<select name="fecha_mes" id="fecha_mes" style='width:90px'>
												<option value='1' <?php echo $fecha_mes == 1 ? 'selected' : '' ?>><?php echo __('Enero') ?></option>
												<option value='2' <?php echo $fecha_mes == 2 ? 'selected' : '' ?>><?php echo __('Febrero') ?></option>
												<option value='3' <?php echo $fecha_mes == 3 ? 'selected' : '' ?>><?php echo __('Marzo') ?></option>
												<option value='4' <?php echo $fecha_mes == 4 ? 'selected' : '' ?>><?php echo __('Abril') ?></option>
												<option value='5' <?php echo $fecha_mes == 5 ? 'selected' : '' ?>><?php echo __('Mayo') ?></option>
												<option value='6' <?php echo $fecha_mes == 6 ? 'selected' : '' ?>><?php echo __('Junio') ?></option>
												<option value='7' <?php echo $fecha_mes == 7 ? 'selected' : '' ?>><?php echo __('Julio') ?></option>
												<option value='8' <?php echo $fecha_mes == 8 ? 'selected' : '' ?>><?php echo __('Agosto') ?></option>
												<option value='9' <?php echo $fecha_mes == 9 ? 'selected' : '' ?>><?php echo __('Septiembre') ?></option>
												<option value='10' <?php echo $fecha_mes == 10 ? 'selected' : '' ?>><?php echo __('Octubre') ?></option>
												<option value='11' <?php echo $fecha_mes == 11 ? 'selected' : '' ?>><?php echo __('Noviembre') ?></option>
												<option value='12' <?php echo $fecha_mes == 12 ? 'selected' : '' ?>><?php echo __('Diciembre') ?></option>
											</select>
											<select name="fecha_anio" id="fecha_anio" style='width:55px'>
	<?php for ($i = (date('Y') - 5); $i < (date('Y') + 5); $i++) { ?>
													<option value='<?php echo $i ?>' <?php echo $fecha_anio == $i ? 'selected' : '' ?>><?php echo $i ?></option>
	<?php } ?>
											</select></span>
									</td>
								</tr>
								<tr>
									<!-- PERIODOS -->
									<td align=right>
										<input type="radio" name="fecha_corta" id="fecha_periodo" value="selector" onclick ="SeleccionarSelector()" <?php if ($fecha_corta == 'selector' || !$fecha_corta) echo 'checked="checked"'; ?> />
									</td>
									<td align=left colspan=3>
										<div id=periodo_rango>

											<input type="text" name="fecha_ini" class="fechadiff" value="<?php echo $fecha_ini ? $fecha_ini : date("d-m-Y", strtotime("$hoy - 1 month")) ?>" id="fecha_ini" size="11" maxlength="10" />
	<?php echo __('al') ?>
											<input type="text" name="fecha_fin" class="fechadiff"  value="<?php echo $fecha_fin ? $fecha_fin : date("d-m-Y", strtotime("$hoy")) ?>" id="fecha_fin" size="11" maxlength="10" />

										</div>
									</td>
								</tr>

							</table>
						</center>


					</fieldset>


					<!-- SELECTOR TIPO DE DATO -->
					<br>
					<fieldset align="center" width="90%" class="border_plomo tb_base">
						<legend onClick="MostrarOculto('tipo_dato')" style="cursor:pointer">
							<span id="tipo_dato_img"><img src= "<?php echo Conf::ImgDir() ?>/mas.gif" border="0" ></span>
	<?php echo __('Tipo de Dato') ?>
						</legend>
						<input type="checkbox" name="tipo_dato_check" id="tipo_dato_check" value="1" <?php echo $tipo_dato_check ? 'checked' : '' ?> style="display:none;" />
						<center>
							<table id="mini_tipo_dato" >
								<tr>
									<td id = 'td_dato' class='<?php echo $comparar ? 'borde_rojo' : 'borde_blanco' ?>' >
										<select name='tipo_dato' id='tipo_dato' style='width:180px; ' onchange="TipoDato(this.value)" >
											<?php
											foreach ($tipos_de_dato as $tipo) {
												echo "<option value='" . $tipo . "'";
												if ($tipo_dato == $tipo)
													echo "selected";
												echo "> " . __($tipo) . "</option>";
											}
											?>
										</select>
									</td>
									<td>
										<span id="vs" style='<?php echo $comparar ? '' : 'display: none;' ?>'>
	<?php echo __(" Vs. ") ?>
										</span>
									</td>
									<td id = 'td_dato_comparado' class='borde_azul' style='<?php echo $comparar ? '' : 'display: none;' ?>' >

										<select name='tipo_dato_comparado' id='tipo_dato_comparado' style='width:180px; ' >
	<?php
	foreach ($tipos_de_dato as $tipo) {
		echo "<option value='" . $tipo . "'";
		if ($tipo_dato_comparado == $tipo)
			echo "selected";
		echo "> " . __($tipo) . "</option>";
	}
	?>
										</select>
									</td>
								</tr>
							</table>
						</center>
						<!-- SELECTOR TIPO DE DATO EXPANDIDO-->
						<table id="full_tipo_dato" style="border: 0px solid black; width:730px; display: none;padding:10px;margin:auto;" border="0" cellpadding="0" cellspacing="0">

							<tr>
											<?php echo celda('horas_trabajadas') ?>
											<?php echo borde_abajo(2) ?>
	<?php echo celda('horas_cobrables') ?>
	<?php echo borde_abajo(2) ?>
	<?php echo celda('horas_visibles') ?>
											<?php echo borde_abajo(2) ?>
											<?php echo celda('horas_cobradas') ?>
	<?php echo borde_abajo(2) ?>
	<?php echo celda('horas_pagadas') ?>
							</tr>
							<tr>
											<?php echo borde_derecha() ?>
											<?php echo nada(1) ?>
											<?php echo borde_derecha() ?>
											<?php echo nada(1) ?>
											<?php echo borde_derecha() ?>
											<?php echo nada(1) ?>
											<?php echo borde_derecha() ?>
											<?php echo nada(1) ?>
							</tr>
							<tr>
	<?php echo nada(9) ?>
							</tr>
							<tr>
	<?php echo nada() ?>
	<?php echo borde_abajo() ?>
	<?php echo celda("horas_no_cobrables") ?>
	<?php echo borde_abajo() ?>
	<?php echo celda("horas_castigadas") ?>
								<?php echo borde_abajo() ?>
								<?php echo celda("horas_por_cobrar") ?>
								<?php echo borde_abajo() ?>
								<?php echo celda("horas_por_pagar") ?>
							</tr>
							<tr>
								<?php echo nada(6) ?>
								<?php echo borde_derecha() ?>
								<?php echo nada(3) ?>
							</tr>
							<tr>
								<?php echo nada(12) ?>
							</tr>
							<tr>
								<?php echo nada(8) ?>
								<?php echo borde_abajo() ?>
								<?php echo celda("horas_incobrables") ?>
								<?php echo nada(3) ?>
							</tr>
							<tr>
	<?php echo nada(1) ?>
							</tr>
							<tr>
	<?php echo nada(13) ?>
							</tr>
							<tr>
								<?php echo celda_disabled('valor_trabajado') ?>
								<?php echo borde_abajo(2) ?>
								<?php echo celda_disabled('valor_cobrable') ?>
								<?php echo borde_abajo(2) ?>
								<?php echo celda_disabled('valor_visible') ?>
								<?php echo borde_abajo(2) ?>
								<?php echo celda('valor_cobrado') ?>
								<?php echo borde_abajo(2) ?>
	<?php echo celda('valor_pagado') ?>
							</tr>
							<tr>
								<?php echo borde_derecha() ?>
								<?php echo nada() ?>
	<?php echo borde_derecha() ?>
								<?php echo nada() ?>
								<?php echo borde_derecha() ?>
	<?php echo nada() ?>
								<?php echo borde_derecha() ?>
								<?php echo nada() ?>
							</tr>
							<tr>
								<?php echo nada(9) ?>
							</tr>
							<tr>
								<?php echo celda('valor_trabajado_estandar') ?>
	<?php echo borde_abajo() ?>
								<?php echo celda_disabled("valor_no_cobrable") ?>
								<?php echo borde_abajo() ?>
	<?php echo celda_disabled("valor_castigado") ?>
								<?php echo borde_abajo() ?>
								<?php echo celda("valor_por_cobrar") ?>
								<?php echo borde_abajo() ?>
								<?php echo celda("valor_por_pagar") ?>
							</tr>
							<tr>
								<?php echo nada(6) ?>
								<?php echo borde_derecha() ?>
								<?php echo nada(3) ?>
							</tr>
							<tr>
								<?php echo nada(12) ?>
							</tr>
							<tr>
								<?php echo nada(8) ?>
								<?php echo borde_abajo() ?>
								<?php echo celda("valor_incobrable") ?>
								<?php echo nada(3) ?>
							</tr>
							<tr>
	<?php echo nada(1) ?>
							</tr>
							<tr>
	<?php echo nada(13) ?>
							</tr>
							<tr>
								<?php echo titulo_proporcionalidad() ?>
								<?php echo nada(2) ?>
								<?php echo moneda() ?>
								<?php echo nada(2) ?>
								<?php echo celda("valor_estandar") ?>
								<?php echo nada(2) ?>
								<?php echo celda("diferencia_valor_estandar") ?>
								<?php echo nada(2) ?>
	<?php echo celda("valor_hora"); ?>
							</tr>
							<tr>
								<?php echo nada(1) ?>
							</tr>
							<tr>
								<?php echo nada(1) ?>
							</tr>
							<tr>
								<?php echo nada(12) ?>
							</tr>
							<tr>
								<?php echo select_proporcionalidad() ?>
								<?php echo nada(2) ?>
	<?php echo select_moneda() ?>
								<?php echo nada(5) ?>

	<?php echo celda("rentabilidad_base") ?>
								<?php echo nada(2) ?>
								<?php echo celda("rentabilidad") ?>
							</tr>
							<tr>
								<?php echo nada(1) ?>
							</tr>
							<tr>
								<?php echo nada(1) ?>
							</tr>
							<tr>
								<?php echo nada(12) ?>
							</tr>
							<tr>	<?php echo tinta() ?>
	<?php echo nada(8) ?>
								<?php echo celda("costo") ?>
								<?php echo nada(2) ?>
	<?php echo celda("costo_hh") ?>

							</tr>
							<tr>
								<?php echo nada(9) ?>

							</tr>

						</table>
					</fieldset>
					<!-- SELECTOR DE VISTA -->
					<br>
					<fieldset align="center" width="90%" class="border_plomo tb_base">
						<legend><?php echo __('Vista') ?></legend>
						<table style="border: 0px solid black; width:730px" cellpadding="0" cellspacing="4">
						<!--<tr>
							<td style="width: 330px; font-size: 11px;" colspan=6>
								<?php echo __('Agrupar por') ?>:&nbsp;
								<img src="<?php echo Conf::ImgDir() ?>/mas.gif" onclick="Agrupadores(1)"; id='mas_agrupadores'
								 style='<?php echo $numero_agrupadores == 6 ? 'display:none;' : '' ?> cursor:pointer;' />
								<img src="<?php echo Conf::ImgDir() ?>/menos.gif" onclick="Agrupadores(-1)"; id='menos_agrupadores'
								 style=' <?php echo $numero_agrupadores == 1 ? 'display:none;' : '' ?> cursor:pointer;' />
								<select name="vista" id="vista" onchange="RevisarTabla();">
								<?php
								foreach ($vistas as $key => $v) {
									$s = implode('-', $v);
									echo '<option value="' . $s . '" ';
									if ($vista == $s)
										echo 'selected';
									echo ">" . implode(' - ', $vistas_lang[$key]);
									echo "</option>\n";
								}
								?>
								</select>
								<input type=hidden name=numero_agrupadores id=numero_agrupadores value=<?php echo $numero_agrupadores ?> />
								<input type=hidden name=vista id=vista value='' />
							</td>
						</tr>-->
							<tr>
								<td colspan=6 align=left>
									<div style="float:left">
										<img src="<?php echo Conf::ImgDir() ?>/menos.gif" onclick="Agrupadores(-1)"; 
											 style='cursor:pointer;' />
										<img src="<?php echo Conf::ImgDir() ?>/mas.gif" onclick="Agrupadores(1)"; 
											 style='cursor:pointer;' />
	<?php echo __('Agrupar por') ?>:&nbsp;
										<input type=hidden name=numero_agrupadores id=numero_agrupadores value=<?php echo $numero_agrupadores ?> />
										<input type=hidden name=vista id="vista" value='' />
									</div>
									<div style="float:left" id="agrupadores">
	<?php
	$ya_elegidos = array();
	for ($i = 0; $i < 6; $i++) {
		echo '<span id="span_agrupador_' . $i . '"';
		if ($i >= $numero_agrupadores)
			echo ' style="display:none;" ';
		echo '>';
		echo '<select name="agrupador[' . $i . ']" id="agrupador_' . $i . '" style="font-size:10px; margin-top:2px; margin-bottom:2px; margin-left:6px; width:110px;" onchange="CambiarAgrupador(' . $i . ');"  ';
		echo '/>';
		$elegido = false;
		$valor_previo = '';
		foreach ($agrupadores as $key => $v) {
			if (!in_array($v, $ya_elegidos)) {
				echo '<option value="' . $v . '" ';
				if (isset($agrupador[$i])) {
					if ($agrupador[$i] == $v) {
						echo 'selected';
						$valor_previo = '<select style="display:none;" id="agrupador_valor_previo_' . $i . '"><option value = "' . $v . '">' . __($v) . '</option></select>';
						$ya_elegidos[] = $v;
					}
				} else if (!$elegido) {
					echo 'selected';
					$valor_previo = '<select style="display:none;" id="agrupador_valor_previo_' . $i . '"><option value = "' . $v . '">' . __($v) . '</option></select>';
					$elegido = true;
					$ya_elegidos[] = $v;
				}
				echo ">" . __($v);
				echo "</option>";
			}
		}
		echo '</select></span>';
		echo $valor_previo;
	}
	?>
									</div>
								</td>
							</tr>
							<tr>
								<td align=center colspan=5>
									<br/>
									<a href="javascript:void(0)" class="btn botonizame" id="runreporte" name="runreporte" icon="code"/>Planilla</a>
									<a href="javascript:void(0)" class="btn botonizame" id="excel" name="excel" icon="xls"  title="Genera la Planilla como un Documento Excel." onclick="Generar(jQuery('#formulario').get(0),'excel');"/><?php echo __('Excel') ?></a>

									<a href="javascript:void(0)" class="btn botonizame" id="dispersion" name="dispersion" icon="icon-chart"   title="Genera la Planilla como un Documento Excel." onclick="Generar(jQuery('#formulario').get(0),'dispersion');"/><?php echo __('Dispersión') ?></a>



									<a href="javascript:void(0)" class="btn botonizame" id="tabla" name="tabla" icon="icon-table" title="Genera un Documento Excel con una tabla cruzada." onclick="Generar(jQuery('#formulario').get(0),'tabla');"/><?php echo __('Tabla') ?></a>

									<a href="javascript:void(0)" class="btn botonizame" id="barras" name="barras" icon="icon-bar" title="Despliega un Gráfico de Barras, usando el primer Agrupador." onclick="Generar(jQuery('#formulario').get(0),'barra');"/><?php echo __('Barras') ?></a>
									<a href="javascript:void(0)" class="btn botonizame" id="circular" name="circular"  icon="pie-chart" title="Despliega un Gráfico de Torta, usando el primer Agrupador." onclick="Generar(jQuery('#formulario').get(0),'circular');"/><?php echo __('Gráfico Torta') ?></a>

								</td>
								<td style="width: 100px; font-size: 11px;">
									<label for="comparar"><?php echo __('Comparar') ?>:</label> <input type="checkbox" name="comparar" id="comparar" value="1" onclick="Comparar()" <?php echo $comparar ? 'checked' : '' ?> title='Comparar' /> </td>
								</td>
							</tr>
							<tr>
								<td colspan = 6>
									<table cellpadding="2" cellspacing="5">
										<tr>
											<td>
												<input type="checkbox" name="orden_barras_max2min" id="orden_barras_max2min" value="1"
										<?php
										if (isset($orden_barras_max2min) || !isset($tipo_dato))
											echo 'checked="checked"';
										?>
													   title=<?php echo __('Ordenar Gráfico de Barras de Mayor a Menor') ?> onclick="MostrarLimite(this.checked)"/>
												<label for="orden_barras_max2min"><?php echo __("Gráficar de Mayor a Menor") ?></label>
											</td>
											<td>
												<span id = "limite_check" <?php if (!isset($orden_barras_max2min) && isset($tipo_dato)) echo 'style= "display: none; "'; ?>>
													<input type="checkbox" name="limitar" id="limite_checkbox" value="1" <?php echo $limitar ? 'checked="checked"' : '' ?> />
													<label for="limite_checkbox"><?php echo __("y mostrar sólo") ?></label> &nbsp;
													<input type="text" name="limite" value="<?php echo $limite ? $limite : '5' ?>" id="limite" size="2" maxlength="2" /> &nbsp;
	<?php echo __("resultados superiores") ?>
													<span>
														</td>
														<td>
															<span id = "agupador_check">
																<input type="checkbox" name="agrupar" id="agrupador_checkbox" value="1" <?php echo $agrupar ? 'checked' : '' ?> />
																<label for="agrupador_checkbox"><?php echo __("agrupando el resto") ?></label>. &nbsp;
																<span>
																	</td>
																	</tr>
																	</table>
																	</td>
																	</tr>
																	</table>
																	</fieldset>
																	</td></tr></table>



																	<!-- RESULTADO -->
	<?php
}

$alto = 800;
switch ($opc) {
	case 'print': {
			$url_iframe = "reporte_avanzado_planilla.php?popup=1";
			break;
		}
	case 'circular': {
			$url_iframe = "reporte_avanzado_grafico.php?tipo_grafico=circular&popup=1";
			$alto = 540;
			if ($orden_barras_max2min)
				$url_iframe .= "&orden=max2min";
			break;
		}
	case 'barra': {
			$url_iframe = "reporte_avanzado_grafico.php?tipo_grafico=barras&popup=1";
			$alto = 540;
			if ($orden_barras_max2min)
				$url_iframe .= "&orden=max2min";
			break;
		}
	case 'dispersion': {
			$url_iframe = "reporte_avanzado_grafico.php?tipo_grafico=dispersion&popup=1";
			$alto = 640;
			if ($orden_barras_max2min)
				$url_iframe .= "&orden=max2min";
			break;
		}
}
$url_iframe .= "&tipo_dato=" . $tipo_dato;
$url_iframe .= "&vista=" . $vista;
$url_iframe .= "&id_moneda=" . $id_moneda;
$url_iframe .= "&prop=" . $proporcionalidad;

if ($limitar)
	$url_iframe .= "&limite=" . $limite;
if ($agrupar)
	$url_iframe .= "&agrupar=1";

if ($filtros_check) {
	if ($check_clientes)
		if (is_array($clientesF))
			$url_iframe .= "&clientes=" . implode(',', $clientesF);
	if ($check_profesionales)
		if (is_array($usuariosF))
			$url_iframe .= "&usuarios=" . implode(',', $usuariosF);

	if ($check_area_asunto)
		if (is_array($areas_asunto))
			$url_iframe .= "&areas_asunto=" . implode(',', $areas_asunto);
	if ($check_tipo_asunto)
		if (is_array($tipos_asunto))
			$url_iframe .= "&tipos_asunto=" . implode(',', $tipos_asunto);
	if ($check_moneda_contrato)
		if (is_array($moneda_contrato))
			$url_iframe .= "&moneda_contrato=" . implode(',', $moneda_contrato);

	if ($check_area_prof)
		if (is_array($areas))
			$url_iframe .= "&areas_usuario=" . implode(',', $areas);
	if ($check_cat_prof)
		if (is_array($categorias))
			$url_iframe .= "&categorias_usuario=" . implode(',', $categorias);

	if ($check_encargados)
		if (is_array($encargados))
			$url_iframe .= "&en_com=" . implode(',', $encargados);

	if ($check_estado_cobro)
		if (is_array($estado_cobro))
			$url_iframe .= "&es_cob=" . implode(',', $estado_cobro);
}
else {
	if (is_array($clientes))
		$url_iframe .= "&clientes=" . implode(',', $clientes);
	if (is_array($usuarios))
		$url_iframe .= "&usuarios=" . implode(',', $usuarios);
}

$url_iframe .= "&fecha_ini=" . $fecha_ini;
$url_iframe .= "&fecha_fin=" . $fecha_fin;

$url_iframe .= "&campo_fecha=" . $campo_fecha;

if ($comparar)
	$url_iframe .= "&tipo_dato_comparado=" . $tipo_dato_comparado;
?>
																</form>

																<?php
																if ($opc && $opc != 'nuevo_reporte' && $opc != 'eliminar_reporte') {

																	echo '<div class="resizable" id="iframereporte">
<div class="divloading">&nbsp;</div>
		 <iframe  class="resizableframe" onload="iframelista();" name="planilla" id="planilla" src="' . $url_iframe . '" frameborder="0" style="display:none;width:730px;height:' . $alto . ';px;"></iframe>
</div>';
																} else {
																	echo '<div class="resizable"  id="iframereporte">

</div>';
																}
																?>

																<script> 
																		RevisarMoneda(); RevisarCircular(); RevisarTabla();
																		jQuery(document).ready(function(){
																			 
																			if(jQuery('#comparar').is(':checked')) {
																				   jQuery('#tabla, #dispersion').css('display','inline-block').show();
																			   } else {
																				   jQuery('#tabla, #dispersion').css('display','none').hide();
																			   }
																			
																			jQuery('#formulario').on('click','#mis_reportes',function() {
																				CargarReporte();
																				 
																			});
																			
																			jQuery('#fullfiltrostoggle').click(function() {
																				jQuery('#filtrosimple').toggle();
																				jQuery('#full_filtros').toggle();
																			});
																		
	
																			jQuery('#comparar').on('click',function() {
																				if(jQuery('#comparar').is(':checked')) {
																				   jQuery('#tabla, #dispersion').css('display','inline-block').show();
																			   } else {
																				   jQuery('#tabla, #dispersion').css('display','none').hide();
																			   }
																			});
	
																			jQuery('#runreporte').on('click',function(){
																				if(jQuery('#comparar').is(':checked')) {
																					jQuery('#tipo_dato_comparado').removeAttr('disabled');

																				} else {
																					jQuery('#tipo_dato_comparado').attr('disabled','disabled');
																				}
																				jQuery('#vista').val("");
																				var vista=[];
																				jQuery('#agrupadores select:visible').each(function(i) {
																					vista[i]=jQuery(this).val();
																				});
																				jQuery('#iframereporte').html('<div class="divloading">&nbsp;</div>');
		
																				jQuery('#vista').val(vista.join('-')) ;
																				jQuery.ajax({
																					url: "reporte_avanzado_planilla.php?popup=1&vista="+jQuery('#vista').val(),
																					data: jQuery('#formulario').serialize(),
																					type: "POST"
																				}).done(function(data) {
																					jQuery('#iframereporte').html(data);
																				});
																			});
																			CargarReporte();
																		});								

																</script> 
<?php
$pagina->PrintBottom($popup);



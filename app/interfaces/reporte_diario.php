<?php
require_once dirname(__FILE__).'/../conf.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);
$Form = new Form($sesion);

/*
 * Debe tener habilitado la Conf ReportesAvanzados para acceder a este reporte.
 */
if (!Conf::GetConf($sesion, 'ReportesAvanzados')) {
	$_SESSION['flash_msg'] = 'No tienes permisos para acceder a ' . __('Reporte Diario') . '.';
	$pagina->Redirect(Conf::RootDir() . '/app/interfaces/reportes_especificos.php');
}

	/*REPORTE DIARIO.*/
	$pagina->titulo = __('Resumen actividades profesionales');

	$tipos_de_dato = array();
	$tipos_de_dato[] ='horas_trabajadas';
	$tipos_de_dato[] ='horas_cobrables';
	$tipos_de_dato[] ='horas_no_cobrables';
	$tipos_de_dato[] ='horas_castigadas';
	$tipos_de_dato[] ='horas_visibles';
	$tipos_de_dato[] ='horas_cobradas';
	$tipos_de_dato[] ='horas_por_cobrar';
	$tipos_de_dato[] ='horas_pagadas';
	$tipos_de_dato[] ='horas_por_pagar';
	$tipos_de_dato[] ='horas_incobrables';
	$tipos_de_dato[] ='valor_cobrado';
	$tipos_de_dato[] ='valor_por_cobrar';
	$tipos_de_dato[] ='valor_pagado';
	$tipos_de_dato[] ='valor_por_pagar';
	$tipos_de_dato[] ='valor_incobrable';
	$tipos_de_dato[] ='rentabilidad';
	$tipos_de_dato[] ='valor_hora';
	$tipos_de_dato[] ='diferencia_valor_estandar';
	$tipos_de_dato[] ='valor_estandar';

	$agrupadores = array(
	'profesional',
	'glosa_cliente',
	'estado',
	'id_cobro',
	'forma_cobro',
	'area_asunto',
	'categoria_usuario',
	'area_usuario',
	'glosa_grupo_cliente',
	'id_usuario_responsable');

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
	$glosa_dato['valor_por_pagar'] = __("Valor Cobrado en Cobros Incobrables");
	$glosa_dato['rentabilidad'] = __("Razón entre el Valor Cobrado, y lo que se habría cobrado usando THHs Estándar");
	$glosa_dato['valor_hora'] = __("Valor Cobrado por cada Hora Cobrada");
	$glosa_dato['diferencia_valor_estandar'] = __("Diferencia entre el Valor Cobrado, y lo que se habría cobrado usando THHs Estándar");
	$glosa_dato['valor_estandar'] = __("Valor que se habría cobrado usando THHs Estándar");

	$glosa_boton['tabla'] = "Genera un Documento Excel con una tabla cruzada.";

	$tipos_moneda = array('valor_cobrado','valor_por_cobrar','valor_pagado','valor_por_pagar','valor_hora','valor_incobrable','diferencia_valor_estandar','valor_estandar');

	$hoy = date("Y-m-d");
	if (!$fecha_anio) {
		$fecha_anio = date('Y');
	}
	if (!$fecha_mes) {
		$fecha_mes = date('m');
	}

	$fecha_ultimo_dia = date('t',mktime(0,0,0,$fecha_mes,5,$fecha_anio));

	if (!isset($numero_agrupadores)) {
		$numero_agrupadores = 1;
	}
	if (!$popup) {
		$pagina->PrintTop($popup);
		if (!$filtros_check) {
			$fecha_m = ''.$fecha_mes;

			$fecha_fin = $fecha_ultimo_dia."-".$fecha_m."-".$fecha_anio;
			$fecha_ini = "01-".$fecha_m."-".$fecha_anio;
		}
?>
<style>

TD.boton_normal { border: solid 2px #e0ffe0; background-color: #e0ffe0; }

TD.boton_presionado { border: solid 2px red; background-color: #e0ffe0; }

TD.boton_comparar { border: solid 2px blue; background-color: #e0ffe0; }

TD.borde_rojo { border: solid 1px red; }

TD.borde_azul { border: solid 1px blue; }

TD.borde_blanco { border: solid 1px white; }

input.btn{ margin:3px;}


.visible{display:'block';}
.invisible{display: none;}

</style>

<script type="text/javascript">

function Generar(form, valor)
{

	form.vista.value = $('agrupador_0').value;
	for(i=1;i<$('numero_agrupadores').value;i++)
	{
		form.vista.value += '-'+$('agrupador_'+i).value;
	}

	form.action = 'planillas/planilla_reporte_diario.php';
	form.submit();
}

function Rangos(obj, form)
{
	var td_show = $('periodo_rango');
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
	}
}

//Sincroniza los selectores de Campo de Fecha Visibles e Invisibles
function SincronizarCampoFecha()
{
	var filtros_check = $('filtros_check');
	var campo_fecha = document.formulario.campo_fecha;
	var campo_fecha_F = document.formulario.campo_fecha_F;

	if(filtros_check.checked)
	{
		for(var i = 0; i < 3; i++)
		{
			if(campo_fecha_F[i].checked)
			{
				campo_fecha[i].checked = true;
			}
		}
	}
	else
	{
		for(var i = 0; i < 3; i++)
		{
			if(campo_fecha[i].checked)
			{
				campo_fecha_F[i].checked = true;
			}
		}
	}
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

	if(
		tipo_de_dato.value in
			{'valor_pagado':'','valor_cobrado':'','valor_por_cobrar':'','valor_por_pagar':'','valor_incobrable':'','valor_hora':'','diferencia_valor_estandar':''}
		)
		Monedas(true);
	else
		Monedas(false);
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

/*Setea el Tipo de Dato, marcando la selección, haciendo visible la moneda y el gráfico circular*/
function TipoDato(valor)
{
	var td_col = document.getElementById(valor);
	var tipo_de_dato = document.getElementById('tipo_dato');
	var comparar = document.getElementById('comparar');
	var tintas = document.getElementsByName('tinta');

	for(var i=0; i< tintas.length; i++)
	{
		if(tintas[i].checked)
			var tinta = tintas[i].value;
	}

		td_col.className = 'boton_presionado';
		<?php
		foreach($tipos_de_dato as $key => $t_d)
		{
			echo " if(valor == '".$t_d."' ){ ";
			echo " tipo_de_dato.selectedIndex = ".$key."; \n";
			echo "} else {td_col= document.getElementById('".$t_d."'); if(td_col.className=='boton_presionado')td_col.className = 'boton_normal'; }\n";
		}
		?>
	RevisarMoneda();
}

</script>
<?php
} // Fin $popup
?>
<form method="post" name="formulario" action="" id="formulario" autocomplete="off">
<input type="hidden" name="opc" id="opc" value="print">
<input type="hidden" name="debug" id="debug" value='<?php echo $debug; ?>'>
<?php
	if (!$popup) {
?>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<!-- SELECTOR DE FILTROS -->
<table width="90%"><tr><td align="center">

<fieldset width="100%" class="border_plomo tb_base" align="center">
<legend onClick="MostrarOculto('filtros')" style="cursor:pointer">
<span id="filtros_img"><img src= "<?=Conf::ImgDir()?><?=$filtros_check? '/menos.gif':'/mas.gif'?>" border="0" ></span>
<?php echo __('Filtros'); ?>
</legend>
<input type="checkbox" name="filtros_check" id="filtros_check" value="1" <?php echo $filtros_check ? 'checked' : ''; ?> style="display:none;" />
<center>
<table id="mini_filtros" style="border: 1px solid white; width:90%; <?php echo $filtros_check ? 'display:none' : ''; ?> " cellpadding="0" cellspacing="3" >
	<tr valign="top">
		<td align="left">
			<b><?php echo __('Profesional'); ?>:</b>
		</td>
		<td align="left">
			<b><?php echo __('Cliente'); ?>:</b>
		</td>
		<td align="left" width='80px'>
			<b><?php echo __('Periodo de'); ?>:</b>

			<br>
		</td>
		<td>
			<?php
				$explica_periodo_trabajo = 'Incluye todo Trabajo con fecha en el Periodo';
				$explica_periodo_cobro = 'Sólo considera Trabajos en Cobros con fecha de corte en el Periodo';
				$explica_periodo_emision = 'Sólo considera Trabajos en Cobros con fecha de emisión en el Periodo';
 			?>
			<span title="<?php echo __($explica_periodo_trabajo); ?>">
			<input type="radio" name="campo_fecha" id="campo_fecha_trabajo" value="trabajo"
																					 <?php if($campo_fecha=='trabajo' || $campo_fecha=='') echo 'checked="checked"'; ?>
																					 onclick ="SincronizarCampoFecha()" />&nbsp;<label for="campo_fecha_trabajo"><?php echo __("Trabajo"); ?></label>
			</span>
			<span title="<?php echo __($explica_periodo_cobro); ?>"><input type="radio" name="campo_fecha" id="campo_fecha_cobro" value="cobro"
																					<?php if($campo_fecha=='cobro' ) echo 'checked="checked"';
																					 ?>
																					 onclick ="SincronizarCampoFecha()" />&nbsp;<label for="campo_fecha_cobro"><?php echo __("Corte"); ?></label>
			</span>
			<span title="<?php echo __($explica_periodo_emision); ?>"><input type="radio" name="campo_fecha" id="campo_fecha_emision" value="emision"
																					<?php if($campo_fecha=='emision' ) echo 'checked="checked"';
																					 ?>
																					 onclick ="SincronizarCampoFecha()" />&nbsp;<label for="campo_fecha_emision"><?php echo __("Emisión"); ?></label>
			</span>
		</td>
	</tr>
	<tr>
		<td align="left">
			<!-- Nuevo Select -->
            <?php echo $Form->select('usuarios[]', $sesion->usuario->ListarActivos('', 'PRO'), $usuarios, array('empty' => 'Todos', 'style' => 'width: 200px')); ?>
		</td>
		</td>
		<td align="left">
			<?php echo Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientes[]",$clientes,"","Todos","200"); ?>
		</td>
		<td align="left" colspan="2" width="40%">
			<div id="periodo" style="display:<?php echo !$rango ? 'inline' : 'none'; ?>;">
				<select name="fecha_mes" style="width:90px">
					<option value='1' <?php echo $fecha_mes == 1 ? 'selected' : '' ?>><?php echo __('Enero'); ?></option>
					<option value='2' <?php echo $fecha_mes == 2 ? 'selected' : '' ?>><?php echo __('Febrero'); ?></option>
					<option value='3' <?php echo $fecha_mes == 3 ? 'selected' : '' ?>><?php echo __('Marzo'); ?></option>
					<option value='4' <?php echo $fecha_mes == 4 ? 'selected' : '' ?>><?php echo __('Abril'); ?></option>
					<option value='5' <?php echo $fecha_mes == 5 ? 'selected' : '' ?>><?php echo __('Mayo'); ?></option>
					<option value='6' <?php echo $fecha_mes == 6 ? 'selected' : '' ?>><?php echo __('Junio'); ?></option>
					<option value='7' <?php echo $fecha_mes == 7 ? 'selected' : '' ?>><?php echo __('Julio'); ?></option>
					<option value='8' <?php echo $fecha_mes == 8 ? 'selected' : '' ?>><?php echo __('Agosto'); ?></option>
					<option value='9' <?php echo $fecha_mes == 9 ? 'selected' : '' ?>><?php echo __('Septiembre'); ?></option>
					<option value='10' <?php echo $fecha_mes == 10 ? 'selected' : '' ?>><?php echo __('Octubre'); ?></option>
					<option value='11' <?php echo $fecha_mes == 11 ? 'selected' : '' ?>><?php echo __('Noviembre'); ?></option>
					<option value='12' <?php echo $fecha_mes == 12 ? 'selected' : '' ?>><?php echo __('Diciembre'); ?></option>
				</select>
				<select name="fecha_anio" style="width:55px">
					<?php for($i=(date('Y')-5); $i < (date('Y')+5); $i++){ ?>
						<option value='<?php echo $i; ?>' <?php echo $fecha_anio == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
					<?php } ?>
				</select>
			</div>
		</td>
	</tr>
</table>
</center>

			<!-- SELECTOR FILTROS EXPANDIDO -->
<table id="full_filtros" style="border: 0px solid black; width:730px; <?php echo $filtros_check ? '' : 'display:none;'; ?> " cellpadding="0" cellspacing="3">
	<tr valign="top">
		<td align="left">
			<b><?php echo __('Profesionales'); ?>:</b></td>
		<td align="left">
			<b><?php echo __('Clientes'); ?>:</b></td>
		<td align="left" width='80px'>
			<b><?php echo __('Periodo de'); ?>:</b>
		</td>
		<td>
			<span title="<?php echo __($explica_periodo_trabajo); ?>">
			<input type="radio" name="campo_fecha_F" value="trabajo" id = "campo_fecha_F"
																					<?php if($campo_fecha=='trabajo' || $campo_fecha=='') echo 'checked="checked"'; ?> onclick ="SincronizarCampoFecha()" />
			<?php echo __("Trabajo"); ?>
			</span>
			<span title="<?php echo __($explica_periodo_cobro); ?>">
			<input type="radio" name="campo_fecha_F" value="cobro" id = "campo_fecha_F"
																					<?php if($campo_fecha=='cobro') echo 'checked="checked"';
																					 ?> onclick ="SincronizarCampoFecha()" />
			<?php echo __("Corte"); ?>
			</span>
			<span title="<?php echo __($explica_periodo_emision); ?>">
			<input type="radio" name="campo_fecha_F" value="emision" id = "campo_fecha_F"
																					<?php if($campo_fecha=='emision') echo 'checked="checked"';
																					 ?> onclick ="SincronizarCampoFecha()" />
			<?php echo __("Emisión"); ?>
			</span>
		</td>
	</tr>

	<?php
		$largo_select = 6;
		if( method_exists('Conf','GetConf') )
		{
			if( Conf::GetConf($sesion,'ReportesAvanzados_FiltrosExtra') )
			{
				$filtros_extra = true;
				$largo_select = 11;
			}
		}
		else if(method_exists('Conf','ReportesAvanzados_FiltrosExtra'))
		{
			if(Conf::ReportesAvanzados_FiltrosExtra())
			{
				$filtros_extra = true;
				$largo_select = 11;
			}
		}
	?>

	<tr valign="top">
		<td rowspan="3" align="left">
		<?php echo Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuariosF[]",$usuariosF,"class=\"selectMultiple\" multiple size=".$largo_select." ","","200"); ?>
		</td>
		<td rowspan="3" align="left">
		<?php echo Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientesF[]",$clientesF,"class=\"selectMultiple\" multiple size=".$largo_select." ","","200"); ?>
	 	</td>
	 	<!-- PERIODOS -->
	 	<td colspan="2" align="center">
			<div id="periodo_rango">
				<?php echo __('Fecha desde'); ?>:&nbsp;
					<input type="text" name="fecha_ini" value="<?php echo $fecha_ini ? $fecha_ini : date("d-m-Y",strtotime("$hoy - 1 month")); ?>" id="fecha_ini" size="11" maxlength="10" />
					<img src="<?php echo Conf::ImgDir(); ?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
				<br />
				<?php echo __('Fecha hasta'); ?>:&nbsp;
					<input type="text" name="fecha_fin" value="<?php echo $fecha_fin ? $fecha_fin : date("d-m-Y",strtotime("$hoy")); ?>" id="fecha_fin" size="11" maxlength="10" />
					<img src="<?php echo Conf::ImgDir(); ?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
			</div>
		</td>
	</tr>
	<!-- TIPO DE ASUNTO Y AREA (CONFIGURABLE) !-->
	<?php
		if ($filtros_extra) {
	?>
	<tr>
		<td align="center">
			<b><?php echo __("Tipo de Asunto"); ?>:</b>

		</td>
		<td align="center">
			<b><?php echo __("Area"); ?>:</b>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_proyecto ORDER BY orden ASC","tipos_asunto[]", $tipos_asunto, "class=\"selectMultiple\" multiple size=5 ", "", "110"); ?>
		</td>
		<td colspan="2">
			<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_area_proyecto ORDER BY orden ASC","areas_asunto[]", $areas_asunto, "class=\"selectMultiple\" multiple size=5 ", "", "140");?>
		</td>
	</tr>
	<?php
		} else {
	?>
		<tr>
			<td align="center">
				&nbsp;
			</td>
			<td align="center">
				&nbsp;
			</td>
		</tr>
		<tr>
			<td rowspan="2">
				&nbsp;
			</td>
			<td rowspan="2">
				&nbsp;
			</td>
		</tr>
	<?php } ?>
	<tr>
		<td align="left" colspan="2">
			<input type="checkbox" name="area_y_categoria" value="1" <?php echo $area_y_categoria ? 'checked="checked"' : ''; ?> onclick="Categorias(this, this.form);" title="Seleccionar área y categoría" />&nbsp;<span style="font-size:9px"><?php echo __('Seleccionar área y categoría'); ?>
		</td>
	</tr>
	<tr>
		<td colspan="4">
			<div id="area_categoria" style="display:<?php echo $area_y_categoria ? 'inline' : 'none'; ?>;">
				<table>
					<tr valign="top">
						<td align="left">
							<b><?php echo __('Área'); ?>:</b>
						</td>
						<td align="left">
							<b><?php echo __('Categoría'); ?>:</b>
						</td>
						<td align="left" colspan="2" width="40%">&nbsp;</td>
					</tr>
					<tr valign="top">
						<td rowspan="2" align="left">
							<?php echo AreaUsuario::SelectAreas($sesion, "areas[]", $areas, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
						</td>
						<td rowspan="2" align="left">
							<?php echo Html::SelectQuery($sesion,"SELECT id_categoria_usuario, glosa_categoria FROM prm_categoria_usuario ORDER BY glosa_categoria", "categorias[]", $categorias, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
						</td>
						<td align="left" colspan="2" width="40%">&nbsp;</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
</fieldset>


			<!-- SELECTOR TIPO DE DATO -->
<br>
<fieldset align="center" width="90%" class="border_plomo tb_base">
<legend onClick="MostrarOculto('tipo_dato')" style="cursor:pointer">
	<span id="tipo_dato_img"><img src= "<?php echo Conf::ImgDir(); ?>/mas.gif" border="0" ></span>
	<?php echo __('Tipo de Dato'); ?>
</legend>
<input type="checkbox" name="tipo_dato_check" id="tipo_dato_check" value="1" <?php echo $tipo_dato_check ? 'checked' : ''; ?> style="display:none;" />
<center>
<table id="mini_tipo_dato" >
	<tr>
		<td id="td_dato" class="<?php echo $comparar ? 'borde_rojo' : 'borde_blanco'; ?>" >
			<select name="tipo_dato" id="tipo_dato" style="width:180px;" onchange="TipoDato(this.value)" >
			<?php
				foreach($tipos_de_dato as $tipo)
				{
					echo "<option value='".$tipo."'";
					if($tipo_dato == $tipo)
						echo "selected";
					echo ">".__($tipo)."</option>";
				}
			?>
			</select>
		</td>
		<td>
			<span id="vs" style='<?php echo $comparar ? '' : 'display: none;'; ?>'>
				<?php echo __(" Vs. "); ?>
			</span>
		</td>
		<td id="td_dato_comparado" class="borde_azul" style="<?php echo $comparar ? '' : 'display: none;'; ?>" >

			<select name="tipo_dato_comparado" id="tipo_dato_comparado" style="width:180px;" >
			<?php
				foreach($tipos_de_dato as $tipo)
				{
					echo "<option value='".$tipo."'";
					if($tipo_dato_comparado == $tipo)
						echo "selected";
					echo ">".__($tipo)."</option>";
				}
			?>
			</select>
		</td>
	</tr>
</table>
</center>
				<!-- SELECTOR TIPO DE DATO EXPANDIDO-->
<table id="full_tipo_dato" style="border: 0px solid black; width:730px; display: none;" cellpadding="0" cellspacing="0">
	<tr>
		<td align="center">
		<?php
			function celda($nombre)
			{
				global $tipo_dato;
				global $tipo_dato_comparado;
				global $comparar;
				global $glosa_dato;
				echo "<td id=\"".$nombre."\"rowspan=2 align=\"center\" class=";
				if($tipo_dato == $nombre || ( !isset($tipo_dato) && $nombre=='horas_trabajadas' ))
					echo "boton_presionado";
				else if($tipo_dato_comparado == $nombre && $comparar)
					echo "boton_comparar";
				else
					echo "boton_normal";
				echo " style=\"height:25px; font-size: 11px; vertical-align: middle; cursor:pointer; \"";
				echo "onclick= TipoDato('".$nombre."')";
				echo " title= \"".__($glosa_dato[$nombre])."\"";
				echo " > ".__($nombre)."</td>";
			}
			function borde_abajo($colspan = 1)
			{
				echo "<td";
				if($colspan!=1) echo " colspan=".$colspan;
				echo " style=\"width:10px; font-size: 3px; border-bottom-style: dotted; border-width: 1px; \"> &nbsp; </td>";
			}
			function borde_derecha()
			{
				echo "<td rowspan=3 style=\"font-size: 3px; width:10px; border-right-style: dotted; border-width: 1px; \"> &nbsp; </td>";
			}
			function nada($numero = 1)
			{
				for($i=0; $i< $numero; $i++)
				echo "<td style=\"font-size: 3px; width:10px; height:7px; \"> &nbsp; </td>";
			}
			function visible_moneda($s,$select = '')
			{
				global $tipo_dato;
				echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
				echo "<div id='moneda".$select."' style =\" height:25px; font-size: 14px; ";
				if ( in_array($tipo_dato,array('valor_cobrado','valor_por_cobrar','valor_pagado','valor_por_pagar')))
					echo " display:inline;\" >";
				else
					echo " display:none;\" >";
				echo $s."</div>";

				echo "<div id='anti_moneda".$select."' style =\" ";
				if ( !in_array($tipo_dato,array('valor_cobrado','valor_por_cobrar','valor_pagado','valor_por_pagar')))
					echo " display:inline;\" >";
				else
					echo " display:none;\" >";
				echo "&nbsp; </div>";

				echo "</td>";
			}
			function moneda()
			{
				visible_moneda(__('Moneda').':');
			}
			function select_moneda()
			{
				global $sesion;
				global $id_moneda;
				visible_moneda(Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda",$id_moneda? $id_moneda:'3', '','',"60"),'_select');
			}
			function tinta2()
			{
				global $comparar;
				echo "<td rowspan=3 align=\"center\" style=\"vertical-align: middle; width:70px; height: 20px; \"> ";
					echo "<table id = \"tipo_tinta\" ";
					if(!$comparar)
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
			function tinta()
			{
				global $comparar;
				echo "<td rowspan=3 align=\"center\" style=\"vertical-align: middle; width:100px; height: 20px; \"> ";
					echo "<span id= \"tipo_tinta\" style =\" width: 100px; ";
					if(!$comparar)
						echo " display:none; ";
					echo " \" >";
							echo "<input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"rojo\" checked=\"checked\" >";
							echo "<span style= \"background-color: red;\" >&nbsp;&nbsp;&nbsp;&nbsp;</span>";
							echo " <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"azul\"> ";
							echo "<span style= \"background-color: blue;\" >&nbsp;&nbsp;&nbsp;&nbsp;</span> </span>";
				echo "&nbsp; </td>";
			}
		?>
		<table style="border: 0px solid black; width:730px" cellpadding="0" cellspacing="0">
			<tr>
				<?php echo celda('horas_trabajadas'); ?>
				<?php echo borde_abajo(2); ?>
				<?php echo celda('horas_cobrables'); ?>
				<?php echo borde_abajo(2); ?>
				<?php echo celda('horas_visibles'); ?>
				<?php echo borde_abajo(2); ?>
				<?php echo celda('horas_cobradas'); ?>
				<?php echo borde_abajo(2); ?>
				<?php echo celda('horas_pagadas'); ?>
			</tr>
			<tr>
				<?php echo borde_derecha(); ?>
				<?php echo nada(); ?>
				<?php echo borde_derecha(); ?>
				<?php echo nada(); ?>
				<?php echo borde_derecha(); ?>
				<?php echo nada(); ?>
				<?php echo borde_derecha(); ?>
				<?php echo nada(); ?>
			</tr>
			<tr>
				<?php echo nada(9); ?>
			</tr>
			<tr>
				<?php echo nada(); ?>
				<?php echo borde_abajo(); ?>
				<?php echo celda("horas_no_cobrables"); ?>
				<?php echo borde_abajo(); ?>
				<?php echo celda("horas_castigadas"); ?>
				<?php echo borde_abajo(); ?>
				<?php echo celda("horas_por_cobrar"); ?>
				<?php echo borde_abajo(); ?>
				<?php echo celda("horas_por_pagar"); ?>
			</tr>
			<tr>
				<?php echo nada(5); ?>
				<?php echo borde_derecha(); ?>
				<?php echo nada(3); ?>
			</tr>
			<tr>
				<?php echo nada(12); ?>
			</tr>
			<tr>
				<?php echo nada(7); ?>
				<?php echo borde_abajo(); ?>
				<?php echo celda("horas_incobrables"); ?>
				<?php echo nada(3); ?>
			</tr>
			<tr>
				<?php echo nada(1); ?>
			</tr>
			<tr>
				<?php echo nada(13); ?>
			</tr>
			<tr>
				<?php echo celda("valor_cobrado"); ?>
				<?php echo borde_abajo(2); ?>
				<?php echo celda("valor_pagado"); ?>
				<?php echo nada(2); ?>
				<?php echo moneda(); ?>
				<?php echo nada(2); ?>
				<?php echo celda("valor_hora"); ?>
				<?php echo nada(2); ?>
				<?php echo celda("diferencia_valor_estandar");; ?>
			</tr>
			<tr>
				<?php echo borde_derecha(); ?>
				<?php echo nada(7); ?>
			</tr>
			<tr>
				<?php echo nada(12); ?>
			</tr>
			<tr>
				<?php echo celda("valor_por_cobrar"); ?>
				<?php echo borde_abajo(); ?>
				<?php echo celda("valor_por_pagar"); ?>
				<?php echo nada(2); ?>
				<?php echo select_moneda(); ?>
				<?php echo nada(2); ?>
				<?php echo celda("rentabilidad"); ?>
				<?php echo nada(2); ?>
				<?php echo celda("valor_estandar"); ?>
			</tr>
			<tr>
				<?php echo nada(8); ?>
			</tr>
			<tr>
				<?php echo nada(12); ?>
				<?php echo tinta(); ?>
			</tr>
			<tr>
				<?php echo celda("valor_incobrable"); ?>
				<?php echo nada(11); ?>
			</tr>
			<tr>
				<?php echo nada(11); ?>
			</tr>
		</table>
	 </td>
	</tr>
</table>
</fieldset>
			<!-- SELECTOR DE VISTA -->
<br>
<fieldset align="center" width="90%" class="border_plomo tb_base">
<legend><?php echo __('Vista'); ?></legend>
	<div style="align:center">
		<input type="hidden" name="numero_agrupadores" id="numero_agrupadores" value="<?php echo $numero_agrupadores; ?>" />
		<input type="hidden" name="vista" id="vista" value="" />
		<?php echo __('Agrupar por'); ?>:&nbsp;
		<?php
				$ya_elegidos = array();
				for($i=0;$i<6;$i++)
				{
						echo '<span id="span_agrupador_'.$i.'"';
						if( $i >= $numero_agrupadores)
							echo ' style="display:none;" ';
						echo '>';
						echo '<select name="agrupador['.$i.']" id="agrupador_'.$i.'" style="font-size:10px; margin-top:2px; margin-bottom:2px; margin-left:6px; width:110px;" ';
						echo '/>';
						$elegido = false;
						$valor_previo = '';
						foreach($agrupadores as $key => $v)
						{
							if(!in_array($v,$ya_elegidos))
							{
								echo '<option value="'.$v.'" ';
								if(isset($agrupador[$i]))
								{
									if($agrupador[$i] == $v)
									{
										echo 'selected';
										$valor_previo = '<select style="display:none;" id="agrupador_valor_previo_'.$i.'"><option value = "'.$v.'">'.__($v).'</option></select>';
										$ya_elegidos[] = $v;
									}
								}
								else if(!$elegido)
								{
									echo 'selected';
									$valor_previo = '<select style="display:none;" id="agrupador_valor_previo_'.$i.'"><option value = "'.$v.'">'.__($v).'</option></select>';
									$elegido = true;
									$ya_elegidos[] = $v;
								}
								echo ">".__($v);
								echo "</option>";
							}
						}
						echo '</select></span>';
						echo $valor_previo;
				}
		?>
		<input type="button" class="btn" title="<?php echo __($glosa_boton['tabla']); ?>" id="tabla" value="<?php echo __('Generar Excel'); ?>" onclick="Generar(this.form,'tabla');">
	</div>
</fieldset>
</td></tr></table>

<script> RevisarMoneda();</script>

		<!-- RESULTADO -->
<?php
}
?>
</form>

<script>
Calendar.setup(
	{
		inputField	: "fecha_ini",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_ini"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_fin",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_fin"		// ID of the button
	}
);
</script>
<?php
	$pagina->PrintBottom($popup);

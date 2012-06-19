<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Trabajo.php';
require_once Conf::ServerDir() . '/classes/Reporte.php';

$sesion = new Sesion(array('REP'));

$pagina = new Pagina($sesion);


$pagina->titulo = __('Resumen actividades profesionales');

$pagina->PrintTop($popup);

$tipos_de_dato = array();  //  horas en reporte general.
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

$agrupadores = array(
	'glosa_cliente',
	'codigo_asunto',
	'glosa_asunto',
	'profesional',
	'estado',
	'id_cobro',
	'forma_cobro',
	'tipo_asunto',
	'prm_area_proyecto.glosa',
	'categoria_usuario',
	'area_usuario',
	'fecha_emision',
	'glosa_grupo_cliente',
	'id_usuario_responsable',
	'mes_reporte',
	'dia_reporte',
	'mes_emision'
);  // vista 


$tipos_moneda = Reporte::tiposMoneda();

$hoy = date("Y-m-d");
if (!$fecha_anio) {
	$fecha_anio = date('Y');
}
if (!$fecha_mes) {
	$fecha_mes = date('m');
}


$fecha_ultimo_dia = date('t', mktime(0, 0, 0, $fecha_mes, 5, $fecha_anio));

$fecha_m = '' . $fecha_mes;

if ($rango && ($fecha_ini != '' && $fecha_fin != '')) {
	$periodo_txt = Utiles::sql2date($fecha_ini) . ' ' . __('a') . ' ' . Utiles::sql2date($fecha_fin);
} else {
	$fecha_fin = $fecha_ultimo_dia . "-" . $fecha_m . "-" . $fecha_anio;
	$fecha_ini = "01-" . $fecha_m . "-" . $fecha_anio;
	$periodo_txt = ucfirst(Utiles::sql2fecha($fecha_ini, '%B')) . ' ' . $fecha_anio;
}

$reporte = new Reporte($sesion);

/* SELECTS MULTIPLES */

// PROFESIONALES usuarios[]

$usuarios_tmp = '';
if (is_array($usuariosF)) {
	foreach ($usuariosF as $key => $value) {
		if (strlen($usuarios_tmp) > 0) {
			$usuarios_tmp .= ",";
		}
		$usuarios_tmp .= $value;
	}
}
$usuarios = $usuarios_tmp;


// CLIENTES clientes[]

$clientes_tmp = '';
if (is_array($clientesF)) {
	foreach ($clientesF as $key => $value) {
		if (strlen($clientes_tmp) > 0) {
			$clientes_tmp .= ",";
		}
		$clientes_tmp .= $value;
	}
}
$clientes = $clientes_tmp;


// AREA areas[]

$areas_tmp = '';
if (is_array($areasF)) {
	foreach ($areasF as $key => $value) {
		if (strlen($areas_tmp) > 0) {
			$areas_tmp .= ",";
		}
		$areas_tmp .= $value;
	}
}
$areas = $areas_tmp;


// AREA TRABAJO  areas_trabajo[]

$areas_trabajo_tmp = '';
if (is_array($areas_trabajoF)) {
	foreach ($areas_trabajoF as $key => $value) {
		if (strlen($areas_trabajo_tmp) > 0) {
			$areas_trabajo_tmp .= ",";
		}
		$areas_trabajo_tmp .= $value;
	}
}
$areas_trabajo = $areas_trabajo_tmp;


// CATEGORIA areas_usuario[]

$areas_usuario_tmp = '';
if (is_array($areas_usuarioF)) {
	foreach ($areas_usuarioF as $key => $value) {
		if (strlen($areas_usuario_tmp) > 0) {
			$areas_usuario_tmp .= ",";
		}
		$areas_usuario_tmp .= $value;
	}
}
$areas_usuario = $areas_usuario_tmp;

$campo_fecha = "trabajo";

$campos_porcentajes = array();

if (isset($_POST['tipo'])) {
	switch ($_POST['tipo']) {
		case 'Profesional':
			$vista = 'profesional-glosa_grupo_cliente-glosa_cliente-glosa_asunto';
			$pos_total = 'c';
			$campos_porcentajes = array('f' => 'c');
			break;
		case 'Cliente':
			$vista = 'glosa_grupo_cliente-glosa_cliente-glosa_asunto-profesional';
			$pos_total = 'd';
			$campos_porcentajes = array('f' => 'd');
			break;
		case 'AreaProfesional':
			$vista = 'area_trabajo-profesional';
			$pos_total = 'd';
			$campos_porcentajes = array('e' => 'total', 'f' => 'e');
			break;
		case 'AreaCliente':
			$vista = 'area_trabajo-glosa_grupo_cliente-glosa_cliente-glosa_asunto';
			$pos_total = 'c';
			$campos_porcentajes = array('c' => 'total', 'f' => 'c');
			break;
		case 'Actividades':
			$vista = 'glosa_actividad-profesional-glosa_cliente-glosa_asunto';
			$pos_total = 'c';
			$campos_porcentajes = array('c' => 'total');
			break;
		default:
			$vista = 'profesional-glosa_grupo_cliente-glosa_cliente-glosa_asunto';
			$pos_total = 'c';
			$campos_porcentajes = array('f' => 'c');
			break;
	}
} else {
	$vista = 'profesional-glosa_grupo_cliente-glosa_cliente-glosa_asunto';
}

if (isset($_POST['horas_sql'])) {
	if ($_POST['horas_sql'] == 'horas_trabajadas_cobrables') {
		$tipo_dato = 'horas_trabajadas';
		$tipo_dato_comparado = 'horas_visibles';
	} else {
		$tipo_dato = $_POST['horas_sql'];
		$tipo_dato_comparado = '';
	}
} else {
	$tipo_dato = 'horas_trabajadas';
	$tipo_dato_comparado = '';
}

$agrupadores = explode('-', $vista);
?>
<script type="text/javascript">
	function Generar(form, valor)
	{
		if(form.tipo.value == 'Profesional')
		{
			form.ver.value = 'prof';
		}
		else if(form.tipo.value == 'Cliente')
		{
			form.ver.value = 'cliente';
		}
		else if(form.tipo.value == 'AreaProfesional')
		{
			form.ver.value = 'area_prof';
		}
		else if(form.tipo.value == 'AreaCliente')
		{
			form.ver.value = 'area_cliente';
		}
		else if(form.tipo.value == 'Actividades')
		{
			form.ver.value = 'actividades';
		}
		else
		{
			alert('Seleccione tipo de vista');
			return false;
		}
	
		form.opc.value = valor;
		if(valor == 'pdf')
		{
			form.action = 'html_to_pdf.php?frequire=resumen_actividades.php&popup=1';
		}
		else if(valor == 'op')
		{
			form.target = '_blank';
			form.action = 'resumen_actividades.php?popup=1';
		}
		else
		{
			form.target = '_self';
			form.action = 'resumen_actividades.php';
		}

		form.submit();
	}

	function DetalleCliente(form, codigo, id_usuario)
	{
		if(!form) {
			var form = $('formulario');
		}
		form.tipo.value = 'Cliente';
		for (var i=0;i < form['clientes[]'].options.length;i++)
		{
			if (form['clientes[]'].options[i].value == codigo) {
				form['clientes[]'].options[i].selected = true;
			} else {
				form['clientes[]'].options[i].selected = false;
			}
		}

		form.action = '?ver=cliente&codigo_cliente='+codigo+'&usuarios='+id_usuario;
		form.submit();
	}

	function DetalleUsuario(form, id_usuario)
	{
		if(!form) {
			var form = $('formulario');
		}

		form.tipo.value = 'Profesional';
		for (var i=0;i < form['usuarios[]'].options.length;i++)
		{
			if (form['usuarios[]'].options[i].value == id_usuario) {
				form['usuarios[]'].options[i].selected = true;
			} else {
				form['usuarios[]'].options[i].selected = false;
			}
		}
		form.action = '?ver=prof&usuarios='+id_usuario;
		form.submit();
	}

	function Rangos(obj, form)
	{
		var td_show = $('periodo_rango');
		var td_hide = $('periodo');

		if(obj.checked) {
			td_hide.style['display'] = 'none';
			td_show.style['display'] = 'inline';
		} else {
			td_hide.style['display'] = 'inline';
			td_show.style['display'] = 'none';
		}
	}

	function Categorias(obj, form)
	{
		var td_show = $('area_categoria');
		if (obj.checked) {
			td_show.style['display'] = 'inline';
		} else {
			td_show.style['display'] = 'none';
		}
	}
</script>
<style>
	#tbl {
		width: 700px;
		padding: 0;
		margin: 0;
		border: 0px solid #C1DAD7;
		font-size: 100%;
		empty-cells:show;
	}

	#tbl_general {
		width: 730px;
		padding: 0;
		margin: 0;
		border: 1px solid #000000;
		font-size: 100%;
		empty-cells:show;
	}

	#tbl_2 {
		width: 730px;
		padding: 0;
		margin: 0;
		border: 0px solid #C1DAD7;
		background-color:#E3EFF2;
		empty-cells:show;
	}

	td.usuario_nombre {
		color: #4f6b72;
		text-align: left;
		background-color: #E0E0E0;
	}

	td.grupo_nombre {
		color: #4f6b72;
		text-align: left;
		background-color: #EAEEF2;
	}

	td.cliente_nombre {
		color: #4f6b72;
		text-align: left;
		background-color: #F3F5F8;
	}

	td.asunto_nombre {
		color: #4f6b72;
		text-align: left;
		background-color: #FDFDFD;
	}

	/* HORAS */
	td.usuario_hr {
		color: #4f6b72;
		text-align: center;
		background-color: #E0E0E0;
	}

	td.grupo_hr {
		color: #4f6b72;
		text-align: center;
		background-color: #EAEEF2;
	}

	td.cliente_hr {
		color: #4f6b72;
		text-align: center;
		background-color: #F3F5F8;
	}

	td.asunto_hr {
		color: #4f6b72;
		text-align: center;
		background-color: #FDFDFD;
	}

	.alt {
		background-color: #D7ECF7;
		color: #797268;
		text-align:center;
	}

	#tbl_grupo
	{
		border: 0px solid #CCCCCC;
		border-collapse:collapse;
		background-color:#ffffff;
	}
	#tbl_cliente
	{
		border: 0px solid #CCCCCC;
		border-collapse:collapse;
		background-color:#F2FBFD;
	}
	#tbl_asunto
	{
		border: 0px solid #CCCCCC;
		border-collapse:collapse;
		/*background-color:#DFECEE;*/
	}
	#tbl_inset
	{
		border-collapse:collapse;
	}

	#tbl_prof
	{
		border: 0px solid #CCCCCC;
		border-collapse:collapse;
		background-color:#C6DEAD;
	}

	.selectMultiple {
		font-size:9px;
		font-family:Arial, Helvetica, sans-serif;
		border:solid 1px #CCCCCC;
		width:200px;
	}

	a:link
	{
		text-decoration: none;
		color: #002255;
	}

	table
	{
		border-collapse:collapse;
		font-family:Arial, Helvetica, sans-serif;
	}

	table.planilla
	{
		width: 710px;
		border-collapse:collapse;
		border-width: 0px;
		font-family:Arial, Helvetica, sans-serif;
		font-size:14px;
	}

	td
	{
		vertical-align:top;
	}

	.td_header
	{
		background-color: #D7ECF7;
		color: #000000;
		font-size:14px;
		text-align:center;
		border-right: 1px solid #CCCCCC;
	}
	.td_h1	{		<?php
if (sizeof($agrupadores) < 6) {
	echo "display:none;";
}
?>	}
	.td_h2	{		<?php
if (sizeof($agrupadores) < 5) {
	echo "display:none;";
}
?>	}
	.td_h3	{		<?php
		if (sizeof($agrupadores) < 4) {
			echo "display:none;";
		}
?>	}
	.td_h4	{		<?php
		if (sizeof($agrupadores) < 3) {
			echo "display:none;";
		}
?>	}
	.td_h5	{		<?php
		if (sizeof($agrupadores) < 2) {
			echo "display:none;";
		}
?>	}

	td.primer
	{
		background-color:#c4c4dd;
		font-size:95%;
<?php
if (sizeof($agrupadores) < 6) {
	echo "display:none;";
}
?>	}
	td.segundo
	{
		background-color:#d2d2ee;
		font-size:90%;
<?php
if (sizeof($agrupadores) < 5) {
	echo "display:none;";
}
?>	}
	td.tercer
	{
		font-size:84%;
		background-color:#d9d9f2;
<?php
if (sizeof($agrupadores) < 4) {
	echo "display:none;";
}
?>
	}
	td.cuarto
	{
		font-size:80%;
		background-color:#e5e5f5;
<?php
if (sizeof($agrupadores) < 3) {
	echo "display:none;";
}
?>

	}
	td.quinto
	{
		font-size:76%;
		background-color:#f1f1f9;
<?php
if (sizeof($agrupadores) < 2) {
	echo "display:none;";
}
?>

	}
	td.sexto
	{
		font-size:74%;
		background-color:#f9f9ff;

	}

	td.campo
	{
		text-align:center;
		border-color: #777777;
		border-right-style: hidden;
		border-right-width: 0px;
		border-left-style: solid;
		border-left-width: 1px;
		border-top-style: solid;
		border-top-width: 1px;
		border-bottom-style: solid;
		border-bottom-width: 1px;
		width:150px;
	}
	td.valor
	{
		white-space:nowrap;
		text-align:right;
		color: #00ff00;
		border-color: #777777;
		border-left-style: hidden;
		border-left-width: 0px;
		border-right-style: solid;
		border-right-width: 1px;
		border-top-style: solid;
		border-top-width: 1px;
		border-bottom-style: solid;
		border-bottom-width: 1px;
	}
	td.porcentaje
	{
		white-space:nowrap;
		text-align:right;
		color: #660000;
		border-color: #777777;	
		border-left-style: hidden;
		border-left-width: 0px;
		border-right-style: solid;
		border-right-width: 1px;
		border-top-style: solid;
		border-top-width: 1px;
		border-bottom-style: solid;
		border-bottom-width: 1px;
	}
	TD.principal { border-right: solid 1px #ccc;  border-bottom: solid 1px #ccc; padding-right: 4px; }
	TD.secundario { border-right: solid 1px #ccc; border-bottom: solid 1px #ccc;  padding-right: 4px; }


	a:link.indefinido { color: #660000; }
	span.indefinido { color: #550000; }

	@media print
	{
		div#print_link {
			display: none;
		}
	}

</style>
<?php
if (!$popup) {
	?>
	<form method="post" name="formulario" action="" id="formulario" autocomplete='off' />
	<input type="hidden" name="opc" id="opc" value='print' />
	<input type="hidden" name="horas_sql" id="horas_sql" value='<?php echo $horas_sql ? $horas_sql : 'horas_trabajadas' ?>'/>
	<input type="hidden" name="ver" id="ver" value='' />
	<input type="hidden" name="postotal" id="postotal" value='d' />
	<input type="hidden" name="tipo_dato" id="tipo_dato" value='' />
	<input type="hidden" name="tipo_dato_comparado" id="tipo_dato_comparado" value='' />

	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<!-- Fin calendario DIV -->
							<?php
							$hoy = date("Y-m-d");
							?>
	<table id="reporte_general_nuevo" class="tb_base border_plomo" style="width:730px;" cellpadding="0" cellspacing="3">
		<tr>
			<td align="center">
				<table style="border: 0px solid black;" width="99%" cellpadding="0" cellspacing="3">
					<tr valign=top>
						<td align=left>
							<b><?php echo __('Profesionales') ?>:</b></td>
						<td align=left>
							<b><?php echo __('Clientes') ?>:</b></td>
						<td align=left colspan=2 width='40%'>
							<b><?php echo __('Periodo') ?>:</b>&nbsp;&nbsp;<input type="checkbox" name="rango" id="rango" value="1" <?php echo $rango ? 'checked' : '' ?> onclick='Rangos(this, this.form);' title='Otro rango' />&nbsp;<span style='font-size:9px'><label for="rango"><?php echo __('Otro rango') ?></label></span></td>
					</tr>
					<tr valign=top>
						<td rowspan="2" align=left>
	<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuariosF[]", $usuariosF, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>	  </td>
						<td rowspan="2" align=left>
	<?php
	if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
		echo Html::SelectQuery($sesion, "SELECT codigo_cliente_secundario AS codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientesF[]", $clientesF, "class=\"selectMultiple\" multiple size=6 ", "", "200");
	} else {
		echo Html::SelectQuery($sesion, "SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientesF[]", $clientesF, "class=\"selectMultiple\" multiple size=6 ", "", "200");
	}
	?>
						</td>
						<!-- PERIODOS -->
								<?php
								if (!$fecha_mes) {
									$fecha_mes = date('m');
								}
								?>
						<td colspan="2" align=left>
							<div id=periodo style='display:<?php echo!$rango ? 'inline' : 'none' ?>;'>
								<select name="fecha_mes" style='width:60px'>
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
	<?php
	if (!$fecha_anio) {
		$fecha_anio = date('Y');
	}
	?>
								<select name="fecha_anio" style='width:55px'>
					<?php for ($i = (date('Y') - 5); $i < (date('Y') + 5); $i++) { ?>
										<option value='<?php echo $i ?>' <?php echo $fecha_anio == $i ? 'selected' : '' ?>><?php echo $i ?></option>
					<?php } ?>
								</select>
							</div>
							<div id=periodo_rango style='display:<?php echo $rango ? 'inline' : 'none' ?>;'>
					<?php echo __('Fecha desde') ?>:
								<input type="text" name="fecha_ini" value="<?php echo $fecha_ini ? $fecha_ini : date("d-m-Y", strtotime("$hoy - 1 month")) ?>" id="fecha_ini" size="11" maxlength="10" />
								<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
								<br />
					<?php echo __('Fecha hasta') ?>:&nbsp;
								<input type="text" name="fecha_fin" value="<?php echo $fecha_fin ? $fecha_fin : date("d-m-Y", strtotime("$hoy - 1 month")) ?>" id="fecha_fin" size="11" maxlength="10" />
								<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
							</div>
						</td>
					</tr>
					<?php
					if (!$tipo) {
						$tipo = 'Profesional';
					}
					switch ($tipo) {
						case 'AreaProfesional':
							$desc = 'Área Trabajo y Profesional';
							break;
						case 'AreaCliente':
							$desc = 'Área Trabajo y Cliente';
							break;
						case 'Cliente':
							$desc = 'Cliente';
							break;
						case 'Actividades':
							$desc = 'Actividades';
							break;
						default:
							$desc = 'Profesional';
							break;
					}
					?>
					<tr valign=top>
						<td align=left colspan=2>
	<?php echo __('Vista') ?>:&nbsp;&nbsp;
							<select name="tipo">
								<option value="Profesional" <?php echo $tipo == 'Profesional' ? 'selected' : '' ?>><?php echo __('Profesional') ?></option>
								<option value="Cliente" <?php echo $tipo == 'Cliente' ? 'selected' : '' ?>><?php echo __('Cliente') ?></option>
	<?php if (UtilesApp::GetConf($sesion, 'UsarAreaTrabajos')) { ?>
									<option value="AreaProfesional" <?php echo $tipo == 'AreaProfesional' ? 'selected' : '' ?>><?php echo __('Área Trabajo - Profesional') ?></option>
									<option value="AreaCliente" <?php echo $tipo == 'AreaCliente' ? 'selected' : '' ?>><?php echo __('Área Trabajo - Cliente') ?></option>
	<?php } ?>
	<?php if (UtilesApp::GetConf($sesion, 'UsoActividades')) { ?>
									<option value="Actividades" <?php echo $tipo == 'Actividades' ? 'selected' : '' ?>><?php echo __('Actividades') ?></option>
	<?php } ?>
							</select><br><br>
	<?php echo __('Horas') ?>:&nbsp;
							<select name='horas_sql' id='horas_sql' style='width:200px'>
								<option value='horas_trabajadas' <?php echo!$horas_sql ? 'selected' : '' ?>><?php echo __('hr_trabajadas') ?></option>
								<option value='horas_visibles' <?php echo $horas_sql == 'horas_visibles' ? 'selected' : '' ?>><?php echo __('hr_cobrable') ?></option>
								<option value='horas_trabajadas_cobrables' <?php echo $horas_sql == 'horas_trabajadas_cobrables' ? 'selected' : '' ?>><?php echo __('horas_trabajadas_cobrables') ?></option>
								<option value='horas_no_cobrables' <?php echo $horas_sql == 'horas_no_cobrables' ? 'selected' : '' ?>><?php echo __('hr_no_cobrables') ?></option>
								<option value='horas_castigadas' <?php echo $horas_sql == 'horas_castigadas' ? 'selected' : '' ?>><?php echo __('hr_castigadas') ?></option>
								<option value='horas_spot' <?php echo $horas_sql == 'horas_spot' ? 'selected' : '' ?>><?php echo __('hr_spot') ?></option>
								<option value='horas_convenio' <?php echo $horas_sql == 'horas_convenio' ? 'selected' : '' ?>><?php echo __('hr_convenio') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td align="left"><input type="checkbox" name="area_y_categoria" id="area_y_categoria" value="1" <?php echo $area_y_categoria ? 'checked="checked"' : '' ?> onclick="Categorias(this, this.form);" title="Seleccionar área y categoría" />&nbsp;<span style="font-size:9px"><label for="area_y_categoria"><?php echo __('Seleccionar área y categoría') ?></label</span></td>
						<td align=right>&nbsp;</td>
						<td align=left colspan=2>
							<input type=button class=btn value="<?php echo __('Generar planilla') ?>" onclick="Generar(this.form,'print')" />
							<input type=button class=btn value="<?php echo __('Imprimir') ?>" onclick="Generar(this.form,'op');">
							<input type=button class=btn value="<?php echo __('Generar Gráfico') ?>" onclick="Generar(this.form,'grafico');">
						</td>
					</tr>
					<tr>
						<td colspan="3">
	<?php echo __('Mostrar sólo los') ?>
							<input type="text" name="limite" value="<?php echo $limite ? $limite : '5' ?>" id="limite" size="2" maxlength="2" />
	<?php echo __('resultados superiores agrupando el resto.') ?>
						</td>
					</tr>
				</table>
				<div id="area_categoria" style="display:<?php echo $area_y_categoria ? 'inline' : 'none' ?>;">
					<table>
						<tr valign="top">
							<td align="left">
								<b><?php echo __('Área') ?>:</b>
							</td>
								<?php if (UtilesApp::GetConf($sesion, 'UsarAreaTrabajos')) { ?>
								<td align="left">
									<b><?php echo __('Área Trabajo') ?>:</b>
								</td>
	<?php } ?>
							<td align="left">
								<b><?php echo __('Categoría') ?>:</b>
							</td>
							<td align="left" colspan="2" width="40%">&nbsp;</td>
						</tr>
						<tr valign="top">
							<td rowspan="2" align="left">
	<?php echo Html::SelectQuery($sesion, "SELECT id, glosa FROM prm_area_usuario ORDER BY glosa", "areasF[]", $areasF, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
							</td>
	<?php if (UtilesApp::GetConf($sesion, 'UsarAreaTrabajos')) { ?>
								<td rowspan="2" align="left">
		<?php echo Html::SelectQuery($sesion, "SELECT * FROM prm_area_trabajo ORDER BY id_area_trabajo ASC", 'areas_trabajoF[]', $areas_trabajoF, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
								</td>
	<?php } ?>
							<td rowspan="2" align="left">
	<?php echo Html::SelectQuery($sesion, "SELECT id_categoria_usuario, glosa_categoria FROM prm_categoria_usuario ORDER BY glosa_categoria", "areas_usuarioF[]", $areas_usuarioF, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
							</td>
							<td align="left" colspan="2" width="40%">&nbsp;</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
	</table>
	</form>
	<br /><br />
	<?php
}

if ($opc == 'print' || $popup) {
	$reporte->setCampoFecha($campo_fecha);
	$reporte->setTipoDato($tipo_dato);
	$reporte->setVista($vista);
	$reporte->addRangoFecha($fecha_ini, $fecha_fin);


	/* USUARIOS */
	$users = explode(",", $usuarios);
	if (!is_array($users)) {
		$users = array($users);
	}
	foreach ($users as $usuario) {
		if ($usuario) {
			$reporte->addFiltro('usuario', 'id_usuario', $usuario);
		}
	}

	/* CLIENTES */
	$clients = explode(",", $clientes);
	if (!is_array($clients)) {
		$clients = array($clients);
	}

	foreach ($clients as $cliente) {
		if ($cliente) {
			$reporte->addFiltro('cliente', 'codigo_cliente', $cliente);
		}
	}

	/* AREAS */
	$areas = explode(",", $areas_asunto);
	if (!is_array($areas)) {
		$areas = array($areas);
	}
	foreach ($areas as $area) {
		if ($area) {
			$reporte->addFiltro('asunto', 'id_area_proyecto', $area);
		}
	}
	/* TIPOS */
	$tipos = explode(",", $tipos_asunto);
	if (!is_array($tipos)) {
		$tipos = array($tipos);
	}
	foreach ($tipos as $tipo) {
		if ($tipo) {
			$reporte->addFiltro('asunto', 'id_tipo_asunto', $tipo);
		}
	}

	/* AREAS USUARIO */
	$areas_usuario = explode(",", $areas_usuario);
	if (!is_array($areas_usuario)) {
		$areas_usuario = array($areas_usuario);
	}
	foreach ($areas_usuario as $area_usuario) {
		if ($area_usuario) {
			$reporte->addFiltro('usuario', 'id_area_usuario', $area_usuario);
		}
	}

	/* CATEGORIAS USUARIO */
	$categorias_usuario = explode(",", $categorias_usuario);
	if (!is_array($categorias_usuario)) {
		$categorias_usuario = array($categorias_usuario);
	}
	foreach ($categorias_usuario as $categoria_usuario) {
		if ($categoria_usuario) {
			$reporte->addFiltro('usuario', 'id_categoria_usuario', $categoria_usuario);
		}
	}

	/* AREAS TRABAJO */

	$areas_trabajo = explode(",", $areas_trabajo);

	if (!is_array($areas_trabajo)) {
		$areas_trabajo = array($areas_trabajo);
	}
	foreach ($areas_trabajo as $area_trabajo) {
		if ($area_trabajo) {
			$reporte->addFiltro('trabajo', 'id_area_trabajo', $area_trabajo);
		}
	}

	$reporte->id_moneda = $id_moneda;

	//genero el formato valor a ser usado en las celdas (
	$moneda = new Moneda($sesion);
	$moneda->Load($id_moneda);


	//echo ">>> " . $reporte->sQuery(); exit; 

	$reporte->Query();
	$r = $reporte->toArray();
	$r_c = $r;

	if ($tipo_dato_comparado) {
		$reporte = new Reporte($sesion);
		foreach ($users as $usuario) {
			if ($usuario) {
				$reporte->addFiltro('usuario', 'id_usuario', $usuario);
			}
		}
		foreach ($clients as $cliente) {
			if ($cliente) {
				$reporte->addFiltro('cliente', 'codigo_cliente', $cliente);
			}
		}
		foreach ($tipos as $tipo) {
			if ($tipo) {
				$reporte->addFiltro('asunto', 'id_tipo_asunto', $tipo);
			}
		}
		foreach ($areas as $area) {
			if ($area) {
				$reporte->addFiltro('asunto', 'id_area_proyecto', $area);
			}
		}
		foreach ($areas_usuario as $area_usuario) {
			if ($area_usuario) {
				$reporte->addFiltro('usuario', 'id_area_usuario', $area_usuario);
			}
		}
		foreach ($categorias_usuario as $categoria_usuario) {
			if ($categoria_usuario) {
				$reporte->addFiltro('usuario', 'id_categoria_usuario', $categoria_usuario);
			}
		}


		$reporte->id_moneda = $id_moneda;
		$reporte->addRangoFecha($fecha_ini, $fecha_fin);
		$reporte->setTipoDato($tipo_dato_comparado);
		$reporte->setVista($vista);
		//$reporte->setProporcionalidad($prop);

		if ($campo_fecha) {
			$reporte->setCampoFecha($campo_fecha);
		}

		$reporte->Query();
		$r_c = $reporte->toArray();

		//Se añaden datos faltantes en cada arreglo:
		$r = $reporte->fixArray($r, $r_c);
		$r_c = $reporte->fixArray($r_c, $r);
	}

	if ($tipo_dato_comparado) {
		$titulo_reporte = __('Resumen - ') . ' ' . __($tipo_dato) . ' vs. ' . __($tipo_dato_comparado) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
	} else {
		$titulo_reporte = __('Resumen - ') . ' ' . __($tipo_dato) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
	}

	if (sizeof($r) == 2) {
		$titulo_reporte = __('No se encontraron datos con el tipo específicado en el período.');
	}
	?>
	<table border=1 cellpadding="3" class="planilla" id ="tabla_planilla" width="100%" >
		<tbody>
			<tr>
				<td colspan=5 style='font-size:90%; font-weight:bold' align=center> 
								<?php echo $titulo_reporte ?>
				</td>
				<td colspan="3" >
					<table cellpadding="2" width="100%" >
						<tr>
							<td style='' align=right>
						<?php echo __('Total') . ' ' . __($tipo_dato) ?>:
							</td>
							<td align="right" style=''>
	<?php echo $r['total'] ?>
							</td>
							<td style='' align=right>
	<?php echo (Reporte::requiereMoneda($tipo_dato)) ? __(Reporte::simboloTipoDato($tipo_dato, $sesion, $id_moneda)) : "&nbsp;" ?>
							</td>
						</tr>
	<?php if ($tipo_dato_comparado) { ?>
							<tr>
								<td align=right>
		<?php echo __('Total') . ' ' . __($tipo_dato_comparado) ?>:
								</td>
								<td align="right" style='white-space:nowrap;'>
		<?php echo $r_c['total'] ?>
								</td>
								<td>
		<?php echo (Reporte::requiereMoneda($tipo_dato_comparado)) ? __(Reporte::simboloTipoDato($tipo_dato_comparado, $sesion, $id_moneda)) : "&nbsp;" ?>
								</td>
							</tr>
	<?php } ?>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	<table border="1" cellpadding="3" class="planilla" id="tabla_planilla_2" width="100%" >
	</tbody>
	<?php

	//Imprime un valor en forma de Link. Añade los filtros correpondientes para ver los trabajos.
	function url($valor, $filtros = array()) {
		global $fecha_ini, $fecha_fin, $clientes, $usuarios;

		$u_clientes = '&lis_clientes=' . $clientes;
		if (!$clientes) {
			$u_clientes = '';
		}
		$u_usuarios = '&lis_usuarios=' . $usuarios;
		if (!$usuarios) {
			$u_usuarios = '';
		}

		$u = "<a href='javascript:void(0)' onclick=\"window.parent.location.href= 'horas.php?from=reporte&fecha_ini=" . $fecha_ini . "&fecha_fin=" . $fecha_fin . $u_usuarios . $u_clientes;

		foreach ($filtros as $filtro) {
			if ($filtro['filtro_valor']) {
				$u.= "&" . $filtro['filtro_campo'] . "=" . urlencode($filtro['filtro_valor']);
				if ($filtro['filtro_campo'] == 'glosa_actividad' && $filtro['filtro_valor'] == 'Indefinido') {
					$u.= "&sin_actividad_definida=1";
				}
			} else {
				$u.= "&" . $filtro['filtro_campo'] . "=NULL";
			}
		}
		$u .= "'\" ";

		if ($valor === '99999!*') {
			$u .= " title = \"" . __("Valor Indeterminado: el denominador de la fórmula es 0.") . "\" class = \"indefinido\"  ";
		}
		$u.= ">" . $valor . "</a>";
		return $u;
	}

	function celda_valor($valor, $filtros = array(), $valor_comparado) {
		global $sesion;
		global $tipo_dato_comparado;
		global $tipo_dato;
		global $formato_valor;

		if ($tipo_dato_comparado) {
			echo "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\"> ";
			echo url(Reporte::FormatoValor($sesion, $valor['valor'], $tipo_dato, '', $formato_valor), $filtros);
			echo "</td> <tr > <td class=\"valor secundario\"> ";
			echo url(Reporte::FormatoValor($sesion, $valor_comparado['valor'], $tipo_dato_comparado, '', $formato_valor), $filtros);
			echo "</td> </tr> </table>";
		} else {
			echo url(Reporte::FormatoValor($sesion, $valor['valor'], $tipo_dato, '', $formato_valor), $filtros);
		}
	}

	function celda_campo($orden, $filas, $valor) {
		echo "<td class=\"" . $orden . " campo\" rowspan=" . $filas;

		if ($valor == __('Indefinido')) {
			echo "> <span title = \"" . __("Agrupador no existe, o no está definido para estos datos.") . "\" class=\"indefinido\" ";
		}
		echo " >" . $valor;
		if ($valor == __('Indefinido')) {
			echo " </span>";
		}
		echo "</td>";
	}

	/*
	 * $array_c = array de donde se sacaran los valores
	 * $pos_total_en_array = valor del key en el array donde se encuentra el valor
	 * $array_k = array de las claves
	 * 
	 * $valor_total = valor segun posición indicada.
	 * 
	 */

	function obtener_valor_en_array($array_c, $pos_total_en_array, $array_k = array()) {
		$valor_total = 0;
		switch ($pos_total_en_array) {
			case 'total':
				$valor_total = $array_c['total'];
				break;
			case 'b':
				$valor_total = $array_c[$array_k[0]][$array_k[1]]['valor'];
				break;
			case 'c':
				$valor_total = $array_c[$array_k[0]][$array_k[1]][$array_k[2]]['valor'];
				break;
			case 'd':
				$valor_total = $array_c[$array_k[0]][$array_k[1]][$array_k[2]][$array_k[3]]['valor'];
				break;
			case 'e':
				$valor_total = $array_c[$array_k[0]][$array_k[1]][$array_k[2]][$array_k[3]][$array_k[4]]['valor'];
				break;
			case 'f':
				$valor_total = $array_c[$array_k[0]][$array_k[1]][$array_k[2]][$array_k[3]][$array_k[4]][$array_k[5]]['valor'];
				break;
			default:
				$valor_total = $array_c[$array_k[0]]['valor'];
		}
		return $valor_total;
	}

	/* HEADERS son agrupadores y tipos de datos */
	echo "<tr>";
	$array_a_num = array('a', 'b', 'c', 'd', 'e', 'f');
	for ($i = 0; $i < 6; $i++) {
		echo "<td class='td_header td_h" . ($i + 1) . "' style='width:80px; border-right: 1px solid #CCCCCC;'>";
		echo __($reporte->agrupador[$i]);
		echo "</td>";
		echo "<td class='td_header td_h" . ($i + 1) . "' style='width:50px; border-right: 1px solid #CCCCCC;'>";
		echo __(Reporte::simboloTipoDato($tipo_dato, $sesion, $id_moneda));
		if ($tipo_dato_comparado) {
			echo __(" vs. ") . __(Reporte::simboloTipoDato($tipo_dato_comparado, $sesion, $id_moneda));
		}
		echo "</td>";

		if (array_key_exists($array_a_num[$i], $campos_porcentajes)) {
			echo "<td class='td_header td_h" . ($i + 1) . "' style='width:50px; border-right: 1px solid #CCCCCC;'>%</td>";
		}
	}
	echo "</tr>";

	/* Iteración principal de Tabla. Se recorren las 4 profundidades del arreglo resultado */
	echo "<tr class=\"primera\">";
	foreach ($r as $k_a => $a) {
		if (is_array($a)) {
			celda_campo('primer', $a['filas'], $k_a);
			echo "<td class=\"primer valor\" rowspan=" . $a['filas'] . " > ";
			echo celda_valor($a, array($a), $r_c[$k_a]);
			echo " </td> ";

			if (array_key_exists('a', $campos_porcentajes)) {
				echo "<td class=\"primer porcentaje\" rowspan=" . $a['filas'] . " > ";
				$v1 = obtener_valor_en_array($r, 'a', array($k_a));
				$total = obtener_valor_en_array($r, $campos_porcentajes['a'], array($k_a));
				$porcentaje = number_format((($v1 * 100) / $total), 2);

				$v1_c = obtener_valor_en_array($r_c, 'a', array($k_a));
				$total_c = obtener_valor_en_array($r_c, $campos_porcentajes['a'], array($k_a));
				;
				$porcentaje_c = number_format((($v1_c * 100) / $total_c), 2);

				if ($tipo_dato_comparado) {
					echo "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\"> <span style=\"color: #333;\">";
					echo $porcentaje;
					echo "</span> </td> <tr > <td class=\"valor secundario\"> <span style=\"color: #333;\">";
					echo $porcentaje_c;
					echo "</span> </td> </tr> </table>";
				} else {
					echo $porcentaje;
				}
				echo " </td>";
			}

			foreach ($a as $k_b => $b) {
				if (is_array($b)) {
					celda_campo('segundo', $b['filas'], $k_b);
					echo "<td class=\"segundo valor\" rowspan=" . $b['filas'] . " > ";
					echo celda_valor($b, array($a, $b), $r_c[$k_a][$k_b]);
					echo " </td> ";

					if (array_key_exists('b', $campos_porcentajes)) {
						echo "<td class=\"segundo porcentaje\" rowspan=" . $b['filas'] . " > ";
						$v1 = obtener_valor_en_array($r, 'b', array($k_a, $k_b));
						$total = obtener_valor_en_array($r, $campos_porcentajes['b'], array($k_a, $k_b));
						$porcentaje = number_format((($v1 * 100) / $total), 2);

						$v1_c = obtener_valor_en_array($r_c, 'b', array($k_a, $k_b));
						$total_c = obtener_valor_en_array($r_c, $campos_porcentajes['b'], array($k_a, $k_b));
						;
						$porcentaje_c = number_format((($v1_c * 100) / $total_c), 2);

						if ($tipo_dato_comparado) {
							echo "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\"> <span style=\"color: #333;\">";
							echo $porcentaje;
							echo "</span> </td> <tr > <td class=\"valor secundario\"> <span style=\"color: #333;\">";
							echo $porcentaje_c;
							echo "</span> </td> </tr> </table>";
						} else {
							echo $porcentaje;
						}
						echo " </td>";
					}

					foreach ($b as $k_c => $c) {
						if (is_array($c)) {
							celda_campo('tercer', $c['filas'], $k_c);
							echo "<td class=\"tercer valor\" rowspan=" . $c['filas'] . " > ";
							echo celda_valor($c, array($a, $b, $c), $r_c[$k_a][$k_b][$k_c]);
							echo " </td>";

							if (array_key_exists('c', $campos_porcentajes)) {
								echo "<td class=\"tercer porcentaje\" rowspan=" . $c['filas'] . " > ";
								$v1 = obtener_valor_en_array($r, 'c', array($k_a, $k_b, $k_c));
								$total = obtener_valor_en_array($r, $campos_porcentajes['c'], array($k_a, $k_b, $k_c));
								$porcentaje = number_format((($v1 * 100) / $total), 2);

								$v1_c = obtener_valor_en_array($r_c, 'c', array($k_a, $k_b, $k_c));
								$total_c = obtener_valor_en_array($r_c, $campos_porcentajes['c'], array($k_a, $k_b, $k_c));
								;
								$porcentaje_c = number_format((($v1_c * 100) / $total_c), 2);

								if ($tipo_dato_comparado) {
									echo "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\"> <span style=\"color: #333;\">";
									echo $porcentaje;
									echo "</span> </td> <tr > <td class=\"valor secundario\"> <span style=\"color: #333;\">";
									echo $porcentaje_c;
									echo "</span> </td> </tr> </table>";
								} else {
									echo $porcentaje;
								}
								echo " </td>";
							}

							foreach ($c as $k_d => $d) {
								if (is_array($d)) {
									celda_campo('cuarto', $d['filas'], $k_d);
									echo "<td class=\"cuarto valor\" rowspan=" . $d['filas'] . " > ";
									echo celda_valor($d, array($a, $b, $c, $d), $r_c[$k_a][$k_b][$k_c][$k_d]);
									echo " </td>";

									if (array_key_exists('d', $campos_porcentajes)) {
										echo "<td class=\"cuarto porcentaje\" rowspan=" . $d['filas'] . " > ";
										$v1 = obtener_valor_en_array($r, 'd', array($k_a, $k_b, $k_c, $k_d));
										$total = obtener_valor_en_array($r, $campos_porcentajes['d'], array($k_a, $k_b, $k_c, $k_d));
										$porcentaje = number_format((($v1 * 100) / $total), 2);

										$v1_c = obtener_valor_en_array($r_c, 'd', array($k_a, $k_b, $k_c, $k_d));
										$total_c = obtener_valor_en_array($r_c, $campos_porcentajes['d'], array($k_a, $k_b, $k_c, $k_d));
										$porcentaje_c = number_format((($v1_c * 100) / $total_c), 2);

										if ($tipo_dato_comparado) {
											echo "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\"> <span style=\"color: #333;\">";
											echo $porcentaje;
											echo "</span> </td> <tr > <td class=\"valor secundario\"> <span style=\"color: #333;\">";
											echo $porcentaje_c;
											echo "</span> </td> </tr> </table>";
										} else {
											echo $porcentaje;
										}
										echo " </td>";
									}

									foreach ($d as $k_e => $e) {
										if (is_array($e)) {
											celda_campo('quinto', $e['filas'], $k_e);
											echo "<td class=\"quinto valor\" rowspan=" . $e['filas'] . " > ";
											echo celda_valor($e, array($a, $b, $c, $d, $e), $r_c[$k_a][$k_b][$k_c][$k_d][$k_e]);
											echo " </td>";

											if (array_key_exists('e', $campos_porcentajes)) {
												echo "<td class=\"quinto porcentaje\" rowspan=" . $e['filas'] . " > ";
												$v1 = obtener_valor_en_array($r, 'e', array($k_a, $k_b, $k_c, $k_d, $k_e));
												$total = obtener_valor_en_array($r, $campos_porcentajes['e'], array($k_a, $k_b, $k_c, $k_d, $k_e));
												$porcentaje = number_format((($v1 * 100) / $total), 2);

												$v1_c = obtener_valor_en_array($r_c, 'e', array($k_a, $k_b, $k_c, $k_d, $k_e));
												$total_c = obtener_valor_en_array($r_c, $campos_porcentajes['e'], array($k_a, $k_b, $k_c, $k_d, $k_e));
												$porcentaje_c = number_format((($v1_c * 100) / $total_c), 2);

												if ($tipo_dato_comparado) {
													echo "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\"> <span style=\"color: #333;\">";
													echo $porcentaje;
													echo "</span> </td> <tr > <td class=\"valor secundario\"> <span style=\"color: #333;\">";
													echo $porcentaje_c;
													echo "</span> </td> </tr> </table>";
												} else {
													echo $porcentaje;
												}
												echo " </td>";
											}

											foreach ($e as $k_f => $f) {
												if (is_array($f)) {
													celda_campo('sexto', $f['filas'], $k_f);
													echo "<td class=\"sexto valor\" rowspan=" . $f['filas'] . " > ";
													echo celda_valor($f, array($a, $b, $c, $d, $e, $f), $r_c[$k_a][$k_b][$k_c][$k_d][$k_e][$k_f]);
													echo " </td>";

													if (array_key_exists('f', $campos_porcentajes)) {
														echo "<td class=\"sexto porcentaje\" rowspan=" . $f['filas'] . " > ";
														$v1 = obtener_valor_en_array($r, 'f', array($k_a, $k_b, $k_c, $k_d, $k_e, $k_f));
														$total = obtener_valor_en_array($r, $campos_porcentajes['f'], array($k_a, $k_b, $k_c, $k_d, $k_e, $k_f));
														$porcentaje = number_format((($v1 * 100) / $total), 2);

														$v1_c = obtener_valor_en_array($r_c, 'f', array($k_a, $k_b, $k_c, $k_d, $k_e, $k_f));
														$total_c = obtener_valor_en_array($r_c, $campos_porcentajes['f'], array($k_a, $k_b, $k_c, $k_d, $k_e, $k_f));
														$porcentaje_c = number_format((($v1_c * 100) / $total_c), 2);

														if ($tipo_dato_comparado) {
															echo "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\"> <span style=\"color: #333;\">";
															echo $porcentaje;
															echo "</span> </td> <tr > <td class=\"valor secundario\"> <span style=\"color: #333;\">";
															echo $porcentaje_c;
															echo "</span> </td> </tr> </table>";
														} else {
															echo $porcentaje;
														}
														echo " </td>";
													}
													echo "</tr> <tr class=\"no_primera\"> ";
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
			echo "</tr>";
		}
	}
	?>
	</tbody>
	</table>
	<?php
}

if ($opc == 'grafico') {

	if (is_array($usuariosF)) {
		$lista_usuarios = join("','", $usuariosF);
		$where_usuario = " AND trabajo.id_usuario IN ('" . $lista_usuarios . "')";
	} else {
		$where_usuario = '';
	}

	if (is_array($clientesF)) {
		$lista_clientes = join("','", $clientesF);
		$where_cliente = "	AND asunto.codigo_cliente IN ('" . $lista_clientes . "')";
	} else {
		$where_cliente = '';
	}

	$where_area = '';
	$where_area_trabajo = '';
	$where_categoria = '';
	if ($area_y_categoria) {
		if (is_array($areasF)) {
			$lista_areas = join("','", $areasF);
			$where_area = " AND usuario.id_area_usuario IN ('$lista_areas')";
		}

		if (UtilesApp::GetConf($sesion, 'UsarAreaTrabajos')) {
			if (is_array($areas_trabajoF)) {
				$lista_areas_trabajo = join("','", $areas_trabajoF);
				$where_area_trabajo = " AND trabajo.id_area_trabajo IN ('$lista_areas_trabajo') ";
			}
		}

		if (is_array($areas_usuarioF)) {
			$lista_categorias = join("','", $areas_usuarioF);
			$where_categoria = " AND usuario.id_categoria_usuario IN ('$lista_categorias')";
		}
	}

	$where = 1;

	if ($rango && ($fecha_ini != '' && $fecha_fin != '')) {
		$where .= " AND trabajo.fecha Between '" . Utiles::fecha2sql($fecha_ini) . "' AND '" . Utiles::fecha2sql($fecha_fin) . "' ";
		$fecha_ini = Utiles::fecha2sql($fecha_ini);
		$fecha_fin = Utiles::fecha2sql($fecha_fin);
		$periodo_txt = Utiles::sql2date($fecha_ini) . ' ' . __('a') . ' ' . Utiles::sql2date($fecha_fin);
	} else {
		$fecha_ini = $fecha_anio . '-' . $fecha_mes . '-01';
		$fecha_fin = $fecha_anio . '-' . $fecha_mes . '-31';
		$where .= " AND trabajo.fecha Between '" . $fecha_ini . "' AND '" . $fecha_fin . "' ";
		$periodo_txt = ucfirst(Utiles::sql2fecha($fecha_ini, '%B')) . ' ' . $fecha_anio;
	}

	if ($ver == 'prof') {
		$orderby = " ORDER BY profesional, grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente	";
	} else if ($ver == 'area_prof') {
		$orderby = " ORDER BY trabajo.id_area_trabajo, profesional, grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente ";
	} else if ($ver == 'area_cliente') {
		$orderby = " ORDER BY trabajo.id_area_trabajo, grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente, asunto.codigo_asunto ASC ";
	} else if ($ver == 'actividades') {
		$orderby = " ORDER BY trabajo.codigo_actividad,profesional, cliente.glosa_cliente, asunto.codigo_asunto ASC ";
	} else {
		$orderby = " ORDER BY grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente, asunto.codigo_asunto ASC ";
	}

	if ($ver == 'prof') {
		$group_by = " GROUP BY trabajo.id_usuario, cliente.codigo_cliente, asunto.codigo_asunto, grupo_cliente.id_grupo_cliente ";
	} else if ($ver == 'area_prof') {
		$group_by = " GROUP BY trabajo.id_area_trabajo, profesional";
	} else if ($ver == 'area_cliente') {
		$group_by = " GROUP BY trabajo.id_area_trabajo, grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente, asunto.codigo_asunto";
	} else if ($ver == 'actividades') {
		$orderby = " GROUP BY trabajo.codigo_actividad,profesional, cliente.codigo_cliente, asunto.codigo_asunto ASC ";
	} else {
		$group_by = " GROUP BY trabajo.id_usuario, cliente.codigo_cliente, asunto.codigo_asunto, grupo_cliente.id_grupo_cliente ";
	}

	$total_hr = 0;
	$col_resultado = "Hr.";

	if ($horas_sql == 'horas_cobrables') {
		$where .= " AND trabajo.cobrable = 1 ";
	}

	if ($horas_sql == 'horas_no_cobrables') {
		$select = "SUM(TIME_TO_SEC(duracion)/3600 ) as horas_no_cobrables,";
		$where .= " AND  trabajo.cobrable = 0 ";
	}

	if ($horas_sql == 'horas_castigadas') {
		$select = "SUM(TIME_TO_SEC(trabajo.duracion)-TIME_TO_SEC(trabajo.duracion_cobrada))/3600 as horas_castigadas,";
		$where .= " AND trabajo.cobrable = 1 ";
	}

	if ($horas_sql == 'horas_spot') {
		$join = " LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato \n";
		$where .= " AND ( ( cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND ( cobro.forma_cobro IN ('TASA','CAP') )) OR ( (cobro.estado IS NULL OR cobro.estado IN ('CREADO','EN REVISION')) AND (contrato.forma_cobro IN ('TASA','CAP') OR contrato.forma_cobro IS NULL ) ) ) \n";
		$horas_sql = 'horas_cobrables';
	}
	if ($horas_sql == 'horas_convenio') {
		$join = " LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato \n";
		$where .= " AND ( ( cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND  ( cobro.forma_cobro IN ('FLAT FEE','RETAINER') )) OR ( (cobro.estado IS NULL OR cobro.estado IN ('CREADO','EN REVISION')) AND (contrato.forma_cobro IN ('FLAT FEE','RETAINER') ) ) ) \n";
		$horas_sql = 'horas_cobrables';
	}

	$query = "SELECT 
								CONCAT_WS(' ',usuario.nombre, usuario.apellido1) as profesional, 
								usuario.username as username_profesional,
								usuario.id_usuario, 
								cliente.id_cliente, 
								cliente.codigo_cliente, 
								cliente.codigo_cliente_secundario, 
								cliente.glosa_cliente,
								CONCAT(asunto.glosa_asunto,' (',asunto.codigo_asunto,')') AS glosa_asunto,
														trabajo.id_area_trabajo as id_area_trabajo, 
														IF( trabajo.id_area_trabajo IS NULL,'Indefinido',prm_area_trabajo.glosa) as glosa_area_trabajo, 
								asunto.codigo_asunto, 
								asunto.codigo_asunto_secundario, 
								grupo_cliente.id_grupo_cliente, 
								grupo_cliente.glosa_grupo_cliente, 
								asunto.codigo_cliente,
								IFNULL( NULLIF( IFNULL( actividad.glosa_actividad, 'Indefinido' ), ' ' ), 'Indefinido' ) as glosa_actividad,
								$select
								SUM(TIME_TO_SEC(duracion)/60)/60 as horas_trabajadas,
								SUM(TIME_TO_SEC(if(trabajo.cobrable = 1,duracion_cobrada,0))/60)/60 as horas_cobrables,
								SUM(tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base*cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) as valor_cobrado,
								SUM(tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base*cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) / SUM(TIME_TO_SEC(duracion_cobrada - duracion)/60)/60 / SUM(TIME_TO_SEC(duracion)/60)/60  as valor_hr_promedio
							FROM trabajo
							LEFT JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
												LEFT JOIN prm_area_trabajo ON prm_area_trabajo.id_area_trabajo = trabajo.id_area_trabajo 
							LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
							LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
							LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
							LEFT JOIN cobro on trabajo.id_cobro = cobro.id_cobro
							LEFT JOIN actividad ON ( trabajo.codigo_actividad = actividad.codigo_actividad )
							$join
							WHERE $where
							$where_usuario
							$where_cliente
							$where_area
							$where_area_trabajo
							$where_categoria
							$group_by 
							$orderby";

	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	if (UtilesApp::GetConf($sesion, 'UsaUsernameEnTodoElSistema')) {
		$letra_profesional = 'profesional';
	} else {
		$letra_profesional = 'username_profesional';
	}
	$contador = 0;

	########## VER GRAFICO ##########
	$titulo_reporte = __('Gráfico de') . ' ' . __($horas_sql) . ' ' . __('en vista por') . ' ' . $desc;
	$datos_grafico = '';
	$total = 0;

	if ($ver == 'prof') {
		while ($row = mysql_fetch_array($resp)) {
			if ($row[$letra_profesional] != $nombres[$contador]) {
				$contador++;
				$nombres[$contador] = $row[$letra_profesional];
			}
			$tiempos[$contador] += $row[$horas_sql];
			$total += $row[$horas_sql];
		}
	}

	if ($ver == 'cliente') {
		while ($row = mysql_fetch_array($resp)) {
			if ($row['glosa_cliente'] != $nombres[$contador]) {
				$contador++;
				$nombres[$contador] = $row['glosa_cliente'];
			}
			$tiempos[$contador] += $row[$horas_sql];
			$total += $row[$horas_sql];
		}
	}

	if ($ver == 'area_prof' || $ver == 'area_cliente') {
		while ($row = mysql_fetch_array($resp)) {
			if ($row['glosa_area_trabajo'] != $nombres[$contador]) {
				$contador++;
				$nombres[$contador] = $row['glosa_area_trabajo'];
			}
			$tiempos[$contador] += $row[$horas_sql];
			$total += $row[$horas_sql];
		}
	}

	if ($ver == 'actividades') {
		while ($row = mysql_fetch_array($resp)) {
			if ($row['glosa_actividad'] != $nombres[$contador]) {
				$contador++;
				$nombres[$contador] = $row['glosa_actividad'];
			}
			$tiempos[$contador] += $row[$horas_sql];
			$total += $row[$horas_sql];
		}
	}

	if ($nombres) {
		arsort($tiempos);
		$otros = 0;

		foreach ($tiempos as $key => $tiempo) {
			if ($limite-- > 0) {
				$datos_grafico .= "&nombres[]=" . urlencode($nombres[$key]) . "&tiempo[]=" . str_replace(',', '.', $tiempos[$key]);
			} else {
				$otros += $tiempos[$key];
			}
		}
		if ($otros) {
			$datos_grafico .= "&nombres[]=Otros&tiempo[]=" . str_replace(',', '.', $otros);
		}
	}

	// echo $datos_grafico; 	exit;
	$html_info .= "<img src='graficos/grafico_resumen_actividades.php?titulo=" . $titulo_reporte . $datos_grafico . "' alt='' />";
	//echo 'graficos/grafico_resumen_actividades.php?titulo='.$titulo_reporte.$datos_grafico;
	echo $html_info;
}
?>
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
//En el caso de que la opcion sea imprimir se imprime al final.
if ($popup) {
	echo "<script>window.print();window.close();</script>";
}
$pagina->PrintBottom($popup);

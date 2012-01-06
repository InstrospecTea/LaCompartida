<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';

	$sesion = new Sesion(array('REP'));

	$pagina = new Pagina($sesion);
	
	$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));

	$pagina->titulo = __('Resumen actividades profesionales');

	if(!$popup)
	{
		$pagina->PrintTop($popup);
?>
<script type="text/javascript">
function Generar(form, valor)
{
	/*if(form['usuarios[]'].selectedIndex == -1)
	{
		for (var i=0;i < form['usuarios[]'].options.length;i++)
		{
		  form['usuarios[]'].options[i].selected = true;
		}
	}

	if(form['clientes[]'].selectedIndex == -1)
	{
		for (var i=0;i < form['clientes[]'].options.length;i++)
		{
		  form['clientes[]'].options[i].selected = true;
		}
	}*/

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
	if(!form)
		var form = $('formulario');
	form.tipo.value = 'Cliente';
	for (var i=0;i < form['clientes[]'].options.length;i++)
	{
	  if (form['clientes[]'].options[i].value == codigo)
			form['clientes[]'].options[i].selected = true;
	  else
			form['clientes[]'].options[i].selected = false;
	}

	form.action = '?ver=cliente&codigo_cliente='+codigo+'&usuarios='+id_usuario;
	form.submit();
}

function DetalleUsuario(form, id_usuario)
{
	if(!form)
		var form = $('formulario');

	form.tipo.value = 'Profesional';
	for (var i=0;i < form['usuarios[]'].options.length;i++)
	{
	  if (form['usuarios[]'].options[i].value == id_usuario)
			form['usuarios[]'].options[i].selected = true;
	  else
			form['usuarios[]'].options[i].selected = false;
	}
	form.action = '?ver=prof&usuarios='+id_usuario;
	form.submit();
}

function Rangos(obj, form)
{
	var td_show = $('periodo_rango');
	var td_hide = $('periodo');

	if(obj.checked)
	{
		td_hide.style['display'] = 'none';
		td_show.style['display'] = 'inline';
	}
	else
	{
		td_hide.style['display'] = 'inline';
		td_show.style['display'] = 'none';
	}
}

function Categorias(obj, form)
{
	var td_show = $('area_categoria');
	if(obj.checked)
		td_show.style['display'] = 'inline';
	else
		td_show.style['display'] = 'none';
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
	background-color: E0E0E0;
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
	font:Arial, Helvetica, sans-serif;
	border:solid 1px #CCCCCC;
	width:200px;
}

a:link
{
	text-decoration: none;
	color: #797268;
}
</style>
<?
	}
	else	#Style de impresion
	{
?>
<style>
#tbl {
	width: 700px;
	padding: 0;
	margin: 0;
	border: 0px solid #C1DAD7;
	/*font-size: 100%;*/
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
	empty-cells:show;
}

td.usuario_nombre {
	color: #000000;
	text-align: left;
}

td.grupo_nombre {
	color: #000000;
	text-align: left;
}

td.cliente_nombre {
	color: #000000;
	text-align: left;
}

td.asunto_nombre {
	color: #000000;
	text-align: left;
}

/* HORAS */
td.usuario_hr {
	color: #000000;
	text-align: center;
}

td.grupo_hr {
	color: #000000;
	text-align: center;
}

td.cliente_hr {
	color: #000000;
	text-align: center;
}

td.asunto_hr {
	color: #000000;
	text-align: center;
}

.alt {
	color: #000000;
	text-align:center;
}

#tbl_grupo
{
	border: 0px solid #CCCCCC;
	border-collapse:collapse;
}
#tbl_cliente
{
	border: 0px solid #CCCCCC;
	border-collapse:collapse;
}
#tbl_asunto
{
	border: 0px solid #CCCCCC;
	border-collapse:collapse;
}
#tbl_inset
{
	border-collapse:collapse;
}

#tbl_prof
{
	border: 0px solid #CCCCCC;
	border-collapse:collapse;
}

a:link
{
	text-decoration: none;
	color: #000000;
}
body
{
	margin-top: 0; margin-bottom: 0;
	margin-left: 0; margin-right:0;
}
h1, p { font-family: Garamond, Verdana, serif; }
table
{
  border-collapse:collapse;
  font-size:10px;
  font: Garamond, Verdana;
}
td
{
	vertical-align:top;
}
</style>
<?
}
?>
<form method=post name=formulario action="" id=formulario autocomplete='off'>
<input type=hidden name=opc id=opc value='print'>
<input type=hidden name=horas_sql id=horas_sql value='<?=$horas_sql ? $horas_sql : 'hr_trabajadas' ?>'/>
<input type=hidden name=ver id=ver value=''>
<?
if(!$popup)
{
?>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<?
$hoy = date("Y-m-d");
?>
<table class="tb_base border_plomo" style="width:730px;" cellpadding="0" cellspacing="3">
	<tr>
	  <td align="center">
<table style="border: 0px solid black;" width="99%" cellpadding="0" cellspacing="3">
	<tr valign=top>
		<td align=left>
			<b><?=__('Profesionales')?>:</b></td>
		<td align=left>
			<b><?=__('Clientes')?>:</b></td>
		<td align=left colspan=2 width='40%'>
			<b><?=__('Periodo') ?>:</b>&nbsp;&nbsp;<input type="checkbox" name="rango" id="rango" value="1" <?=$rango ? 'checked' : '' ?> onclick='Rangos(this, this.form);' title='Otro rango' />&nbsp;<span style='font-size:9px'><label for="rango"><?=__('Otro rango') ?></label></span></td>
	</tr>
	<tr valign=top>
	  <td rowspan="2" align=left>
	  	<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]",$usuarios,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>	  </td>
	  <td rowspan="2" align=left>
	  	<? 
                   if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists( 'Conf','CodigoSecundario' ) && Conf::CodigoSecundario() ) ) 
	  			echo Html::SelectQuery($sesion,"SELECT codigo_cliente_secundario AS codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes[]",$clientes,"class=\"selectMultiple\" multiple size=6 ","","200"); 
	 			 else
	 			 	echo Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes[]",$clientes,"class=\"selectMultiple\" multiple size=6 ","","200");
	 		?>
	 	</td>
	 	<!-- PERIODOS -->
<?
	 	if(!$fecha_mes)
	 		$fecha_mes = date('m');
?>
	 	<td colspan="2" align=left>
		 	<div id=periodo style='display:<?=!$rango ? 'inline' : 'none' ?>;'>
		  	    <select name="fecha_mes" style='width:60px'>
		  	    	<option value='1' <?=$fecha_mes==1 ? 'selected':'' ?>><?=__('Enero') ?></option>
		  	    	<option value='2' <?=$fecha_mes==2 ? 'selected':'' ?>><?=__('Febrero') ?></option>
		  	    	<option value='3' <?=$fecha_mes==3 ? 'selected':'' ?>><?=__('Marzo') ?></option>
		  	    	<option value='4' <?=$fecha_mes==4 ? 'selected':'' ?>><?=__('Abril') ?></option>
		  	    	<option value='5' <?=$fecha_mes==5 ? 'selected':'' ?>><?=__('Mayo') ?></option>
		  	    	<option value='6' <?=$fecha_mes==6 ? 'selected':'' ?>><?=__('Junio') ?></option>
		  	    	<option value='7' <?=$fecha_mes==7 ? 'selected':'' ?>><?=__('Julio') ?></option>
		  	    	<option value='8' <?=$fecha_mes==8 ? 'selected':'' ?>><?=__('Agosto') ?></option>
		  	    	<option value='9' <?=$fecha_mes==9 ? 'selected':'' ?>><?=__('Septiembre') ?></option>
		  	    	<option value='10' <?=$fecha_mes==10 ? 'selected':'' ?>><?=__('Octubre') ?></option>
		  	    	<option value='11' <?=$fecha_mes==11 ? 'selected':'' ?>><?=__('Noviembre') ?></option>
		  	    	<option value='12' <?=$fecha_mes==12 ? 'selected':'' ?>><?=__('Diciembre') ?></option>
		  	    </select>
<?
		  	    if(!$fecha_anio)
		  	    	$fecha_anio = date('Y');
?>
		  	    <select name="fecha_anio" style='width:55px'>
		  	    	<? for($i=(date('Y')-5);$i < (date('Y')+5);$i++){ ?>
		  	    	<option value='<?=$i?>' <?=$fecha_anio == $i ? 'selected' : '' ?>><?=$i ?></option>
		  	    	<? } ?>
		  	    </select>
			</div>
			<div id=periodo_rango style='display:<?=$rango ? 'inline' : 'none' ?>;'>
				<?=__('Fecha desde')?>:
					<input type="text" name="fecha_ini" value="<?=$fecha_ini ? $fecha_ini : date("d-m-Y",strtotime("$hoy - 1 month")) ?>" id="fecha_ini" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
				<br />
				<?=__('Fecha hasta')?>:&nbsp;
					<input type="text" name="fecha_fin" value="<?=$fecha_fin ? $fecha_fin : date("d-m-Y",strtotime("$hoy - 1 month")) ?>" id="fecha_fin" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
			</div>
		</td>
	</tr>
<?
	if(!$tipo)
		$tipo = 'Profesional';
             switch($tipo):
         case 'AreaProfesional':
             $desc='Área Trabajo y Profesional';
             break;
         case 'AreaCliente':
             $desc='Área Trabajo y Cliente';
             break;
         case 'Cliente':
             $desc='Cliente';
             break;
         default:
             $desc='Profesional';
             break;
     endswitch;
?>
	<tr valign=top>
	  <td align=left colspan=2>
	  	<?=__('Vista')?>:&nbsp;&nbsp;
      <select name="tipo">
      	<option value="Profesional" <?=$tipo == 'Profesional' ? 'selected' : ''?>><?=__('Profesional') ?></option>
        <option value="Cliente" <?=$tipo == 'Cliente' ? 'selected' : ''?>><?=__('Cliente') ?></option>
     <?php 
     
        if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarAreaTrabajos')): ?>
        <option value="AreaProfesional" <?=$tipo == 'AreaProfesional' ? 'selected' : '' ?>><?=__('Área Trabajo - Profesional') ?></option>
        <option value="AreaCliente" <?=$tipo == 'AreaCliente' ? 'selected' : '' ?>><?=__('Área Trabajo - Cliente') ?></option>
       
     <?php 

     endif; ?>
      </select><br><br>
			<?=__('Horas')?>:&nbsp;
			<select name='horas_sql' id='horas_sql' style='width:200px'>
	  		<option value='hr_trabajadas' <?=!$horas_sql ? 'selected':'' ?>><?=__('hr_trabajadas')?></option>
			<!--
	  		<option value='hr_asunto_cobrable' <?=$horas_sql == 'hr_asunto_cobrable' ? 'selected':'' ?>><?=__('hr_asunto_cobrable')?></option>
	  		<option value='hr_asunto_no_cobrable' <?=$horas_sql == 'hr_asunto_no_cobrable' ? 'selected':'' ?>><?=__('hr_asunto_no_cobrable')?></option> -->
	  		<option value='hr_cobrable' <?=$horas_sql == 'hr_cobrable' ? 'selected':'' ?>><?=__('hr_cobrable')?></option>
	  		<option value='horas_trabajadas_cobrables' <?=$horas_sql == 'horas_trabajadas_cobrables' ? 'selected':'' ?>><?=__('horas_trabajadas_cobrables')?></option>
	  		<option value='hr_no_cobrables' <?=$horas_sql == 'hr_no_cobrables' ? 'selected':'' ?>><?=__('hr_no_cobrables')?></option>
	  		<option value='hr_castigadas' <?=$horas_sql == 'hr_castigadas' ? 'selected':'' ?>><?=__('hr_castigadas')?></option>
	  		<option value='hr_spot' <?=$horas_sql == 'hr_spot' ? 'selected':'' ?>><?=__('hr_spot')?></option>
	  		<option value='hr_convenio' <?=$horas_sql == 'hr_convenio' ? 'selected':'' ?>><?=__('hr_convenio')?></option>
	  		<!--<option value='valor_cobrado' <?=$horas_sql == 'valor_cobrado' ? 'selected':'' ?>><?=__('Valor Cobrado UF')?></option>-->
	  	</select>
		</td>
	</tr>
	<tr>
		<td align="left"><input type="checkbox" name="area_y_categoria" id="area_y_categoria" value="1" <?=$area_y_categoria ? 'checked="checked"' : '' ?> onclick="Categorias(this, this.form);" title="Seleccionar área y categoría" />&nbsp;<span style="font-size:9px"><label for="area_y_categoria"><?=__('Seleccionar área y categoría') ?></label</span></td>
	    <td align=right>&nbsp;</td>
	    <td align=left colspan=2>
	    	<input type=button class=btn value="<?=__('Generar planilla')?>" onclick="Generar(this.form,'print')" />
	    	<input type=button class=btn value="<?=__('Imprimir')?>" onclick="Generar(this.form,'op');">
				<input type=button class=btn value="<?=__('Generar Gráfico')?>" onclick="Generar(this.form,'grafico');">
	    </td>
	</tr>
	<tr>
		<td colspan="3">
			<?=__('Mostrar sólo los')?>
			<input type="text" name="limite" value="<?=$limite ? $limite : '5' ?>" id="limite" size="2" maxlength="2" />
			<?=__('resultados superiores agrupando el resto.')?>
		</td>
	</tr>
</table>
<div id="area_categoria" style="display:<?=$area_y_categoria ? 'inline' : 'none' ?>;">
	<table>
		<tr valign="top">
			<td align="left">
				<b><?=__('Área')?>:</b>
			</td>
			<?php 	if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarAreaTrabajos')): ?>
			<td align="left">
				<b><?=__('Área Trabajo')?>:</b>
			</td>
			<?php 	endif; 	?>
			<td align="left">
				<b><?=__('Categoría')?>:</b>
			</td>
			<td align="left" colspan="2" width="40%">&nbsp;</td>
		</tr>
		<tr valign="top">
			<td rowspan="2" align="left">
				<?php echo Html::SelectQuery($sesion,"SELECT id, glosa FROM prm_area_usuario ORDER BY glosa", "areas[]", $areas, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
			</td>
			<?php if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarAreaTrabajos')): ?>
			<td rowspan="2" align="left">
				<?php echo Html::SelectQuery($sesion,"SELECT * FROM prm_area_trabajo ORDER BY id_area_trabajo ASC",'id_area_trabajo[]', $id_area_trabajo, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200" ); ?>
			</td>
			<?php 	endif; 	?>
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
<br>
<?
}
/*
echo '<pre>';
echo 'usuarios = ';
var_dump($usuarios);
echo '<br />';
echo 'clientes = ';
var_dump($clientes);
echo '<br />';
echo 'areas = ';
var_dump($areas);
echo '<br />';
echo 'categorias = ';
var_dump($categorias);
echo '<br />';
echo '</pre>';
*/
if(is_array($usuarios)){
	$lista_usuarios = join("','",$usuarios);
	$where_usuario = " AND trabajo.id_usuario IN ('".$lista_usuarios."')";
} else {
	$where_usuario = '';
}

if(is_array($clientes)) {
	$lista_clientes = join("','",$clientes);
	$where_cliente = "	AND asunto.codigo_cliente IN ('".$lista_clientes."')";
} else {
	$where_cliente = '';
}

$where_area = '';
$where_area_trabajo = '';
$where_categoria = '';
if($area_y_categoria)
{
	if(is_array($areas)) {
		$lista_areas = join("','", $areas);
		$where_area = " AND usuario.id_area_usuario IN ('$lista_areas')";
	}
		
	if( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarAreaTrabajos')){
		if ( is_array( $id_area_trabajo ) ){
			$lista_areas_trabajo = join("','", $id_area_trabajo);
			$where_area_trabajo = " AND trabajo.id_area_trabajo IN ('$lista_areas_trabajo') ";
		}
	}
	
	if(is_array($categorias)) {
		$lista_categorias = join("','", $categorias);
		$where_categoria = " AND usuario.id_categoria_usuario IN ('$lista_categorias')";
	}
}

$where = 1;

if( $rango && ($fecha_ini != '' && $fecha_fin != '') )
{
	$where .= " AND trabajo.fecha Between '".Utiles::fecha2sql($fecha_ini)."' AND '".Utiles::fecha2sql($fecha_fin)."' ";
	$fecha_ini = Utiles::fecha2sql($fecha_ini);
	$fecha_fin = Utiles::fecha2sql($fecha_fin);
	$periodo_txt = Utiles::sql2date($fecha_ini).' '.__('a').' '.Utiles::sql2date($fecha_fin);
}
else
{
	$fecha_ini = $fecha_anio.'-'.$fecha_mes.'-01';
	$fecha_fin = $fecha_anio.'-'.$fecha_mes.'-31';
	$where .= " AND trabajo.fecha Between '".$fecha_ini."' AND '".$fecha_fin."' ";
	$periodo_txt = ucfirst(Utiles::sql2fecha($fecha_ini,'%B')).' '.$fecha_anio;
}

if($ver == 'prof')
	$orderby = " ORDER BY profesional, grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente	";
else if( $ver == 'area_prof' )
        $orderby = " ORDER BY trabajo.id_area_trabajo, profesional, grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente ";
else if( $ver == 'area_cliente' )
        $orderby = " ORDER BY trabajo.id_area_trabajo, grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente, asunto.codigo_asunto ASC ";
else
	$orderby = " ORDER BY grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente, asunto.codigo_asunto ASC ";

if($ver == 'prof')
	$group_by = " GROUP BY trabajo.id_usuario, cliente.codigo_cliente, asunto.codigo_asunto, grupo_cliente.id_grupo_cliente ";
else if( $ver == 'area_prof' )
        $group_by = " GROUP BY trabajo.id_area_trabajo, profesional";
else if( $ver == 'area_cliente' )
        $group_by = " GROUP BY trabajo.id_area_trabajo, grupo_cliente.glosa_grupo_cliente, cliente.glosa_cliente, asunto.codigo_asunto";
else
	$group_by = " GROUP BY trabajo.id_usuario, cliente.codigo_cliente, asunto.codigo_asunto, grupo_cliente.id_grupo_cliente ";

$total_hr = 0;
$col_resultado = "Hr.";

if($horas_sql == 'hr_cobrable')
	$where .= " AND trabajo.cobrable = 1 ";

if($horas_sql == 'hr_asunto_cobrable')
    $where .= " AND asunto.cobrable = 1 ";

if($horas_sql == 'hr_asunto_no_cobrable')
    $where .= " AND asunto.cobrable = 0 ";

if($horas_sql == 'hr_no_cobrables')
{
	$select = "SUM(TIME_TO_SEC(duracion)/3600 ) as hr_no_cobrables,";
	$where .= " AND  trabajo.cobrable = 0 ";
}

if($horas_sql == 'hr_castigadas')
{
	$select = "SUM(TIME_TO_SEC(trabajo.duracion)-TIME_TO_SEC(trabajo.duracion_cobrada))/3600 as hr_castigadas,";
	$where .= " AND trabajo.cobrable = 1 ";
}

if($horas_sql == 'hr_spot')
{
	$join = " LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato \n";
	$where .= " AND ( ( cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND ( cobro.forma_cobro IN ('TASA','CAP') )) OR ( (cobro.estado IS NULL OR cobro.estado IN ('CREADO','EN REVISION')) AND (contrato.forma_cobro IN ('TASA','CAP') OR contrato.forma_cobro IS NULL ) ) ) \n";
	$horas_sql = 'hr_cobrable' ;
}
if($horas_sql == 'hr_convenio')
{
	$join = " LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato \n";
	$where .= " AND ( ( cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND  ( cobro.forma_cobro IN ('FLAT FEE','RETAINER') )) OR ( (cobro.estado IS NULL OR cobro.estado IN ('CREADO','EN REVISION')) AND (contrato.forma_cobro IN ('FLAT FEE','RETAINER') ) ) ) \n";
	$horas_sql = 'hr_cobrable' ;
}
if($horas_sql == 'valor_cobrado')
{
	$select = "SUM(
						(tarifa_hh*TIME_TO_SEC( duracion_cobrada)/3600)
					*	(cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto))
					*	(cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base)
					/	IF(cobro_moneda.tipo_cambio IS NOT NULL, cobro_moneda.tipo_cambio, 20000)
					) as monto_cobrado_UF,";

	$join = " LEFT JOIN cobro_moneda on (cobro_moneda.id_cobro = cobro.id_cobro && cobro_moneda.id_moneda=3) \n";
	$where .= " AND cobro.estado IN ('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO', 'INCOBRABLE') AND trabajo.cobrable = 1 ";
	$horas_sql = 'monto_cobrado_UF' ;
	$col_resultado = "UF";
}

$query ="SELECT 
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
						$select
						SUM(TIME_TO_SEC(duracion)/60)/60 as hr_trabajadas,
						SUM(TIME_TO_SEC(if(trabajo.cobrable = 1,duracion_cobrada,0))/60)/60 as hr_cobrable,
						SUM(tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base*cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) as valor_cobrado,
						SUM(tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base*cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) / SUM(TIME_TO_SEC(duracion_cobrada - duracion)/60)/60 / SUM(TIME_TO_SEC(duracion)/60)/60  as valor_hr_promedio
					FROM trabajo
					LEFT JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
                                        LEFT JOIN prm_area_trabajo ON prm_area_trabajo.id_area_trabajo = trabajo.id_area_trabajo 
					LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
					LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
					LEFT JOIN cobro on trabajo.id_cobro = cobro.id_cobro
					$join
					WHERE $where
					$where_usuario
					$where_cliente
					$where_area
					$where_area_trabajo
					$where_categoria
					$group_by 
					$orderby";
// echo $query;
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
	$letra_profesional = 'profesional';
else
	$letra_profesional = 'username_profesional';
$contador=0;
if($opc=='grafico')
{
	########## VER GRAFICO ##########
	$titulo_reporte = __('Gráfico de').' '.__($horas_sql).' '.__('en vista por').' '.$desc;
	$datos_grafico = '';
	$total = 0;

	if($ver == 'prof')
	{
		while($row = mysql_fetch_array($resp))
		{
			if($row[$letra_profesional]!=$nombres[$contador])
			{
				$contador++;
				$nombres[$contador] = $row[$letra_profesional];
			}
			$tiempos[$contador] += $row[$horas_sql];
			$total += $row[$horas_sql];
		}
	}

	if($ver == 'cliente')
	{
		while($row = mysql_fetch_array($resp))
		{
			if($row['glosa_cliente']!=$nombres[$contador])
			{
				$contador++;
				$nombres[$contador] = $row['glosa_cliente'];
			}
			$tiempos[$contador] += $row[$horas_sql];
			$total += $row[$horas_sql];
		}
	}
        
        if($ver == 'area_prof' || $ver == 'area_cliente')
        {
                while($row = mysql_fetch_array($resp))
		{
			if($row['glosa_area_trabajo']!=$nombres[$contador])
			{
				$contador++;
				$nombres[$contador] = $row['glosa_area_trabajo'];
			}
			$tiempos[$contador] += $row[$horas_sql];
			$total += $row[$horas_sql];
		}
        }
        
	if($nombres){
		arsort($tiempos);
		$otros = 0;
		
		foreach($tiempos as $key => $tiempo){
			if($limite-- > 0) $datos_grafico .= "&nombres[]=".$nombres[$key]."&tiempo[]=".str_replace(',','.',$tiempos[$key]);
			else $otros += $tiempos[$key];
		}
		if($otros) $datos_grafico .= "&nombres[]=Otros&tiempo[]=".str_replace(',','.',$otros);
	}
	$html_info .= "<img src='graficos/grafico_resumen_actividades.php?titulo=".$titulo_reporte.$datos_grafico."' alt='' />";
	//echo 'graficos/grafico_resumen_actividades.php?titulo='.$titulo_reporte.$datos_grafico;
	echo $html_info;
}
else
{
	########## VER INFORME ##########
	$titulo_reporte = __('Reporte de').' '.__($horas_sql).' '.__('en vista por').' '.$desc;

	if($ver == 'prof')
	{
		$query_totales_usuarios = "SELECT CONCAT_WS(' ',usuario.nombre, usuario.apellido1) as profesional,
						cliente.codigo_cliente, $select
						SUM(TIME_TO_SEC(duracion)/60)/60 as hr_trabajadas,
						SUM(TIME_TO_SEC(if(trabajo.cobrable = 1,duracion_cobrada,0))/60)/60 as hr_cobrable,
						SUM(tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base*cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) as valor_cobrado
						FROM trabajo
					LEFT JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
					LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
					LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
					LEFT JOIN cobro on trabajo.id_cobro = cobro.id_cobro
                                        $join
					WHERE $where
					$where_usuario
					$where_cliente
					$where_area
					$where_categoria
					GROUP BY trabajo.id_usuario 
					$orderby";
		$resp_totales_usuarios = mysql_query($query_totales_usuarios,$sesion->dbh) or Utiles::errorSQL($query_totales_usuarios,__FILE__,__LINE__,$sesion->dbh);
		
		$valores_usuarios = array();
		while( $row = mysql_fetch_assoc($resp_totales_usuarios) )
		{
			$valores_usuarios[$row['profesional']] = array();
			foreach( $row as $index => $valor )
			{
				if( $valores_usuarios[$row['profesional']][$index] > 0 )
					$valores_usuarios[$row['profesional']][$index] += $valor;
				else
					$valores_usuarios[$row['profesional']][$index] = $valor;
			}
		}
		/*
			$tbl_inset -> tabla de grupo, cliente, asunto
			$tr_usr -> columna tabla usuario
		*/
		$tbl_inset = "<table id='tbl_inset' border='0' cellpadding='0' cellspacing='0' width='100%'>
									 <tr>
										<td class='alt' style='width:80px; border-right: 1px solid #CCCCCC;'>
											".__('Grupo')."
										</td>
										<td class='alt' style='width:50px; border-right: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class='alt' style='width:150px; border-right: 1px solid #CCCCCC;'>
											".__('Cliente')."
										</td>
										<td class='alt' style='width:50px; border-right: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class='alt' style='width:150px; border-right: 1px solid #CCCCCC;'>
											".__('Asuntos')."
										</td>
										<td class='alt' style='width:50px; border-left: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class='alt' style='width:50px; border-left: 1px solid #CCCCCC;'>
											".__('%')."
										</td>
									</tr>
								</table>";
		$tr_usr = "<tr>
								<td class='alt' style='width:150px'>
									".__('Nombre')."
								</td>
								<td class='alt' id='td_horas' style='width:50px'>
									".__($col_resultado)."
								</td>
								<td class='alt' style='width:530px; border-top: 1px solid #000000;'>
									".$tbl_inset."
								</td>
							</tr>";

		#onMouseover=\"ddrivetip('".__('Haga clic botón derecho para cambiar la información mostrada')."');\" onMouseout=\"hideddrivetip()\" style='cursor:pointer'
		while($row = mysql_fetch_array($resp))
		{
				if($row['id_usuario'] != $id_usuario_actual || $mas_de_un_usuario == '')
				{
					if($mas_de_un_usuario == '')
					{
						$mas_de_un_usuario = 1;

						$id_usuario_actual = $row['id_usuario'];
						$profesional_actual = $row[$letra_profesional];

						$id_grupo_cliente_actual = $row['id_grupo_cliente'];
						$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';

						$id_cliente_actual = $row['id_cliente'];
						$glosa_cliente_actual = $row['glosa_cliente'];
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
							
						$cont_usuario =1;
					}
					else
					{
						$tr_cliente = "
												<tr>
													<td class=cliente_nombre style='width:150px'><a href='javascript:void(0)' onclick=\"DetalleCliente(this.form,'".$codigo_cliente_actual."','".$id_usuario_actual."')\">".$glosa_cliente_actual."</a></td>
													<td class=cliente_hr style='width:50px'><a href='javascript:void(0)' onclick=\"	window.self.location.href= 'horas.php?id_usuario=$id_usuario_actual&codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_cliente."</a></td>
													<td class=cliente_nombre style='width:200px'><table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
												</tr>";

						$glosa_cliente_actual = $row['glosa_cliente'];
						$tr_clientes .= $tr_cliente;
						$tr_asuntos = '';
						$dato_cliente = 0;
						$dato_cliente2 = 0;
						$id_cliente_actual = $row['id_cliente'];
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
						$hr = '';
						if($cont_usuario > 1)
							$hr = "<tr><td class=cliente_nombre colspan=3><hr size=1></td></tr>";

						$tr_grupo = "
												<tr>
													<td class=grupo_nombre style='width:80px'>".$glosa_grupo_cliente_actual."</td>
													<td class=grupo_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=$id_usuario_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_grupo=".($id_grupo_cliente_actual ? $id_grupo_cliente_actual : 'NULL')."&clientes=".base64_encode($lista_clientes)."';\">".$glosa_show_grupo."</a></td>
													<td class=grupo_nombre style='width:400px'><table id='tbl_cliente' width='100%'>".$tr_clientes."</table></td>
												</tr>".$hr."";

						$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';
						$tr_clientes = '';
						$dato_grupo = 0;
						$dato_grupo2 = 0;
						$tr_grupos .=$tr_grupo;
						$id_grupo_cliente_actual = $row['id_grupo_cliente'];

						$tr_usuario = $tr_usr."
																<tr>
																	<td class=usuario_nombre style='width:150px'>".$profesional_actual."</td>
																	<td class=usuario_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=$id_usuario_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&clientes=".base64_encode($lista_clientes)."';\">".$glosa_show_usuario."</a></td>
																	<td style='width:530px' class=usuario_hr><table width='100%' id='tbl_grupo'>".$tr_grupos."</table></td>
																</tr>";

						$profesional_actual = $row[$letra_profesional];
						$tr_grupos = '';
						$dato_usuario = 0;
						$dato_usuario2 = 0;
						$tr_usuarios .=$tr_usuario;
						$id_usuario_actual = $row['id_usuario'];
						$cont_usuario =1;
					}
				}
				else if($row['id_grupo_cliente'] != $id_grupo_cliente_actual)
				{
					$tr_cliente = "
												<tr>
													<td class='cliente_nombre' style='width:150px'><a href='javascript:void(0)' onclick=\"DetalleCliente(this.form,'".$codigo_cliente_actual."','".$id_usuario_actual."')\">".$glosa_cliente_actual."</a></td>
													<td class='cliente_hr' style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=$id_usuario_actual&codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_cliente."</a></td>
													<td class='cliente_nombre' style='width:200px'>
														<table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
												</tr>";
					$glosa_cliente_actual = $row['glosa_cliente'];
					$tr_clientes .= $tr_cliente;
					$tr_asuntos = '';
					$dato_cliente = 0;
					$dato_cliente2 = 0;
					$id_cliente_actual = $row['id_cliente'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];

					$tr_grupo = "
											<tr>
												<td class='grupo_nombre' style='width:80px'>".$glosa_grupo_cliente_actual."</td>
												<td class='grupo_hr' style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=$id_usuario_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_grupo=".($id_grupo_cliente_actual ? $id_grupo_cliente_actual : 'NULL')."&clientes=".base64_encode($lista_clientes)."';\">".$glosa_show_grupo."</a></td>
												<td class='grupo_nombre' style='width:400px'><table id='tbl_cliente' width='100%'>".$tr_clientes."</table>
												</td>
											</tr>
											<tr>
												<td colspan=3 class=cliente_nombre><hr size=1></td>
											</tr>";
					$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';
					$tr_clientes = '';
					$dato_grupo = 0;
					$dato_grupo2 = 0;
					$tr_grupos .=$tr_grupo;
					$id_grupo_cliente_actual = $row['id_grupo_cliente'];
				}
				else if($row['id_cliente'] != $id_cliente_actual)
				{
					$tr_cliente = "
											<tr>
												<td class='cliente_nombre' style='width:150px'><a href='javascript:void(0)' onclick=\"DetalleCliente(this.form,'".$codigo_cliente_actual."','".$id_usuario_actual."')\">".$glosa_cliente_actual."</a></td>
												<td class='cliente_hr' style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href='horas.php?id_usuario=$id_usuario_actual&codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_cliente."</a></td>
												<td class='cliente_nombre' style='width:200px'><table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
											</tr>";
					$glosa_cliente_actual = $row['glosa_cliente'];
					$tr_clientes .= $tr_cliente;
					$tr_asuntos = '';
					$dato_cliente = 0;
					$dato_cliente2 = 0;
					$id_cliente_actual = $row['id_cliente'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
				}

				if($horas_sql == 'horas_trabajadas_cobrables')
				{
					$dato = $row['hr_trabajadas'];
					$dato2 = $row['hr_cobrable'];
					if( $valores_usuarios[$row['profesional']]['hr_trabajadas'] > 0 )
						$porcentaje1 = number_format( 100 * $dato / $valores_usuarios[$row['profesional']]['hr_trabajadas'], 2, ',', ' ');
					else	
						$porcentaje1 = "0,00";
					if( $valores_usuarios[$row['profesional']]['hr_cobrable'] > 0 )
						$porcentaje2 = number_format( 100 * $dato2 / $valores_usuarios[$row['profesional']]['hr_cobrable'], 2, ',', ' ');
					else
						$porcentaje2 = "0,00";
					$porcentaje = $porcentaje1.'<br>'.$porcentaje2;
					$minutos_trabajados = number_format(($row['hr_trabajadas']-floor($row['hr_trabajadas']))*60,2);
					$minutos_cobrados = number_format(($row['hr_cobrable']-floor($row['hr_cobrable']))*60,2);
					$dato_minutos = floor($row['hr_trabajadas']).':'.sprintf('%02d',round($minutos_trabajados));
					$dato_minutos2 = floor($row['hr_cobrable']).':'.sprintf('%02d',round($minutos_cobrados));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() ) )
						$glosa_show = Utiles::Formato($dato_minutos,'%s').'<br>'.Utiles::Formato($dato_minutos2,'%s');
					else
						$glosa_show = Utiles::Formato($dato,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato2,'%f', $idioma, 2);
				}
				else if( $horas_sql == 'hr_asunto_cobrable' or $horas_sql == 'hr_asunto_no_cobrable')
				{
					$dato = $row['hr_trabajadas'];
					if( $valores_usuarios[$row['profesional']]['hr_trabajadas'] > 0 )
						$porcentaje = number_format( 100* $dato / $valores_usuarios[$row['profesional']]['hr_trabajadas'], 2, ',', ' ');
					else
						$porcentaje = "0,00";
					$minutos_trabajados = number_format(($row['hr_trabajadas']-floor($row['hr_trabajadas']))*60,2);
					$dato_minutos = floor($row['hr_trabajadas']).':'.sprintf('%02d',round($minutos_trabajados));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
						$glosa_show = Utiles::Formato($dato_minutos,'%s');
					else
						$glosa_show = Utiles::Formato($dato,'%f', $idioma, 2);
				}
				else
				{
					$dato = $row[$horas_sql];
					if( $valores_usuarios[$row['profesional']][$horas_sql] > 0 )
						$porcentaje = number_format(100 * $row[$horas_sql] / $valores_usuarios[$row['profesional']][$horas_sql],2,',',''); // $total_cliente_actual[$row['glosa_cliente']];
					else	
						$porcentaje = "0,00";
					$minutos_trabajados = number_format(($row[$horas_sql]-floor($row[$horas_sql]))*60,2);
					$dato_minutos = floor($row[$horas_sql]).':'.sprintf('%02d',round($minutos_trabajados));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() ) )
						$glosa_show = Utiles::Formato($dato_minutos,'%s');
					else
						$glosa_show = Utiles::Formato($dato,'%f', $idioma, 2);
				}


				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) ) {
				$tr_asunto = "
										<tr>
											<td class=asunto_nombre style='width:150px'>".$row['glosa_asunto']."</td>
											<td class=asunto_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&codigo_asunto=$row[codigo_asunto_secundario]&from=asunto&id_usuario=$id_usuario_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin';\">".$glosa_show."</a></td>
											<td class=asunto_hr style='width:50px'>".$porcentaje."</td>
										</tr>";
				}
				else {
				$tr_asunto = "
									<tr>
										<td class=asunto_nombre style='width:150px'>".$row['glosa_asunto']."</td>
										<td class=asunto_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&codigo_asunto=$row[codigo_asunto]&from=asunto&id_usuario=$id_usuario_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin';\">".$glosa_show."</a></td>
										<td class=asunto_hr style='width:50px'>".$porcentaje."</td>
									</tr>";
				}
				$tr_asuntos .=$tr_asunto;

				$dato_cliente +=$dato;
				$dato_grupo += $dato;
				$dato_usuario += $dato;
				$minutos_trabajados_cliente = number_format(($dato_cliente-floor($dato_cliente))*60,2);
				$dato_minutos_cliente = floor($dato_cliente).':'.sprintf('%02d',round($minutos_trabajados_cliente));
				$minutos_trabajados_grupo = number_format(($dato_grupo-floor($dato_grupo))*60,2);
				$dato_minutos_grupo = floor($dato_grupo).':'.sprintf('%02d',round($minutos_trabajados_grupo));
				$minutos_trabajados_usuario = number_format(($dato_usuario-floor($dato_usuario))*60,2);
				$dato_minutos_usuario = floor($dato_usuario).':'.sprintf('%02d',round($minutos_trabajados_usuario));
				if($horas_sql == 'horas_trabajadas_cobrables')
				{
					$dato_cliente2 +=$dato2;
					$dato_grupo2 += $dato2;
					$dato_usuario2 += $dato2;
					$minutos_trabajados_cliente2 = number_format(($dato_cliente2-floor($dato_cliente2))*60,2);
					$dato_minutos_cliente2 = floor($dato_cliente2).':'.sprintf('%02d',round($minutos_trabajados_cliente2));
					$minutos_trabajados_grupo2 = number_format(($dato_grupo2-floor($dato_grupo2))*60,2);
					$dato_minutos_grupo2 = floor($dato_grupo2).':'.sprintf('%02d',round($minutos_trabajados_grupo2));
					$minutos_trabajados_usuario2 = number_format(($dato_usuario2-floor($dato_usuario2))*60,2);
					$dato_minutos_usuario2 = floor($dato_usuario2).':'.sprintf('%02d',round($minutos_trabajados_usuario2));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
					{
						$glosa_show_cliente = Utiles::Formato($dato_minutos_cliente,'%s').'<br>'.Utiles::Formato($dato_minutos_cliente2,'%s');
						$glosa_show_grupo = Utiles::Formato($dato_minutos_grupo,'%s').'<br>'.Utiles::Formato($dato_minutos_grupo2,'%s');
						$glosa_show_usuario = Utiles::Formato($dato_minutos_usuario,'%s').'<br>'.Utiles::Formato($dato_minutos_usuario2,'%s');
					}
					else
					{
						$glosa_show_cliente = Utiles::Formato($dato_cliente,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato_cliente2,'%f', $idioma, 2);
						$glosa_show_grupo = Utiles::Formato($dato_grupo,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato_grupo2,'%f', $idioma, 2);
						$glosa_show_usuario = Utiles::Formato($dato_usuario,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato_usuario2,'%f', $idioma, 2);
					}
				}
				else
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
					{
						$glosa_show_cliente = Utiles::Formato($dato_minutos_cliente,'%s');
						$glosa_show_grupo = Utiles::Formato($dato_minutos_grupo,'%s');
						$glosa_show_usuario = Utiles::Formato($dato_minutos_usuario,'%s');
					}
					else
					{
						$glosa_show_cliente = Utiles::Formato($dato_cliente,'%f', $idioma, 2);
						$glosa_show_grupo = Utiles::Formato($dato_grupo,'%f', $idioma, 2);
						$glosa_show_usuario = Utiles::Formato($dato_usuario,'%f', $idioma, 2);
					}
				}
				$total_hr += $dato;
		}
			#Ultimo dato en caso de no cambiar cliente en el ultimo dato
			$tr_cliente = "
									<tr>
										<td class=cliente_nombre style='width:150px' valign=top><a href='javascript:void(0)' onclick=\"DetalleCliente(this.form,'".$codigo_cliente_actual."','".$id_usuario_actual."')\">".$glosa_cliente_actual."</a></td>
										<td class=cliente_hr style='width:50px;' valign=top><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=$id_usuario_actual&codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_cliente."</a></td>
										<td class=cliente_nombre style='width:200px'>
											<table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
									</tr>";
			$glosa_cliente_actual = $row['glosa_cliente'];
			$tr_clientes .= $tr_cliente;
			$tr_asuntos = '';
			$dato_cliente = 0;
			$dato_cliente2 = 0;
			$id_cliente_actual = $row['id_cliente'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];

			$tr_grupo = "
								<tr>
									<td class=grupo_nombre style='width:80px' valign=top>".$glosa_grupo_cliente_actual."</td>
									<td class=grupo_hr style='width:50px' valign=top><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=$id_usuario_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_grupo=".($id_grupo_cliente_actual ? $id_grupo_cliente_actual : 'NULL')."&clientes=".base64_encode($lista_clientes)."';\">".$glosa_show_grupo."</a></td>
									<td class=grupo_nombre style='width:400px'>
										<table id='tbl_cliente' width='100%'>".$tr_clientes."</table></td>
								</tr>
								<tr>
									<td colspan=3 class=grupo_nombre><hr size=1></td>
								</tr>";
			$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';
			$tr_clientes = '';
			$dato_grupo = 0;
			$dato_grupo2 = 0;
			$tr_grupos .=$tr_grupo;
			$id_grupo_cliente_actual = $row['id_grupo_cliente'];

			$tr_usuario = $tr_usr."
									<tr>
										<td class=usuario_nombre style='width:150px' valign=top>".$profesional_actual."</td>
										<td class=usuario_hr style='width:50px' valign=top><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=$id_usuario_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&clientes=".base64_encode($lista_clientes)."';\">".$glosa_show_usuario."</a></td>
										<td style='width:530px' class=usuario_nombre>
											<table width='100%' id='tbl_grupo'>".$tr_grupos."</table></td>
									</tr>";
			$profesional_actual = $row[$letra_profesional];
			$tr_grupos = '';
			$dato_usuario = 0;
			$dato_usuario2 = 0;
			$tr_usuarios .=$tr_usuario;
			$id_usuario_actual = $row['id_usuario'];

			$html_info .= "<table width=90%>";
			if($popup)
			{
				$html_info .= "<tr><td align=left><h3><b>".__('Resumen actividades profesionales')."</b></h3></td></tr>";
			}
			$html_info .= "<tr><td>";
			$html_info .= "<table width=100%><tr><td style='font-size:13px; font-weight:bold' align=left>".$titulo_reporte."</td><td width=25% style='font-size:12px; font-weight:bold' align=right>".__('Total').":&nbsp;";
			$minutos_total_hr = number_format(($total_hr-floor($total_hr))*60,2);
			$total_hr_minutos = floor($total_hr).':'.sprintf('%02d',round($minutos_total_hr));
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
				$html_info .= Utiles::Formato($total_hr_minutos,'%s');
			else
				$html_info .= Utiles::Formato($total_hr,'%f');
			$html_info .= "</td></tr>";
			$html_info .= "<tr><td colspan=2 align=left><u>".__('Periodo consultado').": ".$periodo_txt."</u></td></table>";

			$html_info .= "</td></tr><tr><td>&nbsp;</td></tr>";
			$html_info .= "<tr><td>";
			$html_info .= "<table class='tbl_general' border=1 width='100%' cellpadding='0' cellspacing='0'>";
			$html_info .= $tr_usuarios;
			$html_info .= "</table>";
			$html_info .= "</td></tr></table>";

			echo $html_info;
	}
        else if($ver == 'area_cliente') {
            $query_totales_usuarios = " SELECT CONCAT_WS(' ',usuario.nombre, usuario.apellido1) as profesional,
                                                cliente.codigo_cliente, $select
						SUM(TIME_TO_SEC(duracion)/60)/60 as hr_trabajadas,
                                                IF(trabajo.id_area_trabajo IS NOT NULL, prm_area_trabajo.glosa, 'Indefinido') as glosa_area_trabajo, 
                                                trabajo.id_area_trabajo, 
						SUM(TIME_TO_SEC(if(trabajo.cobrable = 1,duracion_cobrada,0))/60)/60 as hr_cobrable,
						SUM(tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base*cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) as valor_cobrado
						FROM trabajo
					LEFT JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
                                        LEFT JOIN prm_area_trabajo ON prm_area_trabajo.id_area_trabajo = trabajo.id_area_trabajo 
					LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
					LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
					LEFT JOIN cobro on trabajo.id_cobro = cobro.id_cobro
                                        $join
					WHERE $where
					$where_usuario
					$where_cliente
					$where_area
					$where_categoria
					GROUP BY glosa_area_trabajo 
					$orderby";
		$resp_totales_usuarios = mysql_query($query_totales_usuarios,$sesion->dbh) or Utiles::errorSQL($query_totales_usuarios,__FILE__,__LINE__,$sesion->dbh);
		
		$valores_areas = array();
		while( $row = mysql_fetch_assoc($resp_totales_usuarios) )
		{
			$valores_areas[$row['glosa_area_trabajo']] = array();
			foreach( $row as $index => $valor )
			{
				if( $valores_areas[$row['glosa_area_trabajo']][$index] > 0 && !in_array($index,array('profesional','codigo_cliente','glosa_area_trabajo','id_area_trabajo')))
					$valores_areas[$row['glosa_area_trabajo']][$index] += $valor;
				else
					$valores_areas[$row['glosa_area_trabajo']][$index] = $valor;
			}
		}
                
                $valor_total = 0;
                foreach($valores_areas as $area => $valor) {
                    $valor_total += $valores_areas[$area][$horas_sql];
                }
                foreach($valores_areas as $area => $valor) {
                    $valores_areas[$area]['porcentaje_area'] = number_format( 100 * $valores_areas[$area][$horas_sql] / $valor_total, 2,',','');
                }
		/*
			$tbl_inset -> tabla de grupo, cliente, asunto
			$tr_usr -> columna tabla usuario
		*/
		$tbl_inset = "<table id='tbl_inset' border='0' cellpadding='0' cellspacing='0' width='100%'>
									 <tr>
										<td class='alt' style='width:80px; border-right: 1px solid #CCCCCC;'>
											".__('Grupo')."
										</td>
										<td class='alt' style='width:50px; border-right: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class='alt' style='width:150px; border-right: 1px solid #CCCCCC;'>
											".__('Cliente')."
										</td>
										<td class='alt' style='width:50px; border-right: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class='alt' style='width:150px; border-right: 1px solid #CCCCCC;'>
											".__('Asuntos')."
										</td>
										<td class='alt' style='width:50px; border-left: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class='alt' style='width:50px; border-left: 1px solid #CCCCCC;'>
											".__('%')."
										</td>
									</tr>
								</table>";
		$tr_area = "<tr>
								<td class='alt' style='width:150px'>
									".__('Área Trabajo')."
								</td>
								<td class='alt' id='td_horas' style='width:50px'>
									".__($col_resultado)."
								</td>
                                                                <td class='alt' id='td_porcentaje_area' style='width:50px'>
                                                                        ".__('%')."
                                                                </td>
								<td class='alt' style='width:530px; border-top: 1px solid #000000;'>
									".$tbl_inset."
								</td>
							</tr>";

		#onMouseover=\"ddrivetip('".__('Haga clic botón derecho para cambiar la información mostrada')."');\" onMouseout=\"hideddrivetip()\" style='cursor:pointer'
		while($row = mysql_fetch_array($resp))
		{
				if($row['id_area_trabajo'] != $id_area_trabajo || $mas_de_una_area == '')
				{
					if($mas_de_una_area == '')
					{
						$mas_de_una_area = 1;

						$id_area_actual = $row['id_area_actual'];
						$area_actual = $row['glosa_area_trabajo'];

						$id_grupo_cliente_actual = $row['id_grupo_cliente'];
						$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';

						$id_cliente_actual = $row['id_cliente'];
						$glosa_cliente_actual = $row['glosa_cliente'];
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
							
						$cont_area =1;
					}
					else
					{
						$tr_cliente = "
												<tr>
													<td class=cliente_nombre style='width:150px'><a href='javascript:void(0)' onclick=\"DetalleCliente(this.form,'".$codigo_cliente_actual."','".$id_usuario_actual."')\">".$glosa_cliente_actual."</a></td>
													<td class=cliente_hr style='width:50px'><a href='javascript:void(0)' onclick=\"	window.self.location.href= 'horas.php?id_area_trabajo=$id_area_trabajo_actual&codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_cliente."</a></td>
													<td class=cliente_nombre style='width:200px'><table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
												</tr>";

						$glosa_cliente_actual = $row['glosa_cliente'];
						$tr_clientes .= $tr_cliente;
						$tr_asuntos = '';
						$dato_cliente = 0;
						$dato_cliente2 = 0;
						$id_cliente_actual = $row['id_cliente'];
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
						$hr = '';
						if($cont_usuario > 1)
							$hr = "<tr><td class=cliente_nombre colspan=3><hr size=1></td></tr>";

						$tr_grupo = "
												<tr>
													<td class=grupo_nombre style='width:80px'>".$glosa_grupo_cliente_actual."</td>
													<td class=grupo_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_area_trabajo=$id_area_trabajo_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_grupo=".($id_grupo_cliente_actual ? $id_grupo_cliente_actual : 'NULL')."';\">".$glosa_show_grupo."</a></td>
													<td class=grupo_nombre style='width:400px'><table id='tbl_cliente' width='100%'>".$tr_clientes."</table></td>
												</tr>".$hr."";

						$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';
						$tr_clientes = '';
						$dato_grupo = 0;
						$dato_grupo2 = 0;
						$tr_grupos .=$tr_grupo;
						$id_grupo_cliente_actual = $row['id_grupo_cliente'];

						$tr_area_trabajo = $tr_area."
																<tr>
																	<td class=usuario_nombre style='width:150px'>".$area_actual."</td>
																	<td class=usuario_hr style='width:50px'><a href='javascript:void(0)'>".$glosa_show_area."</a></td>
                                                                                                                                        <td class=usuario_hr style='width:50px'><a href='javascript:void(0)'>".$glosa_porcentaje_area."</a></td>
																	<td style='width:530px' class=usuario_hr><table width='100%' id='tbl_grupo'>".$tr_grupos."</table></td>
																</tr>";

						$area_actual = $row['glosa_area_trabajo'];
						$tr_grupos = '';
						$dato_area = 0;
						$dato_area2 = 0;
						$tr_areas_trabajos .=$tr_area_trabajo;
						$id_area_actual = $row['id_area_trabajo'];
						$cont_area =1;
					}
				}
				else if($row['id_grupo_cliente'] != $id_grupo_cliente_actual)
				{
					$tr_cliente = "
												<tr>
													<td class='cliente_nombre' style='width:150px'><a href='javascript:void(0)' onclick=\"DetalleCliente(this.form,'".$codigo_cliente_actual."','".$id_usuario_actual."')\">".$glosa_cliente_actual."</a></td>
													<td class='cliente_hr' style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_area_trabajo=$id_area_trabajo_actual&codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_cliente."</a></td>
													<td class='cliente_nombre' style='width:200px'>
														<table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
												</tr>";
					$glosa_cliente_actual = $row['glosa_cliente'];
					$tr_clientes .= $tr_cliente;
					$tr_asuntos = '';
					$dato_cliente = 0;
					$dato_cliente2 = 0;
					$id_cliente_actual = $row['id_cliente'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];

					$tr_grupo = "
											<tr>
												<td class='grupo_nombre' style='width:80px'>".$glosa_grupo_cliente_actual."</td>
												<td class='grupo_hr' style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_area_trabajo=$id_area_trabajo_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_grupo=".($id_grupo_cliente_actual ? $id_grupo_cliente_actual : 'NULL')."&clientes=".base64_encode($lista_clientes)."';\">".$glosa_show_grupo."</a></td>
												<td class='grupo_nombre' style='width:400px'><table id='tbl_cliente' width='100%'>".$tr_clientes."</table>
												</td>
											</tr>
											<tr>
												<td colspan=3 class=cliente_nombre><hr size=1></td>
											</tr>";
					$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';
					$tr_clientes = '';
					$dato_grupo = 0;
					$dato_grupo2 = 0;
					$tr_grupos .=$tr_grupo;
					$id_grupo_cliente_actual = $row['id_grupo_cliente'];
				}
				else if($row['id_cliente'] != $id_cliente_actual)
				{
					$tr_cliente = "
											<tr>
												<td class='cliente_nombre' style='width:150px'><a href='javascript:void(0)' onclick=\"DetalleCliente(this.form,'".$codigo_cliente_actual."','".$id_usuario_actual."')\">".$glosa_cliente_actual."</a></td>
												<td class='cliente_hr' style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href='horas.php?id_area_trabajo=$id_area_trabajo_actual&codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_cliente."</a></td>
												<td class='cliente_nombre' style='width:200px'><table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
											</tr>";
					$glosa_cliente_actual = $row['glosa_cliente'];
					$tr_clientes .= $tr_cliente;
					$tr_asuntos = '';
					$dato_cliente = 0;
					$dato_cliente2 = 0;
					$id_cliente_actual = $row['id_cliente'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
				}

				if($horas_sql == 'horas_trabajadas_cobrables')
				{
					$dato = $row['hr_trabajadas'];
					$dato2 = $row['hr_cobrable'];
					if( $valores_areas[$row['glosa_area_trabajo']]['hr_trabajadas'] > 0 )
						$porcentaje1 = number_format( 100 * $dato / $valores_areas[$row['glosa_area_trabajo']]['hr_trabajadas'], 2, ',', ' ');
					else	
						$porcentaje1 = "0,00";
					if( $valores_areas[$row['glosa_area_trabajo']]['hr_cobrable'] > 0 )
						$porcentaje2 = number_format( 100 * $dato2 / $valores_areas[$row['glosa_area_trabajo']]['hr_cobrable'], 2, ',', ' ');
					else
						$porcentaje2 = "0,00";
					$porcentaje = $porcentaje1.'<br>'.$porcentaje2;
					$minutos_trabajados = number_format(($row['hr_trabajadas']-floor($row['hr_trabajadas']))*60,2);
					$minutos_cobrados = number_format(($row['hr_cobrable']-floor($row['hr_cobrable']))*60,2);
					$dato_minutos = floor($row['hr_trabajadas']).':'.sprintf('%02d',round($minutos_trabajados));
					$dato_minutos2 = floor($row['hr_cobrable']).':'.sprintf('%02d',round($minutos_cobrados));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() ) )
						$glosa_show = Utiles::Formato($dato_minutos,'%s').'<br>'.Utiles::Formato($dato_minutos2,'%s');
					else
						$glosa_show = Utiles::Formato($dato,'%f').'<br>'.Utiles::Formato($dato2,'%f');
				}
				else if( $horas_sql == 'hr_asunto_cobrable' or $horas_sql == 'hr_asunto_no_cobrable')
				{
					$dato = $row['hr_trabajadas'];
					if( $valores_areas[$row['glosa_area_trabajo']]['hr_trabajadas'] > 0 )
						$porcentaje = number_format( 100* $dato / $valores_areas[$row['glosa_area_trabajo']]['hr_trabajadas'], 2, ',', ' ');
					else
						$porcentaje = "0,00";
					$minutos_trabajados = number_format(($row['hr_trabajadas']-floor($row['hr_trabajadas']))*60,2);
					$dato_minutos = floor($row['hr_trabajadas']).':'.sprintf('%02d',round($minutos_trabajados));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
						$glosa_show = Utiles::Formato($dato_minutos,'%s');
					else
						$glosa_show = Utiles::Formato($dato,'%f');
				}
				else
				{
					$dato = $row[$horas_sql];
					if( $valores_areas[$row['glosa_area_trabajo']][$horas_sql] > 0 )
						$porcentaje = number_format(100 * $row[$horas_sql] / $valores_areas[$row['glosa_area_trabajo']][$horas_sql],2,',',''); // $total_cliente_actual[$row['glosa_cliente']];
					else	
						$porcentaje = "0,00";
					$minutos_trabajados = number_format(($row[$horas_sql]-floor($row[$horas_sql]))*60,2);
					$dato_minutos = floor($row[$horas_sql]).':'.sprintf('%02d',round($minutos_trabajados));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() ) )
						$glosa_show = Utiles::Formato($dato_minutos,'%s');
					else
						$glosa_show = Utiles::Formato($dato,'%f');
				}


				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) ) {
				$tr_asunto = "
										<tr>
											<td class=asunto_nombre style='width:150px'>".$row['glosa_asunto']."</td>
											<td class=asunto_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&codigo_asunto=$row[codigo_asunto_secundario]&from=asunto&id_area_trabajo=$id_area_trabajo_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin';\">".$glosa_show."</a></td>
											<td class=asunto_hr style='width:50px'>".$porcentaje."</td>
										</tr>";
				}
				else {
				$tr_asunto = "
									<tr>
										<td class=asunto_nombre style='width:150px'>".$row['glosa_asunto']."</td>
										<td class=asunto_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&codigo_asunto=$row[codigo_asunto]&from=asunto&id_area_trabajo=$id_area_trabajo_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin';\">".$glosa_show."</a></td>
										<td class=asunto_hr style='width:50px'>".$porcentaje."</td>
									</tr>";
				}
				$tr_asuntos .=$tr_asunto;

				$dato_cliente +=$dato;
				$dato_grupo += $dato;
				$dato_area += $dato;
				$minutos_trabajados_cliente = number_format(($dato_cliente-floor($dato_cliente))*60,2);
				$dato_minutos_cliente = floor($dato_cliente).':'.sprintf('%02d',round($minutos_trabajados_cliente));
				$minutos_trabajados_grupo = number_format(($dato_grupo-floor($dato_grupo))*60,2);
				$dato_minutos_grupo = floor($dato_grupo).':'.sprintf('%02d',round($minutos_trabajados_grupo));
				$minutos_trabajados_area = number_format(($dato_area-floor($dato_area))*60,2);
				$dato_minutos_area = floor($dato_area).':'.sprintf('%02d',round($minutos_trabajados_area));
				$glosa_porcentaje_area = $valores_areas[$row['glosa_area_trabajo']]['porcentaje_area'];
                                if($horas_sql == 'horas_trabajadas_cobrables')
				{
					$dato_cliente2 +=$dato2;
					$dato_grupo2 += $dato2;
					$dato_area2 += $dato2;
					$minutos_trabajados_cliente2 = number_format(($dato_cliente2-floor($dato_cliente2))*60,2);
					$dato_minutos_cliente2 = floor($dato_cliente2).':'.sprintf('%02d',round($minutos_trabajados_cliente2));
					$minutos_trabajados_grupo2 = number_format(($dato_grupo2-floor($dato_grupo2))*60,2);
					$dato_minutos_grupo2 = floor($dato_grupo2).':'.sprintf('%02d',round($minutos_trabajados_grupo2));
					$minutos_trabajados_area2 = number_format(($dato_area2-floor($dato_area2))*60,2);
					$dato_minutos_area2 = floor($dato_area2).':'.sprintf('%02d',round($minutos_trabajados_area2));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
					{
						$glosa_show_cliente = Utiles::Formato($dato_minutos_cliente,'%s').'<br>'.Utiles::Formato($dato_minutos_cliente2,'%s');
						$glosa_show_grupo = Utiles::Formato($dato_minutos_grupo,'%s').'<br>'.Utiles::Formato($dato_minutos_grupo2,'%s');
						$glosa_show_area = Utiles::Formato($dato_minutos_area,'%s').'<br>'.Utiles::Formato($dato_minutos_area2,'%s');
					}
					else
					{
						$glosa_show_cliente = Utiles::Formato($dato_cliente,'%f').'<br>'.Utiles::Formato($dato_cliente2,'%f');
						$glosa_show_grupo = Utiles::Formato($dato_grupo,'%f').'<br>'.Utiles::Formato($dato_grupo2,'%f');
						$glosa_show_area = Utiles::Formato($dato_area,'%f').'<br>'.Utiles::Formato($dato_area2,'%f');
					}
				}
				else
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
					{
						$glosa_show_cliente = Utiles::Formato($dato_minutos_cliente,'%s');
						$glosa_show_grupo = Utiles::Formato($dato_minutos_grupo,'%s');
						$glosa_show_area = Utiles::Formato($dato_minutos_area,'%s');
					}
					else
					{
						$glosa_show_cliente = Utiles::Formato($dato_cliente,'%f');
						$glosa_show_grupo = Utiles::Formato($dato_grupo,'%f');
						$glosa_show_area = Utiles::Formato($dato_area,'%f');
					}
				}
				$total_hr += $dato;
		}
			#Ultimo dato en caso de no cambiar cliente en el ultimo dato
			$tr_cliente = "
									<tr>
										<td class=cliente_nombre style='width:150px' valign=top><a href='javascript:void(0)' onclick=\"DetalleCliente(this.form,'".$codigo_cliente_actual."','".$id_usuario_actual."')\">".$glosa_cliente_actual."</a></td>
										<td class=cliente_hr style='width:50px;' valign=top><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_area_trabajo=$id_area_trabajo&codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_cliente."</a></td>
										<td class=cliente_nombre style='width:200px'>
											<table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
									</tr>";
			$glosa_cliente_actual = $row['glosa_cliente'];
			$tr_clientes .= $tr_cliente;
			$tr_asuntos = '';
			$dato_cliente = 0;
			$dato_cliente2 = 0;
			$id_cliente_actual = $row['id_cliente'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];

			$tr_grupo = "
								<tr>
									<td class=grupo_nombre style='width:80px' valign=top>".$glosa_grupo_cliente_actual."</td>
									<td class=grupo_hr style='width:50px' valign=top><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_area_trabajo=$id_area_trabajo_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_grupo=".($id_grupo_cliente_actual ? $id_grupo_cliente_actual : 'NULL')."';\">".$glosa_show_grupo."</a></td>
									<td class=grupo_nombre style='width:400px'>
										<table id='tbl_cliente' width='100%'>".$tr_clientes."</table></td>
								</tr>
								<tr>
									<td colspan=3 class=grupo_nombre><hr size=1></td>
								</tr>";
			$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';
			$tr_clientes = '';
			$dato_grupo = 0;
			$dato_grupo2 = 0;
			$tr_grupos .=$tr_grupo;
			$id_grupo_cliente_actual = $row['id_grupo_cliente'];

			$tr_area_trabajo = $tr_area."
									<tr>
										<td class=usuario_nombre style='width:150px' valign=top>".$area_actual."</td>
										<td class=usuario_hr style='width:50px' valign=top><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_area_trabajo=$id_area_trabajo_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_show_area."</a></td>
										<td class=usuario_hr style='width:50px' valign=top><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_area_trabajo=$id_area_trabajo_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente';\">".$glosa_porcentaje_area."</a></td>
										<td style='width:530px' class=usuario_nombre>
											<table width='100%' id='tbl_grupo'>".$tr_grupos."</table></td>
									</tr>";
			$area_actual = $row['glosa_area_trabajo'];
			$tr_grupos = '';
			$dato_area = 0;
			$dato_area2 = 0;
			$tr_areas_trabajos .= $tr_area_trabajo;
			$id_area_actual = $row['id_area_trabajo'];

			$html_info .= "<table width=90%>";
			if($popup)
			{
				$html_info .= "<tr><td align=left><h3><b>".__('Resumen actividades profesionales')."</b></h3></td></tr>";
			}
			$html_info .= "<tr><td>";
			$html_info .= "<table width=100%><tr><td style='font-size:13px; font-weight:bold' align=left>".$titulo_reporte."</td><td width=25% style='font-size:12px; font-weight:bold' align=right>".__('Total').":&nbsp;";
			$minutos_total_hr = number_format(($total_hr-floor($total_hr))*60,2);
			$total_hr_minutos = floor($total_hr).':'.sprintf('%02d',round($minutos_total_hr));
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
				$html_info .= Utiles::Formato($total_hr_minutos,'%s');
			else
				$html_info .= Utiles::Formato($total_hr,'%f');
			$html_info .= "</td></tr>";
			$html_info .= "<tr><td colspan=2 align=left><u>".__('Periodo consultado').": ".$periodo_txt."</u></td></table>";
			$html_info .= "</td></tr><tr><td>&nbsp;</td></tr>";
			$html_info .= "<tr><td>";
			$html_info .= "<table class='tbl_general' border=1 width='100%' cellpadding='0' cellspacing='0'>";
			$html_info .= $tr_areas_trabajos;
			$html_info .= "</table>";
			$html_info .= "</td></tr></table>";

			echo $html_info;
        }
        else if($ver == 'area_prof') {
                $query_totales_areas = "SELECT CONCAT_WS(' ',usuario.nombre, usuario.apellido1) as profesional,
                                                IF(trabajo.id_area_trabajo IS NOT NULL, prm_area_trabajo.glosa, 'Indefinido') as glosa_area_trabajo,
                                                trabajo.id_area_trabajo, 
						cliente.codigo_cliente, $select
						SUM(TIME_TO_SEC(duracion)/60)/60 as hr_trabajadas,
						SUM(TIME_TO_SEC(if(trabajo.cobrable = 1,duracion_cobrada,0))/60)/60 as hr_cobrable,
						SUM(tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base*cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) as valor_cobrado
					FROM trabajo
					LEFT JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
                                        LEFT JOIN prm_area_trabajo ON prm_area_trabajo.id_area_trabajo = trabajo.id_area_trabajo 
					LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
					LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
					LEFT JOIN cobro on trabajo.id_cobro = cobro.id_cobro
					$join
					WHERE $where
					$where_usuario
					$where_cliente
					$where_area
					$where_categoria
					GROUP BY glosa_area_trabajo 
					$orderby";
		$resp_totales_areas = mysql_query($query_totales_areas,$sesion->dbh) or Utiles::errorSQL($query_totales_areas,__FILE__,__LINE__,$sesion->dbh);
		
		$valores_areas = array();
		while( $row = mysql_fetch_assoc($resp_totales_areas) )
		{
			$valores_areas[$row['glosa_area_trabajo']] = array();
			foreach( $row as $index => $valor )
			{
				if( $valores_areas[$row['glosa_area_trabajo']][$index] > 0 )
					$valores_areas[$row['glosa_area_trabajo']][$index] += $valor;
				else
					$valores_areas[$row['glosa_area_trabajo']][$index] = $valor;
			}
		}
                
                $valor_total = 0;
                foreach($valores_areas as $area => $valor) {
                    $valor_total += $valores_areas[$area][$horas_sql];
                }
                foreach($valores_areas as $area => $valor) {
                    $valores_areas[$area]['porcentaje_area'] = number_format( 100 * $valores_areas[$area][$horas_sql] / $valor_total, 2,',','');
                }
		$where = 1;
		#if(is_array($usuarios))
		#{
			#$lista_usuarios = join(',',$usuarios);
			#$where .= " AND trabajo.id_usuario IN ($lista_usuarios)";
		#}
		#else
			#die(__('Ustede no ha seleccionado a ningún usuario para generar el informe'));

		if( $fecha_ini != '' && $fecha_fin != '' )
			$where .= " AND trabajo.fecha Between '".Utiles::fecha2sql($fecha_ini)."' AND '".Utiles::fecha2sql($fecha_fin)."' ";

		if($codigo_cliente)
			$where .= " AND cliente.codigo_cliente = '".$codigo_cliente."' ";

		$total_hr = 0;

		$tbl_inset = "<table id='tbl_inset' width='100%' border='0' cellpadding='0' cellspacing='0'>
									 <tr>
										<td class=alt style='width:150px; border-right: 1px solid #CCCCCC;'>
											".__('Profesional')."
										</td>
										<td class=alt style='width:50px; border-left: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class=alt style='width:50px; border-left: 1px solid #CCCCCC;'>
											".__('%')."
										</td>
									</tr>
								</table>";
		$tr_area = "<tr>
								<td class=alt style='width:80px'>
									".__('Área Trabajo')."
								</td>
								<td class=alt id='td_horas' style='width:50px' style='cursor:pointer'>
									".__($col_resultado)."
								</td>
                                                                <td class=alt id='td_horas' style='width:50px' style='cursor:pointer'>
                                                                        ".__('%')."
                                                                </td>
								<td class=alt style='width:600px'>
									".$tbl_inset."
								</td>
							</tr>";
		#onMouseover=\"ddrivetip('".__('Haga clic botón derecho para cambiar la información mostrada')."')\" onMouseout=\"hideddrivetip()\"
		while($row = mysql_fetch_array($resp))
		{
				if($row['glosa_area_trabajo'] != $glosa_area_trabajo_actual || $mas_de_una_area == '')
				{ 
					if($mas_de_una_area == '')
					{
						$mas_de_una_area = 1;

						$id_area_trabajo_actual = $row['id_area_trabajo'];
						$glosa_area_trabajo_actual = $row['glosa_area_trabajo'] != '' ? $row['glosa_area_trabajo'] :'-';

						$id_usuario_actual = $row['id_usuario'];
						$glosa_usuario_actual = $row[$letra_profesional];
                                                    
                                                $tr_usuarios = '';
                                                
						$cont_area =1;
					}
					else
					{
						#AREAS
						$tr_area_trabajo = $tr_area."<tr><td class=grupo_nombre style='width:80px'>&nbsp;".$glosa_area_trabajo_actual."</td>
													<td class=grupo_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href='horas.php?fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_area_trabajo=".($id_area_trabajo_actual ? $id_area_trabajo_actual : 'NULL')."&usuarios=".base64_encode($lista_usuarios)."';\">".$glosa_show_area."</a></td>
                                                                                                        <td class=grupo_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href='horas.php?fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_area_trabajo=".($id_area_trabajo_actual ? $id_area_trabajo_actual : 'NULL')."&usuarios=".base64_encode($lista_usuarios)."';\">".$glosa_porcentaje_area."</a></td>
                                                                                                        <td class=grupo_nombre style='width:600px'><table id='tbl_cliente' width='100%'>".$tr_usuarios."</table></td></tr>\n";
						
                                                $id_area_trabajo_actual = $row['id_area_trabajo'];
                                                $glosa_area_trabajo_actual = $row['glosa_area_trabajo'] != '' ? $row['glosa_area_trabajo'] :'-';
                                                
						$dato_area = 0;
						$dato_area2 = 0;
						$tr_areas_trabajos .= $tr_area_trabajo;
                                                
                                                $tr_usuarios = '';
						$id_usuario_actual = $row['id_usuario'];
                                                
						$cont_usuario =1;
					}
				}


				if($horas_sql == 'horas_trabajadas_cobrables')
				{
					$dato = $row['hr_trabajadas'];
					$dato2 = $row['hr_cobrable'];
					if( $valores_areas[$row['glosa_area_trabajo']]['hr_trabajadas'] > 0 )
						$porcentaje1 = number_format( 100 * $dato / $valores_areas[$row['glosa_area_trabajo']]['hr_trabajadas'], 2, ',', '');
					else
						$porcentaje1 = "0,00";
					if( $valores_areas[$row['glosa_area_trabajo']]['hr_cobrable'] > 0 )
						$porcentaje2 = number_format( 100 * $dato2 / $valores_areas[$row['glosa_area_trabajo']]['hr_cobrable'], 2, ',', '');
					else
						$porcentaje2 = "0,00";
					$glosa_show = Utiles::Formato($dato,'%f').'<br>'.Utiles::Formato($dato2,'%f');
					$porcentaje = $porcentaje1.'<br>'.$porcentaje2;
				}
				else
				{
					$dato = $row[$horas_sql];
					if( $valores_areas[$row['glosa_area_trabajo']][$horas_sql] > 0 )
						$porcentaje = number_format( 100 * $dato / $valores_areas[$row['glosa_area_trabajo']][$horas_sql], 2, ',', '');
					else
						$porcentaje = "0,00";
					$glosa_show = Utiles::Formato($dato,'%f');
				}

				$tr_usuario = "<tr><td class=usuario_nombre style='width:150px'><a href='javascript:void(0)' onclick=\"DetalleUsuario(this.form,".$row['id_usuario'].")\">".$row[$letra_profesional]."</a></td>
											<td class=usuario_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=".$row['id_usuario']."&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=asunto&cid_area_trabajo=".$id_area_trabajo_actual."';\">".$glosa_show."</a></td>
											<td class=usuario_hr style='width:50px'>".$porcentaje."</td></tr>\n";
				$tr_usuarios .=$tr_usuario;

				$dato_area += $dato;
				$dato_usuario += $dato;
				$minutos_trabajados_area = number_format(($dato_area-floor($dato_area))*60,2);
				$dato_minutos_area = floor($dato_area).':'.sprintf('%02d',round($minutos_trabajados_area));
				$minutos_trabajados_usuario = number_format(($dato_usuario-floor($dato_usuario))*60,2);
				$dato_minutos_usuario = floor($dato_usuario).':'.sprintf('%02d',round($minutos_trabajados_usuario));
                                $glosa_porcentaje_area = $valores_areas[$row['glosa_area_trabajo']]['porcentaje_area'];
				if($horas_sql == 'horas_trabajadas_cobrables')
				{
					$dato_area2 += $dato2;
					$dato_usuario2 += $dato2;
					$minutos_trabajados_area2 = number_format(($dato_area2-floor($dato_area2))*60,2);
					$dato_minutos_area2 = floor($dato_area2).':'.sprintf('%02d',round($minutos_trabajados_area2));
					$minutos_trabajados_usuario2 = number_format(($dato_usuario2-floor($dato_usuario2))*60,2);
					$dato_minutos_usuario2 = floor($dato_usuario2).':'.sprintf('%02d',round($minutos_trabajados_usuario2));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() ) )
					{
						$glosa_show_area = Utiles::Formato($dato_minutos_area,'%s').'<br>'.Utiles::Formato($dato_minutos_area2,'%s');
						$glosa_show_usuario = Utiles::Formato($dato_minutos_usuario,'%s').'<br>'.Utiles::Formato($dato_minutos_usuario2,'%s');
					}
					else
					{
						$glosa_show_grupo = Utiles::Formato($dato_area,'%f').'<br>'.Utiles::Formato($dato_area2,'%f');
						$glosa_show_usuario = Utiles::Formato($dato_usuario,'%f').'<br>'.Utiles::Formato($dato_usuario2,'%f');
					}
				}
				else
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
					{
						$glosa_show_area = Utiles::Formato($dato_minutos_area,'%s');
						$glosa_show_usuario = Utiles::Formato($dato_minutos_usuario,'%s');
					}
					else
					{
						$glosa_show_area = Utiles::Formato($dato_area,'%f');
						$glosa_show_usuario = Utiles::Formato($dato_usuario,'%f');
					}
				}
				$total_hr += $dato;
		}
                
			#AREAS
			$tr_area_trabajo = $tr_area."
									<tr>
										<td class=grupo_nombre style='width:80px'>&nbsp;".$glosa_area_trabajo_actual."</td>
										<td class=grupo_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_area_trabajo=".($id_area_trabajo_actual ? $id_area_trabajo_actual : 'NULL')."';\">".$glosa_show_area."</a></td>
										<td class=grupo_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_area_trabajo=".($id_area_trabajo_actual ? $id_area_trabajo_actual : 'NULL')."';\">".$glosa_porcentaje_area."</a></td>
										<td class=grupo_nombre style='width:600px'><table id='tbl_cliente' width='100%'>".$tr_usuarios."</table></td>
									</tr>";
			$glosa_area_trabajo_actual = $row['glosa_area_trabajo'] != '' ? $row['glosa_area_trabajo'] :'-';
			$id_area_trabajo_actual = $row['id_area_trabajo'];
                        
			$tr_usuarios = '';
                        $id_usuario_actual = $row['id_usuario'];
                        
			$dato_area = 0;
			$dato_area2 = 0;
			$tr_areas_trabajos .= $tr_area_trabajo;

                        
			$html_info .= "<table width=100%>";
			if($popup)
			{
				$html_info .= "<tr><td align=left><h1><b>".__('Resumen actividades profesionales')."</b></h1></td></tr>";
				$html_info .= "<tr><td>&nbsp;</td></tr>";
			}
			$html_info .= "<tr><td>";
			$html_info .= "<table width=100%><tr><td style='font-size:13px; font-weight:bold' align=left>".$titulo_reporte."</td><td width=25% style='font-size:12px; font-weight:bold' align=right>".__('Total').":&nbsp;";
			$minutos_total_hr = number_format(($total_hr-floor($total_hr))*60,2);
			$total_hr_minutos = floor($total_hr).':'.sprintf('%02d',round($minutos_total_hr));
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
				$html_info .= Utiles::Formato($total_hr_minutos,'%s');
			else
				$html_info .= Utiles::Formato($total_hr,'%f');
			$html_info .= "</td></tr>";
			$html_info .= "<tr><td colspan=2 align=left><u>".__('Periodo consultado').": ".$periodo_txt."</u></td></table>";

			$html_info .= "</td></tr><tr><td>&nbsp;</td></tr>";
			$html_info .= "<tr><td>";
			$html_info .= "<table class='tbl_general' border=1 width='100%' cellpadding='0' cellspacing='0'>";
			$html_info .= $tr_usr;
			$html_info .= $tr_areas_trabajos;
			$html_info .= "</table>";
			$html_info .= "</td></tr></table>";

			echo $html_info;
        }
	else if($ver == 'cliente')
	{
		$query_totales_clientes = "SELECT CONCAT_WS(' ',usuario.nombre, usuario.apellido1) as profesional,
						cliente.codigo_cliente, $select
						SUM(TIME_TO_SEC(duracion)/60)/60 as hr_trabajadas,
						SUM(TIME_TO_SEC(if(trabajo.cobrable = 1,duracion_cobrada,0))/60)/60 as hr_cobrable,
						SUM(tarifa_hh*TIME_TO_SEC(duracion_cobrada)/3600*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base*cobro.monto/IF(cobro.monto_thh>0,cobro.monto_thh,cobro.monto)) as valor_cobrado
						FROM trabajo
					LEFT JOIN usuario ON usuario.id_usuario = trabajo.id_usuario
					LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
					LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
					LEFT JOIN cobro on trabajo.id_cobro = cobro.id_cobro
					$join
					WHERE $where
					$where_usuario
					$where_cliente
					$where_area
					$where_categoria
					GROUP BY cliente.codigo_cliente 
					$orderby";
		$resp_totales_clientes = mysql_query($query_totales_clientes,$sesion->dbh) or Utiles::errorSQL($query_totales_clientes,__FILE__,__LINE__,$sesion->dbh);
		
		$valores_clientes = array();
		while( $row = mysql_fetch_assoc($resp_totales_clientes) )
		{
			$valores_clientes[$row['codigo_cliente']] = array();
			foreach( $row as $index => $valor )
			{
				if( $valores_clientes[$row['codigo_cliente']][$index] > 0 )
					$valores_clientes[$row['codigo_cliente']][$index] += $valor;
				else
					$valores_clientes[$row['codigo_cliente']][$index] = $valor;
			}
		}
		$where = 1;
		#if(is_array($usuarios))
		#{
			#$lista_usuarios = join(',',$usuarios);
			#$where .= " AND trabajo.id_usuario IN ($lista_usuarios)";
		#}
		#else
			#die(__('Ustede no ha seleccionado a ningún usuario para generar el informe'));

		if( $fecha_ini != '' && $fecha_fin != '' )
			$where .= " AND trabajo.fecha Between '".Utiles::fecha2sql($fecha_ini)."' AND '".Utiles::fecha2sql($fecha_fin)."' ";

		if($codigo_cliente)
			$where .= " AND cliente.codigo_cliente = '".$codigo_cliente."' ";

		$total_hr = 0;

		$tbl_inset = "<table id='tbl_inset' width='100%' border='0' cellpadding='0' cellspacing='0'>
									 <tr>
										<td class=alt style='width:150px; border-right: 1px solid #CCCCCC;'>
											".__('Cliente')."
										</td>
										<td class=alt style='width:50px; border-right: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class=alt style='width:150px; border-right: 1px solid #CCCCCC;'>
											".__('Asunto')."
										</td>
										<td class=alt style='width:50px; border-right: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class=alt style='width:150px; border-right: 1px solid #CCCCCC;'>
											".__('Profesional')."
										</td>
										<td class=alt style='width:50px; border-left: 1px solid #CCCCCC;'>
											".__($col_resultado)."
										</td>
										<td class=alt style='width:50px; border-left: 1px solid #CCCCCC;'>
											".__('%')."
										</td>
									</tr>
								</table>";
		$tr_usr = "<tr>
								<td class=alt style='width:80px'>
									".__('Grupo')."
								</td>
								<td class=alt id='td_horas' style='width:50px' style='cursor:pointer'>
									".__($col_resultado)."
								</td>
								<td class=alt style='width:600px'>
									".$tbl_inset."
								</td>
							</tr>";
		#onMouseover=\"ddrivetip('".__('Haga clic botón derecho para cambiar la información mostrada')."')\" onMouseout=\"hideddrivetip()\"
		while($row = mysql_fetch_array($resp))
		{
				if($row['id_grupo_cliente'] != $id_grupo_cliente_actual || $mas_de_un_cliente == '')
				{
					if($mas_de_un_cliente == '')
					{
						$mas_de_un_cliente = 1;

						$id_grupo_cliente_actual = $row['id_grupo_cliente'];
						$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';

						$id_cliente_actual = $row['id_cliente'];
						$glosa_cliente_actual = $row['glosa_cliente'];
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];

						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$id_asunto_actual = $row['codigo_asunto_secundario'];
						else
							$id_asunto_actual = $row['codigo_asunto'];
							
						
						$glosa_asunto_actual = $row['glosa_asunto'];

						$id_usuario_actual = $row['id_usuario'];
						$glosa_usuario_actual = $row[$letra_profesional];

						$cont_usuario =1;
					}
					else
					{
						#USUNTOS
						$tr_asunto = "<tr><td class=asunto_nombre style='width:150px'>".$glosa_asunto_actual."</td>
													<td class=asunto_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&codigo_asunto=$id_asunto_actual&from=asunto&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin';\">".$glosa_show_asunto."</a></td>
													<td class=asunto_nombre style='width:200px'><table id='tbl_prof' width='100%'>".$tr_usuarios."</table></td></tr>\n";
						$glosa_asunto_actual = $row['glosa_asunto'];
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$id_asunto_actual = $row['codigo_asunto_secundario'];
						else
							$id_asunto_actual = $row['codigo_asunto'];
							
						$tr_asuntos .= $tr_asunto;
						$tr_usuarios = '';
						$dato_asunto = 0;
						$dato_asunto2 = 0;
						$id_usuario_actual = $row['id_usuario'];

						#CLIENTE
						$tr_cliente = "<tr><td class=cliente_nombre style='width:150px'>".$glosa_cliente_actual."</td>
													<td class=cliente_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&usuarios=".base64_encode($lista_usuarios)."';\">".$glosa_show_cliente."</a></td>
													<td class=cliente_nombre style='width:400px'><table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td></tr>\n";
						$glosa_cliente_actual = $row['glosa_cliente'];
						$id_cliente_actual = $row['id_cliente'];
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
						$tr_clientes .= $tr_cliente;
						$tr_asuntos = '';
						$dato_cliente = 0;
						$dato_cliente2 = 0;

						#GRUPOS
						$tr_grupo = "<tr><td class=grupo_nombre style='width:80px'>&nbsp;".$glosa_grupo_cliente_actual."</td>
													<td class=grupo_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_grupo=".($id_grupo_cliente_actual ? $id_grupo_cliente_actual : 'NULL')."&usuarios=".base64_encode($lista_usuarios)."&clientes=".base64_encode($lista_clientes)."';\">".$glosa_show_grupo."</a></td>
													<td class=grupo_nombre style='width:600px'><table id='tbl_cliente' width='100%'>".$tr_clientes."</table></td></tr>\n";
						$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';
						$id_grupo_cliente_actual = $row['id_grupo_cliente'];
						$tr_clientes = '';
						$dato_grupo = 0;
						$dato_grupo2 = 0;
						$tr_grupos .=$tr_grupo;

						$cont_usuario =1;
					}
				}
				else if($row['id_cliente'] != $id_cliente_actual)
				{
					#USUNTOS
					$tr_asunto = "<tr><td class=asunto_nombre style='width:150px'>".$glosa_asunto_actual."</td>
												<td class=asunto_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&codigo_asunto=$id_asunto_actual&from=asunto&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin';\">".$glosa_show_asunto."</a></td>
												<td class=asunto_nombre style='width:200px'><table id='tbl_prof' width='100%'>".$tr_usuarios."</table></td></tr>\n";
					$glosa_asunto_actual = $row['glosa_asunto'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$id_asunto_actual = $row['codigo_asunto_secundario'];
						else
							$id_asunto_actual = $row['codigo_asunto'];
							
					$id_usuario_actual = $row['id_usuario'];
					$tr_asuntos .= $tr_asunto;
					$tr_usuarios = '';
					$dato_asunto = 0;
					$dato_asunto2 = 0;

					#CLIENTES
					$tr_cliente = "<tr><td class=cliente_nombre style='width:150px'>".$glosa_cliente_actual."</td>
												<td class=cliente_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&usuarios=".base64_encode($lista_usuarios)."';\">".$glosa_show_cliente."</a></td>
												<td class=cliente_nombre style='width:400px'><table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td></tr>\n";
					$glosa_cliente_actual = $row['glosa_cliente'];
					$id_cliente_actual = $row['id_cliente'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
					$tr_clientes .= $tr_cliente;
					$tr_asuntos = '';
					$dato_cliente = 0;
					$dato_cliente2 = 0;
				}
				else if($row['codigo_asunto'] != $id_asunto_actual)
				{
					#USUNTOS
					$tr_asunto = "<tr><td class=asunto_nombre style='width:150px'>".$glosa_asunto_actual."</td>
												<td class=asunto_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&codigo_asunto=$id_asunto_actual&from=asunto&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin';\">".$glosa_show_asunto."</a></td>
												<td class=asunto_nombre style='width:200px'><table id='tbl_prof' width='100%'>".$tr_usuarios."</table></td></tr>\n";
					$glosa_asunto_actual = $row['glosa_asunto'];
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$id_asunto_actual = $row['codigo_asunto_secundario'];
						else
							$id_asunto_actual = $row['codigo_asunto'];
							
					$tr_asuntos .= $tr_asunto;
					$tr_usuarios = '';
					$dato_asunto = 0;
					$dato_asunto2 = 0;
					$id_usuario_actual = $row['id_usuario'];
				}


				if($horas_sql == 'horas_trabajadas_cobrables')
				{
					$dato = $row['hr_trabajadas'];
					$dato2 = $row['hr_cobrable'];
					if( $valores_clientes[$row['codigo_cliente']]['hr_trabajadas'] > 0 )
						$porcentaje1 = number_format( 100 * $dato / $valores_clientes[$row['codigo_cliente']]['hr_trabajadas'], 2, ',', '');
					else
						$porcentaje1 = "0,00";
					if( $valores_clientes[$row['codigo_cliente']]['hr_cobrable'] > 0 )
						$porcentaje2 = number_format( 100 * $dato2 / $valores_clientes[$row['codigo_cliente']]['hr_cobrable'], 2, ',', '');
					else
						$porcentaje2 = "0,00";
					$glosa_show = Utiles::Formato($dato,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato2,'%f', $idioma, 2);
					$porcentaje = $porcentaje1.'<br>'.$porcentaje2;
				}
				else
				{
					$dato = $row[$horas_sql];
					$glosa_show = Utiles::Formato($dato,'%f', $idioma, 2);
					if( $valores_clientes[$row['codigo_cliente']][$horas_sql] > 0 )
						$porcentaje = number_format( 100 * $dato / $valores_clientes[$row['codigo_cliente']][$horas_sql], 2, ',', '');
					else
						$porcentaje = "0,00";
				}

				$tr_usuario = "<tr><td class=usuario_nombre style='width:150px'><a href='javascript:void(0)' onclick=\"DetalleUsuario(this.form,".$row[id_usuario].")\">".$row[$letra_profesional]."</a></td>
											<td class=usuario_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?id_usuario=".$row[id_usuario]."&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=asunto&codigo_cliente=".$codigo_cliente_actual."&codigo_asunto=".$id_asunto_actual."';\">".$glosa_show."</a></td>
											<td class=usuario_hr style='width:50px'>".$porcentaje."</td></tr>\n";
				$tr_usuarios .=$tr_usuario;

				$dato_cliente +=$dato;
				$dato_grupo += $dato;
				$dato_asunto += $dato;
				$dato_usuario += $dato;
				$minutos_trabajados_cliente = number_format(($dato_cliente-floor($dato_cliente))*60,2);
				$dato_minutos_cliente = floor($dato_cliente).':'.sprintf('%02d',round($minutos_trabajados_cliente));
				$minutos_trabajados_grupo = number_format(($dato_grupo-floor($dato_grupo))*60,2);
				$dato_minutos_grupo = floor($dato_grupo).':'.sprintf('%02d',round($minutos_trabajados_grupo));
				$minutos_trabajados_asunto = number_format(($dato_asunto-floor($dato_asunto))*60,2);
				$dato_minutos_asunto = floor($dato_asunto).':'.sprintf('%02d',round($minutos_trabajados_asunto));
				$minutos_trabajados_usuario = number_format(($dato_usuario-floor($dato_usuario))*60,2);
				$dato_minutos_usuario = floor($dato_usuario).':'.sprintf('%02d',round($minutos_trabajados_usuario));
				if($horas_sql == 'horas_trabajadas_cobrables')
				{
					$dato_cliente2 +=$dato2;
					$dato_grupo2 += $dato2;
					$dato_asunto2 += $dato2;
					$dato_usuario2 += $dato2;
					$minutos_trabajados_cliente2 = number_format(($dato_cliente2-floor($dato_cliente2))*60,2);
					$dato_minutos_cliente2 = floor($dato_cliente2).':'.sprintf('%02d',round($minutos_trabajados_cliente2));
					$minutos_trabajados_grupo2 = number_format(($dato_grupo2-floor($dato_grupo2))*60,2);
					$dato_minutos_grupo2 = floor($dato_grupo2).':'.sprintf('%02d',round($minutos_trabajados_grupo2));
					$minutos_trabajados_asunto2 = number_format(($dato_asunto2-floor($dato_asunto2))*60,2);
					$dato_minutos_asunto2 = floor($dato_asunto2).':'.sprintf('%02d',round($minutos_trabajados_asunto2));
					$minutos_trabajados_usuario2 = number_format(($dato_usuario2-floor($dato_usuario2))*60,2);
					$dato_minutos_usuario2 = floor($dato_usuario2).':'.sprintf('%02d',round($minutos_trabajados_usuario2));
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() ) )
					{
						$glosa_show_cliente = Utiles::Formato($dato_minutos_cliente,'%s').'<br>'.Utiles::Formato($dato_minutos_cliente2,'%s');
						$glosa_show_grupo = Utiles::Formato($dato_minutos_grupo,'%s').'<br>'.Utiles::Formato($dato_minutos_grupo2,'%s');
						$glosa_show_asunto = Utiles::Formato($dato_minutos_asunto,'%s').'<br>'.Utiles::Formato($dato_minutos_asunto2,'%s');
						$glosa_show_usuario = Utiles::Formato($dato_minutos_usuario,'%s').'<br>'.Utiles::Formato($dato_minutos_usuario2,'%s');
					}
					else
					{
						$glosa_show_cliente = Utiles::Formato($dato_cliente,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato_cliente2,'%f', $idioma, 2);
						$glosa_show_grupo = Utiles::Formato($dato_grupo,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato_grupo2,'%f', $idioma, 2);
						$glosa_show_asunto = Utiles::Formato($dato_asunto,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato_asunto2,'%f', $idioma, 2);
						$glosa_show_usuario = Utiles::Formato($dato_usuario,'%f', $idioma, 2).'<br>'.Utiles::Formato($dato_usuario2,'%f', $idioma, 2);
					}
				}
				else
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
					{
						$glosa_show_cliente = Utiles::Formato($dato_minutos_cliente,'%s');
						$glosa_show_grupo = Utiles::Formato($dato_minutos_grupo,'%s');
						$glosa_show_asunto = Utiles::Formato($dato_minutos_asunto,'%s');
						$glosa_show_usuario = Utiles::Formato($dato_minutos_usuario,'%s');
					}
					else
					{
						$glosa_show_cliente = Utiles::Formato($dato_cliente,'%f', $idioma, 2);
						$glosa_show_grupo = Utiles::Formato($dato_grupo,'%f', $idioma, 2);
						$glosa_show_asunto = Utiles::Formato($dato_asunto,'%f', $idioma, 2);
						$glosa_show_usuario = Utiles::Formato($dato_usuario,'%f', $idioma, 2);
					}
				}
				$total_hr += $dato;
		}

			#Ultimo dato en caso de no cambiar cliente en el ultimo dato
			#USUNTOS
			$tr_asunto = "
									<tr>
										<td class='asunto_nombre' style='width:150px'>".$glosa_asunto_actual."</td>
										<td class='asunto_hr' style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&codigo_asunto=$id_asunto_actual&from=asunto&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin';\">".$glosa_show_asunto."</a></td>
										<td class='asunto_nombre' style='width:200px'><table id='tbl_prof' width='100%'>".$tr_usuarios."</table></td>
									</tr>";
			$glosa_asunto_actual = $row['glosa_asunto'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$id_asunto_actual = $row['codigo_asunto_secundario'];
						else
							$id_asunto_actual = $row['codigo_asunto'];
							
			$tr_asuntos .= $tr_asunto;
			$tr_usuarios = '';
			$dato_asunto = 0;
			$dato_asunto2 = 0;
			$id_usuario_actual = $row['id_usuario'];

			#CLIENTE
			$tr_cliente = "
									<tr>
										<td class=cliente_nombre style='width:150px'>".$glosa_cliente_actual."</td>
										<td class=cliente_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?codigo_cliente=$codigo_cliente_actual&fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&usuarios=".base64_encode($lista_usuarios)."';\">".$glosa_show_cliente."</a></td>
										<td class=cliente_nombre style='width:400px'><table id='tbl_asunto' width='100%'>".$tr_asuntos."</table></td>
									</tr>";
			$glosa_cliente_actual = $row['glosa_cliente'];
			$id_cliente_actual = $row['id_cliente'];
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
							$codigo_cliente_actual = $row['codigo_cliente_secundario'];
						else
							$codigo_cliente_actual = $row['codigo_cliente'];
			$tr_clientes .= $tr_cliente;
			$tr_asuntos = '';
			$dato_cliente = 0;
			$dato_cliente2 = 0;

			#GRUPOS
			$tr_grupo = "
									<tr>
										<td class=grupo_nombre style='width:80px'>&nbsp;".$glosa_grupo_cliente_actual."</td>
										<td class=grupo_hr style='width:50px'><a href='javascript:void(0)' onclick=\"window.self.location.href= 'horas.php?fecha_ini=$fecha_ini&fecha_fin=$fecha_fin&from=cliente&id_grupo=".($id_grupo_cliente_actual ? $id_grupo_cliente_actual : 'NULL')."&usuarios=".base64_encode($lista_usuarios)."&clientes=".base64_encode($lista_clientes)."';\">".$glosa_show_grupo."</a></td>
										<td class=grupo_nombre style='width:600px'><table id='tbl_cliente' width='100%'>".$tr_clientes."</table></td>
									</tr>";
			$glosa_grupo_cliente_actual = $row['glosa_grupo_cliente'] != '' ? $row['glosa_grupo_cliente'] :'-';
			$id_grupo_cliente_actual = $row['id_grupo_cliente'];
			$tr_clientes = '';
			$dato_grupo = 0;
			$dato_grupo2 = 0;
			$tr_grupos .= $tr_grupo;

			$html_info .= "<table width=100%>";
			if($popup)
			{
				$html_info .= "<tr><td align=left><h1><b>".__('Resumen actividades profesionales')."</b></h1></td></tr>";
				$html_info .= "<tr><td>&nbsp;</td></tr>";
			}
			$html_info .= "<tr><td>";
			$html_info .= "<table width=100%><tr><td style='font-size:13px; font-weight:bold' align=left>".$titulo_reporte."</td><td width=25% style='font-size:12px; font-weight:bold' align=right>".__('Total').":&nbsp;";
			$minutos_total_hr = number_format(($total_hr-floor($total_hr))*60,2);
			$total_hr_minutos = floor($total_hr).':'.sprintf('%02d',round($minutos_total_hr));
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) || ( method_exists('Conf','MostrarSoloMinutos')&& Conf::MostrarSoloMinutos() ) )
				$html_info .= Utiles::Formato($total_hr_minutos,'%s');
			else
				$html_info .= Utiles::Formato($total_hr,'%f');
			$html_info .= "</td></tr>";
			$html_info .= "<tr><td colspan=2 align=left><u>".__('Periodo consultado').": ".$periodo_txt."</u></td></table>";

			$html_info .= "</td></tr><tr><td>&nbsp;</td></tr>";
			$html_info .= "<tr><td>";
			$html_info .= "<table class='tbl_general' border=1 width='100%' cellpadding='0' cellspacing='0'>";
			$html_info .= $tr_usr;
			$html_info .= $tr_grupos;
			$html_info .= "</table>";
			$html_info .= "</td></tr></table>";

			echo $html_info;
	}
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
<?
//En el caso de que la opcion sea imprimir se imprime al final.
if($popup)
{
	echo "<script>
					window.print();
					window.close();
				</script>";
}
	$pagina->PrintBottom($popup);
?>
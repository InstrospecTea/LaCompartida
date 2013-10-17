<?php

require_once dirname(__FILE__).'/../conf.php';

$sesion = new Sesion(array('DAT','PRO'));
$pagina = new Pagina($sesion);
$id_usuario = $sesion->usuario->fields['id_usuario'];

// solo se muestran las opciones al admin de datos
$params_array['codigo_permiso'] = 'DAT';
// tiene permiso de admin_datos
$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array);

if ($permisos->fields['permitido']) {
	$p_admin = true;
} else {
	$p_admin = false;
}

$cliente = new Cliente($sesion);

if ($excel) {
	$cliente->DownloadExcel(compact('glosa_cliente', 'codigo', 'id_grupo_cliente', 'giro', 'fecha1', 'fecha2', 'solo_activos'));
	exit;
}

if ($permisos->fields['permitido'] && $accion == "eliminar") {
	$cliente_eliminar = new Cliente($sesion);
	$cliente_eliminar->Load($id_cliente);

	if (!$cliente_eliminar->Eliminar()) {
		$pagina->AddError($cliente_eliminar->error);
	} else {
		$pagina->AddInfo(__('Cliente').' '.__('eliminado con éxito'));
	}
}

$pagina->titulo = __('Clientes');
$pagina->PrintTop();
?>
<script type="text/javascript">
function Validar(form)
{
	if(!form.codigo_cliente.value)
	{
		alert("<?php echo __('Debe ingresar un codigo de cliente')?>");
		form.codigo_cliente.focus();
		return false;
	}
	if(!form.glosa_cliente.value)
	{
		alert("<?php echo __('Debe ingresar el nombre del cliente')?>");
		form.glosa_cliente.focus();
		return false;
	}
	return true;
}

function Listar( form, from )
{
	if(from == 'buscar')
		form.action = 'clientes.php?buscar=1';
	else if(from == 'xls')
		form.action = 'clientes.php?excel=1';
	else
		return false;

	form.submit();
	return true;
}

function DescargarIncompletos(form)
{
	form.action = 'contrato_datos_incompletos_xls.php';
	form.submit();
	return true;
}
//funcion java para eliminar
function EliminaCliente(id_cliente)
{
	var desde = <?php echo ($desde)? $desde : '0'?>;
	form = document.getElementById('form_cliente');
	self.location.href = "clientes.php?id_cliente="+id_cliente+"&accion=eliminar&buscar=1&desde="+desde;
	return true;
}
</script>
<?php  	
echo Autocompletador::CSS();
echo Autocompletador::Javascript($sesion,false);
?>
<form method="post" action="#" name="form_cliente" id="form_cliente">
<!--<input type=hidden name=opcion value="Buscar" />-->
<input type="hidden" name="id_cliente" value="<?php echo  $cliente->fields['id_cliente'] ?>" />
<?php if($p_admin) { ?>
	<table width='720px' cellspacing="3" cellpadding="3">
		<tr>
			<td></td>
			<td align="right">
				<a href="agregar_cliente.php"><img src="<?php echo Conf::ImgDir()?>/agregar.gif" border="0"> <?php echo __('Nuevo Cliente')?></a>
			</td>
		</tr>
	</table>
<?php } ?>
	<table width="90%">
		<tr>
			<td> 
				<fieldset width="100%" class="tb_base">
					<legend><?php echo __('Filtros')?></legend>
					<table width='720px' cellspacing="3" cellpadding="3">
						<tr>
							<td align="right" width="35%" class="cvs">
								<?php echo __('Nombre Cliente')?>
							</td>
							<td align="left">
								<input type="text" name="glosa_cliente" id="glosa_cliente" size="50" value="<?php echo $glosa_cliente?>">
								
								<div id="sugerencias_glosa_cliente" class="autocomplete" style="display:none; z-index:100;"></div>
							</td>
						</tr>
						<tr>
							<td align="right" class="cvs">
								<?php echo __('Código')?>
							</td>
							<td align="left">
								<input onkeydown="if(event.keyCode==13)Listar( this.form, 'buscar' );" type="text" name="codigo" size="20" value="<?php echo $codigo?>">
							</td>
						</tr>
						<tr>
							<td align="right" class="cvs">
								<?php echo __('Grupo')?>
							</td>
							<td align="left">
								<?php echo  Html::SelectQuery($sesion, "SELECT id_grupo_cliente, glosa_grupo_cliente FROM grupo_cliente", "id_grupo_cliente", $id_grupo_cliente, "", "Ninguno","width=100px")  ?>
							</td>
						</tr>
					<?php if (Conf::GetConf($sesion, 'UsaGiroClienteParametrizable')) { ?>
						<tr>
							<td align="right" class="cvs">
								<?php echo __('Giro')?>
							</td>
							<td align="left">
								<?php echo  Html::SelectQuery($sesion, "SELECT codigo, glosa FROM prm_codigo WHERE grupo = 'GIRO_CLIENTE' ORDER BY glosa ASC", "giro", $giro, "", "Cualquiera")  ?>
							</td>
						</tr>
					<?php } ?>
						<tr>
							<td align="right" class="cvs">
								<?php echo __('Fecha Desde')?>
							</td>
							<td nowrap align="left" class="cvs">
								<input class="fechadiff" onkeydown="if(event.keyCode==13)Listar(this.form,'buscar')" type="text" name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
			 					&nbsp;&nbsp;<?php echo __('Fecha Hasta')?>
								<input class="fechadiff"  onkeydown="if(event.keyCode==13)Listar(this.form,'buscar')" type="text" name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
			 				</td>
						</tr>
						<tr>
							<td align="right" class="cvs">
								<?php echo __('Solo Activos')?>
							</td>
							<td align="left">
							<input type="checkbox" name=solo_activos id=solo_activos value=1 <?php echo $solo_activos ? "checked" : "" ?>>
							</td>
						</tr>
						<tr>
							<td></td>
							<td align="left">
								<a class="btn botonizame"  href="javascript:void(0);"  icon="find" name='buscar'  onclick="Listar(jQuery('#form_cliente').get(0), 'buscar');"><?php echo __('Buscar')?></a>
								<a class="btn botonizame"  href="javascript:void(0);" icon="xls"   onclick="Listar(jQuery('#form_cliente' ).get(0), 'xls');" ><?php echo __('Descargar listado a Excel')?></a>
								<?php if (Conf::GetConf($sesion,'ValidacionesCliente')) { ?>
									<input type=button class=btn value="<?php echo __('Descargar listado clientes datos incompletos')?>" onclick="DescargarIncompletos(this.form);" >
								<?php } ?>
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
	</table>
</form>
 
<?php
if ($buscar) {
	$where = '1';
	$joins = '';
	if ($glosa_cliente != '') {
		$nombre = addslashes(strtr($glosa_cliente, ' ', '%' ));
		$where .= " AND cliente.glosa_cliente Like '%$nombre%'";
	}
	if ($codigo != '') {
		if (Conf::GetConf($sesion,'CodigoSecundario')) {
			$where .= " AND cliente.codigo_cliente_secundario = '$codigo'";
		} else {
			$where .= " AND cliente.codigo_cliente = '$codigo'";
		}
	}
	if ($id_grupo_cliente > 0 ) {
		$where .= " AND cliente.id_grupo_cliente = ".$id_grupo_cliente."";
	}
	if (!empty($fecha1)) {
		$where .= " AND cliente.fecha_creacion >= '".Utiles::fecha2sql($fecha1)."' ";
	}
	if (!empty($fecha2)) {
		$where .= " AND date_add(cliente.fecha_creacion, interval -1 day) < '".Utiles::fecha2sql($fecha2)."' ";
	}
	if ($solo_activos == 1) {
		$where .= " AND cliente.activo = 1 ";
	}
	if (!empty($giro)) {
		$joins .= " INNER JOIN contrato USING(id_contrato) ";
		$where .= " AND contrato.factura_giro LIKE '%$giro%' ";
	}

	$query = "SELECT SQL_CALC_FOUND_ROWS cliente.*, grupo_cliente.glosa_grupo_cliente
				FROM cliente
				LEFT JOIN grupo_cliente USING (id_grupo_cliente)
				$joins
				WHERE $where";
	
	if ($orden == "") {
		$orden = "cliente.glosa_cliente";
	}
	$x_pag = 20;

	$b = new Buscador($sesion, $query, "Cliente", $desde, $x_pag, $orden);
	$b->AgregarEncabezado("glosa_cliente",__('Nombre Cliente'),"align=left");
	$b->AgregarEncabezado("fecha_creacion",__('Fecha Creación'),"align=left");
	if (Conf::GetConf($sesion,'CodigoSecundario')) {
		$b->AgregarEncabezado("codigo_cliente_secundario",__('Código Secundario'),"align=left");
	} else {
		$b->AgregarEncabezado("codigo_cliente",__('Código'),"align=left");
	}
	$b->AgregarFuncion(__('Asuntos'),'Asuntos',"align=left");
	$b->AgregarEncabezado("glosa_grupo_cliente",__('Grupo'),"align=left");
	if ($p_admin) {
		$b->AgregarFuncion("","Opciones","align=center nowrap");
	}
	$b->color_mouse_over = "#bcff5c";
	$b->Imprimir();
}

function Opciones(& $fila) {
	global $sesion;
	global $desde;
	$id_cliente = $fila->fields['id_cliente'];
	$cod_cliente = $fila->fields['codigo_cliente'];
	#$txt .= "<a href=tarifas_especiales.php?id_cliente=$id_cliente><img src='".Conf::ImgDir()."/usuarios2_16.gif' border=0 alt='Tarifas especiales' /></a>";
	#$txt .=  " <a href=cuenta_corriente.php?codigo_cliente=$cod_cliente><img src='".Conf::ImgDir()."/money_16.gif' border=0 alt='Cuenta corriente cliente' /></a>";
	if (Conf::GetConf($sesion,'UsaDisenoNuevo')) {
		$txt .= " <a href=agregar_cliente.php?id_cliente=$id_cliente title='".__('Editar Cliente')."'><img src='".Conf::ImgDir()."/editar_on.gif' border=0 alt='Editar cliente' /></a>"
			. "<a href='javascript:void(0);' onclick=\"if (confirm('¿".__('Est&aacute; seguro de eliminar el')." ".__('cliente')."?'))EliminaCliente($id_cliente);\"><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 alt='Eliminar' /></a>";
	} else {
		$txt .= " <a href=agregar_cliente.php?id_cliente=$id_cliente title='".__('Editar Cliente')."'><img src='".Conf::ImgDir()."/editar_on.gif' border=0 alt='Editar cliente' /></a>"
		. "<a href='javascript:void(0);' onclick=\"if (confirm('¿".__('Est&aacute; seguro de eliminar el')." ".__('cliente')."?'))EliminaCliente($id_cliente);\"><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
	}
	return $txt;
}
function Asuntos(& $fila) {
	global $sesion;
	if (Conf::GetConf($sesion,'CodigoSecundario')) {
		$codigo_cliente_secundario = $fila->fields['codigo_cliente_secundario'];
		$join = " LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente ";
		$where = " cliente.codigo_cliente_secundario='$codigo_cliente_secundario'";
	} else {
		$codigo_cliente = $fila->fields['codigo_cliente'];
		$join="";
		$where = " asunto.codigo_cliente='$codigo_cliente'";
	}
	$query = "SELECT asunto.id_asunto,asunto.codigo_asunto,asunto.codigo_asunto_secundario,asunto.glosa_asunto FROM asunto $join WHERE asunto.activo=1 AND $where";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	$txt .= "<ul>";
	while(list($id_asunto,$codigo_asunto,$codigo_asunto_secundario,$glosa_asunto) = mysql_fetch_array($resp))
	{
		if (Conf::GetConf($sesion,'CodigoSecundario')) {
			$codigo_asunto=$codigo_asunto_secundario;
		}
		global $p_admin;
		if ($p_admin) {
			$txt .= "<li><a style=\"text-decoration:none;font-size:1em\" href=agregar_asunto.php?id_asunto=$id_asunto>$codigo_asunto - $glosa_asunto</a></li>";
		} else {
			$txt .= "<li>$codigo_asunto - $glosa_asunto</li>";
		}
	}
	$txt .= "</ul>";
	return $txt;
}
$pagina->PrintBottom();

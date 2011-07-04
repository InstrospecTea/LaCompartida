<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Proveedor.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	
	$proveedor = new Proveedor($sesion);
	$where = " WHERE 1";

	
	if($opcion == 'guardar')
	{
		$txt_tipo = "guardado";
		if(!empty($id_proveedor))
		{
			if($proveedor->Load($id_proveedor))
			{
				$txt_tipo = "editado";
			}
		}
		$proveedor->Edit('rut', $rut);
		$proveedor->Edit('glosa', $glosa);
		if($proveedor->Write())
		{
			$pagina->AddInfo( __('Proveedor').' '.$txt_tipo.' '.__('con éxito.'));
			?>
			<script type="text/javascript">
				window.opener.location.reload();
			</script>
			<?
		}
	}

	if($opcion == 'Buscar')
	{
		if($rut)
			$where .= " AND rut like '%".$rut."%'";
		if($glosa)
			$where .= " AND glosa like '%".$glosa."%'";
	}
	if($id_proveedor)
		$where .= " AND id_proveedor = '".$id_proveedor."'";
	$query = "SELECT SQL_CALC_FOUND_ROWS * ,id_proveedor, rut, glosa
			 FROM prm_proveedor
			 ".$where;

	$pagina->titulo = $txt_pagina;
	$pagina->PrintTop($popup);
?>

<script type="text/javascript">
	function Cerrar()
	{
		window.opener.location.reload();
		window.close();
	}
	function Buscar(form)
	{
		form.opcion.value='Buscar';
		form.submit();
	}
	function EditarProveedor(id)
	{
		$('id_proveedor').value=id
		$('rut').value=$('rut_'+id).value
		$('glosa').value=$('glosa_'+id).value
	}
	
	var continuar = 1;
	function Guardar(form)
	{
		if(continuar==0)
		{
			return false;
		}

		if(form.rut.value=='')
		{
			alert('<?=__('Debe ingresar el rut del proveedor')?>');
			form.rut.focus();
			return false;
		}

		if(form.glosa.value=='')
		{
			alert('<?=__('Debe ingresar la glosa del proveedor')?>');
			form.glosa.focus();
			return false;
		}
		
		form.opcion.value='guardar';
		form.submit();
	}

		
	
</script>

<? echo Autocompletador::CSS(); ?>
<form method=post action="" id="form_documentos" autocomplete='off'>
<input type=hidden name="opcion" value="guardar" />
<input type=hidden name="id_proveedor" id="id_proveedor" value="" />
<br>
<table width='90%'>
	<tr>
		<td align=left><b><?=$txt_pagina ?></b></td>
	</tr>
</table>
<br>

<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left width="50%">
			<b><?=__('Información del Proveedor') ?> </b>
		</td>
	</tr>
</table>
<table style="border: 1px solid black;" width='90%'>
	<tr>
		<td align=right width="30%">
			<?=__('Rut')?>
		</td>
		<td align=left colspan="3">
			<input type="text" name="rut" value="<?=$proveedor->fields['rut'] ? $proveedor->fields['rut'] : '' ?>" id="rut" size="12" maxlength="12" />
		</td>
	</tr>
	<tr>
		<td align="right">
			<?=__('Glosa')?>
		</td>
		<td colspan="3" align="left">
			<input type="text" name="glosa" value="<?=$proveedor->fields['glosa'] ? $proveedor->fields['glosa'] : '' ?>" id="glosa" size="50" maxlength="50" />
		</td>
	</tr>
</table>
<br>
<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left>
			<input type=button class=btn value="<?=__('Guardar')?>" onclick='Guardar(this.form);' />
			<input type=button class=btn value="<?=__('Buscar')?>" onclick="Buscar(this.form);" />
			<input type=button class=btn value="<?=__('Cerrar')?>" onclick="Cerrar();" />
		</td>
	</tr>
</table>
<?
	$x_pag = 15;
	$b = new Buscador($sesion, $query, "Objeto", 0, 0, "glosa ASC");
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_facturas";
	$b->titulo = __('Listado de').' '.__('Proveedores');
	$b->titulo .= "<table width=100%>";
	$b->AgregarEncabezado("id_proveedor",__('id'),"align=center");
	$b->AgregarEncabezado("rut",__('Rut'),"align=center");
	$b->AgregarEncabezado("glosa",__('Glosa'),"align=center");
	$b->AgregarFuncion("Opciones",'Opciones',"align=center nowrap");
	$b->color_mouse_over = "#bcff5c";

	$b->Imprimir("",array(),false);

	function Opciones2(& $fila)
	{
		echo '<br>'.Conf::ImgDir();
		$opc_html = "<input type='hidden' value='".$fila->fields['rut']."' id='rut_".$fila->fields['id_proveedor']."'  name='rut_".$fila->fields['id_proveedor']."'>";
		$opc_html .= "<input type='hidden' value='".$fila->fields['glosa']."' id='glosa_".$fila->fields['id_proveedor']."'  name='glosa_".$fila->fields['id_proveedor']."'>";
		$opc_html .= "<img src=".Conf::ImgDir()."/editar_on.gif' border=0 title='Editar' onClick='Editar(this.form,".$fila->fields['id_proveedor'].")'/>";
		return $opc_html;
	}
	function Opciones(& $fila)
    {
		$id_proveedor = $fila->fields['id_proveedor'];
        $opc_html = "<input type='hidden' value='".$fila->fields['rut']."' id='rut_".$id_proveedor."'  name='rut_".$id_proveedor."'>";
		$opc_html .= "<input type='hidden' value='".$fila->fields['glosa']."' id='glosa_".$id_proveedor."'  name='glosa_".$id_proveedor."'>";
		$opc_html .= "<a target=\"_parent\" onClick=EditarProveedor($id_proveedor)><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar Proveedor></a>";
    
		return $opc_html;
	}
?>
</form>


<script type="text/javascript">

Calendar.setup(
	{
		inputField	: "fecha",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_pago",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_pago"		// ID of the button
	}
);
</script>
<?
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo Autocompletador::Javascript($sesion,false);
	}
	echo InputId::Javascript($sesion);
	$pagina->PrintBottom($popup);
?>

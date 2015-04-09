<?php

require_once dirname(__FILE__).'/../conf.php';

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
		<?php
	}
} else if( $opcion == 'eliminar' ) {
	if(!empty($id_proveedor))
	{
		if($proveedor->Load($id_proveedor))
		{
			if( $proveedor->Eliminar() ) {
				$pagina->AddInfo( __('Proveedor eliminado con éxito.'));
				?>
				<script type="text/javascript">
					window.opener.location.reload();
				</script>
				<?php
			} else {
				$pagina->AddError($proveedor->error);
			}
		}
	}
	unset($id_proveedor);
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
$Form = new Form();
?>

<script type="text/javascript">
	function Buscar() {
		jQuery('#opcion').val('Buscar');
		jQuery('#form_proveedor').submit();
	}
	function EditarProveedor(id) {
		jQuery('#id_proveedor').val(id);
		jQuery('#rut').val(jQuery('#rut_' + id).val());
		jQuery('#glosa').val(jQuery('#glosa_'+id).val());
	}

	function EliminarProveedor(id) {
		if( confirm('Está seguro que quiere eliminar el proveedor.') ) {
			jQuery('#id_proveedor').val(id);
			jQuery('#opcion').val('eliminar');
			jQuery('#form_proveedor').submit();
			return true;
		} else {
			return false;
		}
	}

	var continuar = 1;
	function Guardar() {
		if(continuar == 0) {
			return false;
		}

		if(jQuery('#rut').val() == '') {
			alert('<?php echo __('Debe ingresar el rut del proveedor') ?>');
			jQuery('#rut').focus();
			return false;
		}

		if(jQuery('#glosa').val() == '') {
			alert('<?php echo __('Debe ingresar la glosa del proveedor') ?>');
			jQuery('#glosa').focus();
			return false;
		}

		jQuery('#opcion').val('guardar');
		jQuery('#form_proveedor').submit();
	}

</script>

<form method=post action="" id="form_proveedor" autocomplete='off'>
<input type=hidden name="opcion" id="opcion" value="guardar" />
<input type=hidden name="id_proveedor" id="id_proveedor" value="" />
<br>
<table width='90%'>
	<tr>
		<td align=left><b><?php echo $txt_pagina ?></b></td>
	</tr>
</table>
<br>

<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align=left width="50%">
			<b><?php echo __('Información del Proveedor') ?> </b>
		</td>
	</tr>
</table>
<table style="border: 1px solid black;" width='90%'>
	<tr>
		<td align=right width="30%">
			<?php echo __('Rut')?>
		</td>
		<td align=left colspan="3">
			<input type="text" name="rut" value="<?php echo $proveedor->fields['rut'] ? $proveedor->fields['rut'] : '' ?>" id="rut" size="30" maxlength="50" />
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Glosa')?>
		</td>
		<td colspan="3" align="left">
			<input type="text" name="glosa" value="<?php echo $proveedor->fields['glosa'] ? $proveedor->fields['glosa'] : '' ?>" id="glosa" size="50" maxlength="50" />
		</td>
	</tr>
</table>
<br>
<table style="border: 0px solid black;" width='90%'>
	<tr>
		<td align="left">
			<?php
			echo $Form->icon_button(__('Guardar'), 'save', array('onclick' => 'Guardar()'));
			echo $Form->icon_button(__('Buscar'), 'find', array('onclick' => 'Buscar()'));
			echo $Form->icon_button(__('Cerrar'), 'exit', array('onclick' => 'Cerrar()'));
			?>
		</td>
	</tr>
</table>
<?php
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
		$opc_html .= "<a target=\"_parent\" onClick=EliminarProveedor($id_proveedor)><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 title=Eliminar Proveedor></a>";

		return $opc_html;
	}
?>
</form>


<?php
echo $Form->script();
$pagina->PrintBottom($popup);
?>

<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../app/classes/Bodega.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	
	$sesion = new Sesion(array('PRO','REV','ADM','COB'));
	
	$pagina = new Pagina($sesion);
	
	$bodega = new Bodega($sesion);
	
	$bodega->loadById($id_bodega_edicion);
	
	if($opc == 'guardar')
	{
		$bodega->Edit('glosa_bodega',$glosa_bodega);
		
		if($bodega->Write())
		{
		  $id_bodega_edicion = $bodega->fields['id_bodega'];
			$pagina->AddInfo(__('La bodega se ha modificado satisfactoriamente'));
		}
	}
	$pagina->titulo = __('Ingreso de Bodegas');

	$pagina->PrintTop($popup);
	
	$active = ' onFocus="foco(this);" onBlur="no_foco(this);" ';
?>
<script>
function foco(elemento) 
{ 
	elemento.style.border = "2px solid #000000";
} 

function no_foco(elemento) 
{ 
	elemento.style.border = "1px solid #CCCCCC"; 
}
function cambia_bodega(valor) 
{
	var popup = $('popup').value;
	if( confirm('<?=__('confirma cambio bodega?')?>') )
		self.location.href = 'agregar_bodega.php?id_bodega_edicion=' +valor+ '&popup=' +popup;
}
</script>
<style>
#tbl_bodega
{
	font-size: 10px;
	padding: 1px;
	margin: 0px;
	vertical-align: middle;
	border:1px solid #CCCCCC;
}
.text_box
{
	font-size: 10px;
	text-align:right;
}
</style>
<form name=formulario id=formulario method=post action='' autocomplete="off">
	<input type=hidden name='id_bodega_edicion' value='<?=$bodega->fields['id_bodega']?>'>
	<input type=hidden name='opc' value='guardar'>
	<input type=hidden name='popup' id='popup' value='<?=$popup ?>'>

	<table width='100%' border="0" cellpadding="0" cellspacing="0">
		<tr>
<?
	$colspan=2;
	if($id_bodega_edicion || $bodega->fields['id_bodega'])
	{
		$colspan=4;
?>
			<td align=right><?=__('Bodega')?>&nbsp;</td>
			<td align=left><?= Html::SelectQuery($sesion, "SELECT * FROM bodega ORDER BY glosa_bodega","id_bodega", $id_bodega_edicion ? $id_bodega_edicion : $bodega->fields['id_bodega'],"onchange='cambia_bodega(this.value)'","","120"); ?></td>
<?
	}
?>
			<td> <?=__('Nombre')?>: <input type=text name=glosa_bodega value='<?=$bodega->fields['glosa_bodega']?>' <?=$active?>> </td>
		</tr>
		<tr>
			<td colspan=<?=$colspan?> align=right>&nbsp;</td>
		</tr>
		<tr>
			<td colspan=<?=$colspan?> align=right><input type=submit value='<?=__('Guardar') ?>' class=btn ><input type=button onclick="self.location.href='agregar_bodega.php?popup=<?=$popup?>'" value='<?=__('Crear nueva bodega') ?>' class=btn ></td>
		</tr>
	</table>
</form>
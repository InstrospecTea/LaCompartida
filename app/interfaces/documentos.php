<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Archivo.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	
	$sesion = new Sesion(array('DAT','COB'));
	$pagina = new Pagina($sesion);
	$archivo = new Archivo($sesion);
	$contrato = new Contrato($sesion);
	$contrato->Load($id_contrato);
	if(!$contrato->loaded())
		$pagina->AddError(__('No se ha cargado el contrato.'));
	#Subiendo Archivo
	if(!empty($archivo_data['name'])&&$accion=="guardar")
	{
		$archivo->Edit('id_contrato',$contrato->fields['id_contrato']);
		$archivo->Edit('descripcion',$descripcion);
		$archivo->Edit('archivo_data',$archivo_data);
		if($archivo->Write())
			$pagina->AddInfo(__('Documento guardado con éxito'));
		else
			$pagina->AddError(__('No se ha podido guardar el documento.'));
	}
	#eliminando archivo
	if($accion=="eliminar" && $id_archivo)
	{
		if($archivo->Eliminar($id_archivo))
			$pagina->AddInfo(__('Documento eliminado con éxito'));
		else
			$pagina->AddError($archivo->error);
	}
	$pagina->PrintTop(1);
?>
<script>
function Guardar()
{
	var form = $('form_archivo');
	$('accion').value = 'guardar';
	$('id_archivo').value = '';
	form.submit();
	return true;
}
function Eliminar(id)
{
	var form = $('form_archivo');
	if(confirm("<?=__('¿Desea eliminar el archivo seleccionado?')?>") && id)
	{
		$('id_archivo').value = id;
		$('accion').value = 'eliminar';
		form.submit();
		return true;
	}
	return false;
}
</script>
<form name='form_archivo' id='form_archivo' method='post' action="" enctype="multipart/form-data">
<input type=hidden name='id_contrato' id='id_contrato' value='<?=$id_contrato ? $id_contrato : $contrato->fields['id_contrato'] ?>' />
<input type=hidden name='id_cliente' id='id_cliente' value='<?=$id_cliente ?>' />
<input type=hidden name='id_archivo' id='id_archivo' value='' />
<input type=hidden name='accion' id='accion' value='' />
	<!--Samuel-->
<?
		if($id_cliente||$id_asunto) 
		{
?>
<table width="100%">
	<tr>
		<td align=right>
			Documento:
		</td>
		<td align=left>
			<input type=file name="archivo_data">
		</td>
	</tr>
	<tr>
		<td align=right>
			Descripci&oacute;n: 
		</td>
		<td align=left>
			<textarea cols=30 rows=2 name="descripcion"></textarea>
		</td>
	</tr>
	<tr>
		<td colspan=2 align=center>
			<input type=button onclick="return Guardar();" value="<?=__('Cargar Documento')?>" class="btn" />
		</td>
	</tr>
</table>
</form>
<?
			//Listado de documentos con buscador, problemas con IExplorer, doble form.
			if($contrato->loaded())
			{
				if ($desde="")
					$desde=0;
				if ($x_pag="")
					$x_pag=3;
				$query = "SELECT SQL_CALC_FOUND_ROWS *,id_archivo, descripcion, archivo_nombre 
											FROM archivo 
											WHERE id_contrato = '".$contrato->fields['id_contrato']."' ";
				$b = new Buscador($sesion, $query, "Archivo", $desde, $x_pag, 'archivo_nombre');
				$b->nombre = "busc_archivos";
				$b->titulo = __('Listado de Documentos');
				$b->AgregarEncabezado("archivo_nombre",__('Nombre'),"align=left");
				$b->AgregarEncabezado("descripcion",__('Descripción'),"align=left width=60%");
				$b->AgregarFuncion('', 'Opciones', "align=center nowrap width=10%");
				
				#Opciones TR del buscador archivos
				function Opciones(& $fila)
				{
					global $id_cliente;
					global $id_asunto;
					global $sesion;
					$id_archivo = $fila->fields['id_archivo'];
					$_archivo  = new Archivo($sesion);
					$_icono = 'guardar.gif';
					
					if(!empty($id_archivo))
					{
						if($_archivo->Load($id_archivo))
						{
							switch( $_archivo->fields['data_tipo'] )
							{
								case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':    //docx
								case 'application/msword': //doc
									$_icono = 'doc.gif';
									break;
								case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': //xlsx
								case 'application/msexcel':  //xls
								case 'application/vnd.ms-excel': //xls
									$_icono = 'excel.gif';
									break;
								case 'application/x-pdf': //pdf
								case 'application/pdf': //pdf
									$_icono = 'pdf.gif';
									break;
								default: $_icono = 'guardar.gif';
							}
						}
					}

					
					$txt = "<a href=\"#\" onclick=\"nuevaVentana('Archivo','500','100','ver_archivo.php?id_archivo=".$id_archivo."')\"><img src='".Conf::ImgDir()."/".$_icono."' border=0 style='cursor:pointer' /></a>";
					if (!empty($id_cliente))
						$txt .= "&nbsp;<img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' onclick=\"return Eliminar('".$id_archivo."')\" class='mano_on' />";
					else if (!empty($id_asunto))
						$txt .= "&nbsp;<img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' onclick=\"return Eliminar('".$id_archivo."')\" />"; 
					return $txt;
				}
				
				$b->Imprimir();
			}
		} #fin id_cliente OR id_asunto
?>


<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	
	$sesion = new Sesion();
	$pagina = new Pagina($sesion); 

	$popup = true;
	$pagina->titulo = __('Subir excel modificado');
	$pagina->PrintTop( $popup );
	?>
	<script type="text/javascript">
		function confirmar(form)
			{
				if(confirm("Esta modificando algunos trabajos con el excel,\n ¿Desea continuar?"))
					{
						form.submit();
						window.opener.Refrescar();
					}
			}
	</script>
	
	<table width="480">
		<tr>
			<td style="border-bottom: 2px solid #a3d45c;">
				<span align="left" style="display:block; font-weight: bold; font-size: 10pt;">Aqu&iacute; se sube el excel modificado</span>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="center">
				<form name="form_archivo" id="form_archivo" method="post" action="" enctype="multipart/form-data">
					<input type="hidden" name="opc" id="opc" value="subir_excel" />
					Subir excel modificado: <input type="file" name="archivo_data" />
					<input type="button" value="<?=__('Cargar Documento')?>" class="btn" onclick="confirmar(this.form);" />
					<br />
					<?
					if($opc=='subir_excel')
					{
						// Esta función actualiza los trabajos y entrega feedback del resultado.
						echo '<br /><span align="center">'.Trabajo::ActualizarConExcel($archivo_data, $sesion).'</span>';
					}
					?>
				</form>
			</td>
		</tr>
	</table>
	<?
	$pagina->PrintBottom( $popup );
?>

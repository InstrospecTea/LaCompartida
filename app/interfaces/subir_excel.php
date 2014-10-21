<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion();
$pagina = new Pagina($sesion);

$popup = true;
$pagina->titulo = __('Subir excel modificado');
$pagina->PrintTop($popup);
?>

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
				<input type="button" value="<?php echo __('Cargar Documento'); ?>" class="btn" onclick="confirmar(this.form);" />
			</form>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
			<span align="center">
				<?php
				if ($opc == 'subir_excel') {
					// Sólo se permiten xls "%\.(xls|xlsx)$%i"
					if (preg_match('%\.(xls)$%i', $archivo_data['name']) == 1) {
						// Esta función actualiza los trabajos y entrega feedback del resultado.
						echo Trabajo::ActualizarConExcel($archivo_data, $sesion);
					} else {
						echo '<b>La extenci&oacute;n del excel fue adjuntado es inv&aacute;lido.</b>';
					}
				}
				?>
			</span>
		</td>
	</tr>
</table>

<script type="text/javascript">
	function confirmar(form) {
		if (confirm("Esta modificando algunos trabajos con el excel,\n ¿Desea continuar?")) {
			form.submit();
			window.opener.Refrescar();
		}
	}
</script>

<?php $pagina->PrintBottom($popup);

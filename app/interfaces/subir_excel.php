<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion();
$pagina = new Pagina($sesion);

$popup = true;
$pagina->titulo = __('Subir excel modificado');
$pagina->PrintTop($popup);
?>

<style type="text/css">
	table {
		margin: 5px;
	}

	table td {
		padding: 2px;
	}

	.title {
		border-bottom: 2px solid #a3d45c;
	}

	.title span {
		display:block;
		font-weight: bold;
		font-size: 10pt;
	}

	.warning {
		color: red;
		padding: 3px;
	}
</style>
<form name="form_archivo" id="form_archivo" method="post" action="" enctype="multipart/form-data">
<input type="hidden" name="opc" id="opc" value="subir_excel" />
	<table width="480">
		<tr>
			<td class="title">
				<span align="left"><?= __('Excel borrador modificado'); ?></span>
			</td>
		</tr>
		<tr>
			<td class="warning"><?= __('Recuerde que al subir el Excel modificará los trabajos'); ?></td>
		</tr>
		<tr>
			<td align="left">
				<?= __('Idioma del Excel'); ?>:
				<?php echo Html::SelectQuery($sesion, 'SELECT codigo_idioma, glosa_idioma FROM prm_idioma ORDER BY glosa_idioma', 'lang', $lang, '', '', 80); ?>
			</td>
		</tr>
		<tr>
			<td align="left">
				<?= __('Subir excel modificado'); ?>: <input type="file" name="archivo_data" />
				<input type="button" value="<?= __('Cargar Excel'); ?>" class="btn" onclick="enviar(this.form);" />
			</td>
		</tr>
		<tr>
			<td>
				<span align="center">
					<?php
					if ($opc == 'subir_excel') {
						// Sólo se permiten xls "%\.(xls|xlsx)$%i"
						if (preg_match('%\.(xls)$%i', $archivo_data['name']) == 1) {
							// Esta función actualiza los trabajos y entrega feedback del resultado.
							echo Trabajo::ActualizarConExcel($archivo_data, $sesion, $lang);
						} else {
							echo '<b>' . __('La extención del Excel adjuntado es inválida') . '.</b>';
						}
					}
					?>
				</span>
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">
	function enviar(form) {
		form.submit();
		window.opener.Refrescar();
	}
</script>

<?php $pagina->PrintBottom($popup);

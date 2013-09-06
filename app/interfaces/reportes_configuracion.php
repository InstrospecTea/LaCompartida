<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion(array('ADM'));

use TTB\Pagina as Pagina;
$Pagina = new Pagina($Sesion);

$SimpleReport = new SimpleReport($Sesion);

$Pagina->titulo = __('Configuración de Reportes');

$Pagina->PrintTop($popup);

if ($_REQUEST['accion'] == 'guardar') {

	foreach ($data as $tipo => $config) {
		$SimpleReport->LoadWithType($tipo);

		$nueva = array();
		foreach($config as $field => $conf){
			$conf['field'] = $field;
			$conf['title'] = utf8_encode($conf['title']);
			$conf['visible'] = array_key_exists('visible', $conf) && $conf['visible'] == 1;
			$nueva[] = $conf;
		}
		$SimpleReport->Edit('configuracion', json_encode($nueva));
		$SimpleReport->Write();
	}
}

$configuraciones = $SimpleReport->GetAllConfigurations();
?>
<div id="tabs">
	<ul>
		<?php foreach (array_keys($configuraciones) as $tipo) { ?>
			<li><a href="#<?php echo $tipo; ?>"><?php echo $tipo; ?></a></li>
		<?php } ?>
	</ul>
	<?php foreach ($configuraciones as $tipo => $configuracion) { ?>
		<div id="<?php echo $tipo; ?>">
			<form action="reportes_configuracion.php" method="POST" id="<?php echo "form_$tipo"; ?>" class="reportes">
				<input type="hidden" name="accion" value="guardar" />
				<h1>Configuración de: <?php echo $tipo; ?></h1>
				<ul style="text-align: left; list-style-type: none">
					<?php foreach ($configuracion->columns as $field => $column) { ?>
						<li>
							<input name="<?php echo "data[$tipo][$field][order]"; ?>" type="hidden" class="sortable_item" />
							<input name="<?php echo "data[$tipo][$field][visible]"; ?>" type="checkbox" value="1" <?php echo $column->visible ? 'checked="checked"' : ''; ?> />
							<input name="<?php echo "data[$tipo][$field][title]"; ?>" type="text" value="<?php echo utf8_decode($column->title); ?>" />
							<em style="font-size: 0.8em; cursor: move"><?php echo utf8_decode($configuracion->columns[$field]->extras['original_title']) . " ($field)"; ?></em>
						</li>
					<?php } ?>
				</ul>
				<br />
				<div>
					<input type="button" id="<?php echo "submit_$tipo"; ?>" value="Guardar" class="btn" />
				</div>
			</form>
		</div>
		<?php
	}
	?>
</div>
<script type="text/javascript">
	jQuery(window).load(function () {
		jQuery('ul').sortable({ axis: 'y' });
		jQuery('#tabs').tabs();
	});

	jQuery(document).ready(function () {
		jQuery('input[type="button"]').click(changeOrderOnSubmit);
	});

	changeOrderOnSubmit = function() {
		form_submit = jQuery(this.form);
		form_submit.find('.sortable_item').each(function (index) {
			jQuery(this).val(index);
		});

		form_submit.submit();
	}
</script>
<?php
$Pagina->PrintBottom($popup, true);

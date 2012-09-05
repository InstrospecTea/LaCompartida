<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion(array('ADM'));
$Pagina = new Pagina($Sesion);

$SimpleReport = new SimpleReport($Sesion);

$Pagina->titulo = __('Configuración de Reportes');

$Pagina->PrintTop($popup);

if ($_REQUEST['accion'] == 'guardar') {

	foreach ($data as $tipo => $config) {
		$SimpleReport->LoadWithType($tipo);

		if ($SimpleReport->Loaded()) {
			$orig = json_decode($SimpleReport->fields['configuracion_original'], true);
			$nueva = array();
			foreach($orig as $conf){
				if(isset($config[$conf['field']])){
					$conf_data = $config[$conf['field']];
					$conf['title'] = utf8_encode($conf_data['title']);
					$conf['visible'] = array_key_exists('visible', $conf_data) && $conf_data['visible'] == 1;
					$conf['order'] = $conf_data['order'];
				}
				$nueva[] = $conf;
			}
			$SimpleReport->Edit('configuracion', json_encode($nueva));
			$SimpleReport->Write();
		}
	}
}

$reportes = $SimpleReport->GetAll($_REQUEST['tipo']);
?>
<div id="tabs">
	<ul>
		<?php foreach ($reportes as $reporte) { ?>
			<li><a href="#<?php echo $reporte['tipo']; ?>"><?php echo $reporte['tipo']; ?></a></li>
		<?php } ?>
	</ul>
	<?php
	foreach ($reportes as $reporte) {
		$configuracion_original = $SimpleReport->LoadConfigFromJson($reporte['configuracion_original']);

		if (!empty($reporte['configuracion'])) {
			$configuracion = $SimpleReport->LoadConfigFromJson($reporte['configuracion']);
		} else {
			$configuracion = $configuracion_original;
		}
		?>
		<div id="<?php echo $reporte['tipo']; ?>">
			<form action="reportes_configuracion.php" method="POST" id="<?php echo "form_{$reporte['tipo']}"; ?>" class="reportes">
				<input type="hidden" name="accion" value="guardar" />
				<h1>Configuración de: <?php echo $reporte['tipo']; ?></h1>
				<ul style="text-align: left; list-style-type: none">
					<?php foreach ($configuracion->columns as $field => $column) { ?>
						<li>
							<input name="<?php echo "data[{$reporte['tipo']}][$field][order]"; ?>" type="hidden" class="sortable_item" />
							<input name="<?php echo "data[{$reporte['tipo']}][$field][visible]"; ?>" type="checkbox" value="1" <?php echo $column->visible ? 'checked="checked"' : ''; ?> />
							<input name="<?php echo "data[{$reporte['tipo']}][$field][title]"; ?>" type="text" value="<?php echo utf8_decode($column->title); ?>" />
							<em style="font-size: 0.8em; cursor: move"><?php echo utf8_decode($configuracion_original->columns[$field]->title); ?></em>
						</li>
					<?php } ?> 
				</ul>
				<br />
				<div>
					<input type="button" id="<?php echo "submit_{$reporte['tipo']}"; ?>" value="Guardar" class="btn" />
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
$Pagina->PrintBottom($popup);
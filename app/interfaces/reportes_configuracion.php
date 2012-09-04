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
			$config_array = array();

			foreach ($config as $field => $field_config) {
				$field_config['field'] = $field;
				$field_config['title'] = utf8_encode($field_config['title']);
				$field_config['visible'] = (array_key_exists('visible', $field_config) && $field_config['visible'] == 1);
				if(isset($field_config['extras'])){
					$field_config['extras'] = json_decode($field_config['extras']);
				}

				$config_array[] = $field_config;
			}

			$SimpleReport->Edit('configuracion', json_encode($config_array));
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
							<?php
							foreach(array('format', 'sort', 'group') as $campo) {
								if(!empty($column->$campo)) {
							?>
									<input name="<?php echo "data[{$reporte['tipo']}][$field][$campo]"; ?>" type="hidden" value="<?php echo $column->$campo; ?>" />
							<?php
								}
							}?>
							<?php if(!empty($column->extras)){ ?>
								<input name="<?php echo "data[{$reporte['tipo']}][$field][extras]"; ?>" type="hidden" value='<?php echo json_encode($column->extras); ?>' />
							<?php } ?>
								
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
<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);

$form = new Form();
$pagina->titulo = __('Reporte Historial Movimientos');

//
//Post handler
//

if (!empty($_POST)) {


	if (empty($entity_code) && empty($charge) && empty($id_usuario) && empty($codigo_cliente) && empty($codigo_asunto)) {

		$pagina->AddError(__('Debe filtrar al menos por Nº '.__('Cobro').','.__('Cliente').','.__('Asunto').','.__('Usuario').'o código de entidad.'));

	} else {

		$controller = new ReporteHistorialMovimientos($sesion);
		$controller->setFocus($selected_entity);

		if (Conf::GetConf($sesion, 'CodigoSecundario')) {
			$codigo_cliente = $codigo_cliente_secundario;
			$codigo_asunto = $codigo_asunto_secundario;
		}

		//Configuración del controlador del reporte.

		if (!empty($selected_action)) {
			$controller->filterByMovement($selected_action);
		}

		if (!empty($id_usuario)) {
			$controller->setProtagonist($id_usuario);
		}

		if (!empty($entity_code)) {
			$controller->setEntity($entity_code);
		}

		if (!empty($fecha_ini)) {
			$controller->since($fecha_ini);
		}

		if (!empty($fecha_fin)) {
			$controller->until($fecha_fin);
		}

		if (!empty($codigo_cliente)) {
			$controller->setClient($codigo_cliente);
		}

		if (!empty($codigo_asunto)) {
			$controller->setMatter($codigo_asunto);
		}

		if (!empty($charge)) {
			$controller->setCharge($charge);
		}

		$report = $controller->generate();
		if ($to_do == 'excel') {
			$writer = SimpleReport_IOFactory::createWriter($report, 'Spreadsheet');
			$writer->save('Reporte_historial_movimientos');
		} else {
			$writer = SimpleReport_IOFactory::createWriter($report, 'Html');
		}
		
	}
}

$entities = array(
	'trabajo' => __('Trabajos'),
	'gasto' => __('Gastos'),
	'cobro' => __('Cobros'),
	'tramite' => __('Trámites')
);

$actions = array(
	'crear' => __('Crear'),
	'modificar' => __('Modificar'),
	'eliminar' => __('Eliminar')
);


$pagina->PrintTop();
?>
<form id="form_reporte" method="POST">
	<fieldset class="tb_base" style="border: 1px solid #BDBDBD;" width="100%">
		<legend>Filtros</legend>
		<table id="tbl_report" style="border: 0px solid black;width:700px;margin:auto;">
			<tbody>
				<input type="hidden" name="to_do" id="to_do">
				<tr>
		            <td  width="110" style="text-align:right;width:120px;" class="buscadorlabel">
				        <?php echo __('Cliente') ?>
				            </td>
				            <td align=left width="530" nowrap>
				        <?php
				        	UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, true, '320');
				        ?>
		            </td>
		        </tr>
		        <tr>
		            <td align='right' class="buscadorlabel">
		        		<?php echo __('Asunto') ?>
		            </td>
		            <td align=left width="440" nowrap>
						<?php
							UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, 320);
						?>
		            </td>
		        </tr>
				<tr>
					<td class="buscadorlabel">
						<?php echo __('Usuario') ?>
					</td>
					<td align='left' colspan="2">
						<?php echo $form->select('id_usuario', $sesion->usuario->ListarActivos('', 'PRO'), $id_usuario, array('empty' => 'Cualquiera', 'style' => 'width:440px;')); ?>
					</td>
				</tr>
				
				<tr>
					<td class="buscadorlabel">
						<?php echo __('Entidad') ?>
					</td>
					<td align='left' colspan="2">
						<?php echo $form->select('selected_entity', $entities, $selected_entity, array('empty' => false, 'id' => 'selected_entity')); ?>
					</td>
				</tr>
				<tr>
					<td class="buscadorlabel">
						<?php echo __('Código Entidad') ?>
					</td>
					<td align='left' colspan="2">
						<input type="text" id="entity_code" name="entity_code" value="<?php echo $entity_code ?>">
					</td>
				</tr>
				<tr id="numCobro">
					<td class="buscadorlabel">
						<?php echo 'Nº '.__('Cobro') ?>
					</td>
					<td align='left' colspan="2">
						<input type="text" id="charge" name="charge" value="<?php echo $charge ?>">
					</td>
				</tr>
				<tr id="accion" <?php echo $accion_cobro ? 'style="display:none;"' : '' ?>>
					<td class="buscadorlabel">
						<?php echo __('Acción') ?>
					</td>
					<td align='left' colspan="2">
						<?php echo $form->select('selected_action', $actions, $selected_action, array('empty' => __('Cualquiera'), 'id' => 'selected_action')); ?>
					</td>
				</tr>
				<tr>
					<td class="buscadorlabel" colspan=1><?php echo __('Fecha desde') ?></td>
					<td align=left colspan="2">
						<input type="text" name="fecha_ini" class="fechadiff" value="<?php echo $fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
						 &nbsp;&nbsp;&nbsp;&nbsp;
						<div class="buscadorlabel" style="margin-bottom: 3px;width:70px;display:inline-block;" ><?php echo __('Fecha hasta') ?></div>
						<input type="text" name="fecha_fin"  class="fechadiff"  value="<?php echo $fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
					</td>
				</tr>
				<tr>
					<td></td>
					<td align="left" colspan="1">
						<?php echo $form->icon_button(__('Buscar'), 'find', array('id' => 'boton_buscar')); ?>
					</td>
					<td align="left" colspan="1">
						<?php echo $form->icon_button(__('Descargar'), 'xls', array('id' => 'boton_excel')); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</fieldset>
</form>
<br/>
<br/>
<?php
echo $form->script();

if ($writer && $to_do != 'excel') {
	echo $writer->save();
}
?>

<script type="text/javascript">

	jQuery(document).ready(function() {

		jQuery('#boton_buscar').click(function() {
			jQuery('#to_do').val('screen');
			jQuery('#form_reporte').submit();
		});

		jQuery('#boton_excel').click(function() {
			jQuery('#to_do').val('excel');
			jQuery('#form_reporte').submit();
		});

		jQuery('#selected_entity').change(function(){
			var option = jQuery('#selected_entity').val();
			switch (option) {
				case "cobro":
					jQuery('#numCobro').css('display','none');
					break;
				default:
					jQuery('#accion').css('display', '');
					jQuery('#numCobro').css('display','');
					jQuery('#accion_cobro').css('display', 'none');
					break;
			}
		});

		jQuery('#selected_entity').change();

	});
</script>

<?php
$pagina->PrintBottom();

<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);

$form = new Form();
$pagina->titulo = __('Auditoría');

//
//Post handler
//

if (!empty($_POST)) {


	if (empty($entity_code) && empty($charge) && empty($id_usuario) && empty($codigo_cliente) && empty($codigo_asunto)) {

		$pagina->AddError(__('Debe filtrar al menos por Nº '.__('Cobro').', '.__('Cliente').', '.__('Asunto').', '.__('Usuario').' o código de entidad.'));

	} else {

		$showreport = true;

		$controller = new reportehistorialmovimientos($sesion);
		$controller->setfocus($selected_entity);

		if (conf::getconf($sesion, 'codigosecundario')) {
			$codigo_cliente = $codigo_cliente_secundario;
			$codigo_asunto = $codigo_asunto_secundario;
		}

		//configuración del controlador del reporte.

		if (!empty($selected_action)) {
			$controller->filterbymovement($selected_action);
		}

		if (!empty($id_usuario)) {
			$controller->setprotagonist($id_usuario);
		}

		if (!empty($entity_code)) {
			$controller->setentity($entity_code);
		}

		if (!empty($fecha_ini)) {
			$controller->since($fecha_ini);
		}

		if (!empty($fecha_fin)) {
			$controller->until($fecha_fin);
		}

		if (!empty($codigo_cliente)) {
			$controller->setclient($codigo_cliente);
		}

		if (!empty($codigo_asunto)) {
			$controller->setmatter($codigo_asunto);
		}

		if (!empty($charge)) {
			$controller->setcharge($charge);
		}

		if (!empty($fecha_ini) && !empty($fecha_fin)) {
			$sinceobject = new datetime($fecha_ini);
			$untilobject = new datetime($fechrea_fin);
			if ($sinceobject->diff($untilobject)->format('%a') > 31) {
				$pagina->adderror(__('el rango de fechas establecido es superior a un mes, por favor realice una búsqueda en un rango de hasta 31 días.'));
				$showreport = false;
			} else {
				$controller->since($sinceobject->format('y-m-d'));
				$controller->until($untilobject->format('y-m-d'));
			}
		} else {
			$dateinterval = new dateinterval('p31d');
			if (!empty($fecha_ini)) {
				$sinceobject = new datetime($fecha_ini);
				$controller->since($sinceobject->format('y-m-d'));
				$untilobject = $sinceobject->add($dateinterval);
				$controller->until($untilobject->format('y-m-d'));
			}
			if (!empty($fecha_fin)) {
				$untilobject = new datetime($fecha_fin);
				$controller->until($untilobject->format('y-m-d'));
				$sinceobject = $untilobject->sub($dateinterval);
				$controller->since($sinceobject->format('y-m-d'));
			}
		}

		if ($showreport) {
			$report = $controller->generate();
			if ($to_do == 'excel') {
				$writer = simplereport_iofactory::createwriter($report, 'spreadsheet');
				$writer->save('reporte_historial_movimientos');
			} else {
				$writer = simplereport_iofactory::createwriter($report, 'html');
			}
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
						<?php echo __('Dato') ?>
					</td>
					<td align='left' colspan="2">
						<?php echo $form->select('selected_entity', $entities, $selected_entity, array('empty' => false, 'id' => 'selected_entity')); ?>
					</td>
				</tr>
				<tr>
					<td class="buscadorlabel">
						<?php echo __('Código') ?>
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

<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Template.php';

$Sesion = new Sesion(array('ADM'));
$Pagina = new Pagina($Sesion);

$Template = new Template($Sesion);

if (isset($_REQUEST['id_template'])) {
	$Template->Load($_REQUEST['id_template']);
}

switch ($_REQUEST['accion']) {
	case 'eliminar':
		if ($Template->Delete()) {
			$Pagina->AddInfo('Template eliminado con éxito');
		} else {
			$Pagina->AddError($Template->error);
		}
		break;
	
	case 'test':
		$detected_fields = $Template->LoadDocumento()->GetTags();
		break;
		
	case 'descargar':
		$Template->Download("Template_{$Template->fields['id_template']}", array());
		break;
	
	case 'upload':
		$Template->Fill($_REQUEST, true);
		if ($_FILES['documento']['size'] > 0) {
			$Template->Upload($_FILES['documento']);
		} else {
			$Template->Write();
		}
		break;
}

$Pagina->titulo = __('Templates');
$Pagina->PrintTop($popup);

if ($orden == "") {
	$orden = "id_template";
}

$x_pag = 25;

$b = new Buscador($Sesion, $Template->SearchQuery(), "Template", $desde, $x_pag, $orden);
$b->AgregarEncabezado("id_template", __('N°'), "align=center");
$b->AgregarEncabezado("glosa_template", __('Template'), "align=center");
$b->AgregarEncabezado("tipo", __('Tipo'), "align=left");
$b->AgregarFuncion(__('Opciones'), 'Opciones', "align=right");
$b->color_mouse_over = "#bcff5c";
$b->Imprimir();

function Opciones(& $fila) {
	global $Sesion;

	$boton_test = '<a href="templates.php?accion=test&id_template=' . $fila->fields['id_template'] . '" title="Testear Template">'
		. '<img src="' . Conf::ImgDir() . '/check_nuevo.gif" border="0" alt="Descargar Solicitud" /></a>';
	
	$boton_descargar = '<a href="templates.php?accion=descargar&id_template=' . $fila->fields['id_template'] . '" title="Descargar Template">'
		. '<img src="' . Conf::ImgDir() . '/doc.gif" border="0" alt="Descargar Solicitud" /></a>';
	
	$boton_editar = '<a href="templates.php?id_template=' . $fila->fields['id_template'] . '" title="Editar Template">'
		. '<img src="' . Conf::ImgDir() . '/editar_on.gif" border="0" alt="Descargar Solicitud" /></a>';
	
	$boton_eliminar = '';/* '<a href="javascript:void(0);" onclick="if (confirm(\'¿'. __('Está seguro de eliminar la') . ' ' . __('solicitud de adelanto') . '?\')) EliminaSolicitudAdelanto(' . $fila->fields['id_solicitud_adelanto'] . ');">'
		. '<img src="' . Conf::ImgDir() . '/cruz_roja_nuevo.gif" border="0" alt="Eliminar" /></a>';*/
	
	return "$boton_test $boton_descargar $boton_editar $boton_eliminar";
}

?>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">
	<input type="hidden" name="accion" value="upload" />
<?php if ($Template->Loaded()) { ?>
	<input type="hidden" name="id_template" value="<?php echo $Template->fields['id_template']; ?>" />
<?php } ?>
	<table>
		<tr>
			<td>Nombre template</td>
			<td><input type="text" name="glosa_template" id="glosa_template" value="<?php echo $Template->fields['glosa_template']; ?>" /></td>
		</tr>
		<tr>
			<td>Tipo</td>
			<td>
				<?php echo Html::SelectArray(Template::GetTipos(), "tipo", $Template->fields['tipo'], 'id="tipo"'); ?>
			</td>
		</tr>
		<tr>
			<td>Documento</td>
			<td>
				<input type="hidden" name="MAX_FILE_SIZE" value="2000000">
				<input type="file" name="documento" id="documento" />
			</td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Cargar" /></td>
		</tr>
	</table>
</form>
<?php if (is_array($detected_fields)) { ?>
<h2>Se detectaron los siguientes Campos en el template:</h2>
<pre style="text-align: left"><?php echo print_r($detected_fields); ?></pre>
<?php } ?>
<?php
$Pagina->PrintBottom($popup);
<?php
require_once dirname(__FILE__) . '/../app/conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	}
}

spl_autoload_register('autocargaapp');

$sesion = new Sesion(array('ADM'));
$pagina = new Pagina($sesion);
$pagina->titulo = __('Aviso de actualización');
$pagina->PrintTop();
if ($sesion->usuario->fields['rut'] != '99511620') {
	die('No Autorizado');
}

//se guarda el aviso en un archivo para que todos los clientes de este directorio lo vean
$path = Conf::ServerDir() . '/../aviso.txt';

if (!empty($_POST['opc'])) {
	switch ($_POST['opc']) {
		case 'guardar':
			$data = UtilesApp::utf8izar($_POST['aviso']);
			$data['id'] = uniqid();
			$ret = file_put_contents($path, json_encode($data));
			break;
		case 'eliminar':
			$ret = unlink($path);
			break;
	}
}

$aviso = array();
if (file_exists($path)) {
	$data = json_decode(file_get_contents($path), true);
	$aviso = UtilesApp::utf8izar($data, false);
}

$query = "SELECT codigo_permiso, glosa FROM prm_permisos";
$permisos = $sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="text-align: left">
	Este aviso se mostrará desde ahora hasta que se elimine, y lo verán todos los usuarios con los permisos seleccionados, para todos los estudios que usen esta versión del código.
	<br/>
	<br/>
	<form action="" method="POST">
		<label>Mensaje:<textarea name="aviso[mensaje]"><?php echo $aviso['mensaje']; ?></textarea></label>
		<br/>
		<label>Fecha (hora Chile):<input name="aviso[fecha]" value="<?php echo $aviso['fecha']; ?>"/></label>
		<br/>
		<label>Link detalle:<input name="aviso[link]" value="<?php echo $aviso['link']; ?>"/></label>
		<br/>
		<h3>Mostrar a:</h3>
		<?php foreach ($permisos as $permiso) { ?>
			<label>
				<input type="checkbox" name="aviso[permiso][]" value="<?php echo $permiso['codigo_permiso']; ?>" <?php echo isset($aviso['permiso']) && in_array($permiso['codigo_permiso'], $aviso['permiso']) ? 'checked="checked"' : ''; ?>/>
				<?php echo $permiso['glosa']; ?>
			</label>
			<br/>
		<?php } ?>
		<br/>
		<input type="hidden" id="opc" name="opc" value="guardar"/>
		<button id="btn_guardar">Guardar</button>
		<button id="btn_eliminar">Eliminar</button>
	</form>
</div>
<script type="text/javascript">
	jQuery(function(){
		jQuery('#btn_guardar').click(function(){
			jQuery('#opc').val('guardar');
		});
		jQuery('#btn_eliminar').click(function(){
			jQuery('#opc').val('eliminar');
		});
	});
</script>
<?php
$pagina->PrintBottom();

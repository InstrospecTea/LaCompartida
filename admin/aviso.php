<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$Sesion = new Sesion(array('ADM'));
$Pagina = new Pagina($Sesion);
$Pagina->titulo = __('Aviso de actualización');

if (!$Sesion->usuario->TienePermiso('SADM')) {
	die('No Autorizado');
}

if (!empty($_POST['opc'])) {
	switch ($_POST['opc']) {
		case 'guardar':
			$data = $_POST['aviso'];
			if (!Aviso::Guardar($data)) {
				echo 'No se pudo guardar el aviso';
			}
			break;
		case 'eliminar':
			if(!Aviso::Eliminar()) {
				echo 'No se pudo eliminar el aviso';
			}
			break;
	}
}

$aviso = Aviso::Obtener();
$query = "SELECT codigo_permiso, glosa FROM prm_permisos";
$permisos = $Sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
$Pagina->PrintTop();
?>
<div style="text-align: left">
	Este aviso se mostrará desde ahora hasta que se elimine, y lo verán todos los usuarios con los permisos seleccionados, para todos los estudios que usen esta versión del código.
	<br/>
	<br/>
	<form id="form_aviso" action="" method="POST">
		<label>Mensaje: <textarea name="aviso[mensaje]" rows="6" cols="37"><?php echo $aviso['mensaje']; ?></textarea></label>
		<br/>
		<label>Fecha:
			<input id="fecha" name="aviso[fecha]" value="<?php echo $aviso['fecha']; ?>"/>
			<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha" style="cursor:pointer" />
		</label>
		<br/>
		<label>Hora (Chile): <input id="hora" name="aviso[hora]" value="<?php echo $aviso['hora']; ?>"/></label>
		<br/>
		<label>Link detalle: <input name="aviso[link]" value="<?php echo $aviso['link']; ?>"/></label>
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
		<input type="hidden" id="date" name="aviso[date]" value=""/>
		<input type="hidden" id="opc" name="opc" value="guardar"/>
		<button id="btn_guardar">Guardar</button>
		<button id="btn_eliminar">Eliminar</button>
	</form>
</div>
<script type="text/javascript">
	jQuery(function(){
		Calendar.setup({
			inputField  : "fecha",       // ID of the input field
			ifFormat    : "%d-%m-%Y",     // the date format
			button      : "img_fecha"   // ID of the button
		});

		//si se guarda una fecha con hora, se guarda el timestamp en UTC para mostrarlo en hora local
		jQuery('#form_aviso').submit(function(){
			var fecha = jQuery('#fecha').val();
			var hora = jQuery('#hora').val();
			if(fecha && hora){
				var mf = fecha.match(/(\d+)-(\d+)-(\d+)/);
				var mh = hora.match(/(\d+)\D?(\d+)?\D?(\d+)?/);
				var date = new Date(mf[3], mf[2] - 1, mf[1], mh[1], mh[2] || 0, mh[3] || 0);
				jQuery('#date').val(date.getTime());
			}
		});
		jQuery('#btn_eliminar').click(function(){
			jQuery('#opc').val('eliminar');
		});
	});
</script>
<?php
$Pagina->PrintBottom();

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
<style type="text/css">
.checkbox {
	position: relative;
	display: block;
	margin-top: 10px;
	margin-bottom: 10px;
}

.control-label label{
	display: inline-block;
	margin-bottom: 0;
	padding-top: 7px;
	text-align: right;
}

.form {
	text-align: left;
	width: 100%;
}

.form p {
	font-size: 13px;
}

.form-control {
	display: inline-block;
	position: relative;
	width: 100%;
}

.form-control.fechadiff{
	display: inline-block;
	position: relative;
	width: 90%;
}

.form-group {
	margin-bottom: 15px;
}

#form_aviso {
	display: inline-block;
	margin-left: 15px;
}
</style>
<div class="form">
	<p>Este aviso se mostrará desde ahora hasta que se elimine, y lo verán todos los usuarios con los permisos seleccionados, para todos los estudios que usen esta versión del código.</p>
	<form id="form_aviso" action="" method="POST">
		<input type="hidden" id="date" name="aviso[date]" value=""/>
		<input type="hidden" id="opc" name="opc" value="guardar"/>
		<div class="form-group">
			<label class="control-label" for="fecha">Fecha:</label>
			<input type="text" class="form-control fechadiff" id="fecha" name="aviso[fecha]" value="<?php echo $aviso['fecha']; ?>">
		</div>
		<div class="form-group">
			<label class="control-label" for="hora">Hora (Chile):</label>
			<input type="text" class="form-control" id="hora" name="aviso[hora]" value="<?php echo $aviso['hora']; ?>">
		</div>
		<div class="form-group">
			<label class="control-label" for="link">Link detalle:</label>
			<input type="text" class="form-control" id="link" name="aviso[link]" value="<?php echo $aviso['link']; ?>">
		</div>
		<div class="form-group">
			<label class="control-label" for="titulo">Título:</label>
			<input type="text" class="form-control" id="titulo" name="aviso[titulo]" value="<?php echo $aviso['titulo']; ?>">
		</div>
		<div class="form-group">
			<label class="control-label" for="mensaje">Mensaje:</label>
			<textarea class="form-control" id="mensaje" name="aviso[mensaje]" rows="6" cols="37"><?php echo $aviso['mensaje']; ?></textarea>
		</div>
		<div class="form-group">
			<h3>Mostrar a:</h3>
			<?php foreach ($permisos as $permiso) { ?>
				<div class="checkbox">
					<label>
						<input type="checkbox" name="aviso[permiso][]" value="<?php echo $permiso['codigo_permiso']; ?>" <?php echo isset($aviso['permiso']) && in_array($permiso['codigo_permiso'], $aviso['permiso']) ? 'checked="checked"' : ''; ?>/>
						<?php echo $permiso['glosa']; ?>
					</label>
				</div>
			<?php } ?>
		</div>
		<div class="form-group">
			<button id="btn_guardar">Guardar</button>
			<button id="btn_eliminar">Eliminar</button>
		</div>
	</form>
</div>
<script type="text/javascript">
	jQuery(function(){
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

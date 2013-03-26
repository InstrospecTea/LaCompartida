<?php
require_once dirname(__FILE__) . '/../../app/conf.php';
$sesion = new Sesion(array('ADM'));
$pagina = new Pagina($sesion);
$pagina->titulo = __('Auditoria Correos');
$pagina->PrintTop();
?>
<form id="form_correos" action="<?php echo $_SERVER[PHP_SELF]; ?>" method="post">
	<input type="hidden" name="buscar" value="1"/>
	<table style="width: 90%">
		<tr>
			<td>
				<fieldset class="tb_base" width="100%">
					<legend>Correos</legend>
					<table width="100%" cellspacing="3" cellpadding="3">
						<tbody>
							<tr>
								<td width="35%" align="right" class="cvs">Correo</td>
								<td align="left">
									<input type="text" value="<?php echo $mail; ?>" size="40" id="mail" name="mail" autocomplete="off">
								</td>
							</tr>
							<tr>
								<td align="right" class="cvs">Nombre</td>
								<td align="left">
									<input type="text" value="<?php echo $nombre; ?>" size="40" name="nombre">
								</td>
							</tr>
							<tr>
								<td align="right" class="cvs">Tipo</td>
								<td align="left">
									<?php echo Html::SelectQuery($sesion, 'SELECT id, nombre FROM prm_tipo_correo', 'id_tipo_correo', $id_tipo_correo, 'id="id_tipo_correo"', 'Cualquiera', '20em'); ?>
								</td>
							</tr>
							<tr>
								<td align="right" class="cvs">Enviado</td>
								<td align="left">
									<?php echo Html::SelectArrayDecente(array(0 => 'No', 1 => 'Si'), 'enviado', $enviado, 'id="enviado"', 'Cualquiera', '20em'); ?>
								</td>
							</tr>
							<tr>
								<td align=right class="cvs">
									<?php echo __('Fecha Desde') ?>
								</td>
								<td nowrap align=left class="cvs">
									<input class="fechadiff" type="text" name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
									&nbsp;&nbsp;<?php echo __('Fecha Hasta') ?>
									<input class="fechadiff" type="text" name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
								</td>
							</tr>
							<tr>
								<td>
								</td>
								<td align="left">
									<a class="btn botonizame" id="buscar_correos"  href="#"  icon="find"><?php echo __('Buscar') ?></a>
								</td>
							</tr>
						</tbody>
					</table>
				</fieldset>
			</td>
		</tr>
	</table>
</form>

<?php
if ($buscar) {
	$where = '1';
	if (!empty($mail)) {
		$mail = mysql_real_escape_string($mail);
		$where .= " AND C.mail = '$mail'";
	}
	if (!empty($nombre)) {
		$nombre = strtr(mysql_real_escape_string($nombre), ' ', '%');
		$where .= " AND C.nombre Like '%$nombre%'";
	}
	if ($id_tipo_correo != '') {
		$where .= " AND C.id_tipo_correo = {$id_tipo_correo}";
	}
	if ($enviado != '') {
		$where .= " AND C.enviado = {$enviado}";
	}
	if (!empty($fecha1)) {
		$fecha1 = Utiles::fecha2sql($fecha1);
		$where .= " AND C.fecha >= '{$fecha1}'";
	}
	if (!empty($fecha2)) {
		$fecha2 = Utiles::fecha2sql($fecha2);
		$where .= " AND date_add(C.fecha, interval -1 day) < '{$fecha2}'";
	}

	$query = "SELECT SQL_CALC_FOUND_ROWS C.id_log_correo,
					C.subject,
					C.mensaje,
					C.mail,
					C.nombre,
					TC.nombre tipo_correo,
					if(C.enviado, 'Si', 'No') enviado,
					C.fecha_envio,
					C.fecha AS fecha_creacion,
					C.fecha_modificacion
				FROM log_correo AS C
				LEFT JOIN prm_tipo_correo TC ON TC.id = C.id_tipo_correo
				WHERE $where";

	if (!empty($orden)) {
		$orden = 'C.id_log_correo';
	}
	$x_pag = 20;
	$estilo = 'style="text-align: left;"';
	$b = new Buscador($sesion, $query, 'TipoCorreo', $desde, $x_pag, $orden);
	$b->AgregarEncabezado('nombre', __('Nombre Usuario'), $estilo);
	$b->AgregarEncabezado('subject', __('Subject'), $estilo);
	$b->AgregarEncabezado('tipo_correo', __('Tipo'), $estilo);
	$b->AgregarEncabezado('mail', __('Correo'), $estilo);
	$b->AgregarEncabezado('enviado', __('Enviado'), $estilo);
	$b->AgregarEncabezado('intento_envio', __('Intentos'), $estilo);
	$b->AgregarEncabezado('fecha_envio', __('Fecha Envío'), $estilo);
	$b->AgregarEncabezado('fecha_creacion', __('Fecha Creación'), $estilo);
	$b->AgregarFuncion('', 'acciones', 'align="center nowrap"');
	$b->color_mouse_over = '#bcff5c';
	$b->Imprimir();
}

function acciones($data) {
	$id = $data->fields['id_log_correo'];
	$tpl_btn = '<a class="show-%s" href="#" id="%s"><span class="ui-icon ui-icon-%s"></span></a>';
	$btns = '';
	$btns .= sprintf($tpl_btn, 'mail', "mail_$id", 'mail-open');
	$btns .= sprintf($tpl_btn, 'detail', "detail_$id", 'comment');
	return $btns;
}
?>

<script type="text/javascript">
	jQuery('#buscar_correos').click(function() {
		jQuery('#form_correos').submit();
		return false;
	});
	jQuery('.show-mail').click(function() {
		var id = jQuery(this).attr('id').split('_')[1];
		jQuery('<div/>').load(root_dir + '/admin/auditoria/leer_correo.php?id=' + id).dialog({title: 'Correo', width: 640, height: 400, modal: true});
		return false;
	});
	jQuery('.show-detail').click(function() {
		var id = jQuery(this).attr('id').split('_')[1];
		jQuery('<div/>').load(root_dir + '/admin/auditoria/detalle_correo.php?id=' + id).dialog({title: 'Detalle', width: 640, height: 400, modal: true});
		return false;
	});
</script>

<?php
$pagina->PrintBottom();

<?php
	require_once dirname(__FILE__) . '/../conf.php';

	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);
	$pagina->titulo = __('Administración de Usuarios');

	$esRut = strtolower(Conf::GetConf($sesion, 'NombreIdentificador')) == 'rut';

	if ($desde == '') {
		$desde = 0;
	}

	if ($x_pag == '') {
		$x_pag = 20;
	}

	if ($orden == '') {
		$orden = 'apellido1';
	}

	if ($opc == 'eliminado') {
		$pagina->AddInfo(__('Usuario') . ' ' . __('eliminado con éxito'));
	} else if ($opc == 'cancelar') { // Opción cancelar
		$pagina->Redirect('usuario_paso1.php');
	} else if ($opc == 'edit') { // Opción editar
		if ($cambiar_alerta_diaria == 'on') {
			if ($alerta_diaria == 'on') {
				$alerta_diaria = 1;
			} else {
				$alerta_diaria = 0;
			}
			if ($retraso_max == '') {
				$retraso_max = 0;
			}
		}

		if ($cambiar_restriccion_diario == 'on') {
			if ($restriccion_diario == '') {
				$restriccion_diario = 0;
			}
		}

		if ($cambiar_alerta_semanal == 'on') {
			if ($alerta_semanal == 'on') {
				$alerta_semanal = 1;
			} else {
				$alerta_semanal = 0;
			}
			if ($restriccion_max == '') {
				$restriccion_max = 0;
			}
			if ($restriccion_min == '') {
				$restriccion_min = 0;
			}
		}

		if ($cambiar_restriccion_mensual == 'on') {
			if ($restriccion_mensual == '') {
				$restriccion_mensual = 120;
			}
		}

		if ($cambiar_dias_ingreso_trabajo == 'on') {
			if ($dias_ingreso_trabajo == '') {
				$dias_ingreso_trabajo = 7;
			}
		}

		//Actualizar alerta de Usuario
		$datos = compact('alerta_diaria', 'alerta_semanal', 'retraso_max', 'restriccion_max', 'restriccion_min', 'restriccion_mensual', 'dias_ingreso_trabajo', 'restriccion_diario');
		$query3 = 'SELECT id_usuario FROM usuario';
		$usuarios = $sesion->pdodbh->query($query3);

		$usuario = new UsuarioExt($sesion, $rut_limpio);
		while ($usr = $usuarios->fetch(PDO::FETCH_ASSOC)) {
			$usuario->LoadId($usr['id_usuario']);
			$usuario->Guardar($datos, $pagina);
		}

		if ($alerta_diaria == 0) {
			$alerta_diaria = '';
		}
		if ($alerta_semanal == 0) {
			$alerta_semanal = '';
		}
	}

	$pagina->PrintTop();
	$tooltip_text = __('Para agregar un nuevo usuario ingresa su ' . Conf::GetConf($sesion, 'NombreIdentificador') . ' aquí.');
?>

<style type="text/css">
	@import "https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css";

	#tablapermiso {
		border-spacing: 0;
		border-collapse: collapse;
	}

	#tablapermiso th {
		font-size: 10px;
	}

	.dataTables_paginate {
		clear: both;
		margin: -20px 350px 15px 0;
		width: 370px;
		vertical-align: middle;
	}

	.dttnombres, .dttactivo {
		text-align: left;
		font-size: 10px;
		white-space: nowrap;
	}

	.dttpermisos .DataTables_sort_icon, .dttactivo .DataTables_sort_icon {
		display: none;
	}

	.activo, .usuarioinactivo, .usuarioactivo {
		float: left;
		display: inline;
	}

	.inactivo {
		opacity: 0.4;
	}

	.inactivo td {
		background: #F0F0F0;
	}

	#contienefiltro {
		display: inline-block;
		margin-bottom: -8px;
	}

	#tablapermiso_paginate .fg-button {
		padding: 0 5px;
	}

	#tablapermiso_paginate .first, #tablapermiso_paginate .last {
		display: none;
	}

	#tablapermiso  tbody tr.odd {
		background-color: #fff !important;
	}

	#tablapermiso  tbody tr.even {
		background-color: #EFE !important;
	}

	td.sorting_1 {
		background: transparent !important;
	}
</style>

<script src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js"></script>

<script type="text/javascript">
	jQuery(document).ready(function() {
		var oTable = jQuery('#tablapermiso').dataTable({
			"bJQueryUI": true,
			"bDeferRender": true,
			"bDestroy": true,
			"oLanguage": {
				"sProcessing": "Procesando...",
				"sLengthMenu": "Mostrar _MENU_ registros",
				"sZeroRecords": "No se encontraron resultados",
				"sInfo": "Mostrando desde _START_ hasta _END_ de _TOTAL_ registros",
				"sInfoEmpty": "Mostrando desde 0 hasta 0 de 0 registros",
				"sInfoFiltered": "(filtrado de _MAX_ registros en total)",
				"sInfoPostFix": "",
				"sSearch": "<b>Buscar Nombre</b>",
				"sUrl": "",
				"oPaginate": {
					"sPrevious": "anterior",
					"sNext": "siguiente"
				}
			},
			"bFilter": true,
			"aoColumns": [
				{"mDataProp": "rut"},
				{"mDataProp": "id_usuario"},
				{"mDataProp": "nombrecompleto"},
				{"mDataProp": "ADM"},
				{"mDataProp": "DAT"},
				{"mDataProp": "COB"},
				{"mDataProp": "EDI"},
				{"mDataProp": "LEE"},
				{"mDataProp": "OFI"},
				{"mDataProp": "PRO"},
				{"mDataProp": "REP"},
				{"mDataProp": "REV"},
				{"mDataProp": "SEC"},
				{"mDataProp": "SOC"},
				{"mDataProp": "TAR"},
				{"mDataProp": "RET"},
				{"mDataProp": "ACT"}
			],
			"aoColumnDefs": [
				{"sClass": "dttnombres", "aTargets": [2]},
				{"fnRender": function(o, val) {
					var botones = '';
					botones += "<div style='float:left;display:inline;' id='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'>";
					if (val == 1) {
						botones += "<input class='permiso usuarioactivo' type='image' src='https://static.thetimebilling.com/images/lightbulb.png' alt='ACTIVO' title='Usuario Activo'";
					} else {
						botones += "<input class='permiso usuarioinactivo' type='image' src='https://static.thetimebilling.com/images/lightbulb_off.png' alt='INACTIVO' title='Usuario Inactivo'";
					}
					return botones + " rel='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'/></div>&nbsp;<a style='display:inline;position: relative;top: 0;right: 0;' href='usuario_paso2.php?rut=" + o.aData['rut'] + "' title='Editar usuario'><img border=0 src='https://static.thetimebilling.com/images/ver_persona_nuevo.gif' alt='Editar' /></a>";
				}, "sClass": "dttactivo", "bUseRendered": false, "aTargets": [16]},
				{"fnRender": function(o, val) {
					if (val == 1) {
						return "<div class='permiso on' id='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'><input class='permiso' type='image' src='https://static.thetimebilling.com/images/check_nuevo.gif' alt='OK' rel='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'/></div>";
					} else {
						return "<div class='permiso off' id='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'><input class='permiso'  type='image' src='https://static.thetimebilling.com/images/cruz_roja_nuevo.gif' rel='" + o.aData['id_usuario'] + ';' + o.mDataProp + "' alt='NO' /></div>";
					}
				}, "bUseRendered": false, "sClass": "dttpermisos", "aTargets": [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]},
				{"bVisible": false, "aTargets": [0, 1]}
			],
			"fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
				if (aData['ACT'] == "0") {
					jQuery(nRow).addClass('inactivo').attr('title', 'Usuario Inactivo');
				}
			},
			"sAjaxSource": "../interfaces/ajax/usuarios_ajax.php",
			"iDisplayLength": 25,
			"sDom": '<"top"flp>t<"bottom"i>',
			"aLengthMenu": [[25, 50, 100, 200, -1], [25, 50, 100, 200, "Todo"]],
			"sPaginationType": "full_numbers",
			"aaSorting": [[2, "asc"]]
		});

		jQuery('#contienefiltro').append(jQuery('#tablapermiso_filter'));

		oTable.fnFilter('1', 16);

		jQuery('#activo').click(function() {
			if (jQuery(this).is(':checked')) {
				oTable.fnFilter('1', 16, 0, 1);
			} else {
				oTable.fnFilter('', 16, 0, 1);
			}
		});

		jQuery('input.permiso').live('click', function() {
			var self = jQuery(this);
			var src = self.attr('src');
			var dato = self.attr('rel').split(';');
			var alt = '';
			var accion = '';

			self.attr('src', 'https://static.thetimebilling.com/images/ico_loading.gif');

			switch (self.attr('alt')) {
				case 'OK':
					accion = 'revocar';
					alt = 'NO';
					break;
				case 'NO':
					accion = 'conceder';
					alt = 'OK';
					break;
				case 'ACTIVO':
					accion = 'desactivar';
					alt = 'INACTIVO';
					break;
				case 'INACTIVO':
					accion = 'activar';
					alt = 'ACTIVO';
					break;
			}

			jQuery.post(
				'../interfaces/ajax/permiso_ajax.php',
				{
					accion: accion,
					id_usuario: dato[0],
					permiso: dato[1]
				},
				function(data) {
					data = jQuery.parseJSON(data);
					if (data.error != '') {
						alert(data.error);
						self.attr('src', src);
					} else {
						self.attr('src', data.img);
						self.attr('alt', alt);
						if (alt == 'ACTIVO') {
							self.closest('tr').removeClass('inactivo');
						} else if (alt == 'INACTIVO') {
							self.closest('tr').addClass('inactivo');
						}
					}
				}
			);
		});

		jQuery('.descargaxls').click(function() {
			var activo = 0;
			if (jQuery('#activo').is(':checked')) {
				activo = 1;
			}

			var tipoxls = jQuery(this).attr('rel');

			nom = jQuery('#nombre').val();
			if (tipoxls == 'xls')
			{
				destino = '../interfaces/usuarios_xls.php?act=' + activo + '&nombre=' + nom;
			}
			else if (tipoxls == 'xls_vacacion')
			{
				destino = '../interfaces/usuarios_xls.php?act=' + activo + '&nombre=' + nom + '&vacacion=true';
			}
			else if (tipoxls == 'xls_modificaciones')
			{
				destino = '../interfaces/usuarios_xls.php?act=' + activo + '&nombre=' + nom + '&modificaciones=true';
			}
			top.window.location.href = destino;
		});

		jQuery('#costos').click(function() {
			var theform = jQuery(this).parents('form:first');
			theform.submit();
		});

		jQuery('#btnbuscar').click(function() {
			jQuery(this).parents('form:first').attr('action', 'usuario_paso1.php?buscar=1').submit();
		});
	});

	function Listar(form, from) {
		var nom = document.act.nombre.value;
		var activo = 0;

		if ($('activo').checked == true) {
			activo = 1;
		}

		switch (from) {
			case 'buscar':
				form.action = 'usuario_paso1.php?buscar=1';
				break;
			case 'xls':
				form.action = '../interfaces/usuarios_xls.php?act=' + activo + '&nombre=nom';
				break;
			case 'xls_vacacion':
				form.action = '../interfaces/usuarios_xls.php?act=' + activo + '&nombre=nom&vacacion=true';
				break;
			case 'xls_modificaciones':
				form.action = '../interfaces/usuarios_xls.php?act=' + activo + '&nombre=nom&modificaciones=true';
				break;
			default:
				return false;
		}

		form.submit();
	}

	function ModificaTodos(from) {
		if (from.cambiar_alerta_diaria.checked == true) {
			var alerta_diaria = from.alerta_diaria.checked == true ? '\n Alerta diaria:          SI' : '\n Alerta diaria:          NO';
			var retraso_max = '\n Restraso Max:        ';
		} else {
			var alerta_diaria = '';
			var retraso_max = '';
		}

		if (from.cambiar_alerta_semanal.checked == true) {
			var alerta_semanal = from.alerta_semanal.checked == true ? '\n Alerta semanal:     SI' : '\n Alerta semanal:     NO';
			var restriccion_min = '\n Min HH:                 ';
			var restriccion_max = '\n Max HH:                 ';
		} else {
			var alerta_semanal = '';
			var restriccion_min = '';
			var restriccion_max = '';
		}

		var restriccion_mensual = from.cambiar_restriccion_mensual.checked == true ? '\n Min HH mensual: ' : '';
		var dias_ingreso_trabajo = from.cambiar_dias_ingreso_trabajo.checked == true ? '\n Max dias ingreso:  ' : '';

		if (confirm(alerta_diaria + alerta_semanal + retraso_max + from.retraso_max.value + restriccion_min + from.restriccion_min.value + restriccion_max + from.restriccion_max.value + restriccion_mensual + from.restriccion_mensual.value + dias_ingreso_trabajo + from.dias_ingreso_trabajo.value + '\n\n ¿Desea cambiar los restricciones y alertas de todos los usuarios?')) {
			from.action = "usuario_paso1.php";
			from.submit();
		}
	}

	function Cancelar(form) {
		form.opc.value = 'cancelar';
		form.submit();
	}

	function DisableColumna(from, valor, text) {
		switch (text) {
			case 'alerta_diaria':
				var Input1 = $('alerta_diaria');
				var Input2 = $('retraso_max');
				var check = $(valor);

				if (check.checked) {
					Input1.disabled = false;
					Input2.disabled = false;
					Input2.style.background = "#FFFFFF";
				} else {
					Input1.checked = false;
					Input1.disabled = true;
					Input2.value = '';
					Input2.disabled = true;
					Input2.style.background = "#EEEEEE";
				}
				break;
			case 'alerta_semanal':
				var Input1 = $('alerta_semanal');
				var Input2 = $('restriccion_min');
				var Input3 = $('restriccion_max');
				var check = $(valor);

				if (check.checked) {
					Input1.disabled = false;
					Input2.disabled = false;
					Input3.disabled = false;
					Input2.style.background = "#FFFFFF";
					Input3.style.background = "#FFFFFF";
				} else {
					Input1.checked = false;
					Input1.disabled = true;
					Input2.value = '';
					Input2.disabled = true;
					Input3.value = '';
					Input3.disabled = true;
					Input2.style.background = "#EEEEEE";
					Input3.style.background = "#EEEEEE";
				}
				break;
			case 'alerta_mensual':
				var Input1 = $('restriccion_mensual');
				var check = $(valor);

				if (check.checked) {
					Input1.disabled = false;
					Input1.style.background = "#FFFFFF";
				} else {
					Input1.value = '';
					Input1.disabled = true;
					Input1.style.background = "#EEEEEE"
				}
				break;
			case 'dias_ingreso':
				var Input1 = $('dias_ingreso_trabajo');
				var check = $(valor);

				if (check.checked) {
					Input1.disabled = false;
					Input1.style.background = "#FFFFFF";
				} else {
					Input1.value = '';
					Input1.disabled = true;
					Input1.style.background = "#EEEEEE";
				}
				break;
			case 'restriccion_diario':
				var Input1 = $('restriccion_diario');
				var check = $(valor);

				if (check.checked) {
					Input1.disabled = false;
					Input1.style.background = "#FFFFFF";
				} else {
					Input1.value = '';
					Input1.disabled = true;
					Input1.style.background = "#EEEEEE";
				}
				break;
		}
	}
</script>

<table width="96%" align="left">
	<tr>
		<td width="20">&nbsp;</td>
		<td valign="top">
			<table class="info" style="width:100%">
				<tr>
					<td colspan="2" style="text-align:left">
						<b>Administraci&oacute;n de cupos para usuarios activos en el sistema:</b>
					</td>
				</tr>
				<tr>
					<td width="20">&nbsp;</td>
					<td style="text-align:left">
						Estimado <?php echo $sesion->usuario->fields['nombre'] . ' ' . $sesion->usuario->fields['apellido1']; ?>, a continuacion se detalla su cupo actual de usuarios contratados en el sistema.  
					</td>
				</tr>
				<tr>
					<td width="20">&nbsp;</td>
					<td style="text-align:left">
						<ul>
							<li>Usuarios activos con perfil "Profesional": <?php echo Conf::GetConf($sesion, 'CupoUsuariosProfesionales'); ?></li>
							<li>Usuarios activos con perfil "Administrativos": <?php echo Conf::GetConf($sesion, 'CupoUsuariosAdministrativos'); ?></li>
						</ul>
					</td>
				</tr>
				<tr>
					<td width="20">&nbsp;</td>
					<td style="text-align:left">
						Si desea aumentar su cupo debe contactarse con <a href="mailto:areacomercial@lemontech.cl">areacomercial@lemontech.cl</a> o en su defecto puede desactivar usuarios para habilitar cupos.
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td width="20">&nbsp;</td>
		<td valign="top">
			<form action="usuario_paso2.php" method="post" <?php if ($esRut) { echo 'onsubmit="return ValidarRut(this.rut.value, this.dv_rut.value);"'; } ?> >
				<br class="clearfix"/>
				<br>
				<table  width="100%" class="tb_base">
					<tr>
						<td valign="top" class="subtitulo" align="left" colspan="2">
							<?php echo __('Ingrese ') . Conf::GetConf($sesion, 'NombreIdentificador') . __(' del usuario'); ?>:
							<hr class="subtitulo_linea_plomo"/>
						</td>
					</tr>
					<tr>
						<td valign="top" class="texto" align="right">
							<strong><?php echo Conf::GetConf($sesion, 'NombreIdentificador'); ?></strong>
						</td>
						<td valign="top" class="texto" align="left">
							<?php if ($esRut) { ?>
								<input type="text" name="rut" value="" size="10" onMouseover="ddrivetip('<?php echo $tooltip_text ?>')" onMouseout="hideddrivetip()" />-<input type="text" name="dv_rut" value="" maxlength=1 size="1" />
							<?php } else { ?>
								<input type="text" name="rut" value="" size="17" onMouseover="ddrivetip('<?php echo $tooltip_text ?>')" onMouseout="hideddrivetip()" />
							<?php } ?>
							&nbsp;&nbsp;&nbsp;
							<?php if ($sesion->usuario->fields['id_visitante'] == 0) { ?>
								<input type="submit" class="botonizame" name="boton" value="<?php echo __('Aceptar'); ?>" />
							<?php } else { ?>
								<input type="button" class="botonizame" name="boton" value="<?php echo __('Aceptar'); ?>" onclick="alert('Usted no tiene derecho para agegar un usuario nuevo');" />
							<?php } ?>
						</td>
					</tr>
				</table>
				<br class="clearfix"/>
			</form>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>
			<table width="100%" class="tb_base">
				<tr>
					<td>
						<table width="100%">
							<tr>
								<td valign="top" class="subtitulo" align="left" colspan="2">
									<?php echo __('Modificacion de Datos para todos los usuarios ') ?>:
									<hr class="subtitulo_linea_plomo"/>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<form name="form_usuario" method="post" enctype="multipart/form-data">
							<input type="hidden" name="opc" value="edit" />
							<fieldset class="table_blanco">
								<legend><?php echo __('Restricciones y alertas') ?></legend>
								<table width="100%">
									<tr>
										<td width=38% align="right"></td>
										<td width=10% align="right"></td>
										<td width=15% align="left"></td>
										<td width=20% align="right"></td>
										<td width=17% align="center">
											Cambiar valores
										</td>
									</tr>
									<tr>
										<td align="right">
											<label for="alerta_diaria"><?php echo __('Alerta Diaria') ?></label> <input type="checkbox" id="alerta_diaria" name="alerta_diaria" <?php echo $cambiar_alerta_diaria == 'on' ? '' : disabled ?> <?php echo $alerta_diaria != '' ? "checked" : "" ?> />
										</td>
										<td align="right">
											<?php echo __('Retraso max.') ?>
										</td>
										<td align="left">
											<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $retraso_max > 0 ? $retraso_max : '' ?>" id="retraso_max" name="retraso_max" <?php echo $cambiar_alerta_diaria == 'on' ? '' : disabled ?> />
										</td>
										<td align="left"></td>
										<td align="center">
											<input type="checkbox" id="cambiar_alerta_diaria" name="cambiar_alerta_diaria" <?php echo $cambiar_alerta_diaria != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'alerta_diaria');"/>
										</td>
									</tr>
									<tr>
										<td align="right">
											&nbsp;
										</td>
										<td align="right">
											<?php echo __('Min HH.') ?>
										</td>
										<td align="left">
											<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_diario > 0 ? $restriccion_diario : '' ?>" id="restriccion_diario" name="restriccion_diario" <?php echo $cambiar_restriccion_diario == 'on' ? '' : disabled ?> />
										</td>
										<td align="left"></td>
										<td align="center">
											<input type="checkbox" id="cambiar_restriccion_diario" name="cambiar_restriccion_diario" <?php echo $cambiar_restriccion_diario != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'restriccion_diario');"/>
										</td>
									</tr>
									<tr>
										<td align="right">
											<label for="alerta_semanal"><?php echo __('Alerta Semanal') ?></label> <input type="checkbox" id="alerta_semanal" name="alerta_semanal" <?php echo $cambiar_alerta_semanal == 'on' ? '' : disabled ?> <?php echo $alerta_semanal != '' ? "checked" : "" ?> />
										</td>
										<td align="right">
											<?php echo __('Mín. HH') ?>
										</td>
										<td align="left">
											<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_min > 0 ? $restriccion_min : '' ?>" id="restriccion_min" name="restriccion_min" <?php echo $cambiar_alerta_semanal == 'on' ? '' : disabled ?> />
										</td>
										<td align="left">
											<?php echo __('Máx. HH') ?> <input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_max > 0 ? $restriccion_max : '' ?>" id="restriccion_max" name="restriccion_max" <?php echo $cambiar_alerta_semanal == 'on' ? '' : disabled ?> />
										</td>
										<td align="center">
											<input type="checkbox" id="cambiar_alerta_semanal" name="cambiar_alerta_semanal" <?php echo $cambiar_alerta_semanal != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'alerta_semanal');"/>
										</td>
									</tr>
									<tr>
										<td align="right">
											<label for="restriccion_mensual"><?php echo __('Mínimo mensual de horas') ?></label>
										</td>
										<td>&nbsp;</td>
										<td align="left">
											<input type="text" size="10" style="background-color: #EEEEEE;" <?php echo Html::Tooltip("Para no recibir alertas mensuales ingrese 0.") ?> value="<?php echo $restriccion_mensual > 0 ? $restriccion_mensual : '' ?>" id="restriccion_mensual" name="restriccion_mensual" <?php echo $cambiar_restriccion_mensual == 'on' ? '' : disabled ?> />
										</td>
										<td align="left"></td>
										<td align="center">
											<input type="checkbox" id="cambiar_restriccion_mensual" name="cambiar_restriccion_mensual" <?php echo $cambiar_restriccion_mensual != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'alerta_mensual');" />
										</td>
									</tr>
									<tr>
										<td align="right">
											<label for="dias_ingreso_trabajo"><?php echo __('Plazo máximo (en días) para ingreso de trabajos') ?></label>
										</td>
										<td>&nbsp;</td>
										<td align="left">
											<input type="text" size="10" style="background-color: #EEEEEE;" value="<?php echo $dias_ingreso_trabajo > 0 ? $dias_ingreso_trabajo : '' ?>" id="dias_ingreso_trabajo" name="dias_ingreso_trabajo" <?php echo $cambiar_dias_ingreso_trabajo == 'on' ? '' : disabled ?> />
										</td>
										<td align="left"></td>
										<td align="center">
											<input type="checkbox" id="cambiar_dias_ingreso_trabajo" name="cambiar_dias_ingreso_trabajo" <?php echo $cambiar_dias_ingreso_trabajo != '' ? "checked" : "" ?> onclick="DisableColumna(this.form, this, 'dias_ingreso');"/>
										</td>
									</tr>
								</table>
							</fieldset>
							<fieldset class="table_blanco">
								<legend><?php echo __('Guardar datos'); ?></legend>
								<table width="100%">
									<tr><td align="center">
											<?php
											if ($sesion->usuario->fields['id_visitante'] == 0)
												echo "<input type=\"button\" value=\"" . __('Guardar') . "\" class='botonizame' onclick=\"ModificaTodos(this.form);\"  /> &nbsp;&nbsp;";
											else
												echo "<input type=\"button\" value=\"" . __('Guardar') . "\" class='botonizame' onclick=\"alert('Usted no tiene derecho para modificar estos valores.');\" /> &nbsp;&nbsp;";
											?>
											<input type="button" value="<?php echo __('Cancelar') ?>" onclick="Cancelar(this.form);" class='botonizame' />
										</td></tr>
								</table>
							</fieldset>
						</form>
					</td>
				</tr>
			</table>
			<br/>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>
			<table width="100%" class="tb_base">
				<tr>
					<td valign="top" class="subtitulo" align="left" colspan="2">
						<?php echo __('Lista de Usuarios') ?>:
						<hr class="subtitulo_linea_plomo"/>
					</td>
				</tr>
				<tr>
					<td valign="top" align="left" colspan="2"><img src="https://files.thetimebilling.com/templates/default/img/pix.gif" border="0" width="1" height="10"></td>
				</tr>
				<tr>
					<td valign="top" align="center" style="white-space:nowrap">
						<form name="act"  method="post">
							<input type="checkbox" name="activo" id="activo" <?php if (!$activo) echo 'value="1" checked="checked"'; ?> />s&oacute;lo activos &nbsp;&nbsp;&nbsp;
							<span id="contienefiltro"></span>
							&nbsp;&nbsp;
							&nbsp; <a href="#" id="btnbuscar" style="display:none;" class="u1 botonizame" icon="ui-icon-search" rel="buscar">Buscar</a>
							&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel" rel="xls">Descargar Listado</a>
							&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel" rel="xls_vacacion">Descargar Vacaciones</a>
							&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel" rel="xls_modificaciones">Descargar Modificaciones</a>
						</form>
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<br /><?php echo __('Para buscar ingrese el nombre del usuario o parte de él.') ?>
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<br />
						<table id="tablapermiso">
							<thead>
								<tr>
									<td class="encabezado">RUT</td>
									<th>ID</th>
									<th>Nombre</th>
									<th>Admin</th>
									<th>Admin<br>Datos</th>
									<th width="23">Cobranza</th>
									<th>Editar<br/>Biblioteca</th>
									<th>Lectura</th>
									<th>Oficina</th>
									<th>Profesional</th>
									<th>Reportes</th>
									<th>Revisión</th>
									<th>Secretaría</th>
									<th>Socio</th>
									<th>Tarifa</th>
									<th>Retribuciones</th>
									<th width="25">Activo</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<?php if (Conf::GetConf($sesion, 'ReportesAvanzados')) { ?>
		<tr>
			<td></td>
			<td>
				<table width="100%">
					<tr>
						<td valign="top" class="subtitulo" align="left" colspan="2">
							<?php echo __('Costo por usuario'); ?>:
							<hr class="subtitulo"/>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<a id="costos" class="botonizame" icon="ui-icon-search" style="margin:auto;width:230px;" href="<?php echo Conf::RootDir() . '/app/interfaces/costos.php'; ?>">
								<?php echo __('Editar costo mensual por usuario'); ?>
							</a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	<?php } ?>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
</table>

<?php
	function Opciones(& $fila) {
		global $sesion;
		if (Conf::GetConf($sesion, 'UsaDisenoNuevo')) {
			return "<a href='usuario_paso2.php?rut={$fila->fields['rut']}' title='Editar usuario'><img border='0' src='" . Conf::ImgDir() . "/ver_persona_nuevo.gif' alt='Editar' /></a>";
		} else {
			return "<a href='usuario_paso2.php?rut={$fila->fields['rut']}' title='Editar usuario'><img border='0' src='" . Conf::ImgDir() . "/ver_persona.gif' alt='Editar' /></a>";
		}
	}

	function PrintCheck(&$fila) {
		global $sesion, $permisos;
		static $i = 0;

		if ($i == $permisos->num) {
			$i = 0;
		}

		$permiso = $permisos->Get($i);
		$permiso = $permiso->fields['codigo_permiso'];

		$query = "SELECT COUNT(*) FROM usuario_permiso WHERE id_usuario='{$fila->fields['id_usuario']}' AND codigo_permiso='{$permiso}'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$i++;
		list($count) = mysql_fetch_array($resp);
		if (Conf::GetConf($sesion, 'UsaDisenoNuevo')) {
			if ($count > 0) {
				return "<div class='permiso on' id='" . $fila->fields['id_usuario'] . ';' . $permiso . "'><input class='permiso' type='image' src='" . Conf::ImgDir() . "/check_nuevo.gif' alt='OK' rel='" . $fila->fields['id_usuario'] . ';' . $permiso . "'/></div>";
			} else {
				return "<div class='permiso off' id='" . $fila->fields['id_usuario'] . ';' . $permiso . "'><input class='permiso'  type='image' src='" . Conf::ImgDir() . "/cruz_roja_nuevo.gif' rel='" . $fila->fields['id_usuario'] . ';' . $permiso . "' alt='NO' /></div>";
			}
		} else {
			if ($count > 0) {
				return "<img src=" . Conf::ImgDir() . "/check.gif alt='OK' />";
			} else {
				return "<img src=" . Conf::ImgDir() . "/cruz_roja.gif alt='NO' />";
			}
		}
	}

	function PrintActivo(&$fila) {
		global $sesion;

		if (Conf::GetConf($sesion, 'UsaDisenoNuevo')) {
			if ($fila->fields['activo']) {
				return "<img src=" . Conf::ImgDir() . "/check_nuevo.gif alt='OK' />";
			} else {
				return "<img src=" . Conf::ImgDir() . "/cruz_roja_nuevo.gif alt='NO' />";
			}
		} else {
			if ($fila->fields['activo']) {
				return "<img src=" . Conf::ImgDir() . "/check.gif alt='OK' />";
			} else {
				return "<img src=" . Conf::ImgDir() . "/cruz_roja.gif alt='NO' />";
			}
		}
	}

	$pagina->PrintBottom();

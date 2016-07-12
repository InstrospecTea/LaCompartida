(function ($) {
	function actionButtons(o, val) {
		var botones = '';
		botones += "<div style='float:left;display:inline;' id='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'>";
		if (val == 1) {
			botones += "<input class='permiso usuarioactivo' type='image' src='https://static.thetimebilling.com/images/lightbulb.png' alt='ACTIVO' title='Usuario Activo'";
		} else {
			botones += "<input class='permiso usuarioinactivo' type='image' src='https://static.thetimebilling.com/images/lightbulb_off.png' alt='INACTIVO' title='Usuario Inactivo'";
		}
		return botones + " rel='" + o.aData['id_usuario'] + ';' + o.mDataProp + "' data-contracts='" + o.aData['total_contracts'] + "' data-clientscontracts='" + o.aData['total_clients_contracts'] + "' data-matterscontracts='" + o.aData['total_matters_contracts'] + "'/></div>\n\&nbsp;<a style='display:inline;position: relative;top: 0;right: 0;' href='usuario_paso2.php?rut=" + o.aData['rut'] + "' title='Editar usuario'><img border=0 src='https://static.thetimebilling.com/images/ver_persona_nuevo.gif' alt='Editar' /></a>";

	}

	function permissionButton(o, val) {
		if (val == 1) {
			return "<div class='permiso on' id='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'><input class='permiso' type='image' src='https://static.thetimebilling.com/images/check_nuevo.gif' alt='OK' rel='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'/></div>";
		} else {
			return "<div class='permiso off' id='" + o.aData['id_usuario'] + ';' + o.mDataProp + "'><input class='permiso'  type='image' src='https://static.thetimebilling.com/images/cruz_roja_nuevo.gif' rel='" + o.aData['id_usuario'] + ';' + o.mDataProp + "' alt='NO' /></div>";
		}
	}
	var oTable = $('#tablapermiso').dataTable({
		"bJQueryUI": true,
		"bDeferRender": true,
		"bDestroy": true,
		"oLanguage": {
			"sProcessing": __("Procesando..."),
			"sLengthMenu": __("Mostrar _MENU_ registros"),
			"sZeroRecords": __("No se encontraron resultados"),
			"sInfo": __("Mostrando desde _START_ hasta _END_ de _TOTAL_ registros"),
			"sInfoEmpty": __("Mostrando desde 0 hasta 0 de 0 registros"),
			"sInfoFiltered": __("(filtrado de _MAX_ registros en total)"),
			"sInfoPostFix": "",
			"sSearch": __("<b>Buscar Nombre</b>"),
			"sUrl": "",
			"oPaginate": {
				"sPrevious": __("anterior"),
				"sNext": __("siguiente")
			}
		},
		"bFilter": true,
		"aoColumns": [
			{"mDataProp": "rut"},
			{"mDataProp": "id_usuario"},
			{"mDataProp": "nombrecompleto"},
			{"mDataProp": "username"},
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
			{
				"bVisible": false,
				"aTargets": [0, 1]
			},
			{
				"sClass": "dttnombres",
				"aTargets": [2, 3]
			},
			{
				"fnRender": function (o, val) {
					return permissionButton(o, val);
				},
				"bUseRendered": false,
				"sClass": "dttpermisos",
				"aTargets": [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]
			},
			{
				"fnRender": function (o, val) {
					return actionButtons(o, val);
				},
				"sClass": "dttactivo",
				"bUseRendered": false,
				"aTargets": [17]
			}
		],
		"fnRowCallback": function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
			if (aData['ACT'] == "0") {
				jQuery(nRow).addClass('inactivo').attr('title', __('Usuario Inactivo'));
			}
		},
		"sAjaxSource": "../interfaces/ajax/usuarios_ajax.php",
		"iDisplayLength": 25,
		"sDom": '<"top"flp>t<"bottom"i>',
		"aLengthMenu": [
			[25, 50, 100, 200, -1],
			[25, 50, 100, 200, __("Todo")]
		],
		"sPaginationType": "full_numbers",
		"aaSorting": [[2, "asc"]]
	});
	jQuery('#contienefiltro').append(jQuery('#tablapermiso_filter'));
	oTable.fnFilter('1', 17);
	jQuery('#activo').click(function () {
		if (jQuery(this).is(':checked')) {
			oTable.fnFilter('1', 17, 0, 1);
		} else {
			oTable.fnFilter('', 17, 0, 1);
		}
	});

	jQuery('input.permiso').live('click', function () {
		var self = jQuery(this);
		var src = self.attr('src');
		var dato = self.attr('rel').split(';');
		var alt = '';
		var accion = '';
		var continuar = true;
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

		if (accion == 'desactivar') {
			var confirm_str = __('Atención') + ':\n\n' + __('Se desactivará al usuario seleccionado');
			var total_contracts = parseInt(self.attr('data-contracts'));
			var total_clients_contracts = parseInt(self.attr('data-clientscontracts'));
			var total_matters_contracts = parseInt(self.attr('data-matterscontracts'));
			if (total_contracts != 0) {
				confirm_str += ', ' + __('el cual está asociado a') + ' ' + total_contracts + ' ' + __('acuerdos comerciales') + ' ' + __('como') + ' ' + __('Encargado Comercial');
				confirm_str += '\n';
				if (total_clients_contracts > 0) {
					confirm_str += '\n' + __('Clientes') + ': ' + total_clients_contracts;
				}

				if (total_matters_contracts > 0) {
					confirm_str += '\n' + __('Asuntos') + ': ' + total_matters_contracts;
				}
			}

			confirm_str += '\n\n¿' + __('Desea continuar') + '?';
			if (!confirm(confirm_str)) {
				continuar = false;
			}
		}

		if (continuar) {
			self.attr('src', 'https://static.thetimebilling.com/images/ico_loading.gif');
			jQuery.post(
				'../interfaces/ajax/permiso_ajax.php',
				{
					accion: accion,
					id_usuario: dato[0],
					permiso: dato[1]
				},
			function (data) {
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
		}

	});
	jQuery('.descargaxls').click(function () {
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
	jQuery('#costos').click(function () {
		var theform = jQuery(this).parents('form:first');
		theform.submit();
	});
	jQuery('#btnbuscar').click(function () {
		jQuery(this).parents('form:first').attr('action', 'usuario_paso1.php?buscar=1').submit();
	});
	
	$('#form_usuario').on('submit', function () {
		if ($('#cambiar_alerta_diaria').is(':checked')) {
			var alerta_diaria = '\n ' + __('Alerta diaria') + ':          ' + ($('#alerta_diaria').is(':checked') ? __('SI') : __('NO'));
			var retraso_max = '\n Restraso Max:        ' + $('#retraso_max').val();
		} else {
			var alerta_diaria = '';
			var retraso_max = '';
		}

		if ($('#cambiar_alerta_semanal').is(':checked')) {
			var alerta_semanal = '\n ' + __('Alerta semanal') + ':     ' + ($('#alerta_semanal').is(':checked') ? __('SI') : __('NO'));
			var restriccion_min = '\n ' + __('Min HH') + ':                 ' + $('#restriccion_min').val();
			var restriccion_max = '\n ' + __('Max HH') + ':                 ' + $('#restriccion_max').val();
		} else {
			var alerta_semanal = '';
			var restriccion_min = '';
			var restriccion_max = '';
		}

		var restriccion_mensual = $('#cambiar_restriccion_mensual').is(':checked') ? '\n ' + __('Min HH mensual') + ': ' + $('#restriccion_mensual').val() : '';
		var dias_ingreso_trabajo = $('#cambiar_dias_ingreso_trabajo').is(':checked') ? '\n ' + __('Max días ingreso') + ':  ' + $('#dias_ingreso_trabajo').val() : '';

		if (confirm(alerta_diaria + alerta_semanal + retraso_max + restriccion_min + restriccion_max + restriccion_mensual + dias_ingreso_trabajo + '\n\n ' + __('¿Desea cambiar los restricciones y alertas de todos los usuarios?'))) {
			$(this).attr('action', 'usuario_paso1.php');
			return true;
		}
		return false;
	});
})(jQuery);

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
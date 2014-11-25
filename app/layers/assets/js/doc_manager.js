// Obtiene previsualizacion del formato html

var intrvl = 0;
var dm_root = root_dir + '/app/DocManager';
var tags_cache = {'':[]};
var modified = false;

/**
 * 
 * @param string text texto que mostrará la notificación
 * @param string type tipo de notificación, por defecto 'info' success|info|warning|danger
 * @param string title título de la notificación, por defecto vacío.
 * @returns string html
 */
function notify(text, type, title) {
	if (!window.notificator) {
		window.notificator = {text: '', div: ''};
	}
	if (!type) {
		type = 'info';
	}
	if (window.notificator.div) {
		window.notificator.div.alert('close');
	}
	var button = $('<button/>')
		.attr('type', 'button')
		.addClass('close')
		.attr('data-dismiss', 'alert');
	button.append($('<span/>').attr('aria-hidden', true).html('&times;'));
	button.append($('<span/>').addClass('sr-only').html('Close'));
	var strong = !title ? '' : $('<strong/>').html(title).append('<br/>');
	var div = $('<div/>').attr('role', 'alert').addClass('alert alert-dismissible out in').addClass('alert-' + type);
	div.append(button).append(strong).append(text).appendTo('body');
	div.css({position: 'fixed', top: 10, right: 10, width: 300});
	window.notificator.text = text;
	window.notificator.div = div;
	$(div).on('closed.bs.alert', function () {
		window.notificator.text = '';
	})
	div.alert().delay(3000).fadeOut('slow', function(){$(this).alert('close')});
}

function showError(msg) {
	notify(msg, 'danger');
}

/**
 * Verifica si se han realizado modificaciones y avisa al usuario.
 * @returns {Boolean}
 */
function itsModified() {
	if (modified) {
		if(!confirm('Perderá las modificaciones realizadas\n¿Desea continuar?')) {
			return true;
		}
	}
	return false;
}

function PrevisualizarCarta() {
	clearInterval(intrvl);

	var id_cobro = $("#id_cobro").val();
	if (id_cobro === '') {
		showError('Es necesario definir un numero de cobro para previsualizar una carta');
		return;
	}
	
	var existecobro = ExisteCobro(id_cobro);
	if (existecobro === false) {
		showError('No existe cobro');
		return;
	}
	
	var formato = $('#carta_formato').val();
	$.post(dm_root + '/obtener_carta/' + id_cobro, {formato: formato}, function(data) {
		$("#letter_preview").html(data);
	}, 'text');
}

// Function Existe
function ExisteCobro(id_cobro) {

	var existecobro;
	var urlajax = dm_root + '/existe_cobro/' + id_cobro;
	$.ajax(urlajax, {
		method: 'get',
		async: false,
		dataType: 'json',
		success: function(data) {
			existecobro = data.existe;
		}
	});

	return existecobro;

}

function InsertarEnTextArea(text, type) {
	if (type === 'seccion') {
		var inserttxt = '###' + text + '###';
	} else if (type === 'tag') {
		var inserttxt = text;
	}

	var txtarea = $('#carta_formato')[0];
	var scrollPos = txtarea.scrollTop;
	var strPos = 0;
	var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
		"ff" : (document.selection ? "ie" : false));
	strPos = txtarea.selectionStart;

	var front = (txtarea.value).substring(0, strPos);
	var back = (txtarea.value).substring(strPos, txtarea.value.length);
	txtarea.value = front + inserttxt + back;
	strPos = strPos + inserttxt.length;

	txtarea.selectionStart = strPos;
	txtarea.selectionEnd = strPos;
	txtarea.focus();
	txtarea.scrollTop = scrollPos;
}

function set_options(selector, data, empty) {
	$(selector).html('');
	if (empty === true) {
		$(selector).html($('<option/>').html(''));
	}
	$.each(data, function(k, v) {
		var option = $('<option/>').val(k).html(v);
		$(selector).append(option);
	});
}

$(document).ready(function() {
	$('#tabs').tab();

	// Verifica que input solo acepte numeros y no letras.
	$('#id_cobro').keypress(function(e) {
		//if the letter is not digit then display error and don't type anything
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			//display error message
			showError('Ingrese Solo Numeros');
			return false;
		}
	});

	// Observa si hay cambios en el selector de formatos.
	// Carga carta_formato y carta_formato_css. Además obtiene cantidad de cobros asociados.

	$('#carta_id_carta').on('change', function(event) {
		clearInterval(intrvl);
		if (itsModified()) {
			$(this).val($(this).data('current'));
			return true;
		}
		$(this).data('current', $(this).val());
		var id_carta = $(this).val();
		$('#nrel_charges').html('');
		$('#carta_formato').val('');
		$('#carta_formato_css').val('');
		$('#letter_preview').html('');
		var margenes = ['margen_superior', 'margen_inferior', 'margen_izquierdo', 'margen_derecho'];
		$.each(margenes, function(v) {
			$('#carta_' + v).val('');
		});
		if (id_carta) {
			var urlajaxnrelcharges = dm_root + '/obtenenrelncobros/' + id_carta;
			var urlajaxgethtml = dm_root + '/obtener_html/' + id_carta;
			var urlajaxgetcss = dm_root + '/obtener_css/' + id_carta;
			var urlajaxgetmargins = dm_root + '/obtener_margenes/' + id_carta;

			$.get(urlajaxnrelcharges, function(data) {
				$('#nrel_charges').html(data);
			});
			$.get(urlajaxgethtml, function(data) {
				$('#carta_formato').val(data);
			});
			$.get(urlajaxgetcss, function(data) {
				$('#carta_formato_css').val(data);
			});
			$.get(urlajaxgetmargins, function(data) {
				$.each(data, function(k, v) {
					$('#carta_' + k).val(v);
				});
			}, 'json');
			if ($('#id_cobro').val()) {
				PrevisualizarCarta();
			}
		}
	});

	// Observa si hay cambios en el selector de seccion para mostrar tags relacionados a esta.
	$('#secciones').on('change', function() {
		var seccion = $(this).val();
		if (tags_cache[seccion]) {
			set_options('#tag_selector', tags_cache[seccion]);
		} else {
			$('#tag_selector').html('<option>Cargando...</option>');
			var urlajax = dm_root + '/obtener_tags/' + seccion;
			$.get(urlajax, function(data) {
				tags_cache[seccion] = data;
				set_options('#tag_selector', data);
			});
		}
	});

	// Obteniendo Previsualizacion ( formato, formato_css )
	$('#id_cobro').on('change', function() {
		PrevisualizarCarta();
	});
	$('#carta_formato, #carta_formato_css').on('change', function() {
		modified = true;
	});
	// Obteniendo Previsualizacion del formato (live)
	$('#carta_formato').on('input', function() {
		clearInterval(intrvl);
		intrvl = setInterval(function() {
			if ($('#id_cobro').val()) {
				PrevisualizarCarta();
			}
		}, 1000);
	});

	$('#guardar_nuevo').click(function() {
		$.post(dm_root + '/nuevo/', {
			descripcion: $('#carta_descripcion').val(),
			id_formato: $('#id_new_formato').val()
		}, function(reply) {
			if (reply.error) {
				notify(reply.errores.replace('\n', '<br/>'), 'danger', 'Error');
			}
			if (reply.success) {
				modified = false;
				notify('La carta se creó correctamente.', 'success', 'Nueva carta');
				$('#carta_id_carta').append($('<option/>').val(reply.id).html($('#carta_descripcion').val()));
				$('#id_new_formato').append($('<option/>').val(reply.id).html($('#carta_descripcion').val()));
				$('#carta_id_carta').val(reply.id).change();
			}
			$('#carta_descripcion').val('');
			$('#id_new_formato').val('');
		}, 'json');
	});

	$('#guardar_formato').on('click', function() {
		$.post(dm_root + '/guardar/', $('#form_doc').serialize(), function(reply) {
			if (reply.error) {
				notify(reply.error, 'danger', 'Error');
			}
			if (reply.success) {
				notify('La carta se guardó correctamente.', 'success', 'Guardar carta');
			}
		}, 'json');
	});

	$('#eliminar_formato').on('click', function() {
		if(!confirm('Eliminará el formato "' + $('#carta_id_carta option:selected').html() + '"\n¿Desea continuar?')) {
			return;
		}
		$.post(dm_root + '/eliminar/', {id: $('#carta_id_carta').val()}, function(reply) {
			if (reply.deleted) {
				notify('La carta se eliminó correctamente.', 'success', 'Eliminar carta');
				$('#id_new_formato option[value='+ $('#carta_id_carta').val() + ']').remove();
				$('#carta_id_carta option:selected').remove();
				$('#carta_id_carta').val('').change();
				return;
			}
			notify('No se pudo eliminar el formato de carta indicado.', 'danger', 'Error');
		});
	});

	$('#insertar_elemento').click(function() {
		var seccion = $('#secciones').val();
		var tag = $('#tag_selector').val();

		if (!tag) {
			InsertarEnTextArea(seccion, 'seccion');
		} else {
			InsertarEnTextArea(tag, 'tag');
		}

	});

	$('#btn_previsualizar').on('click', function() {
		$('#form_doc').attr('action', dm_root + '/previsualizar/' + $('#id_cobro').val());
		$('#form_doc').submit();
	});

	$('#nuevo_formato').on('show.bs.modal', function() {
		return !itsModified();
	});
});

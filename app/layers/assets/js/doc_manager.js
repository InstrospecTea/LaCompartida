// Obtiene previsualizacion del formato html

var intrvl = 0;
var dm_root = root_dir + '/app/DocManager';
var tags_cache = {'':[]};
window.modified = false;
window.previewing = false;
window.charge_exists = false;
/**
 * @param string text texto que mostrar� la notificaci�n
 * @param string type tipo de notificaci�n, por defecto 'info' success|info|warning|danger
 * @param string title t�tulo de la notificaci�n, por defecto vac�o.
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

/**
 * Controla cuando se presiona una combinaci�n de teclas Ctrl + x
 * @param {type} key
 * @param {type} callback
 * @param {type} args
 * @returns {undefined}
 */
$.ctrl = function(key, callback, args) {
	var isCtrl = false;
	$(document).keydown(function(e) {
		if(!args) args=[]; // IE barks when args is null

		if(e.ctrlKey) isCtrl = true;
		if(e.keyCode == key.charCodeAt(0) && isCtrl) {
			callback.apply(this, args);
			return false;
		}
	}).keyup(function(e) {
		if(e.ctrlKey) isCtrl = false;
	});
};

function showError(msg) {
	notify(msg, 'danger');
}

var loading = {
	elements: {},
	start: function (elements, whenDone) {
		if (!$.isArray(elements)) {
			elements = [elements];
		}
		$.each(elements, function (k, v) {
			loading.elements[v] = true;
		})
		if (whenDone) {
			loading.whenDone = whenDone;
		}
	},
	stop: function (element) {
		this.elements[element] = false;
		if (this.isDone()) {
			this.whenDone();
		}
	},
	isDone: function () {
		var done = true;
		loading = this;
		$.each(loading.elements, function(v) {
			if (loading.elements[v]) {
				done = false;
			}
		});
		return done;
	},
	whenDone: function() {}
}

/**
 * Verifica si se han realizado modificaciones y avisa al usuario.
 * @returns {Boolean}
 */
function itsModified() {
	if (window.modified) {
		if(!confirm('Perder� las modificaciones realizadas\n�Desea continuar?')) {
			return true;
		}
	}
	return false;
}

function PrevisualizarCarta() {
  if (window.previewing || !window.charge_exists) {
    return;
  }
	clearInterval(intrvl);
	var id_cobro = $("#id_cobro").val();
  window.previewing = true;
	$('#form_doc')
		.attr('target', 'letter_preview')
		.attr('action', dm_root + '/obtener_carta/' + id_cobro)
		.submit();
}
// Function Existe
function ExisteCobro(id_cobro) {
	var existe_cobro = false;
	var url_ajax = dm_root + '/existe_cobro/' + id_cobro;
	$.ajax(url_ajax, {
		method: 'get',
		async: false,
		dataType: 'json',
		success: function(data) {
			existe_cobro = data.existe;
		}
	});
	if (!existe_cobro) {
		$('#letter_preview').contents().find('body').html('');
		showError('No existe cobro');
	}
  window.charge_exists = existe_cobro;
	return existe_cobro;

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

$('#tabs').tab();

$('#letter_preview').on('load', function() {
	window.previewing = false;
});

// Verifica que input solo acepte numeros y no letras.
$('#id_cobro').keypress(function(e) {
	//if the letter is not digit then display error and don't type anything
	if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
		//display error message
		showError('Ingrese Solo Numeros');
		return false;
	}
});

$.ctrl('S', function() {
	$('#guardar_formato').click();
});

// Observa si hay cambios en el selector de formatos.
// Carga carta_formato y carta_formato_css. Adem�s obtiene cantidad de cobros asociados.

$('#carta_id_carta').on('change', function(event) {
	clearInterval(intrvl);
	if (itsModified()) {
		$(this).val($(this).data('current'));
		return true;
	}
	window.modified = false;
	$(this).data('current', $(this).val());

	$('#carta_formato').val('');
	$('#carta_formato_css').val('');
	$('#nrel_charges').html('');

	$('#letter_preview').attr('src', 'about:blank');

	var margenes = ['margen_superior', 'margen_inferior', 'margen_izquierdo', 'margen_derecho'];
	$.each(margenes, function(v) {
		$('#carta_' + v).val('');
	});

	var id_carta = $(this).val();
	if (!id_carta) {
		return;
	}

	loading.start(['formato', 'css', 'margen'], function() {
		if (window.charge_exists) {
			PrevisualizarCarta();
		}
	});

	var urlajaxnrelcharges = dm_root + '/obtenenrelncobros/' + id_carta;
	var urlajaxgethtml = dm_root + '/obtener_html/' + id_carta;
	var urlajaxgetcss = dm_root + '/obtener_css/' + id_carta;
	var urlajaxgetmargins = dm_root + '/obtener_margenes/' + id_carta;

	$.get(urlajaxnrelcharges, function(data) {
		$('#nrel_charges').html('').append($('<h5/>').html('(N&deg; cobros asociados: ' + data.cobros_asociados + ')'));
	}, 'json');

	$.get(urlajaxgethtml, function(data) {
		$('#carta_formato').val(data);
		loading.stop('formato');
	});
	$.get(urlajaxgetcss, function(data) {
		$('#carta_formato_css').val(data);
		loading.stop('css');
	});
	$.get(urlajaxgetmargins, function(data) {
		$.each(data, function(k, v) {
			$('#carta_' + k).val(v);
		});
		loading.stop('margen');
	}, 'json');
});

$('#carta_formato, #carta_formato_css').on('change', function() {
	window.modified = true;
});

// Obteniendo Previsualizacion del formato (live)
$('#carta_formato, #carta_formato_css').on('input', function(e) {
	clearInterval(intrvl);
	if (!window.charge_exists) {
    return;
  }
	intrvl = setInterval(function() {
		if ($('#id_cobro').val()) {
			PrevisualizarCarta();
		}
	}, 1500);
});

$('#guardar_nuevo').on('click', function() {
	$.post(dm_root + '/nuevo/', {
		descripcion: $('#carta_descripcion').val(),
		id_formato: $('#id_new_formato').val()
	}, function(reply) {
		if (reply.error) {
			notify(reply.errores.replace('\n', '<br/>'), 'danger', 'Error');
		}
		if (reply.success) {
			window.modified = false;
			notify('La carta se cre� correctamente.', 'success', 'Nueva carta');
			$('#carta_id_carta').append($('<option/>').val(reply.id).html($('#carta_descripcion').val()));
			$('#id_new_formato').append($('<option/>').val(reply.id).html($('#carta_descripcion').val()));
			$('#carta_id_carta').val(reply.id).change();
		}
		$('#carta_descripcion').val('');
		$('#id_new_formato').val('');
	}, 'json');
});

$('#guardar_formato').on('click', function() {
	if (!$('#carta_id_carta').val()) {
		return false;
	}
	$.post(dm_root + '/guardar/', $('#form_doc').serialize(), function(reply) {
		if (reply.success) {
			window.modified = false;
			notify('La carta se guard� correctamente.', 'success', 'Guardar carta');
		} else if (reply.error) {
			notify(reply.error, 'danger', 'Error');
		}
	}, 'json');
});

$('#eliminar_formato').on('click', function() {
	if (!$('#carta_id_carta').val()) {
		return false;
	}
	if(!confirm('Eliminar� el formato "' + $('#carta_id_carta option:selected').html() + '"\n�Desea continuar?')) {
		return;
	}
	$.post(dm_root + '/eliminar/', {id: $('#carta_id_carta').val()}, function(reply) {
		if (reply.deleted) {
			notify('La carta se elimin� correctamente.', 'success', 'Eliminar carta');
			$('#id_new_formato option[value='+ $('#carta_id_carta').val() + ']').remove();
			$('#carta_id_carta option:selected').remove();
			$('#carta_id_carta').val('').change();
			return;
		}
		notify('No se pudo eliminar el formato de carta indicado.', 'danger', 'Error');
	});
});

$('#insertar_elemento').on('click', function() {
	var seccion = $('#secciones').val();
	var tag = $('#tag_selector').val();

	if (!tag) {
		InsertarEnTextArea(seccion, 'seccion');
	} else {
		InsertarEnTextArea(tag, 'tag');
	}

});

$('#btn_previsualizar').on('click', function() {
	if ($('#id_cobro').val() === '') {
		showError('Es necesario definir un numero de cobro para previsualizar una carta');
		return;
	}
	$('#form_doc')
		.removeAttr('target')
		.attr('action', dm_root + '/previsualizar/' + $('#id_cobro').val());
	$('#form_doc').submit();
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
  if (ExisteCobro($(this).val())) {
    PrevisualizarCarta();
  }
});

$('#nuevo_formato').on('show.bs.modal', function() {
	return !itsModified();
});

$('#margenes').on('show.bs.modal', function() {
	if (!$('#carta_id_carta').val()) {
		return false;
	}
});

// Obtiene previsualizacion del formato html

var intrvl = 0;
var dm_root = root_dir + '/app/DocManager';
var tags_cache = {};
function guardar() {
	$('#opc').val('guardar');
	$('#form_doc').submit();
}

function PrevisualizarCarta() {

	clearInterval(intrvl);
	var id_cobro = $("#id_cobro").val();
	var formato = $('#carta_formato').val();
	var existecobro = ExisteCobro(id_cobro);

	if (id_cobro === '') {
		alert('Es necesario definir un numero de cobro para previsualizar una carta');
	} else {

		if (existecobro === false) {
			$("#errmsg").html("No existe cobro").show().fadeOut(1600);
		} else {

			$.post(root_dir + '/obtener_carta/' + id_cobro + '/' + formato, function(data) {
				$("#letter_preview").html(data);
			});
		}
	}
}

function Cargarformato(id_carta) {
	var urlajaxnrelcharges = dm_root + '/obtenenrelncobros/' + id_carta;
	var urlajaxgethtml = dm_root + '/obtener_html/' + id_carta;
	var urlajaxgetcss = dm_root + '/obtener_css/' + id_carta;

	$.get(urlajaxnrelcharges, function(data) {
		$("#nrel_charges").html(data);
	});
	$.get(urlajaxgethtml, function(data) {
		$("#carta_formato").html(data);
	});
	$.get(urlajaxgetcss, function(data) {
		$("#carta\\[formato_css\\]").html(data);
	});
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
		var inserttxt = '%' + text + '%';
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
	alert((empty === true));
	if (empty === true) {
		$(selector).html($('<option/>').html(''));
	}
	$.each(data, function(k, v) {
		var option = $('<option/>').val(k).html(v);
		$(selector).append(option);
	});
}

$(function() {

	// Observa si hay cambios en el selector de formatos.
	// Carga carta[formato] y carta[formato_css]. Además obtiene cantidad de cobros asociados.

	$('#carta\\[id_carta\\]').change(function() {
		var id_carta = $('#carta\\[id_carta\\]').val();
		Cargarformato(id_carta);
	});

	// Observa si hay cambios en el selector de seccion para mostrar tags relacionados a esta.
	$("#secciones").on('change', function() {
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
	$('#id_cobro').change(function() {
		PrevisualizarCarta();
	});

	// Obteniendo Previsualizacion del formato (live)
	$('#carta_formato').on('input', function() {
		clearInterval(intrvl);
		intrvl = setInterval(PrevisualizarCarta, 1000);

	});

	$('#id_new_formato').change(function() {
		Cargarformato($(this).val());
	});

	$('#guardar_nuevo').click(function() {
		guardar();
	});

	$('#guardar_formato').click(function() {
		guardar();
	});

	$('#eliminar_formato').click(function() {
		$('#opc').val('eliminar');
		$('#form_doc').submit();
	});

	$('#insertar_elemento').click(function() {
		var seccion = $("#secciones option:selected").val();
		var tag = $("#tag_selector option:selected").val();

		if (tag == '' || tag === 'Undefinded') {
			InsertarEnTextArea(seccion, 'seccion');
		} else {
			InsertarEnTextArea(tag, 'tag');
		}

	});

	$('#btn_previsualizar').click(function() {
		$('#opc').val('prev');
		$('#form_doc').submit();
	});

});

jQuery(document).ready(function($) {
	$('#tabs').tab();

	// Verifica que input solo acepte numeros y no letras.
	$("#id_cobro").keypress(function(e) {
		//if the letter is not digit then display error and don't type anything
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			//display error message
			$("#errmsg").html("Ingrese Solo Numeros").show().fadeOut(1600);
			return false;
		}
	});
});
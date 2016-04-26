jQuery(function () {
	jQuery('.inlinehelp').each(function() {
		jQuery(this).popover({title: jQuery(this).attr('title'), trigger: 'hover', animation: true, content: jQuery(this).attr('help')});
	});

	jQuery('#button_download_excel').click(function() {
		jQuery('#option').val('xls');
		jQuery('#client_old_debt_accounting_concepts').submit();
	});

	jQuery('#button_search').click(function() {
		jQuery('#option').val('buscar');
		jQuery('#client_old_debt_accounting_concepts').submit();
	});

	jQuery('.subreport tr.encabezado td.encabezado').css('background-color','#ddd');
	jQuery('.subreport tr.encabezado td.encabezado').css('color','#040');

	jQuery('.subtotal td').css('font-weight', 'bold');
	jQuery('.encabezado').show();
	jQuery('.identificadores').show().each(function() {
		var td = jQuery(this);
		var contenido = td.html();
		var uuid = genUUID();
		td.html(jQuery('<a/>', {
			text: 'Mostrar / Ocultar',
			href: 'javascript:void(0)',
			onclick: "showDetails('" + uuid + "');"
		}));
		td.append(jQuery('<br>'));
		var span = jQuery('<span id="' + uuid + '"></span>').hide();
		jQuery.each(jQuery.parseJSON(contenido), function(invoce, charge_id) {
			span.append(jQuery('<a/>', {
				text: invoce,
				style: 'white-space:nowrap;',
				href: 'javascript:void(0)',
				onclick: "nuovaFinestra('Cobro', 1000, 700, '" + root_dir + "/app/interfaces/cobros6.php?id_cobro=" + charge_id + "&popup=1&contitulo=true&id_foco=2', 'top=100, left=155');"
			})).append(' ');
		});
		td.append(span);
	});

	jQuery('.total_normal').each(function(){
		var td = jQuery(this);
		td.css('color','blue');
	});

	jQuery('.total_vencido').each(function(){
		var td = jQuery(this);
		td.css('color','red');
	});

	jQuery('.seguimiento').each(function () {
		var td = jQuery(this);
		var contenido = td.html();
		if (contenido.trim() != '') {
			partes = contenido.split('|');
			codigo_cliente = partes[0];
			cantidad_seguimiento = partes[1];
			var link = jQuery('<a/>', { text: '', href: 'javascript:void(0)' });
			icono = 'tarea_inactiva.gif';
			if (parseInt(cantidad_seguimiento) > 0) {
				icono = 'tarea.gif';
			}
			link.append(jQuery('<img/>', { src: img_dir + '/' + icono }));
			td.html('');
			link.data('codigo_cliente', codigo_cliente);
			link.click(function (e) {
				e.preventDefault();
				e.stopPropagation();

				showPopover(jQuery(this));
			});
			td.append(link).append(' ');
		}
	});

	jQuery('body').click(function () {
		jQuery('#seguimiento_template').hide();
	});
});

showPopover = function(sender) {
	var codigo_cliente = sender.data('codigo_cliente');
	var tpl = jQuery('#seguimiento_template').find('.popover');

	jQuery('#seguimiento_iframe').attr('src', root_dir + '/app/interfaces/ajax/ajax_seguimiento.php?codigo_cliente=' + codigo_cliente);

	sender_pos = sender.position();
	tpl.css('display', 'block');
	tpl.css('left', sender_pos.left - 414);
	tpl.css('top', sender_pos.top - 106);
	tpl.parent().show();
};

function showDetails(id) {
	jQuery('#' + id).toggle();
};

function genUUID() {
	var d = new Date().getTime();
	var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = (d + Math.random()*16)%16 | 0;
		d = Math.floor(d/16);
		return (c=='x' ? r : (r&0x3|0x8)).toString(16);
	});
	return uuid;
};

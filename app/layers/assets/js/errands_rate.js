jQuery(function() {
	var tarifa_temp = '';
	jQuery('.tarifas').on('focus', function() {
		tarifa_temp = jQuery.trim(jQuery(this).val());
	});

	jQuery('.tarifas').on('blur', function() {
		if (jQuery.trim(jQuery(this).val()) == '' && tarifa_temp != '') {
			if (confirm('¿Está seguro de querer eliminar la tarifa?\nEsto puede provocar inconsistencia de datos en los trámites ya creados.')) {
				jQuery(this).val('0');
			} else {
				jQuery(this).val(tarifa_temp);
				jQuery(this).focus();
			}
		}
	});
});

function foco(elemento) {
	elemento.style.border = '2px solid #000000';
}

function cambia_tarifa(valor) {
	var popup = $('popup').value;
	if (confirm("<?php echo __('Confirma cambio de tarifa?'); ?>")) {
		self.location.href = 'tarifas_tramites.php?id_tramite_tarifa_edicion=' + valor + '&popup=' + popup;
	}
}

function no_foco(elemento) {
	elemento.style.border = '1px solid #CCCCCC';
}

function Eliminar() {
	var http = getXMLHTTP();
	http.open('get', 'ajax.php?accion=obtener_tramite_tarifa_defecto&id_tarifa=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
	http.send(null);
	tarifa_defecto_en_bd = http.responseText;

	if (tarifa_defecto_en_bd != <?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>){
		var http = getXMLHTTP();
		http.open('get', 'ajax.php?accion=contratos_con_esta_tramite_tarifa&id_tarifa=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
		http.send(null);
		num_contratos = http.responseText;

		if (num_contratos > 0) {
			respuesta_num_pagos = confirm('<?php echo  __('La tarifa posee'); ?> ' + num_contratos + ' <?php echo __('contratos asociados. \nSi continua se le asignará la tarifa estándar a los contratos afectados.\n¿Está seguro de continuar?.'); ?>');
			if( respuesta_num_pagos ) {
				http.open('get', 'ajax.php?accion=cambiar_a_tramite_tarifa_por_defecto&id_tarifa=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
				http.send(null);
				num_contratos = http.responseText;

				location.href="tarifas_tramites.php?popup=<?php echo $popup?>&id_tramite_tarifa_eliminar=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>&opc=eliminar";
			} else {
				return false;
			}
		} else {
			if (confirm('¿<?php echo __('Está seguro de eliminar la')." ".__('tarifa')?>?')) {
				location.href = "tarifas_tramites.php?popup=<?php echo $popup?>&id_tramite_tarifa_eliminar=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>&opc=eliminar";
			}
		}
	} else {
		alert( 'No puede eliminar la tarifa estándar (por defecto)' );
		return false;
	}
}

function CrearTarifa(from, id) {
	if (document.getElementById('usar_tarifa_previa').checked) {
			self.location.href='tarifas_tramites.php?popup=<?php echo $popup?>&crear=1&id_tramite_tarifa_previa=' + id;
	} else {
		self.location.href='tarifas_tramites.php?popup=<?php echo $popup?>&crear=1';
	}
}

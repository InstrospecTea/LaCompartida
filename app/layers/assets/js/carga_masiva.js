
/**
 * multi_unicos[nombre llave unica multiple] = [lista ordenada de campos que la componen]
 * @type json
 */
var multi_unicos = {};
jQuery.each(campos_clase, function(campo, info) {
  if (typeof(info.unico) === 'string') {
    if (!multi_unicos[info.unico]) {
      multi_unicos[info.unico] = [];
    }
    multi_unicos[info.unico].push(campo);
  }
});

/**
 * listados_inversos[nombre][glosa postprocesada] = id
 * @type json
 */
var listados_inversos = {};

/**
 * genera los listados inversos (glosa => id) a partir de los listados normales (id => glosa)
 * @returns {generarListadosInversos}
 */
function generarListadosInversos() {
  listados_inversos = {};
  jQuery.each(listados, function(nombre, listado) {
    listados_inversos[nombre] = {};
    jQuery.each(listado, function(id, valor) {
      if (typeof(valor) !== 'string') {
        var v = [];
        jQuery.each(valor, function(campo, valor_campo) {
          v.push(valor_campo);
        });
        valor = v.join(' / ');
      }
      listados_inversos[nombre][limpiar(valor)] = id;
    });
  });
}
generarListadosInversos();

/**
 * idx_campos[campo] = numero de columna (th.index())
 * @type json
 */
var idx_campos = {};

/**
 * campos_idx[numero de columna (th.index())] = campo
 * @type json
 */
var campos_idx = {};

/**
 * valores_unicos[llave unica / campo][valor postprocesado] = [numeros de filas]
 * @type type
 */
var valores_unicos = {};
jQuery.each(campos_clase, function(campo, info) {
  if (info.relacion && typeof(info.unico) !== 'string' || info.unico === true) {
    valores_unicos[campo] = {
      unico: info.unico
    };
  }
  else if (typeof(info.unico) === 'string') {
    valores_unicos[info.unico] = {
      unico: info.unico
    };
  }
});

/**
 * columna_validada[indice] dice si se ejecuto alguna vez la validacion de los campos de esa columna
 * @type json
 */
var columna_validada = {};

/**
 * agrega un valor a la lista de valores unicos, y elimina el anterior
 * @param {string} campo
 * @param {string} nuevo
 * @param {string} viejo
 * @param {int} idx_fila
 */
function agregarValorUnico(campo, nuevo, viejo, idx_fila) {
  nuevo = limpiar(nuevo);
  viejo = limpiar(viejo);

  var titulo = campos_clase[campo] ? campos_clase[campo].titulo : campo;

  //si ya existe en la BD
  if (valores_unicos[campo].unico && idx_campos[campo] !== undefined) {
    if (listados_inversos[campo][nuevo]) {
      var id = listados_inversos[campo][nuevo];
      var val = listados[campo][id];
      var ids = '';
      if (typeof(val) === 'string') {
        ids = '#data_' + idx_fila + '_' + idx_campos[campo];
        jQuery(ids).val(val);
      }
      else {
        //marcar todos los campos del multi-unico
        ids = [];
        var vals = [];
        jQuery.each(multi_unicos[campo], function(i, c) {
          var id = '#data_' + idx_fila + '_' + idx_campos[c];
          jQuery(id).val(val[c]);
          ids.push(id);
          vals.push(val[c]);
        });
        ids = ids.join(',');
        val = vals.join(' / ');
      }

      var msg = titulo + ' debe ser �nico, pero ya existe el valor ' + val + ' entre los datos actuales';
      if (campo === llave) {
        var tr = jQuery(ids).addClass('warning')
            .attr('title', msg + '. Se editar� el ' + clase + ' existente')
            .closest('tr').addClass('warning').attr('data-id', id);

        if (!tr.hasClass('error')) {
          tr.attr('title', msg + '. Se editar� el ' + clase + ' existente');
        }
      }
      else if (jQuery(ids).closest('tr').attr('data-id') != id) {
        jQuery(ids).addClass('error').attr('title', msg);
      }
    }
    else if (listados_inversos[campo][viejo] && multi_unicos[campo]) {
      jQuery.each(multi_unicos[campo], function(i, c) {
        jQuery('#data_' + idx_fila + '_' + idx_campos[c]).removeClass('error').removeAttr('title');
      });
    }
    else if (campo === llave) {
      var tr = jQuery('#data_' + idx_fila + '_' + idx_campos[campo]).closest('tr')
          .removeClass('warning').removeAttr('data-id');
      if (!tr.hasClass('error')) {
        tr.removeAttr('title');
      }
    }
  }

  if (viejo && valores_unicos[campo][viejo]) {
    var i = valores_unicos[campo][viejo].indexOf(idx_fila);
    if (i >= 0) {
      valores_unicos[campo][viejo].splice(i, 1);

      switch (valores_unicos[campo][viejo].length) {
        case 0:
          delete valores_unicos[campo][viejo];
          break;
        case 1: //antes eran >1 y ahora queda 1 solo, ya no hay problema de unicidad
          if (valores_unicos[campo].unico) {
            var idx_viejo = valores_unicos[campo][viejo][0];
            if (multi_unicos[campo]) {
              jQuery.each(multi_unicos[campo], function(i, c) {
                jQuery('#data_' + idx_viejo + '_' + idx_campos[c])
                    .removeClass('error').attr('title', '');
              });
            }
            else {
              jQuery('#data_' + idx_viejo + '_' + idx_campos[campo])
                  .removeClass('error').attr('title', '');
            }
          }
          break;
      }
    }

  }

  if (nuevo) {
    if (!valores_unicos[campo][nuevo]) {
      valores_unicos[campo][nuevo] = [];
    }
    var i = valores_unicos[campo][nuevo].indexOf(idx_fila);
    if (i < 0) {
      valores_unicos[campo][nuevo].push(idx_fila);
      if (valores_unicos[campo].unico && idx_campos[campo] !== undefined) {
        if (valores_unicos[campo][nuevo].length > 1) {
          inputsValoresUnicos(campo, nuevo)
              .addClass('error').attr('title', 'El campo ' + titulo + ' debe ser �nico, pero est� repetido en esta carga');
        }
      }
    }
  }

}

/**
 * obtiene los inputs que tienen este valor
 * @param {int} campo
 * @param {string} val
 * @returns {jQuery} inputs
 */
function inputsValoresUnicos(campo, val) {
  val = limpiar(val);
  if (!valores_unicos[campo][val]) {
    return null;
  }
  var ids_tr = valores_unicos[campo][val];
  var ids_input = [];
  var idxs = idx_campos[campo];
  if (typeof(idxs) === 'number') {
    idxs = [idxs];
  }

  jQuery.each(idxs, function(i, idx_col) {
    ids_input.push(jQuery.map(ids_tr, function(id_tr) {
      return '#data_' + id_tr + '_' + idx_col;
    }).join(','));
  });
  return jQuery(ids_input.join(','));
}

/**
 * actualizar el valor del input al cambiar un selector de relacion
 */
function cambioRelacion() {
  var td = jQuery(this).closest('td');
  var input = td.find('[name^=data]');
  var val_input = limpiar(input.val());
  var idx = td.index();
  if (val_input && (input.hasClass('error') || input.hasClass('warning'))) {
    var iguales = inputsValoresUnicos(campos_idx[idx], val_input);
    if (iguales && iguales.length > 1 && confirm('Cambiar todos los datos de esta columna que tienen el valor ' + input.val() + '?')) {
      input = iguales;
    }
  }
  input.val(jQuery(this).find(':selected').text()).change();
}

/**
 * actualizar el input al (des)checkear un checkbox
 */
function cambioCheck() {
  jQuery(this).closest('td').find('[name^=data]')
      .val(jQuery(this).is(':checked') ? 'SI' : 'NO');
}

/**
 * eliminar espacios, minusculas y acentos para comparar
 * @param {string} s
 * @returns {string}
 */
function limpiar(s) {
  if (typeof(s) !== 'string') {
    return '';
  }
  s = s.replace(/\s/g, '').toUpperCase();
  var acentos = {
    '�|�|�': 'A',
    '�|�|�': 'E',
    '�|�|�': 'I',
    '�|�|�': 'O',
    '�|�|�': 'U',
    '�': 'N',
    '\'': ''
  };
  jQuery.each(acentos, function(acento, limpio) {
    s = s.replace(new RegExp(acento, 'g'), limpio);
  });
  return s;
}

/**
 * valida que el valor ingresado este dentro de las opciones existentes
 * @param {jQuery} td
 * @param {jQuery} input
 * @param {string} val
 * @param {string} relacion
 */
function validarRelacion(td, input, val, relacion) {
  if (!val) {
    return;
  }
  val = limpiar(val);

  var id = listados_inversos[relacion][val];
  if (id) {
    td.find('.extra select').val(id);
    input.val(listados[relacion][id]);
  }
  else {
    //fail: no existe el dato, si es creable solo es warning, si no es un error
    var idx = td.index();
    var campo = campos_idx[idx];
    var creable = campos_clase[campo].creable;
    input.addClass(creable ? 'warning' : 'error')
        .attr('title', 'No existe este valor en el listado actual' +
        (creable ? '. Se crear� al cargar los datos' : ''));
    td.find('.extra select').val('');

    if (creable) {
      //homogeneizar espacios/mayusculas con otras filas existentes
      var iguales = inputsValoresUnicos(campo, val);
      if (iguales) {
        iguales.val(input.val());
      }
    }
  }
}

/**
 * actualiza el checkeado de un checkbox cuando se cambia el input
 * @param {jQuery} input
 * @param {bool} defval
 */
function actualizarCheckbox(input, defval) {
  var val = limpiar(input.val());
  var checked = defval;
  if (val !== '') {
    checked = val.charAt(0) !== 'N' && val !== '0';
  }

  var check = input.closest('td').find(':checkbox');
  if (checked) {
    check.attr('checked', 'checked');
  }
  else {
    check.removeAttr('checked');
  }
}

/**
 * valida que el email sea un email (o vacio)
 * @param {jQuery} input
 */
function validarEmail(input) {
  if (input.val() && !input.val().match(/^\s*[\w\.-]+@[\w\.-]+\.\w+\s*$/)) {
    input.addClass('error').attr('title', 'Ingrese un mail v�lido');
  }
}

/**
 * valida el input segun la metadata del campo que representa
 * @param {jQuery} input
 * @param {string} campo
 * @param {json} info
 */
function validacionInput(input, campo, info) {
  if (input[0].className) {
    input.removeClass('error').removeClass('warning').removeAttr('title');
  }
  //elimina espacios repetidos
  input.val(input.val().replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, ''));

  if (!info) {
    return;
  }
  var val = input.val();
  var tr_idx = input.closest('tr').index();

  if (valores_unicos[campo]) {
    agregarValorUnico(campo, val, input.attr('data-viejo'), tr_idx);
  }

  if (info.requerido && val === '') {
    input.addClass('error').attr('title', 'Este campo es obligatorio');
  }
  else {
    if (typeof(info.unico) === 'string') {
      //llave multiple
      var nuevo = [];
      var viejo = [];
      jQuery.each(multi_unicos[info.unico], function(i, c) {
        var inp = jQuery('#data_' + tr_idx + '_' + idx_campos[c]);
        nuevo.push(inp.val());
        viejo.push(inp.attr('data-viejo'));
      });
      agregarValorUnico(info.unico, nuevo.join(' / '), viejo.join(' / '), tr_idx);
    }

    if (info.relacion) {
      validarRelacion(input.closest('td'), input, val, info.relacion);
    }

    if (info.tipo === 'bool') {
      actualizarCheckbox(input, info.defval);
    }
    else if (info.tipo === 'email') {
      validarEmail(input);
    }
  }
  input.attr('data-viejo', val);
}

/**
 * calcula los indices de columnas que corresponden a cada campo y viceversa
 */
function calcularCamposIdx() {
  idx_campos = {};
  campos_idx = {};
  jQuery('[name^=campos]').each(function(idx) {
    idx_campos[jQuery(this).val()] = idx;
    campos_idx[idx] = jQuery(this).val();
  });
  jQuery.each(multi_unicos, function(nombre, campos) {
    var idxs = jQuery.map(campos, function(c) {
      return idx_campos[c];
    });
    idx_campos[nombre] = idxs;
  });
}

/**
 * serializa los inputs dentro de un elemento
 * @param {string} selector
 * @returns {string}
 */
function serializar(selector) {
  var data = {};
  var elem = jQuery(selector);
  var inputs = elem.is(':input[name]') ? elem.filter(':input[name]:visible') : elem.find(':input[name]:visible');
  inputs.each(function() {
    data[jQuery(this).attr('name')] = jQuery(this).val();
  });
  return jQuery.param(data);
}

/**
 * agrega una columna a la derecha de la tabla
 */
function agregarColumna() {
  var idx = jQuery('#data thead th').length;
  var sel_campo = jQuery('#data thead select:first').clone()
      .attr('name', 'campos[' + idx + ']').val('').change(cambioCampo);

  jQuery('#data thead tr').append(
      jQuery('<th/>').append(sel_campo).append(
      jQuery('<button/>', {class: 'btn_eliminar_columna'}).text('X').click(eliminarColumna)
      )
      );
  jQuery('#data tbody tr').each(function() {
    agregarData(jQuery(this), idx);
  });

  sel_campo.change();

  return false;
}

/**
 * agrega una celda a la tabla
 * @param {jQuery} tr
 * @param {int} col_idx
 */
function agregarData(tr, col_idx) {
  var tr_idx = tr.index();
  var td = jQuery('<td/>').append(
      jQuery('<input/>', {
    id: 'data_' + tr_idx + '_' + col_idx,
    name: 'data[' + tr_idx + '][' + col_idx + ']',
    class: 'col_' + col_idx
  }).change(cambioData).focus(focusData)
      ).append(
      jQuery('<div/>', {class: 'extra'})
      );

  if (tr.find('td').length > col_idx) {
    tr.find('td:nth-of-type(' + col_idx + ')').after(td);
  }
  else {
    tr.append(td);
  }

  if (!jQuery('[name="campos[' + col_idx + ']"]').is(':visible')) {
    td.hide();
  }
}

/**
 * agrega una fila al final de la tabla
 */
function agregarFila() {
  var tr = jQuery('<tr/>').appendTo(jQuery('#data tbody'));

  var cols = jQuery('#data thead th').length;
  for (var col_idx = 0; col_idx < cols; col_idx++) {
    agregarData(tr, col_idx);
  }
  tr.append(jQuery('<td/>').append(
      jQuery('<button/>', {class: 'btn_eliminar_fila'}).text('X').click(eliminarFila)
      ));

  tr.find(':input').change();

  return false;
}

/**
 * ignora, vacia y oculta una columna
 */
function eliminarColumna() {
  var idx = jQuery(this).closest('th').index();
  var inputs = jQuery('.col_' + idx);
  if (ocultarInputs(inputs)) {
    jQuery('[name="campos[' + idx + ']"]').val('').closest('th').hide();
    inputs.closest('td').hide();
  }

  return false;
}

/**
 * vacia y oculta una fila
 */
function eliminarFila() {
  var tr = jQuery(this).closest('tr');
  var inputs = tr.find(':input[name]');
  if (ocultarInputs(inputs)) {
    tr.hide();
  }

  return false;
}

/**
 * vacia y oculta un conjunto de inputs, confirmando si es que traen datos
 * @param {jQuery} inputs
 * @returns {Boolean} true si se ocultaron
 */
function ocultarInputs(inputs) {
  var no_vacios = inputs.filter(function() {
    return jQuery(this).val() !== '';
  });
  if (!no_vacios.length || confirm('Hay ' + no_vacios.length + ' campos con datos, est� seguro que desea eliminar la columna?')) {
    no_vacios.val('').change();
    return true;
  }
  else {
    no_vacios.focus();
  }
  return false;
}

/**
 * se llama al editar un input
 */
function cambioData() {
  var input = jQuery(this);
  if (!input.is(':visible')) {
    return true;
  }

  var idx = input.closest('td').index();
  var campo = campos_idx[idx];
  var info = campos_clase[campo];

  input.closest('tr').removeClass('ok');

  validacionInput(input, campo, info);
}

/**
 * mostrar informacion adicional si el tipo de campo lo requiere, y valida la columna si aun no se ha hecho
 */
function focusData() {
  //jQuery('.extra').html('');
  var td = jQuery(this).closest('td');
  var idx = td.index();
  var campo = campos_idx[idx];
  var info = campos_clase[campo];

  if (!columna_validada[idx]) {
    jQuery('[name="campos[' + idx + ']"]').change();
  }

  if (info) {
    //mostrar listado para relaciones o checkbox para bools
    if (info.relacion) {
      var sel = jQuery('#select-' + info.relacion);
      //var sel = jQuery('<select/>', {html: '<option value=""/>'});
      //jQuery.each(listados[info.relacion], function(id, valor) {
      //	sel.append(jQuery('<option/>').val(id).text(valor));
      //});
      sel.change(cambioRelacion);
      td.find('.extra').append(sel);
    }
    else if (info.tipo === 'bool') {
      //agregar checkbox
      var check = jQuery('<input/>', {type: 'checkbox'}).change(cambioCheck);
      r = td.closest("tr")[0].rowIndex - 1;
      if (jQuery('tbody tr td #data_'+r+'_'+idx).parent().contents().children(0).length < 1) {
        td.find('.extra').append(check);
      }
    }
    jQuery(this).change();
  }
}

/**
 * se llama al cambiar el campo que representa una columna
 */
function cambioCampo() {
  validacionColumnas(jQuery(this), 0);
}

/**
 * valida todos los inputs de una columna
 * @param {jQuery} selector_campo
 */
function validarColumna(selector_campo){
  if (!selector_campo.is(':visible')) {
    return true;
  }

  var campo = selector_campo.val();
  var info = campos_clase[campo];

  var idx = selector_campo.closest('th').index();
  var inputs = jQuery('.col_' + idx);
  inputs.each(function() {
    validacionInput(jQuery(this), campo, info);
  });
  columna_validada[idx] = true;
}

/**
 * envia asincronamente las filas de a 1
 * @param {jQuery} trs
 * @param {string} data
 * @param {int} idx
 */
function enviarFila(trs, data, idx) {
  var tr = trs.get(idx);
  if (!tr) {
    resumenEnvio();
    return;
  }
  tr = jQuery(tr);

  tr.addClass('procesando');
  jQuery(window).scrollTop(tr.position().top);
  jQuery.ajax('carga_masiva_ajax.php', {
    type: 'POST',
    data: data + serializar(tr),
    success: function(response) {
      tr.removeClass('procesando');
      try {
        var resp = jQuery.parseJSON(response);
        //se recibio una respuesta json
        if (response === '[]') {
          tr.removeClass('warning').addClass('ok');
        }
        else {
          tr.addClass('error').attr('title', resp[tr.index()]);
        }
      } catch (e) {
        //se recibio un html
        console.log(e);
        var error = 'Error al guardar el dato';
        var i = response.lastIndexOf('<!--');
        if (i >= 0) {
          //si viene un error SQL, mostrarlo como error
          error = response.substr(i + 4, response.length - 7);
        }
        tr.addClass('error').attr('title', error);
      }

      enviarFila(trs, data, idx + 1);
    }
  });
}

/**
 * actualiza los listados y muestra la cantidad de datos cargados y fallados
 */
function resumenEnvio() {
  //se vuelven a cargar los listados para reflejar los datos que se agregaron
  jQuery.ajax('carga_masiva_ajax.php', {
    type: 'POST',
    data: 'clase=' + clase + '&obtener_listados=1',
    success: function(response) {
      try {
        listados = jQuery.parseJSON(response);
        generarListadosInversos();
      } catch (e) {
      }

      //resumen ejecutivo
      var ok = jQuery('tr.ok:visible').length;
      var fail = jQuery('tr.error:visible').length;
      alert(ok + ' datos cargados correctamente, ' + fail + ' errores');
      if (fail) {
        jQuery(window).scrollTop(jQuery('tr.error:visible').position().top);
        jQuery('tr.error :input').one('change', function() {
          jQuery(this).closest('tr').removeClass('error').removeAttr('title');
        });
      }
    }
  });

}

/**
 * validacion asincrona de un conjunto de columnas
 * @param {jQuery} selectores_campo
 * @param {int} idx
 * @param {function} callback opcional
 */
function validacionColumnas(selectores_campo, idx, callback) {
  if (!idx) {
    calcularCamposIdx();
  }

  var col = selectores_campo.get(idx);
  if (col) {
    col = jQuery(col);
    var num_td = col.closest('th').index() + 1;
    var tds = jQuery('#data tbody td:nth-of-type(' + num_td + ')');
    tds.addClass('procesando');
    setTimeout(function() {
      validarColumna(col);
      tds.removeClass('procesando');
      validacionColumnas(selectores_campo, idx + 1, callback);
    }, 0);
  } else if(callback) {
    callback();
  }
}

jQuery(function() {
  jQuery('[name^=campos]').each(function(idx) {
    columna_validada[idx] = false;
  });

  jQuery('[name^=data]').change(cambioData).focus(focusData);

  jQuery('[name^=campos]').change(cambioCampo);

  calcularCamposIdx();

  //si la tabla es muy grande, validarla completa es muy lento
  //asi que se hace al momento de focusear cada columna (o al submitear)
  if (jQuery('input').length < 1000) {
    jQuery('[name^=campos]').change();
  }

  jQuery('#btn_agregar_columna').click(agregarColumna);
  jQuery('#btn_agregar_fila').click(agregarFila);
  jQuery('.btn_eliminar_columna').click(eliminarColumna);
  jQuery('.btn_eliminar_fila').click(eliminarFila);

  jQuery('#btn_enviar').click(function() {
    var msg = '';

    //se validan que esten las columnas que deben estar
    var repetidos = [];
    var faltan = [];
    var campos = jQuery('[name^=campos]');
    jQuery.each(campos_clase, function(campo, info) {
      var num = campos.filter(function() {
        return jQuery(this).val() === campo;
      }).length;
      if (num > 1) {
        repetidos.push(info.titulo);
      }
      else if (!num && info.requerido) {
        faltan.push(info.titulo);
      }
    });
    if (repetidos.length) {
      msg += '\nLas siguientes columnas est�n repetidas: ' + repetidos.join(', ');
    }
    if (faltan.length) {
      msg += '\nLas siguientes columnas no est�n pero son obligatorias: ' + faltan.join(', ');
    }


    // descubrir si 'cuenta bancaria' esta usando y cual es en el orden, tambien 'forma de cobro'
    var numero_campo_cuenta = -1;
    var numero_campo_formacobro = -1;
    for (var i = 0; i < jQuery('[name^=campos]').length; i++) {
      var campo_curr = jQuery('[name^=campos]').get(i).value;
      if (campo_curr == 'id_cuenta') {
        var numero_campo_cuenta = i;
      }
      if (campo_curr == 'forma_cobro') {
        var numero_campo_formacobro = i;
      }
    }

    //cuando hay una 'Cuenta Bancaria' intregada es necesario intregar tambien una 'Forma de Cobro'
    if (numero_campo_cuenta > -1 && numero_campo_formacobro == -1) {
      msg = 'Es necesario la columna Forma de Cobro para cuentas bancarias.'
    } else {
      var str_campo_cuenta = '#data_0_'+numero_campo_cuenta;
      var str_campo_formacobro = '#data_0_'+numero_campo_formacobro;
      var number_lines = jQuery('#data tbody tr').length;
      for (var i = 0; i < number_lines; i++) {
        if ( jQuery('#data_'+i+'_'+numero_campo_cuenta).val() != '' ) {
          if ( jQuery('#data_'+i+'_'+numero_campo_formacobro).val() == '' ) {
            msg = 'Es necesario ingresar una Forma de Cobro para una Cuenta Bancaria en la linea. ';
            jQuery('#data_'+i+'_'+numero_campo_formacobro).focus();
          }
        }
      }
    }


    //si aun no se valida alguna columna, se valida ahora
    validacionColumnas(jQuery('[name^=campos]:visible').filter(function() {
      var idx = jQuery(this).closest('th').index();
      return campos_idx[idx] && !columna_validada[idx];
    }), 0, function() {
      //buscar errores de datos
      var errores = jQuery('.error:visible');
      if (errores.length) {
        msg += '\nHay errores en los datos!';
        jQuery(errores.get(0)).focus();
      }

      if (msg) {
        alert(msg);
        return false;
      }

      //si hay advertencias se avisa pero igual se puede seguir
      var warnings = jQuery('.warning');
      if (warnings.length && !confirm('Hay advertencias! Desea enviar los datos de todas formas?')) {
        jQuery(warnings.get(0)).focus();
        return false;
      }

      //se envian los datos fila por fila
      var data = serializar('#data thead') + '&clase=' + clase + '&';
      enviarFila(jQuery('#data tbody tr:not(.ok):visible'), data, 0);
    });

    return false;
  });

  jQuery('tr.error :input').one('change', function() {
    jQuery(this).closest('tr').removeClass('error').removeAttr('title');
  });
});

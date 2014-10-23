// Obtiene previsualizacion del formato html

var intrvl = 0;

function guardar() {
    $('#opc').val('guardar');
    $('#form_doc').submit();
    window.location.reload();
}

function PrevisualizarCarta() {

    var id_cobro = $("#id_cobro").val();
    var formato = $('#carta\\[formato\\]').val();
    var existecobro = ExisteCobro(id_cobro);

    if (id_cobro === '') {
        alert('Es necesario definir un numero de cobro para previsualizar una carta');
    } else {

        if (existecobro === false) {
            $("#errmsg").html("No existe cobro").show().fadeOut(1600);
        } else {

            $.post("ajax_doc_mngr.php", {
                accion: "obtener_carta",
                id_cobro: id_cobro,
                formato: formato
            }).done(function (data) {
                $("#letter_preview").html(data);
            });
        }
    }
}

function Cargarformato(id_carta) {
    var urlajaxnrelcharges = 'ajax_doc_mngr.php?accion=obtenenrelncobros&id_carta=' + id_carta;
    var urlajaxgethtml = 'ajax_doc_mngr.php?accion=obtener_html&id_carta=' + id_carta;
    var urlajaxgetcss = 'ajax_doc_mngr.php?accion=obtener_css&id_carta=' + id_carta;

    $.get(urlajaxnrelcharges, function (data) {
        $("#nrel_charges").html(data);
    });
    $.get(urlajaxgethtml, function (data) {
        $("#carta\\[formato\\]").html(data);
    });
    $.get(urlajaxgetcss, function (data) {
        $("#carta\\[formato_css\\]").html(data);
    });
}

// Function Existe
function ExisteCobro(id_cobro) {

    var existecobro;
    var urlajax = 'ajax_doc_mngr.php?accion=existe_cobro&id_cobro=' + id_cobro;
    $.ajax(urlajax, {
        method: 'get',
        async: false,
        dataType: 'json',
        success: function (data) {
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

    var txtarea = $('#carta\\[formato\\]')[0];
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

$(function () {

    // Observa si hay cambios en el selector de formatos.
    // Carga carta[formato] y carta[formato_css]. Además obtiene cantidad de cobros asociados.

    $('#carta\\[id_carta\\]').change(function () {
        var id_carta = $('#carta\\[id_carta\\]').val();
        Cargarformato(id_carta);
    });

    // Observa si hay cambios en el selector de seccion para mostrar tags relacionados a esta.
    $("#secciones").on('change', function () {
        var seccion = $(this).val();
        var urlajax = 'ajax_doc_mngr.php?accion=obtener_tags&seccion=' + seccion;
        $("#tags").html('<option>Cargando...</option>');
        $.get(urlajax, function (data) {
            $("#tag_selector").html(data);
        });
    });

    // Obteniendo Previsualizacion ( formato, formato_css )
    $('#id_cobro').change(function () {
        PrevisualizarCarta();
    });

    // Obteniendo Previsualizacion del formato (live)
    $('#carta\\[formato\\]').on('input', function () {
        clearInterval(intrvl);
        intrvl = setInterval(PrevisualizarCarta, 1000);
    });

    $('#id_new_formato').change(function () {
        var id_formato = $('#id_new_formato').val();
        Cargarformato(id_formato);
    });

    // Elimina Formato seleccionado.
    $('#eliminar_formato').click(function () {
        var id_carta = $('#carta\\[id_carta\\]').val();
        var nombre_formato = $("#carta\\[id_carta\\] option:selected").text();
        var urlajax = 'ajax_doc_mngr.php?accion=eliminar_formato&id_carta=' + id_carta;
        $.get(urlajax, function (data) {
            alert('El formato ' + nombre_formato + ' fue eliminado satisfactoriamente');
        });
        location.reload();
    });

    $('#guardar_nuevo').click(function () {
        guardar();
    });

    $('#guardar_formato').click(function () {
        guardar();
    });

    $('#insrt_seccion').click(function () {
        var seccion = $("#secciones option:selected").val();
        InsertarEnTextArea(seccion, 'seccion');
    });

    $('#insrt_tag').click(function () {
        var seccion = $("#tag_selector option:selected").val();
        InsertarEnTextArea(seccion, 'tag');
    });
    
    $('#btn_previsualizar').click(function () {
        
        $('#form_doc').submit();
    });

});

jQuery(document).ready(function ($) {
    $('#tabs').tab();

    // Verifica que input solo acepte numeros y no letras.
    $("#id_cobro").keypress(function (e) {
        //if the letter is not digit then display error and don't type anything
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            //display error message
            $("#errmsg").html("Ingrese Solo Numeros").show().fadeOut(1600);
            return false;
        }
    });
});
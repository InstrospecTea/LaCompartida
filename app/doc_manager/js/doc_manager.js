// Obtiene previsualizacion del formato html

function ObtenerCarta() {

    var id_cobro = $("#id_cobro").val();
    var formato_html = $('#formato_html').val();

    if (id_cobro === '') {
        alert('Es necesario definir un numero de cobro para previsualizar una carta');
    } else {
        $.post("ajax_doc_mngr.php", {
            accion: "obtener_carta",
            id_cobro: id_cobro,
            formato_html: formato_html
        })
                .done(function (data) {
                    $("#letter_preview").html(data);
                });
    }
}

function InsertarValor() {
    $('#formato_html').val('%'+$('#secciones').val()+'%');
}

$(function () {

    // Observa si hay cambios en el selector de formatos.
    // Carga formato_html y formato_css. Además obtiene cantida de cobros asociados.

    $('#id_carta').change(function () {

        var id_carta = $('#id_carta').val();

        var urlajaxnrelcharges = 'ajax_doc_mngr.php?accion=obtenenrelncobros&id_carta=' + id_carta;
        var urlajaxgethtml = 'ajax_doc_mngr.php?accion=obtener_html&id_carta=' + id_carta;
        var urlajaxgetcss = 'ajax_doc_mngr.php?accion=obtener_css&id_carta=' + id_carta;

        $.get(urlajaxnrelcharges, function (data) {
            $("#nrel_charges").html(data);
        });
        $.get(urlajaxgethtml, function (data) {
            $("#formato_html").html(data);
        });
        $.get(urlajaxgetcss, function (data) {
            $("#formato_css").html(data);
        });
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

    // Obteniendo Previsualizacion desde base de datos
    $('#id_cobro').change(function () {
        ObtenerCarta();
    });

    $('#formato_html').on('input', function () {
        ObtenerCarta();
    });

    // Obtiene Documento Word desde la base de datos
    $('#btn_previsualizar').click(function () {
        $('#opc').val('prev');
        $('#form_doc').submit();
    });

    // Elimina Formato seleccionado.
    $('#eliminar_formato').click(function () {
        var id_carta = $('#id_carta').val();
        var nombre_formato = $("#id_carta option:selected").text();
        var urlajax = 'ajax_doc_mngr.php?accion=eliminar_formato&id_carta=' + id_carta;
        console.log(urlajax);
        $.get(urlajax, function (data) {
            alert('El formato ' + nombre_formato + ' fue eliminado satisfactoriamente');
        });
        location.reload();
    });

});

jQuery(document).ready(function ($) {
    $('#tabs').tab();

    // Segmento de codigo solo incremnta el largo de el plugin CKEDITOR
    // Descomentar y definir alto.

//     CKEDITOR.on('instanceReady', function () {
//         var textEditHeight = $(".textPanel").height();
//         var ckTopHeight = $("#cke_1_top").height();
//         var ckContentsHeight = $("#cke_1_contents").height();
//         for (var i = 1; i < 10; i++) {
//             $("#cke_" + i + "_contents").height("400px");
//         }
//     });
});
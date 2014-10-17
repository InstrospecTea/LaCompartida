jQuery(document).ready(function ($) {
    $('#tabs').tab();

    // Segmento de codigo solo incremnta el largo de el plugin CKEDITOR
    // Descomentar y definir alto.

    // CKEDITOR.on('instanceReady', function () {
    //     var textEditHeight = $(".textPanel").height();
    //     var ckTopHeight = $("#cke_1_top").height();
    //     var ckContentsHeight = $("#cke_1_contents").height();
    //     for (var i = 1; i < 10; i++) {
    //         $("#cke_" + i + "_contents").height("400px");
    //     }
    // });
});

$(function () {

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

    $("#secciones").on('change', function () {
        var seccion = $(this).val();
        var urlajax = 'ajax_doc_mngr.php?accion=obtener_tags&seccion=' + seccion;

        $("#tags").html('<option>Cargando...</option>');
        $.get(urlajax, function (data) {
            $("#tag_selector").html(data);
        });
    });

    $("#formato_html").change(function () {
        var id_cobro = $("#id_cobro").val();
        var id_carta = $("#id_carta").val();
        var urlajax = 'ajax_doc_mngr.php?accion=obtener_carta&preview_doc=1&id_carta=' + id_carta + '&id_cobro=' + id_cobro;

        if (id_cobro === '') {
            alert('Es necesario definir un numero de cobro para previsualizar una carta');
        } else {
            $.get(urlajax, function (data) {
                $("#letter_preview").html(data);
            });
        }

    });
});
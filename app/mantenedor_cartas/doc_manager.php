<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$CartaCobro = new CartaCobro($sesion);
$DocManager = new DocManager($sesion);

// Imprime encabezado html con librerias requeridas.
echo $DocManager->GetHtmlHeader();

//$opc = 'prev';
$id_carta = 1;

if ($opc == 'guardar') {
    $id_carta = $CartaCobro->GuardarCarta($_POST['carta']);
    die(json_encode(array('id' => $id_carta)));
} else if ($opc == 'prev') {
    $id_carta = $CartaCobro->PrevisualizarDocumento($carta, $id_cobro);
} else {
    $carta = $CartaCobro->ObtenerCarta($id_carta);
    $cobros_asociados = $DocManager->GetNumOfAsociatedCharges($sesion, $id_carta);
}

// Obtiene Secciones y tags.
$secciones = UtilesApp::mergeKeyValue($CartaCobro->secciones['CARTA']);
$tags = UtilesApp::mergeKeyValue($CartaCobro->diccionario['FECHA']);
?>

<script type="text/javascript">

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
</script>

<!-- Encabezado pagina -->

<div class="container-fluid">
    <div class="col-sm-4"><h4>Mantenedor de Cartas</h4></div>
    <div class="col-sm-4"><?php echo Html::SelectQuery($sesion, 'SELECT id_carta, descripcion FROM carta', 'id_carta', $id_carta, 'class="form-control"', ' ', ''); ?></div>
    <div class="col-sm-4"></div>
</div>

<div class="col-md-6">
    <div class="panel panel-default">
        <div class="panel-heading">
            <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
                <li class="active"><a href="#html_code" data-toggle="tab">(HTML)</a></li>
                <li><a href="#css_code" data-toggle="tab">(CSS)</a></li>
            </ul>
        </div>
        <div class="panel-body">
            <div id="content">
                <div id="my-tab-content" class="tab-content">
                    <div class="tab-pane active" id="html_code">

                        <div class="row">
                            <div class="col-md-1"></div>
                            <div class="col-md-9">
                                <?php echo $DocManager->ImprimirSelector($secciones, 'secciones'); ?>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary btn-sm">Insertar</button>
                            </div>

                        </div>
                        <div class="row" style="margin-bottom: 2%;">

                            <div class="col-md-1"></div>
                            <div class="col-md-9">
                                <?php echo $DocManager->ImprimirSelector($tags, 'tags'); ?>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary btn-sm">Insertar</button>
                            </div>

                        </div>

                        <textarea class="ckeditor" id="formato_html" name="formato_html" style="width:100%; height: 525px;"><?php echo $carta['formato']; ?></textarea>
                    </div>

                    <div class="tab-pane" id="css_code">
                        <textarea name="formato_css" style="width:100%; height: 605px;"><?php echo $carta['formato_css']; ?></textarea>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="col-md-6">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-2"></div>
                <div class="col-md-3">
                    <h5>Revisando Cobro N°</h5>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control"  name="doc_id_cobro" value="">
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-default">Descargar Word</button>
                </div>
            </div>
        </div>

        <div class="panel-body">
            <iframe id="previsualizacion_html" style="width:100%;height:536px"></iframe>
        </div>

        <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-2">
                <button type="button" class="btn btn-default">Margenes</button>
            </div>
            <div class="col-md-5">
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <strong> Cobros asociados </strong> <?php echo $cobros_asociados ?>
                </div>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-danger">Eliminar</button>
                <button type="button" class="btn btn-success">Guardar</button>
            </div>
        </div>
    </div>
</div>



<?php
echo $DocManager->GetHtmlFooter();

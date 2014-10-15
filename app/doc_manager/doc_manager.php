<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$CartaCobro = new CartaCobro($sesion);
$DocManager = new DocManager($sesion);

// Imprime encabezado html con librerias requeridas.
echo $DocManager->GetHtmlHeader();

pr($_POST);
//$opc = 'prev';
//$id_carta = 1;

if ($opc == 'guardar') {
    $id_carta = $CartaCobro->GuardarCarta($_POST['carta']);
    die(json_encode(array('id' => $id_carta)));
} else if ($opc == 'prev') {
    $id_carta = $CartaCobro->PrevisualizarDocumento($carta, $id_cobro);
} else {
    $carta = $CartaCobro->ObtenerCarta($id_carta);
    if (!empty($id_carta)) {
        $cobros_asociados = $DocManager->GetNumOfAsociatedCharges($sesion, $id_carta);
    }
}

echo $CartaCobro->GenerarDocumentoCarta2($parser_carta, $theTag = '', $lang, $moneda_cliente_cambio, $moneda_cli, & $idioma, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $cliente, $id_carta);

// Obtiene Secciones y tags.
$secciones = UtilesApp::mergeKeyValue($CartaCobro->secciones['CARTA']);
$tags = UtilesApp::mergeKeyValue($CartaCobro->diccionario['FECHA']);
?>

<script type="text/javascript">

    jQuery(document).ready(function($) {
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

    function GenerarHTML(seccion) {
        var id = 'editor_' + seccion;

        var html = CKEDITOR.instances[id] ? CKEDITOR.instances[id].getData() : jQuery('#' + id).val();
        if (!html) {
            return '';
        }

        jQuery.each(secciones[seccion] || [], function(s) {
            var tag = '%' + s + '%';
            if (html.indexOf(tag) >= 0) {
                html = html.replace(tag, GenerarHTML(s));
            }
        });
        return html;
    }

    $(function() {
        $('#id_carta').change(function() {
            this.form.submit();
        });

        jQuery('#btn_previsualizar_html').click(function() {
            PrevisualizarHTML();
        });
        function PrevisualizarHTML() {
            var css = jQuery('[name="formato_css"]').val();
            var body = GenerarHTML('CARTA');
            var html = '<style type="text/css">' + css + '</style>' + body;
            jQuery('#previsualizacion_html')[0].contentWindow.document.body.innerHTML = html;
        }
    });

</script>

<!-- Encabezado pagina -->

<div class="container">
    <form>
        <div class="col-sm-4"><h4>Mantenedor de Cartas</h4></div>
        <div class="col-sm-4"><?php echo Html::SelectQuery($sesion, 'SELECT id_carta, descripcion FROM carta', 'id_carta', $id_carta, 'class="form-control"', ' ', ''); ?></div>
        <div class="col-sm-4"><button id="btn_previsualizar_html" type="button" class="btn btn-primary btn-sm">Previsualizar</button></div>
    </form>
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
                                <?php echo $DocManager->ImprimirSelector($secciones, 'secciones', ' ', 'form-control', ''); ?>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary btn-sm">Insertar</button>
                            </div>

                        </div>
                        <div class="row" style="margin-bottom: 2%;">

                            <div class="col-md-1"></div>
                            <div class="col-md-9">
                                <?php echo $DocManager->ImprimirSelector($tags, '$tags', ' ', 'form-control', ''); ?>
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
                <div class="col-md-5">
                    <div class="input-group">
                        <input type="text" class="form-control" maxlength="10">
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="button">Descargar Word</button>
                        </span>
                    </div><!-- /input-group -->
                </div>
                <div class="col-md-2"></div>
            </div>
        </div>

        <div class="panel-body">
            <iframe id="previsualizacion_html" style="width:100%;height:536px"></iframe>
        </div>

        <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-2">
                <button type="button" class="btn btn-default" data-toggle="modal" data-target=".margenes">Margenes</button>
            </div>
            <div class="col-md-5">
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <p style="text-align: right;"><strong> Cobros asociados </strong> <?php echo!empty($cobros_asociados) ? $cobros_asociados : "0"; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-danger">Eliminar</button>
                <button type="button" class="btn btn-success">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para definir margenes -->
<div class="modal fade margenes" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header" align="center">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3 class="modal-title" id="myModalLabel">Configuracion de Margenes</h3>
            </div>

            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-2"><h5>Superior :</h5></div>
                <div class="col-md-2"><input type="text" class="form-control" name="carta[margen_superior]" value="<?php echo $carta['margen_superior']; ?>"/></div>
                <div class="col-md-4"></div>
            </div>

            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-2"><h5>Inferior :</h5></div>
                <div class="col-md-2"><input type="text" class="form-control" name="carta[margen_superior]" value="<?php echo $carta['margen_superior']; ?>"/></div>
                <div class="col-md-4"></div>
            </div>

            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-2"><h5>Izquierdo :</h5></div>
                <div class="col-md-2"><input type="text" class="form-control" name="carta[margen_superior]" value="<?php echo $carta['margen_superior']; ?>"/></div>
                <div class="col-md-4"></div>
            </div>

            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-2"><h5>Derecho :</h5></div>
                <div class="col-md-2"><input type="text" class="form-control" name="carta[margen_superior]" value="<?php echo $carta['margen_superior']; ?>"/></div>
                <div class="col-md-4"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                <button value="1" name="guardar" id="guardar" class="btn btn-success">Confirmar</button>
            </div>

        </div>

    </div>
</div>

<?php
echo $DocManager->GetHtmlFooter();

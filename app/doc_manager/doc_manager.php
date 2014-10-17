<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$CartaCobro = new CartaCobro($sesion);
$DocManager = new DocManager($sesion);

// Imprime encabezado html con librerias requeridas.

echo $DocManager->GetHtmlHeader();

if ($opc == 'guardar') {
    $id_carta = $CartaCobro->GuardarCarta($_POST['carta']);
} else if ($opc == 'prev') {
    $id_carta = $CartaCobro->PrevisualizarDocumento($carta, $id_cobro);
} else {
    $carta = $CartaCobro->ObtenerCarta($id_carta);
}

// Obtiene arreglo que contiene secciones.
$secciones = UtilesApp::mergeKeyValue($CartaCobro->secciones['CARTA']);
?>

<!-- Encabezado mantenedor -->

<div class="container" style="margin-top: 0.5%;">
    <form role="form" id="formato_doc" method="post">    
        <div class="col-sm-4"><h4>Mantenedor de Cartas</h4></div>
        <div class="col-sm-4"><?php echo Html::SelectQuery($sesion, 'SELECT id_carta, descripcion FROM carta', 'id_carta', $id_carta, 'class="form-control"', ' ', ''); ?></div>
        <div class="col-sm-4" id="nrel_charges"></div>
</div>

<!-- Panel HTML-CSS del mantenedor -->


<div class="col-md-6">
    <div class="panel panel-default">

        <div class="panel-heading">
            <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
                <li class="active"><a href="#html_code" data-toggle="tab">HTML</a></li>
                <li><a href="#css_code" data-toggle="tab">CSS</a></li>
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
                                <select id="tag_selector" class="form-control"></select>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary btn-sm">Insertar</button>
                            </div>

                        </div>

                        <textarea class="ckeditor" id="formato_html" name="formato_html" style="width:100%; height: 550px;"></textarea>
                    </div>

                    <div class="tab-pane" id="css_code">
                        <textarea id="formato_css" name="formato_css" style="width:100%; height: 630px;"></textarea>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Panel Previsualizacion HTML -->

<div class="col-md-6">
    <div class="panel panel-default">

        <div class="panel-heading">
            <div class="row">
                <div class="col-md-4">
                    <h6>Previsualizando liquidación N°</h6>
                </div>
                <div class="col-md-3">
                    <input id="id_cobro" name="id_cobro" type="text" class="form-control" maxlength="10" value="<?php echo $id_cobro ?>">
                </div>
                <div class="col-md-5">
                    <button id="btn_previsualizar" name="btn_previsualizar" class="btn btn-default pull-right" type="button">Descargar Word</button>
                </div>
            </div>
        </div>

        <div class="panel-body" style="height:618px; overflow-y:scroll;">
            <div id="letter_preview" class="col-md-12"></div>
        </div>

        <div class="panel-footer">
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-default" data-toggle="modal" data-target=".margenes">Margenes</button>
                </div>
                <div class="col-md-5">

                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger">Eliminar</button>
                    <button type="button" class="btn btn-success">Guardar</button>
                </div>
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
                <div class="col-md-2"><input type="text" class="form-control" name="carta[margen_inferior]" value="<?php echo $carta['margen_inferior']; ?>"/></div>
                <div class="col-md-4"></div>
            </div>

            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-2"><h5>Izquierdo :</h5></div>
                <div class="col-md-2"><input type="text" class="form-control" name="carta[margen_derecho]" value="<?php echo $carta['margen_derecho']; ?>"/></div>
                <div class="col-md-4"></div>
            </div>

            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-2"><h5>Derecho :</h5></div>
                <div class="col-md-2"><input type="text" class="form-control" name="carta[margen_izquierdo]" value="<?php echo $carta['margen_izquierdo']; ?>"/></div>
                <div class="col-md-4"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                <button value="1" name="guardar" id="guardar" class="btn btn-success">Confirmar</button>
            </div>

        </div>

    </div>
</form>
</div>


<?php
echo $DocManager->GetHtmlFooter();

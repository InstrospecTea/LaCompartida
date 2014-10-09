<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$CartaCobro = new CartaCobro($sesion);
$DocManager = new DocManager($sesion);
$Form = new Form;

echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';

// Librerias utilizadas
echo '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script>';
echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">';
echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">';
echo '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>';

echo '<body>';

$id_carta = 1; // Utilizado para previsualizar elementos

if ($opc == 'guardar') {
    $id_carta = $CartaCobro->GuardarCarta($_POST['carta']);
    die(json_encode(array('id' => $id_carta)));
} else if ($opc == 'prev') {
    $id_carta = $CartaCobro->PrevisualizarDocumento($carta, $id_cobro);
} else {
    $carta = $CartaCobro->ObtenerCarta($id_carta);
}

$secciones = UtilesApp::mergeKeyValue($CartaCobro->secciones['CARTA']);

$tags = UtilesApp::mergeKeyValue($CartaCobro->diccionario['DETALLE']);
?>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('#tabs').tab();
    });
</script>

<!-- Controles del mantenedor -->

<div class="container-fluid">

    <h3>Mantenedor de Cartas</h3>

    <div class="panel panel-default">
        <form>
            <div class="row">
                <div class="col-md-6"> 
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Controles del mantenedor:</strong><br>
                            Formato: <?php echo Html::SelectQuery($sesion, 'SELECT id_carta, descripcion FROM carta', 'id_carta', $id_carta, '', ' '); ?>
                            <button type="button" class="btn btn-default btn-sm">Editar</button>
                            <button type="button" class="btn btn-success btn-sm">Guardar</button>
                            <button type="button" class="btn btn-warning btn-sm">Guardar como nuevo</button>
                            <button type="button" class="btn btn-danger btn-sm">Eliminar</button>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Controles del mantenedor:</strong><br>
                            <div class="col-xs-3">
                                <input type="text" class="form-control" placeholder="Numero Cobro">
                            </div>
                            <button type="button" class="btn btn-default btn-sm">Descargar Word</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6"><label>Propiedades del documento:</label>
                    <div cla class="row">
                        <div class="col-md-4">
                            Nombre del formato: <input type="text" class="form-control"  name="carta[descripcion]" value="<?php echo $carta['descripcion']; ?>"/><br/>
                        </div>
                        <div class="col-md-3">
                            Margen Superior: <input type="text" class="form-control"  name="carta[margen_superior]" value="<?php echo $carta['margen_superior']; ?>"/>
                            Margen Izquierdo: <input type="text" class="form-control"  name="carta[margen_izquierdo]" value="<?php echo $carta['margen_izquierdo']; ?>"/>
                            Margen Header: <input  type="text" class="form-control"  name="carta[margen_encabezado]" value="<?php echo $carta['margen_encabezado']; ?>"/>
                        </div>
                        <div class="col-md-3">
                            Margen Inferior: <input type="text" class="form-control"  name="carta[margen_inferior]" value="<?php echo $carta['margen_inferior']; ?>"/>
                            Margen Derecho: <input type="text" class="form-control" name="carta[margen_derecho]" value="<?php echo $carta['margen_derecho']; ?>"/>
                            Margen Footer: <input type="text" class="form-control" name="carta[margen_pie_de_pagina]" value="<?php echo $carta['margen_pie_de_pagina']; ?>"/><br/>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="panel panel-default">
        <div class="row">
            <div class="col-md-1">
                <strong>Secciones:</strong>
            </div>
            <div class="col-md-9">
                <?php echo $DocManager->ArraySelector($secciones, 'secciones'); ?>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-default btn-sm">Insertar Seccion</button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-1">
                <strong>Tags:</strong>
            </div>
            <div class="col-md-9">
                <?php echo $DocManager->ArraySelector($tags, 'tags'); ?>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-default btn-sm">Insertar Tag</button>
            </div>
        </div>
    </div>

    <!-- Paneles de renderizado -->

    <div class="panel panel-default">
        <div class="row">

            <div class="col-md-6">

                <div id="content">

                    <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
                        <li class="active"><a href="#html_code" data-toggle="tab">Código (Template HTML)</a></li>
                        <li><a href="#css_code" data-toggle="tab">Código (Template CSS)</a></li>
                    </ul>

                    <div id="my-tab-content" class="tab-content">
                        <div class="tab-pane active" id="html_code">
                            <textarea name="nota[formato_html]" style="width:800px; height: 500px;"><?php echo $carta['formato']; ?></textarea>
                        </div>
                        <div class="tab-pane" id="css_code">
                            <textarea name="nota[formato_css]" style="width:800px; height: 500px;"><?php echo $carta['formato_css']; ?></textarea>
                        </div>
                    </div>
                </div>

            </div> 

            <div class="col-md-6" id="rendered_template">
                <div class="row" style="text-align: center;">
                    <h4>Vista Previa</h4>
                </div>
                <img src="//placehold.it/800x500" class="img-responsive">
            </div>

        </div>
    </div>
</div>

<?php
echo '</body>';
echo '</html>';

<?php
require_once dirname(__FILE__) . '/../../app/conf.php';
require_once Conf::ServerDir() . '/../fw/funciones/funciones.php';
require_once 'Tablas.php';

$Sesion = new Sesion(array('ADM'));
$pagina = new Pagina($Sesion);
$Tablas = new Tablas($Sesion);

$pagina->titulo = __('Mantención de Tablas');
$pagina->PrintTop();
?>
<div class="loader-overlay" style="z-index: 1000; opacity:.8; background: #fff; top: 0; left: 0; right: 0; bottom: 0; position: fixed;"></div>
<div class="loader-overlay" style="z-index: 1001; top: 0; left: 0; right: 0; bottom: 0; position: fixed; height: 100%; width: 100%;">
    <img alt="cargando" src="//estaticos.thetimebilling.com/templates/cargando.gif" style="background: #fff; margin:150px; padding: 20px; border: 1px solid #eee;"/>
</div>

<div id="configuracion" class="tabs">
    <ul id="tabs"></ul>
    <?php
    $errores = array();
    $query = "SELECT * FROM prm_mantencion_tablas";
    $tablasresult = mysql_query($query, $Sesion->dbh);
    while ($fila = mysql_fetch_assoc($tablasresult)) {
        if (!$Tablas->exists($fila['nombre_tabla'])) {
            $errores[$fila['id_tabla']] = "Tabla {$fila['nombre_tabla']} no existe.";
            continue;
        }

        if (isset($fila['visible']) && $fila['visible'] == '0') {
            echo "{$fila['nombre_tabla']} no visible";
            continue;
        }

        if ($fila['nombre_tabla'] == 'prm_mantencion_tablas' && !$Sesion->usuario->es('SADM')) {
            continue;
        }

        printf('<div class="grupoconf" id="caja%s" rel="%s|%s"> ', $fila['id_tabla'], $fila['glosa_tabla'], $fila['nombre_tabla']);
        printf('<a href="#" onclick="return editar(%s);"><img border="0" src="%s/agregar.gif"> Agregar Registro</a>', "'{$fila['nombre_tabla']}'", Conf::ImgDir());
        if (!empty($fila['info_tabla'])) {
            printf('<p class="info_tabla"><strong>Descripción:</strong> %s</p>', $fila['info_tabla']);
        } else {
            echo '<br/><br/>';
        }
        echo $Tablas->printTable($fila['nombre_tabla'], 1);
        echo '</div>';
    }
    ?>
</div>
<?php if (!empty($errores) && $Sesion->usuario->es('SADM')) { ?>
    <h3>Errores</h3>
    <ul>
        <?php foreach ($errores as $id => $error) { ?>
            <li id="error_<?php echo $id; ?>">
                <?php echo $error; ?>
                <input type="button" value="<?php echo __('Eliminar'); ?>" onclick="eliminar_fila(<?php echo $id; ?>)">
            </li>
        <?php } ?>
    </ul>
<?php } ?>
<script type="text/javascript">
    function eliminar_fila(id) {
        var url = 'ajax_tablas.php';
        jQuery.post(url, {accion: 'eliminar', id: id}, function (data) {
            if (data.success) {
                jQuery('#prm_mantencion_tablas_' + id).remove();
                jQuery('#error_' + id).remove();
            } else {
                alert('Ocurrio un error al eliminar.');
            }
        }, 'json');
    }

    function editar(table, id) {
        var url = 'editar.php';
        var nuevo = id;

        jQuery.post(url, {'tabla': table, id: id, nuevo: nuevo}, function (html) {
            jQuery('<div/>').html(html).dialog({title: 'Tabla ' + table, width: 400, height: 300, modal: true});
        }, 'html');

        return false;
    }

    function guardar_tabla(form) {
        var url = jQuery(form).attr('action');
        jQuery.post(url, jQuery(form).serialize(), function (data) {
            if (data.success) {
                if (data.new) {
                    var row = jQuery('#' + data.row_id);
                    jQuery(data.row).insertBefore(row);
                    row.remove();
                } else {
                    jQuery('#' + data.table + ' tbody').append(data.row);
                }

                jQuery('#' + data.row_id).click(editar_fila);

            } else {
                if (data.error) {
                    alert('Ocurrio un error al guardar: ' + data.error);
                } else {
                    alert('Ocurrio un error al guardar.');
                }
            }
            cerrar_tabla(form);
        }, 'json');
    }

    function cerrar_tabla(form) {
        jQuery(form).closest('.ui-dialog-content').dialog('destroy').remove();
    }

    function editar_fila() {
        var table = jQuery(this).closest('table').attr('id');
        var id = jQuery(this).data('row-id');
        editar(table, id);
    }

    function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : event.keyCode

        if (charCode == 46) { // punto
            return true;
        }

        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }

        return true;
    }

    jQuery(document).ready(function () {

        jQuery('.editable').click(editar_fila);

        jQuery('.grupoconf').hover(function () {
            var tablazo = jQuery(this).attr('rel').split('|');
            jQuery('#nombretabla').val(tablazo[1]);
        });

        jQuery('.grupoconf').each(function () {
            var LaID = jQuery(this).attr('id');
            var Glosa = jQuery(this).attr('rel').split('|');
            jQuery('#tabs').append('<li><a  rel="' + Glosa[1] + '" href="#' + LaID + '">' + Glosa[0] + '</a></li>');
        });

        jQuery('#guardalangs').click(function () {
            jQuery.post('../../app/ajax.php', jQuery('#formlangs').serialize(), function (data) {
                jQuery('#langs li').remove();
                jQuery('#langs').append(data);
                jQuery(".buttonset").buttonset();
                jQuery('.sortable').sortable();
            });
        });

        jQuery('#guardaplugins').click(function () {
            jQuery.post('../../app/ajax.php', jQuery('#formplugins').serialize(), function (data) {
                jQuery('#plugins li').remove();
                jQuery('#plugins').append(data);
                jQuery(".buttonset").buttonset();
                jQuery('.sortable').sortable();
            });
        });

        if (typeof jQueryUI == 'undefined') {
            jQuery('.loader-overlay').fadeOut(300, function () {
                jQuery(this).remove();
            });
        } else {
            jQueryUI.done(function () {
                jQuery('.loader-overlay').fadeOut(300, function () {
                    jQuery(this).remove();
                });
            });
        }
    });
</script>
<style type="text/css">
    tr.editable:hover {
        background-color: #bcff5c;
        cursor: pointer;
    }

    .dynaInput {
        border: none;
        background: #ffc;
        font: 12px/24px Tahoma, Arial, Geneva, sans-serif;
        width: 100%;
        height: 100%;
    }

    .dynaDiv {
        display: block;
        height: 100%;
    }

    #langs { list-style-type: none; margin: 0; padding: 0; width: 340px;margin:auto; }
    #langs li { list-style:none; margin: 0 2px 1px 3px; padding: 0.2em; padding-left: 1.5em; font-size: 1.4em; height: 18px; }

    #langs li .ui-state-default {width: 200px;height: 20px;padding-left:15px;text-align:left;}
    #langs li label span {height:20px;}
    #langs li span.ui-icon {position:relative;margin: 2px 0 0 -20px;}

    .info_tabla {
        color: #666;
        background-color: #eee;
        border: 1px solid #ccc;
        text-align: left;
        padding: .5em 1em;
    }
</style>
<?php
$pagina->PrintBottom();


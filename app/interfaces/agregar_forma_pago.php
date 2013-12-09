<?php
    require_once dirname(__FILE__).'/../conf.php';

    $sesion = new Sesion();
    $pagina = new Pagina($sesion);
    $group = 'PRM_FACTURA_MX_METOD';
    $formaPago = new PrmCodigo($sesion, "", array('grupo' => $group));

    if ($opcion == 'guardar') {
        $txt_tipo = "guardado";
        if (!empty($id_codigo)) {
            if($formaPago->Load($id_codigo)) {
                $txt_tipo = "editado";
            }
        }
        if (!is_null($codigo) && !empty($codigo)) {
            $formaPago->Edit('codigo', $codigo);
        } else {
            $formaPago->Edit('codigo', $formaPago->nextCode());
        }
        $formaPago->Edit('glosa', $glosa);
        $formaPago->Edit('grupo', $group);
        if ($formaPago->Write()){
            $pagina->AddInfo( __('Método de Pago') . ' ' . $txt_tipo . ' ' . __('con éxito.'));
            ?>
            <script type="text/javascript">
                window.opener.location.reload();
            </script>
            <?php
            $formaPago = new PrmCodigo($sesion, "", array('grupo' => $group));
        }
    } else if ( $opcion == 'eliminar' ) {
        if (!empty($id_codigo)) {
            if ($formaPago->Load($id_codigo)) {
                if ($formaPago->Delete()) {
                    $pagina->AddInfo( __('Método de Pago eliminado con éxito.'));
                    ?>
                    <script type="text/javascript">
                        window.opener.location.reload();
                    </script>
                    <?php
                } else {
                    $pagina->AddError($formaPago->error);
                }
            }
        }
        unset($id_codigo);
        unset($glosa);
        unset($codigo);
        $formaPago = new PrmCodigo($sesion, "", array('grupo' => $group));
    }

    $pagina->titulo = $txt_pagina;
    $pagina->PrintTop($popup);
?>

<script type="text/javascript">

    function EditarCodigo(id) {
        jQuery('#id_codigo').val(id)
        jQuery('#codigo').val(jQuery('#codigo_' + id).val());
        jQuery('#glosa').val(jQuery('#glosa_' + id).val());
        jQuery('#glosa').focus();
    }

    function Nuevo() {
        jQuery('#id_codigo').val("");
        jQuery('#codigo').val("");
        jQuery('#glosa').val("");
        jQuery('#codigo').focus();
    }

    function EliminarCodigo(id) {
        if( confirm('¿Está seguro que quiere eliminar el Método de pago?.') ) {
            jQuery('#id_codigo').val(id)
            jQuery('#opcion').val('eliminar');
            jQuery('#form_documentos').submit();
            return true;
        } else {
            return false;
        }
    }

    var continuar = 1;
    function Guardar(form) {
        if (continuar == 0) {
            return false;
        }

        if (form.glosa.value == '') {
            alert('<?=__('Debe ingresar la glosa del Método de pago')?>');
            form.glosa.focus();
            return false;
        }

        form.opcion.value='guardar';
        form.submit();
    }
</script>

<form method=post action="" id="form_documentos" autocomplete='off'>
<input type=hidden name="opcion" id="opcion" value="guardar" />
<input type=hidden name="id_codigo" id="id_codigo" value="" />
<input type=hidden name="codigo" value="<?php echo $formaPago->fields['codigo'] ? $formaPago->fields['codigo'] : '' ?>" id="codigo" />

<table width='90%'>
    <tr>
        <td align=left><b><?=$txt_pagina ?></b></td>
    </tr>
</table>

<table style="border: 0px solid black;" width='90%'>
    <tr>
        <td align=left width="50%">
            <b><?=__('Información del Método de pago') ?> </b>
        </td>
    </tr>
</table>
<table style="border: 1px solid black;" width='90%'>
    <tr>
        <td align="right">
            <?=__('Glosa')?>
        </td>
        <td colspan="3" align="left">
            <input type="text" name="glosa" value="<?php echo $formaPago->fields['glosa'] ? $formaPago->fields['glosa'] : '' ?>" id="glosa" size="50" maxlength="50" />
        </td>
    </tr>
</table>
<br>
<table style="border: 0px solid black;" width='90%'>
    <tr>
        <td align=left>
            <input type=button class=btn value="<?=__('Guardar')?>" onclick='Guardar(this.form);' />
            <input type=button class=btn value="<?=__('Cerrar')?>" onclick="Cerrar();" />
        </td>
    </tr>
</table>
<?php
    $x_pag = 15;
    $b = new Buscador($sesion, $formaPago->query(), "Objeto", 0, 0, "glosa ASC");
    $b->mensaje_error_fecha = "N/A";
    $b->nombre = "busc_codigo";
    $b->titulo = __('Listado de').' '.__('Métodos de pago');
    $b->titulo .= "<table width=100%>";
    $b->AgregarEncabezado("id_codigo",__('id'),"align=center");
    $b->AgregarEncabezado("codigo",__('Código'),"align=center");
    $b->AgregarEncabezado("glosa",__('Glosa'),"align=center");
    $b->AgregarFuncion("Opciones",'Opciones',"align=center nowrap");
    $b->color_mouse_over = "#bcff5c";

    $b->Imprimir("",array(),false);

    function Opciones2(& $fila) {
        echo '<br>'.Conf::ImgDir();
        $opc_html = "<input type='hidden' value='".$fila->fields['codigo']."' id='codigo_".$fila->fields['id_codigo']."'  name='codigo_".$fila->fields['id_codigo']."'>";
        $opc_html .= "<input type='hidden' value='".$fila->fields['glosa']."' id='glosa_".$fila->fields['id_codigo']."'  name='glosa_".$fila->fields['id_codigo']."'>";
        $opc_html .= "<img src=".Conf::ImgDir()."/editar_on.gif' border=0 title='Editar' onClick='Editar(this.form,".$fila->fields['id_codigo'].")'/>";
        return $opc_html;
    }
    function Opciones(& $fila) {
        $id_codigo = $fila->fields['id_codigo'];
        $opc_html = "<input type='hidden' value='".$fila->fields['codigo']."' id='codigo_".$id_codigo."'  name='codigo_".$id_codigo."'>";
        $opc_html .= "<input type='hidden' value='".$fila->fields['glosa']."' id='glosa_".$id_codigo."'  name='glosa_".$id_codigo."'>";
        $opc_html .= "<a target=\"_parent\" onClick=EditarCodigo($id_codigo)><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title='Editar Forma de Pago'></a>";
        $opc_html .= "<a target=\"_parent\" onClick=EliminarCodigo($id_codigo)><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 title='Eliminar Forma de Pago'></a>";

        return $opc_html;
    }
?>
</form>


<?
    $pagina->PrintBottom($popup);
?>

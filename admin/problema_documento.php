<?php
/* DISCLAIMER: En honor al tiempo, se hace de esta horrible forma este admin ... me de vergüenza u.u*/

require_once dirname(__FILE__) . '/../app/conf.php';

$Sesion = new Sesion(array('ADM'));
$Pagina = new Pagina($Sesion);

if (!$Sesion->usuario->TienePermiso('SADM')) {
    die('No Autorizado');
}

$RootDir = Conf::RootDir();
$ImgDir  = Conf::ImgDir();

if( isset($ver) && $ver == 'neteo') {
    $query = "
        SELECT *
        FROM neteo_documento ND
        INNER JOIN documento D ON( ND.id_documento_cobro = D.id_documento )
        WHERE id_documento_cobro = $id_documento_cobro";
    $stm = $Sesion->pdodbh->query($query);
    $neteos = $stm->fetchAll(PDO::FETCH_OBJ);

    $html = <<<HTML
    <table>
         <tbody>
             <tr>
                 <td>Honorarios</td>
                 <td>{$neteos[0]->honorarios}</td>
             </tr>
             <tr>
                 <td>Gastos</td>
                 <td>{$neteos[0]->gastos}</td>
             </tr>
             <tr>
                 <td>Total</td>
                 <td><strong>{$neteos[0]->monto}</strong></td>
             </tr>
         </tbody>
     </table>

     <img src="{$ImgDir}/ver_persona_nuevo.gif" onclick="nuevaVentana('Editar_Cobro',730,580,'$RootDir/app/interfaces/cobros6.php?id_cobro={$neteos[0]->id_cobro}&popup=1&popup=1&contitulo=true', 'top=100, left=155');" />
    <hr>
HTML;

    $html .= <<<HTML
    <table>
        <tr>
            <th>ID Neteo</th>
            <th>Hon Cobro</th>
            <th>Gas Cobro</th>
            <th>Total Cobro</th>
            <th>Hon Pago</th>
            <th>Gas Pago</th>
            <th>Total Pago</th>
        </tr>
HTML;

    $total_cobros = $total_pagos = $total_final_cobros = $total_final_pagos = 0;
    $cantidadNeteos = count($neteos);

    foreach( $neteos as $neteo ) {
        $total_cobros = $neteo->valor_cobro_honorarios + $neteo->valor_cobro_gastos;
        $total_pagos  = $neteo->valor_pago_honorarios + $neteo->valor_pago_gastos;

        $total_final_cobros += $total_cobros;
        $total_final_pagos  += $total_pagos;

        $html .= <<<HTML
        <tr id="ND-{$neteo->id_neteo_documento}">
            <td>{$neteo->id_neteo_documento}</td>
HTML;

        if($cantidadNeteos == 1){
            $diferencia_honorarios = sprintf("%0.2f", $neteo->honorarios - $neteo->valor_cobro_honorarios);
            $diferencia_gastos     = sprintf("%0.2f", $neteo->gastos - $neteo->valor_cobro_gastos);

            $html .= <<<HTML
                <td align="center">
                    DC: {$neteo->honorarios}
                    <br>
                    <input name="valor_cobro_honorarios" type="number" value="{$neteo->valor_cobro_honorarios}"  /> <br>
                    Diff: <strong>{$diferencia_honorarios}</strong>
                </td>
                <td align="center">
                    DC: {$neteo->gastos}
                    <br>
                    <input name="valor_cobro_gastos" type="number" value="{$neteo->valor_cobro_gastos}"  /> <br>
                    Diff: <strong>{$diferencia_gastos}</strong>
                </td>

                <td align="center"><br>$total_cobros</td>

                <td align="center">
                    DC: {$neteo->gastos}
                    <br>
                    <input name="valor_pago_honorarios" type="number" value="{$neteo->valor_pago_honorarios}"  /> <br>
                    Diff: <strong>{$diferencia_gastos}</strong>
                </td>

                <td align="center">
                    DC: {$neteo->gastos}
                    <br>
                    <input name="valor_pago_gastos" type="number" value="{$neteo->valor_pago_gastos}"  /> <br>
                    Diff: <strong>{$diferencia_gastos}</strong>
                </td>

                <td align="center"><br>$total_pagos</td>

                <td>
                    <br>
                    <button onclick="saveNeteo({$neteo->id_neteo_documento})">guardar</button>
                </td>
HTML;
        } else {
           $html .= <<<HTML
                <td align="center"><input type="number" name="valor_cobro_honorarios" value="{$neteo->valor_cobro_honorarios}"  /></td>
                <td align="center"><input type="number" name="valor_cobro_gastos" value="{$neteo->valor_cobro_gastos}"  /></td>

                <td align="center">$total_cobros</td>

                <td align="center"><input type="number" name="valor_pago_honorarios" value="{$neteo->valor_pago_honorarios}"  /></td>
                <td align="center"><input type="number" name="valor_pago_gastos" value="{$neteo->valor_pago_gastos}"  /></td>

                <td align="center">$total_pagos</td>

                <td>
                    <br>
                    <button onclick="saveNeteo({$neteo->id_neteo_documento})">guardar</button>
                </td>
HTML;
        }

        $html .= <<<HTML
        </tr>
HTML;
        $total_honorarios_cobro += $neteo->valor_cobro_honorarios;
        $total_gastos_cobro     += $neteo->valor_cobro_gastos;

        $total_honorarios_pago += $neteo->valor_pago_honorarios;
        $total_gastos_pago     += $neteo->valor_pago_gastos;
    }

    $html .= <<<HTML
    <tfoot>
        <tr>
            <td>&nbsp;</td>
            <td align="center"><strong>$total_honorarios_cobro</strong></td>
            <td align="center"><strong>$total_gastos_cobro</strong></td>
            <td><strong>$total_final_cobros</strong></td>
            <td align="center"><strong>$total_honorarios_pago</strong></td>
            <td align="center"><strong>$total_gastos_pago</strong></td>
            <td align="center"><strong>$total_final_pagos</strong></td>
        </tr>
    </tfoot>
    </table>

    <hr>DC: informaci&oacute;n Documento de Cobro | Diff: Diferencia de saldos<hr>
HTML;



    $query = "SELECT FP.*
        FROM cta_cte_fact_mvto CCFM_P
        INNER JOIN cta_cte_fact_mvto_neteo CCFMN ON(CCFMN.id_mvto_pago = CCFM_P.id_cta_cte_mvto)
        INNER JOIN cta_cte_fact_mvto CCFM_C ON(CCFM_C.id_cta_cte_mvto = CCFMN.id_mvto_deuda)
        INNER JOIN factura_pago FP ON( FP.id_factura_pago = CCFM_P.id_factura_pago)
        INNER JOIN factura_cobro FC ON( FC.id_factura = CCFM_C.id_factura)

        WHERE
        FC.id_cobro = $id_cobro";

    $stm = $Sesion->pdodbh->query($query);
    $facturasPago = $stm->fetchAll(PDO::FETCH_OBJ);

    $html .= <<<HTML
    <table style="width:100%">
        <thead>
            <tr>
                <th>Numero</th>
                <th>Fecha</th>
                <th>Descripcion</th>
                <th>Monto</th>
                <th>Monto Moneda Cobro</th>
            </tr>
        </thead>
        <tbody>
HTML;

    foreach( $facturasPago as $factura ) {
        $html .= <<<HTML
        <tr>
            <td>{$factura->id_factura_pago}</td>
            <td>{$factura->fecha}</td>
            <td>{$factura->descripcion}</td>
            <td>{$factura->monto}</td>
            <td>{$factura->monto_moneda_cobro}</td>
        </tr>
HTML;
    }

$html .= <<<HTML
        </tbody>
    </table>

    <hr>
HTML;


$html .= <<<HTML
    <button onclick="jQuery('#neteos').hide();" >Cerrar</button>
HTML;


echo $html;
    exit;


} elseif( isset($ver) && $ver == 'update_documento_estado' ) {
    $pagado = strtoupper($pagado);

    $field = in_array(strtolower($field), array('gastos', 'honorarios')) ? "{$field}_pagados" : "";

    if( !$field ) exit;

    $query = "UPDATE documento SET $field = '$pagado' WHERE id_documento = $id_documento";


    $stm = $Sesion->pdodbh->query($query);

    echo $stm->rowCount();

    exit;

} elseif( isset($ver) && $ver == 'update_documento_saldo' ) {
    $pagado = strtoupper($pagado);

    $field = in_array(strtolower($field), array('gastos', 'honorarios')) ? "saldo_{$field}" : "";

    if( !$field ) exit;

    $extra = '';

    if( $saldo == 0 ) {
        $f = str_replace("saldo_", '', $field);
        $extra = ", {$f}_pagados = 'SI'";
    }

    $query = "UPDATE documento SET $field = '$saldo' $extra WHERE id_documento = $id_documento";

    $stm = $Sesion->pdodbh->query($query);

    echo $stm->rowCount();

    exit;

} elseif( isset($ver) && $ver == 'update_neteo' ) {
    $query = "UPDATE neteo_documento
                SET valor_cobro_gastos = $valor_cobro_gastos,
                    valor_cobro_honorarios = $valor_cobro_honorarios,
                    valor_pago_gastos = $valor_pago_gastos,
                    valor_pago_honorarios = $valor_pago_honorarios
                WHERE id_neteo_documento = $id_neteo_documento";

echo $query; die;
    $stm = $Sesion->pdodbh->query($query);
    echo $stm->rowCount();
    exit;
}





$query = "SELECT *
FROM
(SELECT DC.id_cobro, DC.id_documento, DC.saldo_honorarios, DC.saldo_gastos,
DC.honorarios_pagados AS DC_honorarios_pagados, DC.gastos_pagados AS DC_gastos_pagados,
round(ifnull(sum(ND.valor_cobro_honorarios), 0), M.cifras_decimales) pago_honorarios,
Round(ifnull(sum(ND.valor_cobro_gastos), 0), M.cifras_decimales) pago_gastos,
round(saldo_honorarios + saldo_gastos, M.cifras_decimales) AS saldo_documento,
round((DC.honorarios + DC.gastos) - (ifnull(sum(valor_cobro_honorarios), 0) + ifnull(sum(valor_cobro_gastos), 0)), M.cifras_decimales) AS saldo_final,
DC.monto, C.estado,
GROUP_CONCAT(ND.id_neteo_documento) AS neteos,
COUNT(ND.id_neteo_documento) AS cantidad_neteos
FROM documento DC
    INNER JOIN cobro C ON( C.id_cobro = DC.id_cobro)
    INNER JOIN prm_moneda M ON M.id_moneda = DC.id_moneda
    LEFT JOIN neteo_documento ND ON ND.id_documento_cobro = DC.id_documento
WHERE DC.tipo_doc = 'N' AND C.estado IN('PAGADO')
AND DC.fecha_creacion >='2014-01-01'
AND (
    (DC.saldo_honorarios = 0 AND DC.honorarios_pagados = 'NO') OR
    (DC.saldo_gastos = 0 AND DC.gastos_pagados = 'NO')
)
GROUP BY id_documento

UNION
SELECT C.id_cobro,  DC.id_documento, DC.saldo_honorarios, DC.saldo_gastos,
DC.honorarios_pagados AS DC_honorarios_pagados, DC.gastos_pagados AS DC_gastos_pagados,
round(ifnull(sum(ND.valor_cobro_honorarios), 0), M.cifras_decimales) pago_honorarios,
round(ifnull(sum(ND.valor_cobro_gastos), 0), M.cifras_decimales) pago_gastos,
round(saldo_honorarios + saldo_gastos, M.cifras_decimales) AS saldo_documento,
round((DC.honorarios + DC.gastos) - (ifnull(sum(valor_cobro_honorarios), 0) + ifnull(sum(valor_cobro_gastos), 0)), M.cifras_decimales) AS saldo_final,
DC.monto, C.estado,
GROUP_CONCAT(ND.id_neteo_documento) AS neteos,
COUNT(ND.id_neteo_documento) AS cantidad_neteos
FROM documento DC
INNER JOIN cobro C ON( C.id_cobro = DC.id_cobro)
INNER JOIN prm_moneda M ON M.id_moneda = DC.id_moneda
LEFT JOIN neteo_documento ND ON id_documento_cobro = id_documento
WHERE tipo_doc='N'
  AND DC.fecha_creacion >= '2014-01-01'
GROUP BY id_documento
HAVING saldo_documento <> saldo_final -- AND saldo_final < 10
) AS un
ORDER BY id_cobro";

$documentos = $Sesion->pdodbh->query($query)->fetchAll( PDO::FETCH_OBJ );

$Pagina->titulo = __(count($documentos) . ' Saldos a corregir');

$Pagina->PrintTop();
?>


<table>
    <thead>
        <tr>
            <th>Documento</th>
            <th>Honorarios Pagados</th>
            <th>Saldo Honorarios</th>
            <th>Gastos Pagados</th>
            <th>Saldo Gastos</th>
            <th>Diferencia entre DocCobro y Neteos</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($documentos as $documento): ?>
        <tr>
            <td>
                <p>Cobro #<?=$documento->id_cobro ?> <img src="<?= Conf::ImgDir() ?>/ver_persona_nuevo.gif" onclick="nuevaVentana('Editar_Cobro',730,580,'<?= Conf::RootDir() ?>/app/interfaces/cobros6.php?id_cobro=<?=$documento->id_cobro?>&popup=1&popup=1&contitulo=true', 'top=100, left=155');" /> <br><strong><?= $documento->estado ?></strong></p>
            </td>
            <td id="estado_honorarios-<?= $documento->id_documento ?>" >
                <p style="text-align:center">
                <?php if ($documento->DC_honorarios_pagados == 'NO' && $documento->saldo_honorarios == 0): ?>

                    <label for="DC_honorarios_pagados-<?= $documento->id_documento ?>-SI">SI</label>
                    <input type="radio" name="DC_honorarios_pagados-<?= $documento->id_documento ?>" id="DC_honorarios_pagados-<?= $documento->id_documento ?>-SI" value="SI"  <?= $documento->DC_honorarios_pagados == 'SI' ? 'checked' :'' ?> >

                    <label for="DC_honorarios_pagados-<?= $documento->id_documento ?>-NO">NO</label>
                    <input type="radio" name="DC_honorarios_pagados-<?= $documento->id_documento ?>" id="DC_honorarios_pagados-<?= $documento->id_documento ?>-NO" value="NO" <?= $documento->DC_honorarios_pagados == 'NO' ? 'checked' :'' ?> >

                    <br>

                    <button onclick="saveDocumentoEstado(<?= $documento->id_documento ?>, 'honorarios')">guardar</button>

                <?php elseif( $documento->DC_honorarios_pagados == 'NO' ): ?>
                    <span>Saldo Pendiente</span>
                <?php else: ?>
                    <span>SI</span>
                <?php endif ?>
                </p>
            </td>

            <td id="saldo_honorarios-<?= $documento->id_documento ?>">
                <p>
                    <input type="number" value="<?= $documento->saldo_honorarios ?>" name="saldo_honorarios" <?= $documento->saldo_honorarios == 0 ? 'disabled': '' ?>>
                    <?php if( $documento->saldo_honorarios != 0 ): ?>
                    <button onclick="saveDocumentoSaldo(<?= $documento->id_documento ?>, 'honorarios')">guardar</button>
                <?php endif ?>
                </p>
            </td>

            <td id="estado_gastos-<?= $documento->id_documento ?>" >
                <p style="text-align:center">
                <?php if( $documento->DC_gastos_pagados == 'NO' && $documento->saldo_gastos == 0): ?>
                    <label for="DC_gastos_pagados-<?= $documento->id_documento ?>-SI">SI</label>
                    <input type="radio" name="DC_gastos_pagados-<?= $documento->id_documento ?>" id="DC_gastos_pagados-<?= $documento->id_documento ?>-SI" value="SI" <?= $documento->DC_gastos_pagados == 'SI' ? 'checked' :'' ?> >

                    <label for="DC_gastos_pagados-<?= $documento->id_documento ?>-NO">NO</label>
                    <input type="radio" name="DC_gastos_pagados-<?= $documento->id_documento ?>" id="DC_gastos_pagados-<?= $documento->id_documento ?>-NO" value="NO"  <?= $documento->DC_gastos_pagados == 'NO' ? 'checked' :'' ?>  >

                    <br>

                    <button onclick="saveDocumentoEstado(<?= $documento->id_documento ?>, 'gastos')">guardar</button>

                <?php elseif( $documento->DC_gastos_pagados == 'NO' ): ?>
                    <span>Saldo Pendiente</span>

                <?php else: ?>
                    <span>SI</span>
                 <?php endif ?>
                </p>
            </td>

            <td id="saldo_gastos-<?= $documento->id_documento ?>">
                <p>
                    <input type="number" value="<?= $documento->saldo_gastos ?>" name="saldo_gastos" <?= $documento->saldo_gastos == 0 ? 'disabled': '' ?>>
                <?php if( $documento->saldo_gastos != 0 ): ?>
                    <button onclick="saveDocumentoSaldo(<?= $documento->id_documento ?>, 'gastos')">guardar</button>
                <?php endif ?>
                </p>
            </td>
            <td>
            <?php if( $documento->cantidad_neteos > 0 ): ?>
                <p><?= abs($documento->monto - ($documento->pago_gastos + $documento->pago_honorarios)) < 0.1 ? 'NP' : sprintf("D: %0.2f - ND: %0.2f = Dif: <strong>%0.2f</strong>", $documento->monto, $documento->pago_gastos + $documento->pago_honorarios,
                    $documento->monto - ($documento->pago_gastos + $documento->pago_honorarios)) ?>

                    <?php if( abs($documento->monto - ($documento->pago_gastos + $documento->pago_honorarios)) >= 0.1 ): ?>
                        <img src="<?= Conf::ImgDir() ?>/ver_persona_nuevo.gif" onclick="verNeteo(<?= $documento->id_documento?>, <?= $documento->id_cobro ?>);
            /*nuevaVentana('Editar_Cobro',730,580,'<?= $RootDir ?>/app/interfaces/cobros6.php?id_cobro=<?= $documento->id_cobro ?>&popup=1&popup=1&contitulo=true', 'top=100, left=155');*/" />
                    <?php endif ?>
            </p>
            <?php else: ?>
                <p>Documento sin Neteos</p>
            <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>

<div id="neteos"></div>

<style>
tr:hover {
    background:rgba(0,0,0,0.1);
    opacity:1;
}

tr:not(:hover) {
    /*opacity:.5;*/
}

td{
    border-bottom: 1px solid #333;
}

td input {
    width:50px;
}

td input[type="radio"] {
  width: 20px;
}

input:not([disabled]) {
    border:1px solid red;
}

#neteos {
    display:none;
    background: white;
    width:600px;
    height:600px;
    overflow: scroll;
    position:fixed;
    top:50%; left:50%;
    margin-left: -300px;
    margin-top:-300px;
    border:3px solid #333;
}

</style>

<script>
function verNeteo(id_documento_cobro, id_cobro) {
    jQuery('#neteos').html('');
    var
        url  = 'problema_documento.php?ver=neteo';
        url += "&id_documento_cobro=" + id_documento_cobro;
        url += "&id_cobro=" + id_cobro;

    jQuery.get(
        url,
        function(response){
            jQuery('#neteos').html( response ).show();
        })
}

function saveNeteo(id_neteo_documento) {
    var nd = jQuery("#ND-" + id_neteo_documento),
        valorCobroGastos     = nd.find('[name="valor_cobro_gastos"]').val(),
        valorCobroHonorarios = nd.find('[name="valor_cobro_honorarios"]').val(),

        valorPagoGastos     = nd.find('[name="valor_pago_gastos"]').val(),
        valorPagoHonorarios = nd.find('[name="valor_pago_honorarios"]').val();

    jQuery.post(
        "problema_documento.php?ver=update_neteo",
        {
            id_neteo_documento :    id_neteo_documento,
            valor_cobro_honorarios: valorCobroHonorarios,
            valor_cobro_gastos:     valorCobroGastos,
            valor_pago_honorarios:  valorPagoHonorarios,
            valor_pago_gastos:      valorPagoGastos
        },
        function(response){
            alert(response + " Documentos Actualizados");

            if( response != 0 ) {
                window.location.reload()
            }
        }
    )
}

function saveDocumentoEstado(id_documento, which){
    var doc = jQuery("#estado_" + which + "-" + id_documento),
        pagado = doc.find('[name="DC_' + which + '_pagados-' + id_documento + '"]:checked').val() ;

    jQuery.post(
        "problema_documento.php?ver=update_documento_estado&field=" + which,
        {
            field: which,
            pagado: pagado,
            id_documento: id_documento
        },
        function( response ){
            alert(response + " Documentos Actualizados");

            if( response != 0 ) {
                window.location.reload()
            }
        }
    )
}

function saveDocumentoSaldo(id_documento, which){
    var doc = jQuery("#saldo_" + which + "-" + id_documento),
        saldo = doc.find('[name="saldo_' + which + '"]').val();

    jQuery.post(
        "problema_documento.php?ver=update_documento_saldo&field=" + which,
        {
            field: which,
            saldo: saldo,
            id_documento: id_documento
        },
        function( response ){
            alert(response + " Documentos Actualizados");

            if( response != 0 ) {
                window.location.reload()
            }
        }
    )
}
</script>

<?php
$Pagina->PrintBottom();

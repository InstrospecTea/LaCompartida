<?php
/* DISCLAIMER: En honor al tiempo, se hace de esta horrible forma este admin ... me de vergüenza u.u*/

require_once dirname(__FILE__) . '/../app/conf.php';

$Sesion = new Sesion(array('ADM'));
$Pagina = new Pagina($Sesion);
$Pagina->titulo = __('Documentos con problemas de saldo');

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
    <hr><hr>
HTML;

    $html .= <<<HTML
    <table>
        <tr>
            <th>ID Neteo</th>
            <th>Honorarios</th>
            <th>Gastos</th>
            <th>Total</th>
        </tr>
HTML;

    $total_honorarios = $total_gastos = $total_final = 0;
    $cantidadNeteos = count($neteos);

    foreach( $neteos as $neteo ) {
        $total = $neteo->valor_cobro_honorarios + $neteo->valor_cobro_gastos;
        $total_final += $total;
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

                <td>
                    <br>
                    <button onclick="saveNeteo({$neteo->id_neteo_documento})">guardar</button>
                </td>
HTML;
        } else {
           $html .= <<<HTML
                <td align="center"><input type="number" value="{$neteo->valor_cobro_honorarios}"  /></td>
                <td align="center"><input type="number" value="{$neteo->valor_cobro_gastos}"  /></td>
HTML;
        }

        $html .= <<<HTML
            <td align="center"><br>{$total} </td>
        </tr>
HTML;
        $total_honorarios += $neteo->valor_cobro_honorarios;
        $total_gastos += $neteo->valor_cobro_gastos;
    }

    $html .= <<<HTML
    <tfoot>
        <tr>
            <td>&nbsp;</td>
            <td align="center"><strong>$total_honorarios</strong></td>
            <td align="center"><strong>$total_gastos</strong></td>
            <td>&nbsp;</td>
            <td align="center"><strong>$total_final</strong></td>
        </tr>
    </tfoot>
    </table>

    <hr>DC: informaci&oacute;n Documento de Cobro | Diff: Diferencia de saldos<hr>
    <button onclick="jQuery('#neteos').hide();" >Cerrar</button>
HTML;


echo $html;
    exit;


} elseif( isset($ver) && $ver == 'update_neteo' ) {
    $query = "UPDATE neteo_documento
                SET valor_cobro_gastos = $valor_cobro_gastos,
                    valor_cobro_honorarios = $valor_cobro_honorarios
                WHERE id_neteo_documento = $id_neteo_documento";

    $stm = $Sesion->pdodbh->query($query);

    echo $stm->rowCount() . " Rows updated";
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
GROUP_CONCAT(ND.id_neteo_documento) AS neteos
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
GROUP_CONCAT(ND.id_neteo_documento) AS neteos
FROM documento DC
INNER JOIN cobro C ON( C.id_cobro = DC.id_cobro)
INNER JOIN prm_moneda M ON M.id_moneda = DC.id_moneda
LEFT JOIN neteo_documento ND ON id_documento_cobro = id_documento
WHERE tipo_doc='N'
  AND DC.fecha_creacion >= '2014-01-01'
GROUP BY id_documento
HAVING saldo_documento <> saldo_final AND saldo_final < 10
) AS un
ORDER BY id_cobro";

$documentos = $Sesion->pdodbh->query($query)->fetchAll( PDO::FETCH_OBJ );

$Pagina->PrintTop();
?>

<table>
    <thead>
        <tr>
            <th>Documento</th>
            <th>Honorarios Pagados</th>
            <th>Gastos Pagados</th>
            <th>Saldo Honorarios</th>
            <th>Saldo Gastos</th>
            <th>Diferencia entre DocCobro y Neteos</th>
            <th>&nbsp;</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($documentos as $documento): ?>
        <tr>
            <td>
                <p>Cobro #<?=$documento->id_cobro ?> <img src="<?= Conf::ImgDir() ?>/ver_persona_nuevo.gif" onclick="nuevaVentana('Editar_Cobro',730,580,'<?= Conf::RootDir() ?>/app/interfaces/cobros6.php?id_cobro=<?=$documento->id_cobro?>&popup=1&popup=1&contitulo=true', 'top=100, left=155');" /> <br><strong><?= $documento->estado ?></strong></p>
            </td>
            <td >
                <p style="text-align:center">
                <?php if ($documento->DC_honorarios_pagados == 'NO' && $documento->saldo_honorarios == 0): ?>

                    <label for="DC_honorarios_pagados-<?= $documento->id_cobro ?>">NO</label>
                    <input type="radio" name="DC_honorarios_pagados-<?= $documento->id_cobro ?>" id="DC_honorarios_pagados-<?= $documento->id_cobro ?>" value="NO" <?= $documento->DC_honorarios_pagados == 'NO' ? 'checked' :'' ?> >
                    <label for="DC_honorarios_pagados-<?= $documento->id_cobro ?>">SI</label>
                    <input type="radio" name="DC_honorarios_pagados-<?= $documento->id_cobro ?>" id="DC_honorarios_pagados-<?= $documento->id_cobro ?>" value="SI"  <?= $documento->DC_honorarios_pagados == 'SI' ? 'checked' :'' ?> >
                <?php else: ?>
                    Saldado
                <?php endif ?>
                </p>
            </td>
            <td>
                <p style="text-align:center">
                <?php if ($documento->DC_gastos_pagados == 'NO' && $documento->saldo_gastos == 0): ?>

                    <label for="DC_gastos_pagados-<?= $documento->id_cobro ?>">NO</label>
                    <input type="radio" name="DC_gastos_pagados-<?= $documento->id_cobro ?>" id="DC_gastos_pagados-<?= $documento->id_cobro ?>" value="NO"  <?= $documento->DC_gastos_pagados == 'NO' ? 'checked' :'' ?>  >
                    <label for="DC_gastos_pagados-<?= $documento->id_cobro ?>">SI</label>
                    <input type="radio" name="DC_gastos_pagados-<?= $documento->id_cobro ?>" id="DC_gastos_pagados-<?= $documento->id_cobro ?>" value="SI" <?= $documento->DC_gastos_pagados == 'SI' ? 'checked' :'' ?> >
                <?php else: ?>
                    Saldado
                 <?php endif ?>
                </p>
            </td>
            <td>
                <p>
                    <input type="number" value="<?= $documento->saldo_honorarios ?>" <?= $documento->saldo_honorarios == 0 ? 'disabled': '' ?>>
                </p>
            </td>
            <td>
                <p>
                    <input type="number" value="<?= $documento->saldo_gastos ?>" <?= $documento->saldo_gastos == 0 ? 'disabled': '' ?>>
                </p>
            </td>
            <td>
                <p><?= abs($documento->monto - ($documento->pago_gastos + $documento->pago_honorarios)) < 0.1 ? 'NP' : sprintf("D: %0.2f - ND: %0.2f = Dif: %0.2f", $documento->monto, $documento->pago_gastos + $documento->pago_honorarios,
                    $documento->monto - ($documento->pago_gastos + $documento->pago_honorarios)) ?>

                    <?php if( abs($documento->monto - ($documento->pago_gastos + $documento->pago_honorarios)) >= 0.1 ): ?>
                        <img src="<?= Conf::ImgDir() ?>/ver_persona_nuevo.gif" onclick="verNeteo(<?= $documento->id_documento?>, <?= $documento->id_cobro ?>);
            nuevaVentana('Editar_Cobro',730,580,'<?= $RootDir ?>/app/interfaces/cobros6.php?id_cobro=<?= $documento->id_cobro ?>&popup=1&popup=1&contitulo=true', 'top=100, left=155');" />
                    <?php endif ?>
            </p>
            </td>
            <td></td>
            <td></td>
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

input:not([disabled]) {
    border:1px solid red;
}

#neteos {
    display:none;
    background: white;
    width:400px;
    height:400px;
    overflow: scroll;
    position:fixed;
    top:50%; left:50%;
    margin-left: -200px;
    margin-top:-200px;
    border:3px solid #333;
}

</style>

<script>
function verNeteo(id_documento_cobro, id_cobro) {
    jQuery('#neteos').html('');
    jQuery.get(
        'problema_documento.php?ver=neteo&id_documento_cobro=' + id_documento_cobro,
        function(response){
            jQuery('#neteos').html( response ).show();
        })
}

function saveNeteo(id_neteo_documento) {
    var nd = jQuery("#ND-" + id_neteo_documento),
        valorCobroGastos     = nd.find('[name="valor_cobro_gastos"]').val(),
        valorCobroHonorarios = nd.find('[name="valor_cobro_honorarios"]').val();

    jQuery.post(
        "problema_documento.php?ver=update_neteo",
        {
            id_neteo_documento : id_neteo_documento,
            valor_cobro_honorarios: valorCobroHonorarios,
            valor_cobro_gastos: valorCobroGastos
        },
        function(response){
            alert( response );
            window.location.reload()
        }
    )
}
</script>

<?php
$Pagina->PrintBottom();

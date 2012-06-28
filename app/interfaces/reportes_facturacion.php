<?
    require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/classes/InputId.php';
    require_once Conf::ServerDir().'/classes/Trabajo.php';


    $sesion = new Sesion(array('REP'));
    $pagina = new Pagina($sesion);

	if($opcion == 'desplegar')
    {
        $pagina->Redirect("planillas/planilla_factura_cobros.php?anio=$anio");
    }

    $pagina->titulo = "Reportes";
    $pagina->PrintTop();
?>
<form method=post name="formaulario">
<!-- action="<?= $_SERVER[PHP_SELF] ?>">-->
<input type=hidden name=opcion value="desplegar" />

<table style="border: 1px solid black;">
    <tr>
        <td align=right>
            Periodo
        </td>
        <td align=left>
			<select name="anio">
				<?for($anio=2005;$anio<=2015;$anio++){?>
				<option value="<?=$anio?>"><?=$anio?></option>
				<?}?>
			</select>
        </td>
    </tr>
<!--
    <tr>
        <td align=right>
            Cliente
        </td>
        <td align=left>
            <?= Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente FROM cliente ORDER BY glosa_cliente", "codigo_cliente[]", $codigo_cliente,"multiple") ?>
        </td>
    </tr>
-->
    <tr>
        <td colspan=4 align=right>
            <input type=submit class=btn value="Generar reporte" />
        </td>
    </tr>

</table>

</form>

<?
    echo(InputId::Javascript($sesion));
    $pagina->PrintBottom();
?>


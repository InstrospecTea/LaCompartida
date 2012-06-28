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

    if($id_usuario == "")
        $id_usuario = $sesion->usuario->fields['id_usuario'];

	if($id_usuario != '' && $opcion == 'desplegar')
	{
		list($a1,$m1,$d1) = split("-", $fecha1);
        list($a2,$m2,$d2) = split("-", $fecha2);
        $fechaini=$a1."-". $m1 ."-01";
        $fechafin=$a2."-". $m2."-01";
        $periodos = ceil(($m2-$m1 + 12*($a2-$a1)));

        $wherein = "";
        foreach($id_usuario as $user)
        {
            $wherein .= $co.$user;
            $co = ',';
        }

		$pagina->Redirect("planillas/planilla_horas_usuarios.php?fechaini=$fechaini&fechafin=$fechafin&wherein=$wherein&periodos=$periodos");
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
            Fecha desde
        </td>
        <td align=left>
            <?= Html::PrintCalendar("fecha1", "$fecha1"); ?>
        </td>
    </tr>
    <tr>
        <td align=right>
            Fecha hasta
        </td>
        <td align=left>
            <?= Html::PrintCalendar("fecha2", "$fecha2"); ?>
        </td>
    </tr>
    <tr>
        <td align=right>
            Usuario
        </td>
        <td align=left>
            <?= Html::SelectQuery($sesion,"SELECT id_usuario,CONCAT_WS(', ',apellido1,nombre) FROM usuario ORDER BY apellido1", "id_usuario[]", $id_usuario,"multiple") ?>
        </td>
	</tr>
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

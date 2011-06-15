<?
    require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../app/classes/InputId.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/classes/Funciones.php';

    $sesion = new Sesion(array('REP'));
    $pagina = new Pagina($sesion);
    $id_usuario = $sesion->usuario->fields['id_usuario'];

    $glosa_operaciones = array("Duración","Duración cobrada","Cantidad");
    if($excel == 1) //excel
        $operaciones = array("Duración" => "SUM(TIME_TO_SEC(duracion)) /(3600*24.0000)",
                            "Duración cobrada" => "SUM( IF(cobrable = 1,TIME_TO_SEC(duracion_cobrada),0)) /(3600*24.0000)",
                            "Cantidad" => "COUNT(*)");
    else //pantalla
        $operaciones = array("Duración" => "FORMAT(SUM(TIME_TO_SEC(duracion))/(3600),1)",
                                "Duración cobrada" => "FORMAT(SUM( IF( cobrable = 1, TIME_TO_SEC(duracion_cobrada),0))/(3600),1)",
                                "Cantidad" => "COUNT(*)");

		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
			$glosa_usuario = "username_usuario";
		else
			$glosa_usuario = "nombre_usuario";
    $dimensiones = array($glosa_usuario,"glosa_actividad","glosa_cliente","glosa_asunto","forma_cobro","cobrable","glosa_moneda","glosa_tipo_proyecto");
    $glosa_dimensiones = array("Usuario","Actividad","Cliente",__('Asunto'),"Forma de cobro",__('Asunto')." cobrable","Moneda","Tipo ".__('asunto'));

    $operacion2 = $operaciones[$operacion];


    $query1 = "SELECT 
    							CONCAT_WS(', ',usuario.apellido1,usuario.nombre) as nombre_usuario, 
    							usuario.username as username_usuario,
    							prm_moneda.glosa_moneda, 
    							trabajo.codigo_asunto, 
    							trabajo.codigo_actividad, 
    							prm_tipo_proyecto.*,
                	asunto.forma_cobro, 
                	asunto.cobrable as asunto_cobrable,
                	asunto.glosa_asunto, 
                	cliente.glosa_cliente, 
                	actividad.glosa_actividad, 
                	trabajo.duracion, 
                	trabajo.duracion_cobrada, 
                	trabajo.cobrable 
                FROM trabajo LEFT JOIN asunto ON asunto.codigo_asunto = trabajo.codigo_asunto
                JOIN contrato  ON asunto.id_contrato = contrato.id_contrato 
                LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente 
                LEFT JOIN actividad ON trabajo.codigo_actividad = actividad.`codigo_actividad` 
                LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario 
                LEFT JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda 
                LEFT JOIN prm_tipo_proyecto ON asunto.id_tipo_asunto = prm_tipo_proyecto.id_tipo_proyecto
                WHERE trabajo.fecha BETWEEN '$fecha1' AND '$fecha2'";


    if($accion == "mostrar")
    { 
		$query = "SELECT $dimension1,$dimension2,$operacion2 FROM ( $query1 ) AS olap
                    GROUP BY $dimension1,$dimension2";

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		for($i = 0; list($d1,$d2,$c) = mysql_fetch_array($resp); $i++)
        {
            if($d1 == "")
                $d1 = "Vacío";
            if($d2 == "")
                $d2 = "Vacío";
            $d1s[$i] = $d1;
            $d2s[$i] = $d2;
            $result[$d1][$d2] = $c;
        }
        if(is_array($d1s))
            $d1s = array_values(array_unique($d1s));
        if(is_array($d2s))
            $d2s = array_values(array_unique($d2s));

        if($excel)
        {
            require_once('olap.xls.php');
            exit;
        }

        for($i = 0; $i < count($d2s); $i++)
            $encabezado .= "<td>".$d2s[$i]."</td>";
        $tabla = "<div style=\"width: 900px; display:block; overflow: scroll;\"><table border=1 style=\"border-collapse: collapse; border: 1px solid black\"><tr><td></td>$encabezado</tr>";

        
        for($i = 0; $i < count($d1s); $i++)
        {
            $tabla .= "<tr>";
            for($j = 0; $j < count($d2s); $j++)
            {
                if($j == 0)
                    $tabla .= "<td>".$d1s[$i]."</td>";
                if(stristr($result[$d1s[$i]][$d2s[$j]],":"))
                {
                    list($h,$m,$s) = split(":",$result[$d1s[$i]][$d2s[$j]]);
                    $res = "$h:$m";
                }
                else
                    $res = $result[$d1s[$i]][$d2s[$j]];

                $tabla .= "<td align=right>".$res."</td>";
            }
            $tabla .= "</tr>";
        }
        $tabla .= "</table></div>";
    }
    $pagina->titulo = __('Reporte genérico');
    $pagina->PrintTop();
?>
<form method=post>
<input type=hidden name=accion value=mostrar>
<input type=hidden name=excel value="0">
<table class="border_plomo tb_base">
    <tr>
        <td>
            <?=__('Dimensión 1')?>
        </td>
        <td>
            <?= SelectDimension($dimensiones,$glosa_dimensiones,"dimension1",$dimension1) ?>
        </td>
    </tr>
    <tr>
        <td>
            <?=__('Dimensión 2')?>
        </td>
        <td>
            <?= SelectDimension($dimensiones,$glosa_dimensiones,"dimension2",$dimension2) ?>
        </td>
    </tr>
    <tr>
        <td>
            <?=__('Fecha inicio')?>
        </td>
        <td>
            <?= Html::PrintCalendar("fecha1",$fecha1) ?>
        </td>
    </tr>
    <tr>
        <td>
            <?=__('Fecha fin')?>
        </td>
        <td>
            <?= Html::PrintCalendar("fecha2",$fecha2) ?>
        </td>
    </tr>
    <tr>
        <td>
            <?=__('Trabajos')?>
        </td>
        <td>
            <?= SelectDimension($glosa_operaciones,$glosa_operaciones,"operacion",$operacion) ?>
        </td>
    </tr>
    <tr>
        <td colspan=2 align=center>
            <input type=submit class=btn onclick="this.form.excel.value=0;" value=<?=__('Desplegar')?>>
            <input type=submit class=btn onclick="this.form.excel.value=1;" value=<?=__('Excel')?>>
        </td>
    </tr>
</table>

<br />
<br />

<?= $tabla ?>
<?
    $pagina->PrintBottom();

    function SelectDimension( $array_valores, $array_glosas, $name, $selected='', $opciones='', $titulo='')
    {
        $select = "<select name='$name' $opciones style='width: 150px;'>";
        if($titulo != '')
            $select .= "<option value=''>".$titulo."</option>\n";

        for($i = 0; $i < count($array_valores); $i++)
        {
            if($array_valores[$i] == $selected)
                $select .= "<option value='${array_valores[$i]}' selected>${array_glosas[$i]}</option>\n";
            else
                $select .= "<option value='${array_valores[$i]}'>${array_glosas[$i]}</option>\n";
        }

        $select .= "</select>";

        return $select;
    }
?>
<script type="text/javascript">
function setDateDefecto()
{
    hoy = new Date();//tiene hora actual
    hoy.setHours(0,0,0,0);
    ninety_days = new Date();
    ninety_days.setDate(hoy.getDate()-30);

    if(fecha1_Object.picked.date.getTime() == hoy.getTime())
        fecha1_Object.setValor(ninety_days);
}
setDateDefecto();
</script>

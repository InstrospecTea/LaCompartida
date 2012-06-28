<?
    require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Html.php';
    require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/classes/InputId.php';
    require_once Conf::ServerDir().'/classes/Trabajo.php';
    require_once Conf::ServerDir().'/classes/Funciones.php';

    $sesion = new Sesion(array('REV'));
    $pagina = new Pagina($sesion);
    $id_usuario = $sesion->usuario->fields['id_usuario'];

    $t = new Trabajo($sesion);

    if($id_trab > 0)
        $t->Load($id_trab);

    if($opcion == "eliminar")
    {
        $t = new Trabajo($sesion);
        $t->Load($id_trabajo);
        if($t->Estado() == "Abierto")
            if(! $t->Eliminar() )
                $pagina->AddError($t->error);
    }

    $pagina->titulo = __('Revisar horas');
    $pagina->PrintTop();

    if($estado == "")
        $estado = "abiertos";
?>
<iframe name=trabajos id=trabajos src='trabajos.php?popup=1&id_usuario=<?=$id_usuario?>' frameborder=0 width=740px height=10000px></iframe>
<?
    $pagina->PrintBottom();

/*
<form method=post action="<?= $_SERVER[PHP_SELF] ?>">
<input type=hidden name=opcion value="buscar" />

<table style="border: 1px solid black;">
    <tr>
        <td align=right>
            Cliente
        </td>
        <td>
            <?= InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos');"); ?>
        </td>
        <td align=right>
                Desde
        </td>
        <td>
            <?= Html::PrintCalendar("fecha_desde", "$fecha_desde"); ?>
        </td>
    </tr>
    <tr>
        <td align=right>
            <?=__('Asunto')?>
        </td>
        <td>
            <?= InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto) ?>
        </td>
        <td align=right>
            Hasta
        </td>
        <td>
            <?= Html::PrintCalendar("fecha_hasta", "$fecha_hasta"); ?>
        </td>
    </tr>
    <tr>
        <td align=right>
            Estado
        </td>
        <td>
            <select name=estado>
                <option value=todos>Todos</option>
                <option value=abiertos <?= $estado == "abiertos" ? "selected" : ""?>>Abiertos</option>
                <option value=revisados <?= $estado == "revisados" ? "selected" : ""?>>Revisados</option>
                <option value=cobrados <?= $estado == "cobrados" ? "selected" : ""?>>Cobrados</option>
            </select>
        </td>
    <tr>
    <tr>
        <td colspan=4 align=right>
            <input type=submit value=Buscar />
        </td>
    </tr>

</table>
<br/><br/>
    
</form>
<script src=guardar_campo_trabajo.js type="text/javascript"></script>
<script type="text/javascript">
function setDateDefecto()
{
    hoy = new Date();//tiene hora actual
    hoy.setHours(0,0,0,0);
    ninety_days = new Date();
    ninety_days.setDate(hoy.getDate()-10);

    if(fecha_desde_Object.picked.date.getTime() == hoy.getTime())
        fecha_desde_Object.setValor(ninety_days);
}
setDateDefecto();
</script>
<?
    echo(InputId::Javascript($sesion));

    #if($opcion == "buscar")
    if(true)
    {
        if($fecha_desde == "")
            $fecha_desde = "DATE_SUB(NOW(), INTERVAL 10 DAY)";
        else
            $fecha_desde = "'$fecha_desde'";

        if($fecha_hasta == "")
            $fecha_hasta = "NOW()";
        else
            $fecha_hasta = "'$fecha_hasta'";

        if($estado == "abiertos")
            $where = " AND revisado=0";
        if($estado == "revisados")
            $where = " AND revisado=1 AND (id_cobro IS NULL OR id_cobro=0)";
        if($estado == "cobrados")
            $where = " AND id_cobro > 0";

        if($orden == "")
            $orden = "id_trabajo desc";

        $query = "SELECT SQL_CALC_FOUND_ROWS *,trabajo.codigo_asunto
                    FROM trabajo 
                    JOIN asunto USING(codigo_asunto)
                    LEFT JOIN actividad ON trabajo.codigo_actividad=actividad.codigo_actividad
                    LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente
                    WHERE trabajo.id_usuario = $id_usuario
                    AND fecha BETWEEN $fecha_desde AND $fecha_hasta
                    $where
                    ";
  
	    if($codigo_asunto != "")
            $query .= " AND trabajo.codigo_asunto = '$codigo_asunto'";
        else if($codigo_cliente != "")
            $query .= " AND trabajo.codigo_asunto IN (SELECT codigo_asunto FROM asunto WHERE codigo_cliente = '$codigo_cliente')";
                        
        $x_pag = 20;

        $b = new Buscador($sesion, $query, "Trabajo", $desde, $x_pag, $orden);
        $b->formato_fecha = "%d-%m-%y";
        $b->mensaje_sin_resultados = "No se encontraron trabajos que cumplan el criterio ingresado";
        $b->AgregarEncabezado("trabajo.fecha","Fecha");
        $b->AgregarEncabezado("cliente.glosa_cliente","Cliente");
        $b->AgregarEncabezado("asunto.glosa_asunto",__('Asunto'));
        $b->AgregarEncabezado("actividad.glosa_actividad","Actividad");
        $b->AgregarEncabezado("trabajo.duracion","Duración");
        $b->AgregarEncabezado("revisado","Status");
        $b->AgregarEncabezado("","Eliminar");
        $b->funcionTR = "funcionTR";
        $b->Imprimir();
    
        echo("<br/><br />");


        $query = "SELECT SQL_CALC_FOUND_ROWS glosa_cliente, SEC_TO_TIME(SUM(TIME_TO_SEC(duracion))) AS total 
                    FROM trabajo LEFT JOIN asunto USING (codigo_asunto)
                        LEFT JOIN cliente USING (codigo_cliente)
                    WHERE fecha BETWEEN $fecha_desde AND $fecha_hasta
                        AND trabajo.id_usuario = $id_usuario
                    GROUP BY glosa_cliente";
        $b = new Buscador($sesion, $query, "Trabajo", $desde, $x_pag, $orden);
        $b->no_pages = true;
        $b->mensaje_sin_resultados = "No se encontraron trabajos ";
        $b->AgregarEncabezado("glosa_cliente","Cliente","align=center");
        $b->AgregarFuncion("Horas Trabajadas","Horas","align=center");
        $b->Imprimir();
    }


    $pagina->PrintBottom();

    function funcionTR(& $trabajo)
    {
        global $sesion;
        static $i = 0;
        if($i % 2)
            $color = "#ffffff";
        else
            $color = "#eeeeee";

        if($trabajo->Estado() == "Abierto")
        {
            $editable = 1;
        }

        $formato_fecha = "%d-%m-%y";
        $fecha = Utiles::sql2fecha($trabajo->fields[fecha],$formato_fecha);
        $html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\">";
        $html .= "<td>$fecha</td>";
        $html .= "<td nowrap align=center>";
        if($editable)
            $html .= InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente$i", $trabajo->fields['codigo_cliente'],"","CargarSelect('codigo_cliente$i','codigo_asunto$i','cargar_asuntos');") ;
        else    
            $html .= $trabajo->fields['glosa_cliente'];
        $html .= "</td>";
        $html .= "<td nowrap align=center>";
        if($editable)
            $html .= InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto$i", $trabajo->fields['codigo_asunto'],"","CargarSelect('codigo_asunto$i','codigo_actividad$i','cargar_actividades');GuardarCampoTrabajo('".$trabajo->fields['id_trabajo']."','codigo_asunto',this.value)");
        else
            $html .= $trabajo->fields['glosa_asunto'];
            
        $html .= "</td>";
        $html .= "<td nowrap align=center>";
        if($editable)
            $html .= InputId::Imprimir($sesion,"actividad","codigo_actividad","glosa_actividad", "codigo_actividad$i", $trabajo->fields['codigo_actividad'],"","GuardarCampoTrabajo('".$trabajo->fields['id_trabajo']."','codigo_actividad',this.value)");
        else
            $html .= "</td>";
        $html .= "<td>";
        if($editable)
            $html .= Html::PrintTime("duracion$i",$trabajo->fields['duracion'],"onchange=\"GuardarCampoTrabajo('".$trabajo->fields['id_trabajo']."','duracion',this.value)\"");
        else
            $html .= SplitDuracion($trabajo->fields['duracion']);
        $html .= "</td>";
        $html .= "<td>".$trabajo->Estado()."</td>";
        if($editable)
            $html .= "<td align=center><a href=?opcion=eliminar&id_trabajo=".$trabajo->fields[id_trabajo]."><img src=".Conf::ImgDir()."/cruz_roja.gif border=0></a></td>";
        else
            $html .= "<td></td>";
        $html .= "</tr>";
        $html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B\">";
        $html .= "<td><strong>Descripción</strong></td><td colspan=6 align=left>";
        if($editable)
		{
#            $html .= "<textarea cols=50 onchange=\"GuardarCampoTrabajo('".$trabajo->fields['id_trabajo']."','descripcion',this.value)\">".$trabajo->fields[descripcion]."</textarea>";
			$html .= "<textarea cols=50>".$trabajo->fields[descripcion]."</textarea>";
        	$html .= "&nbsp;&nbsp;&nbsp&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=submit name='guardar' value='Guardar' onclick=\"GuardarCampoTrabajo('".$trabajo->fields['id_trabajo']."','descripcion',this.value)\">";
        }
		else
            $html .= $trabajo->fields[descripcion];
        $html .= "</td></tr>";
        $i++;
        return $html;
    }
    function SplitDuracion($time)
    {
        list($h,$m,$s) = split(":",$time);
        return $h.":".$m;
    }
	function Horas(& $fila)
    {
        global $sesion;
        $hora = $fila->fields['total'];
        return SplitDuracion($hora);
    }
*/
?>

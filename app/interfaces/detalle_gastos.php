<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/classes/Asunto.php';
    require_once Conf::ServerDir().'/classes/Cobro.php';

	$sesion = new Sesion(array('REV','ADM'));
	$pagina = new Pagina($sesion);
	$pagina->titulo = "Emitir " . __("Cobro") . " :: Seleccion de Gastos";


    $cobro = new Cobro($sesion);
    if(!$cobro->Load($id_cobro))
		$pagina->FatalError("Cobro inválido");

    $cobro->LoadAsuntos();
    $comma_separated = implode("','", $cobro->asuntos);

	if($orden == "")
		$orden = "fecha";

	$query = "SELECT SQL_CALC_FOUND_ROWS gasto.*, usuario.*, prm_moneda.*, asunto.glosa_asunto, gasto.descripcion 
				FROM gasto 
				LEFT JOIN asunto USING(codigo_asunto)
				LEFT JOIN usuario ON usuario.id_usuario=gasto.id_usuario
				LEFT JOIN prm_moneda ON gasto.id_moneda=prm_moneda.id_moneda
                LEFT JOIN usuario_tarifa ON usuario_tarifa.id_usuario = usuario.id_usuario AND gasto.id_moneda = usuario_tarifa.id_moneda
				WHERE asunto.codigo_asunto IN ('$comma_separated') 	
					AND gasto.id_cobro IS NULL AND gasto.cobrable = 1
				";

	$pagina->PrintTop(1);

	$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
	$b->nombre = "busc_gastos";
//	$b->titulo = "Gastos por Asuntos";
//    $b->AgregarFuncion("Nombre","Nombre");
    $b->AgregarEncabezado("nombre","Nombre");
	$b->AgregarEncabezado("fecha","Fecha");
	$b->AgregarEncabezado("glosa_asunto","Asunto", "align=center");
	$b->AgregarEncabezado("descripcion","Descripción");
    $b->AgregarEncabezado("monto","", "");
    $b->funcionTR = "funcionTR";
//    $b->AgregarFuncion("Monto","Monto","align=center");
//    $b->AgregarFuncion("Cobrable","Cobrable","align=center");
	$b->Imprimir();
?>
<table width=100%>
<tr>
	<td align=right>
		<strong>TOTAL GASTOS <?=$moneda_base['simbolo']?><?=number_format($total_gastos,2,',','.')?></strong>
	</td>
</tr>
</table>
<?

    function Nombre(& $fila)
    {
		return $fila->fields[apellido1].", ".$fila->fields[nombre];
    }
    function Monto(& $fila)
    {
		return $fila->fields[simbolo] . " " .number_format($fila->fields[monto],2,",",".");
    }
    function funcionTR(& $gasto)
    {
        global $sesion;
        static $i = 0;
		global $total_gastos;

        $moneda_base = Utiles::MonedaBase($sesion);

        if( $gasto->fields['tipo_cambio'] == 1)
            $tipo_cambio = "-";
        else
            $tipo_cambio = $moneda_base['simbolo'].$gasto->fields['tipo_cambio'];

		$pagado = $gasto->fields['tipo_cambio'] * $gasto->fields[monto];
		$total_gastos += $pagado;

        if($i % 2 == 0)
            $color = "#dddddd";
        else
            $color = "#ffffff";
        $formato_fecha = "%d/%m/%y";
        $fecha = Utiles::sql2fecha($gasto->fields[fecha],$formato_fecha);
        $html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; \">";
        $html .= "<td align=left>".Nombre($gasto)."</td>";
        $html .= "<td align=center>".$fecha."</td>";
        $html .= "<td align=left>".$gasto->fields['glosa_asunto']."</td>";
        $html .= "<td align=left>".$gasto->fields['descripcion']."</td>";
        $html .= "<td align=center>&nbsp;</td>";
        $html .= "</tr>";
        $html .= "<tr bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B; \">";
        $html .= "<td><strong>Moneda</strong><br>".$gasto->fields['simbolo']."</td>";
        $html .= "<td><strong>Subtotal</strong><br>".$gasto->fields['simbolo']." ".number_format($gasto->fields['monto'],2,',','.')."</td>";
		$html .= "<td></td>";
        $html .= "<td><strong>Tasa</strong><br>".$tipo_cambio."</td>";
        $html .= "<td><strong>Total</strong><br>".$moneda_base['simbolo'].number_format($pagado,2,',','.')."</td>";
        $html .= "</tr>";
        $i++;
        return $html;
    }
	$pagina->PrintBottom(1);
?>

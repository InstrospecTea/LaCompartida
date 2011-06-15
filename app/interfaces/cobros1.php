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

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);

	if($opc == "siguiente")
	{
		$pagina->Redirect("cobros2.php?codigo_cliente=".$codigo_cliente);	
	}

	$pagina->titulo = __('Emitir cobro :: Selección del Cliente');
	$pagina->PrintTop();
?>
<br><br>
<form method=post action=cobros1.php>
<input type=hidden name=opcion value="buscar">
<input type=hidden name=opc value="siguiente">
<table style="border: 1px solid black;">
	<tr>
		<td align=right>
			<strong><?=__('Cliente')?></strong>
		</td>
		<td>
			<?= InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","",200) ?>
		</td>
	</tr>
    <tr>
        <td align=right>
            <strong><?=__('Carta de Cobro')?></strong>
        </td>
        <td>
			<?=Html::SelectQuery($sesion,"SELECT contrato.id_contrato, CONCAT(contrato.id_contrato, ' ',if(cliente.id_contrato = contrato.id_contrato,'Cliente', CONCAT('".__('Asunto')." ',temporal.asuntos))) FROM contrato JOIN cliente ON contrato.codigo_cliente = cliente.codigo_cliente 
			LEFT JOIN (	SELECT id_contrato, CONCAT_WS(',',glosa_asunto) as asuntos FROM asunto GROUP BY id_contrato ) as temporal ON contrato.id_contrato = temporal.id_contrato				 WHERE contrato.codigo_cliente = 'AST'","id_usuario",$id_usuario,'','Todos','250')?>
        </td>
    </tr>
	<tr>
		<td colspan=4>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=4 align=right>
			<input type=submit class=btn value=<?=__('Siguiente')?> onclick="return Validar(this.form);">
		</td>
	</tr>
</table>
</form>
<script>
function Validar(form)
{
    if(!form.codigo_cliente.value)
    {
        alert("<?=__('Debe seleccionar un cliente')?>");
		form.codigo_cliente.focus();
        return false;
    }
    return true;
}
</script>
<?
	echo(InputId::Javascript($sesion));

	if($opcion == "buscar")
	{

		$query = "SELECT SQL_CALC_FOUND_ROWS *,trabajo.id_asunto
					FROM trabajo 
					JOIN asunto USING(id_asunto)
					LEFT JOIN actividad ON trabajo.id_actividad=actividad.id_actividad
					LEFT JOIN cliente ON cliente.id_cliente=asunto.id_cliente
					";
						
		$b = new Buscador($sesion, $query, "Trabajo", $desde, $x_pag, $orden);
		$b->AgregarEncabezado("fecha",__('Fecha'));
		$b->AgregarEncabezado("cliente",__('Cliente'));
		$b->AgregarEncabezado("titulo",__('Asunto'));
		$b->AgregarEncabezado("actividad",__('Actividad'));
		$b->AgregarEncabezado("duracion",__('Duración'));
		$b->AgregarEncabezado("status",__('Status'));
		$b->funcionTR = "funcionTR";
		$b->Imprimir();
	}


	$pagina->PrintBottom();

	function funcionTR(& $trabajo)
	{
		global $sesion;
		static $i = 0;

		$formato_fecha = "%d/%m/%Y";
		$fecha = Utiles::sql2fecha($trabajo->fields[fecha],$formato_fecha);
	 	$html .= "<tr style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B;\">";
	 	$html .= "<td>$fecha</td>";
	 	$html .= "<td>".$trabajo->fields[glosa_cliente]."</td>";
	 	$html .= "<td nowrap>". InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto$i", $trabajo->fields['codigo_asunto']) ."</td>";
	 	$html .= "<td nowrap>". InputId::Imprimir($sesion,"actividad","codigo_actividad","glosa_actividad", "codigo_actividad$i", $trabajo->fields['codigo_actividad']) ."</td>";
	 	$html .= "<td>".$trabajo->fields[duracion]."</td>";
	 	$html .= "</tr>";
	 	$html .= "<tr style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B\">";
	 	$html .= "<td><strong>Descripción</strong></td><td colspan=5><textarea cols=50>".$trabajo->fields[descripcion]."</textarea></td></tr>";
		$i++;
		return $html;
	}
?>

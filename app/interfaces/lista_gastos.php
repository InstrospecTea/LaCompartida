<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('OFI'));
	$pagina = new Pagina($sesion);
	$pagina->PrintTop(1);

	if($orden == "")
		$orden = "fecha DESC";

	$query = "SELECT SQL_CALC_FOUND_ROWS *, cta_corriente.egreso, cta_corriente.codigo_cliente
				FROM cta_corriente 
				LEFT JOIN asunto USING(codigo_asunto)
				LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
				LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
				";
	$x_pag = 10;
	$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
	$b->nombre = "busc_gastos";
	$b->titulo = "Gastos por ".__('asunto');
    $b->AgregarFuncion("Nombre",__('Nombre'));
	$b->AgregarEncabezado("fecha",__('Fecha'));
	$b->AgregarEncabezado("codigo_cliente",__('Cliente'), "align=center");
	$b->AgregarEncabezado("glosa_asunto",__('Asunto'), "align=center");
	$b->AgregarEncabezado("descripcion",__('Descripción'),"align=left");
    $b->AgregarFuncion("Egreso","Monto","align=right nowrap");
    $b->AgregarFuncion("","Opciones","align=center nowrap");
	$b->color_mouse_over = "#DF9862";
	$b->Imprimir();

    function Opciones(& $fila)
    {
		$id_gasto = $fila->fields['id_movimiento'];
        return "<a target=\"_parent\" href=gastos.php?id_gasto=$id_gasto><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar actividad></a>"
        . "<a target=_parent href='javascript:void(0)' onclick=\"parent.EliminaGasto($id_gasto)\" ><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 title=Eliminar></a>";
    }
    function Nombre(& $fila)
    {
		return $fila->fields[apellido1].", ".$fila->fields[nombre];
    }
    function Monto(& $fila)
    {
		return $fila->fields[simbolo] . " " .number_format($fila->fields[egreso],2,",",".");
    }
	$pagina->PrintBottom(1);
?>

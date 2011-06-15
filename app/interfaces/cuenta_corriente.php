<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);

	$pagina->titulo = __('Cuenta corriente');
	$pagina->PrintTop();

	$cliente = new Cliente($sesion);
	$cliente->LoadByCodigo($codigo_cliente);
	$total = number_format($cliente->TotalCuentaCorriente(),0,",",".");

	if($orden == "")
		$orden = "fecha DESC";

    #se seleccioanan todos los movimientos
	$query = "SELECT SQL_CALC_FOUND_ROWS * , fecha,ingreso,egreso,id_moneda, descripcion, id_movimiento 
				FROM cta_corriente 
				WHERE codigo_cliente = '$codigo_cliente' ";  

	$x_pag = 20;

	echo("<h1>".__('Balance cuenta corriente: $')." ".$total."</h1>");

	$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
	$b->nombre = "busc_gastos";
	$b->titulo = "Cuenta corriente $codigo_cliente";
	$b->AgregarEncabezado("fecha",__('Fecha'));
    $b->AgregarFuncion("Ingreso","Ingreso","align=right nowrap");
    $b->AgregarFuncion("Egreso","Egreso","align=right nowrap");
	$b->AgregarEncabezado("descripcion",__('Descripción'), "align=left");
    $b->AgregarFuncion("","Opciones","align=center nowrap");
	$b->color_mouse_over = "#DF9862";
	$b->Imprimir();
?>

<input type=button value="<?=__('Ingresar provisión para gastos')?>" onclick="self.location='ingreso_provision.php?codigo_cliente=<?= $codigo_cliente ?>';"><input type=button value="<?=__('Cancelar')?>" onclick="history.back(-1)">

<?
    function Opciones(& $fila)
    {
		$id_gasto = $fila->fields['id_movimiento'];
		if($id_gasto > 0)
			return "<a target=\"_parent\" href=gastos.php?id_gasto=$id_gasto><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar actividad></a>";
        
    }
    function Nombre(& $fila)
    {
		return $fila->fields[apellido1].", ".$fila->fields[nombre];
    }
    function Ingreso(& $fila)
	{
		global $sesion;
		$numero = $fila->fields[ingreso];

		$txt .= Utiles::Glosa($sesion, $fila->fields[id_moneda], "simbolo", "prm_moneda", "id_moneda") ;
		$txt .= " ";
		$txt .= number_format($numero,2,",",".");
		if($fila->fields[ingreso] > 0)
			return $txt;
    }
    function Egreso(& $fila)
	{
		global $sesion;
		$numero = $fila->fields[egreso];

		$txt .= Utiles::Glosa($sesion, $fila->fields[id_moneda], "simbolo", "prm_moneda", "id_moneda") ;
		$txt .= " ";
		$txt .= number_format($numero,2,",",".");
		if($fila->fields[egreso] > 0)
			return $txt;
    }
	$pagina->PrintBottom();
?>

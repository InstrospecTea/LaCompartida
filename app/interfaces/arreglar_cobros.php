<?
  require_once dirname(__FILE__).'/../conf.php';
  require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
  require_once Conf::ServerDir().'/../fw/classes/Pagina.php';

	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Asunto.php';
	require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
	require_once Conf::ServerDir().'/../app/classes/Gasto.php';

	$sesion = new Sesion(array('COB'));

	$pagina = new Pagina($sesion);

	$pagina->PrintTop();

	if( $fix != 1 )	$fix = 0;
?>

<table width="90%">

<?
	$query = "SELECT * FROM cobro WHERE id_contrato IS NULL AND fecha_creacion<=NOW()";
	$cobros = new ListaCobros($sesion,'',$query);

	for($i=0;$i<$cobros->num;$i++)
	{
		$cobro = $cobros->Get($i);
?>
	<tr>
		<td>
			<hr size="1">
			<b>Cobro <?=$cobro->fields['id_cobro']?>, por un monto de <?=$cobro->fields['monto']?></b><br>

<?
		$cobro->LoadAsuntos();
		$asuntos_por_coma = implode("','", $cobro->asuntos);

		// Moneda
		$moneda = new Objeto($sesion,'','','prm_moneda','id_moneda');
		$moneda->Load($cobro->fields['id_moneda']);

		if( $cobro->fields['id_moneda_base'] == 0 )
		{
			$base = Utiles::MonedaBase($sesion);
			$base_id = $base['id_moneda'];

			if( $fix != '1' ) echo " - Campo id_moneda_ base en 0 -> Se actualizará a ".$base['id_moneda']." (".$base['simbolo'].")<br>";
			$cobro->Edit('id_moneda_base', $base['id_moneda']);
		}
		else
		{
			$base_id = $cobro->fields['id_moneda_base'];
		}

		$moneda_base = new Objeto($sesion,'','','prm_moneda','id_moneda');
		$moneda_base->Load($base_id);

		if( $cobro->fields['tipo_cambio_moneda_base'] == 0 )
		{
			if( $fix != '1' ) echo " - Campo tipo_cambio_moneda_base en 0 -> Se actualizará a ".$moneda_base->fields['tipo_cambio']."<br>";
			$cobro->Edit('tipo_cambio_moneda_base', $moneda_base->fields['tipo_cambio']);
		}

		if( $cobro->fields['tipo_cambio_moneda'] == 0 )
		{
			if( $fix != '1' ) echo " - Campo tipo_cambio_moneda cobro en 0 -> Se actualizará a ".$moneda->fields['tipo_cambio']."<br>";
			$cobro->Edit('tipo_cambio_moneda', $moneda->fields['tipo_cambio']);
		}

		// Subtotal
		if( $cobro->fields['monto_subtotal'] != $cobro->fields['monto']-$cobro->fields['descuento'] )
		{
			if( $fix != '1' ) echo " - Campo monto_subtotal en ".$cobro->fields['monto_subtotal']." -> Se actualizará a ".($cobro->fields['monto']-$cobro->fields['descuento'])."<br>";
			$cobro->Edit('monto_subtotal',number_format($cobro->fields['monto']-$cobro->fields['descuento'],6,".","") );
		}

		// Variables con los subtotales del cobro
		$cobro_total_honorario_hh = 0;			// Valor total de las HH trabajados para el cobro
		$cobro_total_gastos = 0;				// Valor total de los gastos (egresos) del cobro

		// Problemas con los trabajos: tarifa_hh
		$query = "SELECT SQL_CALC_FOUND_ROWS trabajo.duracion_cobrada, trabajo.descripcion,trabajo.fecha,trabajo.id_usuario,
						trabajo.monto_cobrado, trabajo.id_moneda as id_moneda_trabajo, trabajo.id_trabajo, trabajo.tarifa_hh,
						trabajo.codigo_asunto, CONCAT_WS(' ', nombre, apellido1) as nombre_usuario
						FROM trabajo
						LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
						WHERE trabajo.id_cobro = '". $cobro->fields['id_cobro'] . "'
						ORDER BY trabajo.fecha ASC";

		$lista_trabajos = new ListaTrabajos($sesion,'',$query);

		for($z=0;$z<$lista_trabajos->num;$z++)
		{
			$trabajo = $lista_trabajos->Get($z);

			list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);
			$duracion = $h + ($m > 0 ? ($m / 60) :'0');
			$duracion_minutos = $h*60 + $m;

			// Se obtiene la tarifa del profesional que hizo el trabajo
			$profesional[$trabajo->fields['nombre_usuario']]['tarifa'] = Funciones::Tarifa($sesion,$trabajo->fields['id_usuario'],$cobro->fields['id_moneda'],$trabajo->fields['codigo_asunto']);

			// Se calcula el valor del trabajo, según el tiempo trabajado y la tarifa
			$valor_trabajo = $duracion * $profesional[$trabajo->fields['nombre_usuario']]['tarifa'];

			// Se suman los valores del trabajo a las variables del cobro
			$cobro_total_honorario_hh += $valor_trabajo;

			if( $trabajo->fields['tarifa_hh'] == '' )
			{
				if( $profesional[$trabajo->fields['nombre_usuario']]['tarifa'] != '' )
				{
					if( $fix != '1' ) echo ' -- En el trabajo #'.$trabajo->fields['id_trabajo'].' el campo tarifa_hh es NULL -> Se actualizará a: '.$profesional[$trabajo->fields['nombre_usuario']]['tarifa'].'<br>';
					$trabajo->Edit('tarifa_hh', $profesional[$trabajo->fields['nombre_usuario']]['tarifa']);
				}
				else
				{
					if( $fix != '1' ) echo ' -- En el trabajo #'.$trabajo->fields['id_trabajo'].' el campo tarifa_hh es NULL y no se encontro la tarifa de '.$trabajo->fields['nombre_usuario'].'<br>';
				}
			}

			if( $fix == 1 )
			{
				$trabajo->Write();
			}
		}

		// Gastos asociados al cobro
		$query = "SELECT SQL_CALC_FOUND_ROWS cta_corriente.descripcion, cta_corriente.fecha,cta_corriente.id_moneda,cta_corriente.egreso,prm_moneda.tipo_cambio,cta_corriente.id_movimiento
				FROM cta_corriente
				LEFT JOIN asunto USING(codigo_asunto)
				LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
				WHERE cta_corriente.id_cobro='$id_cobro' and egreso > 0
				ORDER BY  cta_corriente.fecha ASC";

		$lista_gastos = new ListaGastos($sesion,'',$query);

		for( $v=0; $v<$lista_gastos->num; $v++ )
		{
			$gasto = $lista_gastos->Get($v);
			$cobro_total_gastos += $gasto->fields['egreso'] * ($gasto->fields['tipo_cambio'] / $cobro->fields['tipo_cambio_moneda_base']);
		}
		if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) && $cobro->fields['porcentaje_impuesto'] )
		{
			$cobro_total_gastos *= (1+$cobro->fields['porcentaje_impuesto']/100);
		}

		if( $cobro->fields['monto_thh'] == $cobro_total_honorario_hh )
		{
			if( $fix != '1' ) echo ' - El campo monto_thh es '.$cobro->fields['monto_thh'].' -> se actualizará a '.$cobro_total_honorario_hh.'<br>';
			$cobro->Edit('monto_thh',number_format($cobro_total_honorario_hh,6,".","") );
		}
		if( $cobro->fields['monto_gastos'] != $cobro_total_gastos )
		{
			if( $fix != '1' ) echo ' - El campo monto_gastos es '.$cobro->fields['monto_gastos'].' -> se actualizará a '.$cobro_total_gastos.'<br>';
			$cobro->Edit('monto_gastos', number_format( $cobro_total_gastos, 6, ".", "") );
		}

		if( $fix == 1 )
		{
			$cobro->Write();
		}
?>

		</td>
	</tr>

<?
	}
?>

</table>

<br><br>
<a href="?fix=0">Buscar problemas</a>
| <a href="?fix=1">Arreglar problemas</a>
<br><br>

<?
	$pagina->PrintBottom();
?>

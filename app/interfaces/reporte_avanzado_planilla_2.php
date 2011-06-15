<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Reporte2.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	
	$agrupadores = explode('-',$vista);

	$pagina->PrintTop(1);
?>


<!-- ESTILOS -->
<style>
a:link
{
	text-decoration: none;
	color: #002255;
}

table
{
  border-collapse:collapse;
  font: Garamond, Verdana;
}

table.planilla
{
  width: 710;
  border-collapse:collapse;
  border-width: 0px;
  font: Garamond, Verdana;
  font-size:14px;
}

td
{
	vertical-align:top;
}

.td_header
{
	background-color: #D7ECF7;
	color: #000000;
	font-size:14px;
	text-align:center;
	border-right: 1px solid #CCCCCC;
}
.td_h1	{		<? if( sizeof($agrupadores) < 6  ) echo "display:none;"   ?>	}
.td_h2	{		<? if( sizeof($agrupadores) < 5  ) echo "display:none;"   ?>	}
.td_h3	{		<? if( sizeof($agrupadores) < 4  ) echo "display:none;"   ?>	}
.td_h4	{		<? if( sizeof($agrupadores) < 3  ) echo "display:none;"   ?>	}
.td_h5	{		<? if( sizeof($agrupadores) < 2  ) echo "display:none;"   ?>	}

td.primer
{
	background-color:#c4c4dd;
	font-size:95%;
	<? if( sizeof($agrupadores)<6 ) echo "display:none;"   ?>
}
td.segundo
{
	background-color:#d2d2ee;
	font-size:90%;
	 <? if( sizeof($agrupadores) <5 ) echo "display:none;"   ?> 
}
td.tercer
{
	font-size:84%;
	background-color:#d9d9f2;
	 <? if( sizeof($agrupadores) <4 ) echo "display:none;"   ?> 
}
td.cuarto
{
	font-size:80%;
	background-color:#e5e5f5;
	 <? if( sizeof($agrupadores) <3 ) echo "display:none;"   ?> 

}
td.quinto
{
	font-size:76%;
	background-color:#f1f1f9;
	 <? if( sizeof($agrupadores) <2 ) echo "display:none;"   ?> 

}
td.sexto
{
	font-size:74%;
	background-color:#f9f9ff;

}

td.campo
{
	text-align:center;
	border-color: #777777;
	border-right-style: hidden;
	border-right-width: 0px;
	border-left-style: solid;
	border-left-width: 1px;
	border-top-style: solid;
	border-top-width: 1px;
	border-bottom-style: solid;
	border-bottom-width: 1px;
	width:150px;
}
td.valor
{
	white-space:nowrap;
	text-align:right;
	color: #00ff00;
	border-color: #777777;
	border-left-style: hidden;
	border-left-width: 0px;
	border-right-style: solid;
	border-right-width: 1px;
	border-top-style: solid;
	border-top-width: 1px;
	border-bottom-style: solid;
	border-bottom-width: 1px;
}
TD.principal { border-right: solid 1px red;  border-bottom: solid 1px red; padding-right: 4px; }
TD.secundario { border-right: solid 1px blue; border-bottom: solid 1px blue;  padding-right: 4px; }


a:link.indefinido { color: #660000; }
span.indefinido { color: #550000; }

</style>

<?
	/*Se crea el reporte según el Input del usuario*/
	$reporte = new Reporte($sesion);
	
	/*USUARIOS*/
	$users = explode(",",$usuarios);
	if(!is_array($users))	
		$users = array($users);
	foreach($users as $usuario)
		if($usuario)
			$reporte->addFiltro('usuario','id_usuario',$usuario);
		
	/*CLIENTES*/
	$clients = explode(",",$clientes);
	if(!is_array($clients))	
		$clients = array($clients);

	foreach($clients as $cliente)
		if($cliente)
			$reporte->addFiltro('cliente','codigo_cliente',$cliente);

	/*AREAS*/
	$areas = explode(",",$areas_asunto);
	if(!is_array($areas))	
		$areas = array($areas);
	foreach($areas as $area)
		if($area)
			$reporte->addFiltro('asunto','id_area_proyecto',$area);
	/*TIPOS*/
	$tipos = explode(",",$tipos_asunto);
	if(!is_array($tipos))	
		$tipos = array($tipos);
	foreach($tipos as $tipo)
		if($tipo)
			$reporte->addFiltro('asunto','id_tipo_asunto',$tipo);

	/*AREAS USUARIO*/
	$areas_usuario = explode(",",$areas_usuario);
	if(!is_array($areas_usuario))	
		$areas_usuario = array($areas_usuario);
	foreach($areas_usuario as $area_usuario)
		if($area_usuario)
			$reporte->addFiltro('usuario','id_area_usuario',$area_usuario);
	
	/*CATEGORIAS USUARIO*/
	$categorias_usuario = explode(",",$categorias_usuario);
	if(!is_array($categorias_usuario))	
		$categorias_usuario = array($categorias_usuario);
	foreach($categorias_usuario as $categoria_usuario)
		if($categoria_usuario)
			$reporte->addFiltro('usuario','id_categoria_usuario',$categoria_usuario);

	$reporte->id_moneda = $id_moneda;	
	$reporte->addRangoFecha($fecha_ini,$fecha_fin);
	
	if($campo_fecha)
		$reporte->setCampoFecha($campo_fecha);

	$reporte->setTipoDato($tipo_dato);
	$reporte->setVista($vista);

	$reporte->Query();
	$r = $reporte->toArray();

	$r_c = $r;

	if($tipo_dato_comparado)
	{
		$reporte = new Reporte($sesion);
		foreach($users as $usuario)
			if($usuario)
				$reporte->addFiltro('trabajo','id_usuario',$usuario);
		foreach($clients as $cliente)
			if($cliente)
				$reporte->addFiltro('cliente','codigo_cliente',$cliente);
		foreach($tipos as $tipo)
			if($tipo)
				$reporte->addFiltro('asunto','id_tipo_asunto',$tipo);
		foreach($areas as $area)
			if($area)
				$reporte->addFiltro('asunto','id_area_proyecto',$area);
		foreach($areas_usuario as $area_usuario)
			if($area_usuario)
				$reporte->addFiltro('usuario','id_area_usuario',$area_usuario);
		foreach($categorias_usuario as $categoria_usuario)
			if($categoria_usuario)
				$reporte->addFiltro('usuario','id_categoria_usuario',$categoria_usuario);


		$reporte->id_moneda = $id_moneda;	
		$reporte->addRangoFecha($fecha_ini,$fecha_fin);
		$reporte->setTipoDato($tipo_dato_comparado);
		$reporte->setVista($vista);

		if($campo_fecha)
			$reporte->setCampoFecha($campo_fecha);

		$reporte->Query();
		$r_c = $reporte->toArray();

		//Se añaden datos faltantes en cada arreglo:
		$r = $reporte->fixArray($r,$r_c);
		$r_c = $reporte->fixArray($r_c,$r);
	}

	if($tipo_dato_comparado)
		$titulo_reporte = __('Resumen - ').' '.__($tipo_dato).' vs. '.__($tipo_dato_comparado).' '.__('en vista por').' '.__($agrupadores[0]);
	else
		$titulo_reporte = __('Resumen - ').' '.__($tipo_dato).' '.__('en vista por').' '.__($agrupadores[0]);

	if(sizeof($r)==2)
	{
		$titulo_reporte = __('No se encontraron datos con el tipo específicado en el período.');
	}
?>
<script>
	function Resize()
	{
			height = $('tabla_planilla').offsetHeight + $('tabla_planilla_2').offsetHeight;
			width = $('tabla_planilla_2').offsetWidth;
			if(width < 694)
				width = 694;
			parent.ResizeIframe(width+4, height+25);
	}
</script>

	<table border=1 cellpadding="3" class="planilla" id ="tabla_planilla" style="width:99%" >
	<tbody>
	<tr>
		<td colspan=5 style='font-size:90%; font-weight:bold' align=center> 
			<?=$titulo_reporte?>
		</td>
		<td colspan=3 >
			<table cellpadding="2" width="100%" >
				<tr>
					<td style='' align=right>
						<?=__('Total').' '.__($tipo_dato)?>:
					</td>
					<td align="right" style=''>
						<?=$r['total']?>
					</td>
					<td style='' align=right>
						<?=(Reporte::requiereMoneda($tipo_dato))? __(Reporte::simboloTipoDato($tipo_dato,$sesion,$id_moneda)):"&nbsp;" ?>
					</td>
				</tr>
				<? if($tipo_dato_comparado)
				{?>
				<tr>
					<td align=right>
						<?=__('Total').' '.__($tipo_dato_comparado)?>:
					</td>
					<td align="right" style='white-space:nowrap;'>
						<?= $r_c['total'] ?>
					</td>
					<td>
						<?=(Reporte::requiereMoneda($tipo_dato_comparado))? __(Reporte::simboloTipoDato($tipo_dato_comparado,$sesion,$id_moneda)):"&nbsp;" ?>
					</td>
				</tr>
				<?}?>
			</table>
		</td>
	</tr>
	</tbody>
</table>
<table border=1 cellpadding="3" class="planilla" id="tabla_planilla_2" >
</tbody>
		<?
		//Imprime un valor en forma de Link. Añade los filtros correpondientes para ver los trabajos.
		function url($valor,$filtros = array())
		{
			global $fecha_ini, $fecha_fin,$clientes,$usuarios;

			$u_clientes = '&lis_clientes='.$clientes;
			if(!$clientes)
				$u_clientes = '';
			$u_usuarios = '&lis_usuarios='.$usuarios;
			if(!$usuarios)
				$u_usuarios = '';

			$u = "<a href='javascript:void(0)' onclick=\"window.parent.location.href= 'horas.php?from=reporte&fecha_ini=".$fecha_ini."&fecha_fin=".$fecha_fin.$u_usuarios.$u_clientes;

			foreach($filtros as $filtro)
				if($filtro['filtro_valor'])
					$u.= "&".$filtro['filtro_campo']."=".urlencode($filtro['filtro_valor']);
				else
					$u.= "&".$filtro['filtro_campo']."=NULL";
			$u .= "'\" ";

			if($valor === '99999!*')
				$u .= " title = \"".__("Valor Indeterminado: el denominador de la fórmula es 0.")."\" class = \"indefinido\"  "; 
			$u.= ">".$valor."</a>";
			return $u;
		}

		function celda_valor($valor,$filtros=array(),$valor_comparado)
		{
			global $sesion;
			global $tipo_dato_comparado;
			global $tipo_dato;
			if($tipo_dato_comparado)
			{
				echo "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\"> ";
				echo url(Reporte::FormatoValor($sesion,$valor['valor'],$tipo_dato),$filtros);
				echo "</td> <tr > <td class=\"valor secundario\"> ";
				echo url(Reporte::FormatoValor($sesion,$valor_comparado['valor'],$tipo_dato_comparado),$filtros);
				echo "</td> </tr> </table>";
			}
			else
				echo url(Reporte::FormatoValor($sesion,$valor['valor'],$tipo_dato),$filtros);
		}

		function celda_campo($orden,$filas,$valor)
		{
			echo "<td class=\"".$orden." campo\" rowspan=".$filas;
			
			if($valor == __('Indefinido'))
				echo "> <span title = \"".__("Agrupador no existe, o no está definido para estos datos.")."\" class=\"indefinido\" ";
			echo " >".$valor;
			if($valor == __('Indefinido'))
				echo " </span>";
			echo	"</td>";
		}

		/* HEADERS son agrupadores y tipos de datos */
		echo	"<tr>";
		for($i=0;$i<6;$i++)
		{
			echo "<td class='td_header td_h".($i+1)."' style='width:80px; border-right: 1px solid #CCCCCC;'>";
			echo __($reporte->agrupador[$i]);
			echo "</td>";
			echo "<td class='td_header td_h".($i+1)."' style='width:50px; border-right: 1px solid #CCCCCC;'>";
			echo __(Reporte::simboloTipoDato($tipo_dato,$sesion,$id_moneda));
			if($tipo_dato_comparado)
				echo __(" vs. ").__(Reporte::simboloTipoDato($tipo_dato_comparado,$sesion,$id_moneda));
			echo "</td>";
		}
		echo	"</tr>";
		
		/*Iteración principal de Tabla. Se recorren las 4 profundidades del arreglo resultado */ 		
		echo "<tr class=\"primera\">";
		foreach($r as $k_a => $a)
		{
			if(is_array($a))
			{
				celda_campo('primer',$a['filas'],$k_a);
				echo "<td class=\"primer valor\" rowspan=".$a['filas']." > ";
					celda_valor($a,array($a),$r_c[$k_a]);
				echo " </td> ";

				foreach($a as $k_b => $b)
				{
					if(is_array($b))
					{
						celda_campo('segundo',$b['filas'],$k_b);
						echo "<td class=\"segundo valor\" rowspan=".$b['filas']." > ";
							echo celda_valor($b,array($a,$b),$r_c[$k_a][$k_b]);
							echo " </td> ";
						foreach($b as $k_c => $c)
						{
							if(is_array($c))
							{
								celda_campo('tercer',$c['filas'],$k_c);
								echo "<td class=\"tercer valor\" rowspan=".$c['filas']." > ";
									echo celda_valor($c,array($a,$b,$c),$r_c[$k_a][$k_b][$k_c]);
								echo " </td>";
								foreach($c as $k_d => $d)
								{
									if(is_array($d))
									{
										celda_campo('cuarto',$d['filas'],$k_d);
										echo "<td class=\"cuarto valor\" rowspan=".$d['filas']." > ";
											echo celda_valor($d,array($a,$b,$c,$d),$r_c[$k_a][$k_b][$k_c][$k_d]);
										echo " </td>";

										foreach($d as $k_e => $e)
										{
											if(is_array($e))
											{
												celda_campo('quinto',$e['filas'],$k_e);
												echo "<td class=\"quinto valor\" rowspan=".$e['filas']." > ";
													echo celda_valor($e,array($a,$b,$c,$d,$e),$r_c[$k_a][$k_b][$k_c][$k_d][$k_e]);
												echo " </td>";
												
												foreach($e as $k_f => $f)
												{
													if(is_array($f))
													{
														celda_campo('sexto',$f['filas'],$k_f);
														echo "<td class=\"sexto valor\" rowspan=".$f['filas']." > ";
															echo celda_valor($f,array($a,$b,$c,$d,$e,$f),$r_c[$k_a][$k_b][$k_c][$k_d][$k_e][$k_f]);
														echo " </td>";
														echo "</tr> <tr class=\"no_primera\"> ";													
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
				echo "</tr>";
			}
		}
?>
	</tbody>
	</table>
<script>
Event.observe(window, "load", function(e)
{
	Resize();
});
</script>
<?
	$pagina->PrintBottom($popup);
?>

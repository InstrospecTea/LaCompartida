<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Reporte.php';
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


<?
	$email_style = array();
	$email_style_valor = array();

	$email_style['primer'] = '';
	$email_style['segundo'] = '';
	$email_style['tercer'] = '';
	$email_style['cuarto'] = '';
	$email_style['quinto'] = '';
	$email_style['sexto'] = '';
	$email_style_valor['primer'] = '';
	$email_style_valor['segundo'] = '';
	$email_style_valor['tercer'] = '';
	$email_style_valor['cuarto'] = '';
	$email_style_valor['quinto'] = '';
	$email_style_valor['sexto'] = '';
	if($email)
	{
		$base = 'style="border:1px solid #CCC;vertical-align:top;';
		$principal = 'style="border:1px solid #E33;vertical-align:top;';
		$secundario = 'style="border:1px solid #33E;vertical-align:top;';

		$email_style['primer'] = $base.'background-color:#c4c4dd;font-size:95%;text-align:center;"';
		$email_style['segundo'] = $base.'background-color:#d2d2ee; font-size:90%;text-align:center;"';
		$email_style['tercer'] = $base.'font-size:84%;background-color:#d9d9f2;text-align:center;"';
		$email_style['cuarto'] = $base.'font-size:80%;background-color:#e5e5f5;text-align:center;"';
		$email_style['quinto'] = $base.'font-size:76%;background-color:#f1f1f9;text-align:center;"';
		$email_style['sexto'] = $base.'font-size:74%;background-color:#f9f9ff;text-align:center;"';

		$email_style_valor['primer']['base'] = $base.'background-color:#c4c4dd;font-size:95%;text-align:right;"';
		$email_style_valor['segundo']['base'] = $base.'background-color:#d2d2ee; font-size:90%;text-align:right;"';
		$email_style_valor['tercer']['base'] = $base.'font-size:84%;background-color:#d9d9f2;text-align:right;"';
		$email_style_valor['cuarto']['base'] = $base.'font-size:80%;background-color:#e5e5f5;text-align:right;"';
		$email_style_valor['quinto']['base'] = $base.'font-size:76%;background-color:#f1f1f9;text-align:right;"';
		$email_style_valor['sexto']['base'] = $base.'font-size:74%;background-color:#f9f9ff;text-align:right;"';

		$email_style_valor['primer']['principal'] = $principal.'background-color:#c4c4dd;font-size:95%;text-align:right;"';
		$email_style_valor['segundo']['principal'] = $principal.'background-color:#d2d2ee; font-size:90%;text-align:right;"';
		$email_style_valor['tercer']['principal'] = $principal.'font-size:84%;background-color:#d9d9f2;text-align:right;"';
		$email_style_valor['cuarto']['principal'] = $principal.'font-size:80%;background-color:#e5e5f5;text-align:right;"';
		$email_style_valor['quinto']['principal'] = $principal.'font-size:76%;background-color:#f1f1f9;text-align:right;"';
		$email_style_valor['sexto']['principal'] = $principal.'font-size:74%;background-color:#f9f9ff;text-align:right;"';

		$email_style_valor['primer']['secundario'] = $secundario.'background-color:#c4c4dd;font-size:95%;text-align:right;"';
		$email_style_valor['segundo']['secundario'] = $secundario.'background-color:#d2d2ee; font-size:90%;text-align:right;"';
		$email_style_valor['tercer']['secundario'] = $secundario.'font-size:84%;background-color:#d9d9f2;text-align:right;"';
		$email_style_valor['cuarto']['secundario'] = $secundario.'font-size:80%;background-color:#e5e5f5;text-align:right;"';
		$email_style_valor['quinto']['secundario'] = $secundario.'font-size:76%;background-color:#f1f1f9;text-align:right;"';
		$email_style_valor['sexto']['secundario'] = $secundario.'font-size:74%;background-color:#f9f9ff;text-align:right;"';
	}
?>

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

@media print
{
		div#print_link {
		display: none;
		}
}

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
	
	/*USUARIOS*/
	$monedascontrato = explode(",",$moneda_contrato);
	if(!is_array($monedascontrato))	
		$monedascontrato = array($monedascontrato);
	foreach($monedascontrato as $monedacontrato)
		if($monedacontrato)
			$reporte->addFiltro('contrato','id_moneda',$monedacontrato);
		
	/*ENCARGADOS*/
	$encargados = explode(",",$en_com);
	if(!is_array($encargados))	
		$encargados = array($encargados);
	foreach($encargados as $encargado)
		if($encargado)
			$reporte->addFiltro('contrato','id_usuario_responsable',$encargado);
	

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

	//genero el formato valor a ser usado en las celdas (
	$moneda	= new Moneda($sesion);
	$moneda->Load($id_moneda);
	
	$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
	$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
	$formato_valor=array('cifras_decimales'=>$moneda->fields['cifras_decimales'],
	'miles'=>$idioma->fields['separador_miles'],
	'decimales'=>$idioma->fields['separador_decimales']);

	$reporte->addRangoFecha($fecha_ini,$fecha_fin);
	
	if($campo_fecha)
		$reporte->setCampoFecha($campo_fecha);

	
	$reporte->setVista($vista);
	$reporte->setProporcionalidad($prop);

	/*ESTADOS*/
	$estados = explode(",",$es_cob);
	if(!is_array($estados))	
		$estados = array($estados);
	
	$reporte_c=$reporte;
	
	
	
	
	
	$reporte->setTipoDato($tipo_dato);
	foreach($estados as $estado)
		if($estado)
			$reporte->addFiltro('cobro','estado',$estado);
		
	
	$reporte->Query();
	$r = $reporte->toArray();

	$r_c = $r;

	if($tipo_dato_comparado)
	{
		$reporte_c->setTipoDato($tipo_dato_comparado);
	foreach($estados as $estado)
		if($estado)
			$reporte_c->addFiltro('cobro','estado',$estado);
		
		$reporte_c->Query();
		$r_c = $reporte_c->toArray();
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
   

	<div id='print_link' align=right>
		<a href='javascript:void(0)' onclick='window.print()'>
			<?=__('Imprimir')?>
		</a>
	</div>

	<table cellpadding="3" class="planilla" id ="tabla_planilla" style="width:99%" >
	<tbody>
	<tr>
		<td colspan=5 style='font-size:90%; font-weight:bold; <?= $email? 'border:0px;':'' ?>' align=center> 
			<?=$titulo_reporte?>
		</td>
		<td colspan=3 >
			<table cellpadding="2" width="100%" >
				<tr>
					<td style='' align=right>
						<?=__('Total').' '.__($tipo_dato)?>:
					</td>
					<td align="right" style=''>
						
					    <?php echo    Reporte::FormatoValor($sesion,$r['total'],$tipo_dato,'',$formato_valor); ?>
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
						<?php echo    Reporte::FormatoValor($sesion,$r_c['total'],$tipo_dato_comparado,'',$formato_valor); ?>
					</td>
					<td style='' align=right>
						<?=(Reporte::requiereMoneda($tipo_dato_comparado))? __(Reporte::simboloTipoDato($tipo_dato_comparado,$sesion,$id_moneda)):"&nbsp;" ?>
					</td>
				</tr>
				<?}?>
			</table>
		</td>
	</tr>
	</tbody>
</table>
<table border=1 cellpadding="3" class="planilla" id="tabla_planilla_2">
<tbody>
		<?
		//Imprime un valor en forma de Link. Añade los filtros correpondientes para ver los trabajos.
		if(!function_exists('url')){
		function url($valor,$filtros = array(), $email)
		{
			global $fecha_ini, $fecha_fin,$clientes,$usuarios;
			
			if($email)
				return $valor;

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
		}}

		if(!function_exists('celda_valor')){
		function celda_valor(&$s,$orden,$valor,$filtros=array(),$valor_comparado,$comparado,$email, $email_style)
		{
			global $sesion;
			//global $tipo_dato_comparado; //No puede ser global, conflicto con CRON
			global $tipo_dato;
			global $formato_valor;
			
			if($comparado)
			{
				$s .= "<table style=\"width:100%;\" > <tr> <td class=\"valor principal\" ";
				if($email)
					$s .= ' '.$email_style[$orden]['principal'].' ';
				$s .= " > ";
				$s .= url(Reporte::FormatoValor($sesion,$valor['valor'],$tipo_dato,'',$formato_valor),$filtros,$email);
				$s .= "</td> <tr > <td class=\"valor secundario\" ";
				if($email)
					$s .= ' '.$email_style[$orden]['secundario'].' ';
				$s .= " > ";
				$s .= url(Reporte::FormatoValor($sesion,$valor_comparado['valor'],$comparado,'',$formato_valor),$filtros,$email);
				$s .= "</td> </tr> </table>";
			}
			else
				$s .= url(Reporte::FormatoValor($sesion,$valor['valor'],$tipo_dato,'',$formato_valor),$filtros,$email);
		}}

		if(!function_exists('celda_campo')){
		function celda_campo(&$s,$orden,$filas,$valor, $email, $email_style)
		{

			$s .= "<td class=\"".$orden." campo\" rowspan=".$filas;
			
			if($email)
				$s .= ' '.$email_style[$orden].' ';

			if($valor == __('Indefinido'))
				$s .= "> <span title = \"".__("Agrupador no existe, o no está definido para estos datos.")."\" class=\"indefinido\" ";
			$s .= " >".$valor;
			if($valor == __('Indefinido'))
				$s .= " </span>";
			$s .=	"</td>";
		}}

		/* HEADERS son agrupadores y tipos de datos */
		$t =	"<tr>";
		for($i=0;$i<6;$i++)
		{
			$t .= "<td class='td_header td_h".($i+1)."' style='width:80px; ";
			if($email)
				$t .= " background-color: #D7ECF7;
						color: #000000;
						font-size:14px;
						text-align:center;
						border-right: 1px solid #CCCCCC; ";
			$t .= "' >";
			$t .= __($reporte->agrupador[$i]);
			$t .= "</td>";
			$t .= "<td class='td_header td_h".($i+1)."' style='width:50px; ";
			if($email)
				$t .= " background-color: #D7ECF7;
						color: #000000;
						font-size:14px;
						text-align:center;
						border-right: 1px solid #CCCCCC;' ";
			$t .= "' >";
			$t .= __(Reporte::simboloTipoDato($tipo_dato,$sesion,$id_moneda));
			if($tipo_dato_comparado)
				$t .= __(" vs. ").__(Reporte::simboloTipoDato($tipo_dato_comparado,$sesion,$id_moneda));
			$t .= "</td>";
		}
		$t .=	"</tr>";
		
		/*Iteración principal de Tabla. Se recorren las 4 profundidades del arreglo resultado */ 		
		$t .= "<tr class=\"primera\">";
		foreach($r as $k_a => $a)
		{
			if(is_array($a))
			{
				celda_campo($t,'primer',$a['filas'],$k_a,$email,$email_style);
				$t .= "<td class=\"primer valor\" rowspan=".$a['filas']." ".$email_style_valor['primer']['base']." > ";
					celda_valor($t,'primer',$a,array($a),$r_c[$k_a],$tipo_dato_comparado,$email,$email_style_valor);
				$t .= " </td> ";

				foreach($a as $k_b => $b)
				{
					if(is_array($b))
					{
						celda_campo($t,'segundo',$b['filas'],$k_b,$email,$email_style);
						$t .= "<td class=\"segundo valor\" rowspan=".$b['filas']." ".$email_style_valor['segundo']['base']." > ";
							celda_valor($t,'segundo',$b,array($a,$b),$r_c[$k_a][$k_b],$tipo_dato_comparado,$email,$email_style_valor);
							$t .= " </td> ";
						foreach($b as $k_c => $c)
						{
							if(is_array($c))
							{
								celda_campo($t,'tercer',$c['filas'],$k_c,$email,$email_style);
								$t .= "<td class=\"tercer valor\" rowspan=".$c['filas']." ".$email_style_valor['tercer']['base']." > ";
									$t .= celda_valor($t,'tercer',$c,array($a,$b,$c),$r_c[$k_a][$k_b][$k_c],$tipo_dato_comparado,$email,$email_style_valor);
								$t .= " </td>";
								foreach($c as $k_d => $d)
								{
									if(is_array($d))
									{
										celda_campo($t,'cuarto',$d['filas'],$k_d,$email,$email_style);
										$t .= "<td class=\"cuarto valor\" rowspan=".$d['filas']." ".$email_style_valor['cuarto']['base']." > ";
											celda_valor($t,'cuarto',$d,array($a,$b,$c,$d),$r_c[$k_a][$k_b][$k_c][$k_d],$tipo_dato_comparado,$email,$email_style_valor);
										$t .= " </td>";

										foreach($d as $k_e => $e)
										{
											if(is_array($e))
											{
												celda_campo($t,'quinto',$e['filas'],$k_e,$email,$email_style);
												$t .= "<td class=\"quinto valor\" rowspan=".$e['filas']." ".$email_style_valor['quinto']['base']." > ";
													celda_valor($t,'quinto',$e,array($a,$b,$c,$d,$e),$r_c[$k_a][$k_b][$k_c][$k_d][$k_e],$tipo_dato_comparado,$email,$email_style_valor);
												$t .= " </td>";
												
												foreach($e as $k_f => $f)
												{
													if(is_array($f))
													{
														celda_campo($t,'sexto',$f['filas'],$k_f,$email,$email_style);
														$t .= "<td class=\"sexto valor\" rowspan=".$f['filas']." ".$email_style_valor['sexto']['base']." > ";
															celda_valor($t,'sexto',$f,array($a,$b,$c,$d,$e,$f),$r_c[$k_a][$k_b][$k_c][$k_d][$k_e][$k_f],$tipo_dato_comparado,$email,$email_style_valor);
														$t .= " </td>";
														$t .= "</tr> <tr class=\"no_primera\"> ";	
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
				
			}
		}
		$t .= "</tr>";
		echo $t;
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

<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
        require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';

	$sesion = new Sesion(array('REP'));
	//Revisa el Conf si esta permitido
	if( method_exists('Conf','GetConf') )
	{
		if( !Conf::GetConf($sesion,'ReportesAvanzados') )
		{
			header("location: reportes_especificos.php");
		}
	}
	else if( method_exists('Conf','ReportesAvanzados') )
	{
		if( !Conf::ReportesAvanzados() )
		{
			header("location: reportes_especificos.php");
		}
	}
	else
		header("location: reportes_especificos.php");
	$pagina = new Pagina($sesion);
	
	
	$mis_reportes = array();
	
	
	if($opc == 'eliminar_reporte')
		if(!in_array($nuevo_reporte,$mis_reportes))
		{
			$query = "DELETE FROM usuario_reporte  WHERE id_usuario = '".$sesion->usuario->fields['id_usuario']."' AND reporte =  '".mysql_real_escape_string($eliminado_reporte)."';";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		}
	
	$query_mis_reportes = "SELECT reporte FROM usuario_reporte WHERE id_usuario = '".$sesion->usuario->fields['id_usuario']."'";
	$resp_mis_reportes = mysql_query($query_mis_reportes,$sesion->dbh) or Utiles::errorSQL($query_mis_reportes,__FILE__,__LINE__,$sesion->dbh);
	while( list($reporte_encontrado) = mysql_fetch_array($resp_mis_reportes) )
			$mis_reportes[] = $reporte_encontrado;
	
	if($opc == 'nuevo_reporte')
		if(!in_array($nuevo_reporte,$mis_reportes))
		{
			$query = "INSERT INTO usuario_reporte (id_usuario,reporte) VALUES ('".$sesion->usuario->fields['id_usuario']."' , '".mysql_real_escape_string($nuevo_reporte)."' );";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$mis_reportes[] = mysql_real_escape_string($nuevo_reporte);
		}
	
	

	/*REPORTE AVANZADO. ESTA PANTALLA SOLO TIENE INPUTS DEL USUARIO. SUBMIT LLAMA AL TIPO DE REPORTE SELECCIONADO*/
	$pagina->titulo = __('Resumen actividades profesionales');

	$tipos_de_dato = array();
	$tipos_de_dato[] ='horas_trabajadas';
	$tipos_de_dato[] ='horas_cobrables';
	$tipos_de_dato[] ='horas_no_cobrables';
	$tipos_de_dato[] ='horas_castigadas';
	$tipos_de_dato[] ='horas_visibles';
	$tipos_de_dato[] ='horas_cobradas';
	$tipos_de_dato[] ='horas_por_cobrar';
	$tipos_de_dato[] ='horas_pagadas';
	$tipos_de_dato[] ='horas_por_pagar';
	$tipos_de_dato[] ='horas_incobrables';
	$tipos_de_dato[] ='valor_cobrado';
	$tipos_de_dato[] ='valor_por_cobrar';
	$tipos_de_dato[] ='valor_pagado';
	$tipos_de_dato[] ='valor_por_pagar';
	$tipos_de_dato[] ='valor_incobrable';
	$tipos_de_dato[] ='rentabilidad';
	$tipos_de_dato[] ='valor_hora';
	$tipos_de_dato[] ='diferencia_valor_estandar';
	$tipos_de_dato[] ='valor_estandar';

	$tipos_de_dato[] ='valor_trabajado_estandar';
	$tipos_de_dato[] ='rentabilidad_base';
	if($debug == 1)
	{
		$tipos_de_dato[] ='valor_pagado_parcial';
		$tipos_de_dato[] ='valor_por_pagar_parcial';
	
	}


	$agrupadores = array(
	'glosa_cliente',
	'codigo_asunto',
	'glosa_asunto',
	'profesional',
	'estado',
	'id_cobro',
	'forma_cobro',
	'tipo_asunto',
	'prm_area_proyecto.glosa',
	'categoria_usuario',
	'area_usuario',
        'fecha_emision',
	'glosa_grupo_cliente',
	'id_usuario_responsable',
	'id_usuario_secundario',
	'mes_reporte',
	'dia_reporte',
	'mes_emision'
	);
	if($debug==1)
	{
		$agrupadores[] = 'id_trabajo';
		$agrupadores[] = 'dia_corte';
		$agrupadores[] = 'dia_emision';
		$agrupadores[] = 'id_contrato';
		$agrupadores[] = 'grupo_o_cliente';
	}

	$glosa_dato['horas_trabajadas'] = "Total de Horas Trabajadas";
	$glosa_dato['horas_cobrables'] = __("Total de Horas Trabajadas en asuntos Cobrables");
	$glosa_dato['horas_no_cobrables'] = __("Total de Horas Trabajadas en asuntos no Cobrables");
	$glosa_dato['horas_castigadas'] = __("Diferencia de Horas Cobrables con las Horas que ve el cliente en nota de Cobro");
	$glosa_dato['horas_visibles'] = __("Horas que ve el Cliente en nota de cobro (tras revisión)");
	$glosa_dato['horas_cobradas'] = __("Horas Visibles en Cobros que ya fueron Emitidos");
	$glosa_dato['horas_por_cobrar'] = "Horas Visibles que aún no se Emiten al Cliente";
	$glosa_dato['horas_pagadas'] = __("Horas Cobradas en Cobros con estado Pagado");
	$glosa_dato['horas_por_pagar'] = __("Horas Cobradas que aún no han sido pagadas");
	$glosa_dato['horas_incobrables'] = __("Horas en Cobros Incobrables");
	$glosa_dato['valor_por_cobrar'] = __("Valor monetario estimado que corresponde a cada Profesional en horas por cobrar");
	$glosa_dato['valor_cobrado'] = __("Valor monetario que corresponde a cada Profesional, en un Cobro ya Emitido");
	$glosa_dato['valor_incobrable'] = __("Valor monetario que corresponde a cada Profesional, en un Cobro Incobrable");
	$glosa_dato['valor_pagado'] = __("Valor Cobrado que ha sido Pagado");
	$glosa_dato['valor_por_pagar'] = __("Valor Cobrado que aún no ha sido pagado");
	$glosa_dato['rentabilidad'] = __("Valor Cobrado / Valor Estándar");
	$glosa_dato['valor_hora'] = __("Valor Cobrado / Horas Cobradas");
	$glosa_dato['diferencia_valor_estandar'] = __("Valor Cobrado - Valor Estándar");	
	$glosa_dato['valor_estandar'] = __("Valor Cobrado, si se hubiera usado THH Estándar");
	$glosa_dato['valor_trabajado_estandar'] = __("Horas Trabajadas por THH Estándar, para todo Trabajo");
	$glosa_dato['rentabilidad_base'] = __("Valor Cobrado / Valor Trabajado Estándar");

	$glosa_boton['planilla'] = "Despliega una Planilla con deglose por cada Agrupador elegido.";
	$glosa_boton['excel'] = "Genera la Planilla como un Documento Excel.";
	$glosa_boton['tabla'] = "Genera un Documento Excel con una tabla cruzada.";
	$glosa_boton['barra'] = "Despliega un Gráfico de Barras, usando el primer Agrupador.";
	$glosa_boton['torta'] = "Despliega un Gráfico de Torta, usando el primer Agrupador.";
	$glosa_boton['dispersion'] = "Despliega un Gráfico de Dispersión, usando el primer Agrupador.";

	$tipos_moneda = Reporte::tiposMoneda();

	$hoy = date("Y-m-d");
	if(!$fecha_anio)
		$fecha_anio = date('Y');
	if(!$fecha_mes)
		$fecha_mes = date('m');

	$fecha_ultimo_dia = date('t',mktime(0,0,0,$fecha_mes,5,$fecha_anio));

	if(!isset($numero_agrupadores))
		$numero_agrupadores = 1;
	if(!$popup)
	{
		$pagina->PrintTop($popup);
		if(!$filtros_check)
		{
			$fecha_m = ''.$fecha_mes;

			$fecha_fin = $fecha_ultimo_dia."-".$fecha_m."-".$fecha_anio;
			$fecha_ini = "01-".$fecha_m."-".$fecha_anio;
		}
?>
<style>

TD.boton_normal { border: solid 2px #e0ffe0; background-color: #e0ffe0; }

TD.boton_presionado { border: solid 2px red; background-color: #e0ffe0; }

TD.boton_comparar { border: solid 2px blue; background-color: #e0ffe0; }

TD.boton_disabled { border: solid 2px #e5e5e5; background-color: #e5e5e5; color:#444444;}

TD.borde_rojo { border: solid 1px red; }

TD.borde_azul { border: solid 1px blue; }

TD.borde_blanco { border: solid 1px white; }

input.btn{ margin:3px;}


.visible{display:'block';}
.invisible{display: none;}

</style>

<script type="text/javascript">

function Agrupadores(num)
{
	var numero_agrupadores = parseInt($('numero_agrupadores').value);
	numero_agrupadores += num;
	if(numero_agrupadores < 1)
		numero_agrupadores = 1;
	if(numero_agrupadores > 6)
		numero_agrupadores = 6;
	$('numero_agrupadores').value = numero_agrupadores;
	for(var i =0; i < 6; i++)
	{
		var selector = $('span_agrupador_'+i);
		if(i<numero_agrupadores)
			selector.show();
		else
			selector.hide();
	}
	//if(numero_agrupadores == 1)
	//	$('menos_agrupadores').hide();
	//else
	//	$('menos_agrupadores').show();

	//if(numero_agrupadores == 6)
	//$('mas_agrupadores').hide();
	//else
	//	$('mas_agrupadores').show();
	RevisarTabla();
}

//Al cambiar un agrupador, en los agrupadores siguientes, el valor previo se hace disponible y el valor nuevo se indispone.
function CambiarAgrupador(num)
{
	var selector = $('agrupador_'+num);
	var selector_previo = $('agrupador_valor_previo_'+num);

	//Los selectores siguientes
	for(var i = num + 1; i < 6; i++)
	{
		var selector_siguiente = $('agrupador_'+i);
		//se indispone lo nuevo
		for(var j = 0; j < selector_siguiente.length; j++)
		{
			if(selector_siguiente.options[j].text == selector.options[selector.selectedIndex].text)
			{
				selector_siguiente.options[j]=null;
				CambiarAgrupador(i);	
			}
		}
		//y se dispone lo viejo, SOLO si no resultó elegido en uno anterior
		var valor = selector_previo.value;
		var txt = selector_previo.options[selector_previo.selectedIndex].text;
		var elegido_en_anterior = false;
		for(var k = i; k >= 0; k--)
		{
			var anterior = $('agrupador_'+k);
			if(anterior.options[anterior.selectedIndex].text == txt)
				elegido_en_anterior = true;
		}
		if(!elegido_en_anterior)
		{
			opc = new Option(txt,valor);
			selector_siguiente.options[selector_siguiente.options.length] = opc;
		}
	}
	//ahora selector_previo debe guardar el dato nuevo, para el proximo cambio
	selector_previo.options[0] = null;
	valor = selector.value;
	txt = selector.options[selector.selectedIndex].text;
	opc = new Option(txt,valor);
	selector_previo.options[0] = opc;	
}

function ResizeIframe(width, height)
{
	currentfr = document.getElementById('planilla'); 
	currentfr.height = height+'px'; // currentfr.Document.body.scrollHeight;
	currentfr.width = width+'px'; // currentfr.Document.body.scrollHeight;

}

function Generar(form, valor)
{
	form.opc.value = valor;

	form.vista.value = $('agrupador_0').value;
	for(i=1;i<$('numero_agrupadores').value;i++)
	{
		form.vista.value += '-'+$('agrupador_'+i).value;
	}
	if(valor == 'pdf')
	{
		form.action = 'html_to_pdf.php?frequire=reporte_avanzado.php&popup=1';
	}
	else if(valor == 'excel')
	{
			form.action = 'planillas/planilla_reporte_avanzado.php';
	}
	else if(valor == 'tabla')
	{
		form.action = 'planillas/planilla_reporte_avanzado_tabla.php';
	}
	else
		form.action = 'reporte_avanzado.php';
	form.submit();
}

function Rangos(obj, form)
{
	var td_show = $('periodo_rango');
	var td_hide = $('periodo');

	if(obj.checked)
	{
		td_hide.style['display'] = 'none';
		td_show.style['display'] = '';
	}
	else
	{
		td_hide.style['display'] = '';
		td_show.style['display'] = 'none';
	}
}

/*Al activar la Comparación, debo hacer cosas Visibles y cambiar valores*/
function Comparar()
{
	var comparar = $('comparar');
	var tipo_de_dato = document.getElementById('tipo_dato');
	var tipo_de_dato_comparado = document.getElementById('tipo_dato_comparado');
	var tinta = document.getElementById('tipo_tinta');
	var vs = document.getElementById('vs');
	var dispersion = document.getElementById('dispersion');
	var td_dato_comparado = document.getElementById('td_dato_comparado');
	var td_dato = document.getElementById('td_dato');


	//Si el valor comparado es igual al principal, debo cambiarlo:
	if(tipo_de_dato_comparado.selectedIndex == tipo_de_dato.selectedIndex)
	{
		if(tipo_de_dato_comparado.selectedIndex == 0)
			tipo_de_dato_comparado.selectedIndex = 1;
		else
			tipo_de_dato_comparado.selectedIndex = 0;
	}

	if(comparar.checked)
	{
		tinta.style['display'] = '';
		vs.style['display'] = 'inline';
		dispersion.style['display'] = 'inline';
		td_dato_comparado.style['display'] = '';
		td_dato.className = 'borde_rojo';

		elegido = document.getElementById(tipo_de_dato_comparado.value);
		elegido.className = 'boton_comparar';
	}
	else
	{
		tinta.style['display'] = 'none';
		vs.style['display'] = 'none';
		td_dato_comparado.style['display'] = 'none';
		dispersion.style['display'] = 'none';
		td_dato.className = 'borde_blanco';

		elegido = document.getElementById(tipo_de_dato_comparado.value);
		elegido.className = 'boton_normal';
	}

	RevisarMoneda();
	RevisarCircular();
	RevisarTabla();
}

//Sincroniza los selectores de Campo de Fecha Visibles e Invisibles
function SincronizarCampoFecha()
{
	var filtros_check = $('filtros_check');
	var campo_fecha = document.formulario.campo_fecha;
	var campo_fecha_F = document.formulario.campo_fecha_F;

	if(filtros_check.checked)
	{
		for(var i = 0; i < 3; i++)
		{
			if(campo_fecha_F[i].checked)
			{
				campo_fecha[i].checked = true;
			}
		}
	}
	else
	{
		for(var i = 0; i < 3; i++)
		{
			if(campo_fecha[i].checked)
			{
				campo_fecha_F[i].checked = true;
			}
		}
	}
}

//Muestra Categoría y Area
function Categorias(obj, form)
{
	var td_show = $('area_categoria');
	if(obj.checked)
		td_show.style['display'] = 'inline';
	else
		td_show.style['display'] = 'none';
}

//Revisa visibilidad de la Moneda
function RevisarMoneda()
{
	var tipo_de_dato = document.getElementById('tipo_dato');
	var tipo_de_dato_comparado = document.getElementById('tipo_dato_comparado');
	var comparar = $('comparar');

	if(
		tipo_de_dato.value in
			{'valor_pagado':'','valor_cobrado':'','valor_por_cobrar':'','valor_por_pagar':'','valor_incobrable':'','valor_hora':'','diferencia_valor_estandar':'','valor_trabajado_estandar':''}
		||
			(comparar.checked && tipo_de_dato_comparado.value in
				{'valor_pagado':'','valor_cobrado':'','valor_por_cobrar':'','valor_por_pagar':'','valor_incobrable':'','valor_hora':'','diferencia_valor_estandar':'','valor_trabajado_estandar':''}
			)
		)
		Monedas(true);
	else
		Monedas(false);
}

//Revisa la visibilidad del botón de gráfico circular.
function RevisarCircular()
{
	var tipo_de_dato = document.getElementById('tipo_dato');
	var tipo_de_dato_comparado = document.getElementById('tipo_dato_comparado');
	var comparar = $('comparar');
	var circular = $('circular');

	if(!comparar.checked)
	{
		if(
			tipo_de_dato.value in
				{'rentabilidad':'','valor_hora':'','diferencia_valor_estandar':'','horas_castigadas':'','rentabilidad_base':''}
		)
			circular.style['display'] = 'none';
		else
			circular.style['display'] = '';
	}
	else
		circular.style['display'] = 'none';
}

function RevisarTabla()
{
	var comparar = $('comparar');
	var vista = $('vista');
	var tabla = $('tabla');

	if(!comparar.checked && $('numero_agrupadores').value==2)
	{
		tabla.style['display'] = '';
	}
	else
		tabla.style['display'] = 'none';
}

function SelectValueSet(SelectName, Value)
{
  SelectObject = $(SelectName);
  for(index = 0; 
    index < SelectObject.length; 
    index++) {
   if(SelectObject[index].value == Value)
     SelectObject.selectedIndex = index;
   }
}

function CargarReporte(reporte)
{
	if(reporte == "0")
	{
		$('span_eliminar_reporte').style.visibility = 'hidden';
		return 0;
	}
	else if(reporte == 'nuevo_reporte')
	{
		$('span_eliminar_reporte').style.visibility = 'hidden';
		GenerarReporte();
	}
	else
		$('span_eliminar_reporte').style.visibility = 'visible';
	
	var elementos = reporte.split('.');
	var datos = elementos[0].split(',');
	var agrupadores = elementos[1].split(',');
	
	SelectValueSet('tipo_dato',datos[0]);
	if(datos.size() == 2)
	{
		SelectValueSet('tipo_dato_comparado',datos[1]);
		$('comparar').checked = true;
	}
	else
		$('comparar').checked = false;		
	Comparar();
	
	Agrupadores( agrupadores.size() - parseInt($('numero_agrupadores').value));
	
	for(i = 0; i < agrupadores.size(); i++)
	{
		SelectValueSet('agrupador_'+i,agrupadores[i]);
		CambiarAgrupador(i);
	}
	
	$('eliminado_reporte').value = reporte;
}

function GenerarReporte()
{
		var s = $('tipo_dato').value;
		if($('comparar').checked == true)
			s += ','+$('tipo_dato_comparado').value;
		s += '.';
		var numero_agrupadores = parseInt($('numero_agrupadores').value);
		for(i = 0; i < numero_agrupadores; i++)
		{
				if(i != 0)
				 s += ',';
				s += $('agrupador_'+i).value;
		}
		$('nuevo_reporte').value = s;
		$('formulario').opc.value = 'nuevo_reporte';
		$('formulario').submit();	
}

function EliminarReporte()
{
		$('formulario').opc.value = 'eliminar_reporte';
		$('formulario').submit();	
}


//Hace visible o invisible el input de Moneda.
function Monedas(visible)
{
	var div_moneda = $('moneda');
	var div_anti_moneda = $('anti_moneda');
	var div_moneda_select = $('moneda_select');
	var div_anti_moneda_select = $('anti_moneda_select');

	if(visible)
	{
		div_moneda.style['display'] = 'inline';
		div_moneda_select.style['display'] = 'inline';
		div_anti_moneda.style['display'] = 'none';
		div_anti_moneda_select.style['display'] = 'none';
	}
	else
	{
		div_moneda.style['display'] = 'none';
		div_moneda_select.style['display'] = 'none';
		div_anti_moneda.style['display'] = 'inline';
		div_anti_moneda_select.style['display'] = 'inline';
	}
}

//Hace visible o invisible por ID [Para Inputs con + y -]
function MostrarOculto(ID)
{
	var table_full = $("full_"+ID);
	var table_mini = $("mini_"+ID);
	var check = $(ID+"_check");
	var img = $(ID+"_img");

	if(table_full.style['display']!="none")
	{
		table_full.style['display'] = "none";
		table_mini.style['display'] = "";
		check.checked = false;
		img.innerHTML = "<img src='../templates/default/img/mas.gif' border='0' title='Desplegar'>";
	}
	else
	{
		table_full.style['display'] = "";
		table_mini.style['display'] = "none";
		check.checked = true;
		img.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
	}
}

function MostrarLimite(visible)
{
	var limite_check = $('limite_check');
	var limite_checkbox = $("limite_checkbox");
	if(visible)
	{
		limite_check.style['display'] = 'inline';
	}
	else
	{
		limite_checkbox.checked = false;
		limite_check.style['display'] = 'none';
	}
}

/*Setea el Tipo de Dato, marcando la selección, haciendo visible la moneda y el gráfico circular*/
function TipoDato(valor)
{
	var td_col = document.getElementById(valor);
	var tipo_de_dato = document.getElementById('tipo_dato');
	var tipo_de_dato_comparado = document.getElementById('tipo_dato_comparado');
	var comparar = document.getElementById('comparar');
	var tintas = document.getElementsByName('tinta');

	for(var i=0; i< tintas.length; i++)
	{
		if(tintas[i].checked)
			var tinta = tintas[i].value;
	}

	if(!comparar.checked || (comparar.checked && tinta=='rojo' && valor!= tipo_de_dato_comparado.value))
	{
		td_col.className = 'boton_presionado';

		<?
		foreach($tipos_de_dato as $key => $t_d)
		{
			echo " if(valor == '".$t_d."' ){ ";
			echo " tipo_de_dato.selectedIndex = ".$key."; \n";
			echo "} else {td_col= document.getElementById('".$t_d."'); if(td_col.className=='boton_presionado')td_col.className = 'boton_normal'; }\n";
		}
		?>
	}
	else if(valor != tipo_de_dato.value)
	{
			td_col.className = 'boton_comparar';
			<?
			foreach($tipos_de_dato as $key => $t_d)
			{
				echo " if(valor == '".$t_d."' ){ ";
				echo " tipo_de_dato_comparado.selectedIndex = ".$key."; \n";
				echo "} else {td_col= document.getElementById('".$t_d."'); if(td_col.className=='boton_comparar') td_col.className = 'boton_normal'; }\n";
			}
			?>
	}
	RevisarMoneda();
	RevisarCircular();
}

</script>
<?
}
?>
<form method=post name=formulario action="" id=formulario autocomplete='off'>
<input type=hidden name=opc id=opc value='print'>
<input type=hidden name=debug id=debug value='<?=$debug?>'>
<?
if(!$popup)
{
?>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<!-- SELECTOR DE FILTROS -->
<table width="90%"><tr><td align="center">

<fieldset width="100%" class="border_plomo tb_base" align="center"><legend><?=__('Mis Reportes')?></legend>
<div>
	<select name="mis_reportes_elegido" id='mis_reportes' onchange="CargarReporte($('mis_reportes').value);">
		<option value="0"><?=__('Seleccione Reporte...')?></option>
		<?
		   $estilo_eliminar_reporte = 'style="visibility:hidden"';
		   if(empty($mis_reportes))
		   {
			 echo '<option value="0">-- '.__('No se han agregado reportes').'. --</option>';
		   }
		   else
		   {
			   $j = 1;
			   foreach($mis_reportes as $mi_reporte)
			   {
				   $elementos = explode('.',$mi_reporte);
				   $mis_datos = explode(',',$elementos[0]);
				   $mis_agrupadores = explode(',',$elementos[1]);
				   
					foreach($mis_datos as $i => $mi_dato)
						$mis_datos[$i] = __($mi_dato);
					foreach($mis_agrupadores as $i => $mi_agrupador)
						$mis_agrupadores[$i] = __($mi_agrupador);
					
					$selected_mi_reporte = '';
					if($mi_reporte == $nuevo_reporte || $mis_reportes_elegido == $mi_reporte)
					{
						$selected_mi_reporte = 'selected';
						$estilo_eliminar_reporte = '';
					}
					
					$num = $j<10? '0'.$j:$j;
				    echo '<option '.$selected_mi_reporte.' value="'.$mi_reporte.'">'.$num.' )&nbsp;&nbsp;'.implode(' vs. ',$mis_datos).' : '.implode(' - ',$mis_agrupadores)."</option>";
				    $j++;
				}
		   }
		?>
		<option value = "nuevo_reporte">+++ <?=__('Agregar selección actual')?>. +++</option>
	</select>
	<span id="span_eliminar_reporte" <?=$estilo_eliminar_reporte?> >&nbsp;<a style='color:#CC1111' href="javascript:void(0)" onclick="EliminarReporte();"><?=__('Eliminar')?></a></span>
	<input type=hidden name='nuevo_reporte' id='nuevo_reporte' />
	<input type=hidden name='eliminado_reporte' id='eliminado_reporte' />
</div>
</fieldset>

<fieldset width="100%" class="border_plomo tb_base" align="center">
<legend onClick="MostrarOculto('filtros')" style="cursor:pointer">
<span id="filtros_img"><img src= "<?=Conf::ImgDir()?><?=$filtros_check? '/menos.gif':'/mas.gif'?>" border="0" ></span>
<?=__('Filtros')?>
</legend>
<input type="checkbox" name="filtros_check" id="filtros_check" value="1" <?=$filtros_check? 'checked':''?> style="display:none;" />
<center>
<table id="mini_filtros" style="border: 1px solid white; width:90%; <?=$filtros_check? 'display:none':''?> " cellpadding="0" cellspacing="3" >
	<tr valign=top>
		<td align=left>
			<b><?=__('Profesional')?>:</b>
		</td>
		<td align=left>
			<b><?=__('Cliente')?>:</b>
		</td>
		<td align=left width='80px'>
			<b><?=__('Periodo de') ?>:</b>

			<br>
		</td>
		<td>
			<?
				$explica_periodo_trabajo = 'Incluye todo Trabajo con fecha en el Periodo';
				$explica_periodo_cobro = 'Sólo considera Trabajos en Cobros con fecha de corte en el Periodo';
				$explica_periodo_emision = 'Sólo considera Trabajos en Cobros con fecha de emisión en el Periodo';
			?>
			<span title="<?=__($explica_periodo_trabajo)?>">
			<input type="radio" name="campo_fecha" id="campo_fecha_trabajo" value="trabajo"
																					 <? if($campo_fecha=='trabajo' || $campo_fecha=='') echo 'checked="checked"'; ?>
																					 onclick ="SincronizarCampoFecha()" />&nbsp;<label for="campo_fecha_trabajo"><?=__("Trabajo")?></label>
			</span>
			<span title="<?=__($explica_periodo_cobro)?>"><input type="radio" name="campo_fecha" id="campo_fecha_cobro" value="cobro"
																					<? if($campo_fecha=='cobro' ) echo 'checked="checked"';
																					 ?>
																					 onclick ="SincronizarCampoFecha()" />&nbsp;<label for="campo_fecha_cobro"><?=__("Corte")?></label>
			</span>
			<span title="<?=__($explica_periodo_emision)?>"><input type="radio" name="campo_fecha" id="campo_fecha_emision" value="emision"
																					<? if($campo_fecha=='emision' ) echo 'checked="checked"';
																					 ?>
																					 onclick ="SincronizarCampoFecha()" />&nbsp;<label for="campo_fecha_emision"><?=__("Emisión")?></label>
			</span>
		</td>
	</tr>
	<tr>
		<td align=left>
			<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]",$usuarios,"","Todos","200"); ?>	 </td>
		</td>
		<td align=left>
			<?=Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientes[]",$clientes,"","Todos","200"); ?>
		</td>
		<td align=left colspan=2 width='40%'>
			<div id=periodo style='display:<?=!$rango ? 'inline' : 'none' ?>;'>
				<select name="fecha_mes" style='width:90px'>
					<option value='1' <?=$fecha_mes==1 ? 'selected':'' ?>><?=__('Enero') ?></option>
					<option value='2' <?=$fecha_mes==2 ? 'selected':'' ?>><?=__('Febrero') ?></option>
					<option value='3' <?=$fecha_mes==3 ? 'selected':'' ?>><?=__('Marzo') ?></option>
					<option value='4' <?=$fecha_mes==4 ? 'selected':'' ?>><?=__('Abril') ?></option>
					<option value='5' <?=$fecha_mes==5 ? 'selected':'' ?>><?=__('Mayo') ?></option>
					<option value='6' <?=$fecha_mes==6 ? 'selected':'' ?>><?=__('Junio') ?></option>
					<option value='7' <?=$fecha_mes==7 ? 'selected':'' ?>><?=__('Julio') ?></option>
					<option value='8' <?=$fecha_mes==8 ? 'selected':'' ?>><?=__('Agosto') ?></option>
					<option value='9' <?=$fecha_mes==9 ? 'selected':'' ?>><?=__('Septiembre') ?></option>
					<option value='10' <?=$fecha_mes==10 ? 'selected':'' ?>><?=__('Octubre') ?></option>
					<option value='11' <?=$fecha_mes==11 ? 'selected':'' ?>><?=__('Noviembre') ?></option>
					<option value='12' <?=$fecha_mes==12 ? 'selected':'' ?>><?=__('Diciembre') ?></option>
				</select>
				<select name="fecha_anio" style='width:55px'>
					<? for($i=(date('Y')-5);$i < (date('Y')+5);$i++){ ?>
					<option value='<?=$i?>' <?=$fecha_anio == $i ? 'selected' : '' ?>><?=$i ?></option>
					<? } ?>
				</select>
			</div>
		</td>
	</tr>
</table>
</center>

<!-- SELECTOR FILTROS EXPANDIDO -->
<?
		$largo_select = 6;
		if( method_exists('Conf','GetConf') )
		{
			if( Conf::GetConf($sesion,'ReportesAvanzados_FiltrosExtra') )
			{
				$filtros_extra = true;
				$largo_select = 11;
			}
		}
		else if(method_exists('Conf','ReportesAvanzados_FiltrosExtra'))
		{
			if(Conf::ReportesAvanzados_FiltrosExtra())
			{
				$filtros_extra = true;
				$largo_select = 11;
			}
		}
	?>

<table id="full_filtros" style="border: 0px solid black; width:730px; <?=$filtros_check? '':'display:none;'?> " cellpadding="0" cellspacing="3">
	<tr valign=top>
		<td align=left>
			<b><?=__('Profesionales')?>:</b></td>
		<td align=left>
			<b><?=__('Clientes')?>:</b></td>
		<td align=left width='80px'>
			<b><?=__('Periodo de') ?>:</b>
		</td>
		<td colspan="<?= $filtros_extra? '2':'1'?>">
			<span title="<?=__($explica_periodo_trabajo)?>">
			<input type="radio" name="campo_fecha_F" value="trabajo" id = "campo_fecha_F"
																					<? if($campo_fecha=='trabajo' || $campo_fecha=='') echo 'checked="checked"'; ?> onclick ="SincronizarCampoFecha()" />
			<?=__("Trabajo")?>
			</span>
			<span title="<?=__($explica_periodo_cobro)?>">
			<input type="radio" name="campo_fecha_F" value="cobro" id = "campo_fecha_F"
																					<? if($campo_fecha=='cobro') echo 'checked="checked"';
																					 ?> onclick ="SincronizarCampoFecha()" />
			<?=__("Corte")?>
			<span title="<?=__($explica_periodo_emision)?>">
			<input type="radio" name="campo_fecha_F" value="emision" id = "campo_fecha_F"
																					<? if($campo_fecha=='emision') echo 'checked="checked"';
																					 ?> onclick ="SincronizarCampoFecha()" />
			<?=__("Emisión")?>
			</span>
		</td>
	</tr>

	<tr valign=top>
		<td rowspan="3" align=left>
		<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuariosF[]",$usuariosF,"class=\"selectMultiple\" multiple size=".$largo_select." ","","200"); ?>		</td>
		<td rowspan="3" align=left>
		<?=Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientesF[]",$clientesF,"class=\"selectMultiple\" multiple size=".$largo_select." ","","200"); ?>
	 	</td>
	 	<!-- PERIODOS -->
	 	<td colspan="<?= $filtros_extra? '3':'2'?>" align=center>
			<div id=periodo_rango>
				<?=__('Fecha desde')?>:&nbsp;
					<input type="text" name="fecha_ini" value="<?=$fecha_ini ? $fecha_ini : date("d-m-Y",strtotime("$hoy - 1 month")) ?>" id="fecha_ini" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
				<br />
				<?=__('Fecha hasta')?>:&nbsp;
					<input type="text" name="fecha_fin" value="<?=$fecha_fin ? $fecha_fin : date("d-m-Y",strtotime("$hoy")) ?>" id="fecha_fin" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
			</div>
		</td>
	</tr>
	<!-- TIPO DE ASUNTO Y AREA (CONFIGURABLE) !-->
	<?
	if($filtros_extra)
	{?>
	<tr>
		<td align=center colspan=2>
			<b><?=__("Tipo de Asunto")?>:</b>

		</td>
		<td align=center>
			<b><?=__("Area")?>:</b>
		</td>
	</tr>
	<tr>
		<td colspan=2>
			<?= Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_proyecto","tipos_asunto[]",$tipos_asunto,"class=\"selectMultiple\" multiple size=5 ","","110"); ?>
		</td>
		<td colspan=2>
			<?= Html::SelectQuery($sesion, "SELECT * FROM prm_area_proyecto","areas_asunto[]", $areas_asunto,"class=\"selectMultiple\" multiple size=5 ","","140");?>
		</td>
	</tr>
	<?}
	else
	{?>
		<tr>
			<td align=center>
				&nbsp;
			</td>
			<td align=center>
				&nbsp;
			</td>
		</tr>
		<tr>
			<td rowspan=2>
				&nbsp;
			</td>
			<td rowspan=2>
				&nbsp;
			</td>
		</tr>
	<?}?>
	<tr>
		<td align=left colspan=2>
			<input type="checkbox" name="area_y_categoria" value="1" <?=$area_y_categoria ? 'checked="checked"' : '' ?> onclick="Categorias(this, this.form);" title="Seleccionar área y categoría" />&nbsp;<span style="font-size:9px"><?=__('Seleccionar área y categoría') ?>
		</td>
	</tr>
	<tr>
		<td colspan=4>
			<div id="area_categoria" style="display:<?=$area_y_categoria ? 'inline' : 'none' ?>;">
				<table>
					<tr valign="top">
						<td align="left">
							<b><?=__('Área')?>:</b>
						</td>
						<td align="left">
							<b><?=__('Categoría')?>:</b>
						</td>
						<td align="left" colspan="2" width="40%">&nbsp;</td>
					</tr>
					<tr valign="top">
						<td rowspan="2" align="left">
							<?=Html::SelectQuery($sesion,"SELECT id, glosa FROM prm_area_usuario ORDER BY glosa", "areas[]", $areas, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
						</td>
						<td rowspan="2" align="left">
							<?=Html::SelectQuery($sesion,"SELECT id_categoria_usuario, glosa_categoria FROM prm_categoria_usuario ORDER BY glosa_categoria", "categorias[]", $categorias, 'class="selectMultiple" multiple="multiple" size="6" ', "", "200"); ?>
						</td>
						<td align="left" colspan="2" width="40%">&nbsp;</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
</fieldset>


			<!-- SELECTOR TIPO DE DATO -->
<br>
<fieldset align="center" width="90%" class="border_plomo tb_base">
<legend onClick="MostrarOculto('tipo_dato')" style="cursor:pointer">
	<span id="tipo_dato_img"><img src= "<?=Conf::ImgDir()?>/mas.gif" border="0" ></span>
	<?=__('Tipo de Dato')?>
</legend>
<input type="checkbox" name="tipo_dato_check" id="tipo_dato_check" value="1" <?=$tipo_dato_check? 'checked':''?> style="display:none;" />
<center>
<table id="mini_tipo_dato" >
	<tr>
		<td id = 'td_dato' class='<?=$comparar? 'borde_rojo':'borde_blanco'?>' >
			<select name='tipo_dato' id='tipo_dato' style='width:180px; ' onchange="TipoDato(this.value)" >
			<?
				foreach($tipos_de_dato as $tipo)
				{
					echo "<option value='".$tipo."'";
					if($tipo_dato == $tipo)
						echo "selected";
					echo "> ".__($tipo)."</option>";
				}
			?>
			</select>
		</td>
		<td>
			<span id="vs" style='<?=$comparar? '':'display: none;'?>'>
				<?=__(" Vs. ")?>
			</span>
		</td>
		<td id = 'td_dato_comparado' class='borde_azul' style='<?=$comparar? '':'display: none;'?>' >

			<select name='tipo_dato_comparado' id='tipo_dato_comparado' style='width:180px; ' >
			<?
				foreach($tipos_de_dato as $tipo)
				{
					echo "<option value='".$tipo."'";
					if($tipo_dato_comparado == $tipo)
						echo "selected";
					echo "> ".__($tipo)."</option>";
				}
			?>
			</select>
		</td>
	</tr>
</table>
</center>
				<!-- SELECTOR TIPO DE DATO EXPANDIDO-->
<table id="full_tipo_dato" style="border: 0px solid black; width:730px; display: none;" cellpadding="0" cellspacing="0">
	<tr>
		<td align="center">
		<?
			function celda($nombre)
			{
				global $tipo_dato;
				global $tipo_dato_comparado;
				global $comparar;
				global $glosa_dato;
				echo "<td id=\"".$nombre."\"rowspan=2 align=\"center\" class=";
				if($tipo_dato == $nombre || ( !isset($tipo_dato) && $nombre=='horas_trabajadas' ))
					echo "boton_presionado";
				else if($tipo_dato_comparado == $nombre && $comparar)
					echo "boton_comparar";
				else
					echo "boton_normal";
				echo " style=\"height:25px; font-size: 11px; vertical-align: middle; cursor:pointer; \"";
				echo "onclick= TipoDato('".$nombre."')";
				echo " title= \"".__($glosa_dato[$nombre])."\"";
				echo " > ".__($nombre)."</td>";
			}
			function celda_disabled($nombre)
			{
				echo "<td id=\"".$nombre."\"rowspan=2 align=\"center\" class= boton_disabled style=\"height:25px; font-size: 11px; vertical-align: middle;\"";
				echo " title= \"".__($glosa_dato[$nombre])."\"";
				echo " > ".__($nombre)."</td>";
			
			}
			function borde_abajo($colspan = 1)
			{
				echo "<td";
				if($colspan!=1) echo " colspan=".$colspan;
				echo " style=\"width:10px; font-size: 3px; border-bottom-style: dotted; border-width: 1px; \"> &nbsp; </td>";
			}
			function borde_derecha()
			{
				echo "<td rowspan=3 style=\"font-size: 3px; width:10px; border-right-style: dotted; border-width: 1px; \"> &nbsp; </td>";
			}
			function nada($numero = 1)
			{
				for($i=0; $i< $numero; $i++)
				echo "<td style=\"font-size: 3px; width:10px; height:7px; \"> &nbsp; </td>";
			}
			function titulo_proporcionalidad()
			{
				echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
				echo "<div id='titulo_proporcionalidad' style =\" height:25px; font-size: 14px;  display:inline;\" >";
				echo "&nbsp;&nbsp;".__('Proporcionalidad').":</div>";
				echo "</td>";
			}
			function select_proporcionalidad()
			{
				global $proporcionalidad;
				$o1 = 'selected';
				$o2 = '';
				if($proporcionalidad == 'cliente')
				{
					$o1 = '';
					$o2 = 'selected';
				}
				echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
				echo "<div id='select_proporcionalidad' style =\" height:25px; font-size: 14px;  display:inline;\" >";
				echo "&nbsp;&nbsp;<select name='proporcionalidad'>";
					echo "<option value='estandar' ".$o1.">".__('Estándar')."</option>";
					echo "<option value='cliente' ".$o2.">".__('Cliente')."</option>";
				echo "</select></td>";
			}
			function visible_moneda($s,$select = '')
			{
				global $tipo_dato;
				echo "<td rowspan=2 style=\"vertical-align: middle;\" >";
				echo "<div id='moneda".$select."' style =\" height:25px; font-size: 14px; ";
				if ( in_array($tipo_dato,array('valor_cobrado','valor_por_cobrar','valor_pagado','valor_por_pagar','valor_trabajado_estandar')))
					echo " display:inline;\" >";
				else
					echo " display:none;\" >";
				echo $s."</div>";

				echo "<div id='anti_moneda".$select."' style =\" ";
				if ( !in_array($tipo_dato,array('valor_cobrado','valor_por_cobrar','valor_pagado','valor_por_pagar','valor_trabajado_estandar')))
					echo " display:inline;\" >";
				else
					echo " display:none;\" >";
				echo "&nbsp; </div>";

				echo "</td>";
			}
			function moneda()
			{
				visible_moneda(__('Moneda').':');
			}
			function select_moneda()
			{
				global $sesion;
				global $id_moneda;
                                if( $id_moneda ) 
                                    $moneda = $id_moneda;
                                else 
                                    $moneda = Moneda::GetMonedaReportesAvanzados( $sesion );
				visible_moneda(Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda",$moneda, '','',"60"),'_select');
			}
			function tinta2()
			{
				global $comparar;
				echo "<td rowspan=3 align=\"center\" style=\"vertical-align: middle; width:70px; height: 20px; \"> ";
					echo "<table id = \"tipo_tinta\" ";
					if(!$comparar)
						echo " style =\" display:none; \" ";
					else
						echo " ";
					echo ">";
						echo "<tr>";
							echo "<td> <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"rojo\" checked=\"checked\" > </td>";
							echo "<td style= \"background-color: red;\" >&nbsp;&nbsp;&nbsp;&nbsp;</td>";
							echo "<td> <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"azul\"> </td>";
							echo "<td style= \"background-color: blue;\" > &nbsp;&nbsp;&nbsp;&nbsp;</td>";
						echo "</tr>";
					echo "</table>";
				echo "&nbsp; </td>";
			}
			function tinta()
			{
				global $comparar;
				echo "<td rowspan=3 align=\"center\" style=\"vertical-align: middle; width:100px; height: 20px; \"> ";
					echo "<span id= \"tipo_tinta\" style =\" width: 100px; ";
					if(!$comparar)
						echo " display:none; ";
					echo " \" >";
							echo "<input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"rojo\" checked=\"checked\" >";
							echo "<span style= \"background-color: red;\" >&nbsp;&nbsp;&nbsp;&nbsp;</span>";
							echo " <input type=\"radio\" name=\"tinta\" id=\"tinta\" value=\"azul\"> ";
							echo "<span style= \"background-color: blue;\" >&nbsp;&nbsp;&nbsp;&nbsp;</span> </span>";
				echo "&nbsp; </td>";
			}
		?>
		<table style="border: 0px solid black; width:730px" cellpadding="0" cellspacing="0">
			<tr>
				<?=celda('horas_trabajadas')?>
				<?=borde_abajo(2)?>
				<?=celda('horas_cobrables')?>
				<?=borde_abajo(2)?>
				<?=celda('horas_visibles')?>
				<?=borde_abajo(2)?>
				<?=celda('horas_cobradas')?>
				<?=borde_abajo(2)?>
				<?=celda('horas_pagadas')?>
			</tr>
			<tr>
				<?=borde_derecha()?>
				<?=nada()?>
				<?=borde_derecha()?>
				<?=nada()?>
				<?=borde_derecha()?>
				<?=nada()?>
				<?=borde_derecha()?>
				<?=nada()?>
			</tr>
			<tr>
				<?=nada(9)?>
			</tr>
			<tr>
				<?=nada()?>
				<?=borde_abajo()?>
				<?=celda("horas_no_cobrables")?>
				<?=borde_abajo()?>
				<?=celda("horas_castigadas")?>
				<?=borde_abajo()?>
				<?=celda("horas_por_cobrar")?>
				<?=borde_abajo()?>
				<?=celda("horas_por_pagar")?>
			</tr>
			<tr>
				<?=nada(5)?>
				<?=borde_derecha()?>
				<?=nada(3)?>
			</tr>
			<tr>
				<?=nada(12)?>
			</tr>
			<tr>
				<?=nada(7)?>
				<?=borde_abajo()?>
				<?=celda("horas_incobrables")?>
				<?=nada(3)?>
			</tr>
			<tr>
				<?=nada(1)?>
			</tr>
			<tr>
				<?=nada(13)?>
			</tr>
			<tr>
				<?=celda_disabled('valor_trabajado')?>
				<?=borde_abajo(2)?>
				<?=celda_disabled('valor_cobrable')?>
				<?=borde_abajo(2)?>
				<?=celda_disabled('valor_visible')?>
				<?=borde_abajo(2)?>
				<?=celda('valor_cobrado')?>
				<?=borde_abajo(2)?>
				<?=celda('valor_pagado')?>
			</tr>
			<tr>
				<?=borde_derecha()?>
				<?=nada()?>
				<?=borde_derecha()?>
				<?=nada()?>
				<?=borde_derecha()?>
				<?=nada()?>
				<?=borde_derecha()?>
				<?=nada()?>
			</tr>
			<tr>
				<?=nada(9)?>
			</tr>
			<tr>
				<?=celda('valor_trabajado_estandar')?>
				<?=borde_abajo()?>
				<?=celda_disabled("valor_no_cobrable")?>
				<?=borde_abajo()?>
				<?=celda_disabled("valor_castigado")?>
				<?=borde_abajo()?>
				<?=celda("valor_por_cobrar")?>
				<?=borde_abajo()?>
				<?=celda("valor_por_pagar")?>
			</tr>
			<tr>
				<?=nada(4)?>
				<?=borde_derecha()?>
				<?=nada(3)?>
			</tr>
			<tr>
				<?=nada(12)?>
			</tr>
			<tr>
				<?=nada(7)?>
				<?=borde_abajo()?>
				<?=celda("valor_incobrable")?>
				<?=nada(3)?>
			</tr>
			<tr>
				<?=nada(1)?>
			</tr>
			<tr>
				<?=nada(13)?>
			</tr>
			<tr>
				<?=titulo_proporcionalidad()?>
				<?=nada(2)?>
				<?=moneda()?>
				<?=nada(2)?>
				<?=celda("valor_estandar")?>
				<?=nada(2)?>
				<?=celda("diferencia_valor_estandar")?>
				<?=nada(2)?>
				<?=celda("valor_hora");?>
			</tr>
			<tr>
				<?=nada(2)?>
				<?=nada(6)?>
			</tr>
			<tr>
				<?=nada(12)?>
			</tr>
			<tr>
				<?=select_proporcionalidad()?>
				<?=nada(2)?>
				<?=select_moneda()?>
				<?=nada(5)?>
				<?=celda("rentabilidad_base")?>
				<?=nada(2)?>
				<?=celda("rentabilidad")?>
			</tr>
			<tr>
				<?=nada(8)?>
			</tr>
			<tr>
				<?=nada(12)?>
				<?=tinta()?>
			</tr>
		</table>
	 </td>
	</tr>
</table>
</fieldset>
			<!-- SELECTOR DE VISTA -->
<br>
<fieldset align="center" width="90%" class="border_plomo tb_base">
<legend><?=__('Vista')?></legend>
<table style="border: 0px solid black; width:730px" cellpadding="0" cellspacing="4">
<!--<tr>
	<td style="width: 330px; font-size: 11px;" colspan=6>
		<?=__('Agrupar por')?>:&nbsp;
		<img src="<?=Conf::ImgDir()?>/mas.gif" onclick="Agrupadores(1)"; id='mas_agrupadores'
		 style='<?=$numero_agrupadores==6?'display:none;':''?> cursor:pointer;' />
		<img src="<?=Conf::ImgDir()?>/menos.gif" onclick="Agrupadores(-1)"; id='menos_agrupadores'
		 style=' <?=$numero_agrupadores==1?'display:none;':''?> cursor:pointer;' />
		<select name="vista" id="vista" onchange="RevisarTabla();">
		<?
				foreach($vistas as $key => $v)
				{
					$s = implode('-',$v);
					echo '<option value="'.$s.'" ';
					if($vista == $s)
						echo 'selected';
					echo ">".implode(' - ',$vistas_lang[$key]);
					echo "</option>\n";
				}
		?>
		</select>
		<input type=hidden name=numero_agrupadores id=numero_agrupadores value=<?=$numero_agrupadores?> />
		<input type=hidden name=vista id=vista value='' />
	</td>
</tr>-->
<tr>
	<td colspan=6 align=left>
	<div style="float:left">
		<img src="<?=Conf::ImgDir()?>/menos.gif" onclick="Agrupadores(-1)"; 
		 style='cursor:pointer;' />
		 <img src="<?=Conf::ImgDir()?>/mas.gif" onclick="Agrupadores(1)"; 
		 style='cursor:pointer;' />
		 <?=__('Agrupar por')?>:&nbsp;
		<input type=hidden name=numero_agrupadores id=numero_agrupadores value=<?=$numero_agrupadores?> />
		<input type=hidden name=vista id=vista value='' />
	</div>
	<div style="float:left">
		<?
				$ya_elegidos = array();
				for($i=0;$i<6;$i++)
				{
						echo '<span id="span_agrupador_'.$i.'"';
						if( $i >= $numero_agrupadores)
							echo ' style="display:none;" ';
						echo '>';
						echo '<select name="agrupador['.$i.']" id="agrupador_'.$i.'" style="font-size:10px; margin-top:2px; margin-bottom:2px; margin-left:6px; width:110px;" onchange="CambiarAgrupador('.$i.');"  ';
						echo '/>';
						$elegido = false;
						$valor_previo = '';
						foreach($agrupadores as $key => $v)
						{
							if(!in_array($v,$ya_elegidos))
							{
								echo '<option value="'.$v.'" ';
								if(isset($agrupador[$i]))
								{
									if($agrupador[$i] == $v)
									{
										echo 'selected';	
										$valor_previo = '<select style="display:none;" id="agrupador_valor_previo_'.$i.'"><option value = "'.$v.'">'.__($v).'</option></select>';
										$ya_elegidos[] = $v;
									}
								}
								else if(!$elegido)
								{
									echo 'selected';
									$valor_previo = '<select style="display:none;" id="agrupador_valor_previo_'.$i.'"><option value = "'.$v.'">'.__($v).'</option></select>';
									$elegido = true;
									$ya_elegidos[] = $v;
								}
								echo ">".__($v);
								echo "</option>";
							}
						}
						echo '</select></span>';
						echo $valor_previo;
				}
		?>
	</div>
	</td>
</tr>
<tr>
	<td align=center colspan=5>
			<input type="button" class="btn" title="<?=__($glosa_boton['planilla'])?>" value="<?=__('Planilla')?>" onclick="Generar(this.form,'print')" />
			<input type="button" class="btn" title="<?=__($glosa_boton['excel'])?>" value="<?=__('Excel')?>" onclick="Generar(this.form,'excel');">
			<input type="button" class="btn" title="<?=__($glosa_boton['tabla'])?>" id="tabla" value="<?=__('Tabla')?>" onclick="Generar(this.form,'tabla');">
			<input type="button" class="btn" title="<?=__($glosa_boton['barra'])?>" value="<?=__('Barras')?>" onclick="Generar(this.form,'barra');">
			<input type="button" class="btn" title="<?=__($glosa_boton['dispersion'])?>" id="dispersion" style="<?= $comparar ? '':'display:none;'?>" value="<?=__('Dispersión')?>" onclick="Generar(this.form,'dispersion');">
			<input type=button class=btn title="<?=__($glosa_boton['torta'])?>" id="circular" value="<?=__('Gráfico Torta')?>" onclick="Generar(this.form,'circular');" style="<?= $comparar ? 'display:none;':''?>" >
	</td>
	<td style="width: 100px; font-size: 11px;">
		<label for="comparar"><?=__('Comparar')?>:</label> <input type="checkbox" name="comparar" id="comparar" value="1" onclick="Comparar()" <?= $comparar? 'checked':''?> title='Comparar' /> </td>
	</td>
</tr>
<tr>
	<td colspan = 6>
		<table cellpadding="2" cellspacing="5">
			<tr>
				<td>
					<input type="checkbox" name="orden_barras_max2min" id="orden_barras_max2min" value="1"
					<?
						if(isset($orden_barras_max2min) || !isset($tipo_dato))
							echo 'checked="checked"';
					?>
					title=<?=__('Ordenar Gráfico de Barras de Mayor a Menor')?> onclick="MostrarLimite(this.checked)"/>
					<label for="orden_barras_max2min"><?=__("Gráficar de Mayor a Menor")?></label>
				</td>
				<td>
					<span id = "limite_check" <? if(!isset($orden_barras_max2min) && isset($tipo_dato) ) echo 'style= "display: none; "'; ?>>
						<input type="checkbox" name="limitar" id="limite_checkbox" value="1" <?=$limitar?'checked="checked"':''?> />
						<label for="limite_checkbox"><?=__("y mostrar sólo") ?></label> &nbsp;
						<input type="text" name="limite" value="<?=$limite ? $limite : '5' ?>" id="limite" size="2" maxlength="2" /> &nbsp;
						<?=__("resultados superiores") ?>
					<span>
				</td>
				<td>
					<span id = "agupador_check">
						<input type="checkbox" name="agrupar" id="agrupador_checkbox" value="1" <?= $agrupar? 'checked':''?> />
						<label for="agrupador_checkbox"><?=__("agrupando el resto") ?></label>. &nbsp;
					<span>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</fieldset>
</td></tr></table>

<script> RevisarMoneda(); RevisarCircular(); RevisarTabla();</script>

		<!-- RESULTADO -->
<?
}

	$alto = 800;
	switch($opc)
	{
		case 'print':
		{
			$url_iframe = "reporte_avanzado_planilla.php?popup=1";
			break;
		}
		case 'circular':
		{
			$url_iframe = "reporte_avanzado_grafico.php?tipo_grafico=circular&popup=1";
			$alto = 540;
			if($orden_barras_max2min)
				$url_iframe .= "&orden=max2min";
			break;
		}
		case 'barra':
		{
			$url_iframe = "reporte_avanzado_grafico.php?tipo_grafico=barras&popup=1";
			$alto = 540;
			if($orden_barras_max2min)
				$url_iframe .= "&orden=max2min";
			break;
		}
		case 'dispersion':
		{
			$url_iframe = "reporte_avanzado_grafico.php?tipo_grafico=dispersion&popup=1";
			$alto = 640;
			if($orden_barras_max2min)
				$url_iframe .= "&orden=max2min";
			break;
		}
	}
	$url_iframe .= "&tipo_dato=".$tipo_dato;
	$url_iframe .= "&vista=".$vista;
	$url_iframe .= "&id_moneda=".$id_moneda;
	$url_iframe .= "&prop=".$proporcionalidad;

	if($limitar)
		$url_iframe .= "&limite=".$limite;
	if($agrupar)
		$url_iframe .= "&agrupar=1";

	if($filtros_check)
	{
		if(is_array($clientesF))
			$url_iframe .= "&clientes=".implode(',',$clientesF);
		if(is_array($usuariosF))
			$url_iframe .= "&usuarios=".implode(',',$usuariosF);

		if(is_array($areas_asunto))
			$url_iframe .= "&areas_asunto=".implode(',',$areas_asunto);
		if(is_array($tipos_asunto))
			$url_iframe .= "&tipos_asunto=".implode(',',$tipos_asunto);

		if($area_y_categoria)
		{
			if(is_array($areas))
				$url_iframe .= "&areas_usuario=".implode(',',$areas);
			if(is_array($categorias))
				$url_iframe .= "&categorias_usuario=".implode(',',$categorias);
		}

		$url_iframe .= "&fecha_ini=".$fecha_ini;
		$url_iframe .= "&fecha_fin=".$fecha_fin;
	}
	else
	{
		if(is_array($clientes))
			$url_iframe .= "&clientes=".implode(',',$clientes);
		if(is_array($usuarios))
			$url_iframe .= "&usuarios=".implode(',',$usuarios);

		$url_iframe .= "&fecha_ini=01-".$fecha_mes."-".$fecha_anio;
		$fecha_ultimo_dia = date('t',mktime(0,0,0,$fecha_mes,5,$fecha_anio));
		$url_iframe .= "&fecha_fin=".$fecha_ultimo_dia."-".$fecha_mes."-".$fecha_anio;
	}
	$url_iframe .= "&campo_fecha=".$campo_fecha;

	if($comparar)
		$url_iframe .= "&tipo_dato_comparado=".$tipo_dato_comparado;

?>
</form>

	<?
	if($opc && $opc != 'nuevo_reporte' && $opc != 'eliminar_reporte'):
	?>
		 <iframe name=planilla id=planilla src='<?=$url_iframe ?>' frameborder=0 width=730px height=<?=$alto?>px></iframe>
	<? endif; ?>

<script>
Calendar.setup(
	{
		inputField	: "fecha_ini",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_ini"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_fin",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_fin"		// ID of the button
	}
);
</script>
<?
	$pagina->PrintBottom($popup);
?>

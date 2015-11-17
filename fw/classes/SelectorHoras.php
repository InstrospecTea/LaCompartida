<?php 
require_once dirname(__FILE__).'/../../app/conf.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class SelectorHoras
{
	function PrintTimeSelector($sesion, $input_name, $value, $max_horas, $oncambio='', $editable=true)
    {
    	if( method_exists('Conf','GetConf') )
    		$intervalo = Conf::GetConf($sesion,'Intervalo');
    	else if( method_exists('Conf','Intervalo') ) 
    		$intervalo = Conf::Intervalo();
			$hora_separado = split(':',$value);
			if( $hora_separado[1]%$intervalo != 0 )
					$hora_separado[1]=Utiles::EnIntervalo( $sesion, $hora_separado[1] );
			if( $max_horas == '' )
				{
				if( $editable )
					$html = "<input type=\"text\" id=\"hora_".$input_name."\" size=\"3\" onchange=\"CambiaHora(this.value,'".$input_name."');\" value=\"".$hora_separado[0]."\">";
				else
					$html = "<input type=\"text\" id=\"hora_".$input_name."\" size=\"3\" readonly value=\"".$hora_separado[0]."\">";
				}
			else
				{
					if( $editable )
						$html = "<select id=\"hora_".$input_name."\" onchange=\"CambiaHora(this.value,'".$input_name."','".$max_horas."');".$oncambio."\" value=\"".$hora_separado[0]."\">"; 
					else
						$html = "<select id=\"hora_".$input_name."\" onfocus=\"this.defaultIndex=this.selectedIndex;\" onchange=\"this.selectedIndex=this.defaultIndex;\" value=\"".$hora_separado[0]."\">"; 
						 for($i=0;$i<$max_horas+1;$i++) 
									{ 
										if( $hora_separado[0]==Utiles::PongaCero($i) )
												$html .= "<option value=\"".$i."\" selected>".Utiles::PongaCero($i)."</option>"; 
										else 
												$html .= "<option value=\"".$i."\">".Utiles::PongaCero($i)."</option>"; 
									}	
				$html .= "</select>";
				}
					if($editable)
						$html .= "<select id=\"minuto_".$input_name."\" onchange=\"CambiaMinuto(this.value,'".$input_name."','".$max_horas."');".$oncambio."\" value='".$hora_separado[1]."'>"; 
					else
						$html .= "<select id=\"minuto_".$input_name."\" onfocus=\"this.defaultIndex=this.selectedIndex;\" onchange=\"this.selectedIndex=this.defaultIndex;\" value='".$hora_separado[1]."'>";
							 for($j=0;$j<60;$j+=$intervalo) 
									{ 
										if( $hora_separado[1]==Utiles::PongaCero($j) )
												$html .= "<option value=\"".$j."\" selected>".Utiles::PongaCero($j)."</option>"; 
										else
												$html .= "<option value=\"".$j."\">".Utiles::PongaCero($j)."</option>"; 
									}	
				 if($editable)
          {
               $html .= "</select><img id=\"subir_hora\" onmousedown=\"setMouseDown('".$input_name."','subir',".$intervalo.");\" onmouseup=\"setMouseUp();\" onmouseout=\"setMouseUp();\" src=\"".Conf::ImgDir()."/mas.gif\" />
                  			 <img id=\"bajar_hora\" onmousedown=\"setMouseDown('".$input_name."','bajar',".$intervalo.");\" onmouseup=\"setMouseUp();\" onmouseout=\"setMouseUp();\" src=\"".Conf::ImgDir()."/menos.gif\" />
                         <input type=\"hidden\" name=\"".$input_name."\" id=\"".$input_name."\" value=\"".$value."\" />";
          }
         else
          {
               $html .= "</select><img id=\"subir_hora\" src=\"".Conf::ImgDir()."/mas.gif\" />
   	                     <img id=\"bajar_hora\" src=\"".Conf::ImgDir()."/menos.gif\" />
                         <input type=\"hidden\" name=\"".$input_name."\" id=\"".$input_name."\" value=\"".$value."\" />";
          }
		return $html; 
    } 
    
  function Javascript()
  {
    $output = "
		<script type=\"text/javascript\">
		function CambiaHora( horas, campo, max_horas )
		{
			var tiempo = $(campo).value;
			tiempo = tiempo.split(':');
			$(campo).value = PongaCero(horas)+':'+tiempo[1]+':00';
			if(campo=='duracion') 
				{
					$('duracion_cobrada').value = PongaCero(horas)+':'+tiempo[1]+':00';
					$('hora_duracion_cobrada').value = horas;
					$('minuto_duracion_cobrada').value = $('minuto_duracion').value;
				}
			if( horas == max_horas )
				CambiaMinuto( '0', campo, 'limit' );
		}
		
		function CambiaMinuto( minutos, campo, max_horas)
		{
			var tiempo = $(campo).value;
			tiempo = tiempo.split(':');
			if( tiempo[0] == max_horas || max_horas == 'limit' )
				{
				minutos = '0';
				$('minuto_'+campo).value = minutos;
				}
			$(campo).value = tiempo[0]+':'+PongaCero(minutos)+':00';
			if(campo=='duracion') 
				{
					$('duracion_cobrada').value = tiempo[0]+':'+PongaCero(minutos)+':00';
					$('minuto_duracion_cobrada').value = minutos;
					$('hora_duracion_cobrada').value = $('hora_duracion').value;
				}
		}
		
		function PongaCero( numero )
		{
		 		if( numero < 10 )
				numero = '0'+numero;
		 		return numero;
		}
		
		
		function SubeTiempo( campo, direccion, intervalo, cont )
		{
			var gRepeatTimeInMS = $('gRepeatTimeInMS').value;
			var gIsMouseDown = $('gIsMouseDown').value;
			
			if( !cont )
				var cont=0;
			
			if( gIsMouseDown=='true' )
			{
				cont++;
				if(cont==5)
					$('gRepeatTimeInMS').value = 100;
				else if(cont==10)
					$('gRepeatTimeInMS').value = 50;
				else if(cont==20)
					$('gRepeatTimeInMS').value = 25;
				var tiempo = $(campo).value;
				tiempo = tiempo.split(':');
				if( direccion == 'subir' && tiempo[0] < $('max_hora').value )
					{
					var minutos = (tiempo[1]-0)+intervalo;
					if(minutos > 59)
						{
							$(campo).value = PongaCero((tiempo[0]-0)+1)+':'+PongaCero(minutos-60)+':00';
							$('hora_'+campo).value = (tiempo[0]-0)+1;
							$('minuto_'+campo).value = minutos-60;
							if( campo=='duracion' )
								{
								if( $('duracion_cobrada') )
									{
									$('duracion_cobrada').value = PongaCero((tiempo[0]-0)+1)+':'+PongaCero(minutos-60)+':00';
									$('hora_duracion_cobrada').value = (tiempo[0]-0)+1;
									$('minuto_duracion_cobrada').value = minutos-60;
									}
								}
						}
					else
						{
							$(campo).value = tiempo[0]+':'+PongaCero(minutos)+':00';
							$('minuto_'+campo).value = minutos;
							if( campo=='duracion' )
								{
									if( $('duracion_cobrada') )
										{
											$('duracion_cobrada').value = tiempo[0]+':'+PongaCero(minutos)+':00';
											$('hora_duracion_cobrada').value = tiempo[0]-0;
											$('minuto_duracion_cobrada').value = minutos;
										}
								}
						}
					}
				else if( direccion == 'bajar' && ( tiempo[0] > 0 || tiempo[1] > 0 ) )
					{
						var minutos = tiempo[1]-intervalo;
						if( minutos < 0 && $('hora_'+campo).value > 0 )
							{
								$(campo).value = PongaCero(tiempo[0]-1)+':'+PongaCero(minutos+60)+':00';
								$('hora_'+campo).value = tiempo[0]-1;
								$('minuto_'+campo).value = minutos+60;
								if( campo=='duracion' )
									{
										if( $('duracion_cobrada') )
											{
												$('duracion_cobrada').value = PongaCero(tiempo[0]-1)+':'+PongaCero(minutos+60)+':00';
												$('hora_duracion_cobrada').value = tiempo[0]-1;
												$('minuto_duracion_cobrada').value = minutos+60;
											}
									}
							}
						else
							{
								$(campo).value = tiempo[0]+':'+PongaCero(minutos)+':00';
								$('minuto_'+campo).value = minutos;
								if( campo=='duracion' )
									{
										if( $('duracion_cobrada') )
											{
												$('duracion_cobrada').value = tiempo[0]+':'+PongaCero(minutos)+':00';
												$('hora_duracion_cobrada').value = tiempo[0]-0;
												$('minuto_duracion_cobrada').value = minutos;
											}
									}
							}
					}
				setTimeout(\"SubeTiempo('\"+campo+\"', '\"+direccion+\"', \"+intervalo+\",\"+cont+\" );\", gRepeatTimeInMS);
			}
			else
				$('gRepeatTimeInMS').value = 200;
		}
		
		
		
		function setMouseDown( campo, direccion, intervalo )
		{
		$('gIsMouseDown').value = true;
		SubeTiempo( campo, direccion, intervalo );
		}
		
		function setMouseUp()
		{
		$('gIsMouseDown').value = false;
		}
	</script>";
		return $output;
  }
}

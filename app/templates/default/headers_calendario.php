<!--Incluye classes Javascript del dhtmlxScheduler -->
<script src="<?=Conf::RootDir()?>/fw/js/dhtmlxScheduler/codebase/dhtmlxscheduler.js?v=091201" type="text/javascript" charset="utf-8"></script>
	<script src="<?=Conf::RootDir()?>/fw/js/dhtmlxScheduler/codebase/ext/dhtmlxscheduler_agenda_view.js?v=091201" type="text/javascript" charset="utf-8"></script>
	<script src="<?=Conf::RootDir()?>/fw/js/dhtmlxScheduler/codebase/ext/dhtmlxscheduler_year_view.js?v=091201" type="text/javascript" charset="utf-8"></script>
  
 <!--Incluye css del dhtmlxScheduler -->
	<link rel="stylesheet" href="<?=Conf::RootDir()?>/fw/js/dhtmlxScheduler/codebase/dhtmlxscheduler.css" type="text/css" media="screen" title="no title" charset="utf-8" />
	<link rel="stylesheet" href="<?=Conf::RootDir()?>/fw/js/dhtmlxScheduler/codebase/ext/dhtmlxscheduler_ext.css" type="text/css" title="no title" charset="utf-8" />


<!-- Defina css individualmento por distinto eventos -->
<style type="text/css" media="screen">
		html, body{
			margin:0px;
			padding:0px;
			height:100%;
		}	
	.good_day .dhx_month_body{
		background-color: #FFFF80;
	}
	.good_day .dhx_month_head{
		background-color: #EE91EC;
	}
	</style>
	<? 
	echo "<style type=\"text/css\" media=\"screen\">";
	$query = "SELECT tabla_datos, color_datos FROM datos_calendario";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	while( $row = mysql_fetch_array($resp) ) 
	{
	echo ".dhx_cal_event.".$row['tabla_datos']." div{
		background-color:".$row['color_datos']." !important; 
		color:white !important;
	}
	.dhx_cal_event_line.".$row['tabla_datos']."{
		background-color:".$row['color_datos']." !important; 
		color:white !important;
	}
	.dhx_cal_event_clear.".$row['tabla_datos']."{
		color:".$row['color_datos']." !important;
	}";
	} 
	echo "</style>"; 
	?> 


<!-- En la function init() se puede definir codigo que sea execuado cuando hay eventos.
 		tambien se pueden definir opciones de la configuracion en init() -->
<script type="text/javascript" charset="utf-8">
	function init() {
		/* configuraciones */
		
		//para cargar solo los datos necesario para la vista actual
		//todavia con problemas, ademas hay que hacerlo compatible con los filtros 
		scheduler.setLoadMode("month");
		
		//scheduler.config.hour_date = '';
		scheduler.config.drag_resize = false;
		scheduler.config.drag_move = false;
		scheduler.config.drag_create = false;
		scheduler.config.first_hour = 6;
		scheduler.config.last_hour = 24;
		scheduler.config.xml_date="%Y-%m-%d %H:%i";
		//minimal date size step in minutes
		scheduler.config.date_step = "5";
		
		scheduler._load_url = "<?=Conf::RootDir()?>/app/interfaces/eventos.php<?=$argumentos?>";
		
		//height of top area with navigation buttons and tabs
		scheduler.xy.nav_height=22;
		
		//height of event bars in month view
		scheduler.xy.bar_height=20;
		
		//expected width of scrollbar
		//scheduler.xy.scroll_width=18;

		//width of y-scale
		//scheduler.xy.scale_width=50;
		
		//height of x-scale
		//scheduler.xy.scale_height=20;
	
		//width of selection menu on the day|week views
		scheduler.xy.menu_width=25;
		
		scheduler.config.show_loading=true;
		
		scheduler.config.agenda_start = new Date(); //now

		scheduler.config.agenda_end = scheduler.date.add(new Date(), 1, "month"); //1 month from a current date

		/* templates, define aqui el html de eventos */
		scheduler.templates.event_class=function(start,end,event)
		{
			return event.type;
		}
		
		scheduler.templates.event_text=function(start,end,event)
		{
				var text = event.text;
				var titulo = event.titulo;
				var icon = event.icon;
				if( icon != '' )
					return "<img src='<?=Conf::RootDir()?>"+icon+"' height='10px'/><b>"+titulo+"</b><br><b>Descripcion:</b> "+text;
				else 
					return "<b>"+titulo+"</b><br><b>Descripcion:</b> "+text;
		}
		scheduler.templates.event_header=function(start,end,event){
        return scheduler.templates.hour_scale(start);
		}
		scheduler.templates.event_bar_text=function(start,end,event)
		{
        var text = event.text;
        var icon = event.icon;
        if( icon != '' )
        	return "<img src='<?=Conf::RootDir()?>"+icon+"' height='12px'/>&nbsp;<span title='"+text+"'>"+text+"</span>";
				else 
					return "<span title='"+text+"'>&nbsp;"+text+"&nbsp;</span>";
		}
		scheduler.templates.year_tooltip=function(start,end,event){
			var text = event.text;
      var icon = event.icon;
			return "<img src='<?=Conf::RootDir()?>"+icon+"' height='15px'/>&nbsp;<span title='"+text+"'>"+text+"</span>";
		}
		
		/* cargar datos de los eventos al calendario */ 
		
		
		/* agregar eventos, define codigo individual para definir que pasa en caso de eventos */
		/*scheduler.attachEvent("onMouseOver", function (event_id, native_event_object){
       var evs = scheduler.getEvent( event_id );
       ddrivetip(html);
  	});*/
  	scheduler.showLightbox = function(id){
  				var convert = scheduler.date.date_to_str("%d-%m-%Y");
					var fecha = convert( scheduler.getEvent( id ).start_date );
  				/*alert( fecha );*/
         if( scheduler.getEvent( id ).tabla_id > 0 )
         	{
         		var evs = scheduler.getEvent( id ); 
						var id_tabla = evs.tabla_id;
						
						<? 
						$cont=1;
						$query = "SELECT tabla_datos, url_modificacion FROM datos_calendario";
						$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						
						while( list($tipo,$url)=mysql_fetch_array($resp) )
						{ 
							if( $cont==1 )
							{
								echo " if( evs.type=='".$tipo."' ) var vurl = '".$url."'+id_tabla; ";
							}
							else
								echo " else if( evs.type=='".$tipo."' ) var vurl = '".$url."'+id_tabla; ";
								
								$cont++;
			      }
			      ?>
			      if( vurl )
			      nuevaVentana('Modificar_Evento','550','550', vurl,'top=100,left=120');
         	}
         else
         	{
         		var text_window = "<img src='<?=Conf::ImgDir()?>/tarea.gif'>&nbsp;&nbsp;<span style='font-size:15px; color:#FF0000; text-align:center;font-weight:bold'><u><?=__("Nuevo Evento")?></u><br><br>";
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?=__('Agrega evento del tipo:')?>.</span><br>';
							text_window += '<br><form id="form_tipo"><table width="100%">';
							<?
							$query = "SELECT id_datos_calendario, url_modificacion, glosa_datos FROM datos_calendario WHERE monstrar_datos=1";
							$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
							 
							while( list( $id, $url, $glosa ) = mysql_fetch_array($resp) )
							{ ?>
								var url = '<?=$url?>null&fecha='+ fecha;
								text_window += '<tr><td width="25%"></td><td width="80%" align="left"><input type="radio" name="tipo" id="tipo" value="'+url+'" /><?=$glosa?></td></tr>';
					<?	} ?>
							text_window += '</table></form>';
							Dialog.alert(text_window,
							{
								top:150, left:290, width:400, okLabel: "<?=__('Agregar')?>", buttonClass: "btn", className: "alphacube",
								id: "myDialogId",
								ok:function(win){
											if( $('form_tipo').tipo.value )
												var vurl = $('form_tipo').tipo.value
											else
												{
												for(var i=0;i<$('form_tipo').tipo.length;i++)
													{
														if( $('form_tipo').tipo[i].checked )
															var vurl = $('form_tipo').tipo[i].value;
													}
												}
											nuevaVentana('Modificar_Evento','550','550', vurl,'top=100,left=120');
										 return true;
								}
							}); 
         	}
    }
		/*scheduler.attachEvent("onEventCreated", function(event_id,event_object){
							alert( scheduler.getEvent( event_id ).start_date );
             var text_window = "<img src='<?=Conf::ImgDir()?>/tarea.gif'>&nbsp;&nbsp;<span style='font-size:15px; color:#FF0000; text-align:center;font-weight:bold'><u><?=__("Nuevo Evento")?></u><br><br>";
							text_window += '<span style="text-align:center; font-size:11px; color:#000; "><?=__('Agrega evento del tipo:')?>.</span><br>';
							text_window += '<br><table width="100%">';
							<?
							$query = "SELECT id_datos_calendario, url_modificacion, glosa_datos FROM datos_calendario WHERE monstrar_datos=1";
							$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
							 
							while( list( $id, $url, $glosa ) = mysql_fetch_array($resp) )
							{ ?>
								text_window += '<tr><td width="20%"></td><td width="80%" align="left"><input type="radio" name="tipo" id="tipo" value="<?=$url?>" /><?=$glosa?></td></tr>';
					<?	} ?>
							text_window += '</table>';
							Dialog.alert(text_window,
							{
								top:150, left:290, width:400, okLabel: "<?=__('Agregar')?>", buttonClass: "btn", className: "alphacube",
								id: "myDialogId",
								ok:function(win){
											nuevaVentana('Modificar_Evento','550','550', $('tipo').value,'top=100,left=120');
										 return true;
								}
							});
    });*/
		/*scheduler.attachEvent("onDblClick", function (event_id, native_event_object){
			var evs = scheduler.getEvent( event_id ); 
			var id = evs.tabla_id;
			
			<? 
			$cont=1;
			$query = "SELECT tabla_datos, url_modificacion FROM datos_calendario";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
			while( list($tipo,$url)=mysql_fetch_array($resp) )
			{ 
				if( $cont==1 )
				{
					echo " if( evs.type=='".$tipo."' ) var vurl = '".$url."'+id; ";
				}
				else
					echo " else if( evs.type=='".$tipo."' ) var vurl = '".$url."'+id; ";
					
					$cont++;
      }
      ?>
      if( vurl )
      nuevaVentana('Modificar_Evento','550','550', vurl,'top=100,left=120');
  	});*/

          
    scheduler.init('scheduler_here', new Date(),"month");
    scheduler._loaded = {};  
	}
</script>

<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
    

	$sesion = new Sesion( array() );
	$pagina = new Pagina($sesion);
	$pagina->titulo = "Bienvenido a ".Conf::AppName();

	$pagina->PrintTop();
	
	
       
        
	// Si existe 'ColumnaNotificacion' en Conf demonstra una ventana con noticias del sistema
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ColumnaNotificacion') != '' ) || method_exists('Conf','ColumnaNotificacion') )
	{
			if( method_exists('Conf','GetConf') ) 
				$columna_notificacion = Conf::GetConf($sesion, 'ColumnaNotificacion');
			else	
				$columna_notificacion = Conf::ColumnaNotificacion();
			// Cual es la ultima noticia..
			$query = "SELECT id_notificacion FROM notificacion ORDER BY fecha DESC LIMIT 1";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$resultado = mysql_fetch_array($resp);
			$notificacion1=$resultado[0];
			
			// Hasta que noticia ha leido el usuario...
			$query = "SELECT ".$columna_notificacion." FROM usuario WHERE id_usuario=".$sesion->usuario->fields['id_usuario'];
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$resultado = mysql_fetch_array($resp);
			$notificacion2=$resultado[0];
			
			// Si existen nuevas noticias cuales no ha visto el usuario demuenstralos 
			if( $notificacion1 != $notificacion2 && $not != true && $notificacion1 != '')
			{
		?>
		
		<script type="text/javascript">
		var text_window=new Array();
		<? 
		$query = "SELECT fecha, texto_notificacion FROM notificacion WHERE id_notificacion >".$notificacion2." ORDER BY id_notificacion";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$i=0;
		while( list($fecha,$texto_notificacion)=mysql_fetch_array($resp) )
		{
			// Sin estas lineas no se puede ingresar texto con mas que una linea
			$texto_notificacion=str_replace("\r\n","<br />&nbsp;&nbsp;",$texto_notificacion);
			$texto_notificacion=str_replace("\r","<br />&nbsp;&nbsp;",$texto_notificacion);
			$texto_notificacion=str_replace("\n","<br />&nbsp;&nbsp;",$texto_notificacion);
		?>
		var j=<?php echo $i  ;?>;
		// Navega entre las noticias con clicks en las imagenes del texto HTML...
		text_window[j] = "<div align=center style='bgColor:#66FF66;'><span style='font-size:12px; color:#FF0000; bgColor:#66FF66; text-align:center; font-weight:bold'><img style=cursor:pointer title='izquierda' src='<?php echo Conf::ImgDir() ;?>/logo_lemon.png' /><h1><u><?php echo __("Noticias") ;?></u></h1></span><br><a onclick=demonstrarNoticias(<?php echo $i  ;?>,'sub')><img style=cursor:pointer title='izquierda' src='<?php echo Conf::ImgDir() ;?>/izquierda.gif' /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><?php echo $i+1  ;?> / <?php echo $notificacion1 - $notificacion2  ;?></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick=demonstrarNoticias(<?php echo $i  ;?>,'add')><img style=cursor:pointer title='derecha' src='<?php echo Conf::ImgDir() ;?>/derecha.gif' /></a></div>";
		text_window[j] += '<table width=100%><tr><td align=left><b>[<?php echo Utiles::sql2fecha($fecha,'%d-%m-%Y') ;?>]</b></td></tr><tr><td align=left>&nbsp;&nbsp;<?php echo $texto_notificacion  ;?></td></tr></table><br /><a href=../../app/interfaces/noticias.php >Ver todas las noticias</a>';
		<?php		$i++;
		}
		$usu=$sesion->usuario->fields['id_usuario'];
		?>
		var cont_ventanas=0;
		var i=0;
			Dialog.alert(text_window[i],
					{
						minHeight:screen.height-500, maxHeight:screen.height-300, width:400, okLabel: "<?php echo __('Cerrar') ;?>",
						buttonClass: "btn", className: "alphacube", id: 'noticia0', destroyOnClose: true,
						ok:function(win){ 
								CambiaMensajeLeido( 0 ); 
									$('overlay_modal').style.visibility = 'hidden';
									$('overlay_modal').style.display = 'none';
									win.close();
									return false;
							}
					});
			
			
			function demonstrarNoticias(cont,opc)
			{
				var name='noticia'+cont_ventanas;
				
				if(opc=='add')
				{
					
					if(text_window[cont+1] != undefined)
					{
						cont_ventanas++;
					$(name).style.visibility = 'hidden';
							CambiaMensajeLeido( cont );
			
					Dialog.alert(text_window[cont+1],
						{
						minHeight:screen.height-500, maxHeight:screen.height-300, width:400, okLabel: "<?php echo __('Cerrar') ;?>",
						buttonClass: "btn", className: "alphacube", id: 'noticia'+cont_ventanas, destroyOnClose: true,
						ok:function(win){ 
							CambiaMensajeLeido( cont+1 );
									$('overlay_modal').style.visibility = 'hidden';
									$('overlay_modal').style.display = 'none';
									win.close();
									return false;
							}
						});
					}
					else
						{
							win.close();
							return false;
						}
				}
				else if(opc=='sub')
					{
						
						if(text_window[cont-1] != undefined)
						{
							CambiaMensajeLeido( cont );
							
							cont_ventanas++;
							$(name).style.visibility = 'hidden';
							Dialog.alert(text_window[cont-1],
							{
							minHeight:screen.height-500, maxHeight:screen.height-300, width:400, okLabel: "<?php echo __('Cerrar') ;?>",
							buttonClass: "btn", className: "alphacube", id: "noticia"+cont_ventanas, destroyOnClose: true,
							ok:function(win){ 
										CambiaMensajeLeido( cont );
										$('overlay_modal').style.visibility = 'hidden';
										$('overlay_modal').style.display = 'none';
										win.close();
										return false;
								}
							});
						}
						else
							{
							return false;
							}
							
					}
					return true;
				}
				
				// En caso que ha leido una nueva noticia nueva modificar la tabla, si necesario ( si no lo leio antes ).
				function CambiaMensajeLeido( con )
				{
					var http = getXMLHTTP();
								var url = root_dir + '/app/ajax.php?accion=actualizacion_usuario&id=<?php echo $usu  ;?>&id_not=<?php echo $notificacion2+1  ;?>&corr=' + con;
								cargando = true;
								http.open('get', url, true);
								cargando = false;
								http.send(null); 
				}
				
				 
		</script>
		<?php			}
}

	require_once dirname(__FILE__).'/../../app/templates/'.Conf::Templates().'/index.php';

	$pagina->PrintBottom();
?>

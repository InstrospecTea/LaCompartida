<?php
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	$sesion = new Sesion( array() );
        $rel='v ';
          if (file_exists(Conf::ServerDir().'/../app/version_svn.php') and $versionsvn=file_get_contents(Conf::ServerDir().'/../app/version_svn.php', NULL, NULL, 13,5)) $rel.=$versionsvn; 
          if (file_exists(Conf::ServerDir().'/../app/version_db.php') && include(Conf::ServerDir().'/../app/version_db.php'))  $rel.=' db'.$VERSION; 		
 
		
      
?>

<body  class="non_popup" rel="<?php echo htmlspecialchars($rel) ;?>">
<?php  if ( !Conf::GetConf($sesion,'ActualizacionTerminado') ) {
	echo "<h2>Estimado cliente, </h2>&nbsp;&nbsp;Estamos actualizando su sistema. El proceso de actualización se demora aproximadamente 10 a 15 minutos ...";
	echo "<br/><br/><img src='". Conf::ImgDir() ."/logo_lemon.png' />";
	exit; 
		} ?>
   

		<div  class="tb_facebook">
                    <div style="position:absolute;top:0px;left:50%;margin-left:-485px;width:485px;text-align:left;"><img src="<?php echo Conf::ImgDir()?>/logo_top.png" /></div>
			    <div style="position:absolute;top:0px;left:50%;width:485px;text-align:right;"><br/>
			    	<span style="color:#FFFFFF;">
			    		<span class="text_bold">Usuario</span>: 
			    			<?php echo $sesion->usuario->fields['nombre']?> <?php echo $sesion->usuario->fields['apellido1']?> <?php echo $sesion->usuario->fields['apellido2']?> | 
			    			<a style="color: white;" href="#" onClick="irIntranet('/fw/usuarios/index.php');">Inicio</a>
								<?php if ($_SESSION['ACTIVO_JUICIO'] && method_exists('Conf','HostJuicios') ){?> 
										| <a style="color: white;" href="<?php echo Conf::HostJuicios()?>" onClick="irIntranet('/fw/usuarios/index.php');">Gestión de Causas</a>
								<?php }?> 
                                                        | <a style="color: white;" href="http://lemontech.zendesk.com/home" target="_blank" >Soporte</a> 
							| <a href="#" style="color: white;" onClick="irIntranet('/fw/usuarios/logout.php?salir=true');">Salir</a></span></td>
			  </div>
                </div>
		
    <div style="display:block;width:980px;margin:10px auto;">
   <?php $rootlength = strlen( Conf::RootDir() ); 
		echo UtilesApp::PrintMenuDisenoNuevojQuery($this->sesion, substr($_SERVER['PHP_SELF'],$rootlength)) ;
	
    ?>
    </div>

    
     <div id="mainttb">
            
    <div class="titulo_sec"  >
			<h2><?php echo $this->titulo; ?></h2>
			<hr size="2" width="850" align="center" color="#a3d45c"/>
    </div>
 
    <div class="cont_tabla" >
			<?php 	if($this->num_infos > 0):	?>
			
			<table width="80%" class="info">
				<tr>
					<td valign="top" align="left" style="font-size: 12px;">
						<?php echo $this->GetInfos(); ?>
					</td>
				</tr>
			</table>
			
						<br/><br/>
			
			<?php endif;
                        if($this->num_errors > 0): 		?>
			
			<table width="80%" class="alerta">
				<tr>
					<td valign="top" align="left" style="font-size: 12px;">
						<strong>Se han encontrado los siguientes errores:</strong><br/>
						<?php echo $this->GetErrors(); ?>
					</td>
				</tr>
			</table>
						<br/><br/>
			
			<?php endif; 	?>

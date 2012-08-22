<?php
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	$sesion = new Sesion( array() );
  
      
    
?>

<body  class="non_popup"   <?php if(UtilesApp::GetConf($sesion, 'UsarOverlay')) echo 'title="overlay"'; ?>>
<?php  


if ( !UtilesApp::GetConf($sesion,'ActualizacionTerminado') ) {
	echo "<div style='display:block;margin:auto;text-align:center;'><h2>Estimado cliente, </h2>&nbsp;&nbsp;Estamos actualizando su sistema. El proceso de actualización se demora aproximadamente 10 a 15 minutos ...";
	 
	    
	      echo "<br/><br/><img src='https://estaticos.thetimebilling.com/images/logo_top_new_tt2_blanco.png' />";
	 if($_SESSION['RUT']='99511620') echo '<br><h3>&nbsp;&nbsp;&nbsp; <a href="'.Conf::RootDir().'/app/update.php?hash='.Conf::Hash().'"/>Update</a></h3>';
	exit; 
	  echo '</div>';
		}
		?>
   

		<div  class="tb_facebook">
                    <div style="position:absolute;top:0px;left:50%;margin-left:-485px;width:485px;text-align:left;"><a style="border:0 none;" href="<?php echo Conf::RootDir().'/app/usuarios/index.php';?>" style="border:0 none;text-decoration:none;">
			<?php 
			if(defined('ROOTDIR')&& ROOTDIR=='tt2'):
			    echo '<img  style="border:0 none;"  src="https://estaticos.thetimebilling.com/images/logo_top_new_tt2_blanco.png" />';
			    else:
			    echo '<img  style="border:0 none;" src="'. Conf::ImgDir().'/logo_top.png" rel="'.ROOTDIR.'"/>';
                            
                            echo '<script>';
                            echo "var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;";
                            echo "if (is_chrome && window.console) console.log('Es Chrome '+navigator.userAgent);";
                            echo '</script>';
			endif;
			?></a></div>
			    <div style="position:absolute;top:0px;left:50%;width:485px;text-align:right;"><br/>
			    	<span style="color:#FFFFFF;">
			    		<span class="text_bold">Usuario</span>: 
			    			<?php echo $sesion->usuario->fields['nombre']?> <?php echo $sesion->usuario->fields['apellido1']?> <?php echo $sesion->usuario->fields['apellido2']?> | 
			    			<a style="color: white;" href="#" onClick="irIntranet('/fw/usuarios/index.php');">Inicio</a>
								<?php if (isset($_SESSION['ACTIVO_JUICIO']) && method_exists('Conf','HostJuicios') ){?> 
										| <a style="color: white;" href="<?php echo Conf::HostJuicios()?>" onClick="irIntranet('/fw/usuarios/index.php');">Gestión de Causas</a>
								<?php }?> 
                                                        | <a style="color: white;" href="http://soporte.thetimebilling.com" target="_blank" >Soporte</a> 
							 <?php if (isset($_SESSION['switchuser'])) echo '| <a  style="color: white;" style="border:0 none;" href="'. Conf::RootDir().'/app/usuarios/index.php?endswitch=1">Volver a Modo Admin</a> '; ?>
							| <a href="#" style="color: white;" onClick="irIntranet('/fw/usuarios/logout.php?salir=true');">Salir</a></span></td>
			  </div>
                </div>
		
    <div style="display:block;width:980px;margin:10px auto;">
   <?php $rootlength = strlen( Conf::RootDir() ); 
		echo UtilesApp::PrintMenuDisenoNuevojQuery($this->sesion, substr($_SERVER['PHP_SELF'],$rootlength)) ;
	
    ?>
    </div>

    
     <div id="mainttb" style="padding: 30px 0 5px ;width:960px;position:relative;left:-10px; ">
            
    <div class="titulo_sec"  >
    <?php 
    if (UtilesApp::GetConf($sesion, 'BeaconTimer')) {
        $beaconleft=UtilesApp::GetConf($sesion, 'BeaconTimer')-time();
        if ($beaconleft<0) {
           //echo 'Versi&oacute;n expirada del software';  
        } else {
            echo '<!--Beaconleft:'.$beaconleft.'-->';
        }
    }
        ?>
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
			
			<?php endif; 	 
			
		

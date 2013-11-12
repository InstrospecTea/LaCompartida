<?php 	
require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';

	$sesion = new Sesion( array('ADM') );
	$pagina = new Pagina($sesion);
	$pagina->titulo = __('Administraci�n de Usuarios') ;

	if ($opc=="eliminado")
		$pagina->AddInfo(__('Usuario').' '.__('eliminado con �xito'));

	if($desde == "")
			$desde = 0;
	if($x_pag == "")
			$x_pag = 20;
	if($orden == '')
			$orden = 'apellido1';

	//Opci�n cancelar
	if($opc == 'cancelar')
	{
		$pagina->Redirect('usuario_paso1.php');
	}
	//Opci�n editar
	else if($opc == 'edit')
	{
		if($cambiar_alerta_diaria=='on')
		{
			if($alerta_diaria=='on') $alerta_diaria=1; else $alerta_diaria=0;
			if($retraso_max=='') $retraso_max=0;
		}
		else
		{
			$alerta_diaria='alerta_diaria';
			$retraso_max='retraso_max';
		}
		if($cambiar_restriccion_diario=='on')
		{
			if($restriccion_diario=='')
				$restriccion_diario=0;
		}
		else
		{
			$restriccion_diario='restriccion_diario';
		}
		if($cambiar_alerta_semanal=='on')
		{
			if($alerta_semanal=='on')
				$alerta_semanal=1;
			else
				$alerta_semanal=0;
			if($restriccion_max=='')
				$restriccion_max=0;
			if($restriccion_min=='')
				$restriccion_min=0;
		}
		else
		{
			$alerta_semanal='alerta_semanal';
			$restriccion_max='restriccion_max';
			$restriccion_min='restriccion_min';
		}
		if($cambiar_restriccion_mensual=='on')
		{
			if($restriccion_mensual=='')
				$restriccion_mensual=120;
		}
		else
		{
			$restriccion_mensual='restriccion_mensual';
		}
		if($cambiar_dias_ingreso_trabajo=='on')
		{
			if($dias_ingreso_trabajo=='')
				$dias_ingreso_trabajo=7;
		}
		else
		{
			$dias_ingreso_trabajo='dias_ingreso_trabajo';
		}

		//Actualizar alerta de Usuario
		$query3 = "UPDATE usuario SET alerta_diaria=".$alerta_diaria.", alerta_semanal=".$alerta_semanal.", retraso_max=".$retraso_max.",
					restriccion_max=".$restriccion_max.", restriccion_min=".$restriccion_min.", restriccion_mensual=".$restriccion_mensual.",
					dias_ingreso_trabajo=".$dias_ingreso_trabajo.", restriccion_diario=".$restriccion_diario;
		$resp3 = mysql_query($query3,$sesion->dbh) or Utiles::errorSQL($query3,__FILE__,__LINE__,$sesion->dbh);

		//Insert para el registro de cambios Restricciones y alertas generales
		$nuevos = 'Alerta diaria: '.$alerta_diaria.',';
		$nuevos .= 'Retraso max. diaria (HH): '.$retraso_max.',';
		$nuevos .= 'Restricci�n min. diaria (HH): '.$restriccion_diario.',';
		$nuevos .= 'Alerta semanal: '.$alerta_semanal.',';
		$nuevos .= 'Restricci�n min. semanal (HH): '.$restriccion_min.',';
		$nuevos .= 'Restricci�n max. semanal (HH): '.$restriccion_max.',';
		$nuevos .= 'Restricci�n min. mensual (Hrs): '.$restriccion_mensual.',';
		$nuevos .= 'Plazo max. (d�as) para ingreso de trabajos: '.$dias_ingreso_trabajo;
		$query4 = "INSERT INTO usuario_cambio_historial (id_usuario,id_usuario_creador,nombre_dato,valor_original,valor_actual,fecha)";
		$query4 .= " VALUES(NULL,'".$sesion->usuario->fields['id_usuario']."','Restricciones y alertas generales',NULL,'".$nuevos."',NOW())";
		$resp = mysql_query($query4, $sesion->dbh) or Utiles::errorSQL($query4,__FILE__,__LINE__,$sesion->dbh);

		if($alerta_diaria==0)
			$alerta_diaria='';
		if($alerta_semanal==0)
			$alerta_semanal='';
	}

	$pagina->PrintTop();
	$tooltip_text = __('Para agregar un nuevo usuario ingresa su '.UtilesApp::GetConf($sesion,'NombreIdentificador').' aqu�.');
?>
    <style type="text/css">
      @import "https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css";
  #tablapermiso {border-spacing:0;border-collapse:collapse;}
	  #tablapermiso th {font-size:10px;}
  .dataTables_paginate {clear: both; margin: -20px 350px 15px 0;width:370px;vertical-align:middle;}
  .dttnombres, .dttactivo {text-align:left;font-size:10px;white-space: nowrap;}
 .dttpermisos .DataTables_sort_icon, .dttactivo .DataTables_sort_icon {display:none;}
 .activo, .usuarioinactivo, .usuarioactivo {float:left;display:inline;}
 .inactivo {opacity:0.4;}
 .inactivo td {background:#F0F0F0;}
 /*th.dttpermisos {-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);}*/
 #contienefiltro {display:inline-block;margin-bottom:-8px;}
 #tablapermiso_paginate .fg-button {padding:0 5px;}
 #tablapermiso_paginate .first, #tablapermiso_paginate .last {display:none;}
#tablapermiso  tbody tr.odd {background-color: #fff !important;}
#tablapermiso  tbody tr.even {background-color: #EFE !important;}
td.sorting_1 {background:transparent !important;}
    </style>
   <script  src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js"></script>

<script type="text/javascript">
    jQuery(document).ready(function() {
		

 
	  var oTable=jQuery('#tablapermiso').dataTable( {
		
		 
	  "bJQueryUI": true,
		     "bDeferRender": true,
"bDestroy":true,
               
		 		"oLanguage": {   
		    "sProcessing":   "Procesando..." ,
		    "sLengthMenu":   "Mostrar _MENU_ registros",
		    "sZeroRecords":  "No se encontraron resultados",
		    "sInfo":         "Mostrando desde _START_ hasta _END_ de _TOTAL_ registros",
		    "sInfoEmpty":    "Mostrando desde 0 hasta 0 de 0 registros",
		    "sInfoFiltered": "(filtrado de _MAX_ registros en total)",
		    "sInfoPostFix":  "",
		    "sSearch":       "<b>Buscar Nombre</b>",
		    "sUrl":          "",
		    "oPaginate": {
			 
			"sPrevious": "anterior",
			"sNext":     "siguiente"
			 
		 }
	 },
	 "bFilter": true,
	 		 "aoColumns": [
				 	{ "mDataProp": "rut" }, 
				 	{ "mDataProp": "id_usuario" }, 
			{ "mDataProp": "nombrecompleto" }, 
			 
				{ "mDataProp": "ADM" },
				{ "mDataProp": "DAT" },
			
			     { "mDataProp": "COB" },
				
				{ "mDataProp": "EDI" },
				{ "mDataProp": "LEE" },
				{ "mDataProp": "OFI" },
				{ "mDataProp": "PRO" },
				{ "mDataProp": "REP" },
				{ "mDataProp": "REV" },
				{ "mDataProp": "SEC" },
				{ "mDataProp": "SOC" },
				{ "mDataProp": "TAR" },
				{ "mDataProp": "RET" },
				{ "mDataProp": "ACT" } 
	 
		],   
		   "aoColumnDefs": [
			    { "sClass": "dttnombres", "aTargets": [ 2  ] },
		 
		
	{  "fnRender": function ( o, val ) {
				var botones='';
				botones+= "<div style='float:left;display:inline;' id='"+o.aData['id_usuario']+';'+o.mDataProp+"'>";
				if(val==1) {
					botones+="<input class='permiso usuarioactivo' type='image' src='https://static.thetimebilling.com/images/lightbulb.png' alt='ACTIVO' title='Usuario Activo'";
				} else {
    			
				 botones+= "<input class='permiso usuarioinactivo' type='image' src='https://static.thetimebilling.com/images/lightbulb_off.png' alt='INACTIVO' title='Usuario Inactivo'";
				}
			   		return botones+" rel='"+o.aData['id_usuario']+';'+o.mDataProp+"'/></div>&nbsp;<a style='display:inline;position: relative;top: 0;right: 0;' href='usuario_paso2.php?rut="+o.aData['rut']+"' title='Editar usuario'><img border=0 src='https://static.thetimebilling.com/images/ver_persona_nuevo.gif' alt='Editar' /></a>";
	},   "sClass": "dttactivo", "bUseRendered": false, "aTargets": [ 16 ]   }  ,
	{  "fnRender": function ( o, val ) {
			   	if(val==1) {
					return "<div class='permiso on' id='"+o.aData['id_usuario']+';'+o.mDataProp+"'><input class='permiso' type='image' src='https://static.thetimebilling.com/images/check_nuevo.gif' alt='OK' rel='"+o.aData['id_usuario']+';'+o.mDataProp+"'/></div>";
				} else {
    				return "<div class='permiso off' id='"+o.aData['id_usuario']+';'+o.mDataProp+"'><input class='permiso'  type='image' src='https://static.thetimebilling.com/images/cruz_roja_nuevo.gif' rel='"+o.aData['id_usuario']+';'+o.mDataProp+"' alt='NO' /></div>";
				}
	}, "bUseRendered": false, "sClass": "dttpermisos",  "aTargets": [3,4,5,6,7,8,9,10,11,12,13,14,15   ]   }  ,
	 { "bVisible": false, "aTargets": [ 0,1 ] }
	
    
    ],
	"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
        if ( aData['ACT'] == "0" )        jQuery(nRow).addClass('inactivo').attr('title','Usuario Inactivo');
         },
	  "sAjaxSource": "../interfaces/ajax/usuarios_ajax.php",
		 "iDisplayLength": 25,
		       "sDom":  '<"top"flp>t<"bottom"i>',
	    "aLengthMenu": [[25, 50, 100,200, -1], [25, 50, 100,200, "Todo"]],
	    "sPaginationType": "full_numbers",
	   
	    "aaSorting": [[ 2, "asc" ]]
		
	     });
		  jQuery('#contienefiltro').append(jQuery('#tablapermiso_filter'));
		 /*jQuery('#tablapermiso_filter').append(' S&oacute;lo activos');*/
		 
		 oTable.fnFilter( '1',16 );
	 	
		 jQuery('#activo').click(function() {
			if(jQuery(this).is(':checked')) {
				 oTable.fnFilter( '1',16,0,1 );
			} else {
				 oTable.fnFilter('',16,0,1  );
			}
		 });
jQuery('.permiso').live('click',function() {
   var Objeto=jQuery(this);
   var Dato=jQuery(this).attr('rel').split(';');
  
   var Act=jQuery(this).attr('alt');
  
   var Accion='';
    if(Act=='OK') {
	Accion='revocar';
	jQuery(this).attr('alt','NO');
    } else if (Act=='NO') {
	Accion='conceder';
	jQuery(this).attr('alt','OK');
	
    } else if (Act=='ACTIVO') {
		Accion='desactivar';
	jQuery(this).attr('alt','INACTIVO');
	jQuery(this).closest('tr').addClass('inactivo');
	} else {
		Accion='activar';
	jQuery(this).attr('alt','ACTIVO');
	jQuery(this).closest('tr').removeClass('inactivo');
	}
     jQuery.post('../interfaces/ajax/permiso_ajax.php',{accion:Accion,userid:Dato[0], permiso:Dato[1]},function(data) {
		
		Objeto.attr('src',data);
		 
	    });
            Objeto.attr('src','https://static.thetimebilling.com/images/ico_loading.gif');
	    return false;
  
});

	
	    jQuery('.descargaxls').click(function() {
		var activo=0;
		if(jQuery('#activo').is(':checked')) activo=1;

		var tipoxls=jQuery(this).attr('rel');

		nom=jQuery('#nombre').val();
		if(tipoxls == 'xls')
		{
			destino = '../interfaces/usuarios_xls.php?act='+activo+'&nombre='+nom;
		}
		else if(tipoxls == 'xls_vacacion')
		{
			destino = '../interfaces/usuarios_xls.php?act='+activo+'&nombre='+nom+'&vacacion=true';
		}
		else if(tipoxls == 'xls_modificaciones')
		{
			destino = '../interfaces/usuarios_xls.php?act='+activo+'&nombre='+nom+'&modificaciones=true';
		}
		top.window.location.href=destino;
		//if (console!==undefined) console.log(destino);
	    });
 	    
	     jQuery('#costos').click(function(){

		 var theform=jQuery(this).parents('form:first');
		 console.log('submit 04');
		  theform.submit();
	     });
	     jQuery('#btnbuscar').click(function(){

	     	console.log('submit 05');
		 jQuery(this).parents('form:first').attr('action','usuario_paso1.php?buscar=1').submit();

	     });
        });
 

function RevisarRut( form )
{
	if (! asegurarIngreso() ) {
		return false;
	} else {
		return true;
	}	
	//if( Rut(form.rut.value, form.dv_rut.value ) )
		
	//alert( 'El rut es inv�lido' );
	//return false;
}

function Listar( form, from )
{
	var nom=document.act.nombre.value;
	var activo = 0;
	if($('activo').checked==true)
		activo = 1;

	if(from == 'buscar')
		form.action = 'usuario_paso1.php?buscar=1';
	else if(from == 'xls')
	{
		form.action = '../interfaces/usuarios_xls.php?act='+activo+'&nombre=nom';
	}
	else if(from == 'xls_vacacion')
	{
		form.action = '../interfaces/usuarios_xls.php?act='+activo+'&nombre=nom&vacacion=true';
	}
	else if(from == 'xls_modificaciones')
	{
		form.action = '../interfaces/usuarios_xls.php?act='+activo+'&nombre=nom&modificaciones=true';
	}
	else
		return false;
	//alert(form.action);
	console.log('submit 01');
	form.submit();
	//return true;
}

function ModificaTodos( from )
{
if( from.cambiar_alerta_diaria.checked==true)
	{
	var alerta_diaria = from.alerta_diaria.checked;
	if( alerta_diaria == true ) alerta_diaria='\n Alerta diaria:          SI'; else alerta_diaria='\n Alerta diaria:          NO';
	var retraso_max = '\n Restraso Max:        '
	}
else
	{
	var alerta_diaria = '';
	var retraso_max = '';
	}
if( from.cambiar_alerta_semanal.checked==true)
	{
	var alerta_semanal = from.alerta_semanal.checked;
	if( alerta_semanal == true ) alerta_semanal='\n Alerta semanal:     SI'; else alerta_semanal='\n Alerta semanal:     NO';
	var restriccion_min = '\n Min HH:                 '
	var restriccion_max = '\n Max HH:                 '
	}
else
	{
	var alerta_semanal = '';
	var restriccion_min = '';
	var restriccion_max = '';
	}
if( from.cambiar_restriccion_mensual.checked==true)
	{
	var restriccion_mensual = '\n Min HH mensual: '
	}
else
	{
	var restriccion_mensual = '';
	}
if( from.cambiar_dias_ingreso_trabajo.checked==true)
	{
	var dias_ingreso_trabajo = '\n Max dias ingreso:  '
	}
else
	{
	var dias_ingreso_trabajo = '';
	}

if(confirm( alerta_diaria + alerta_semanal + retraso_max + from.retraso_max.value + restriccion_min + from.restriccion_min.value + restriccion_max + from.restriccion_max.value + restriccion_mensual + from.restriccion_mensual.value + dias_ingreso_trabajo + from.dias_ingreso_trabajo.value + '\n\n �Desea cambiar los restricciones y alertas de todos los usuarios?' ))
{
from.action="usuario_paso1.php";
console.log('submit 02');
from.submit();
}
}
function Cancelar(form)
{
	form.opc.value = 'cancelar';
	console.log('submit 03');
	form.submit();
}

function DisableColumna( from, valor, text)
{
	if(text == 'alerta_diaria')
	{
		var Input1 = $('alerta_diaria');
		var Input2 = $('retraso_max');
			var check = $(valor);

		if(check.checked)
		{
			Input1.disabled= false;
			Input2.disabled= false;
			Input2.style.background="#FFFFFF";
		}
		else
		{
			Input1.checked = false;
			Input1.disabled = true;
			Input2.value = '';
			Input2.disabled = true;
			Input2.style.background="#EEEEEE";
		}
	}
	else if(text == 'alerta_semanal')
	{
		var Input1 = $('alerta_semanal');
		var Input2 = $('restriccion_min');
		var Input3 = $('restriccion_max');
			var check = $(valor);

		if(check.checked)
		{
			Input1.disabled= false;
			Input2.disabled= false;
			Input3.disabled= false;
			Input2.style.background="#FFFFFF";
			Input3.style.background="#FFFFFF";
		}
		else
		{
			Input1.checked = false;
			Input1.disabled = true;
			Input2.value = '';
			Input2.disabled = true;
			Input3.value = '';
			Input3.disabled = true;
			Input2.style.background="#EEEEEE";
			Input3.style.background="#EEEEEE";
		}
	}
	if(text == 'alerta_mensual')
	{
		var Input1 = $('restriccion_mensual');
			var check = $(valor);

		if(check.checked)
		{
			Input1.disabled= false;
			Input1.style.background="#FFFFFF";
		}
		else
		{
			Input1.value = '';
			Input1.disabled = true;
			Input1.style.background="#EEEEEE"
		}
	}
	if(text == 'dias_ingreso')
	{
		var Input1 = $('dias_ingreso_trabajo');
			var check = $(valor);

		if(check.checked)
		{
			Input1.disabled= false;
			Input1.style.background="#FFFFFF";
		}
		else
		{
			Input1.value = '';
			Input1.disabled = true;
			Input1.style.background="#EEEEEE";
		}
	}
	if(text == 'restriccion_diario')
	{
		var Input1 = $('restriccion_diario');
		var check = $(valor);

		if(check.checked)
		{
			Input1.disabled= false;
			Input1.style.background="#FFFFFF";
		}
		else
		{
			Input1.value = '';
			Input1.disabled = true;
			Input1.style.background="#EEEEEE";
		}
	}
}

function asegurarIngreso(p) 
{
	var valRut = document.getElementById('rut');

	if (valRut.value == '') {
		alert('El campo rut no puede ser vac�o.');
		return false;
	} else {
		return true;
	}

}


</script>
<table width="96%" align="left">
	<tr>
		<td width="20">&nbsp;</td>
		<td valign="top">

<?php   if( strtolower(UtilesApp::GetConf($sesion,'NombreIdentificador'))=='rut' )	{ ?>
			<form action="usuario_paso2.php" method="post" onsubmit="return RevisarRut(this);">
<?php  }
	else
		{ ?>
			<form action="usuario_paso2.php" method="post">
<?php  } ?>
</table>
<br class="clearfix"/>
<br>
<table  width="100%" class="tb_base">
	<tr>
		<td valign="top" class="subtitulo" align="left" colspan="2">
			<?php echo __('Ingrese ') . UtilesApp::GetConf($sesion,'NombreIdentificador') . __(' del usuario')?>:
			<hr class="subtitulo_linea_plomo"/>
		</td>
	</tr>
	<tr>
		<td valign="top" class="texto" align="right">
			<strong><?php echo  UtilesApp::GetConf($sesion,'NombreIdentificador'); ?></strong>
		</td>
		<td valign="top" class="texto" align="left">
			<?php   if( strtolower(UtilesApp::GetConf($sesion,'NombreIdentificador'))=='rut' ) { ?>
				<input type="text" id="rut" name="rut" value="" size="10" onMouseover="ddrivetip('<?php $tooltip_text?>')" onMouseout="hideddrivetip()" />-<input type="text" name="dv_rut" value="" maxlength=1 size="1" />
			<?php } else { ?>
				<input type="text" id="rut" name="rut" value="" size="17" onMouseover="ddrivetip('<?php echo $tooltip_text?>')" onMouseout="hideddrivetip()" />
			<?php } ?>
				&nbsp;
			<?php 			if( $sesion->usuario->fields['id_visitante'] == 0 )
				echo "&nbsp;&nbsp;<input type=\"submit\" class='botonizame' name=\"boton\" value=\"".__('Aceptar')."\" onclick=\"\"/>";
			else
				echo "&nbsp;&nbsp;<input type=\"button\" class='botonizame' name=\"boton\" value=\"".__('Aceptar')."\" onclick=\"alert('Usted no tiene derecho para agegar un usuario nuevo');\" />";
			?>
		</td>
	</tr>
	 
</table>
<br class="clearfix"/>
<table width=100% class="tb_base">
</form>
				</td>
		</tr>

	<tr><td></td>
		<td>
			<table width=100% class="tb_base">
				<tr>
					<td>
				<table width=100%>
					<tr>
							<td valign="top" class="subtitulo" align="left" colspan="2">
							<?php echo __('Modificacion de Datos para todos los usuarios ')?>:
							<hr class="subtitulo_linea_plomo"/>
							</td>
						</tr>
				</table>
		</td>
	</tr>

	<tr><td>
		<form name="form_usuario" method="post" enctype="multipart/form-data">
			 <input type="hidden" name="opc" value="edit" />
	<fieldset class="table_blanco">
		<legend><?php echo __('Restricciones y alertas')?></legend>
		<table width=100%>
		<tr>
			<td width=38% align="right"></td>
			<td width=10% align="right"></td>
			<td width=15% align="left"></td>
			<td width=20% align="right"></td>
			<td width=17% align="center">
				Cambiar valores
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="alerta_diaria"><?php echo __('Alerta Diaria')?></label> <input type="checkbox" id="alerta_diaria" name="alerta_diaria" <?php echo $cambiar_alerta_diaria=='on' ? '' : disabled ?> <?php echo $alerta_diaria!='' ? "checked" : "" ?> />
			</td>
			<td align="right">
				<?php echo __('Retraso max.')?>
			</td>
			<td align="left">
				<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $retraso_max > 0 ? $retraso_max : '' ?>" id="retraso_max" name="retraso_max" <?php echo $cambiar_alerta_diaria=='on' ? '' : disabled ?> />
			</td>
			<td align="left"></td>
			<td align="center">
				<input type="checkbox" id="cambiar_alerta_diaria" name="cambiar_alerta_diaria" <?php echo $cambiar_alerta_diaria!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'alerta_diaria');"/>
			</td>
		</tr>
		<tr>
			<td align="right">
				&nbsp;
			</td>
			<td align="right">
				<?php echo __('Min HH.')?>
			</td>
			<td align="left">
				<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_diario > 0 ? $restriccion_diario : '' ?>" id="restriccion_diario" name="restriccion_diario" <?php echo $cambiar_restriccion_diario =='on' ? '' : disabled ?> />
			</td>
			<td align="left"></td>
			<td align="center">
				<input type="checkbox" id="cambiar_restriccion_diario" name="cambiar_restriccion_diario" <?php echo $cambiar_restriccion_diario!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'restriccion_diario');"/>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="alerta_semanal"><?php echo __('Alerta Semanal')?></label> <input type="checkbox" id="alerta_semanal" name="alerta_semanal" <?php echo $cambiar_alerta_semanal=='on' ? '' : disabled ?> <?php echo $alerta_semanal!='' ? "checked" : "" ?> />
			</td>
			<td align="right">
				<?php echo __('M�n. HH')?>
			</td>
			<td align="left">
				<input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_min > 0 ? $restriccion_min : '' ?>" id="restriccion_min" name="restriccion_min" <?php echo $cambiar_alerta_semanal=='on' ? '' : disabled ?> />
			</td>
			<td align="left">
				<?php echo __('M�x. HH')?> <input type="text" style="background-color: #EEEEEE;" size=10 value="<?php echo $restriccion_max > 0 ? $restriccion_max : '' ?>" id="restriccion_max" name="restriccion_max" <?php echo $cambiar_alerta_semanal=='on' ? '' : disabled ?> />
			</td>
			<td align="center">
				<input type="checkbox" id="cambiar_alerta_semanal" name="cambiar_alerta_semanal" <?php echo $cambiar_alerta_semanal!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'alerta_semanal');"/>
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="restriccion_mensual"><?php echo __('M�nimo mensual de horas')?></label>
			</td>
			<td>&nbsp;</td>
			<td align="left">
				<input type="text" size="10" style="background-color: #EEEEEE;" <?php echo Html::Tooltip("Para no recibir alertas mensuales ingrese 0.")?> value="<?php echo $restriccion_mensual > 0 ? $restriccion_mensual : '' ?>" id="restriccion_mensual" name="restriccion_mensual" <?php echo $cambiar_restriccion_mensual=='on' ? '' : disabled ?> />
			</td>
			<td align="left"></td>
			<td align="center">
				<input type="checkbox" id="cambiar_restriccion_mensual" name="cambiar_restriccion_mensual" <?php echo $cambiar_restriccion_mensual!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'alerta_mensual');" />
			</td>
		</tr>
		<tr>
			<td align="right">
				<label for="dias_ingreso_trabajo"><?php echo __('Plazo m�ximo (en d�as) para ingreso de trabajos')?></label>
			</td>
			<td>&nbsp;</td>
			<td align="left">
				<input type="text" size="10" style="background-color: #EEEEEE;" value="<?php echo $dias_ingreso_trabajo > 0 ? $dias_ingreso_trabajo : '' ?>" id="dias_ingreso_trabajo" name="dias_ingreso_trabajo" <?php echo $cambiar_dias_ingreso_trabajo=='on' ? '' : disabled ?> />
			</td>
			<td align="left"></td>
			<td align="center">
				<input type="checkbox" id="cambiar_dias_ingreso_trabajo" name="cambiar_dias_ingreso_trabajo" <?php echo $cambiar_dias_ingreso_trabajo!='' ? "checked" : "" ?> onclick="DisableColumna(this.form,this, 'dias_ingreso');"/>
			</td>
		</tr>

		</table>
		</fieldset>
		<fieldset class="table_blanco">
			<legend><?php echo __('Guardar datos')?></legend>
			<table width=100%>
			<tr><td align="center">
			<?php if( $sesion->usuario->fields['id_visitante'] == 0 )
						echo "<input type=\"button\" value=\"".__('Guardar')."\" class='botonizame' onclick=\"ModificaTodos(this.form);\"  /> &nbsp;&nbsp;";
				 else
				 		echo "<input type=\"button\" value=\"".__('Guardar')."\" class='botonizame' onclick=\"alert('Usted no tiene derecho para modificar estos valores.');\" /> &nbsp;&nbsp;";
			?>
			<input type="button" value="<?php echo __('Cancelar')?>" onclick="Cancelar(this.form);" class='botonizame' />
		</td></tr>
		</table>
		</fieldset>
   </form>
 </td>
</tr>
</table>
<br/>
</td>
</tr>

		<tr><td></td><td>
		<table width=100% class="tb_base">
		<tr>
				<td valign="top" class="subtitulo" align="left" colspan="2">
						<?php echo __('Lista de Usuarios')?>:
						<hr class="subtitulo_linea_plomo"/>
				</td>
		</tr>
		
		<tr>
				<td valign="top" align="left" colspan="2"><img src="https://files.thetimebilling.com/templates/default/img/pix.gif" border="0" width="1" height="10"></td>
		</tr>
		<tr>
				<td valign="top" align="center" style="white-space:nowrap">
			<form name="act"  method="post">
					  	 
						
						<input type="checkbox" name="activo"  id="activo" <?php if(!$activo) echo 'value="1" checked="checked"'; ?> />s&oacute;lo activos &nbsp;&nbsp;&nbsp;
						<span id="contienefiltro"></span>
						&nbsp;&nbsp; 
						
						&nbsp; <a href="#" id="btnbuscar" style="display:none;" class="u1 botonizame"  icon="ui-icon-search" rel="buscar">Buscar</a>
						
						&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel"   rel="xls">Descargar Listado</a>
						&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel" rel="xls_vacacion">Descargar Vacaciones</a>
						&nbsp; <a href="#" class="u1 descargaxls botonizame" icon="ui-icon-excel" rel="xls_modificaciones">Descargar Modificaciones</a>
					
						
			</form>
				    
				</td>
		</tr>
		<tr>
				<td colspan=2>
				<br /><?php echo __('Para buscar ingrese el nombre del usuario o parte de �l.')?>
				</td>
		</tr>
		<tr>
				<td colspan=2>
		<br />
<table id="tablapermiso">
	<thead><tr><td class="encabezado">RUT</td>
		<th>ID</th>
				<th>Nombre</th>
			
				<th>Admin</th>
				<th>Admin<br>Datos</th><th width="23">Cobranza</th>
					<th>Editar<br/>Biblioteca</th>
				
				
				
				<th>Lectura</th>
				<th>Oficina</th>
				<th>Profesional</th>
				<th>Reportes</th>
				<th>Revisi�n</th>
				<th>Secretar�a</th>
				<th>Socio</th>
				<th>Tarifa</th>
				<th>Retribuciones</th>
				<th width="25">Activo</th>
			 
		</tr></thead>
	<tbody></tbody>
		
	
	
	
</table>
				</td>
		</tr>
</table>

		<tr><td colspan="2">&nbsp;</td></tr>

<?php if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ReportesAvanzados') ) || ( method_exists('Conf','ReportesAvanzados') && Conf::ReportesAvanzados() ) )
		{ ?>
		<tr><td></td><td>
		<table width=100%>
		<tr>
				<td valign="top" class="subtitulo" align="left" colspan="2">
						<?php echo __('Costo por usuario')?>:
						<hr class="subtitulo"/>
				</td>
		</tr>
		<tr>
				<td colspan=2>
				<a  id="costos"   class="botonizame"  icon="ui-icon-search" style="margin:auto;width:200px;" href="<?php echo Conf::RootDir().'/app/interfaces/costos.php' ?>"  ><?php echo __('Editar costo mensual por usuario') ?></a>
				</td>
		</tr>
		</table>
<?php } ?>
		<tr><td colspan="2">&nbsp;</td></tr>
</table>


<?php 	function Opciones(& $fila)
	{
			global $sesion;
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) )
				{
					return "<a href=usuario_paso2.php?rut=".$fila->fields[rut]." title='Editar usuario'><img border=0 src=".Conf::ImgDir()."/ver_persona_nuevo.gif alt='Editar' /></a>";
				}
			else
				{
					return "<a href=usuario_paso2.php?rut=".$fila->fields[rut]." title='Editar usuario'><img border=0 src=".Conf::ImgDir()."/ver_persona.gif alt='Editar' /></a>";
				}
	}
	function PrintCheck(& $fila)
	{
		global $sesion, $permisos;
		static $i = 0;

		if($i == $permisos->num)
			$i = 0;
		$permiso = $permisos->Get($i);
		$permiso = $permiso->fields[codigo_permiso];

		$query = "SELECT COUNT(*) FROM usuario_permiso WHERE id_usuario='".$fila->fields['id_usuario']."' AND codigo_permiso='$permiso'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$i++;
				list($count) = mysql_fetch_array($resp);
		if ( UtilesApp::GetConf($sesion,'UsaDisenoNuevo') )	{
				if($count > 0)
					return "<div class='permiso on' id='". $fila->fields['id_usuario'].';'.$permiso."'><input class='permiso' type='image' src='".Conf::ImgDir()."/check_nuevo.gif' alt='OK' rel='". $fila->fields['id_usuario'].';'.$permiso."'/></div>";
				else
					return "<div class='permiso off' id='". $fila->fields['id_usuario'].';'.$permiso."'><input class='permiso'  type='image' src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' rel='". $fila->fields['id_usuario'].';'.$permiso."' alt='NO' /></div>";
			}
		else
			{
				if($count > 0)
					return "<img src=".Conf::ImgDir()."/check.gif alt='OK' />";
				else
					return "<img src=".Conf::ImgDir()."/cruz_roja.gif alt='NO' />";
			}
	}
	function PrintActivo(& $fila)
	{
		global $sesion;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) )
			{
				if($fila->fields[activo])
					return "<img src=".Conf::ImgDir()."/check_nuevo.gif alt='OK' />";
				else
					return "<img src=".Conf::ImgDir()."/cruz_roja_nuevo.gif alt='NO' />";
			}
		else
			{
				if($fila->fields[activo])
					return "<img src=".Conf::ImgDir()."/check.gif alt='OK' />";
				else
					return "<img src=".Conf::ImgDir()."/cruz_roja.gif alt='NO' />";
			}
	}
	$pagina->PrintBottom();
?>

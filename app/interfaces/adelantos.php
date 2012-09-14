<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
 	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Documento.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
 

	$sesion = new Sesion(array('OFI','COB','SEC'));	
	$pagina = new Pagina($sesion);
	$documento = new Documento($sesion);
	$cliente = new Cliente($sesion);

	$pagina->titulo = __('Revisar Adelantos');

	//Filtros
	$filtros = array('id_documento' => $id_documento, 'codigo_cliente' => $codigo_cliente, 'fecha_inicio' => $fecha1, 'fecha_fin' => $fecha2, 'moneda' => $moneda_adelanto, 'tiene_saldo' => $tiene_saldo);

	if ($opc == "eliminar" and !empty($id_documento_e))
	{
		$sql = "DELETE FROM documento WHERE id_documento = " . mysql_real_escape_string($id_documento_e) . " AND es_adelanto = 1 AND monto = saldo_pago";
		$query = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $sesion->dbh);
		if (mysql_affected_rows($sesion->dbh) > 0)
		{
			$pagina->AddInfo(__('Adelanto') . ' ' . __('eliminado con éxito'));
		}
	}

	$pagina->PrintTop();
	
	$codigo_cliente = empty($codigo_cliente) && $codigo_cliente_secundario ? $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario) : $codigo_cliente;
	
	$params_array['codigo_permiso'] = 'COB';
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	

	
	
		
?>
<style type="text/css">
    @import "https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/css/jquery.dataTables.css";
 #tablon {border-spacing:0;border-collapse:collapse;}
	  #tablon th {font-size:10px;}
  .dataTables_paginate {clear: both; margin: -20px 350px 15px 0;width:390px;vertical-align:middle;}
  .dttnombres, .dttactivo {text-align:left;font-size:10px;white-space: nowrap;}
.dataTables_paginate .last, dataTables_paginate .first,  .DataTables_sort_icon {display:none;}
 .activo, .usuarioinactivo, .usuarioactivo {float:left;display:inline;}
 .inactivo {opacity:0.4;}
 .inactivo td {background:#F0F0F0;}
 /*th.dttpermisos {-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);}*/
 #contienefiltro {display:inline-block;margin-bottom:-8px;}
 #tablon_paginate .fg-button {padding:0 5px;}
 #tablon_paginate .first, #tablon_paginate .last {display:none;}
#tablon  tbody tr.odd {background-color: #fff !important;}
#tablon  tbody tr.even {background-color: #EFE !important;}
td.sorting_1 {background:transparent !important;}
.tipodescripcion {font-size:8pt;clear:left;overflow:visible;position:relative;margin:3px 0;}
.marginleft {margin-left: 3px;}
.eligegasto {float:left;margin-top: 0 !important;}
#totalcta {float: left;position: relative;top: 15px;}
#losadelantos {margin-top:10px;clear:both;}

</style>
<script  src="https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://static.thetimebilling.com/tabletools/js/TableTools.js"></script>

<script type="text/javascript">
	var MonedaArray= new Array();
	<?php 
		$currency=array();
		$querycurrency="select * from prm_moneda";
		$respcurrency = mysql_query($querycurrency, $sesion->dbh);
		$i=0;
		while($fila= mysql_fetch_assoc($respcurrency)) {
			$currency[++$i]=$fila;
			echo 'MonedaArray['.$i.'] = "'.$currency[$i]['simbolo'].'"'."\n";
		}
		  echo 'var PERMISOCOBRANZA = '.($p_cobranza->fields['permitido'] ? 1 : 0) .';';
	?>
		
    jQuery(document).ready(function() {
     
	 
	 jQuery('.noborraradelanto').live('click',function() {
	 		 jQuery('#mensaje').html('No se puede borrar el adelanto, ha sido utilizado en al menos <?php echo __('un cobro'); ?>...');  
			 
	 });
	 
	 jQuery('.desborraradelanto').live('click',function() {
	 var laID=jQuery(this).attr('id').replace('desborra_','');

	if(confirm('Confirma restaurar adelanto #'+laID+'?')) {
		jQuery.post('ajax/ajax_adelantos.php?accion=desborraadelanto',{id_documento:laID},function(data) {
															if(window.console) console.log(data);
														},'jsonp');
														} else {
															return false;
														}
	});
	 jQuery('.borraradelanto').live('click',function() {
	 var laID=jQuery(this).attr('id').replace('borra_','');

	if(confirm('Confirma eliminar adelanto #'+laID+'?')) {
		jQuery.post('ajax/ajax_adelantos.php?accion=borraadelanto',{id_documento:laID},function(data) {
															if(window.console) console.log(data);
														},'jsonp');
														} else {
															return false;
														}
	});
 
		
		jQuery('#boton_buscar').click(function() {
			var tablagastos=   jQuery('#tablon').hide().dataTable({
                                 "fnPreDrawCallback": function( oSettings ) {
									jQuery('#tablon').fadeTo('fast',0.1);
									
								},
								 
								"bDestroy":true,	
                                "oLanguage": {    "sProcessing":   "Procesando..." ,   "sLengthMenu":   "Mostrar _MENU_ registros","sZeroRecords":  "No se encontraron resultados",  "sInfo":         "Mostrando desde _START_ hasta _END_ de _TOTAL_ registros",
                                    "sInfoEmpty":    "Mostrando desde 0 hasta 0 de 0 registros",  "sInfoFiltered": "(filtrado de _MAX_ registros en total)",   "sInfoPostFix":  "",  "sSearch":       "Filtrar:",
                                    "sUrl":          "", 	"oPaginate": {            "sPrevious": "Anterior",   "sNext":     "Siguiente"}
                                },
                                "bFilter": false,    "bProcessing": true,
                                "sAjaxSource": "ajax/ajax_adelantos.php?accion=listaadelanto&where=1&"+jQuery('#form_adelantos').serialize(),
                                "bJQueryUI": true,
                                "bDeferRender": true,
							 
								"fnServerData": function ( sSource, aoData, fnCallback ) {
									jQuery.ajax( {	"dataType": 'json', "type": "POST", "url": sSource, "data": aoData, 
										"success": fnCallback,
										"complete" :function() {
											jQuery('#tablon').fadeTo(0, 1);
										}
									})
								},
							
                                "aoColumnDefs": [
                               {  "sClass": "alignleft",    "aTargets": [ 1,3 ]   },
							   {  "sClass": "alignright",    "aTargets": [ 4,5 ]   },
							     {  "sClass": "aligncenter",    "aTargets": [ 7 ]   },
									 {  "sClass": "marginleft",    "aTargets": [ 1,3 ]   },
                                    
                                    
                                    {  "sWidth": "60px",    "aTargets": [0,2,5,4,5 ]   },
                                   
									  { "bVisible": false, "aTargets": [ 7 ] },
									  
			    {  "fnRender": function ( o, val ) {
							 return   o.aData[6]+'<br>'+ o.aData[3];
                        },    "aTargets": [ 3 ]   } ,
                     
					 {  "fnRender": function ( o, val ) {
							 return   MonedaArray[+o.aData[7]] +' '+o.aData[4]
                        },    "aTargets": [ 4 ]   } ,
					 {  "fnRender": function ( o, val ) {
							 return   MonedaArray[+o.aData[7]] +' '+o.aData[5]
                        },    "aTargets": [ 5]   } ,
					
					
					 {  "fnRender": function ( o, val ) {
							
							var respuesta="<a href=\"javascript:void(0)\"  style=\"float:right;display:inline;margin-right:10px;\" onclick=\"nuovaFinestra('Agregar_Adelanto', 730, 580,'ingresar_documento_pago.php?id_documento="+ o.aData[0]+"&amp;adelanto=1&amp;popup=1', 'top=100, left=155');\"><img src=\"https://static.thetimebilling.com/images/editar_on.gif\" border=\"0\" title=\"Editar\"></a>";
							
							if (jQuery('#eliminados').is(':checked')) {
								respuesta="<a href=\"javascript:void(0)\"  id=\"desborra_"+ o.aData[0]+"\" class='desborraradelanto' \"><img src=\"https://static.thetimebilling.com/images/undelete.gif\" border=\"0\" title=\"Restaurar\"></a>";	
							} else {
								if (o.aData[4]!=o.aData[5]) {
									respuesta+="<a href=\"javascript:void(0)\"    class='noborraradelanto' \"><img src=\"https://static.thetimebilling.com/images/delete-icon-off.gif\" border=\"0\" title=\"No se puede editar\"></a>";
								} else {
									respuesta+="<a href=\"javascript:void(0)\"  id=\"borra_"+ o.aData[0]+"\" class='borraradelanto' \"><img src=\"https://static.thetimebilling.com/images/delete-icon16.gif\" border=\"0\" title=\"Editar\"></a>";	
								}
							}
							if(PERMISOCOBRANZA==1) {
								return   respuesta;
							} else {
								return   '-';
							}
                        },    "aTargets": [ 6 ]   } 
					 	
						
						
                    
	         ],
			 "aaSorting": [[0,'desc']],
                "iDisplayLength": 25,
                "aLengthMenu": [[25,50, 150, 300,500, -1], [25,50, 150, 300,500, "Todo"]],
                "sPaginationType": "full_numbers",
                "sDom":  'T<"top"ip>rt<"bottom">'
                ,"oTableTools": {            "sSwfPath": "../js/copy_cvs_xls.swf",	"aButtons": [ "xls","copy" ]     }
				
               
			  
            }).show();
		});
		 
});



    function Refrescarse() {
                
             jQuery('#boton_buscar').click();
    }
	function AgregarNuevo(tipo)
	{
		<?php
		if (UtilesApp::GetConf($sesion,'CodigoSecundario')) { ?>
			var codigo_cliente_secundario = $('codigo_cliente_secundario').value;
			var url_extension = "&codigo_cliente_secundario=" + codigo_cliente_secundario;
		<?php } else { ?>
			var codigo_cliente = $('codigo_cliente').value;
			var url_extension = "&codigo_cliente=" + codigo_cliente;
		<?php } ?>
		if(tipo == 'adelanto')
		{
			var urlo = "ingresar_documento_pago.php?popup=1&adelanto=1" + url_extension;
                        return	nuovaFinestra('Agregar_Adelanto', 720, 500, urlo, 'top=100, left=125');
        
                }
    
	}
 
 
        
</script>

<?php 
//echo Autocompletador::CSS();
 
	?>

<table width="90%">
	<tr>
		<td>
			<form method='post' name="form_adelantos" action='adelantos.php' id="form_adelantos">
                            <input  id="xdesde"  name="xdesde" type="hidden" value="">
				<input type='hidden' name='opc' id='opc' value=buscar>
				<!-- Calendario DIV -->
				<div id="calendar-container" style="width:221px; position:absolute; display:none;">
					<div class="floating" id="calendar"></div>
				</div>
				<!-- Fin calendario DIV -->
				<fieldset class="tb_base" style="width: 100%;border: 1px solid #BDBDBD;">
					<legend><?php echo __('Filtros')?></legend>
					<table style="border: 0px solid black" width='720px'>
						<tr>
							<td align="right"><label for="id_documento">N° Adelanto</laber>
							<td align="left">
								<input type="text" size="6" name="id_documento" id="id_documento" value="<?php echo $id_documento ?>">
							</td>
						</tr>
						<tr>
	    					<td align="right" width="30%"><?php echo __('Nombre Cliente') ?></td>
	    					<td colspan="3" align="left">
				<?php UtilesApp::CampoCliente($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>

	  						</td>
						</tr>
						<tr>
	    					<td align="right" width="30%"><?php echo __('Asunto') ?></td>
	    					<td colspan="3" align="left">
                                <?php   UtilesApp::CampoAsunto($sesion,$codigo_cliente,$codigo_cliente_secundario,$codigo_asunto,$codigo_asunto_secundario); ?>

	  						</td>
						</tr>
						<tr>
							<td align=right><?php echo __('Fecha Desde') ?></td>
							<td align="left">
								<input type="text" name="fecha1" class="fechadiff" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
								
							</td>
							<td align="left" colspan="2">
								<?php echo __('Fecha Hasta')?>
								<input type="text" name="fecha2" class="fechadiff" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
								
							</td>
						</tr>
						<tr>
							<td align=right>
								<?php echo __('Moneda') ?>
							</td>
							<td colspan="2" align="left">
								<?php echo  Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_adelanto", $moneda_adelanto, "", __('Todas'),''); ?>
							</td>
							<td></td>
						</tr>
						<tr>
							<td align=right>
								<?php echo __('Sólo '. __('Adelantos'). ' con Saldo') ?>
							</td>
							<td colspan="2" align="left">
								<input type="checkbox" id="tiene_saldo" name="tiene_saldo" value="1" <?php echo $tiene_saldo ? 'checked' : ''?>/>
							</td>
							<td></td>
						</tr>
							<tr>
							<td align=right>
								<?php echo __('Buscar '. __('Adelantos'). ' eliminados') ?>
							</td>
							<td colspan="2" align="left">
								<input type="checkbox" id="eliminados" name="eliminados" value="1" <?php echo $eliminados ? 'checked' : ''?>/>
							</td>
							<td></td>
						</tr>
						<tr>
							<td></td>
							<td colspan=2 align=left>
								<input name="boton_buscar" id="boton_buscar" type="button" value="<?php echo __('Buscar') ?>" class="btn">
                                                                
							</td>
							<td width='40%' align="right">
								<?php if($p_cobranza->fields['permitido']) {				?>
								<img src="<?php echo Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('adelanto')" title="Agregar Adelanto"><?php echo __('Agregar')?> <?php echo __('adelanto') ?></a>
							<?php } ?>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</td>
	</tr>
</table>

<div id="losadelantos">
	<div id="mensaje"></div>
	   <table cellpadding="0" cellspacing="0" border="0" class="display" id="tablon" style="width:920px;display:none;">
	<thead>
		<tr class="encabezadolight">
		<th >ID Adelanto</th>
<th width="200"><?php echo __('Cliente') ;?></th>
<th>Fecha</th>
<th width="250">Descripción<br><small>(<?php echo __('Asunto') ;?>)</small></th>


<th>Monto</th>
<th>Saldo</th>
<th width="60">Acciones</th>
		<th >idcobro</th></tr>
	</thead>
	<tbody>
		
	</tbody></table>
</div>
<?php

	
	
	/*if (UtilesApp::GetConf($sesion,'TipoSelectCliente')=='autocompletador')
	{
		echo(Autocompletador::Javascript($sesion));
	}
	 * 
	 */
	//echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
 

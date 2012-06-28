<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/classes/FacturaPdfDatos.php';
	
	$sesion = new Sesion(array('ADM','COB'));
	$pagina = new Pagina($sesion);
    
        if( empty($id_documento_legal) ) {
            $query = "SELECT id_documento_legal FROM prm_documento_legal LIMIT 1";
            $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            list($id_documento_legal) = mysql_fetch_array($resp);
        }
        
        if( empty($id_factura_pdf_datos_categoria) ) {
            $query = "SELECT id_factura_pdf_datos_categoria FROM factura_pdf_datos_categoria LIMIT 1";
            $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            list($id_factura_pdf_datos_categoria) = mysql_fetch_array($resp);
        }
	
	if( $opc == 'guardar' || $opc == 'imprimir_factura' ) {
		foreach($_POST as $key => $value) {
			list($indicador, $campo, $id) = explode("_",$key);
                        if( $id == 'documento' ) {
                            list($e1,$e2,$e3,$e4,$id) = explode("_",$key);
                            $campo = 'id_documento_legal';
                        }
			
			if( $indicador != 'fac' ) continue;
			
			$factura_pdf_datos = new FacturaPdfDatos($sesion);
			$factura_pdf_datos->Load($id);
			$factura_pdf_datos->Edit($campo, $value);
			if( empty($_POST['fac_activo_'.$id]) ) {
				$factura_pdf_datos->Edit('activo','0');
			}
			$factura_pdf_datos->Write();
		}
        $query = "SELECT id_factura FROM factura WHERE id_documento_legal = '$id_documento_legal' ORDER BY id_factura DESC LIMIT 1";
        $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
        list($id_factura) = mysql_fetch_array($resp);

		$factura_pdf_datos = new FacturaPdfDatos($sesion);
		$factura_pdf_datos->generarFacturaPDF( $id_factura, true );
	}
	
	if( $opc == 'imprimir_factura' ) {
		$factura_pdf_datos->generarFacturaPDF( $id_factura );
	}
	
	$pagina->titulo = __('Mantención factura PDF');
	$pagina->PrintTop();
 ?>
<script>
  
var Filas=new Array;
     var Actual=0;
    
     var staticpath='https://static.thetimebilling.com/';

	var factorx=1;
        var factory=1;
    
    jQuery(document).ready(function() {    
 var Id_documento_legal=jQuery('#select_id_documento_legal').val();
     var Id_categoria=jQuery('#select_id_factura_pdf_datos_categoria').val();
	jQuery(document).keydown(function(e){
	
	var x=0;
	var y=0;
        if(e.keyCode>40 || e.keyCode<37) return true;
	if (e.keyCode ==37) x=-1;
	if (e.keyCode ==38) y=-1;
	if (e.keyCode ==39) x=1;
	if (e.keyCode ==40) y=1;
	if(Actual.length>0) 	{
	   var ancho=jQuery('#pizarra').width();
           var pgx=parseInt(jQuery('#ancho').val());
           var pgy=parseInt(jQuery('#alto').val());
           var alto=ancho*pgy/pgx;
           jQuery('#pizarra').height(alto);
            factorx=ancho/pgx;
	    factory=alto/pgy;
	   jQuery('#fac_coordinateX_'+Actual).val(parseInt(jQuery('#fac_coordinateX_'+Actual).val())+x);
	   jQuery('#fac_coordinateY_'+Actual).val(parseInt(jQuery('#fac_coordinateY_'+Actual).val())+y); 
           var posx=parseInt(factorx*jQuery('#fac_coordinateX_'+Actual).val());
	   var posy=parseInt(factory*jQuery('#fac_coordinateY_'+Actual).val());
	   jQuery('#caja_'+Actual).css({left:posx,top:posy});
	  }
	   return false;
	
	});


    
    jQuery('#select_id_documento_legal').change(function() {
        var Id_documento_legal=jQuery('#select_id_documento_legal').val()
        jQuery('#id_documento_legal').val(Id_documento_legal);
        var Id_categoria=jQuery('#select_id_factura_pdf_datos_categoria').val();
       jQuery('#uploadify').hide().appendTo('#cambio_tipo_doc');
        jQuery.post('ajax/mantencion_factura_pdf_ajax.php',{opc: 'dibuja_tabla', id_documento_legal:Id_documento_legal},function(data) {
       
       jQuery("#tabla_coordenadas").html(data).show(); 
            pizarron();
            filasporcat();
            var Pos=jQuery(".cat_"+Id_categoria).first().attr('rel');          
            jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria]});
          //  jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria]});
		  jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria], 'margin-bottom':parseInt(216-24*Filas[Id_categoria])});
            jQuery( "#tabla_coordenadas" ).css({'top':24*(1-Pos)} );
            jQuery('#uploadify').appendTo('#fatcell').show();
	    jQuery("#contienecoordenadas").removeClass('divloading');
        });
	
	jQuery( "#tabla_coordenadas" ).hide();
        jQuery("#contienecoordenadas").addClass('divloading'); 
    });
    
    jQuery('#select_id_factura_pdf_datos_categoria').change(function() {
        var Id_categoria=jQuery('#select_id_factura_pdf_datos_categoria').val()
        jQuery('#id_factura_pdf_datos_categoria').val(Id_categoria);
        var Pos=jQuery(".cat_"+Id_categoria).first().attr('rel');
        jQuery( "#tabla_coordenadas" ).css({'top':(24*(1-Pos))});
        //  jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria]});
		  jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria], 'margin-bottom':parseInt(216-24*Filas[Id_categoria])});
        
    });
    
    jQuery('#botonguardar').click(function() {
        jQuery(this).attr({'disabled':true, value:'Guardando...'});
        jQuery.post('ajax/mantencion_factura_pdf_ajax.php',jQuery('#datospdf').serialize(),function(data) {
            jQuery('#mensaje').html(data);
            jQuery('#botonguardar').attr({'disabled':false, value:'Guardar'});
            pizarron();
        });  
    });
    
    jQuery('#botonimprimir').click(function() {
        var Id_documento_legal=jQuery('#id_documento_legal').val();
        var Src="ajax/mantencion_factura_pdf_ajax.php?opc=imprimir_factura&id_documento_legal="+Id_documento_legal;
        jQuery('#botonimprimir').attr({'disabled':true, value:'Generando Documento'});
        jQuery.ajax({
	  type: 'POST',
	  url: 'ajax/mantencion_factura_pdf_ajax.php',
	  data: jQuery('#datospdf').serialize(),
	  //dataType: xml,
	  success: function(data) {
		    jQuery('<iframe id="TestFrame"></iframe>').appendTo('body');
		    jQuery('#TestFrame').hide();
		      jQuery.when(jQuery('#TestFrame').attr({'src':Src})).then(function() {
			    jQuery('#botonimprimir').attr({'disabled':false, value:'Imprimir Documento'});
			});
         
		}
	});
        return jQuery('#TestFrame').remove();
    });
    
    jQuery('.cajitas').live('click',function() {
        Actual=jQuery(this).attr('id').replace('caja_','');      
        var Id_categoria=jQuery(this).attr('rel')
        jQuery('#select_id_factura_pdf_datos_categoria').val(Id_categoria);
        var Pos=jQuery(".cat_"+Id_categoria).first().attr('rel');
        jQuery( "#tabla_coordenadas" ).css({'top':(24*(1-Pos))});
        jQuery("#fila_"+Actual).css({'background':'#CFC'});
       //  jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria]});
		    jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria], 'margin-bottom':parseInt(216-24*Filas[Id_categoria])});
        jQuery("#fila_"+Actual).animate({'backgroundColor':'#FFF'},2000);
    });
    
    jQuery('.cajitas').live('dblclick',function() {
   	Editando=jQuery(this).attr('id').replace('caja_',''); 
	Cat=jQuery(this).attr('rel');
	 var $exhtml=jQuery('#fac_ejemplo_'+Editando).val();
	jQuery(this).html('<textarea class="temptextarea"  id="textarea_'+Editando+'" rel="'+Cat+'">'+$exhtml+'</textarea>');
	jQuery('#textarea_'+Editando).focus();
    });
   
   jQuery('.temptextarea').live('blur',function() {
	ID=jQuery(this).attr('id').replace('textarea_',''); 
	Cat=jQuery(this).attr('rel');
	jQuery('#fac_ejemplo_'+ID).val(jQuery(this).val().replace("\n",'<br/>'));
	jQuery('#caja_'+ID).remove();
	agregaporid(ID,Cat);
    });
    
    jQuery('.fac_activo').live('change',function(){
        var ID=jQuery(this).attr('id').replace('fac_activo_','');
        var Cat=jQuery(this).attr('rel');
        if(jQuery(this).is(':checked')){
            agregaporid(ID,Cat);
        } else {
            jQuery('#caja_'+ID).remove();
        };
    });
    
    jQuery('.facfont').live('change',function(){    
        var ID=jQuery(this).attr('rel');
        var Font=jQuery('#fac_font_'+ID).val();
        jQuery('#caja_'+ID).css({'font-family':Font});
    });

   jQuery('#pizarra').mousemove(function(event) {
     //console.log(event.pageX + ", " + event.pageY);
    });
    
    jQuery('.facpos').live('keyup',function(){    
        var ID=jQuery(this).attr('rel');
        ancho=jQuery('#pizarra').width();
        pgx=parseInt(jQuery('#ancho').val());
        pgy=parseInt(jQuery('#alto').val());
        alto=ancho*pgy/pgx;
        jQuery('#pizarra').height(alto);
        var factorx=ancho/pgx;
        var factory=alto/pgy;
        var posx=parseInt(factorx*jQuery('#fac_coordinateX_'+ID).val());
        var posy=parseInt(factory*jQuery('#fac_coordinateY_'+ID).val());
        jQuery('#caja_'+ID).css({left:posx,top:posy});
    });
    
    jQuery('.facsize').live('keyup',function(){   
        var ID=jQuery(this).attr('rel');
        ancho=jQuery('#pizarra').width();
        pgx=parseInt(jQuery('#ancho').val());
        pgy=parseInt(jQuery('#alto').val());
        alto=ancho*pgy/pgx;
        jQuery('#pizarra').height(alto);
        var factorx=ancho/pgx;
        var factory=alto/pgy;
        var Width=parseInt(factorx*jQuery('#fac_cellW_'+ID).val());
        var Height=parseInt(factory*jQuery('#fac_cellH_'+ID).val());
        jQuery('#caja_'+ID).css({width:Width,height: Height});
    });
    
    jQuery('#papersize').live('change',function() {
        if (jQuery(this).val()=='0x0') {
            jQuery('#papersize').hide()
            jQuery('#ancho').show()
            jQuery('#alto').show()
            return true;
        }
        var llaves=jQuery(this).val().split('x');
        
        jQuery('#ancho').val(llaves[0]);
        jQuery('#alto').val(llaves[1]);
        
        var ancho=jQuery('#pizarra').width();
        var pgx=parseInt(jQuery('#ancho').val());
        var pgy=parseInt(jQuery('#alto').val());
        var alto=ancho*pgy/pgx;
        jQuery('#pizarra').height(alto);
        var factorx=ancho/pgx;
        var factory=alto/pgy;
        
        jQuery('.cajitas').each(function() {
            var cajaID=jQuery(this).attr('id').replace('caja_','');
            var coordx=jQuery('#fac_coordinateX_'+cajaID).val();
            var coordy=jQuery('#fac_coordinateY_'+cajaID).val();
            //   alert(cajaID+' : '+coordx+' y '+coordy);
            var posx=parseInt(factorx*coordx);
            var posy=parseInt(factory*coordy);
            jQuery(this).css({left:posx,top:posy});
        });
        
    });
    jQuery('.facstyle').live('change',function(){    
        var ID=jQuery(this).attr('rel');
        var Style=jQuery('#fac_style_'+ID).val();
        var Css='';
        if(Style=='') Css={'text-decoration':'none', 'font-weight':'normal', 'font-style':'normal'};
        if(Style=='B') Css={'text-decoration':'none', 'font-weight':'bold', 'font-style':'normal'};
        if(Style=='I') Css={'text-decoration':'none', 'font-weight':'normal', 'font-style':'italics'};
        if(Style=='U') Css={'text-decoration':'underline', 'font-weight':'normal', 'font-style':'normal'};
        jQuery('#caja_'+ID).css(Css);
    });
    
    jQuery('.facmayus').live('change',function(){    
        var ID=jQuery(this).attr('rel');
        var Mayus=jQuery('#fac_mayuscula_'+ID).val();
        var transform='none';
        if(Mayus=='may') transform='uppercase';
        if(Mayus=='min') transform='lowercase';
        jQuery('#caja_'+ID).css({'text-transform':transform});
    });
    
    
    jQuery('.fontsize').live('change',function(){    
        var ID=jQuery(this).attr('rel');     
        var Fontsize=jQuery('#fac_tamano_'+ID).val();
        jQuery('#caja_'+ID).css({'font-size':Fontsize+'pt'});
    });
      
    jQuery("#cambio").click(function () {
       
        var Img = jQuery("#cambio").attr("rel");
        jQuery.post( staticpath+'scan/uploadifyplus.php', {
            action: 'borrar',
            img: Img
        });
		  jQuery("#cambio").hide();
            jQuery("#fotela").show();
             jQuery("#fotelaUploader").show();
				jQuery("#fotelaQueue").show();	
				jQuery("#fondo").val('').change();
				jQuery('#botonguardar').click();
    });    


});	// termina Document Ready

function pizarron() {
        jQuery('#pizarra').html('');
        jQuery("#pizarra").append('<div id="subpizarra" style="position:absolute;top:0;left:0;width:100%;height:100%;background: transparent 0 0 no-repeat;opacity:0.5; z-index:0;"></div>');
        jQuery('input:checkbox:checked', "#tabla_coordenadas").each(function() {
            var ID=jQuery(this).attr('id').replace('fac_activo_','');
            var Cat=jQuery(this).attr('rel');
            agregaporid(ID,Cat);
            
        });
	var Fondo=jQuery('#fondo').val();
        if(Fondo!==undefined && Fondo.length>0) {
            jQuery('#subpizarra').css({'background-image':'url('+Fondo+')'});
            
            jQuery("#cambio").attr("rel",  Fondo.replace(staticpath+'scan/','')).show();
            jQuery("#fotela").hide();
            jQuery("#fotelaUploader").hide();
            jQuery("#fotelaQueue").hide();			 
        } else {
	    jQuery("#cambio").attr("rel", '').hide();
	  jQuery("#fotela").show();
            jQuery("#fotelaUploader").show();
            jQuery("#fotelaQueue").show();  
	}
    }
    function filasporcat() {
        Filas[0]=0; Filas[1]=0;          Filas[2]=0;          Filas[3]=0;          Filas[4]=0;          Filas[8]=0;
        var tipo=0;
        jQuery("#tabla_coordenadas ul").each(function() {
            tipo=jQuery(this).attr('class').replace("cat_",'');
            Filas[8]=Filas[8]+1;
            Filas[tipo]=Filas[tipo]+1;
        });
        
    }
    
    function agregaporid(ID,Cat) {
	    ancho=jQuery('#pizarra').width();
	    pgx=parseInt(jQuery('#ancho').val());
	    pgy=parseInt(jQuery('#alto').val());
	    factor=ancho/pgx;
	     alto=factor*pgy;
	    jQuery('#pizarra').height(alto);

	    var Pos=0;
	    var Relleno=jQuery('#fac_ejemplo_'+ID).val();
	    if(Relleno.length==0) Relleno=jQuery('#glosa_'+ID).attr('rel');
	    if(Relleno.length==0) Relleno=jQuery('#glosa_'+ID).html();
	    var ArrayRelleno=Relleno.split("\n");


	    var Fontsize=jQuery('#fac_tamano_'+ID).val();
	    var Extension=Fontsize*ArrayRelleno[0].length;
	    var posx=factor*jQuery('#fac_coordinateX_'+ID).val();
	    var posy=factor*jQuery('#fac_coordinateY_'+ID).val();
	    var Width=jQuery('#fac_cellW_'+ID).val();
	    if(Width==0) Width=Extension/2.8;
	    Width=Width*factor;

	    var Height=jQuery('#fac_cellH_'+ID).val();
	    if(Height==0) Height=Fontsize*(ArrayRelleno.length)/1.8;
	    Height=Height*factor;
	   // console.log(ArrayRelleno,ArrayRelleno[0].length,ArrayRelleno.length,Extension, Width, Height, factor);
	    var Font=jQuery('#fac_font_'+ID).val();
	    var Style=jQuery('#fac_style_'+ID).val();

	    var Mayus=jQuery('#fac_mayuscula_'+ID).val();
	    var transform='none';
	    if(Mayus=='may') transform='uppercase';
	    if(Mayus=='min') transform='lowercase';

	    var Style=jQuery('#fac_style_'+ID).val();
	    var Css='';
	    if(Style=='') Css={'text-decoration':'none', 'font-weight':'normal', 'font-style':'normal'};
	    if(Style=='B') Css={'text-decoration':'none', 'font-weight':'bold', 'font-style':'normal'};
	    if(Style=='I') Css={'text-decoration':'none', 'font-weight':'normal', 'font-style':'italics'};
	    if(Style=='U') Css={'text-decoration':'underline', 'font-weight':'normal', 'font-style':'normal'};
	    jQuery("#pizarra").append("<div rel='"+Cat+"' class='cajitas' id='caja_"+ID+"' style='text-transform:"+transform+";font-family:"+Font+";font-size:"+Fontsize+"pt;position:absolute; width:"+Width+"px;height:"+Height+"px;left:"+posx+"px;top:"+posy+"px;z-index:"+ID+";'>"+Relleno+"</div>");
	    jQuery('#caja_'+ID).draggable({cursor:'move', containment:'#pizarra', 
		drag:function(event,ui) {
		     Actual=jQuery(this).attr('id').replace('caja_','');      
	    var Id_categoria=jQuery(this).attr('rel')
	    jQuery('#select_id_factura_pdf_datos_categoria').val(Id_categoria);
		    jQuery('#fac_coordinateX_'+ID).val(parseInt(ui.position.left/factor));
		    jQuery('#fac_coordinateY_'+ID).val(parseInt(ui.position.top/factor)); 
		    jQuery('#fila_'+ID).css('background','#CFC');
		    jQuery('#select_id_factura_pdf_datos_categoria').val(Cat);
		    Pos=jQuery(".cat_"+Cat).first().attr('rel');
		    jQuery( "#tabla_coordenadas" ).css({'top':(24*(1-Pos))});
		    jQuery("#fila_"+ID).css({'background':'#CFC'});
		  //  jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria]});
			jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria], 'margin-bottom':parseInt(216-24*Filas[Id_categoria])});
		    jQuery("#fila_"+ID).animate({'backgroundColor':'#FFF'},2000);
		},
		stop:function(event,ui) {
		    jQuery('#fila_'+ID).css('background','#FFF');
		}
	    }).resizable({
		resize:function(event,ui) {
		     Actual=jQuery(this).attr('id').replace('caja_','');      
	    var Id_categoria=jQuery(this).attr('rel')
	    jQuery('#select_id_factura_pdf_datos_categoria').val(Id_categoria);
		    jQuery('#fac_coordinateX_'+ID).val(parseInt(ui.position.left/factor));
		    jQuery('#fac_coordinateY_'+ID).val(parseInt(ui.position.top/factor));  
		    jQuery('#fac_cellW_'+ID).val(parseInt(ui.size.width/factor));
		    jQuery('#fac_cellH_'+ID).val(parseInt(ui.size.height/factor));  
		    jQuery('#fila_'+ID).css('background','#CFC');
		    jQuery('#select_id_factura_pdf_datos_categoria').val(Cat);
		    Pos=jQuery(".cat_"+Cat).first().attr('rel');
		    jQuery( "#tabla_coordenadas" ).css({'top':(24*(1-Pos))});
		    jQuery("#fila_"+ID).css({'background':'#CFC'});
		    //  jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria]});
			jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria], 'margin-bottom':parseInt(216-24*Filas[Id_categoria])});
		    jQuery("#fila_"+ID).animate({'backgroundColor':'#FFF'},2000);
		},
		stop:function(event,ui) {
		    jQuery('#'+ID).css('background','#FFF');
		}               
	    }).css(Css);
	} //fin agregar por ID
   	   
function YoucangonowMichael() {
  Id_documento_legal=jQuery('#select_id_documento_legal').val();
   Id_categoria=jQuery('#select_id_factura_pdf_datos_categoria').val();
   staticpath='https://static.thetimebilling.com/';
	     //if (typeof(console)!==undefined) console.log('cargando...'+Id_documento_legal);
	 jQuery.post('ajax/mantencion_factura_pdf_ajax.php',{opc: 'dibuja_tabla', id_documento_legal:Id_documento_legal},function(data) { 
		jQuery("#tabla_coordenadas").html(data);  
		 filasporcat();
                    var Pos=jQuery(".cat_"+Id_categoria).first().attr('rel');  
                    jQuery( "#tabla_coordenadas" ).css({'top':24*(1-Pos)} );
                   jQuery("#contienecoordenadas").css({'height':24*Filas[Id_categoria], 'margin-bottom':parseInt(216-24*Filas[Id_categoria])});
		   jQuery('#datospdf').show();
		    pizarron();
                   
		   jQuery('#uploadify').appendTo('#fatcell').show();
		})        
                  
				
            
     

    
    jQuery.when(jQuery.ajax({async: false,cache:true, type: "GET", url: 'https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js', dataType: 'script' }) ,
	 jQuery.ajax({async: false,cache:true,  type: "GET", url: staticpath+'jquery.uploadify.v2.1.0.min.js', dataType: 'script' })	 
	 ).then(function() {
           
		      jQuery('#fotela').uploadify({
			'scriptAccess': 'always',
			'uploader': staticpath+'scan/uploadify.swf',
			'script': staticpath+'scan/uploadifyplus.php',
			'folder': '/' + jQuery("#underscan").val(),
			'cancelImg': staticpath+'images/cancel.png',
			'fileDesc': 'Archivos de imagen para la web',
			'fileExt': '*.jpg;*.gif;*.png',
			'buttonImg': staticpath+'images/miniupload.gif',
			'rollover': true,
			'width': 20,
			'height': 19,
			'auto': true,
			'multi':false,

			'onComplete': function (event, queueID, fileObj, response, data) {
			    
			    jQuery("#uploading").hide();   
			    jQuery("#fotela").hide();
			    jQuery("#fotelaUploader").hide();
			    jQuery("#fotelaQueue").hide();		
			    jQuery("#cambio").attr("rel",  jQuery("#underscan").val() + '/' + fileObj.name).show();
			    jQuery("#fondo").val(staticpath+'scan/'+  jQuery("#cambio").attr('rel'));
			    jQuery("#fotelaQueue").hide();			 
			    jQuery('#botonguardar').click();
			}

		    });
	var Fondo=jQuery('#fondo').val();
        if(Fondo!==undefined && Fondo.length>0) {
            jQuery('#subpizarra').css({'background-image':'url('+Fondo+')'});
            
            jQuery("#cambio").attr("rel",  Fondo.replace(staticpath+'scan/','')).show();
            jQuery("#fotela").hide();
            jQuery("#fotelaUploader").hide();
            jQuery("#fotelaQueue").hide();			 
        }
		jQuery('#uploadify').appendTo('#fatcell').show();
		jQuery('#pizarra').removeClass('divloading');
            });  

   } 

 </script>

      
        <table width="80%" >
            <tr>
                <td style="text-align:right;vertical-align: middle;" >
                    <?php echo __('Tipo documento legal:') ?>
                    &nbsp;
                </td>
                <td  align="left">
                    <?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", "select_id_documento_legal", $id_documento_legal, " "); ?>
                </td>
            
                <td style="text-align:right;vertical-align: middle;">&nbsp;&nbsp;
                    <?php echo __('Tipo dato:') ?>
                    &nbsp;
                </td>
                <td width="25%" align="left">
                    <?php echo Html::SelectQuery($sesion, "SELECT id_factura_pdf_datos_categoria, glosa FROM factura_pdf_datos_categoria", "select_id_factura_pdf_datos_categoria", $id_factura_pdf_datos_categoria, "  "); ?>
                </td>
                <td >
                
                	<input type="button"  id="botonguardar" value="Guardar" style="width:80px;">
	&nbsp;
	
	<input type="button"  id="botonimprimir"  value="Imprimir Documento" style="width:120px;"></td>

            </tr>
        </table>
 <div id="cambio_tipo_doc" style="display:none;">&nbsp;</div>
			<div id="uploadify" style="display:none;height:22px;width:24px;overflow:hidden;margin:2px 0 0 5px; ">
			<img id="uploading" src="https://static.thetimebilling.com/images/uploading.gif"  height="20" width="20" style="border:0;text-decoration:none;display:none;"/>
			<div id="fotela" style="width:20px;overflow:hidden;"></div>
			<a style="display:none;" href="#" id="cambio" rel="" >
			<img  src="https://static.thetimebilling.com/images/delete-icon.gif"  height="19" width="19" style="border:0;text-decoration:none;"/>
			</a>
			</div>

<?php
if(defined('SUBDOMAIN')&&defined('ROOTDIR')) {
$underscan=SUBDOMAIN.'/'.ROOTDIR;    
} else {
$fffurl=parse_url('http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
$rootbeer=explode('/',$fffurl['path']);
define('CURHOST',$fffurl['host']);
define('CURROOTDIR',$rootbeer[1]);
$underscan=CURHOST.'/'.CURROOTDIR;
}
	
	echo "<form id='datospdf' action=\"#\" style='display:none;' method=\"POST\">";
	echo '<input type="hidden" value="'. $underscan .'" name="underscan" id="underscan"/>';

	echo "<input type=\"hidden\" name=\"opc\" id=\"opc\" value=\"guardar\" />";
    echo "<input type=\"hidden\" name=\"id_documento_legal\" id=\"id_documento_legal\" value=\"$id_documento_legal\" />";
	echo "<input type=\"hidden\" name=\"id_factura_pdf_datos_categoria\" id=\"id_factura_pdf_datos_categoria\" value=\"$id_factura_pdf_datos_categoria\" />";
    echo "<table align=\"center\" style='width:810px'; cellpadding=\"0\" cellspacing=\"0\">";
	echo "<div class='cabecera'><ul>";
	echo "<li  class=\"st1cell encabezado\">Tipo Dato</li>";
	echo "<li   class=\"nd2cell encabezado\">Activo</li>";
	echo "<li  class=\"rd3cell encabezado\">Posici&oacute;n<br>Horizontal</li>";
	echo "<li  class=\"rd3cell encabezado\">Posici&oacute;n<br>Vertical</li>";
    echo "<li  class=\"rd3cell encabezado\">Ancho<br>[mm]</li>";
    echo "<li  class=\"rd3cell encabezado\">Alto<br>[mm]</li>";
	echo "<li style='width:120px;' class=\"encabezado\">Tipograf&iacute;a</li>";
	echo "<li style='width:80px;' class=\"encabezado\">Estilo</li>";
	echo "<li style='width:100px;' class=\"encabezado\">Mayúscula</li>";
	echo "<li style='width:50px;text-align:left;' class=\"encabezado\">Tamaño</li>";
	echo "</ul></div>";
	echo "<div id='contienecoordenadas' ><div id='tabla_coordenadas' ></div></div>";
        echo "</form>";
	
	echo '<div id="mensaje" style="clear:both;display:block;margin:10px auto ;color:#999;font-size:14px;">Vista Previa: las cajas en torno al texto son puramente referenciales</div>';
	
	echo '<div id="pizarra" class="divloading" style="text-align:left; position:relative; border: 1px solid #CCC;width:800px;height:300px;margin:10px auto;">&nbsp;</div>';	
	
	
	$pagina->PrintBottom();
?>

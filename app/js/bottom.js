 

jQuery.ajax({async: false, cache:true, type: "GET", url: 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.19/jquery-ui.min.js', dataType: 'script', success: function() {     
   jQuery.datepicker.regional['es'] = {
		closeText: 'Cerrar',
		prevText: '&#x3c;Ant',
		nextText: 'Sig&#x3e;',
		currentText: 'Hoy',
		monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
		'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
		monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun',
		'Jul','Ago','Sep','Oct','Nov','Dic'],
		dayNames: ['Domingo','Lunes','Martes','Mi&eacute;rcoles','Jueves','Viernes','S&aacute;bado'],
		dayNamesShort: ['Dom','Lun','Mar','Mi&eacute;','Juv','Vie','S&aacute;b'],
		dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','S&aacute;'],
		weekHeader: 'Sm',
		dateFormat: 'dd-mm-yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['es']);
		
		(function( jQuery ) {
		jQuery.widget( "ui.combobox", {
			_create: function() {
				var self = this,
					select = this.element.hide(),
					selected = select.children( ":selected" ),
					value = selected.val() ? selected.text() : "";
				var input = this.input = jQuery( "<input>" )
					.insertAfter( select )
					.val( value )
					.autocomplete({
						delay: 0,
						minLength: 0,
						source: function( request, response ) {
							var matcher = new RegExp( jQuery.ui.autocomplete.escapeRegex(request.term), "i" );
							response( select.children( "option" ).map(function() {
								var text = jQuery( this ).text();
								if ( this.value && ( !request.term || matcher.test(text) ) )
									return {
										label: text.replace(
											new RegExp(
												"(?![^&;]+;)(?!<[^<>]*)(" +
												jQuery.ui.autocomplete.escapeRegex(request.term) +
												")(?![^<>]*>)(?![^&;]+;)", "gi"
											), "<strong>$1</strong>" ),
										value: text,
										option: this
									};
							}) );
						},
						select: function( event, ui ) {
							ui.item.option.selected = true;
							self._trigger( "selected", event, {
								item: ui.item.option
							});
						},
						change: function( event, ui ) {
							if ( !ui.item ) {
								var matcher = new RegExp( "^" + jQuery.ui.autocomplete.escapeRegex( jQuery(this).val() ) + "$", "i" ),
									valid = false;
								select.children( "option" ).each(function() {
									if ( jQuery( this ).text().match( matcher ) ) {
										this.selected = valid = true;
										return false;
									}
								});
								if ( !valid ) {
									// remove invalid value, as it didn't match anything
									jQuery( this ).val( "" );
									select.val( "" );
									input.data( "autocomplete" ).term = "";
									return false;
								}
							}
						}
					})
					.addClass( "ui-widget ui-widget-content ui-corner-left" );

				input.data( "autocomplete" )._renderItem = function( ul, item ) {
					return jQuery( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>" + item.label + "</a>" )
						.appendTo( ul );
				};

				this.button = jQuery( "<button type='button'>&nbsp;</button>" )
					.attr( "tabIndex", -1 )
					.attr( "title", "Mostrar Todo" )
					.insertAfter( input )
					.button({
						icons: {
							primary: "ui-icon-triangle-1-s"
						},
						text: false
					})
					.removeClass( "ui-corner-all" )
					.removeClass( "ui-state-default" )
					.addClass( "ui-widget-black ui-corner-right ui-button-icon" )
					.click(function() {
						// close if already visible
						if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
							input.autocomplete( "close" );
							return;
						}

						// work around a bug (likely same cause as #5265)
						jQuery( this ).blur();

						// pass empty string as value to search for, displaying all results
						input.autocomplete( "search", "" );
						input.focus();
					});
			},

			destroy: function() {
				this.input.remove();
				this.button.remove();
				this.element.show();
				jQuery.Widget.prototype.destroy.call( this );
			}
		});
	})( jQuery );
	
	
	
	
		if(window.location==top.window.location) {
		    jQuery('#dialogomodal').dialog({
			    autoOpen: false, height: 'auto',width: 800,  maxHeight: 550, modal: true,  
			    show: 'fade', hide: 'fade',  position: ['center',30],  dialogClass: 'lemondialog',
			    close: function(event,ui) { 
				 var prescroll=jQuery(this).attr('alt');
							    if( top.window.Refrescarse ) {
								top.window.Refrescarse();
							      top.window.jQuery('#dialogomodal .activomodal').show();
							     
							    } else {
							    if( parent.window.Refrescar ) 	parent.window.Refrescar();
							    }

			       top.window.jQuery('#soymodal').attr({'src':'','rel':'inactivo'});
			       							     top.window.jQuery('html, body').animate({scrollTop:prescroll},500);


			    }
		    }).attr('rel','activomodal').append(DivLoading).append('<iframe id="soymodal" rel="inactivo" style="display:none;height:100%;width:100%" frameborder="0"></iframe>');
		     }
	     
	
	jQuery('.combox').combobox();
	jQuery('.tabs' ).tabs();
	jQuery('.fechadiff').datepicker( {
			showOn: "both",
			buttonImage: "https://static.thetimebilling.com/images/calendar.gif",
			buttonImageOnly: true
    }); 
	   
          jQuery('.botonizame').each(function() {
        
             jQuery(this).button({  icons: {
                     primary: jQuery(this).attr('icon')	
                     ,secondary: jQuery(this).attr('icon2')
                 }                   });
                if(jQuery(this).attr('setwidth')>0) jQuery(this).css({'width':jQuery(this).attr('setwidth')+'px', 'text-align':'left'});
           }); 
           jQuery( ".buttonset").buttonset();
           jQuery('.sortable').sortable();
            if(typeof  YoucangonowMichael == 'function')      YoucangonowMichael(); 
  
   }});




(function(){
  function loadChartbeat() {
    window._sf_endpt=(new Date()).getTime();
    var e = document.createElement('script');
    e.setAttribute('language', 'javascript');
    e.setAttribute('type', 'text/javascript');
    e.setAttribute('src',
       (("https:" == document.location.protocol) ? "https://a248.e.akamai.net/chartbeat.download.akamai.com/102508/" : "http://static.chartbeat.com/") +
       "js/chartbeat.js");
    document.body.appendChild(e);
  }
  var oldonload = window.onload;
  window.onload = (typeof window.onload != 'function') ?
     loadChartbeat : function() { oldonload(); loadChartbeat(); };
})();


SetFocoPrimerElemento();


function nuovaFinestra(name, w, h, url, opciones ) {

                if(top.window.jQuery('body').attr('title')=='overlay' && top.window.jQuery('#soymodal').attr('rel')=='inactivo' && top.window.jQuery('#dialogomodal').length>0 && top.window.jQuery('#dialogomodal').attr('rel')=='activomodal') {
                            
				var inipos=Math.max(top.window.jQuery('body').scrollTop(), top.window.jQuery('html').scrollTop());
			
			
			    
			    jQuery.when( top.window.jQuery('html, body').animate({scrollTop:0}, 500) ).done(function() {
				top.window.jQuery('#dialogomodal').dialog('open').attr('alt',inipos).dialog('option','title',name.replace('_',' ')).dialog('option','height',h).dialog('option','width',w);
				    
					   			
					    top.window.jQuery('#soymodal').attr({'src':url, 'rel':'activo'}).show('fast',function() {
						 top.window.jQuery('#dialogomodal .divloading').slideUp();
					    });
				    
				
			    });
                        } else {
			  if(window.console) console.log(top.window.jQuery('#dialogomodal'));
			     return  nuevaVentana( name, w, h, url, opciones );  
                        }
                }
function Cerrar() {

        if(window.location==parent.window.location) { //estoy en un popup
             if(  parent.window.Refrescarse ) {
                          parent.window.Refrescarse(); 
                   } else if(  parent.window.Refrescar ) {
                         parent.window.Refrescar(); 
                   } else if (window.opener!==undefined && window.opener.Refrescar) {
                        window.opener.Refrescar();
                   }
            window.close();
        } else { //estoy en un overlay
           if(  top.window.Refrescarse )   top.window.Refrescarse(); 
          
	top.window.jQuery('#dialogomodal').dialog('close');
	top.window.jQuery('#soymodal').attr({'src':'','rel':'inactivo'});        
    }
        
}   

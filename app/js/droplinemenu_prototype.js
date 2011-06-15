var myTimeout;
var tag_activo = null;
var cont_url_padre = 0;
var pariente = null;
var url_actual = document.URL;
var url_pariente = null;
function buildmenu(menuid) {
	var pariente = parienteLINodo();
	
	if(navigator.appName == 'Microsoft Internet Explorer') {
		mostrarTagActivoDefault();
	}
	
	$("droplinetabs1").observe('mouseover', function(e) {
		clearTimeout(myTimeout);
	});
	
	$("droplinetabs1").observe('mouseout', function(e) {
		myTimeout = setTimeout(function() { 
				mostrarTagActivoDefault();
			}
			, 2000);
	});	
	
	$("fd_menu_grey").observe('mouseover', function(e) {
		clearTimeout(myTimeout);
	});
	
	$("fd_menu_grey").observe('mouseout', function(e) {
		myTimeout = setTimeout(function() { 
				mostrarTagActivoDefault();
			}
			, 2000);
	});	
	
	
	
	$("droplinetabs1").observe('mouseover', function(e) {
		var deplegadores = $$("#droplinetabs1 ul li a");
		var lista_spiffy = $$("#droplinetabs1 ul li b");
		var lista_div = $$("#droplinetabs1 ul li div");
		var lista_span = $$("#droplinetabs1 ul li a span");
		var lista_li = $$("#droplinetabs1 ul li");
		
		if(navigator.appName == 'Microsoft Internet Explorer') {
			for(var i=0;i < deplegadores.length;i++) {
				var listado_all_span = deplegadores[i].getElementsByTagName("span");
				var listado_all_spiffy1 = deplegadores[i].getElementsByClassName("spiffy1");
				var listado_all_spiffy2 = deplegadores[i].getElementsByClassName("spiffy2");
				var listado_all_spiffy3 = deplegadores[i].getElementsByClassName("spiffy3");
				var listado_all_spiffy4 = deplegadores[i].getElementsByClassName("spiffy4");
				var listado_all_spiffy5 = deplegadores[i].getElementsByClassName("spiffy5");
				var listado_all_spiffyfg = deplegadores[i].getElementsByClassName("spiffyfg");
				var listado_all_color_activo = deplegadores[i].getElementsByClassName("color_activo");
				var cambiar_estilo = 0;
				for(var o=0;o < listado_all_span.length;o++){
					if(listado_all_span[o].style.background!='E0E0E0') {
						cambiar_estilo = 1;
					}
				}
				if((tag_activo != deplegadores[i]) && cambiar_estilo == 1) {
					for(var o=0;o < listado_all_span.length;o++){
						listado_all_span[o].style.background='E0E0E0';
						listado_all_span[o].style.color='346700';
					}
					for(var o=0;o < listado_all_spiffy1.length;o++){
						listado_all_spiffy1[o].style.background='E0E0E0';
						listado_all_spiffy2[o].style.background='E0E0E0';
						listado_all_spiffy3[o].style.background='E0E0E0';
						listado_all_spiffy4[o].style.background='E0E0E0';
						listado_all_spiffy5[o].style.background='E0E0E0';
						listado_all_spiffy1[o].style.borderColor='E0E0E0';
						listado_all_spiffy2[o].style.borderColor='E0E0E0';
						listado_all_spiffy3[o].style.borderColor='E0E0E0';
						listado_all_spiffy4[o].style.borderColor='E0E0E0';
						listado_all_spiffy5[o].style.borderColor='E0E0E0';
						listado_all_spiffyfg[o].style.background='E0E0E0';
						listado_all_color_activo[o].style.background='E0E0E0';
						var listado_all_spiffy2_sub_color_activo = listado_all_spiffy2[o].getElementsByClassName("color_activo");
						for(var u=0; u<listado_all_spiffy2_sub_color_activo.length;u++) {
							listado_all_spiffy2_sub_color_activo[u].style.background='E0E0E0';
						}
					}
				}
			}
		}
		
		for(var i=0;i < deplegadores.length;i++) {
			var padre = deplegadores[i].parentNode.parentNode;
			if(navigator.appName != 'Microsoft Internet Explorer') {
				if(padre.getElementsByTagName("a")[0].className == 'a_color_activo') {
					padre.getElementsByTagName("a")[0].className='a_color_activo_no_destacado';	
				}
			}
			
			deplegadores[i].onmouseover = mostrar;
		}	
	});
	
	function mostrar() {
		var padre = this.parentNode.parentNode;
		var url_actual = document.URL;
		$('fd_menu_grey').innerHTML = '<div id="sub_menu"></div>';
		$('sub_menu').style.height = '28px';
		$('sub_menu').style.margin = '0px';
		$('sub_menu').style.background = '42A62B';
		$('sub_menu').innerHTML = '<ul>' + padre.getElementsByTagName("ul")[0].innerHTML + '</ul>';	
		
		if(navigator.appName == 'Microsoft Internet Explorer') {
			tag_activo = this;
			var listado_all_span = this.getElementsByTagName("span");
			var listado_all_spiffy1 = this.getElementsByClassName("spiffy1");
			var listado_all_spiffy2 = this.getElementsByClassName("spiffy2");
			var listado_all_spiffy3 = this.getElementsByClassName("spiffy3");
			var listado_all_spiffy4 = this.getElementsByClassName("spiffy4");
			var listado_all_spiffy5 = this.getElementsByClassName("spiffy5");
			var listado_all_spiffyfg = this.getElementsByClassName("spiffyfg");
			var listado_all_color_activo = this.getElementsByClassName("color_activo");
			for(var o=0;o < listado_all_span.length;o++){
				listado_all_span[o].style.background='42A62B';
				listado_all_span[o].style.color='FFFFFF';
			}
			for(var o=0;o < listado_all_spiffy1.length;o++){
				listado_all_spiffy1[o].style.background='42A62B';
				listado_all_spiffy2[o].style.background='42A62B';
				listado_all_spiffy3[o].style.background='42A62B';
				listado_all_spiffy4[o].style.background='42A62B';
				listado_all_spiffy5[o].style.background='42A62B';
				listado_all_spiffy1[o].style.borderColor='42A62B';
				listado_all_spiffy2[o].style.borderColor='42A62B';
				listado_all_spiffy3[o].style.borderColor='42A62B';
				listado_all_spiffy4[o].style.borderColor='42A62B';
				listado_all_spiffy5[o].style.borderColor='42A62B';
				listado_all_spiffyfg[o].style.background='42A62B';
				listado_all_color_activo[o].style.background='42A62B';
				var listado_all_spiffy2_sub_color_activo = listado_all_spiffy2[o].getElementsByClassName("color_activo");
				for(var u=0; u<listado_all_spiffy2_sub_color_activo.length;u++) {
					listado_all_spiffy2_sub_color_activo[u].style.background='42A62B';
				}
			}
		}
	}
		
	function mostrarTagActivoDefault() {
		var url_actual = document.URL;
		var cont_url = 0;
		var deplegadores = $$("#droplinetabs1 ul li a");
		for(var i=0;i < deplegadores.length;i++) {
			if(cont_url == 0) {
				if(deplegadores[i] == url_pariente) {
					var padre = deplegadores[i].parentNode.parentNode;
					
					if(navigator.appName == 'Microsoft Internet Explorer') {
						tag_activo = null;
						deplegadores[i].getElementsByClassName("spiffy1")[0].style.background='42A62B';
						deplegadores[i].getElementsByClassName("spiffy2")[0].style.background='42A62B';
						deplegadores[i].getElementsByClassName("spiffy3")[0].style.background='42A62B';
						deplegadores[i].getElementsByClassName("spiffy4")[0].style.background='42A62B';
						deplegadores[i].getElementsByClassName("spiffy5")[0].style.background='42A62B';
						deplegadores[i].getElementsByClassName("spiffyfg")[0].style.background='42A62B';
						deplegadores[i].getElementsByClassName("spiffy")[0].style.background='A0A0A0';
						deplegadores[i].getElementsByClassName("color_activo")[0].style.background='42A62B';
						deplegadores[i].getElementsByClassName("spiffy")[0].style.background='A0A0A0';
						deplegadores[i].getElementsByTagName("span")[0].style.background='42A62B';
						deplegadores[i].getElementsByTagName("span")[0].style.color='FFFFFF';
						deplegadores[i].getElementsByClassName("spiffy1")[0].style.borderColor='42A62B';
						deplegadores[i].getElementsByClassName("spiffy2")[0].style.borderColor='42A62B';
						deplegadores[i].getElementsByClassName("spiffy3")[0].style.borderColor='42A62B';
						deplegadores[i].getElementsByClassName("spiffy4")[0].style.borderColor='42A62B';
						deplegadores[i].getElementsByClassName("spiffy5")[0].style.borderColor='42A62B';	
						var spiffy2 = deplegadores[i].getElementsByClassName("spiffy2")[0];
						spiffy2.getElementsByClassName("color_activo")[0].style.background='42A62B';
						if(tag_activo == null) {
							tag_activo = deplegadores[i];
							for(var i=0;i < deplegadores.length;i++) {
								var listado_all_span = deplegadores[i].getElementsByTagName("span");
								var listado_all_spiffy1 = deplegadores[i].getElementsByClassName("spiffy1");
								var listado_all_spiffy2 = deplegadores[i].getElementsByClassName("spiffy2");
								var listado_all_spiffy3 = deplegadores[i].getElementsByClassName("spiffy3");
								var listado_all_spiffy4 = deplegadores[i].getElementsByClassName("spiffy4");
								var listado_all_spiffy5 = deplegadores[i].getElementsByClassName("spiffy5");
								var listado_all_spiffyfg = deplegadores[i].getElementsByClassName("spiffyfg");
								var listado_all_color_activo = deplegadores[i].getElementsByClassName("color_activo");
								var cambiar_estilo = 0;
								for(var o=0;o < listado_all_span.length;o++){
									if(listado_all_span[o].style.background!='E0E0E0') {
										cambiar_estilo = 1;
									}
								}
								if((tag_activo != deplegadores[i]) && cambiar_estilo == 1) {
									for(var o=0;o < listado_all_span.length;o++){
										listado_all_span[o].style.background='E0E0E0';
										listado_all_span[o].style.color='346700';
									}
									for(var o=0;o < listado_all_spiffy1.length;o++){
										listado_all_spiffy1[o].style.background='E0E0E0';
										listado_all_spiffy2[o].style.background='E0E0E0';
										listado_all_spiffy3[o].style.background='E0E0E0';
										listado_all_spiffy4[o].style.background='E0E0E0';
										listado_all_spiffy5[o].style.background='E0E0E0';
										listado_all_spiffyfg[o].style.background='E0E0E0';
										listado_all_color_activo[o].style.background='E0E0E0';
										var listado_all_spiffy2_sub_color_activo = listado_all_spiffy2[o].getElementsByClassName("color_activo");
										for(var u=0; u<listado_all_spiffy2_sub_color_activo.length;u++) {
											listado_all_spiffy2_sub_color_activo[u].style.background='E0E0E0';
										}
									}
								}
							}
						}
						
					}
					else {
						if(pariente.getElementsByTagName("a")[0].className != 'a_color_activo') {
							pariente.getElementsByTagName("a")[0].className='a_color_activo';
						}
					}
					$('fd_menu_grey').innerHTML = '<div id="sub_menu"></div>';
					$('sub_menu').style.height = '28px';
					$('sub_menu').style.margin = '0px';
					$('sub_menu').style.background = '42A62B';
					if(pariente != null) {
						if(navigator.appName == 'Microsoft Internet Explorer') {
							$('sub_menu').innerHTML = '<ul>' + pariente.parentNode.parentNode.getElementsByTagName("ul")[0].innerHTML + '</ul>';		
						}
						else {
							$('sub_menu').innerHTML = '<ul>' + pariente.getElementsByTagName("ul")[0].innerHTML + '</ul>';		
						}
						
					}
					cont_url++;
				}
			}
		}
	}
	
	function parienteLINodo() {
		var deplegadores_submenu = $$("#fd_menu_grey ul li a");
		var deplegadores_menu = $$("#droplinetabs1 ul li");
		var nodo_actual = null;
		var subnodo_actual = null;
			for(var v=0; v< deplegadores_submenu.length;v++) {
				if(cont_url_padre == 0) {
					if(deplegadores_submenu[v] == url_actual) {
					subnodo_actual = deplegadores_submenu[v].innerHTML;
					var ancestros = deplegadores_submenu[v];
					for(var w=0; w< deplegadores_menu.length;w++) {
						if(deplegadores_menu[w].getElementsByTagName("a")[0].innerHTML == ancestros.getElementsByTagName("span")[0].innerHTML) {
							nodo_actual = deplegadores_menu[w]; 
						}
					}
					cont_url_padre++;	
				}
			}
		}
		if(cont_url_padre == 0) {
			var deplegadores_menu = $$("#droplinetabs1 ul li a");
			for(var v=0; v< deplegadores_menu.length;v++) {
				if(cont_url_padre == 0) {
					if(deplegadores_menu[v] == url_actual) {
						nodo_actual = deplegadores_menu[v];
						cont_url_padre++;	
					}
				}
			}
		}
		if(cont_url_padre > 0){
			pariente =  nodo_actual.parentNode.parentNode;
			url_pariente = url_actual;
			if(navigator.appName == 'Microsoft Internet Explorer') {
				pariente = pariente.getElementsByTagName("a")[0];
				url_pariente = pariente;
			}
		}
		return pariente;
	}	
}



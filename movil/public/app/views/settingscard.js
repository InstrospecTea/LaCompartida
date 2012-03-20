ToolbarDemo.views.Settingscard = Ext.extend(Ext.form.FormPanel, {

	name: 'setting',
	elLoad:null,

	user:Ext.ModelMgr.create({
			name : '',
			password  : 0
		}, 'Usuario'),
    title: "config",
	store:ToolbarDemo.stores.usuario,
    iconCls: "settings",
    scroll: "vertical",
    initComponent: function() {
	
	var buttonbar = {
            xtype: 'toolbar',
            //dock: 'bottom',
			//cls: 'x-list-header',
			title: 'Configuracion',
			//titleCls:'x-list-header2',
			//style: 'height:50%'
            //items: [cancelButton, {xtype: 'spacer'}, reloj]
        };
	
	var errorPanel = new Ext.Panel({
			id:'errorPanelSettingCard',
			hidden: true,
			html: '<div>El usuario o la contrase&ntildea es incorrecto</div>',
			cls: 'errorField-tab',
		});
	
        Ext.apply(this, {
            dockedItems: [{
                xtype: "toolbar",
				id:'toolbarSettingCard',
				cls: 'logoLemon',
                //title: "Lemon"
            },buttonbar],
            items: [
				{
                xtype: 'fieldset',
                title: 'Detalles',
                items: [{
                    xtype: 'textfield',
                    name : 'name',
                    label: 'Usuario',
					id: 'user',
                },{
                    xtype: 'passwordfield',
                    name : 'password',
                    label: 'Clave',
					id: 'pass',
                },errorPanel],
            },{
                xtype:  'button',
                text:   'Guardar',
                ui:     'confirm',
				handler: this.login,
				scope:this,
            }],
			listeners:{
				show:this.autoFill,
				scope:this,
			},
			
			
        });
		
		//var user = 

		//this.down('#user').setValue('holi');
		//this.down('#pass').setValue('passs');
		
		//ToolbarDemo.views.Settingscard.load(user);
		
		
		
        ToolbarDemo.views.Settingscard.superclass.initComponent.apply(this, arguments);
		
		//var oli=5;
    },
	
	setOffline:function(){
		var tool = ToolbarDemo.views.settingsCard.down('#toolbarSettingCard');
		tool.removeCls('logoLemon');
		tool.addCls('logoLemonOff');
	},
	
	setOnline:function(){
		var tool = ToolbarDemo.views.settingsCard.down('#toolbarSettingCard');
		tool.addCls('logoLemon');
		tool.removeCls('logoLemonOff');
	},
	
	setErrorTab:function(params) {
		var error=ToolbarDemo.views.settingsCard.down('#errorPanelSettingCard');
		//var errorHtml=Ext.get('errorPanelSettingCard');
		if(!params.hide){
			//errorHtml.update(params.text);
			//error.html=params.text;
			error.show();
		}else{
			error.hide();
		}
	},
	
	init:function() {
		if (ToolbarDemo.views.settingsCard.store.getCount()>0){			
			var model = ToolbarDemo.views.settingsCard.store.getAt(0);
			ToolbarDemo.views.settingsCard.user.name=model.data.name;
			ToolbarDemo.views.settingsCard.user.password=model.data.password;	
		}	
	},
	
	autoFill: function(){
	
		if (this.store.getCount()>0){
			var model = this.store.getAt(0);
			this.load(model);
			//alert(model);
			//alert('holi D: hay un elemento!!!');
		}		
		//this.load(this.user);
		//alert(this.user);
		//alert('holi D:');
	
	},	
	
	//{ rut : '99511620-0' , password : 'admin.asdwsx'}
	login: function() {		
		//var el     = Ext.getBody(),
		var contenedor=this;
		this.elLoad = Ext.getBody();
		myMask = new Ext.LoadMask(this.elLoad, { msg : 'Espere por favor...' });
		//myMask.show();
		myMask.msg = 'Cargando';	
		this.elLoad.mask(Ext.LoadingSpinner + ' ' + myMask.msg + ' ', myMask.msgCls, false);
		//setTimeout(function() {el.mask(Ext.LoadingSpinner + ' ' + myMask.msg + ' ', myMask.msgCls, false);}, 1000);			
		//myMask.hide();
		//myMask.disable( );
		
		var user=this.down('#user');
		var pass=this.down('#pass');
		
		if (this.store.getCount()>0){
			var model = this.store.getAt(0);
			//model.actualizar(user.getValue(),pass.getValue());
			//model.set("name" , user.getValue());
			//model.set("password"  , pass.getValue());
            //model.save();
			this.store.remove(model);
			this.store.sync();
			this.store.create({name : user.getValue(),password  : pass.getValue()});
			this.store.sync();
		}else{
			
			this.store.create({name : user.getValue(),password  : pass.getValue()});
			this.store.sync();
		}
		//alert(user.getValue());
		//alert(pass.getValue());
		this.user.name=user.getValue();//'99511620-0';
		this.user.password=pass.getValue();//'admin.asdwsx';
		var rut = this.user.name;
		var password = this.user.password;
		
		$.ajax({
			type:"post",
			url:"../login",
			data: {"rut": rut, "password": password},
			complete:function(req) {
			//alert(req),
                                //alert("rut: " + rut + " password: "+ password);
                            
				if(req.status == 200 || req.status == 0) {
					//alert("El usuario y la contraseña son correctos");
					ToolbarDemo.views.settingsCard.setErrorTab({hide:true});
					ToolbarDemo.views.settingsCard.cargar_intervaloFinMask(contenedor);
					//ToolbarDemo.views.settingsCard.cargar_clientes(rut,password,contenedor);
					//ToolbarDemo.views.settingsCard.cargar_asuntos(rut,password,contenedor);	
					
					//ToolbarDemo.views.viewportLogin.hide();
					//ToolbarDemo.views.viewport.show({ type: 'slide' , cover: 'true' ,direction:'right'});
					
					//ToolbarDemo.views.viewport.setActiveItem(new ToolbarDemo.views.Homecard({id: 'home'},{ type: 'slide', direction: 'left' }));
					//ToolbarDemo.views.homeCard = ToolbarDemo.views.viewport.down('#home');
					//ToolbarDemo.views.viewport.remove(ToolbarDemo.views.viewport.down('#setting'),false);
					//ToolbarDemo.views.viewport.add({items: [{ xtype: 'homecard', id: 'home' }]});
					
					//ToolbarDemo.views.viewport.setActiveItem(ToolbarDemo.views.homeCard,{ type: 'slide' , cover: 'true' , direction: 'right' });
				} else {
					ToolbarDemo.views.settingsCard.setErrorTab({hide:false, text:'<div>El usuario o la contrase&ntildea es incorrecto</div>'});
					//alert("El usuario o la contraseña es incorrecto");
					
					contenedor.finLogin();
				}
			}
		});
		
		
		return false;
	},
		
	cargar_clientes: function(rut,password,contenedor){
		var rut = rut;
		var password = password;
		console.log('cargar clientes con rut '+rut+ ' y pass '+password);
		$.ajax({
			type:"post",
			url:"../clientes",
			data: {"rut": rut, "password": password},
			complete:function(req) {				
				if(req.status == 200 || req.status == 0) {
					//status 0 server error?
					if(req.status == 0 ){//&& !(navigator.onLine)){//recordar que no siempre se cumplen ambos (pc con apache y conexion, por safari)
						 Ext.dispatch({
								controller: 'Home',
								action    : 'loadClientesAsuntos',
						});
					}else{
						var clientes = JSON.parse(req.responseText);
						ToolbarDemo.views.usersForm.cargarClientes(clientes);
						
						Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Actualizados',
								sujeto:		'clientes',
								response    : req.responseText,
								status		: req.statusText,
						});
					}
					//alert(clientes.length)
					//for(var i=0 ; i<clientes.length;i=i+1)
					//{
						//alert(clientes[i]);
					//	ToolbarDemo.stores.clienteStore.create({codigo: clientes[i].codigo,glosa: clientes[i].glosa});
					//	ToolbarDemo.stores.clienteStore.sync();
					//}
					//alert(clientes[0].codigo)
					//localStorage.clientes = req.responseText;
					//app.mostrar_clientes('form#new_job_form .client_list');
					//app.mostrar_clientes('form#edit_job_form .client_list');
					//app.mostrar_clientes('form#old_job_form .client_list');
					//alert("Los clientes fueron cargados");					
				} else {
					//alert("Los clientes no fueron cargados");
						Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Error al actualizar',
								sujeto:		'clientes',
								response    : req.responseText,
								status		: req.statusText,
						});
				}			
			}
		});
		return false;
	},
		
	cargar_asuntos: function(rut,password,contenedor){
		var rut = rut;
		var password = password;
		$.ajax({
			type:"post",
			url:"../asuntos",
			data: {"rut": rut, "password": password},
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					//status 0 server error?
					if(req.status == 0 ){//&& !(navigator.onLine)){//recordar que no siempre se cumplen ambos (pc con apache y conexion, por safari)
						 Ext.dispatch({
								controller: 'Home',
								action    : 'loadClientesAsuntos',
						});
					}else{
						console.log(req);
						var asuntos = JSON.parse(req.responseText);
						ToolbarDemo.views.usersForm.cargarAsuntos(asuntos);
						
						Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Actualizados',
								sujeto:		'asuntos',
								response    : req.responseText,
								status		: req.statusText,
						});
					}					
					
					//for(var i=0 ; i<asuntos.length;i=i+1)
					//{
					//	ToolbarDemo.stores.asuntoStore.create({codigo: asuntos[i].codigo,glosa: asuntos[i].glosa,codigo_padre:asuntos[i].codigo_padre});
						//ToolbarDemo.stores.asuntoStore.sync();
					//}
					
					//localStorage.asuntos = req.responseText;
					//alert("Los asuntos fueron cargados");
					
					contenedor.finLogin();
				} else {
					//alert("Los asuntos no fueron cargados");	
					Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Error al actualizar',
								sujeto:		'asuntos',
								response    : req.responseText,
								status		: req.statusText,
						});
					
					
					contenedor.finLogin();
				}			
			}
		});
		return false;
	},
	
	cargar_intervalo: function(contenedor){
		$.ajax({
			type:"get",
			url:"../intervalo",
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					//status 0 server error?
					if(req.status == 0 ){//&& !(navigator.onLine)){//recordar que no siempre se cumplen ambos (pc con apache y conexion, por safari)
						ToolbarDemo.views.usersForm.cargarIntervalo("");
					}else{
						var intervalo = req.responseText;
						var intervaloJ = JSON.parse(req.responseText);
						ToolbarDemo.views.usersForm.cargarIntervalo(intervaloJ);
					}
					
					//localStorage.intervalo = req.responseText;
					//if(localStorage.intervalo && !duration_ready) {
					//	app.setup_sw_durations(parseInt(localStorage.intervalo));
					//	duration_ready = true;
					//}
					//alert("El intervalo fue cargado");					
				} else {
					//alert("El intervalo no fue cargado");					
				}			
			}
		});
		return false;
	},
	
	cargar_intervaloFinMask: function(contenedor){
		$.ajax({
			type:"get",
			url:"../intervalo",
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					//status 0 server error?
					if(req.status == 0 ){//&& !(navigator.onLine)){//recordar que no siempre se cumplen ambos (pc con apache y conexion, por safari)
						ToolbarDemo.views.usersForm.cargarIntervalo("");
					}else{
						var intervalo = req.responseText;
						var intervaloJ = JSON.parse(req.responseText);
						ToolbarDemo.views.usersForm.cargarIntervalo(intervaloJ);
					}
					
					//localStorage.intervalo = req.responseText;
					//if(localStorage.intervalo && !duration_ready) {
					//	app.setup_sw_durations(parseInt(localStorage.intervalo));
					//	duration_ready = true;
					//}
					//alert("El intervalo fue cargado");
					contenedor.finLogin();					
				} else {
					//alert("El intervalo no fue cargado");					
					contenedor.finLogin();
				}			
			}
		});
		return false;
	},
	
	cargar_clientesActualizar: function(rut,password,contenedor){
		var rut = rut;
		var password = password;
		$.ajax({
			type:"post",
			url:"../clientes",
			data: {"rut": rut, "password": password},
			complete:function(req) {				
				if(req.status == 200 || req.status == 0) {
					//status 0 server error?
					if(req.status == 0 ){//&& !(navigator.onLine)){//recordar que no siempre se cumplen ambos (pc con apache y conexion, por safari)
						ToolbarDemo.views.actualizacionLog.setError({hide:false,text:"Actulamente no esta conectado"});
					}else{
						var clientes = JSON.parse(req.responseText);
						ToolbarDemo.views.usersForm.cargarClientes(clientes);
						Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Actualizados',
								sujeto:		'clientes',
								response    : req.responseText,
								status		: req.statusText,
						});
					}
					//alert("Los clientes fueron cargados");					
				} else {
					Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Error al actualizar',
								sujeto:		'clientes',
								response    : req.responseText,
								status		: req.statusText,
						});
					//alert("Los clientes no fueron cargados");					
				}			
			}
		});
		return false;
	},
	
	cargar_asuntosActualizar: function(rut,password,contenedor){
		var rut = rut;
		var password = password;
		$.ajax({
			type:"post",
			url:"../asuntos",
			data: {"rut": rut, "password": password},
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					//status 0 server error?
					if(req.status == 0 ){//&& !(navigator.onLine)){//recordar que no siempre se cumplen ambos (pc con apache y conexion, por safari)
						ToolbarDemo.views.actualizacionLog.setError({hide:false,text:"Actualmente no esta conectado"});
						ToolbarDemo.views.actualizacionLog.finLogin();
					}else{
						var asuntos = JSON.parse(req.responseText);
						ToolbarDemo.views.usersForm.cargarAsuntos(asuntos);
						Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Actualizados',
								sujeto:		'asuntos',
								response    : req.responseText,
								status		: req.statusText,
						});
					}					
					
					contenedor.finLogin();
				} else {
					//alert("Los asuntos no fueron cargados");	
					Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Error al actualizar',
								sujeto:		'asuntos',
								response    : req.responseText,
								status		: req.statusText,
						});
					contenedor.finLogin();
				}			
			}
		});
		return false;
	},
	
	cargar_intervaloActualizar: function(contenedor){
		$.ajax({
			type:"get",
			url:"../intervalo",
			complete:function(req) {
				if(req.status == 200 || req.status == 0) {
					//status 0 server error?
					if(req.status == 0 ){//&& !(navigator.onLine)){//recordar que no siempre se cumplen ambos (pc con apache y conexion, por safari)
						ToolbarDemo.views.usersForm.cargarIntervalo("");
						ToolbarDemo.views.actualizacionLog.setError({hide:false,text:"Actualmente no esta conectado"});
						//ToolbarDemo.views.actualizacionLog.finLogin();
					}else{
						var intervalo = req.responseText;
						var intervaloJ = JSON.parse(req.responseText);
						ToolbarDemo.views.usersForm.cargarIntervalo(intervaloJ);
						Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Actualizado',
								sujeto:		'intervalo',
								response    : req.responseText,
								status		: req.statusText,
						});
					}
					
					//localStorage.intervalo = req.responseText;
					//if(localStorage.intervalo && !duration_ready) {
					//	app.setup_sw_durations(parseInt(localStorage.intervalo));
					//	duration_ready = true;
					//}
					//alert("El intervalo fue cargado");					
				} else {
					//alert("El intervalo no fue cargado");					
					Ext.dispatch({
								controller: 'Home',
								action    : 'newActualizarLog',
								text:		'Error al actualizar',
								sujeto:		'intervalo',
								response    : req.responseText,
								status		: req.statusText,
						});
				}			
			}
		});
		return false;
	},
	
	send: function(values,model,index){	
		var rut = ToolbarDemo.views.settingsCard.user.name;
		var password = ToolbarDemo.views.settingsCard.user.password;
		var fecha = values.fecha.format('Y-m-d');//"Y-M-D"
		
		//alert(rut+' send '+password);
		
		var arr = (values.duracion+"").split(':');
		var hour = parseInt(arr[0],10);
		var minute = parseInt(arr[1],10);	
		var duracion = hour*60+minute;//"min_duration"
		
		$.ajax({
			type:"post",
			url:"../trabajos2",
			data: {"rut": rut, "password": password, "codigo_asunto": values.codigo_asunto, "descripcion": values.descripcion, "fecha": fecha, "duracion": duracion,"ordenado_por": values.ordenado_por},              
			complete: function(req, msg) {
				if(req.status == 200 || req.status == 0) {
					//alert("envio 1");
					//var job_id = $form.attr("job_id");
					//if(job_id) {
						//var current_jobs = $.parseJSON(localStorage.jobs);
						//current_jobs.splice(job_id,1);
						//localStorage.jobs = JSON.stringify(current_jobs);
						//app.update_job_list();
					//}	
					//alert("El trabajo fue enviado.");
					
					//var record={data:{index:index}};
					//alert(index+'_'+record+'_'+record.data+'_'+record.data.index);
					//status 0 server error?
					if(req.status == 0 ){//&& !(navigator.onLine)){//recordar que no siempre se cumplen ambos (pc con apache y conexion, por safari)
						Ext.dispatch({
							controller: 'Home',
							action    : 'sendOffline',
						});
					}else{
						var data=model.data;
						Ext.dispatch({
							controller: 'Home',
							action    : 'remove',
							model    : model,
						});
						Ext.dispatch({
							controller: 'Home',
							action    : 'newLog',
							response    : req.responseText,
							status		: req.statusText,
							data		: data,
						});
					}
					
				}else{
					//alert(req.responseText);
					alert("El trabajo no pudo ser enviado.");
					//hay algunos errores en los que el remove no es capaz de eliminar, basicamente porque el error modifica la entrada de tal forma
					//que ya no es identificable siquiera por su id
					Ext.dispatch({
						controller: 'Home',
						action    : 'newLog',
						response    : req.responseText,
						status		: req.statusText,
						model		: model,		
					});
					
					Ext.dispatch({
						controller: 'Home',
						action    : 'remove',
						model    : model,
					});
				}			
			}
		});
		
		//alert("rut: _"+ rut + "_ password: _" + password + "_ codigo_asunto: _" + values.codigo_asunto + "_ descripcion: _" + values.descripcion + "_ fecha: _" + fecha + "_ duracion: _" + duracion);
		//this.update_job_list();
                //jQT.goBack();
		return false;
	},
	
	returnLogin: function(){
	
		var user={name:"",password:""};
		if (this.store.getCount()>0){
			var model = this.store.getAt(0);
			user.name=model.data.name;
			user.password=model.data.password;
			
		}else{
			
		}
	
		return user;
	
	},
	
	finLogin:function() {
		ToolbarDemo.views.settingsCard.elLoad.unmask();
	},
	
});

Ext.reg('settingscard', ToolbarDemo.views.Settingscard);

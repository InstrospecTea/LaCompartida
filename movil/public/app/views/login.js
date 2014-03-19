ToolbarDemo.views.LoginCard = Ext.extend(Ext.form.FormPanel, {

	elLoad:null,
	actualizar:false,

	user:Ext.ModelMgr.create({
			name : '',
			password  : null,
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
			id:'errorPanel',
			hidden: true,
			//html: '<div>El usuario o la contrase&ntildea es incorrecto</div>',
			cls: 'errorField-tab',
		});

        Ext.apply(this, {
            dockedItems: [{
                xtype: "toolbar",
				id:'toolbarLogin',
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
                text:   'Ingresar',
                ui:     'confirm',
				handler: this.login,
				scope:this,
            }],
			listeners:{
				beforerender:this.autoFill,
				scope:this,
			},


        });

		//var user =

		//this.down('#user').setValue('holi');
		//this.down('#pass').setValue('passs');

		//ToolbarDemo.views.Settingscard.load(user);



        ToolbarDemo.views.LoginCard.superclass.initComponent.apply(this, arguments);

		var oli=5;
    },

	setOffline:function(){
		var tool = ToolbarDemo.views.loginCard.down('#toolbarLogin');
		tool.addCls('logoLemonOff');
		tool.removeCls('logoLemon');
	},

	setOnline:function(){
		var tool = ToolbarDemo.views.loginCard.down('#toolbarLogin');
		tool.addCls('logoLemon');
		tool.removeCls('logoLemonOff');
	},

	setErrorTab:function(params) {
		var error=ToolbarDemo.views.loginCard.down('#errorPanel');
		var errorHtml=Ext.get('errorPanel');
		if(!params.hide){
			errorHtml.update(params.text);
			//error.html=params.text;
			error.show();
		}else{
			error.hide();
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
		this.user.rut=user.getValue();
		this.user.password=pass.getValue();
		var rut = this.user.rut;
		var password = this.user.password;
		//alert(rut+'  '+password);
		//if(!(navigator.onLine)){alert('offline!!!! :O');}
		//else{alert('online!!!! ;)');}
		Ext.dispatch({
					controller: 'Home',
					action: 'loginActualizar',
			});

		console.log('rut '+rut+ ' y pass '+password);
		$.ajax({
			type:"post",
			url:"../index.php/login",
			data: {"rut": rut, "password": password},
			complete:function(req) {
			//alert(req),
                                //alert("rut: " + rut + " password: "+ password);

				if(req.status == 200 || req.status == 0) {
					//alert("El usuario y la contraseña son correctos");
					if(ToolbarDemo.views.loginCard.actualizar){
						ToolbarDemo.views.settingsCard.cargar_intervalo(rut,password,contenedor);
						ToolbarDemo.views.loginCard.setErrorTab({hide:true});
						ToolbarDemo.views.settingsCard.cargar_clientes(rut,password,contenedor);
						ToolbarDemo.views.settingsCard.cargar_asuntos(rut,password,contenedor);
					}else{
						ToolbarDemo.views.settingsCard.cargar_intervaloFinMask(contenedor);
						ToolbarDemo.views.loginCard.setErrorTab({hide:true});
					}
					//ToolbarDemo.views.viewportLogin.hide();
					//ToolbarDemo.views.viewport.show({ type: 'slide' , cover: 'true' ,direction:'right'});

					//ToolbarDemo.views.viewport.setActiveItem(new ToolbarDemo.views.Homecard({id: 'home'},{ type: 'slide', direction: 'left' }));
					//ToolbarDemo.views.homeCard = ToolbarDemo.views.viewport.down('#home');
					//ToolbarDemo.views.viewport.remove(ToolbarDemo.views.viewport.down('#setting'),false);
					//ToolbarDemo.views.viewport.add({items: [{ xtype: 'homecard', id: 'home' }]});

					//ToolbarDemo.views.viewport.setActiveItem(ToolbarDemo.views.homeCard,{ type: 'slide' , cover: 'true' , direction: 'right' });

					//ToolbarDemo.views.loginCard.finLogin();
				} else {
					ToolbarDemo.views.loginCard.setErrorTab({hide:false, text:'<div>El usuario o la contrase&ntildea es incorrecto</div>'});
					//alert("El usuario o la contraseña es incorrecto");

					ToolbarDemo.views.loginCard.elLoad.unmask();
					//contenedor.finLogin();
				}
			}
		});


		return false;
	},

	login2: function() {
		ToolbarDemo.views.settingsCard.autoFill();
		ToolbarDemo.views.settingsCard.login();
	},

	finLogin:function() {
		ToolbarDemo.views.settingsCard.init();
		//ToolbarDemo.views.listCard.onIntervalAction();
		Ext.dispatch({
					controller: 'Home',
					action: 'initListCard',
		});
		ToolbarDemo.views.loginCard.elLoad.unmask();
		Ext.dispatch({
					controller: 'Home',
					action: 'endLogin',
		});
	},

});

Ext.reg('login', ToolbarDemo.views.LoginCard);

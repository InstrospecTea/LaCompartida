ToolbarDemo.views.ActualizacionLog = Ext.extend(Ext.Panel, {

   name: 'actualizacionLog',
   title: "actualizar",
   iconCls: "inbox2",
   //icon:"more",
   cardSwitchAnimation: 'slide',
   elLoad:null,
   actButton:null,
   
   initComponent: function(){
        var titlebar, backButton, deleteButton, buttonbar, list;
		
		var errorPanel = new Ext.Panel({
			id:'errorPanelActualizacionLog',
			hidden: true,
			//html: '<div>No se puede Sincronizar. No hay coneccion</div>',
			cls: 'errorField-tab',
		});
		
		backButton={
			itemId: 'backButton',
            iconCls: 'arrow_left',
            iconMask: true,
            ui: 'plain',
            handler: function(){
				Ext.dispatch({
					controller: 'Home',
					action: 'revealViewport',
					target: 'listCard',
				});
			},
            scope: this
		};
		
		deleteButton={
			itemId: 'deleteButton',
            iconCls: 'delete',
            iconMask: true,
			hidden:true,
            ui: 'plain',
            handler: this.onDeleteAction,
            scope: this
		};
		
		this.actButton=new Ext.Button({
			xtype: 'button',
			itemId: 'syncButton',
            iconCls: 'inbox2',
            iconMask: true,
            ui: 'plain',
            handler: this.onActualizarAction,
            scope: this
		});
		
        titlebar = {			
            dock: 'top',
			id: 'toolbarActualizacionLog',
            xtype: 'toolbar',
			cls: 'logoLemon',
            //title: 'Lemon',
            items: [  { xtype: 'spacer' }]
        };	
		
		buttonbar = {
            xtype: 'toolbar',
            //dock: 'bottom',
			//cls: 'x-list-header',
			title: 'Actualizaciones',
			//titleCls:'x-list-header2',
			//style: 'height:50%'
            items: [backButton,deleteButton, {xtype: 'spacer'} , this.actButton]
        };
		
        list = {
			//title:'Updates',
            xtype: 'list', 
			id: 'listActualizacionLog',
			//grouped: true,
            itemTpl:new Ext.XTemplate(
						'<tpl if="this.notError(statusText)">',
							'<p style=" font-size:80%; font-weight:bold;">{text}</p>',
						'</tpl>',
						'<tpl if="!this.notError(statusText)">',
							'<p style=" font-size:80%; font-weight:bold;">{text}</p>',
							'<p style=" font-size:50%; font-weight:bold; color:red">{statusText}</p>',
						'</tpl>',
						{// XTemplate configuration:
							compiled: true,
							auxNum: 0,
							// member functions:
							isnotZero: function(num){
								//var a =values;
								return num!=0;
							},
							notError: function(statusText){
								return statusText=='OK';
							},
							index: function(num){
								if(num==0){
									this.auxNum=0;
								}else{
									this.auxNum=this.auxNum+1;
									return this.auxNum;
								}
								
								return '';
							},
						}
					),					
            store: ToolbarDemo.stores.actualizarStore,
            emptyText:'<div class="emptytext"  style="font-size: 0.75em; color:white">&nbsp Actualmente no hay actualizaciones disponibles.</div>',		
			
        };
		
		
        Ext.apply(this, {
            //html: 'placeholder',
            layout: 'fit',
            dockedItems: [titlebar,buttonbar],
            items: [list,errorPanel],
			
        });

       ToolbarDemo.views.ActualizacionLog.superclass.initComponent.call(this);	   	   
    },	
	
	setError:function(params){
		var error=ToolbarDemo.views.actualizacionLog.down('#errorPanelActualizacionLog');
		var errorHtml=Ext.get('errorPanelActualizacionLog');
		if(!params.hide){
			errorHtml.update(params.text);
			//error.html=params.text;
			ToolbarDemo.views.actualizacionLog.down('#listActualizacionLog').setPosition(0,20);
			error.show();
		}else{
			ToolbarDemo.views.actualizacionLog.down('#listActualizacionLog').setPosition(0,0);
			error.hide();
		}
	},
	
	setOffline:function(){
		var tool = ToolbarDemo.views.actualizacionLog.down('#toolbarActualizacionLog');
		tool.removeCls('logoLemon');
		tool.addCls('logoLemonOff');
	},
	
	setOnline:function(){
		var tool = ToolbarDemo.views.actualizacionLog.down('#toolbarActualizacionLog');
		tool.addCls('logoLemon');
		tool.removeCls('logoLemonOff');
	},
	
	actualizar: function(){
		var contenedor=this;
		this.elLoad = Ext.getBody();
		myMask = new Ext.LoadMask(this.elLoad, { msg : 'Espere por favor...' });
		//myMask.show();
		myMask.msg = 'Cargando';	
		this.elLoad.mask(Ext.LoadingSpinner + ' ' + myMask.msg + ' ', myMask.msgCls, false);
	
		Ext.dispatch({
					controller: 'Home',
					action: 'clienteAsunto',
					contenedor: contenedor,
		});
	
	},
	
	finLogin:function() {
		ToolbarDemo.views.actualizacionLog.elLoad.unmask();
	},
	
	onActualizarAction:function(){
		this.actualizar();
	},
	
	onDeleteAction:function(){
		Ext.dispatch({
				controller: 'Home',
				action: 'clearActualizarLogs',
			});
	},
	
});

Ext.reg('actualizacionLog', ToolbarDemo.views.ActualizacionLog);

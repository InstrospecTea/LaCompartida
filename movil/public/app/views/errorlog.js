ToolbarDemo.views.Errorlog = Ext.extend(Ext.Panel, {

   name: 'errorlog',
   title: "Sync",
   iconCls: "sync",
   //icon:"more",
   cardSwitchAnimation: 'slide',
   syncButton:null,
   
   initComponent: function(){
        var titlebar, list, backButton, deleteButton, clearButton,buttonbar;
		
		var errorPanel = new Ext.Panel({
			id:'errorPanelErrorLog',
			hidden: true,
			//html: '<div>No se puede Sincronizar. No hay coneccion</div>',
			cls: 'errorField-tab',
		});
		
        titlebar = {			
            dock: 'top',
			id: 'toolbarErrorLog',
            xtype: 'toolbar',
			cls: 'logoLemon',
            //title: 'Lemon',
            items: [  { xtype: 'spacer' }]
        };		
		
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
		
		this.syncButton=new Ext.Button({
			xtype: 'button',
			itemId: 'syncButton',
            iconCls: 'sync',
            iconMask: true,
            ui: 'plain',
            handler: this.onSyncAction,
            scope: this
		});
		
		buttonbar = {
            xtype: 'toolbar',
            //dock: 'bottom',
			//cls: 'x-list-header',
			title: 'Actualizaciones',
			//titleCls:'x-list-header2',
			//style: 'height:50%'
            items: [backButton,deleteButton, {xtype: 'spacer'} , this.syncButton]
        };
		
        list = {
			//title:'Updates',
            xtype: 'list', 
			id: 'listErrorLog',
			//grouped: true,
            itemTpl:new Ext.XTemplate(
					'<p style=" font-size:80%; font-weight:bold;">{text}</p>',
					//'<tpl for=".">',		
						//'<tpl if="this.isOK(logs)" >',						
						//' <p>{[this.prueba(values.logs)]}</p>',
						'<tpl if="this.isnotZero(OK)">',
							//'<p id="subidosOK" style=" font-size:60%; color:green">Subidos ok {OK}  </p>',
							'{[this.index(0)]}',
							'<tpl for="values.logs">',
								'<tpl if="this.notError(statusText)">',
									'<p style=" font-size:50%;"><span  style=" color:green">&#x2713</span>. &nbsp {clientes} &nbsp Asunto:{tareas}</p>',
									//'<p style=" font-size:50%;">{descripcion} &nbsp Ordenado por:{ordenado_por}</p>',
								'</tpl>',
							'</tpl>',
						'</tpl>',
						//'</tpl>',
						'<tpl if="this.isnotZero(ERROR)">',
							//'<p style=" font-size:60%; color:red">Subidos con error {ERROR}</p>',
							'{[this.index(0)]}',
							'<tpl for="values.logs">',
								'<tpl if="!this.notError(statusText)">',
									'<p style=" font-size:50%;"><span  style=" color:red">&#x2718</span>. &nbsp {clientes} &nbsp Asunto:{tareas} &nbsp ({statusText})</p>',
									//'<p style=" font-size:50%;">{descripcion} &nbsp Ordenado por:{ordenado_por}</p>',
								'</tpl>',
							'</tpl>',
						'</tpl>',
					//'</tpl>',					
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
					
					
					
            store: ToolbarDemo.stores.updateStore,
            emptyText: '<div class="emptytext"  style="font-size: 0.75em; color:white">&nbsp Actualmente no hay actualizaciones disponibles.</div>',
			listeners:{
				selectionchange: this.onSelectionChange,
				scope: this,
			},
			
        };
		
		
        Ext.apply(this, {
            //html: 'placeholder',
            layout: 'fit',
            dockedItems: [titlebar,buttonbar],
            items: [list,errorPanel],
			
        });

       ToolbarDemo.views.Errorlog.superclass.initComponent.call(this);	   	   
    },	
	
	setError:function(params){
		var error=ToolbarDemo.views.errorlog.down('#errorPanelErrorLog');
		var errorHtml=Ext.get('errorPanelErrorLog');
		if(!params.hide){
			errorHtml.update(params.text);
			//error.html=params.text;
			ToolbarDemo.views.errorlog.down('#listErrorLog').setPosition(0,20);
			error.show();
		}else{
			ToolbarDemo.views.errorlog.down('#listErrorLog').setPosition(0,0);
			error.hide();
		}
	},
	
	setOffline:function(){
		var tool = ToolbarDemo.views.errorlog.down('#toolbarErrorLog');
		tool.removeCls('logoLemon');
		tool.addCls('logoLemonOff');
	},
	
	setOnline:function(){
		var tool = ToolbarDemo.views.errorlog.down('#toolbarErrorLog');
		tool.addCls('logoLemon');
		tool.removeCls('logoLemonOff');
	},
	
	setBadgeText: function(params) {
	
		if(params){
			//if(params=='aumentar'){
			//	var errorlog = ToolbarDemo.views.errorlog.tab;
			//	errorlog.setBadge('!');
			//}else{
				var errorlog = ToolbarDemo.views.errorlog.tab;
				var badgenumber = parseInt(errorlog.badgeText);
				var nextnumber = isNaN(badgenumber) ? 1 : badgenumber+1;
				errorlog.setBadge(nextnumber);
			//}
		}else{
			ToolbarDemo.views.errorlog.tab.setBadge("");
		}
	
	},
	
	onSelectionChange:function(DataView,node,selections){
		var b=$('#subidosOK').css('color');
		var a=node;
		
	},	
	
	onSyncAction:function(){
		Ext.dispatch({
				controller: 'Home',
				action: 'send',
				caller: 'syncButton',
			});
	},
	
	onDeleteAction:function(){
		Ext.dispatch({
				controller: 'Home',
				action: 'clearLogs',
			});
	},
});

Ext.reg('errorlog', ToolbarDemo.views.Errorlog);

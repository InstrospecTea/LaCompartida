ToolbarDemo.views.Listcard = Ext.extend(Ext.Panel, {

   name: 'list',
   title: "trabajos",
   iconCls: "list",
   //icon:"list",
   cardSwitchAnimation: 'slide',
   proyecto: -1,
   intervalID:null,
   interval:20000,
   actualizar:true,
   primero:true,
   //enviar :null,
   
   initComponent: function(){
        var addButton, titlebar, list, buttonbar;

		
        addButton = {
            itemId: 'addButton',
            iconCls: 'add',
            iconMask: true,
            ui: 'plain',
            handler: this.onAddAction,
            scope: this
        };

        titlebar = {
            dock: 'top',
			id:'toolbarListCard',
            xtype: 'toolbar',
			cls:  'logoLemon',
           // title: 'Lemon',
           //items: [logoLemon]
        };
		
		buttonbar = {
            xtype: 'toolbar',
            //dock: 'bottom',
			//cls: 'x-list-header',
			title: 'Trabajos',
			//titleCls:'x-list-header2',
			//style: 'height:50%'
            items: [{xtype: 'spacer'}, addButton]
        };

        list = {
            xtype: 'list',
            itemTpl: new Ext.XTemplate( 
					'<p> <span style="font-size: 70%">{clientes}</span> &nbsp <span style="font-size: 60%">Asunto: {tareas}</span></p>',
					'<tpl if="!this.areEmpty(descripcion,ordenado_por)" >',
						'<p style=" font-size: 50%">', 
							'<tpl if="!this.isEmpty(ordenado_por)" >',
								'Ordenado por: {ordenado_por} &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp',
							'</tpl>',
							'<tpl if="!this.isEmpty(descripcion)" >',
								'Descripcion: {[this.descripcionCorta(values.descripcion)]}',
							'</tpl>',
						'</p>',
					'</tpl>',
						
						{// XTemplate configuration:
							compiled: true,
							// member functions:
							areEmpty:function(text1,text2){
								return (this.isEmpty(text1) && this.isEmpty(text2));
							},
							
							isEmpty:function(text){
							
								if(text.length==0){
									return true;
								}else if(!text.match(/\S/g)){
									return true;
								}
								
								return false;
							},
							descripcionCorta: function(text){
								var texto;
								if(text.length>80){
									texto=text.substring(0, 79)+'...';
								}else{
									texto=text;
								}						
								//a=values;
								return texto;
							},
						}
					),
            store: ToolbarDemo.stores.users,           

            listeners:{				
				itemdoubletap: this.onItemdobletapAction,
				itemtap: this.onItemtapAction,	
				scope: this,
			},
			
			emptyText: '<div class="emptytext" style="font-size: 0.75em; color:white">&nbsp Actualmente no hay trabajos por sincronizar.</div>',
        };
		
		
        Ext.apply(this, {
            //html: 'placeholder',
            layout: 'fit',
            dockedItems: [titlebar,buttonbar],
            items: [list],
			
        });

       ToolbarDemo.views.Listcard.superclass.initComponent.call(this);
	  
	   //this.intervalID=setInterval(this.onIntervalAction,this.interval);	   
    },	
	
	setOffline:function(){
		var tool = ToolbarDemo.views.listCard.down('#toolbarListCard');
		tool.removeCls('logoLemon');
		tool.addCls('logoLemonOff');
	},
	
	setOnline:function(){
		var tool = ToolbarDemo.views.listCard.down('#toolbarListCard');
		tool.addCls('logoLemon');
		tool.removeCls('logoLemonOff');
	},
	
	setIntervalID: function() {
		//ToolbarDemo.views.listCard.intervalID=setInterval(ToolbarDemo.views.listCard.onIntervalAction,ToolbarDemo.views.listCard.interval);;
	},
	
	setActualizar: function(storeCount) {
		if(storeCount>0){
			if(!ToolbarDemo.views.listCard.primero){
				ToolbarDemo.views.listCard.actualizar=true;
				ToolbarDemo.views.listCard.setIntervalID();
				ToolbarDemo.views.listCard.primero=true;
			}
		}else{
			ToolbarDemo.views.listCard.actualizar=false;
			ToolbarDemo.views.listCard.primero=false;
		}
	},
	
	setProyecto: function(index) {
	
		if(ToolbarDemo.views.listCard.proyecto==index){
			ToolbarDemo.views.listCard.proyecto=index;
			//ToolbarDemo.views.listCard.down('#enviar').disable();
		}else{
			ToolbarDemo.views.listCard.proyecto=index;
			//ToolbarDemo.views.listCard.down('#enviar').enable();
		}
	},
	
	setBadgeText: function(params) {
	
		if(params){
			var listCard = ToolbarDemo.views.listCard.tab;
			var badgenumber = parseInt(listCard.badgeText);
            var nextnumber = isNaN(badgenumber) ? 1 : badgenumber+1;
            listCard.setBadge(nextnumber);
		}else{
			var listCard = ToolbarDemo.views.listCard.tab;
			var badgenumber = parseInt(listCard.badgeText);
            var nextnumber = isNaN(badgenumber) ? "" : badgenumber-1;
			nextnumber = nextnumber<=0 ? "" : nextnumber;
            listCard.setBadge(nextnumber);
		}
	
	},	
	
	onIntervalAction: function(){
		if(ToolbarDemo.views.listCard.actualizar){
				//alert('enviando...');
				Ext.dispatch({
					controller: 'Home',
					action: 'send',
			});
		}else{
			//alert('no envio mas :P');
			clearInterval(ToolbarDemo.views.listCard.intervalID);
		}			
	},
	
    onAddAction: function() {	
        Ext.dispatch({
            controller: 'Home',
            action: 'addProyecto',
			caller: 'list',
        });
    },	
	
	onItemtapAction: function(list, index, item, e) {
	
		this.setProyecto(index);
		
		//if(this.delayedTask == null || this.delayedTask === 'tap'){            
		//setup a delayed task that is called IF double click is not later detected
		//this.delayedTask = new Ext.util.DelayedTask(function(){
		//alert('oh mira un evento tap!!!!!');
		//this.onItemtapAction,
		//Ext.dispatch({
         //   controller: 'Home',
         //   action: 'selectProyect',
         //   index: index,
			//hour: 
			//min: 
        //}),
		//this.delayedTask = 'tap';}, this);

		//invoke (with reasonable time to cancel)
		//this.delayedTask.delay(250);}
		Ext.dispatch({
				controller: 'Home',
				action: 'editForm',
				index: index
			});
	
        
    },
	
    onItemdobletapAction: function(list, index, item, e) {
	
		//if(this.delayedTask != null && this.delayedTask != 'tap'){
		//	this.delayedTask.cancel();
		//	this.delayedTask = null;
		//}                        
		
		//handle the double click
		//alert('oh mira un evento doble tap!!!!!');
		//this.onItemdobletapAction();	
	
		//if(this.delayedTask != 'tap' || !this.delayedTask){
			//alert('aciendo edit');
		if(index==this.proyecto) {
			this.setProyecto(index);
			//Ext.dispatch({
				//controller: 'Home',
				//action: 'editForm',
				//index: index
			//});
		}
		//}
		//else{this.delayedTask=null;}
    },	
	
});

Ext.reg('listcard', ToolbarDemo.views.Listcard);

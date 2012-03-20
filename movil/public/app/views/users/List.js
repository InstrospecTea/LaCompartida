ToolbarDemo.views.UsersList = Ext.extend(Ext.Panel, {
	

	
    initComponent: function(){
        var volver, addButton, titlebar, list;

		volver = {
            text: 'volver',
            ui: 'back',
            handler: this.onVolverAction
        };
		
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
            xtype: 'toolbar',
            title: 'Proyectos',
            items: [ volver, { xtype: 'spacer' }, addButton ]
        };

        list = {
            xtype: 'list',
            itemTpl: '{id}: {clientes} -> {tareas} [{fechaS} {inicio}--{fin} ({duracion})]  {index}',
            store: ToolbarDemo.stores.users,
            emptyText: '<div class="emptytext">There are no users in the system at the moment.</div>',

         


            listeners: {
						scope: this,
						   
				itemdoubletap: this.onItemdobletapAction,
                itemtap: this.onItemtapAction,
				
            }
        };
		
		
        Ext.apply(this, {
            html: 'placeholder',
            layout: 'fit',
            dockedItems: [titlebar],
            items: [list]
			
		
        });

        ToolbarDemo.views.UsersList.superclass.initComponent.call(this);
    },

    onAddAction: function() {
        Ext.dispatch({
            controller: 'Home',
            action: 'newForm'
        });
    },

	onItemtapAction: function(list, index, item, e) {
	
		if(this.delayedTask == null || this.delayedTask === 'tap'){            
		//setup a delayed task that is called IF double click is not later detected
		this.delayedTask = new Ext.util.DelayedTask(function(){
		//alert('oh mira un evento tap!!!!!');
		//this.onItemtapAction,
		Ext.dispatch({
            controller: 'Home',
            action: 'selectProyect',
            index: index,
			//hour: 
			//min: 
        }),
		this.delayedTask = 'tap';}, this);

		//invoke (with reasonable time to cancel)
		this.delayedTask.delay(250);}
	
        
    },
	
    onItemdobletapAction: function(list, index, item, e) {
	
		if(this.delayedTask != null && this.delayedTask != 'tap'){
			this.delayedTask.cancel();
			this.delayedTask = null;
		}                        
		//handle the double click
		//alert('oh mira un evento doble tap!!!!!');
		//this.onItemdobletapAction();	
	
		if(this.delayedTask != 'tap'){
		
			Ext.dispatch({
				controller: 'Home',
				action: 'editForm',
				index: index
			});
		}
		else{this.delayedTask=null;}
    },
	
	onVolverAction: function() {
		//ToolbarDemo.views['usersList'].hide();
        Ext.dispatch({
            controller: 'Home',
            action: 'home'
        });
    },
});

Ext.reg('ToolbarDemo.views.UsersList', ToolbarDemo.views.UsersList);

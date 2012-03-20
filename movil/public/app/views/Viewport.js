ToolbarDemo.views.Viewport = Ext.extend(Ext.TabPanel, {
    fullscreen: true,
	layout: 'card',
	primera:false,
	//activeTab: 1,
	
    initComponent: function() {
        Ext.apply(this, {
			
            tabBar: {
                dock: 'bottom',
                layout: {
                    pack: 'center'
                }
            },
            items: [
				//{ xtype: 'homecard', id: 'home' },
				{ xtype: 'errorlog', id: 'log' },
				{ xtype: 'listcard', id: 'list' },
				{ xtype: 'settingscard', id: 'setting' },
				{ xtype: 'actualizacionLog', id: 'actualizacion' },
			],
						
            listeners: {
				beforeorientationchange:function(panel, orientation, width, height ){
					Ext.dispatch({
						controller: 'Home',
						action: 'onRotate',
					});
				},
				scope:this,
                //beforecardswitch: this.onBeforeCardSwitch,
				//show: function() {
                   //ToolbarDemo.views.listCard.show();
                //},
                //hide: function() {
                    //ToolbarDemo.views.listCard.hide();
                //},
            },			
           		
        });
        ToolbarDemo.views.Viewport.superclass.initComponent.apply(this, arguments);
    },
	
	userList: function(target) {
		alert('usando metodo userList');
		ToolbarDemo.views.viewport.hide({ type: 'slide' , cover: 'true' ,direction:'left'});
		ToolbarDemo.views.viewport2.show({ type: 'slide' , cover: 'true' ,direction:'left'});
		
		
		//ToolbarDemo.Viewport.items[0].show();
		//this.getTabBar().hide();
		//this.getTabBar().disable();
		//this.componentLayout.childrenChanged = true;		
		//this.doComponentLayout();
        //var direction = (target === 'usersList') ? 'right' : 'left'	;	
        //this.setActiveItem(		
        //    ToolbarDemo.views[target],
        //    { type: 'slide', direction: direction }
        //);
		//this.setActiveItem(	new ToolbarDemo.views.UsersList({id: 'usersList',iconCls: "more"}),{ type: 'slide', direction: direction }
        //);
		//ToolbarDemo.views.usersList = ToolbarDemo.views.viewport.down('#usersList');
		//this.componentLayout.childrenChanged = true;
		//this.doComponentLayout();		
		
    },
	
	reveal: function(target) {
		//ToolbarDemo.views.ViewPort.remove("usersList");
		//ToolbarDemo.views.usersList.disable();
		//this.getTabBar().show();
		//this.componentLayout.childrenChanged = true;
		
		//ToolbarDemo.views.viewport.remove(ToolbarDemo.views.usersList,true);
		//this.componentLayout.childrenChanged = false;
		//this.doComponentLayout();
		//this.doLayout();		
		//ToolbarDemo.views['usersList'].disable();
		//alert(target);
		//var holi=ToolbarDemo.views[target];
        //var direction = (target === 'usersList') ? 'right' : 'left'	;	
        this.setActiveItem(		
            ToolbarDemo.views[target]
			//target//,
            //,{}
        );
		
    },
	
	onBeforeCardSwitch: function(card, oldCard, indexCard, animated) { 
		
		if(oldCard.name=='errorlog'){
			//ToolbarDemo.views.errorlog.setBadgeText();
			ToolbarDemo.views.errorlog.setError({hide:true});
		}
		else if(card.name=='errorlog'){
			//ToolbarDemo.views.errorlog.setBadgeText();
			Ext.dispatch({
					controller: 'Home',
					action: 'send',
					caller: '',
			});
		}
		
				
		if(oldCard.name=='actualizacionLog'){
			//ToolbarDemo.views.errorlog.setBadgeText();
			ToolbarDemo.views.actualizacionLog.setError({hide:true});
		}
		else if(card.name=='actualizacionLog'){
			//ToolbarDemo.views.errorlog.setBadgeText();
			ToolbarDemo.views.actualizacionLog.actualizar();
		}
		
		
	},
});

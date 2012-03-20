ToolbarDemo.views.Viewport2 = Ext.extend(Ext.Panel, {
    fullscreen: true,
    layout: 'card',
	
    initComponent: function() {
        Ext.apply(this, {
            items: [			
                { xtype: 'ToolbarDemo.views.UsersForm', id: 'usersForm' },
				{ xtype: 'ToolbarDemo.views.Timer', id: 'timer' },				
            ],
			listeners: {
                show: function() {
					if(!this.rotate){
						//ToolbarDemo.views.usersForm.initForm();
						ToolbarDemo.views.usersForm.show();
					}
                },
                hide: function() {
					ToolbarDemo.views.usersForm.hide();
					ToolbarDemo.views.timer.init();
                },
            },
        });
        ToolbarDemo.views.Viewport2.superclass.initComponent.apply(this, arguments);
    },

    reveal: function(target) {
		
		if(target=='timer'){
			var botones=ToolbarDemo.views.timer.down('#botones');
			if(Ext.getOrientation()=='landscape'){		
				var w=botones.width;
				if(parseInt((w/window.innerWidth)*100)!=45){
					//botones.setWidth('45%');
				}								
			}else{
				var w=botones.width;
				if(parseInt((w/window.innerWidth)*100)!=68){
					//botones.setWidth('68%');
				}
			}
		}
		
		if(target==='noAnimation'){
			ToolbarDemo.views.viewport2.setActiveItem(
				ToolbarDemo.views['frontal']);			
		}
		else{
			var direction = (target === 'usersForm') ? 'right' : 'left'
			ToolbarDemo.views.viewport2.setActiveItem(
				ToolbarDemo.views[target],
				{ type: 'slide', direction: direction }
			);
		}
    }
});

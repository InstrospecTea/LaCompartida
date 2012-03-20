ToolbarDemo.views.ViewportLogin = Ext.extend(Ext.TabPanel, {
    fullscreen: true,
	layout: 'card',
	
    initComponent: function(params) {
        Ext.apply(this, {
            tabBar: {
                dock: 'bottom',
                layout: {
                    pack: 'center'
                }
            },
            items:[{ xtype: 'login', id: 'settingLogin' }],
            listeners: {
                //show: function() {
                   //ToolbarDemo.views.loginCard.show();
                //},
                //hide: function() {
                   // ToolbarDemo.views.loginCard.hide();
                //}
				beforeorientationchange:function(panel, orientation, width, height ){
				//alert('holi :D log');
					//return false;
					//alert(orientation);
					if(orientation=='landscape'){
						//ToolbarDemo.views.viewportLogin.down('#settingLogin').setScrollable('vertical');						
					}else{
						//ToolbarDemo.views.viewportLogin.down('#settingLogin').setScrollable(false);
					}
				},
				scope:this,
            },
            
        });
        ToolbarDemo.views.ViewportLogin.superclass.initComponent.apply(this, arguments);
    },	
	
});

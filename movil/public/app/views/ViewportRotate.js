ToolbarDemo.views.ViewportRotate = Ext.extend(Ext.Panel, {
    fullscreen: true,
	layout: 'card',
	cls:'rotate',
	
    initComponent: function(params) {
		
		
	
        Ext.apply(this, {
          			
			style:	{
					'background-color': '#111',
					'z-index':"100000",
					//width:'1000%',
				},
			
            items:[{ xtype: 'ToolbarDemo.views.Rotation', id: 'rotation' }],
                        
        });
        ToolbarDemo.views.ViewportRotate.superclass.initComponent.apply(this, arguments);
		
    },	
	
});

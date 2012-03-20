ToolbarDemo.views.Rotation = Ext.extend(Ext.Panel, {

	name: 'rotation',
	
    initComponent: function() {
		
		var imagen1={
			
			html:'<div class="logoLemonRotation"></div>',
			hidden:true,
			style:	{
					top: '0.1%',
				},	
		
		};
		
		var imagen2={
			
			html:'<div class="logoLemonRotation" style="background-color: #111"></div>',
			style:	{
					top: '50%',//'9.4%',
					'background-color': '#111',
				},	
		
		};
		
        Ext.apply(this, {
           			
			
			style:	{
					'background-color': '#111',
					//'z-index':"1000",
					//height:'150%',
				},
							
            items: [imagen1,imagen2],
				
			
        });
		
        ToolbarDemo.views.Rotation.superclass.initComponent.apply(this, arguments);
		
    },
});

Ext.reg('ToolbarDemo.views.Rotation', ToolbarDemo.views.Rotation);

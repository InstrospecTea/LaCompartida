ToolbarDemo.views.Searchcard = Ext.extend(Ext.Panel, {
    title: "Log",
    iconCls: "more",
    //styleHtmlContent: true,
    //html: "placeholder text",
    initComponent: function() {
	
		var calendarView = new Ext.ux.TouchCalendarView({
                        minDate: (new Date()).add(Date.DAY, -40),
                        maxDate: (new Date()).add(Date.DAY, 55),
                        mode: 'day',
                        weekStart: 0,
                        value: new Date(),
                        eventStore: eventStore,
                        
                        plugins: [new Ext.ux.TouchCalendarEvents({
                            eventBarTpl: new Ext.XTemplate('{event} - {location}')
                        })]
                    });
		
	
        Ext.apply(this, {
			items: [calendarView],
			dockedItems: [{
                            xtype: 'toolbar',
                            dock: 'top',
                            items: [{
                                xtype: 'button',
                                text: 'Month View',
                                handler: function(){
                                    calendarView.setMode('month');
                                }
                            }, {
                                xtype: 'button',
                                text: 'Week View',
                                handler: function(){
                                    calendarView.setMode('week');
                                }
                            }, {
                                xtype: 'button',
                                text: 'Day View',
                                handler: function(){
                                    calendarView.setMode('day');
								}
                            }, {
                                xtype: 'button',
                                text: 'holi',
                                handler: function(){
									calendarView.hide();
                                    //calendarView.setMode('holi');  
								}
                            }]
                        }],
            layout: 'fit',
        });
        ToolbarDemo.views.Searchcard.superclass.initComponent.apply(this, arguments);
    }
});

Ext.reg('searchcard', ToolbarDemo.views.Searchcard);

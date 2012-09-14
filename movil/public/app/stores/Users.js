ToolbarDemo.stores.users = new Ext.data.Store({
    model: 'User',    	

	autoLoad: true,
	

});


ToolbarDemo.stores.usuario = new Ext.data.Store({
    model: 'Usuario',
    autoLoad: true,
});

ToolbarDemo.stores.clienteStore = new Ext.data.Store({
    model: 'Cliente',
    autoLoad: true,
});

ToolbarDemo.stores.asuntoStore = new Ext.data.Store({
    model: 'Asunto',
    autoLoad: true,
});

ToolbarDemo.stores.logStore = new Ext.data.Store({
    model: 'Log',
    autoLoad: true,
});

ToolbarDemo.stores.updateStore = new Ext.data.Store({
    model: 'Update',
    autoLoad: true,
	//sorters: [
    //    {
    //        property : 'id',
    //       direction: 'DESC'
    //   }
	//],
});

ToolbarDemo.stores.actualizarStore = new Ext.data.Store({
    model: 'Actualizar',
    autoLoad: true,
	//sorters: [
    //    {
    //        property : 'id',
    //       direction: 'DESC'
    //   }
	//],
});
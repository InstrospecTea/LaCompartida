ToolbarDemo.stores.users = new Ext.data.Store({
    model: 'User',    	
//	proxy: {
        //type: 'ajax',
		//url: '../login',
		//url: '../intervalo',
		//url: '../index.php',
		//method: "POST",
		//params: { rut : '99511620-0' , password : 'admin.asdwsx'},
		//success:function(){
			//	alert("Success!");
			//},
			//failure:function(){
			//	alert("Error");
			//}
    //},	
	autoLoad: true,
	
	//Ext.Ajax.defaultHeaders = {
    //'Accept': 'application/json'
	//};

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
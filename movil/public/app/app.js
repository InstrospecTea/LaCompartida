ToolbarDemo = new Ext.Application({

	

	icon: 'apple-touch-icon.png',
    phoneStartupScreen: 'apple-touch-startup.png',

    name: "ToolbarDemo",

    launch: function() {
				
		ToolbarDemo.init();
    },
	
	init: function() {
	
	
		//alert('holis');
		
		//codigo que permite actualizar la pagina cada vez que se detecta un cambio en el *.manifest
		//window.applicationCache.addEventListener('updateready', function(e) {
			if (window.applicationCache.status == window.applicationCache.UPDATEREADY) {
			  // Browser downloaded a new app cache.
			  // Swap it in and reload the page to get the new hotness.
			  window.applicationCache.swapCache();
			  //if (confirm('A new version of this site is available. Load it?')) {
				window.location.reload();
			  //}
			} else {
			  // Manifest didn't changed. Nothing new to server.
			}
		//  }, false);


		
		this.views.viewportLogin = new this.views.ViewportLogin();
        this.views.viewport = new this.views.Viewport();
		this.views.viewport2 = new this.views.Viewport2();
		this.views.viewportRotate = new this.views.ViewportRotate();
		
		this.views.rotation = this.views.viewportRotate.down('#rotation');
		
		this.views.viewportRotate.hide();
		
		this.views.viewport.hide();
		//this.views.viewportLogin.hide(); 
		//this.views.viewport2.hide();
		
		//this.views.usersList = this.views.viewport2.down('#usersList');
		this.views.usersForm = this.views.viewport2.down('#usersForm');
		this.views.timer = this.views.viewport2.down('#timer');
				
		this.views.viewport2.hide();
		
		//this.views.homeCard = this.views.viewport.down('#home');
		this.views.settingsCard = this.views.viewport.down('#setting');
		this.views.loginCard = this.views.viewportLogin.down('#settingLogin');
		this.views.listCard = this.views.viewport.down('#list');
		this.views.errorlog = this.views.viewport.down('#log');
		this.views.actualizacionLog = this.views.viewport.down('#actualizacion');
		
		
		
		if(navigator.onLine){
			Ext.dispatch({controller: 'Home',action: 'setOnLine',});	
		}else{
			Ext.dispatch({controller: 'Home',action: 'setOffLine',});	
		}		
		
		if(Ext.is.Phone){
		$(document).ready(function () {
		$(window)    
          .bind('orientationchange', function(){
               if (window.orientation % 180 == 0){
                   $(document.body).css("-webkit-transform-origin", "")
                       .css("-webkit-transform", "");
										   
				  					   
				   Ext.dispatch({controller: 'Home',action: 'deRotate'});
               } 
               else {                   
                   if ( window.orientation > 0) { //clockwise
                   //  $(document.body).css("-webkit-transform-origin", "200px 190px")
                      // .css("-webkit-transform",  "translate(-2px,0px) rotate(-90deg) scale(0.94, 1.045)");
					   $(".rotate").css("-webkit-transform-origin", "0px 0px")
                         .css("-webkit-transform",  "scale(1.5,1.38)");
					   Ext.dispatch({controller: 'Home',action: 'rotate'});
                   }
                   else {
                     //(document.body).css("-webkit-transform-origin", "240px 150px")
                      // .css("-webkit-transform",  "rotate(90deg)");
					  // $(document.body).css("-webkit-transform-origin", "")
                      //   .css("-webkit-transform",  "translate(17%)"); scale(1.5,1.38)
					  $(".rotate").css("-webkit-transform-origin", "0px 0px")
                         .css("-webkit-transform",  "scale(1.5,1.38)");
					  Ext.dispatch({controller: 'Home',action: 'rotate'});
                   }
               }
           })
          .trigger('orientationchange'); 
		});}

	
		
		window.addEventListener('online', function () {
			Ext.dispatch({controller: 'Home',action: 'setOnLine',});	
		});

		window.addEventListener('offline', function () {		  
			Ext.dispatch({controller: 'Home',action: 'setOffLine',});	
		});
		
		
		if(!this.views.loginCard.down('#user').getValue()==''){
		
			//this.views.loginCard.login();
		}else{
			
		}
		
        //this.views.homecard = this.views.viewport.getComponent('home');
	}
});


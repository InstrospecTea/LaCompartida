/*********************
//* jQuery Drop Line Menu- By Dynamic Drive: http://www.dynamicdrive.com/
//* Last updated: June 27th, 09'
//* Menu avaiable at DD CSS Library: http://www.dynamicdrive.com/style/
*********************/
var $targetulanterior;
var $id_timeout;
var droplinemenu={

arrowimage: {classname: 'downarrowclass', src: '', leftpadding: 5}, //customize down arrow image
animateduration: {over: 0, out: 0}, //duration of slide in/ out animation, in milliseconds


buildmenu:function(menuid){
	jQuery(document).ready(function($){
		var $mainmenu=$("#"+menuid+">ul")
		var $headers=$mainmenu.find("ul").parent()
		$headers.each(function(i){
			var $curobj=$(this)
			var $subul=$(this).find('ul:eq(0)')
			this._dimensions={h:$curobj.find('a:eq(0)').outerHeight()}
			this.istopheader=$curobj.parents("ul").length==1? true : false
			if (!this.istopheader)
				$subul.css({left:0, top:this._dimensions.h})
			var $innerheader=$curobj.children('a').eq(0)
			$innerheader=($innerheader.children().eq(0).is('span'))? $innerheader.children().eq(0) : $innerheader //if header contains inner SPAN, use that
			/*$innerheader.append(
				'<img src="'+ droplinemenu.arrowimage.src
				+'" class="' + droplinemenu.arrowimage.classname
				+ '" style="border:0; padding-left: '+droplinemenu.arrowimage.leftpadding+'px" />'
			)*/
			$curobj.hover(
				function(e){
					var $targetul=$(this).children("ul:eq(0)")
					clearTimeout($id_timeout)
					if($targetulanterior)
					{
					$targetulanterior.slideUp(droplinemenu.animateduration.out)
					if( $targetulanterior.attr('active') != 'true' && navigator.appName != 'Microsoft Internet Explorer' ) {
																								$('li[active=true]>div>a').css('background-color','#42a62b')
																								$('li[active=true]>div>a').css('color','#FFFFFF')
																								$targetulanterior.parent().find("div>a:eq(0)").css('background-color','#E0E0E0') 
																								$targetulanterior.parent().find("div>a:eq(0)").css('color','#346700') 
																							}
					if( $targetulanterior.attr('active') != 'true' && navigator.appName == 'Microsoft Internet Explorer' ) 
																								{
																									$('li[active=true]>div>a>div.spiffyfg>span').css('background-color','#42a62b')
																									$('li[active=true]>div>a>div.spiffyfg>span').css('color','#FFFFFF')
																									$('li[active=true]>div>a>div.spiffyfg').css('background-color','#42a62b')
																									for(var i=1;i<6;i++) {
																									$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('border-left','1px solid #42a62b')
																									$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('border-right','1px solid #42a62b')
																									if( i > 2 )
																										$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('background-color','#42a62b')
																									else	
																										$('li[active=true]>div>a>b.spiffy>b.spiffy'+i+'>b.color_activo').css('background-color','#42a62b')
																									}
																									$targetulanterior.parent().find('div>a>div.spiffyfg>span').css('background-color','#E0E0E0')
																									$targetulanterior.parent().find('div>a>div.spiffyfg>span').css('color','#346700')
																									$targetulanterior.parent().find('div>a>div.spiffyfg').css('background-color','#E0E0E0')
																									for(var i=1;i<6;i++) {
																									$targetulanterior.parent().find('div>a>b.spiffy>b.spiffy'+i).css('border-left','1px solid #E0E0E0')
																									$targetulanterior.parent().find('div>a>b.spiffy>b.spiffy'+i).css('border-right','1px solid #E0E0E0')
																									if( i > 2 )
																										$targetulanterior.parent().find('div>a>b.spiffy>b.spiffy'+i).css('background-color','#E0E0E0')
																									else	
																										$targetulanterior.parent().find('div>a>b.spiffy>b.spiffy'+i+'>b.color_activo').css('background-color','#E0E0E0')
																									}
																								}
					}
					if( $targetul.attr('active') != 'true' && navigator.appName != 'Microsoft Internet Explorer') {
							$('li[active=true]>div>a').css('background-color','#E0E0E0') 
							$('li[active=true]>div>a').css('color','#346700') 
							$targetul.parent().find("div>a:eq(0)").css('background-color','#42a62b')
							$targetul.parent().find("div>a:eq(0)").css('color','#FFFFFF')
						}
					if( $targetul.attr('active') != 'true' && navigator.appName == 'Microsoft Internet Explorer' ) {
							$('li[active=true]>div>a>div.spiffyfg>span').css('background-color','#E0E0E0')
							$('li[active=true]>div>a>div.spiffyfg>span').css('color','#346700')
							$('li[active=true]>div>a>div.spiffyfg').css('background-color','#E0E0E0')
							for(var i=0;i<6;i++) {
							$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('border-left','1px solid #E0E0E0')
							$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('border-right','1px solid #E0E0E0')
							if( i > 2)
								$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('background-color','#E0E0E0')
							else	
								$('li[active=true]>div>a>b.spiffy>b.spiffy'+i+'>b.color_activo').css('background-color','#E0E0E0')
							}
							$targetul.parent().find('div>a>div.spiffyfg>span').css('background-color','#42a62b')
							$targetul.parent().find('div>a>div.spiffyfg>span').css('color','#FFFFFF')
							$targetul.parent().find('div>a>div.spiffyfg').css('background-color','#42a62b')
							for(var i=0;i<6;i++) {
							$targetul.parent().find('div>a>b.spiffy>b.spiffy'+i).css('border-left','1px solid #42a62b')
							$targetul.parent().find('div>a>b.spiffy>b.spiffy'+i).css('border-right','1px solid #42a62b')
							if( i > 2)
								$targetul.parent().find('div>a>b.spiffy>b.spiffy'+i).css('background-color','#42a62b')
							else	
								$targetul.parent().find('div>a>b.spiffy>b.spiffy'+i+'>b.color_activo').css('background-color','#42a62b')
							}
						}
					if ($targetul.queue().length<=1) //if 1 or less queued animations
						if (this.istopheader)
							$targetul.css({left: $mainmenu.offset().left, top: $mainmenu.offset().top+this._dimensions.h})
						if (document.all && !window.XMLHttpRequest) //detect IE6 or less, fix issue with overflow
							$mainmenu.find('ul').css({overflow: (this.istopheader)? 'hidden' : 'visible'})
						$targetul.slideDown(droplinemenu.animateduration.over)
				},
				function(e){
					$targetul=$(this).children("ul:eq(0)")
					$targetulanterior=$targetul
					$id_timeout=setTimeout(function() { $targetul.slideUp(droplinemenu.animateduration.out) 
																								if( $targetul.attr('active') != 'true' && navigator.appName != 'Microsoft Internet Explorer' ) 
																								{
																								$('li[active=true]>div>a').css('background-color','#42a62b')
																								$('li[active=true]>div>a').css('color','#FFFFFF')
																								$targetul.parent().find("div>a:eq(0)").css('background-color','#E0E0E0') 
																								$targetul.parent().find("div>a:eq(0)").css('color','#346700') 
																								}
																								if( $targetul.attr('active') != 'true' && navigator.appName == 'Microsoft Internet Explorer' ) 
																								{
																									$('li[active=true]>div>a>div.spiffyfg>span').css('background-color','#42a62b')
																									$('li[active=true]>div>a>div.spiffyfg>span').css('color','#FFFFFF')
																									$('li[active=true]>div>a>div.spiffyfg').css('background-color','#42a62b')
																									for(var i=1;i<6;i++) {
																									$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('border-left','1px solid #42a62b')
																									$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('border-right','1px solid #42a62b')
																									if( i > 2 )
																										$('li[active=true]>div>a>b.spiffy>b.spiffy'+i).css('background-color','#42a62b')
																									else	
																										$('li[active=true]>div>a>b.spiffy>b.spiffy'+i+'>b.color_activo').css('background-color','#42a62b')
																									}
																									$targetul.parent().find('div>a>div.spiffyfg>span').css('background-color','#E0E0E0')
																									$targetul.parent().find('div>a>div.spiffyfg>span').css('color','#346700')
																									$targetul.parent().find('div>a>div.spiffyfg').css('background-color','#E0E0E0')
																									for(var i=1;i<6;i++) {
																									$targetul.parent().find('div>a>b.spiffy>b.spiffy'+i).css('border-left','1px solid #E0E0E0')
																									$targetul.parent().find('div>a>b.spiffy>b.spiffy'+i).css('border-right','1px solid #E0E0E0')
																									if( i > 2 )
																										$targetul.parent().find('div>a>b.spiffy>b.spiffy'+i).css('background-color','#E0E0E0')
																									else	
																										$targetul.parent().find('div>a>b.spiffy>b.spiffy'+i+'>b.color_activo').css('background-color','#E0E0E0')
																									}
																								}
																							},2000)
				}
			) //end hover
		}) //end $headers.each()
		$mainmenu.find("ul").css({display:'none', visibility:'visible', width:$mainmenu.width()})
	}) //end document.ready
}
}

TouchScroll=function(node, nontouch, scroll, compat){
	this.debug =  !!nontouch;
	this.compat = !!compat;
	this.rough =  !!scroll;


	this.axisX = this.axisY = true;
	
	if (typeof node!= "object")
		node = document.getElementById(node);

	this._init();
	node.addEventListener("touchstart",this,false);
	node.addEventListener("webkitTransitionEnd",this,false);
	if (this.debug)
		node.addEventListener("mousedown",this,false);
		
		
	this.node = node;
	for (var i=0; i < node.childNodes.length; i++)
		if (node.childNodes[i].nodeType == 1){
			this.area = node.childNodes[i];
			break;
		}

	if (window.getComputedStyle(this.node).position == "static")
		this.node.style.position = "relative";
	this.area.style.cssText += "-webkit-transition: -webkit-transform; -webkit-user-select:none; -webkit-transform-style:preserve-3d;";
	this.scrolls={};
};

TouchScroll.prototype = {
	refresh:function(){
		this.node.style.webkitTransformStyle="flat";
		this.node.style.webkitTransformStyle="preserve-3d";
	},
	scrollTo:function(x,y,speed){
		this.set_matrix({e:x,f:y}, (speed||0));
	},
	onscroll:function(x,y){}, 
	handleEvent:function(ev){
		return this["ev_"+ev.type](ev);
	},
	get_matrix:function(node){
		return new WebKitCSSMatrix(window.getComputedStyle(node||this.area).webkitTransform);
	},
	set_matrix:function(value,speed,node){
		(node||this.area).style.webkitTransform = "translate("+Math.round(value.e)+"px,"+Math.round(value.f)+"px)";
		(node||this.area).style.webkitTransitionDuration= speed;
	},
	ev_touchstart:function(ev){ 
		this.ev_mousedown(ev.touches[0]);
		ev.preventDefault();
		return false;
	},
	ev_mousedown:function(ev){
		var touch  = ev;

		this.x = touch.pageX;
		this.y = touch.pageY;
		this.dx = this.node.offsetWidth;
		this.dy = this.node.offsetHeight;
		this.mx = this.area.scrollWidth;
		this.my = this.area.scrollHeight;
		this.target = touch.target;
		
		if (!this.rough){
			var temp = this.get_matrix();
			this.target_x = temp.e;
			this.target_y = temp.f;
			if (!this.scroll && this.compat){
				temp.e = this.node.scrollLeft*-1;
				temp.f = this.node.scrollTop*-1;
				this.node.scrollTop = this.node.scrollLeft = 0;
			} 
			
			this.set_matrix(temp,0);
			this._correct_scroll(this.target_x, this.target_y);
		}
		this.scroll_x = this.scroll_y = this.scroll = false;		
		
		
		this._init_events();
	},
	ev_touchend:function(){
		return this.ev_mouseup();
	},
	ev_mouseup:function(){
		this._deinit_events();
		if (!this.scroll){
			this._remove_scroll();
			var ev = document.createEvent("MouseEvent");
			ev.initMouseEvent("click",true, true);
			this.target.dispatchEvent(ev);
		} 
		this.target = null;
	},
	ev_webkitTransitionEnd:function(){
		if (this.target || !this.scroll) return;
		
		this._remove_scroll();
		var temp = this.get_matrix();
		if (this.compat && (temp.e||temp.f)){ 
			var y = temp.f; var x = temp.e;
			temp.e = temp.f = 0;
			this.set_matrix(temp,0);
			
			this.node.scrollTop = -1*y;
			this.node.scrollLeft = -1*x;
		}
		
		this.scroll = false;
	},
	ev_touchmove:function(ev){
		return this.ev_mousemove(ev.touches[0]);
	},
	ev_mousemove:function(ev){
		if (!this.target) return;
		var touch = ev;
		
		var dx = (touch.pageX - this.x)*(this.axisX?5:0);//Math.min(3,this.mx/this.dx);
		var dy = (touch.pageY - this.y)*(this.axisY?5:0);//Math.min(3,this.my/this.dy);
		
		if (Math.abs(dx)<10 && Math.abs(dy)<10) return;
		
		if (Math.abs(dx)>50)
			this.scroll_x=true;
		if (Math.abs(dy)>50)
			this.scroll_y=true;
			
		
		if (this.scroll_x || this.scroll_y){
			this.x = touch.pageX; this.y = touch.pageY;
			this.scroll = true;
			var temp = this.get_matrix();
			dx = dx + (this.target_x - temp.e);
			dy = dy + (this.target_y - temp.f);
			
			var speed = "2000ms";
			var fast = "500ms";
			this.target_x = dx+temp.e;
			this.target_y = dy+temp.f;
			
			if (this.target_x > 0) {
				this.target_x = 0;
				speed = fast;
			}
			if (this.target_y > 0) {
				this.target_y = 0;
				speed = fast;
			}
			if (this.mx - this.dx + this.target_x < 0){
				this.target_x = - this.mx + this.dx;
				speed = fast;
			}
			if (this.my - this.dy + this.target_y < 0){
				this.target_y = - this.my + this.dy;
				speed = fast;
			}
		

			this.set_matrix({e:this.target_x,f:this.target_y},speed);
			this._add_scroll(temp.e, temp.f);
			this._correct_scroll(this.target_x, this.target_y, speed);
			this.onscroll(this.target_x, this.target_y);
		}
		return false;
	},
	_correct_scroll:function(x,y,speed){ 
		if (this.scrolls.x){
			var stemp = this.get_matrix(this.scrolls.x);
			var sx = this.dx*x/this.mx;
			this.set_matrix({e:-1*sx,f:0}, speed, this.scrolls.x);
		}
		if (this.scrolls.y){ 
			var stemp = this.get_matrix(this.scrolls.y);
			var sy = this.dy*y/this.my;
			this.set_matrix({e:0,f:-1*sy}, speed, this.scrolls.y);				
		}		
	},
	_remove_scroll:function(){
		if (this.scrolls.x)
			this.scrolls.x.parentNode.removeChild(this.scrolls.x);
		if (this.scrolls.y)	
			this.scrolls.y.parentNode.removeChild(this.scrolls.y);
		this.scrolls = {};
	},
	_add_scroll:function(){
		if (this.scrolls.ready) return;
		
		var d;
		if (this.my>5 && this.axisY){
			var h = this.dy*this.dy/this.my-1;
			this.scrolls.y = d = document.createElement("DIV");
			d.className="dhx_scroll_y";
			d.style.height = h +"px";
			this.node.appendChild(d);
		}
		if (this.mx>5 && this.axisX){
			var h = this.dx*this.dx/this.mx;
			this.scrolls.x = d = document.createElement("DIV");
			d.className="dhx_scroll_x";
			d.style.width = h +"px";
			this.node.appendChild(d);
		}
		
		var temp = this.get_matrix();
		this._correct_scroll(temp.e, temp.f, 0);
		this.scrolls.ready = true;
	},
	_init_events:function(){
		document.addEventListener("touchmove",this,false);	
		document.addEventListener("touchend",this,false);	
		if (this.debug){
			document.addEventListener("mousemove",this,false);	
			document.addEventListener("mouseup",this,false);	
		}
	},
	_deinit_events:function(){
		document.removeEventListener("touchmove",this,false);	
		document.removeEventListener("touchend",this,false);	
		if (this.debug){
			document.removeEventListener("mousemove",this,false);	
			document.removeEventListener("mouseup",this,false);	
		}
	},
	_init:function(){
		document.styleSheets[0].insertRule(".dhx_scroll_x { width:50px;height:4px;background:rgba(0, 0, 0, 0.4);position:absolute; left:0px; bottom:3px; border:1px solid transparent; -webkit-border-radius:4px;-webkit-transition: -webkit-transform;}",0);
		document.styleSheets[0].insertRule(".dhx_scroll_y { width:4px;height:50px;background:rgba(0, 0, 0, 0.4);position:absolute; top:0px; right:3px; border:1px solid transparent; -webkit-border-radius:4px;-webkit-transition: -webkit-transform;}",0);
		this._init = function(){};
	}
};
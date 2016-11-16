scheduler.templates.calendar_month =  scheduler.date.date_to_str("%F %Y");
scheduler.templates.calendar_scale_date =  scheduler.date.date_to_str("%D");

scheduler.renderCalendar=function(obj){
	date = date||(new Date());
	var cont = obj.container;
	var pos = obj.position;
	var date = obj.date;
	
	if (typeof cont == "string")
		cont = document.getElementById(cont);
	if (typeof date == "string")
		date = this.templates.api_date(date);

	if (typeof pos == "string")
		pos = document.getElementById(pos);
	if (pos && (typeof pos.left == "undefined")){
		var tpos = getOffset(pos);
		pos = { 
			top:tpos.top + pos.offsetHeight,
			left:tpos.left
		};
	};
	if (!cont)
		cont = scheduler._get_def_cont(pos);
	
		
	var cal = this._render_calendar(cont,date,obj);
	var start = scheduler.date.month_start(date);
	var end   = scheduler.date.add(start,1,"month");
	var evs = this.getEvents(start,end);
	for (var i=0; i < evs.length; i++){
		var ev = evs[i];
		var d = ev.start_date;
		if (d.valueOf()<start.valueOf()) 
			d = start;
		while (d<ev.end_date){
			this.markCalendar(cal,d,"dhx_year_event");
			d = this.date.add(d,1,"day");
			if (d.valueOf()>=end.valueOf()) 
				break;
		}
	}
	cal.onclick = function(e){
		e = e||event;
		var src = e.target||e.srcElement;
		
		if (src.className.indexOf("dhx_month_head")!=-1){
			var pname = src.parentNode.className;
			if (pname !="dhx_after" && pname!="dhx_before"){
				var newdate = scheduler.templates.xml_date(this.getAttribute("date"));
				newdate.setDate(parseInt(src.innerHTML,10));
				scheduler.unmarkCalendar(this);
				scheduler.markCalendar(this,newdate,"dhx_calendar_click")
				this._last_date=newdate;
				if (obj.handler) obj.handler.call(scheduler, newdate, this);
			}
		}
	}
	return cal;
};
scheduler._get_def_cont = function(pos){
	if (!this._def_count){
		this._def_count = document.createElement("DIV");
		this._def_count.style.cssText = "position:absolute;z-index:10100;width:251px; height:175px;";
		this._def_count.onclick = function(e){ (e||event).cancelBubble = true; };
		document.body.appendChild(this._def_count);
	}
		
	this._def_count.style.left = pos.left+"px";
	this._def_count.style.top  = pos.top+"px";
	this._def_count._created = new Date();
	
	return this._def_count;
};
scheduler._locateCalendar=function(cal,date){
	var table=cal.childNodes[2].childNodes[0];
	if (typeof date == "string")
		date = scheduler.templates.api_date(date);
		
	var d  = cal.week_start+date.getDate()-1;
	return table.rows[Math.floor(d/7)].cells[d%7].firstChild;
}
scheduler.markCalendar=function(cal,date,css){
	this._locateCalendar(cal,date).className+=" "+css;
}
scheduler.unmarkCalendar=function(cal,date,css){
	date=date||cal._last_date;
	css=css||"dhx_calendar_click";
	if (!date) return;
	var el = this._locateCalendar(cal,date);
	el.className= (el.className||"").replace(RegExp(css,"g"));
}
scheduler._week_template=function(width){
	var summ = (width || 250);
	var left = 0;

	var week_template = document.createElement("div");
	var dummy_date = this.date.week_start(new Date());
	for (var i=0; i<7; i++){
		this._cols[i]=Math.floor(summ/(7-i));
		this._render_x_header(i,left,dummy_date,week_template);
		dummy_date = this.date.add(dummy_date,1,"day");
		summ-=this._cols[i];
		left+=this._cols[i];
	}
	week_template.lastChild.className+=" dhx_scale_bar_last";
	return week_template;
}
scheduler._render_calendar=function(obj,sd,conf){
	/*store*/ var temp = this._cols; this._cols=[]; var temp2 = this._mode; this._mode = "calendar"; var temp3 = this._colsS; this._colsS = {height:0};
			  var temp4 = new Date(this._min_date); var temp5 = new Date(this._max_date); var temp6 = new Date(scheduler._date);
	
	sd = this.date.month_start(sd);
	var week_template = this._week_template(obj.offsetWidth-1);
	
	var d = document.createElement("DIV");
	d.className="dhx_cal_container dhx_mini_calendar";
	d.setAttribute("date",this.templates.xml_format(sd));
	d.innerHTML="<div class='dhx_year_month'></div><div class='dhx_year_week'>"+week_template.innerHTML+"</div><div class='dhx_year_body'></div>";
	
	d.childNodes[0].innerHTML=this.templates.calendar_month(sd);
	if (conf.navigation){
	var arrow = document.createElement("DIV");				
		arrow.className = "dhx_cal_prev_button";
		arrow.style.cssText="left:1px;top:2px;position:absolute;"
		arrow.innerHTML = "&nbsp;"				
		d.firstChild.appendChild(arrow);
		arrow.onclick=function(){
			conf.date = scheduler.date.add(d._date, -1, "month");
			scheduler.destroyCalendar(d);
			scheduler.renderCalendar(conf);
		}
		
		arrow = document.createElement("DIV");
		arrow.className = "dhx_cal_next_button";
		arrow.style.cssText="left:auto; right:1px;top:2px;position:absolute;"
		arrow.innerHTML = "&nbsp;"		
		d.firstChild.appendChild(arrow);
		arrow.onclick=function(){
			conf.date = scheduler.date.add(d._date, 1, "month");
			scheduler.destroyCalendar(d);
			scheduler.renderCalendar(conf);
		}
		d._date = new Date(sd);
	}
	
	d.week_start = (sd.getDay()-(this.config.start_on_monday?1:0)+7)%7;
	
	var dd = this.date.week_start(sd);
	this._reset_month_scale(d.childNodes[2],sd,dd);
	
	var r=d.childNodes[2].firstChild.rows;
	for (var k=r.length; k<6; k++) {
		r[0].parentNode.appendChild(r[0].cloneNode(true));
		for (var ri=0; ri < r[k].childNodes.length; ri++) {
		   r[k].childNodes[ri].className = "dhx_after";
		};
	}
	
	obj.appendChild(d);
	
	/*restore*/ this._cols=temp; this._mode = temp2; this._colsS = temp3; this._min_date=temp4; this._max_date=temp5; scheduler._date = temp6;
	return d;
};
scheduler.destroyCalendar=function(cal){
	if (!cal && this._def_count && this._def_count.firstChild){
		if ((new Date()).valueOf() - this._def_count._created.valueOf() > 500)
			cal  = this._def_count.firstChild;
	}
	if (!cal) return;
	cal.onclick=null;
	cal.innerHTML="";
	if (cal.parentNode)
		cal.parentNode.removeChild(cal);
	if (this._def_count)
		this._def_count.style.top = "-1000px";
};
scheduler.isCalendarVisible=function(){
	if (this._def_count && parseInt(this._def_count.style.top) > 0 )
		return this._def_count;
	return false;
};
scheduler.attachEvent("onTemplatesReady",function(){
	dhtmlxEvent(document.body, "click", function(){ scheduler.destroyCalendar(); });
});

scheduler.templates.calendar_time = scheduler.date.date_to_str("%d-%m-%Y");

scheduler.form_blocks.calendar_time={
	render:function(){
		var html = "<input class='dhx_readonly' type='text' readonly='true'>";
		
		var cfg = scheduler.config;
		var dt = this.date.date_part(new Date());
		if (cfg.first_hour)
			dt.setHours(cfg.first_hour);
			
		html+=" <select>";
		for (var i=60*cfg.first_hour; i<60*cfg.last_hour; i+=this.config.time_step*1){
			var time=this.templates.time_picker(dt);
			html+="<option value='"+i+"'>"+time+"</option>";
			dt=this.date.add(dt,this.config.time_step,"minute");
		}
		html+="</select>";
		
		
		return "<div style='height:30px; padding-top:0px; font-size:inherit;' class='dhx_cal_lsection'>"+html+"<span style='font-weight:normal; font-size:10pt;'> &nbsp;&ndash;&nbsp; </span>"+html+"</div>";
	},
	_init_once:function(inp,date){
		inp.onclick = function(){
			scheduler.renderCalendar({
				position:inp, 
				date:this._date,
				navigation:true,
				handler:function(new_date){
					inp.value = scheduler.templates.calendar_time(new_date);
					inp._date = new Date(new_date);
					scheduler.destroyCalendar();
				}
			})
		};
	},
	set_value:function(node,value,ev){
		function _attach_action(inp, date){
			scheduler.form_blocks.calendar_time._init_once(inp,date);
			inp.value = scheduler.templates.calendar_time(date);
			inp._date = new Date(date);
		};

		var s=node.getElementsByTagName("input");		
		_attach_action(s[0],ev.start_date);
		_attach_action(s[1],ev.end_date);
		scheduler.form_blocks.calendar_time._init_once = function(){};
		
		var s=node.getElementsByTagName("select");		
		s[0].value=ev.start_date.getHours()*60+ev.start_date.getMinutes();
		s[1].value=ev.end_date.getHours()*60+ev.end_date.getMinutes();
		
	},
	get_value:function(node,ev){
		s=node.getElementsByTagName("input");
		ev.start_date = s[0]._date;
		ev.end_date = s[1]._date;
		if (ev.end_date<=ev.start_date) 
			ev.end_date=scheduler.date.add(ev.start_date,scheduler.config.time_step,"minute");
	},
	focus:function(node){
	}
};

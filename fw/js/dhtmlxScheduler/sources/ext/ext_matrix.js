(function(){
scheduler.matrix = {};
scheduler.createTimelineView=function(obj){
	var merge=function(a,b){
		for (var c in b)
			if (typeof a[c] == "undefined")
				a[c]=b[c];
	}
	
	merge(obj,{
		name:"matrix",
		x:"time",
		y:"time",
		x_step:1,
		x_unit:"hour",
		y_unit:"day",
		y_step:1,
		x_start:0,
		x_size:24,
		y_start:0,
		y_size:	7,
		render:"cell",
		dx:200,
		dy:50
	});
	
	//init custom wrappers
	scheduler[obj.name+"_view"]=function(){
		scheduler.renderMatrix.apply(obj, arguments);
	};
	
	var old = scheduler.render_data;
	scheduler.render_data=function(evs){
		if (this._mode == obj.name)
   			set_full_view.call(obj,true);
   		else
   			return old.apply(this,arguments);
	};
	
	scheduler.attachEvent("onOptionsLoad",function(){
		obj.order = {};
		for(var i=0; i<obj.y_unit.length;i++)
			obj.order[obj.y_unit[i].key]=i;
		if (scheduler._date) 
			scheduler.setCurrentView(scheduler._date, scheduler._mode);
	});
	scheduler.callEvent("onOptionsLoad",[]);
	
	
	scheduler.matrix[obj.name]=obj;
	scheduler.templates[obj.name+"_cell_value"] = function(ar){ return ar?ar.length:""; };
	scheduler.templates[obj.name+"_cell_class"] = function(ar){ return ""; };
	scheduler.templates[obj.name+"_scalex_class"] = function(ar){ return ""; };
	scheduler.templates[obj.name+"_tooltip"] = function(a,b,e){ return e.text; };
	scheduler.templates[obj.name+"_date"] = function(start,end){
		return scheduler.templates.week_date(start,end);
	};
	
	scheduler.templates[obj.name+"_scale_date"] = scheduler.date.date_to_str(obj.x_date||scheduler.config.hour_date);
	scheduler.date[obj.name+"_start"]=scheduler.date.day_start;
	scheduler.date["add_"+obj.name]=function(a,b,c){
		return scheduler.date.add(a,(obj.x_length||obj.x_size)*b*obj.x_step,obj.x_unit);
	};
	
	scheduler.attachEvent("onSchedulerResize",function(){
		if (this._mode == obj.name){
			set_full_view.call(obj,true);
			return false;
		}
		return true;
	});
	scheduler.attachEvent("onBeforeDrag",function(){
		return  this._mode != obj.name;
	});
};


	
function trace_events(){
	//minimize event set
	var evs = scheduler.getEvents(scheduler._min_date, scheduler._max_date);
	var matrix =[];
	for (var i=0; i < this.y_unit.length; i++) 
		matrix[i]=[];
	
		
	//next code defines row for undefined key
	//most possible it is an artifact of incorrect configuration
	if (!matrix[y])
		matrix[y]=[];
	
	for (var i=0; i < evs.length; i++) {
		var y = this.order[evs[i][this.y_property]];
		var x = 0; 
		while (this._trace_x[x+1] && evs[i].start_date>=this._trace_x[x+1]) x++;
		while (this._trace_x[x] && evs[i].end_date>this._trace_x[x]) {
			if (!matrix[y][x]) matrix[y][x]=[];
			matrix[y][x].push(evs[i]);
			x++;
		}
	};
	return matrix;
};

	

function y_scale(d){ 
	var html = "<table style='table-layout:fixed;' cellspacing='0' cellpadding='0'>";
	var evs=[];
	if (this.render == "cell")
		evs = trace_events.call(this);
	else {
		var tevs = scheduler.getEvents(scheduler._min_date, scheduler._max_date);
		for (var j=0; j<tevs.length; j++){
			var ind =  this.order[ tevs[j][this.y_property] ];
			if (!evs[ind]) evs[ind] = [];
			evs[ind].push(tevs[j]);
		}
	}
		
	var summ = 0; 
	for (var i=0; i < scheduler._cols.length; i++)
		summ+=scheduler._cols[i];
	var step = new Date();
	
	step = (scheduler.date.add(step, this.x_step*this.x_size, this.x_unit)-step)/summ;
	
	//autosize height, if we have a free space
	var height = this.dy;
	if (this.y_unit.length*height < d.offsetHeight)
		height = Math.floor((d.offsetHeight -1 )/this.y_unit.length);
		
	for (var i=0; i<this.y_unit.length; i++){
		html+="<tr style='height:"+height+"px'><td class='dhx_matrix_scell' style='width:"+(this.dx-1)+"px'>"+this.y_unit[i].label+"</td>";
		if (this.render == "cell"){
			for (var j=0; j < scheduler._cols.length; j++)
			html+="<td class='dhx_matrix_cell "+scheduler.templates[this.name+"_cell_class"](evs[i][j],this._trace_x[j],this.y_unit[i])+"' style='width:"+(scheduler._cols[j]-1)+"px'><div  style='width:"+(scheduler._cols[j]-1)+"px'>"+scheduler.templates[this.name+"_cell_value"](evs[i][j])+"<div></td>";
		} else {
			html+="<td><div style='width:"+summ+"px; height:"+height+"px; position:relative;' class='dhx_matrix_line'>";
			if (evs[i]){
				evs[i].sort(function(a,b){ return a.start_date>b.start_date?1:-1; });
				var stack=[]; 
				for (var j=0; j<evs[i].length; j++){
					var ev = evs[i][j];
					//get line in stack
					var stack_pointer = 0;
					while (stack[stack_pointer] && stack[stack_pointer].end_date > ev.start_date )
						stack_pointer++;
					stack[stack_pointer]=ev;
					//render line
					
					var x=Math.max(0,(ev.start_date - scheduler._min_date)/step)*0.995;
					var x2=Math.min(summ,(ev.end_date - scheduler._min_date)/step);
					x2 =  x2*1.005 - 0.005*summ; //quite mad heuristic
					
					var hb = scheduler.xy.bar_height;
					var y=2+stack_pointer*hb; 
					
				
					var cs = scheduler.templates.event_class(ev.start_date,ev.end_date,ev);
					cs = "dhx_cal_event_line "+(cs||"");
		
					html+='<div event_id="'+ev.id+'" class="'+cs+'" style="position:absolute; top:'+y+'px; left:'+x+'px; width:'+Math.max(0,x2-x-15)+'px;'+(ev._text_style||"")+'">'+scheduler.templates.event_bar_text(ev.start_date,ev.end_date,ev)+'</div>';
				}
			}
			html+="<table cellpadding='0' cellspacing='0' style='width:"+summ+"px; height:"+height+"px'>";
			for (var j=0; j < scheduler._cols.length; j++)
				html+="<td class='dhx_matrix_cell "+scheduler.templates[this.name+"_cell_class"](evs[i],this._trace_x[j],this.y_unit[i])+"' style='width:"+(scheduler._cols[j]-1)+"px'><div  style='width:"+(scheduler._cols[j]-1)+"px'><div></td>";
			html+="</table>";			
			html+="</div></td>";	
		}
		html+="</tr>";
	}
	html += "</table>";
	this._matrix = evs;
	d.innerHTML = html;
}
function x_scale(h){
	h.innerHTML = "<div></div>"; h=h.firstChild;
	
	scheduler._cols=[];	//store for data section
	scheduler._colsS={height:0};
	this._trace_x =[];
	
	
	if (this.x_start)
		scheduler._min_date = scheduler.date.add(scheduler._min_date,this.x_start*this.x_step, this.x_unit);
		
	var start = scheduler._min_date;
	var summ = scheduler._x-this.dx-18; //border delta

	var left = this.dx;
	
	for (var i=0; i<this.x_size; i++){
		scheduler._cols[i]=Math.floor(summ/(this.x_size-i));
		this._trace_x[i]=new Date(start);
		
		scheduler._render_x_header(i, left, start, h);
		
		var cs = scheduler.templates[this.name+"_scalex_class"](start);
		if (cs)	
			h.lastChild.className += " "+cs;
			
		start = scheduler.date.add(start, this.x_step, this.x_unit);
		
		summ-=scheduler._cols[i];
		left+=scheduler._cols[i];
	}
	
	var trace = this._trace_x;
	h.onclick = function(e){
		var pos = locate_hcell(e);
		if (pos)
			scheduler.callEvent("onXScaleClick",[pos.x, trace[pos.x]]);
	};
	h.ondblclick = function(e){
		var pos = locate_hcell(e);
		if (pos)
			scheduler.callEvent("onXScaleDblClick",[pos.x, trace[pos.x]]);
	};
}
function set_full_view(mode){
	if (mode){	
		scheduler.set_sizes();
		_init_matrix_tooltip();
		//we need to have day-rounded scales for navigation
		//in same time, during rendering scales may be shifted
		var temp = scheduler._min_date;
			x_scale.call(this,scheduler._els["dhx_cal_header"][0]);
			y_scale.call(this,scheduler._els["dhx_cal_data"][0]);
		scheduler._min_date = temp;
		
		scheduler._els["dhx_cal_date"][0].innerHTML=scheduler.templates[this.name+"_date"](scheduler._min_date, scheduler._max_date);
		scheduler._table_view=true;
	}
};


function hideToolTip(){ 
	if (scheduler._tooltip){
		scheduler._tooltip.style.display = "none";
		scheduler._tooltip.date = "";
	}
};
function showToolTip(obj,pos,offset){ 
	if (obj.render != "cell") return;
	var mark = pos.x+"_"+pos.y;		
	var evs = obj._matrix[pos.y][pos.x];
	
	if (!evs) return hideToolTip();
	if (scheduler._tooltip){
		if (scheduler._tooltip.date == mark) return;
		scheduler._tooltip.innerHTML="";
	} else {
		var t = scheduler._tooltip = document.createElement("DIV");
		t.className = "dhx_tooltip";
		document.body.appendChild(t);
		t.onclick = scheduler._click.dhx_cal_data;
	}
	
	
	
	var html = "";
   
	for (var i=0; i<evs.length; i++){
		html+="<div class='dhx_tooltip_line' event_id='"+evs[i].id+"'>"
		html+="<div class='dhx_tooltip_date'>"+(evs[i]._timed?scheduler.templates.event_date(evs[i].start_date):"")+"</div>";
		html+="<div class='dhx_event_icon icon_details'>&nbsp;</div>";
		html+=scheduler.templates[obj.name+"_tooltip"](evs[i].start_date, evs[i].end_date,evs[i])+"</div>";
   }
   
	scheduler._tooltip.style.display="";   
	scheduler._tooltip.style.top = "0px";
   
	if (document.body.offsetWidth-offset.left-scheduler._tooltip.offsetWidth < 0)
		scheduler._tooltip.style.left = offset.left-scheduler._tooltip.offsetWidth+"px";
	else
		scheduler._tooltip.style.left = offset.left+pos.src.offsetWidth+"px";
      
	scheduler._tooltip.date = mark;
	scheduler._tooltip.innerHTML = html;
   
	if (document.body.offsetHeight-offset.top-scheduler._tooltip.offsetHeight < 0)
		scheduler._tooltip.style.top= offset.top-scheduler._tooltip.offsetHeight+pos.src.offsetHeight+"px";
	else
		scheduler._tooltip.style.top= offset.top+"px";
};

function _init_matrix_tooltip(){
	dhtmlxEvent(scheduler._els["dhx_cal_data"][0], "mouseover", function(e){
		var obj = scheduler.matrix[scheduler._mode];
		if (obj){
			var pos = locate_cell(e);
			var e = e || event;
			var src = e.target||e.srcElement;
			if (pos)
				return showToolTip(obj,pos,getOffset(pos.src));
		}
		hideToolTip();
	});
   _init_matrix_tooltip=function(){};
}

scheduler.renderMatrix = function(mode){
	scheduler._min_date = scheduler.date[this.name+"_start"](scheduler._date);
	scheduler._max_date = scheduler.date.add(scheduler._min_date, 1, this.name);
	
	scheduler._table_view = true;
	set_full_view.call(this,mode);
};

function html_index(el) {
	var p = el.parentNode.childNodes;
	for (var i=0; i < p.length; i++) 
		if (p[i] == el) return i;
	return -1;
};
function locate_hcell(e){
	e = e||event;
	var trg = e.target?e.target:e.srcElement;
	while (trg && trg.tagName != "DIV")
		trg=trg.parentNode;
	if (trg && trg.tagName == "DIV"){
		var cs = trg.className.split(" ")[0];
		if (cs == "dhx_scale_bar")
			return { x:html_index(trg), y:-1, src:trg, scale:true };
	}
};
function locate_cell(e){
	e = e||event;
	var trg = e.target?e.target:e.srcElement;
	while (trg && trg.tagName != "TD")
		trg=trg.parentNode
	if (trg && trg.tagName == "TD"){
		var cs = trg.className.split(" ")[0];
		
		if (cs == "dhx_matrix_cell")
			return { x:trg.cellIndex-1, y:trg.parentNode.rowIndex, src:trg};
		else if (cs == "dhx_matrix_scell")
			return { x:-1, y:trg.parentNode.rowIndex, src:trg, scale:true };
	}
	return false;
};

var old_click = scheduler._click.dhx_cal_data;
scheduler._click.dhx_cal_data = function(e){
	var ret = old_click.apply(this,arguments);
	var obj = scheduler.matrix[scheduler._mode];
	if (obj){
		var pos = locate_cell(e);
		if (pos){
			if (pos.scale)
				scheduler.callEvent("onYScaleClick",[pos.y, obj.y_unit[pos.y]]);
			else
				scheduler.callEvent("onCellClick",[pos.x,pos.y, obj._trace_x[pos.x], (((obj._matrix[pos.y]||{})[pos.x])||[])]);
		}
	}
	return ret;
};

scheduler.dblclick_dhx_matrix_cell = function(e){
	var obj = scheduler.matrix[scheduler._mode];
	if (obj){
		var pos = locate_cell(e);
		if (pos){
			if (pos.scale)
				scheduler.callEvent("onYScaleDblClick",[pos.y, obj.y_unit[pos.y]]);
			else
				scheduler.callEvent("onCellDblClick",[pos.x,pos.y, obj._trace_x[pos.x], (((obj._matrix[pos.y]||{})[pos.x])||[])]);
		}
	}
};
scheduler.dblclick_dhx_matrix_scell = function(e){
	return scheduler.dblclick_dhx_matrix_cell(e);
};

})();
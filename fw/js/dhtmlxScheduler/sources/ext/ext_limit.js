scheduler.config.limit_start = new Date(-3999,0,0);
scheduler.config.limit_end   = new Date( 3999,0,0);
scheduler.config.limit_view  = false;

(function(){
	var before = null;
	
	scheduler.attachEvent("onBeforeViewChange",function(om,od,nm,nd){
		nd = nd||od; nm = nm||om;
		if (scheduler.config.limit_view){
			if (nd.valueOf()>scheduler.config.limit_end.valueOf() || this.date.add(nd,1,nm)<=scheduler.config.limit_start.valueOf()){
				setTimeout(function(){
					scheduler.setCurrentView(scheduler._date, nm);
				},1);
				return false;
			}
		}
		return true;
	});
	var blocker = function(ev){
		var c = scheduler.config;
		var res = (ev.start_date.valueOf() >= c.limit_start.valueOf() && ev.end_date.valueOf() <= c.limit_end.valueOf());
		if (!res) scheduler.callEvent("onLimitViolation",[ev.id, ev]);
		return res;
	};
	
	scheduler.attachEvent("onBeforeDrag",function(id){
		if (!id) return true;
		return blocker(scheduler.getEvent(id));
	});
	scheduler.attachEvent("onBeforeLightbox",function(id){
		var ev = scheduler.getEvent(id);
		before = [ev.start_date, ev.end_date];
		return true;
	});	
	scheduler.attachEvent("onEventAdded",function(id){
		if (!id) return true;
		var ev = scheduler.getEvent(id);
		if (!blocker(ev)){
			if (ev.start_date < scheduler.config.limit_start)
				ev.start_date = new Date(scheduler.config.limit_start);
			if (ev.end_date > scheduler.config.limit_end)
				ev.end_date = new Date(scheduler.config.limit_end);
		}
		return true;
	});
	scheduler.attachEvent("onEventChanged",function(id){
		if (!id) return true;
		var ev = scheduler.getEvent(id);
		if (!blocker(ev)){
			if (!before) return false;
			ev.start_date = before[0];
			ev.end_date = before[1];
			ev._timed=this.is_one_day_event(ev);
		};
		return true;
	});
	scheduler.attachEvent("onBeforeEventChanged",function(ev){
		return blocker(ev);
	});

})();
	

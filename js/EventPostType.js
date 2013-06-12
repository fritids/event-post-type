/*! ===========================================================
 * bootstrap-tooltip.js v2.0.4
 * http://twitter.github.com/bootstrap/javascript.html#tooltips
 * Inspired by the original jQuery.tipsy by Jason Frame
 * ===========================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */
!function($){var Tooltip=function(element,options){this.init("tooltip",element,options);};Tooltip.prototype={constructor:Tooltip,init:function(type,element,options){var eventIn,eventOut;this.type=type;this.$element=$(element);this.options=this.getOptions(options);this.enabled=true;if(this.options.trigger!="manual"){eventIn=this.options.trigger=="hover"?"mouseenter":"focus";eventOut=this.options.trigger=="hover"?"mouseleave":"blur";this.$element.on(eventIn,this.options.selector,$.proxy(this.enter,this));this.$element.on(eventOut,this.options.selector,$.proxy(this.leave,this));}this.options.selector?(this._options=$.extend({},this.options,{trigger:"manual",selector:""})):this.fixTitle();},getOptions:function(options){options=$.extend({},$.fn[this.type].defaults,options,this.$element.data());if(options.delay&&typeof options.delay=="number"){options.delay={show:options.delay,hide:options.delay};}return options;},enter:function(e){var self=$(e.currentTarget)[this.type](this._options).data(this.type);if(!self.options.delay||!self.options.delay.show){return self.show();}clearTimeout(this.timeout);self.hoverState="in";this.timeout=setTimeout(function(){if(self.hoverState=="in"){self.show();}},self.options.delay.show);},leave:function(e){var self=$(e.currentTarget)[this.type](this._options).data(this.type);if(this.timeout){clearTimeout(this.timeout);}if(!self.options.delay||!self.options.delay.hide){return self.hide();}self.hoverState="out";this.timeout=setTimeout(function(){if(self.hoverState=="out"){self.hide();}},self.options.delay.hide);},show:function(){var $tip,inside,pos,actualWidth,actualHeight,placement,tp;if(this.hasContent()&&this.enabled){$tip=this.tip();this.setContent();if(this.options.animation){$tip.addClass("fade");}placement=typeof this.options.placement=="function"?this.options.placement.call(this,$tip[0],this.$element[0]):this.options.placement;inside=/in/.test(placement);$tip.remove().css({top:0,left:0,display:"block"}).appendTo(inside?this.$element:document.body);pos=this.getPosition(inside);actualWidth=$tip[0].offsetWidth;actualHeight=$tip[0].offsetHeight;switch(inside?placement.split(" ")[1]:placement){case"bottom":tp={top:pos.top+pos.height,left:pos.left+pos.width/2-actualWidth/2};break;case"top":tp={top:pos.top-actualHeight,left:pos.left+pos.width/2-actualWidth/2};break;case"left":tp={top:pos.top+pos.height/2-actualHeight/2,left:pos.left-actualWidth};break;case"right":tp={top:pos.top+pos.height/2-actualHeight/2,left:pos.left+pos.width};break;}$tip.css(tp).addClass(placement).addClass("in");}},isHTML:function(text){return typeof text!="string"||(text.charAt(0)==="<"&&text.charAt(text.length-1)===">"&&text.length>=3)||/^(?:[^<]*<[\w\W]+>[^>]*$)/.exec(text);},setContent:function(){var $tip=this.tip(),title=this.getTitle();$tip.find(".tooltip-inner")[this.isHTML(title)?"html":"text"](title);$tip.removeClass("fade in top bottom left right");},hide:function(){var that=this,$tip=this.tip();$tip.removeClass("in");function removeWithAnimation(){var timeout=setTimeout(function(){$tip.off($.support.transition.end).remove();},500);$tip.one($.support.transition.end,function(){clearTimeout(timeout);$tip.remove();});}$.support.transition&&this.$tip.hasClass("fade")?removeWithAnimation():$tip.remove();},fixTitle:function(){var $e=this.$element;if($e.attr("title")||typeof($e.attr("data-original-title"))!="string"){$e.attr("data-original-title",$e.attr("title")||"").removeAttr("title");}},hasContent:function(){return this.getTitle();},getPosition:function(inside){return $.extend({},(inside?{top:0,left:0}:this.$element.offset()),{width:this.$element[0].offsetWidth,height:this.$element[0].offsetHeight});},getTitle:function(){var title,$e=this.$element,o=this.options;title=$e.attr("data-original-title")||(typeof o.title=="function"?o.title.call($e[0]):o.title);return title;},tip:function(){return this.$tip=this.$tip||$(this.options.template);},validate:function(){if(!this.$element[0].parentNode){this.hide();this.$element=null;this.options=null;}},enable:function(){this.enabled=true;},disable:function(){this.enabled=false;},toggleEnabled:function(){this.enabled=!this.enabled;},toggle:function(){this[this.tip().hasClass("in")?"hide":"show"]();}};$.fn.tooltip=function(option){return this.each(function(){var $this=$(this),data=$this.data("tooltip"),options=typeof option=="object"&&option;if(!data){$this.data("tooltip",(data=new Tooltip(this,options)));}if(typeof option=="string"){data[option]();}});};$.fn.tooltip.Constructor=Tooltip;$.fn.tooltip.defaults={animation:true,placement:"top",selector:false,template:'<div class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover",title:"",delay:0};}(window.jQuery);
/*!
 * Date prototype extensions.
 * Copyright (c) 2006 J�rn Zaefferer and Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
Date.dayNames=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
Date.abbrDayNames=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
Date.monthNames=["January","February","March","April","May","June","July","August","September","October","November","December"];
Date.abbrMonthNames=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
Date.firstDayOfWeek=1;
Date.format="dd/mm/yyyy";
Date.fullYearStart="20";
(function(){function b(c,d){if(!Date.prototype[c]){Date.prototype[c]=d}}b("isLeapYear",function(){var c=this.getFullYear();return(c%4==0&&c%100!=0)||c%400==0});b("isWeekend",function(){return this.getDay()==0||this.getDay()==6});b("isWeekDay",function(){return !this.isWeekend()});b("getDaysInMonth",function(){return[31,(this.isLeapYear()?29:28),31,30,31,30,31,31,30,31,30,31][this.getMonth()]});b("getDayName",function(c){return c?Date.abbrDayNames[this.getDay()]:Date.dayNames[this.getDay()]});b("getMonthName",function(c){return c?Date.abbrMonthNames[this.getMonth()]:Date.monthNames[this.getMonth()]});b("getDayOfYear",function(){var c=new Date("1/1/"+this.getFullYear());return Math.floor((this.getTime()-c.getTime())/86400000)});b("getWeekOfYear",function(){return Math.ceil(this.getDayOfYear()/7)});b("setDayOfYear",function(c){this.setMonth(0);this.setDate(c);return this});b("addYears",function(c){this.setFullYear(this.getFullYear()+c);return this});b("addMonths",function(d){var c=this.getDate();this.setMonth(this.getMonth()+d);if(c>this.getDate()){this.addDays(-this.getDate())}return this});b("addDays",function(c){this.setTime(this.getTime()+(c*86400000));return this});b("addHours",function(c){this.setHours(this.getHours()+c);return this});b("addMinutes",function(c){this.setMinutes(this.getMinutes()+c);return this});b("addSeconds",function(c){this.setSeconds(this.getSeconds()+c);return this});b("zeroTime",function(){this.setMilliseconds(0);this.setSeconds(0);this.setMinutes(0);this.setHours(0);return this});b("asString",function(d){var c=d||Date.format;if(c.split("mm").length>1){c=c.split("mmmm").join(this.getMonthName(false)).split("mmm").join(this.getMonthName(true)).split("mm").join(a(this.getMonth()+1))}else{c=c.split("m").join(this.getMonth()+1)}c=c.split("yyyy").join(this.getFullYear()).split("yy").join((this.getFullYear()+"").substring(2)).split("dd").join(a(this.getDate())).split("d").join(this.getDate());return c});Date.fromString=function(t){var n=Date.format;var p=new Date("01/01/1970");if(t==""){return p}t=t.toLowerCase();var m="";var e=[];var c=/(dd?d?|mm?m?|yy?yy?)+([^(m|d|y)])?/g;var k;while((k=c.exec(n))!=null){switch(k[1]){case"d":case"dd":case"m":case"mm":case"yy":case"yyyy":m+="(\\d+\\d?\\d?\\d?)+";e.push(k[1].substr(0,1));break;case"mmm":m+="([a-z]{3})";e.push("M");break}if(k[2]){m+=k[2]}}var l=new RegExp(m);var q=t.match(l);for(var h=0;h<e.length;h++){var o=q[h+1];switch(e[h]){case"d":p.setDate(o);break;case"m":p.setMonth(Number(o)-1);break;case"M":for(var g=0;g<Date.abbrMonthNames.length;g++){if(Date.abbrMonthNames[g].toLowerCase()==o){break}}p.setMonth(g);break;case"y":p.setYear(o);break}}return p};var a=function(c){var d="0"+c;return d.substring(d.length-2)}})();
/*!
 * eventsCalendar calendar widget
 * based on jQuery datePicker by Kelvin Luck (http://www.kelvinluck.com/)
 * @author Peter Edwards <bjorsq@gmail.com>
 * @version 1.2
 * @package EventPostType_Plugin
 */
jQuery(function($){
    if ($('#eventsCalendar').length) {
    	/* cache for events retrieved via JSON */
    	var events = {};
    	/* hover class for table cells */
    	var hoverClass = "ec-hover";
       	/* set initial date */
    	var today = getDateFromURL();
        var displayedMonth = today.getMonth();
    	var displayedYear = today.getFullYear();
    	/* container for calendar */
    	var $c = $('#eventsCalendar');
    	/* empty container */
    	$c.empty();
    	/**
    	 * set the headings for the calendar
    	 */
    	$c.append(
    		$('<div id="ec-info"></div>'),
    		$('<div class="ec-container"></div>')
    			.append(
    				$('<h4></h4>'),
    				$('<div class="ec-nav-prev ec-nav"></div>')
    				.append(
    					$('<a class="ec-nav-prev-year" href="#" title="Previous Year">&lt;&lt;</a>')
    						.bind('click', function()
    							{
    								return displayNewMonth(this, 0, -1);
    							}),
    					$('<a class="ec-nav-prev-month" href="#" title="Previous Month">&lt;</a>')
    						.bind('click', function()
    							{
    								return displayNewMonth(this, -1, 0);
    							})
    				),
    				$('<div class="ec-nav-next ec-nav"></div>')
    				.append(
    					$('<a class="ec-nav-next-year" href="#" title="Next Year">&gt;&gt;</a>')
    						.bind('click', function()
    							{
    								return displayNewMonth(this, 0, 1);
    							}),
    					$('<a class="ec-nav-next-month" href="#" title="Next Month">&gt;</a>')
    						.bind('click', function()
    							{
    								return displayNewMonth(this, 1, 0);
    							})
    				),
    				$('<div></div>').attr('id', 'ec-calendar')
    			)
    	);
    	$('.ec-nav').show();

    	/* get events and render calendar table */
    	getEvents(displayedMonth, displayedYear, function(){
    		/* set the title */
    	   	$('h4', $c).html(Date.monthNames[displayedMonth] + ' ' + displayedYear);
    		/* render the calendar table */
    		renderCalendar(displayedMonth, displayedYear);
    	});
    }

	/* shortcut to create elements */
	function dc(a)
	{
		return document.createElement(a);
	};
	
	/* returns a date based on URL parameters */
	function getDateFromURL()
	{
		var regex = new RegExp('([0-9]{4})/([0-9]{2})');
		var results = regex.exec(window.location.href);
		if (results === null) {
			return (new Date()).zeroTime();
		} else {
			return (new Date(results[1], (parseInt(results[2]) - 1), 1)).zeroTime();
		}
	}

	/**
	 * adds events to the calendar
	 * @param object jQuery TD object
	 * @param date js date object for the day the TD represents
	 * @param integer zero based index of month
	 * @param integer full year
	 */
	function addEvents(td, currentDate, month, year)
	{
		var ev = events['for-' + month + '-' + year] || false;
		if (ev) {
			var e4d = [];
			for (i = 0; i < ev.length; i++) {
				var ds = currentDate.getTime();
				var de = currentDate.getTime()+86399999;
				if (ev[i].start_jstimestamp >= ds){
					if (ev[i].start_jstimestamp < de) {
						e4d.push(i);
					}
				} else {
					if (ev[i].end_jstimestamp > ds) {
						e4d.push(i);
					}
				}
			}
			if (e4d.length) {
                var eventText = '';
				for (j = 0; j < e4d.length; j++) {
					eventText += '<h3>'+ev[e4d[j]].title+'</h3><p>'+ev[e4d[j]].dateStr+'</p>';
				}
                var dayHREF = eventsJSON.substr(0,eventsJSON.length - 5)+"/"+currentDate.getFullYear()+'/'+getTwoDigits((currentDate.getMonth()+1))+'/'+getTwoDigits(currentDate.getDate());
                td.addClass("event")
                    .tooltip({
                        title:function(){
                            return $(eventText);
                        }
                    }).click(function(){
                        window.location.href=dayHREF;
                    });
                /*.bind("mouseenter", function(){
					tdp = $(this).position();
					$('#ec-info').hide().empty().html(eventText).css({width:200,height:'auto'});
					var h = $('#ec-info').height();
					$('#ec-info').css({left:tdp.left,top:tdp.top,width:$(this).width(),height:$(this).height()})
						.show()
						.animate({'width':200,'height':h})
						.bind("mouseleave",function(){$(this).hide();});
				});*/
			}
		}
	};
	
	/**
	 * gets events for the calendar using JSON
	 * assumes that the eventsJSON variable has already been set to the correct URL for the events feed
	 * @param integer zero-indexed month
	 * @param integer full year
	 * @param function callback function to be executed when events have been retrieved
	 */
	function getEvents(month, year, callback)
	{
		/* see if the events are cached */
		if (!events['for-' + month + '-' + year]) {
			/* get events using JSON request */
			$.getJSON(eventsJSON+'/'+year+'/'+getTwoDigits((month+1)), function(data) {
				events['for-' + month + '-' + year] = data;
				callback();
			});
		} else {
			/* get events from the cache */
			callback();
		}
	}

    /**
     * gets a two-digit number to use in a URL
     * pads to two figures with a zero
     */
    function getTwoDigits(num)
    {
        var num = parseInt(num);
        if (num < 10) {
            return "0"+num;
        } else {
            return ""+num;
        }
    }

	/**
	 * renders the calendar table
	 * @param integer zero based index of month
	 * @param integer full year
	 */
	function renderCalendar(month, year)
	{
		/* build the calendar table */
		var headRow = $(dc('tr'));
		for (var i=Date.firstDayOfWeek; i<Date.firstDayOfWeek+7; i++) {
			var weekday = i%7;
			var day = Date.dayNames[weekday];
			headRow.append(
				jQuery(dc('th')).attr({'scope':'col', 'abbr':day, 'title':day, 'class':(weekday == 0 || weekday == 6 ? 'weekend' : 'weekday')}).html(day.substr(0, 1))
			);
		}
		var calendarTable = $(dc('table')).attr({'cellspacing':2,'class':'eventsCalendar'}).append($(dc('thead')).append(headRow));
		var tbody = $(dc('tbody'));
		var today = (new Date()).zeroTime();
		var currentDate = new Date(year, month, 1);

		var firstDayOffset = Date.firstDayOfWeek - currentDate.getDay() + 1;
		if (firstDayOffset > 1) firstDayOffset -= 7;
		var weeksToDraw = Math.ceil(( (-1*firstDayOffset+1) + currentDate.getDaysInMonth() ) /7);
		currentDate.addDays(firstDayOffset-1);
		/* hover functions for IE */
		var doHover = function()
		{
			$(this).addClass(hoverClass);
		};
		var unHover = function()
		{
			$(this).removeClass(hoverClass);
		};	
			
		var w = 0;
		while (w++ < weeksToDraw) {
			var r = jQuery(dc('tr'));
			for (var i = 0; i < 7; i++) {
				var thisMonth = currentDate.getMonth() == month;
				var d = $(dc('td'))
					.text(currentDate.getDate() + '')
					.attr('class', (thisMonth ? 'current-month ' : 'other-month ') +
						(currentDate.isWeekend() ? 'weekend ' : 'weekday ') +
						(thisMonth && currentDate.getTime() == today.getTime() ? 'today ' : ''))
					.hover(doHover, unHover);
					addEvents(d, currentDate, month, year);
					r.append(d);
					currentDate.addDays(1);
			}
			tbody.append(r);
		}
		calendarTable.append(tbody);
		/* add calendar table */	
		$('#ec-calendar').empty().append(calendarTable);
	};
	
	/**
	 * function to change the month displayed
	 */
	function displayNewMonth(ele, m, y)
	{
		/* remove focus from the link */
		ele.blur();
		/* find out what date to display */
		var t;
		if ((!m && !y) || (isNaN(m) && isNaN(y))) {
			// no month or year passed - default to current month
			t = new Date().zeroTime();
			t.setDate(1);
		} else if (isNaN(m)) {
			// just year passed in - presume we want the displayedMonth
			t = new Date(displayedYear + y, displayedMonth, 1);
		} else if (isNaN(y)) {
			// just month passed in - presume we want the displayedYear
			t = new Date(displayedYear, displayedMonth + m, 1);
		} else {
			// year and month passed in - that's the date we want!
			t = new Date(displayedYear + y, displayedMonth + m, 1)
		}
		displayedMonth = t.getMonth();
		displayedYear = t.getFullYear();
		
		/* get the events */
		getEvents(displayedMonth, displayedYear, function(){
			/* set the title */
			$('h4', $c).html(Date.monthNames[displayedMonth] + ' ' + displayedYear);
			/* render the calendar */
			renderCalendar(displayedMonth, displayedYear);
		});
		return false;
	};
	if ($('.add-filter').length) {
		$('.add-filter').on('click', function(e) {
			var list = $(this).parent().next('.event-taxonomy-list');
			if ($(list).is(':visible')) {
				$(this).removeClass("expanded");
				$(list).slideUp();
			} else {
				$(this).addClass("expanded");
				$(list).slideDown();
			}
			return false;
		});
	}
});


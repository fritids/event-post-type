/*!
 * Extension for JavaScript's Date object to simulate PHP's date function
 * @author Jacob Wright
 * @see <a href="http://jacwright.com/projects/javascript/date_format/">JavaScript Date.format</a>
 */ 
Date.prototype.format = function(format) {
        var returnStr = '';
        var replace = Date.replaceChars;
        if (format && format.length) {
            for (var i = 0; i < format.length; i++) {               
            	var curChar = format.charAt(i);                 
            	if (i - 1 >= 0 && format.charAt(i - 1) == "\\") {
                    returnStr += curChar;
                } else if (replace[curChar]) {
                    returnStr += replace[curChar].call(this);
                } else if (curChar != "\\"){
                    returnStr += curChar;
                }
            }
        }
        return returnStr;
};
Date.replaceChars = {
        shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        longMonths: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        shortDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        longDays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],

        // Day
        d: function() { return (this.getDate() < 10 ? '0' : '') + this.getDate(); },
        D: function() { return Date.replaceChars.shortDays[this.getDay()]; },
        j: function() { return this.getDate(); },
        l: function() { return Date.replaceChars.longDays[this.getDay()]; },
        N: function() { return this.getDay() + 1; },
        S: function() { return (this.getDate() % 10 == 1 && this.getDate() != 11 ? 'st' : (this.getDate() % 10 == 2 && this.getDate() != 12 ? 'nd' : (this.getDate() % 10 == 3 && this.getDate() != 13 ? 'rd' : 'th'))); },
        w: function() { return this.getDay(); },
        z: function() { var d = new Date(this.getFullYear(),0,1); return Math.ceil((this - d) / 86400000); }, // Fixed now
        // Week
        W: function() { var d = new Date(this.getFullYear(), 0, 1); return Math.ceil((((this - d) / 86400000) + d.getDay() + 1) / 7); }, // Fixed now
        // Month
        F: function() { return Date.replaceChars.longMonths[this.getMonth()]; },
        m: function() { return (this.getMonth() < 9 ? '0' : '') + (this.getMonth() + 1); },
        M: function() { return Date.replaceChars.shortMonths[this.getMonth()]; },
        n: function() { return this.getMonth() + 1; },
        t: function() { var d = new Date(); return new Date(d.getFullYear(), d.getMonth(), 0).getDate() }, // Fixed now, gets #days of date
        // Year
        L: function() { var year = this.getFullYear(); return (year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)); },       // Fixed now
        o: function() { var d  = new Date(this.valueOf());  d.setDate(d.getDate() - ((this.getDay() + 6) % 7) + 3); return d.getFullYear();}, //Fixed now
        Y: function() { return this.getFullYear(); },
        y: function() { return ('' + this.getFullYear()).substr(2); },
        // Time
        a: function() { return this.getHours() < 12 ? 'am' : 'pm'; },
        A: function() { return this.getHours() < 12 ? 'AM' : 'PM'; },
        B: function() { return Math.floor((((this.getUTCHours() + 1) % 24) + this.getUTCMinutes() / 60 + this.getUTCSeconds() / 3600) * 1000 / 24); }, // Fixed now
        g: function() { return this.getHours() % 12 || 12; },
        G: function() { return this.getHours(); },
        h: function() { return ((this.getHours() % 12 || 12) < 10 ? '0' : '') + (this.getHours() % 12 || 12); },
        H: function() { return (this.getHours() < 10 ? '0' : '') + this.getHours(); },
        i: function() { return (this.getMinutes() < 10 ? '0' : '') + this.getMinutes(); },
        s: function() { return (this.getSeconds() < 10 ? '0' : '') + this.getSeconds(); },
        u: function() { var m = this.getMilliseconds(); return (m < 10 ? '00' : (m < 100 ? '0' : '')) + m; },
        // Timezone
        e: function() { return "Not Yet Supported"; },
        I: function() { return "Not Yet Supported"; },
        O: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + '00'; },
        P: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + ':00'; }, // Fixed now
        T: function() { var m = this.getMonth(); this.setMonth(0); var result = this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/, '$1'); this.setMonth(m); return result;},
        Z: function() { return -this.getTimezoneOffset() * 60; },
        // Full Date/Time
        c: function() { return this.format("Y-m-d\\TH:i:sP"); }, // Fixed now
        r: function() { return this.toString(); },
        U: function() { return this.getTime() / 1000; }
};
/*!
 * EventPostType admin area scripts
 * @uses Date.format (http://jacwright.com/projects/javascript/date_format/)
 * @uses jQuery (http://www.jquery.com)
 * @author Peter Edwards <bjorsq@gmail.com>
 * @version 1.2
 * @package EventPostType_Plugin
 */
jQuery(function($){
	if ($('#thumbnail_size_select').val() != "custom") {
		$('#thumbnail_size_input').hide();
		$('#custom_thumbnail_desc').hide();
	}
	$('#thumbnail_size_select').change(function(){
		if ($(this).val() == "custom") {
			$('#thumbnail_size_input').show().focus();
			$('#custom_thumbnail_desc').show();
		} else {
			$('#thumbnail_size_input').hide();
			$('#custom_thumbnail_desc').hide();
			$('#ept_plugin_options_thumbnail_size').val($('#thumbnail_size_select').val());
		}
		console.log($('#ept_plugin_options_thumbnail_size').val());
	});
	$('#thumbnail_size_input').keyup(function(){
		$('#ept_plugin_options_thumbnail_size').val($(this).val());
	});
	function updateDatePreview(){
		var today = new Date();
		var from = new Date();
		from.setHours(13);
		from.setMinutes(45);
		from.setSeconds(0);
		from.setDate(today.getDate()-1);
		var to = new Date();
		to.setHours(14);
		to.setMinutes(30);
		to.setSeconds(0);
		var pv = $('#date_preview');
		pv.empty();
		pv.append('<p><strong>Date format preview</strong></p>');
		pv.append('<p><span class="datelabel">All day event: </span><span class="datepreview">'+$('#date_label').val()+today.format($('#date_fmt').val())+$('#allday').val()+'</span></p>');
		pv.append('<p><span class="datelabel">Event on a single day: </span><span class="datepreview">'+$('#date_label').val()+today.format($('#date_fmt').val())+$('#date_time_separator').val()+$('#time_label').val()+from.format($('#time_fmt').val())+$('#time_separator').val()+to.format($('#time_fmt').val())+'</span></p>');
        pv.append('<p><span class="datelabel">Event spanning multiple days: </span><span class="datepreview">'+$('#date_label').val()+from.format($('#date_fmt').val())+$('#date_separator').val()+to.format($('#date_fmt').val())+'</span></p>');
    }
    $('.dateformat').keyup(function(){updateDatePreview();});
    updateDatePreview();
    $('.format-examples').change(function(){
   	    var fmt = $(this).val();
   	    if (fmt) {
   	   	    var target = $(this).attr("id").substring(3);
   	   	    $('#'+target).val(fmt);
   	   	    updateDatePreview();
   	    }
    });
    $('#ept-help-tabs').tabs();
    /* hide dates filter from admin screen */
    $('select[name=m]').next().hide();
    $('select[name=m]').hide();
    if ($('#event_dates').length) {
        /* do datepickers */
        var dateConverter = new AnyTime.Converter({format:anytime_settings.dateFormat});
        var timeConverter = new AnyTime.Converter({format:anytime_settings.timeFormat});
        $('input.datepicker').AnyTime_picker({format:anytime_settings.dateFormat});
        $('input.timepicker').AnyTime_picker({format:anytime_settings.timeFormat});
        $('input#event_dates_allday').click(function(){checkAllDay();});
        $("#event_dates_duration").val("null");
        function checkAllDay(){
            if ($('#event_dates_allday:checked').length) {
                $('#event_dates_end_date').val("").attr("disabled", true);
                $('#event_dates_end_time').val("").attr("disabled", true);
                $('#event_dates_start_time').val("").attr("disabled", true);
                $("#event_dates_duration_minutes").val("null");
                $("#event_dates_duration_minutes").hide();
                $('#event_dates_start_time').hide();
                $('#event_dates_end').hide();
            } else {
                $('#event_dates_end_date').removeAttr("disabled");
                $('#event_dates_end_time').removeAttr("disabled");
                $('#event_dates_start_time').removeAttr("disabled");
                $("#event_dates_duration_minutes").show();
                $('#event_dates_start_time').show();
                $('#event_dates_end').show();
            }
        }
        /* set end date as same as start date if empty */
        $("#event_dates_start_date").change( function(e) {
            if (!$('#event_dates_allday:checked').length && $("#event_dates_end_date").val() == "") {
                try {
                    var fromTime = dateConverter.parse($("#event_dates_start_date").val()).getTime();
                    fromDay = new Date(fromTime);
                    fromDay.setHours(2,0,0,0);
                    $("#event_dates_end_date").
                        AnyTime_noPicker().
                        removeAttr("disabled").
                        AnyTime_picker({earliest:fromDay,format:anytime_settings.dateFormat});
                } catch(e){};
            }
        });
        $("#event_dates_duration_minutes").change(function(e) {
            var duration = $("#event_dates_duration_minutes option:selected").val()
            if (duration !== "null") {
                if ($("#event_dates_start_date").val() !== "" && $("#event_dates_start_time").val() !== "") {
                    try {
                        var fromTime = timeConverter.parse($("#event_dates_start_time").val());
                        fromTime.setMinutes(fromTime.getMinutes()+parseInt(duration));
                        $("#event_dates_end_time").val(timeConverter.format(fromTime));
                        $("#event_dates_end_date").val($("#event_dates_start_date").val());
                    } catch(e){};
                }
            }
        });
        /* set the duration when loading */
        if ($("#event_dates_start_time").val() !== "" && $("#event_dates_end_time").val() !== "") {
            try {
                var fromTime = timeConverter.parse($("#event_dates_start_time").val());
                var toTime = timeConverter.parse($("#event_dates_end_time").val());
                $("#event_dates_duration_minutes").val((toTime - fromTime)/60000);
            } catch(e){};
        }
        checkAllDay();
    }
    /* sets the status of the checkbox on the quick edit form for sticky events */
    $('a.editinline').live('click', function() {
        var id = inlineEditPost.getId(this);
        var eis_val = $('#event_is_sticky_text_'+id).attr('data-event_is_sticky');
        if (eis_val == "1") {
            $('.event_is_sticky_cb').attr('checked', 'checked');
        } else {
            $('.event_is_sticky_cb').removeAttr('checked');
        }
    });
});

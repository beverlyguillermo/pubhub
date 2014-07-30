// adapted from: http://www.benknowscode.com/2012/11/selecting-ranges-jquery-ui-datepicker.html

(function ($) {

    var rangepicker = function ($) {

        /**
         * Element(s) plugin was activated on
         */
        var elem;

        /**
         * Input created to reside above the datepicker
         */
        var input;
        
        /**
         * Div created to reside nex to elem to
         * activate the datepicker on
         */
        var datepickerDiv;

        /**
         * Callback function called when someone selects a date
         * or date range in the datepicker.
         */
        var callback;
        
        /**
         * Used to track date range and correspond to the dates selected. We start at
         * -1 so that both start and end points of the range do not exist on the calendar.
         * @type {Number}
         */
        var prv = -1, // previous date selection
            cur = -1; // current date selection


        $.datepicker._defaults.onAfterUpdate = null;

        var datepicker__updateDatepicker = $.datepicker._updateDatepicker;
        
        $.datepicker._updateDatepicker = function( inst ) {
            datepicker__updateDatepicker.call( this, inst );

            var onAfterUpdate = this._get(inst, "onAfterUpdate");

            if (onAfterUpdate) {
                var thisIs = inst.input ? inst.input[0] : null;
                var args = [(inst.input ? inst.input.val() : ''), inst];

                onAfterUpdate.apply(thisIs, args);
            }
        };

        return {

            init: function (element, cb) {

                elem = element;
                callback = cb;

                // add an input to display range
                input = $("<input type='text' id='rangedisplay' />").hide();
                elem.parent().append(input);

                // add a div
                datepickerDiv = $("<div />");
                elem.parent().append(datepickerDiv);

                // attach datepicker to datepickerDiv div to avoid the default functionality
                // of the datepicker closing when a date is selected
                datepickerDiv
                    .hide()
                    .datepicker({
                        minDate: 0,
                        showButtonPanel: true,
                        onSelect: rangepicker.onSelect,
                        beforeShowDay: rangepicker.beforeShowDay,
                        onAfterUpdate: rangepicker.onAfterUpdate
                    })
                    .position({
                        my: "left top",
                        at: "left bottom",
                        of: elem
                    });

                // when the group changes
                $("input[name=" + elem.attr("name") + "]").on("change", function () {

                    if ($(this).is(elem)) {
                        rangepicker.show();
                    } else {
                        rangepicker.hide();
                    }

                });
                
                input.on("focus", rangepicker.onFocus);

            },

            /**
             * Runs when the user has closed the datepicker
             * @param  {string} dateText
             * @param  {object} inst
             * @return null
             */
            onClose: function (dateText) {
                
                var dates = [];

                // range
                if ( dateText.indexOf(" - ") > -1 ) {
                    dates = dateText.split(" - ");

                // single date
                } else if ( dateText.length > 0 ) {
                    dates.push(dateText);
                }

                // check formatting of elements to make sure they are real dates
                var passed = true;
                var regex = /^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/;
                $.each(dates, function (i, date) {
                    if (!regex.test(date)) {
                        passed = false;
                    }
                });

                if (dates.length > 0 && passed) {
                    callback.call(elem, dates);
                } else {
                    return;
                }

            },

            /**
             * Runs when the input is focused on
             * @return null
             */
            onFocus: function () {

                input.show();

                var v = this.value;

                // if a date is already set...
                try {

                    // range
                    if ( v.indexOf(" - ") > -1 ) {
                        var existingDate = v.split(" - ");

                        prv = $.datepicker.parseDate("mm/dd/yy", existingDate[0]).getTime();
                        cur = $.datepicker.parseDate("mm/dd/yy", existingDate[1]).getTime();

                    // single date
                    } else if ( v.length > 0 ) {
                        prv = cur = $.datepicker.parseDate("mm/dd/yy", v).getTime();
                        datepickerDiv.datepicker("setDate", new Date(cur));
                    }

                } catch (e) {
                    cur = prv = -1;
                }

                // if ( cur > -1 ) {
                //     datepickerDiv.datepicker("setDate", new Date(cur));
                // }

                datepickerDiv.datepicker("refresh").show();
            },

            onUnfocus: function () {
                input.hide();
            },

            /**
             * Runs each time the user selects a date.
             * @param  {string} dateText
             * @param  {object} inst
             * @return null
             */
            onSelect: function (dateText, inst) {
                
                // formated dates that display in the input
                var d1, d2;

                // set the previous selection the current value of cur
                prv = cur;

                // set the value of cur to be the date just selected
                cur = (new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay)).getTime();
                

                // update the input's valiue

                // only one date has been selected
                if ( prv == -1 || prv == cur ) {
                    prv = cur;
                    elem.val( dateText );
                    input.val( dateText );

                // a range has been selected
                } else {
                    d1 = $.datepicker.formatDate( 'mm/dd/yy', new Date(Math.min(prv,cur)), {} );
                    d2 = $.datepicker.formatDate( 'mm/dd/yy', new Date(Math.max(prv,cur)), {} );
                    elem.val(d1 + " - " + d2);
                    input.val(d1 + " - " + d2);
                }
            },

            /**
             * Called for each day in the date picker before it is displayed. Allows you to indicate
             * whether the date is selectable (first element in array) and a class to apply to that
             * date's cell (second element in array).
             * @param  {date} date
             * @return array
             */
            beforeShowDay: function (date) {
                
                var clss = "";

                if (date.getTime() >= Math.min(prv, cur) && date.getTime() <= Math.max(prv, cur)) {
                    clss = "date-range-selected";
                }

                return [true, clss];
            },

            onAfterUpdate: function ( inst ) {

                $("<button />")
                    .addClass("ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all")
                    .attr("type", "button")
                    .attr("data-handler", "hide")
                    .attr("data-event", "click")
                    .html("Apply")
                    .appendTo(datepickerDiv.find(".ui-datepicker-buttonpane"))
                    .on("click", function() {
                        datepickerDiv.hide();
                        rangepicker.onClose.call(elem, elem.val());
                    });
            },

            hide: function () {
                datepickerDiv.hide();
                rangepicker.onUnfocus();
            },

            show: function () {
                datepickerDiv.show();
                rangepicker.onFocus();
            }

        }

    }($);
 
    $.fn.rangepicker = function (args) {

        // console.log("rangepicker...");
        // console.log(args);

        this.each(function () {
            rangepicker.init($(this), args);
        });

    };
 
})(jQuery);
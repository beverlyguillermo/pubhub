var hubevents = (function ($, lazy, _gaq) {

    /**
     * Holds the event data
     * @type {[type]}
     */
	var $container = $(".events-container");
    var isotopeOptions = {
        layoutMode: "masonry",
        itemSelector: '.event',
        getSortData: {
            startDate: function ( $elem ) {
                return $elem.attr("data-start");
            }
        },
        sortBy: "startDate"
    };

    /**
     * Keeps track of which filters in each filter group are
     * activated. The "all" filter does not count towards this.
     * @type {Object}
     */
    var filters = {};

    /**
     * Keeps track of which 'subscription' filters in each filter group are
     * activated. The "all" filter does not count towards this.
     * @type {Object}
     */
    var subscription_filters = {};

    /**
     * Events that loaded with the page
     */
    var $originalEvents;

    /**
     * The last date range requested by the user
     */
    var previousDateRange;

    /**
     * Keeps track of whether the lazyloaded content
     * is active or the content loaded with the page is.
     * @type {Boolean}
     */
    var dateRangeActive = false;

    /**
     * Events loaded via lazyload. Each time content is
     * loaded via a date range, it's stored here so that
     * if the user tries to load the same range again,
     * it's already preloaded.
     */
    var $lazyload;

    return {

        init: function() {

            // turn off filters until they're setup
            $(".filters-col")
                .css("height", 150)
                .addClass("data-loading");

            // save off events loaded with the page
            $originalEvents = $($container.html());

            // wait until the images are loaded to initialize isotope
            $container.imagesLoaded( function() {

                $container.isotope(isotopeOptions, function () {

                    hubevents.subscribe.setupFilters();
                    hubevents.filtering.setupFilters();

                    // setup rangepicker to run newRange() when a date range is selected
                    $("input#range").rangepicker(function (dates) {
                        hubevents.newRange(dates);
                    });

                    hubevents.filtering.setupFilterToggle();

                    // turn on filters
                    $(".filters-col")
                        .removeClass("data-loading")
                        .css("height", "auto");
                    $(".filters-container")
                        .animate({ opacity: 1 }, 1000);

                });

            });
        },

        removeAll: function (callback) {
            $container.isotope("remove", $container.find(".event"));
        },

        newRange: function (dates) {

            var joinedDates = dates.join(",");

            // new date range is the same as the previous, reload saved lazyload data
            if (joinedDates === previousDateRange && !dateRangeActive) {

                hubevents.removeAll();
                $container.isotope("insert", $lazyload);

            // new date range is different than the previous, get new data
            } else {

                hubevents.removeAll();
                hubevents.subscribe.filters({ date: hubevents.utility.convertDates(dates) });
                hubevents.loadMore(hubevents.formatDates(dates), 1);
                
            }

            previousDateRange = joinedDates;
            dateRangeActive = true;
        },

        /**
         * Formats dates for the API
         * @param  {array} dates Dates selected by rangepicker
         * @return string
         */
        formatDates: function (dates) {
            
            dates = $.map(dates, function (date) {

                var dateParts = date.split("/");
                return dateParts[2] + "-" + dateParts[0] + "-" + dateParts[1];

            });

            return dates.join(",");

        },

        loadMore: function (dates, page) {

            lazy.createPromise({

                type: "custom",
                endpoint: "events",
                params: { per_page: 100 , page: page, date: dates }

            }).then(function (data) {

                $lazyload = $(lazy.compileTemplate(data, "template-events-home-more"));

                $lazyload.imagesLoaded( function() {
                    $container.isotope("insert", $lazyload);
                });

                if (data._links.next) {
                    hubevents.loadMore(dates, page + 1);
                }

            });

        },

        resetEvents: function () {
            hubevents.removeAll();
            $container.isotope("insert", $originalEvents);
            dateRangeActive = false;
        },

        filtering: {

            /**
             * Enables toggling of filters on mobile
             */
            setupFilterToggle: function () {

                $(".filters-container .display-nav").on("click", function (e) {

                    var $container = $(this).parents(".filters-container");

                    // do not close if the filters are open and the icon was not clicked on
                    if ($container.hasClass("open") && !$(e.target).is("i")) {
                        return;
                    }

                    $container.find(".filters").toggleClass("open closed").toggle(500, "easeOutQuart");
                    $container.toggleClass("open closed");
                });

            },

            /**
             * Setup filter logic
             */
            setupFilters: function() {

                $("form#filters").on("change", function (e) {

                    var $target = $(e.target);
                    var fieldset = $target.parents("fieldset");
                    var group = fieldset.attr("data-group");

                    // reset events back to original page load
                    if (group == "date" && $target.attr("id") !== "range" && dateRangeActive) {
                        hubevents.resetEvents();
                    }

                    // show or hide the rande display
                    // if (group == "date" && $target.attr("id") !== "range") {
                    //     $("input#range").rangepicker("hide");

                    // }

                    hubevents.filtering.recalibrateFilters($target);
                    hubevents.filtering.filter(filters);
                    hubevents.filtering.track($target);
                });

            },

            recalibrateFilters: function ($target) {

                var isAll = $target.hasClass("all");
                var fieldset = $target.parents("fieldset");
                var group = fieldset.attr("data-group");

                if (!filters[group] || isAll || fieldset.hasClass("singleselect")) {
                    filters[group] = [];
                }

                // if the item was checked
                if ($target.attr("checked")) {

                    if (isAll) {

                        // uncheck all other inputs in the group
                        fieldset.find("input:not(input.all)").removeAttr("checked");

                    } else {

                        // make sure input.all is not checked
                        fieldset.find("input.all").removeAttr("checked");

                        // add the filter to the array of selected filters, unless its a range
                        if ($target.attr("id") !== "range") {
                            filters[group].push($target.val());
                        } else {
                            filters["date"] = [];
                        }
                    }

                // if the item was unchecked
                } else {

                    // you cannot uncheck "all", so return
                    if (isAll) {
                        return;
                    }

                    var filterIndex = $.inArray($target.val(), filters[group]);

                    // remove the filter
                    filters[group].splice(filterIndex, 1);

                    // if the last checkbox has been unchecked, check the "all" checkbox
                    if ($target.parents("fieldset").find("input[type=checkbox]:checked").length === 0) {
                        fieldset.find("input.all").attr("checked", "checked");
                    }
                }
            },

            filter: function (selectedFilters) {
                // now comes the filtering fun

                var i = 0;
                var comboFilters = [];

                // console.log(filters);

                for (var groupName in selectedFilters) {

                    // items selected in this filter group
                    var filterGroup = selectedFilters[groupName];

                    // skip to next group if nothing is checked
                    if (!filterGroup.length) {
                        continue;
                    }

                    // console.log(filterGroup);

                    // if the first time through, create a new array with the values checked in this group
                    if (i === 0) {
                        comboFilters = filterGroup;

                    } else {

                        var filterSelectors = [];

                        // copy to fresh array
                        var groupCombo = comboFilters;

                        // merge filter Groups
                        for (var k  = 0, len3 = filterGroup.length; k < len3; k++) {

                            for (var j = 0, len2 = groupCombo.length; j < len2; j++) {
                                filterSelectors.push( groupCombo[j] + filterGroup[k] ); // [ 1, 2 ]
                            }

                        }

                        // apply filter selectors to combo filters for next group
                        comboFilters = filterSelectors

                    }

                    i++
                }

                var newOptions = {};
                $.extend(newOptions, isotopeOptions, { filter: comboFilters.join(', ') });
                $container.isotope(newOptions);

                // console.log(comboFilters);
            },

            track: function(checkbox) {

                if (checkbox.attr("checked")) {
                    var category = checkbox.parents("fieldset").data().group;
                    var filter = checkbox.attr("id");

                    _gaq.push(["_trackEvent", "Events", category, filter]);
                }
            }

        },

        utility:  {

            convertDates: function (dates) {
                return $.map(dates, function (elem, i) {
                    var parts = elem.split("/");
                    return parts[2] + "-" + parts[0] + "-" + parts[1];
                });
            }

        },

        subscribe: {
            Base64: {
                // private property
                _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

                // public method for encoding
                encode : function (input)
                {
                    var output = "";
                    var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
                    var i = 0;

                    input = this._utf8_encode(input);

                    while (i < input.length) {

                        chr1 = input.charCodeAt(i++);
                        chr2 = input.charCodeAt(i++);
                        chr3 = input.charCodeAt(i++);

                        enc1 = chr1 >> 2;
                        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                        enc4 = chr3 & 63;

                        if (isNaN(chr2)) {
                            enc3 = enc4 = 64;
                        } else if (isNaN(chr3)) {
                            enc4 = 64;
                        }

                        output = output +
                        this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
                        this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

                    }

                    return output;
                },

                // public method for decoding
                decode : function (input)
                {
                    var output = "";
                    var chr1, chr2, chr3;
                    var enc1, enc2, enc3, enc4;
                    var i = 0;

                    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

                    while (i < input.length) {

                        enc1 = this._keyStr.indexOf(input.charAt(i++));
                        enc2 = this._keyStr.indexOf(input.charAt(i++));
                        enc3 = this._keyStr.indexOf(input.charAt(i++));
                        enc4 = this._keyStr.indexOf(input.charAt(i++));

                        chr1 = (enc1 << 2) | (enc2 >> 4);
                        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                        chr3 = ((enc3 & 3) << 6) | enc4;

                        output = output + String.fromCharCode(chr1);

                        if (enc3 != 64) {
                            output = output + String.fromCharCode(chr2);
                        }
                        if (enc4 != 64) {
                            output = output + String.fromCharCode(chr3);
                        }

                    }

                    output = this._utf8_decode(output);

                    return output;

                },

                // private method for UTF-8 encoding
                _utf8_encode : function (string)
                {
                    string = string.replace(/\r\n/g,"\n");
                    var utftext = "";

                    for (var n = 0; n < string.length; n++) {

                        var c = string.charCodeAt(n);

                        if (c < 128) {
                            utftext += String.fromCharCode(c);
                        }
                        else if((c > 127) && (c < 2048)) {
                            utftext += String.fromCharCode((c >> 6) | 192);
                            utftext += String.fromCharCode((c & 63) | 128);
                        }
                        else {
                            utftext += String.fromCharCode((c >> 12) | 224);
                            utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                            utftext += String.fromCharCode((c & 63) | 128);
                        }

                    }

                    return utftext;
                },

                // private method for UTF-8 decoding
                _utf8_decode : function (utftext)
                {
                    var string = "";
                    var i = 0;
                    var c = c1 = c2 = 0;

                    while ( i < utftext.length ) {

                        c = utftext.charCodeAt(i);

                        if (c < 128) {
                            string += String.fromCharCode(c);
                            i++;
                        }
                        else if((c > 191) && (c < 224)) {
                            c2 = utftext.charCodeAt(i+1);
                            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                            i += 2;
                        }
                        else {
                            c2 = utftext.charCodeAt(i+1);
                            c3 = utftext.charCodeAt(i+2);
                            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                            i += 3;
                        }

                    }

                    return string;

                }
            },
            /* Modify subscription event links */
            filters: function(filter) {
              var link = $('.events-subscribe').prop('href');
              var original_params = $.deparam.querystring(link);
              if (link.indexOf("?")>-1){
                link = link.substr(0,link.indexOf("?"));
              }
              if (typeof original_params[link] != 'undefined') delete original_params[link];
              if (typeof original_params['p'] != 'undefined')  delete original_params['p'];
              var params = $.deparam.querystring($.param($.extend(original_params, filters, filter)));
              $('.events-subscribe').prop('href', $.param.querystring(link, { "p": hubevents.subscribe.Base64.encode(JSON.stringify(params)) } ));
            },

            /* Set up subscribe events */
            setupFilters: function() {
              $('input#subscribe-range').rangepicker2(function(dates) { });
              $("form#subscribe-filters").on("change", function (e) {
                  var $target = $(e.target);
                  var fieldset = $target.parents("fieldset");
                  var group = fieldset.attr("data-group");
                  var isAll = $target.hasClass("all");

                  if (!subscription_filters[group] || isAll || fieldset.hasClass("singleselect")) {
                      subscription_filters[group] = [];
                  }
                  // if the item was checked
                  if ($target.attr("checked")) {
                      if (isAll) {
                          // uncheck all other inputs in the group
                          fieldset.find("input:not(input.all)").removeAttr("checked");
                      } else {
                          // make sure input.all is not checked
                          fieldset.find("input.all").removeAttr("checked");
                          // add the filter to the array of selected filters, unless its a range
                          if ($target.attr("id") !== "range") {
                              subscription_filters[group].push($target.val());
                          } else {
                              subscription_filters["date"] = [];
                          }
                      }
                  // if the item was unchecked
                  } else {
                      // you cannot uncheck "all", so return
                      if (isAll) {
                          return;
                      }

                      var filterIndex = $.inArray($target.val(), subscription_filters[group]);

                      // remove the filter
                      subscription_filters[group].splice(filterIndex, 1);

                      // if the last checkbox has been unchecked, check the "all" checkbox
                      if ($target.parents("fieldset").find("input[type=checkbox]:checked").length === 0) {
                          fieldset.find("input.all").attr("checked", "checked");
                      }
                  }
                  hubevents.subscribe.filters(subscription_filters);
              });
            }
        }

    };

})(jQuery, lazy, _gaq);

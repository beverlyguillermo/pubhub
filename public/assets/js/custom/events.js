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

                    hubevents.filtering.setupFilters();

                    // setup rangepicker to run loadMore() when a date range is selected
                    $("input#range").rangepicker(function (dates) {
                        hubevents.loadMore(dates);
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

            /*
            $(".btn.events-subscribe").on("click", function(e) {
              e.preventDefault();
              var text = $(this).prop('href');
              window.prompt ("Copy to url to clipboard: press Ctrl+C (or CMD+C on Mac) and then Enter to close", text);
            });
            */
		},

        removeAll: function (callback) {
            $container.isotope("remove", $container.find(".event"));
        },

        loadMore: function (dates) {

            var joinedDates = dates.join("-");

            // new date range is the same as the previous, reload saved lazyload data
            if (joinedDates === previousDateRange && !dateRangeActive) {

                hubevents.removeAll();
                $container.isotope("insert", $lazyload);

            // new date range is different than the previous, get new data
            } else {

                hubevents.removeAll();

                hubevents.subscribe.filters({ date: hubevents.utility.convertDates(dates) });

                var promise = lazy.createPromise({
                    type: "custom",
                    endpoint: "events",
                    params: {
                        date: hubevents.utility.convertDates(dates),
                        per_page: -1
                    }
                });

                promise.then(function (data) {

                    $lazyload = $(lazy.compileTemplate(data, "template-events-home-more"));

                    $lazyload.imagesLoaded( function() {
                        $container.isotope("insert", $lazyload);
                    });
                    
                });
            }

            previousDateRange = joinedDates;
            dateRangeActive = true;
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
                    hubevents.subscribe.filters(filters);
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
            filters: function(filter) {
              var link = $('.events-subscribe').prop('href');
              var params = $.param($.extend($.deparam.querystring(link), filters, filter));
              $('.events-subscribe').prop('href', $.param.querystring(link, params));
            }
        }

	};

})(jQuery, lazy, _gaq);
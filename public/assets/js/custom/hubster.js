/**
 * Hubster application library
 * @version 0.1.0
 */
var hubster = (function ($) {
    var $ = jQuery || {},
        _env = window.location.href.split("//")[1].split(".")[0];

    var _urlPrefix = $.inArray(_env, ["local", "staging", "beta"]) > -1 ? _env + "." : "";
    var _apiBase = "http://" + _urlPrefix + "api.hub.jhu.edu/";

    var _apiVersion = 0;
    var _apiKey = "70a252dc26486819e5817371a48d6e3b5989cb2a";

    var _rails = {};
    var _railNav = {};
    var _railCounter = 0;

    var _offsetPercent = "20";

    var _searchActive = false;
    var _searchBar;

    // Google Analytics i12n
    var _gaq = window._gaq || [];

    return {
        getEnv: function () {
            return _env;
        },
        getTemplates: function () {
            return _templates;
        },
        api: {
            post: function (endpoint, requestData) {
                return $.ajax({
                    type: "POST",
                    url: _apiBase + endpoint,
                    data: requestData
                });
            }
        },
        loadSearch: function (e, searchLink) {
            if (_searchActive) {
                return hubster.closeSearch();
            }
            var setHeight = 50;
            var pad = 10;

            var container = $(".header-global");
            var nav = $(".global-navigation");

            _searchBar = $("<div id='jsSearchBar' />");
            var center = $("<div/>").addClass("center force");
            
            container.css({
                marginTop: "-" + (setHeight + pad * 2) + "px"
            });

            _searchBar.css({
                height: setHeight + "px",
                padding: pad + "px 0",
                backgroundColor: "#3478B6"
            });

            var templateHtml = $("#template-search-form").html();
            var form = $(_.template(templateHtml)());

            container.prepend(_searchBar.append(center.append(form))).animate({
                marginTop: 0
            }, 400, "easeInOutBack", function () {
                container.addClass("search-on");
            });

            form.find("input").focus();

            _searchActive = true;

        },
        closeSearch: function (e, closeLink) {
            var up = _searchBar.outerHeight();
            var container = $(".header-global");

            container.animate({ 
                marginTop: "-" + up + "px" 
            }, 400, "easeInOutBack", function () {
                container.css({
                    marginTop: ""
                }).removeClass("search-on");
                _searchBar.remove();

                _searchActive = false;
            });
        },
        handleSharing: function (link) {
            var $link = $(link);
            var href = link.href;
            var service = $link.attr("data-service");
            var popup;
            var winOptions = {
                width: 550,
                height: 420,
                resizable: "yes",
                scrollbars: "yes"
            };
            var options;
            // HAXXORIFFIC!
            var articlePath = window.location.pathname || window.location.href.split("hub.jhu.edu")[1];

            _gaq.push(["_trackEvent", "Share Bar", service, articlePath]);

            var setOptions = (function () {
                var optionsList = [];
                winOptions.left = $(window).width() / 2 - winOptions.width / 2;
                winOptions.top = $(window).height() / 2 - winOptions.height / 2;
                for (var option in winOptions) {
                    if (winOptions.hasOwnProperty(option)) {
                        optionsList.push(option + "=" + winOptions[option])
                    };
                }
                return options = optionsList.join(",");
            });
            setOptions();

            

            if (service === "twitter") {
                //setOptions();
                popup = window.open(href, "ShareTwitter", options);
            }
            else if (service === "facebook") {
                winOptions.width = 960;
                setOptions();
                popup = window.open(href, "ShareFacebook", options);
            }
            else if (service === "print") {
                winOptions.width = 700;
                winOptions.height = $(window).height();
                setOptions();
                popup = window.open(href, "SharePrint", options);
            }
            else if (service === "comment") {
                $.scrollTo("#comments", {
                    duration: 800,
                    easing: "easeInOutCirc", 
                    offset: 15
                });
            }
        },
        // Adjust times in feature area, using momentJS
        convertDates: function (format) {
            format = format || "MMMM-D-YYYY h-mm-s-a";
            $(".publish-date").each( function (i, value) {

                var $val = $(value);
                var dt = $val.text();
                var m = moment(dt, format);
                var now = moment(new Date());
                var diff = now.diff(m, "hours");

                // Use "ago" if the publish date is less than 72 hours ago
                if (diff < 72) {
                    $val.html(" <i class='icon icon-time'></i> " + m.fromNow());
                } else if (m.format("YYYY") != now.format("YYYY")) {
                    $val.html(" <i class='icon icon-time'></i> " + m.format("MMMM D, YYYY"));
                } else {
                    $val.html(" <i class='icon icon-time'></i> " + m.format("MMMM D"));
                }
                
            });
        },
        activateNewTrending: function () {
            // Load new/trending tabs
            lazy.load($(".quick-tabs").find(".tab"));

            // Listen for click event
            $(".quick-tabs .navigation a").on("click", function (e) {
                var clicked = $(this);
                var tabs = $(".quick-tabs .tabs");
                var active = tabs.find(".active");
                var inactive = active.siblings();
                var switchedTo = $(inactive).attr("data-title");

                e.preventDefault();

                // Run only if the current click is on the unactive tab
                if (clicked.hasClass("active")) {                  
                    return;
                } else {
                    
                    active.css({position: "relative"})
                        .animate({
                            left: "1000px"
                        }, 200, "easeInOutQuart", function () {
                            $(this).toggleClass("active inactive");
                            inactive.css({position: "relative", left: "1000px"})
                                .toggleClass("active inactive")
                                .animate({
                                    left: 0
                                }, 300, "easeInOutQuart", function () {

                                });
                        });
                    
                    $(".quick-tabs .navigation li").toggleClass("active inactive");

                }

                // Google Analytics Tracking
                _gaq.push(['_trackEvent', 'Widgets', 'New-Trending List', 'Switched to the "' + switchedTo + '" view']);

            });
        },
        global: {
            init: function () {
                $("#openSearch").on("click", function (e) {
                    e.preventDefault();
                    hubster.loadSearch(e, this);
                });

                $(".header-global").on("click", "form i", function (e) {
                    var $clicked = $(this);

                    e.preventDefault();
                    e.stopPropagation();
                    
                    if ($clicked.hasClass("search-submit")) {
                        $clicked.closest("form").submit();
                    }
                    if ($clicked.hasClass("search-close")) {
                        return hubster.closeSearch();
                    }
                });

                $(".header-global .scroll-nav").on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var href = this.href.split("#");
                    var anchor = "#" + href[1];

                    $.scrollTo(anchor, {
                        duration: 700,
                        easing: "easeInOutBack", 
                        offset: -30
                    });
                });

                $(document).on("keyup", function (e) {
                    if (_searchActive && e.keyCode == 27) {
                        hubster.closeSearch();
                    }
                });

                $(".fit-video").fitVids();
            }
        },
        single: {
            init: function () {
                $("#shareBar a").on("click", function (e) {
                    e.preventDefault();
                    var link = this;
                    hubster.handleSharing(link);
                });

                // Set up new/trending tabs
                hubster.activateNewTrending();

                hubster.convertDates();
            }
        },
        homepage: {
            init: function () {
                var popular = $("#popular");
                var latest = $("#latest");
                
                // Set up new/trending tabs
                hubster.activateNewTrending();

                // Activates the loading gear icon on pressing 'g', for easter egg + demo purposes
                $("body").on("keypress", function (e) {
                    if (e.keyCode === 103) {
                        var $html = $("html");
                        var $railNav = $(".rail-navigation");
                        $html.toggleClass("wf-loading wf-active");
                        $railNav.hide();
                        setTimeout(function () {
                            $html.toggleClass("wf-loading wf-active");
                            $railNav.fadeIn(500);
                        }, 3000);
                    }
                });

                hubster.gatherRails();
                _rails.waypoint(function (e, direction) {
                    if (direction == "up") {
                        _railCounter -= 1;
                        $.publish("rail.reached", {name: name, direction: direction, count: _railCounter, ignore: true});
                    }
                    if (direction === "down") {
                        _railCounter += 1;
                        
                        var type = $(this).attr("data-type");
                        var endpoint = $(this).attr("data-endpoint");
                        var name = "page-rail" + ":" + endpoint;

                        $.publish("rail.reached", {name: name, direction: direction, count: _railCounter, rail: this});
                    }
                }, {
                    offset: "20%",
                    onlyOnScroll: true
                });

                _rails.find(".center").waypoint(function (e, direction) {
                    if (direction === "up") {
                        var type = $(this).parent(".rail").attr("data-type");
                        var endpoint = $(this).parent(".rail").attr("data-endpoint");
                        var name = "page-rail" + ":" + endpoint;

                        $.publish("rail.reached", {name: name, direction: direction, count: _railCounter, rail: this});
                    }
                }, {
                    offset: function () {
                        var height = $(this).outerHeight();
                        var vpHeight = $.waypoints("viewportHeight");
                        var percentage = 20;
                        if (height > vpHeight * (percentage/100)) {
                            return (vpHeight * (percentage/100)) - height;
                        }
                        return 0;
                    },
                    onlyOnScroll: true
                });

                $("#featureQueue").waypoint(function () {
                    _railNav.find("li").removeClass("active");
                }, { offset: -50 });

                $.subscribe("rail.reached", function (e, info) {
                    hubster.homepage.updateNav(info);
                    if (!info.ignore) {
                        _rails.removeClass("active");
                        var rail = $(info.rail);
                        if (rail.hasClass("center")) {
                            rail = rail.parent(".rail");
                        }
                        rail.addClass("active");
                    }

                });

                hubster.convertDates();

                // Mobile show/hide new and trending
                $(".mobile-open-tabs").on("click", function () {
                    $(".quick-tabs").toggleClass("open").toggle(500, "easeOutQuart"); // fade in/out
                    $(this).toggleClass("open closed");
                });


                _railNav.find("a").on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var target = $(this).attr("data-target");
                    var calculatedOffset = 10;
                    if (target === "target-top") {
                        calculatedOffset = -20;
                    }
                    $.scrollTo("#" + target, {
                        duration: 500,
                        easing: "easeInOutCirc", 
                        offset: calculatedOffset
                    });
                });

                // Subscribe to the section.loaded event to make the nav items animate in
                $.subscribe("section.loaded", function (e, info) {
                    $(".rail-navigation ." + info.id).addClass("loaded");
                });

                // Load all of the rails
                lazy.load([".rail"]);
                lazy.load(_rails.find(".slide.latest, .slide.popular"));

                $(".slider-navigation a").on("click", function (e) {
                    e.preventDefault();
                    var $el = $(this);
                    var $sliderNav = $el.parents("ul.slider-navigation");
                    var rail = $el.parents(".rail");
                    var rHeight = rail.height();
                    var activeSlide = rail.find(".slide.active");
                    var target = $el.attr("data-target");
                    var newSlide = rail.find(".slide." + target);
                    var newHeight = newSlide.height();

                    // events rail needs to take into consideration the rail header
                    if (rail.hasClass("events_rail")) {
                        var headerHeight = $el.parents(".rail-description").outerHeight(true);
                        newHeight = newHeight + headerHeight;
                    }

                    if (activeSlide.hasClass(target)) { return; }
                
                    $sliderNav.find("a.active")
                        .removeClass("active")
                        .find("i.icon-chevron-right")
                        .addClass("icon-white");

                    $el.addClass("active").find("i.icon-chevron-right").removeClass("icon-white");
                    
                    rail.css({height: rHeight + "px"});
                    activeSlide
                        .css({
                            position: "relative"                     
                        })
                        .animate({
                            top: "1000px"
                        }, 400, "easeInQuart")
                        .delay(300)
                        .css({position:""})
                        .toggleClass("active hidden");

                    rail.delay().animate({height: newHeight + "px"}, 300, "easeInQuart");

                    newSlide
                        .delay()
                        .css({
                            position: "relative",
                            top: "1000px",
                            opacity: 0
                        })
                        .toggleClass("active hidden")
                        .animate({
                            top: 0,
                            opacity: 1
                        }, 400, "easeOutQuart", function () {
                            $.waypoints("refresh");
                            rail.css({height: ""});
                        });

                });
                
            },

            updateNav: function (info) {
                if (
                    (info.count === 1 && info.direction === "down") 
                    || 
                    (info.count === 0 && info.direction === "up")
                ) {
                    _railNav.toggleClass("fixed");
                }

                if (info.ignore) {
                    return;
                }

                

                var selector = "." + info.name.replace(/\//g, "-").replace("rail:", "");

                var oldActives = _railNav.find(".active");
                var newActive = _railNav.find(selector);
                var newLink = newActive.find("a");

                oldActives.removeClass("active");
                newActive.addClass("active");
                
                if ((info.count % 2 !== 0 && info.direction === "down") || (info.count % 2 === 0 && info.direction === "up")) {
                    if (newActive.parent("ul").hasClass("topics")) {
                        newActive.animate({ 
                            left: '15px'
                        }, 200, "easeOutBounce", function () {
                            $(this).delay().animate({ left: 0 }, 800, "easeOutBounce");
                        });
                    } else {
                        newActive.animate({ 
                            right: '15px'
                        }, 200, "easeOutBounce", function () {
                            $(this).delay().animate({ right: 0 }, 800, "easeOutBounce");
                        });
                    }
                }
            }
        },
        articlesPage: {
            updatePopularity: function(id) {
                apiPromise = hubster.api.post("articles/" + id + "/conversions", { code: 100, v: _apiVersion, key: _apiKey });
            }
        },
        taxonomyPage: {
            init: function() {
                hubster.activateNewTrending();
                hubster.convertDates();
            }
        },
        gatherRails: function () {
            _rails = $(".rail");
            _railNav = $(".rail-navigation");
        }
    };
    
})(jQuery);

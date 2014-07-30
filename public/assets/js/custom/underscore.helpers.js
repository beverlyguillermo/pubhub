var underscoreHelpers = (function ($) {

    return {

        events: {
            
            /**
             * Get HTML for the topic-based thumbnail.
             * Based on Twig macro
             * @param  {object} event
             * @return string
             */
            topicThumbnail: function (event) {
                var topics = event._embedded.topics;
                if (topics) {
                    var randomTopic = topics[Math.floor(Math.random() * topics.length)];
                    return '<div class="placeholder"><a href="' + event.url + '"><img src="/assets/img/topics/' + randomTopic.slug + '.gif" /></a></div>';
                } else {
                    return "";
                }
            },

            /**
             * Get event date HTML.
             * Based on Twig macro
             * @param  {object} event
             * @return string
             */
            eventDate: function (event) {

                var niceDate = "";


                var startDate = moment(event.start_date, "YYYY-MM-DD");
                var startDateUnix = startDate.format("X");
                var endDate = moment(event.end_date, "YYYY-MM-DD");
                var endDateUnix = endDate.format("X");

                // create today's date in the same format as start_date
                // and end_date and then convert to unixtime
                var todayDate = moment(new Date()).format("YYYY-MM-DD");
                var todayDateUnix = moment(todayDate, "YYYY-MM-DD").format("X");


                // single day event
                if (startDateUnix == endDateUnix) {

                    if (startDateUnix == todayDateUnix) {
                        niceDate = "Today";
                    } else if (todayDateUnix < startDateUnix) {
                        // future
                        niceDate = startDate.format("MMMM D");
                    } else {
                        // past (add year)
                        niceDate = startDate.format("MMMM D, YYYY");
                    }


                // multi-day event
                } else {

                    if ((todayDateUnix >= startDateUnix && todayDateUnix <= endDateUnix) || startDateUnix >= todayDateUnix) {
                        // future
                        niceDate = startDate.format("MMMM D") + " - " + endDate.format("MMMM D");
                    } else {
                        // past (add year)
                        niceDate = startDate.format("MMMM D, YYYY") + " - " + endDate.format("MMMM D, YYYY");
                    }

                }


                // display

                var months = {
                    "January": "Jan",
                    "February": "Feb",
                    "August": "Aug",
                    "September": "Sept",
                    "October": "Oct",
                    "November": "Nov",
                    "December": "Dec"
                };

                niceDate = niceDate.replace(/January|February|August|September|October|November|December/g, function (match, offset, string) {
                    return months[match];
                });

                return '<div class="meta date"><i class="icon icon-calendar"></i><div class="text">' + niceDate + '</div></div>';
                
            },

            /**
             * Get event time HTML.
             * Based on Twig macro
             * @param  {object} event
             * @return string
             */
            eventTime: function (event) {

                if (event.start_time) {

                    var niceTime;

                    var startTime = moment(event.start_time, "HH:mm");

                    var niceStartTime = startTime.format("h:mm a");

                    if (startTime.format("mm") == "00") {
                        niceStartTime = startTime.format("ha");
                    }

                    niceTime = niceStartTime

                    if (event.end_time && event.start_time != event.end_time) {

                        var endTime = moment(event.end_time, "HH:mm");

                        if (startTime.format("a") == endTime.format("a")) {
                            niceStartTime = startTime.format("h:mm");

                            if (startTime.format("mm") == "00") {
                                niceStartTime = startTime.format("h");
                            }
                        }

                        var niceEndTime = endTime.format("h:mm a");

                        if (endTime.format("mm") == "00") {
                            niceEndTime = endTime.format("ha");
                        }

                        niceTime = niceStartTime + " - " + niceEndTime;

                    }

                    return '<div class="meta time"><i class="icon icon-time"></i><div class="text">' + niceTime + '</div></div>';

                }
            },

            /**
             * Get event location HTML.
             * Based on Twig macro
             * @param  {object} event
             * @return string
             */
            eventLocation: function (event) {

                var output = "";

                if (event._embedded.locations) {

                    var location = event._embedded.locations[0];
                    var mainLocation = location.name;
                    var campus = location.parent ? location.parent.name : null;
                    var supplement = event.supplemental_location_info;

                    output = '<div class="meta location"><i class="icon icon-map-marker"></i><div class="text">';

                    output += '<div class="building">';

                    if (supplement) {
                        output += supplement + ", ";
                    }

                    if (mainLocation) {
                        output += '<a href="#map">' + mainLocation + '</a><span class="nolink">' + mainLocation + '</span>';
                    }

                    output += "</div>"

                    if (campus) {
                        output += '<span class="campus">' + campus + '</span>';
                    }

                    output += '<div class="address"><a href="http://maps.google.com/?q=' +  location.address + ' ' + location.city + ' ' + location.state + ' ' + location.zipcode +'">' + location.address + '<br />' + location.city + ', ' + location.state + ' ' + location.zipcode + '</a></div>';

                    output += "</div></div>";


                } else if (event.supplemental_location_info) {

                    output = '<div class="meta location"><i class="icon icon-map-marker"></i><div class="text">' + event.supplemental_location_info + '</div></div>';

                }

                return output;

            },

            /**
             * Get registration HTML.
             * Based on Twig macro
             * @param  {object} event
             * @return string
             */
            eventRegistration: function (event) {
                return event.registration_required ? '<div class="meta registration"><span>Registration is required</span></div>' : "";
            },

            /**
             * Get an array of classes to apply to the event HTML element
             * Based on app/models/Pages::matchFiltersToContent()
             * @param  {object} event
             * @return array
             */
            getClasses: function (event) {

                var classes = ["teaser", "event", "force"];

                $.each(["locations", "topics"], function (i, filter) {

                    if (event._embedded[filter]) {

                        $.each(event._embedded[filter], function (i, f) {

                            classes.push(f.slug);
                            if (f.parent) {
                                classes.push(f.parent.slug);
                            }

                        });

                    }

                });

                return classes;
            }
        }

    };

})(jQuery);
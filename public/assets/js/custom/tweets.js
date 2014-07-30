var tweets = (function ($) {
    var $ = jQuery || {};

    var _foundTweets;
    var _cycle ;

    return {
    	loadTweets: function (container, cycle) {

            _cycle = cycle || false;

            var storefrontBase = "http://hub.jhu.edu/";
            var endpoint = "twitter/api";
            var requestData = {
                screenName: "HubJHU",
                typeTimeline: false,
                favsCount: 3,
                listCount: 10,
                showRetweets: false,
                typeList: true,
                typeFavs: true,
                listName: "hub-communications"
            };

            var twitterPromise = $.ajax({
                url: storefrontBase + endpoint,
                dataType: "html",
                data: requestData
            });

            twitterPromise.done(function (payload) {
                var tweetList = $("<ul class='tweet-list'/>");
                var items;

                tweetList.append(payload);
                items = tweetList.find("li");

                $.each(items, function (i, item) {
                    var $item = $(item);
                    var created = $item.attr("data-created");
                    var m = moment(created, "MM-DD-YYYY HH:mm");
                    var ago = m.fromNow();
                    $item.find(".created-at").text(ago);
                });

                container.append(tweetList);
                tweets.display(tweetList);
            });
            
            twitterPromise.fail(function (jqXHR, textStatus, errorThrown) {
                $.log(["Twitter API call failed to return", jqXHR, textStatus, errorThrown]);
            });
        },
        display: function(list) {

            _foundTweets = list.find("li");
            _max = _foundTweets.length - 1;
            _i = 0;
            var controller;
            var secondsPerTweet = 7;

            $.each(_foundTweets, function (_i, item) {
                var $tweet = $(item)
                var height = $tweet.height();
            });

            if (_cycle) {

                $("#advanceTweets").on("click", function (e) {
                    e.preventDefault();
                    cycle();
                });
                
                var runCycle = (function() {
                    controller = setInterval(tweets.cycle, secondsPerTweet * 1000);
                })();

                _foundTweets.on({ 
                    mouseenter: function (e) {
                        clearInterval(controller);
                    },
                    mouseleave: function (e) {
                        controller = setInterval(tweets.cycle, secondsPerTweet * 1000);
                    }
                });

            }
        },
        cycle: function() {

                var current = _foundTweets.eq(_i);
                var next;

                _i = (_i === _max) ? 0 : _i + 1;

                next = _foundTweets.eq(_i);

                // animate out
                current.toggleClass("active hidden");

                // animate in
                next.delay()
                    .css({
                        position: "relative",
                        top: "500px",
                        opacity: 0
                    })
                    .toggleClass("active hidden")
                    .animate({
                        top: 0,
                        opacity: 1
                    }, 400, "easeOutQuart");
            }
    }
})(jQuery);

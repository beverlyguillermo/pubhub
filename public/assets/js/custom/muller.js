window.onload = (function() {
	jQuery("document").ready(function ($) {

		var tribute = (function ($) {
	    
		    var templates = {};
		    
		    return {
		        compileTemplate: function (name, template) {
		            templates[name] = _.template(template);
		        },
		        guestbook: {
		            showEntries: function (page) {
		                $.ajax({
		                    "url": "http://web1.johnshopkins.edu/~assets/president/muller_guestbook/api/entries.php",
		                    "dataType": "jsonp",
		                    "data": { page: page || 1, per_page: 100 },
		                    "success": function (data) {

		                        var entries = _.filter(data, function (entry) {
		                            return entry.name !== null && entry.message !== null;
		                        });
		                        var html;
		                        
		                        if (entries.length > 0) {
		                        	console.log(templates.entry);
		                        	html = "<style>.gb-entries {  padding: 0; } .gb-entries li { display: none; } .gb-entries li.hidden { display: none; } .gb-entries li.active { display: block; }</style>";
		                            html += "<hr><h3>From the guestbook</h3>";
		                            html += "<ul class='gb-entries'>";
		                            html += templates.entry({ entries: entries });
		                            html += "</ul>";
		                            html += "<p><strong><a href='http://web.jhu.edu/administration/president/tributes/muller/guestbook'>Sign the guestbook &raquo;</a></strong><br><strong><a href='http://web.jhu.edu/administration/president/tributes/muller/guestbook'>View all entries &raquo;</a></strong></p><hr>";
		                        }
		                        
		                        $(".body-content").append(html);
		                        $(".gb-entries li:first").addClass("active");
		                        tribute.cycleEntries($("ul.gb-entries"));
		                    },
		                });
		            }
		        },
		        cycleEntries: function (list) {

		            var entries = list.find("li");
		            var max = entries.length - 1;
		            var i = 0;
		            var controller;
		            var secondsPerTweet = 10;

		            function cycle() {

		                var current = entries.eq(i);
		                var next;

		                i = (i === max) ? 0 : i + 1;

		                next = entries.eq(i);

		                // out
		                current.toggleClass("active hidden");

		                // in
		                next.delay()
		                    .css({
		                        position: "relative",
		                        opacity: 0
		                    })
		                    .toggleClass("active hidden")
		                    .animate({
		                        opacity: 1
		                    }, 400, "easeOutQuart");
		            }

		            var runCycle = (function() {
		                controller = setInterval(cycle, secondsPerTweet * 1000);
		            })();

		            entries.on({ 
		                mouseenter: function (e) {
		                    clearInterval(controller);
		                },
		                mouseleave: function (e) {
		                    controller = setInterval(cycle, secondsPerTweet * 1000);
		                }
		            });
		        }
		    };
		    
		}($));

	    var entryTemplate = "<% _.each(entries, function (entry) { %><% if (entry.message && entry.name) { %><li><p class='message' style='line-height:130%;'><%= entry.message %></p><p class='name'>&mdash;<%= entry.name %></p></li><% } %><% }); %>";
	    tribute.compileTemplate("entry", entryTemplate);
	    tribute.guestbook.showEntries();
	    
	});
});
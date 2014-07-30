<% _.each(_embedded.articles, function (article) { %>

	<%
		// Create a new language ("short") that will create short output.
		// For example, instead of "4 days ago," our new language will output "4d."
		moment.lang('short', {
		    relativeTime : {
		        // stuff we changed
				s: "s",
				m: "1m",
				mm: "%dm",
				h: "1h",
				hh: "%dh",
				d: "1d",
				dd: "%dd",
				
				// defaults (need to be included, otherwise, we get errors)
				future: "in %s",
				past: "%s ago",
				M: "a month",
				MM: "%d months",
				y: "a year",
				yy: "%d years"
		    }
		});

		// Use short language
		moment.lang('short');

		var day = moment.unix(article.publish_date); 
		var now = moment(new Date());
		var diff = now.diff(day, "hours");
		var time = day.fromNow(true);

		// Reset back to English
		moment.lang('en');
	%>

	<li><a href="<%= article.url %>"><span class="ago" data-time="<%= day.format('MMMM D YYYY h:mm:s a') %>"><%= time %></span> <%= article.headline %></a></li>
<% }); %>
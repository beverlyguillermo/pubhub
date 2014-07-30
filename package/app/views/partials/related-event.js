<div class="related-events count-<%= _embedded.events.length %>">
	<% _.each(_embedded.events, function (event) { %>
		
		<%
		// setup classes on the main div
		var classes = ["teaser", "event", "force"];
		var thumb = event._embedded.image_thumbnail;

		if (thumb) {
			classes.push("has-image");
		} else {
			classes.push("no-image");
		}
	    %>

		<div class="<%= classes.join(' ') %> force">

			<div class="thumbnail">
				<% if (thumb) { %>
					<a href="<%= event.url %>"><img class="thumbnail" src="<%= thumb[0].sizes.thumbnail %>" /></a>
				<% } else { %>
					<%= thelpers.events.opicThumbnail(event) %>
				<% } %>
			</div>

			<div class="teaser-text">
				<h5 class="overline"><%= helpers.events.eventDate(event) %></h5>
				<h2><a href="<%= event.url %>"><%= event.name %></a></h2>
								
				<% if (event.excerpt) { %>
				<div class="summary">
					<span class="summary-text"><%= event.excerpt %></span>
				</div>
				<% } %>
			</div>

		</div>

	<% }); %>
</div>
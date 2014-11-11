<div class="loaded-articles count-<%= _embedded.articles.length %>">
<% _.each(_embedded.articles, function (article, i) { %>
	<% 
		var thumb = article._embedded.image_thumbnail;
		var kicker = article.kicker;
		var classes = ["teaser", "col", "article"];

		if (!thumb) {
			classes.push("no-image");
		}
		if (kicker) {
			classes.push("kicker");
		}

		if (i == 2) {
			classes.push("end");
		}
	%>
	<div class="<%= classes.join(' ') %>">
	
		<% if (thumb) { %>
			<a href="<%= article.url %>"><img class="thumbnail" src="<%= thumb[0].sizes.thumbnail %>" /></a>
		<% } %>

		<% 
			// Create publish date using momentJS
            var m = moment.unix(article.publish_date);
            var now = moment(new Date());
            var diff = now.diff(m, "hours");
            var pubDate;

            // Use "ago" if the publish date is less than 72 hours ago
            if (diff < 72) {
                pubDate = m.fromNow();
            } else if (m.format("YYYY") != now.format("YYYY")) {
                pubDate = m.format("MMMM D, YYYY");
            } else {
                pubDate = m.format("MMMM D");
            }
		%>

		<% if (kicker) { %>
			<h5 class="overline"><%= kicker %></h5>
		<% } %>
		<h3><a href="<%= article.url %>"><%= article.alt_headline || article.headline %></a></h3>

		<% if (article.type == "magazine_article" || article.type == "gazette_article") { %>
			
			<span class="meta publish-date"><i class="icon icon-time"></i> <%= article.publication_content.issue.short_edition %> <%= article.publication_content.issue.year %></span>

		<% } else { %>

			<div style="white-space: nowrap;" class="publish-date" data-stamp="<%= m.format('MMMM D YYYY h:mm:s a') %>">
				<i class="icon icon-time"></i> <%= pubDate %>
			</div>

		<% } %>

		<div class="media-promo">
			<% if (article._embedded.videos) { %>
		 		&nbsp;&nbsp;|&nbsp;&nbsp;<i class="icon-facetime-video"></i>&nbsp;Video
		 	<% } %>

		 	<% if (article._links.galleries) { %>
		 		&nbsp;&nbsp;|&nbsp;&nbsp;<i class="icon-camera"></i>&nbsp;Photos
		 	<% } %>
	 	</div>

		<%
			if (article.type === "magazine_article") {
				article.source = {
					name: "Johns Hopkins Magazine",
					url: article.url,
					internal: true
				};
			}
			if (article.type === "gazette_article") {
				article.source = {
					name: "Gazette",
					url: article.url,
					internal: true
				};
			}
		%>

		<div class="summary">
			<span class="summary-text"><%= article.excerpt || "" %></span>

			<% if (article.source && article.source.name) { %>

				<span class="source">
					<a href="<%= article.source.url %>">
						<span class="source-separator">/</span>&nbsp;<%= article.source.name %>
						<% if (!article.source.internal) { %>
						<% } %>
					</a>
				</span>

			<% } %>

		</div>

	</div>

<% }); %>
</div>

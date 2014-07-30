<% _.each(data, function (article) { %>

	<%
		// Setup initial classes
		var classes = ["teaser", "article", "force"];

		// Add a class for the image or lack thereof
		var image = article._embedded.image_thumbnail;
		if (image) {
			classes.push("has-image");
		} else {
			classes.push("has-image");
		}

		// Add classes for each tag
		if (article._embedded.tags) { 
			_.each(article._embedded.tags, function (tag) {
				classes.push(tag.slug);
			});
		}

		// Setup headline
		var headline = article.alt_headline ? article.alt_headline : article.headline;

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

	<div class="<%= classes.join(" ") %>" data-pub="<%= article.publish_date %>">

		<% if (image) { %>
			<div class="thumbnail">
				<a href="<%= article.url %>"><img src="<%= image[0].sizes.thumbnail %>" /></a>
			</div>
		<% } %>

		<% if (article.kicker) { %>
			<h5 class="overline"><%= article.kicker %></h5>
		<% } %>

		<h2><a href="<%= article.url %>"><%= headline %></a></h2>

		<div style="white-space: nowrap;" class="publish-date" data-stamp="<%= m.format('MMMM D YYYY h:mm:s a') %>">
			<i class="icon icon-time"></i> <%= pubDate %>
		</div>

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
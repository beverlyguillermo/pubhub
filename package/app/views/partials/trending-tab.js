<% _.each(_embedded.articles, function (article) { %>
	<li><a href="<%= article.url %>"><%= article.headline %></a></li>
<% }); %>
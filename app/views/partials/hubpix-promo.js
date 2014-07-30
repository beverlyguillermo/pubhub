<% _.every(photos, function (photo, i) { %>

	<% if ((i + 1) > numToDisplay) {
		return false;
	} %>

	<%
	var classes = ["photo"];
	if ((i + 1) % 3 == 0) {
		// this is the third in the list
		classes.push("end");
	}
	%>
	<div class="<%= classes.join(' ') %>" data-username="<%= photo.user.username %>" data-id="<%= photo.urlId %>" data-tags="<%= photo.tags.join(' ') %>">
		<a href="<%= photo.link %>"><img src="<%= photo.images.low_resolution.url %>" /></a>
	</div>

	<% return true; %>

<% }); %>
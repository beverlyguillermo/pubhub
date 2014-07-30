<table>
<tr>
<%
var thumb;
if (_embedded.image_thumbnail) {
	thumb = _embedded.image_thumbnail[0];
}

if (thumb) { %>
	<td class="thumbnail">
		<img src="<%= thumb.sizes.thumbnail %>" />
	</td>
<% } %>
<td>
	<p>
		<strong><%= name %></strong>
		<a class="action-link action-edit label" href="<%= edit_url %>" target="_newtab">Edit</a>&nbsp;<a class="action-link action-view label" href="<%= url %>" target="_newtab">View</a>
	</p>
</td>
</tr>
</table>
<%

function topicThumbnail(event) {
	var topics = event._embedded.topics;
	if (topics) {
		var randomTopic = topics[Math.floor(Math.random() * topics.length)];
		return '<div class="placeholder"><a href="' + event.url + '"><img src="/assets/img/topics/' + randomTopic.slug + '.gif" /></a></div>';
	} else {
		return "";
	}
}

function eventDate(event) {
	
	var startDate = moment(event.start_date, "YYYY-MM-DD");
	var niceDate = startDate.format("MMMM D");

	if (event.end_date && event.start_date != event.end_date) {
		var endDate = moment(event.end_date, "YYYY-MM-DD");

		if (endDate.format("MMMM") === startDate.format("MMMM")) {
			niceDate += " - " + endDate.format("D");
		} else {
			// shorten the month names
			niceDate = startDate.format("MMM D") + " - " + endDate.format("MMM D");
		}
	}

	var months = {
		"January": "Jan",
		"February": "Feb",
		"August": "Aug",
		"September": "Sept",
		"October": "Oct",
		"November": "Nov",
		"December": "Dec"
	};

	for (var key in months) {
		niceDate = niceDate.replace(key, months[key]);
	}

	return '<div class="meta date"><i class="icon icon-calendar"></i><div class="text">' + niceDate + '</div></div>';
}

function eventLocation(event) {
	
	var niceLocation = "";
	var open = '<div class="meta location"><i class="icon icon-map-marker"></i><div class="text">';
	var close = '</div></div>';

	if (event._embedded.locations) {

		niceLocation += open;

			var location = event._embedded.locations[0];
			niceLocation += '<div class="building">' + location.name + '</div>';
				
		niceLocation += close;

	} else if (event.supplemental_location_info) {

		niceLocation += open;
		niceLocation += event.supplemental_location_info;
		niceLocation += close;
	}

	return niceLocation;

}

%>

<div class="loaded-events count-<%= _embedded.events.length %>">
<% _.each(_embedded.events, function (event, i) { %>
	<% 
		var thumb = event._embedded.image_thumbnail;
		var teaserNum = i + 1;
		var classes = ["teaser", "teaser-" + teaserNum, "event", "col", "force"];
	%>
	<div class="<%= classes.join(' ') %>">

		<h5 class="overline"><%= eventDate(event) %></h5>

		<div class="thumbnail">
			<% if (thumb) { %>
				<a href="<%= event.url %>"><img class="thumbnail" src="<%= thumb[0].sizes.thumbnail %>" /></a>
			<% } else { %>
				<%= topicThumbnail(event) %>
			<% } %>
		</div>

		<h3><a href="<%= event.url %>"><%= event.alt_name || event.name %></a></h3>

		<%= eventLocation(event) %>

		<% if (event.excerpt) { %>
			<div class="summary">
				<span class="summary-text"><%= event.excerpt %></span>
			</div>
		<% } %>

	</div>

<% }); %>
</div>
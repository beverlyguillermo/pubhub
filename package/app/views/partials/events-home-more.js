<%

// Used to load more events on /events

_.each(_embedded.events, function (event) {
    
    var classes = helpers.events.getClasses(event);

    if (event.featured) {
        classes.push("featured");
    }

    var image = event._embedded.image_thumbnail;
    if (image) {
        classes.push("has-image");
    }

    %>

    <div class="<%= classes.join(" ") %>" "data-start"="<%= event.start_date %>" "data-end"="<%= event.end_date %>">


        <% if (event.featured) { %>
            <div class="featured-tag"><i class="icon-star"></i> <span>Featured</span></div>
        <% } %>
        
        <div class="thumbnail">
            <% if (image) { %>
                <a href="<%= event.url %>">
                <div class="{{ class }}">
                    <img src="<%= image[0].sizes.thumbnail %>" alt="<%= image[0].title %>" />
                </div>
                </a>
            <% } else { %>
                <%= helpers.events.topicThumbnail(event) %>
            <% } %>
        </div>

        <div class="teaser-text">

            <h5 class="overline"><%= helpers.events.eventDate(event) %></h5>
            <h2><a href="<%= event.url %>"><%=  event.name %></a></h2>
            <%= helpers.events.eventTime(event) %>
            <%= helpers.events.eventLocation(event) %>
            <%= helpers.events.eventRegistration(event) %>

            <% if (event.excerpt) { %>
                <div class="summary">
                    <span class="summary-text"><%= event.excerpt %></p></span>
                </div>
            <% } %>

        </div>

    </div>

<% }); %>
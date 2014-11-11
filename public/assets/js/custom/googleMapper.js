var googleMapper = (function (google, $) {

	var _selector;

	return {

		init: function (selector) {
			_selector = $(selector);
			return this;
		},

		createMap: function (latitude, longitude) {

			var latLong = new google.maps.LatLng(latitude, longitude);

			var options = {
				zoom: 17,
				center: latLong,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			}
			var node = _selector.get(0); // what google is expecting
			var map = new google.maps.Map(node, options);

			// show the map
			_selector.css({
				height: "400px",
				width: "100%"
			});

			return map;
		},

		plotPoint: function (map, latitude, longitude) {

			var latLong = new google.maps.LatLng(latitude, longitude);

			var marker = new google.maps.Marker({
				position: latLong,
				map: map
			});

			return marker;
		},

		/**
		 * [ description]
		 * @param  {object} map  	Google maps object (returned from createMap)
		 * @param  {object} marker 	Google marker object (returned from plotPoint)
		 * @param  {string} html 	HTML to show in the window
		 * @param  {boolean} show 	Show the info window on load?
		 * @return {object} infoWindow
		 */
		addInfoWindow: function(map, marker, html, show, maxWidth) {

			var options = {
				content: html
			};

			if  (maxWidth) {
				options.maxWidth = maxWidth;
			}

			var infoWindow = new google.maps.InfoWindow(options);

			google.maps.event.addListener(marker, 'click', function() {
				infoWindow.open(map, marker);
			});

			if (show) {
				// wait a couple seconds to make sure the map is loaded
				setTimeout(function () {
			        google.maps.event.trigger(marker, 'click');
			    }, 2500);
			}

			return infoWindow;
		},

		/**
		 * Generate HTML for a location object
		 * @param {object} location Location object from Hub API
		 * @return string The compiled HTML
		 */
		locationHtml: function(location) {

			var html = "<div class='mapsInfoWindow'>";

			// name
			html += "<h1>" + location.name + "</h1>";

			// address
			html += "<address>";
			if (location.address) {
				html += location.address + "<br />";
			}

			if (location.city && location.state) {
				html += location.city + ", " + location.state;
				if (location.zipcode) {
					html += " " + location.zipcode;
				}
			} else {
				if (location.city) {
					html += location.city;
				}
				if (location.state) {
					html += location.city;
				}
				if (location.zipcode) {
					html += " " + location.zipcode;
				}
			}
			html += "</address>";

			// other deets
			html += "<div class='otherContact'>";

			if (location.latitude && location.longitude) {
				html += "<p class='directions'><a href='https://www.google.com/maps/dir/Current+Location/" + location.latitude + "," + location.longitude + "'>Get directions &raquo;</a>";
			}

			if (location.website) {
				html += "<p class='website'><a href='" + location.website + "'>Website &raquo;</a>";
			}

			if (location.email) {
				html += "<p class='email'><a href='mailto:" + location.email + "'>Email &raquo;</a>";
			}

			if (location.phone) {
				html += "<p class='phone'>" + location.phone + "</a>";
			}
			html += "</div>";

			html += "</div>";
			
			return html;
		}

	};

})(google, jQuery);

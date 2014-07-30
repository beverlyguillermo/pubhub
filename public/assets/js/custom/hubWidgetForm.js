var hubWidgetForm = (function ($) {

	var _form = $("#hubWidgetForm");
	var _style = $(".widget-preview link");

	var _styles = {
		none: "",
		light: "http://hub.jhu.edu/assets/shared/css/widget-light.css",
		dark: "http://hub.jhu.edu/assets/shared/css/widget-dark.css"
	};

	function setupEvents() {
		_form.on("change", "input", hubWidgetForm.updateWidget);
	}

	return {

		init: function() {
			setupEvents();
			return hubWidgetForm;
		},

		updateWidget: function(e) {

			var name = this.name;
			if (name && (name === "first_name" || name === "last_name" || name === "email")) {
				return;
			}

			// update data attributes

			var attrs = {};

			attrs.title = _form.find("input[name=title]").attr("value");
			attrs.count = _form.find("input[name=count]").attr("value");
			attrs.tags = _form.find("input[name=tags]").attr("value");

			attrs.topics = [];
			$.each(_form.find("input[name='topics[]']:checked"), function(i, value) {
				attrs.topics.push($(this).attr("value"));
			});

			var html = "<div id='hubWidget' version='0' key='d9dc617e3c52275069213ad4381c1431897cace3' data-title='" + attrs.title + "' data-count='" + attrs.count + "'";

			if (attrs.topics.length > 0) {
				html += " data-topics='" + attrs.topics.join(",") + "'";
			}

			if (attrs.tags != "") {
				html += " data-tags='" + attrs.tags + "'";
			}


			html += "></div>";


			$(".hubWidgetContainer").html(html);

			// reload widget
			var widget = document.getElementById("hubWidget");
			widgetCreator.create(widget, {
				version: widget.getAttribute("version"),
				key: widget.getAttribute("key")
			});



			// update stylesheet
			var style = _form.find("input[name='theme']:checked").attr("value");

			if (style == "light" || style == "none") {
				$(".widget-preview #hubWidget").css({
					backgroundColor: "none",
					padding: 0
				});
			} else {
				$(".widget-preview #hubWidget").css({
					backgroundColor: "#000",
					padding: 10
				});
			}

			_style.attr("href", _styles[style]);

		}

	}

})(jQuery);
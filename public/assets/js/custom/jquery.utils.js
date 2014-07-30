(function ($) {

	/**
	 * Easy logging to console for debugging
	 * @param {mixed} message [Pass any kind of message to be logged if possible]
	 * @param {boolean} split [Pass in true to split an array of messages into multilines]
	 * @return undefined
	 */
	
	// jQuery debug constant created and set to false by default.
	// To turn on logging, call $.DEBUG = true from within your application code.
	$.DEBUG = false;

	$.log = function () {
		var message = arguments[0],
			split = arguments[1],
			messageNum,
			i;

		if (!$.DEBUG || !window.console || !window.console.log || typeof window.console.log !== "function") {
			return false;
		}

		if (split && typeof message === "object") {
			messageNum = message.length;
			if (messageNum) {
			// It's an array, use a for loop
				for (i = 0; i < messageNum; i += 1) {
					$.log(message[i]);
				}
			} else {
				// Object here, use the EVIL for/in
				for (var prop in message) {
					if (message.hasOwnProperty(prop)) {
						$.log([prop + ":", message[prop], " "], true);
					}
				}
			}
			return;
		}

		window.console.log(message);
		return;
	};

	/**
	 * PubSub Add-on, should move to the tiny pubsub file
	 * after I fork Ben Alman's gist and add this line
	 */
	$.fn.subscribe = function (event, data) {
		var name = $(this).attr("data-section-name");
		// Add a reference to 'this' to the event data
		$.subscribe(event, {target: this, sectionName: name}, data);
	};

	$.fn.unsubscribe = function (event) {
		$.unsubscribe(event);
	}

})(jQuery);
var preview = (function ($) {

	return {
		/**
	     * Checks the URL to see if we are on a preview URL.
	     * @return {boolean}
	     */
		isPreview: function() {

	        var pathname = window.location.pathname;
	        var parts = pathname.split("/");

	        var length = parts.length;

	        if (parts[length - 1] == "preview" || parts[length - 1] == "" && parts[length - 2] == "preview") {
	            return true;
	        } else {
	            return false;
	        }
	    },
        /**
         * If we are on a preview URL, find all the links that contain
         * "hub.jhu.edu" within them and all the relative links. Add
         * "/preview" to the end of these links
         * 
         * @param {object} elem Element to search for links in
         * @return null
         */
        previewifyLinks: function (elem) {

            if (!elem) {
                elem = $("body");
            }

            if (preview.isPreview()) {
                var links = elem.find("a[href*='hub.jhu.edu'], a[href^='/']");
                links.each(function () {
                    $(this).attr("href", $(this).attr("href") + "/preview");
                });
            }
        }
	}

})(jQuery);
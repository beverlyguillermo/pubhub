/**
 * Hubstagram-specific library of JS methods
 */
var hubstagram = (function ($) {

	/**
	 * Hubstagram library object for reference
	 * inside return object.
	 * @type {Object}
	 */
	var _library;

	/**
	 * Default settings
	 * @type {Object}
	 */
	var _defaultSettings = {
		imageContainerSelector: ".container",
		item: ".photo"
	};

	/**
	 * DOM object that contains the photos
	 * @type {jQuery Object}
	 */
	var _container;


	var _item;
	
	/**
	 * Tags on photos in the current pool
	 * @type {Object}
	 */
	var _tags;

	/**
	 * Underscore.js template to render the
	 * Hub/Pix promo section
	 */
	var _template;

	var _env = window.location.href.split("//")[1].split(".")[0];
	var _urlPrefix = $.inArray(_env, ["local", "staging", "beta"]) > -1 ? _env + "." : "";
    var _apiBase = "http://" + _urlPrefix + "hub.jhu.edu/";


	/**
	 * Used to get photos for sidebar promo
	 * @param  {Object}   data
	 * @param  {Function} callback
	 * @return {null}
	 */
	function getPhotos(data, callback) {
		return $.ajax({
		    url: _library.baseUrl + "instagram/api",
		    dataType: "jsonP",
		    data: data,
		    success: callback,
		    error: function() {
		    	// do something here
		    }
		});
	}
	

	return {

		/**
		 * User defined settings
		 * @type {Object}
		 */
		userSettings: {},

		baseUrl: _apiBase,

		/**
		 * Sets up basic initialization
		 * @param  {Object} settings
		 * @return {Object} _library
		 */
		init: function (settings) {

			_library = this;
			_library.userSettings = $.extend({}, _defaultSettings, settings);
			_container = $(_library.userSettings.container);
			_item = $(_library.userSettings.item);

			return _library;
		},

		/**
		 * Takes care of configuring and displaying
		 * the Hub/Pix page.
		 * @return {Object} _library
		 */
		display: function () {

			// set up isotope
			_container.isotope({
				masonry: {
					columnWidth: 76
				}
			});

			// configure shuffle
			var shuffleTrigger = _library.userSettings.shuffleTrigger || {};
			
			shuffleTrigger.hover(
				function () {
					$(this).css({"cursor": "pointer"});
				},
				function () {
					$(this).css({"cursor": "default"});
				}
			);

			shuffleTrigger.on("click", function() {
				_container.isotope("shuffle");
			});
			
			hubstagram.cleanDom(_library.userSettings);

			return _library;
		},

		/**
		 * Takes care of configuring and displaying
		 * the Hub/Pix promo area
		 * @return {[type]} [description]
		 */
		promote: function (count) {

			_template = $(_library.userSettings.template).html();

			var data = $.extend({ count: 3 }, { count: count });

			getPhotos(data, function (payload) {

				// clean photos
				var cleaned = _library.cleanArray(payload);

				// plop in template
				var parsedTemplate = _.template(_template, { photos: cleaned.media, numToDisplay: data.count });

				_container.addClass("loaded");
                _container.removeClass("data-loading");

				_container.html(parsedTemplate);
			});
			
			return _library;
		},

		filter: function (filterBy) {
			_container.isotope({
				filter: filterBy
			});
		},
		
		/**
		 * Clean photos already in the DOM by getting rid
		 * of banned photos and photos by banned users.
		 * This is used on Hub/Pix page
		 * 
		 * @return null
		 */
		cleanDom: function () {
			
			var bannedUsers = _library.userSettings.bannedUsers || [];
			var bannedPhotos = _library.userSettings.bannedPhotos || [];

			_container.find(_item).each(function (index, value) {
				var username = $(this).attr("data-username");
				var id = $(this).attr("data-id");

				if ($.inArray(username, bannedUsers) > -1 || $.inArray(id, bannedPhotos) > -1) {
					hubstagram.removePhoto($(this));
				}
			});

			hubstagram.filterTags();
			hubstagram.displayTags();
		},

		/**
		 * Clean photos in an object by getting rid
		 * of banned photos and photos by banned users.
		 * 
		 * This is used for the Hub/Pix promo and can
		 * be used by Hub/Pix page if we ever move
		 * to ASYNC loading.
		 *
		 * @param {Object} Payload object
		 * @return {Object} Cleaned payload object
		 */
		cleanArray: function(object) {

			var bannedUsers = object.recentlyBannedUsers || [];
			var bannedPhotos = object.recentlyBannedPhotos || [];

			// Keep track of the indexes to remove later. Cannot do this
			// within foreach because it updates the indexes of all elements
			// if one is removed.
			var indexesToRemove = [];

			$.each(object.media, function (index, photo) {

				var username = photo.user.username ;
				var id = photo.urlId;

				if ($.inArray(username, bannedUsers) > -1 || $.inArray(id, bannedPhotos) > -1) {
					indexesToRemove.push(index);

					// comment this back in when hub/pix is using ASYNC
					// var photoTags = photo.tags;
					// _library.updateTagCount(photoTags);
				}
			});
			
			// Splice array with multiple indicies. Found here:
			// http://upshots.org/actionscript/javascript-splice-array-on-multiple-indices-multisplice
			for (var i = 0; i < indexesToRemove.length; i++){
		        var index = indexesToRemove[i] - i;
		        object.media.splice(index, 1);
		    }

			return object;
		},

		/**
		 * Gets rid of tags with less than a count of 3
		 * @return null
		 */
		filterTags: function () {
			$.each(_library.userSettings.tags, function (index, value) {
				if (value.count < 3) {
					delete _library.userSettings.tags[index];
				}
			});
		},

		/**
		 * Remove a given photo from the stream and
		 * update the tag count on tags on that photo.
		 * 
		 * @param  {object} photo Photo object
		 * @return null
		 */
		removePhoto: function (photo) {

			_container.isotope("remove", photo);

			// Update tags
			var photoTags = photo.attr("data-tags").split(" ");
			_library.updateTagCount(photoTags);
			
		},

		/**
		 * Substract one from each tag's count
		 * @param  {array} tags 
		 * @return null
		 */
		updateTagCount: function (tags) {
			$.each(tags, function (index, value) {
				if (_library.userSettings.tags[value]) {
					_library.userSettings.tags[value]["count"] = _library.userSettings.tags[value]["count"] - 1;
				}
			});
		},

		/**
		 * Display the final list of tags.
		 * @return null
		 */
		displayTags: function () {
			var html = "";
			$.each(_library.userSettings.tags, function (index, value) {
				html += "<li><a href=\"#\" data-filter=\".tag-" + index + "\" onclick=\"_gaq.push(['_trackEvent', 'Hub Pix', 'Tags', '" + index + "']);\">#" + index + "</a><span class=\"tag-count\">( " + value.count + " )</span></li>";
			});

			$(".tag-set ul").append(html);
		}
	};

})(jQuery);
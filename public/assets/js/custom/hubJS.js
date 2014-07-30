// Production steps of ECMA-262, Edition 5, 15.4.4.19
// Reference: http://es5.github.com/#x15.4.4.19
// 
// From: https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Array/map
if (!Array.prototype.map) {
  Array.prototype.map = function(callback, thisArg) {
 
    var T, A, k;
 
    if (this == null) {
      throw new TypeError(" this is null or not defined");
    }
 
    // 1. Let O be the result of calling ToObject passing the |this| value as the argument.
    var O = Object(this);
 
    // 2. Let lenValue be the result of calling the Get internal method of O with the argument "length".
    // 3. Let len be ToUint32(lenValue).
    var len = O.length >>> 0;
 
    // 4. If IsCallable(callback) is false, throw a TypeError exception.
    // See: http://es5.github.com/#x9.11
    if (typeof callback !== "function") {
      throw new TypeError(callback + " is not a function");
    }
 
    // 5. If thisArg was supplied, let T be thisArg; else let T be undefined.
    if (thisArg) {
      T = thisArg;
    }
 
    // 6. Let A be a new array created as if by the expression new Array(len) where Array is
    // the standard built-in constructor with that name and len is the value of len.
    A = new Array(len);
 
    // 7. Let k be 0
    k = 0;
 
    // 8. Repeat, while k < len
    while(k < len) {
 
      var kValue, mappedValue;
 
      // a. Let Pk be ToString(k).
      //   This is implicit for LHS operands of the in operator
      // b. Let kPresent be the result of calling the HasProperty internal method of O with argument Pk.
      //   This step can be combined with c
      // c. If kPresent is true, then
      if (k in O) {
 
        // i. Let kValue be the result of calling the Get internal method of O with argument Pk.
        kValue = O[ k ];
 
        // ii. Let mappedValue be the result of calling the Call internal method of callback
        // with T as the this value and argument list containing kValue, k, and O.
        mappedValue = callback.call(T, kValue, k, O);
 
        // iii. Call the DefineOwnProperty internal method of A with arguments
        // Pk, Property Descriptor {Value: mappedValue, : true, Enumerable: true, Configurable: true},
        // and false.
 
        // In browsers that support Object.defineProperty, use the following:
        // Object.defineProperty(A, Pk, { value: mappedValue, writable: true, enumerable: true, configurable: true });
 
        // For best browser support, use the following:
        A[ k ] = mappedValue;
      }
      // d. Increase k by 1.
      k++;
    }
 
    // 9. return A
    return A;
  };      
}

/* **********************************************
     Begin deferred.js
********************************************** */

var Deferred = (function() {

	var deferred = {

		newDefer: {},

		resolve: function(responseText, status, statusText) {

			// result of the function passed to then()
			var result = deferred.fulfilled(responseText, status, statusText);

			if (result && result.then) {
				// we need to wait here until promise resolves
				result.then(function(data) {
					deferred.newDefer.resolve(data);
				});
			}

			else if (typeof deferred.newDefer.resolve == "function") {
				// another 'then' was defined
				deferred.newDefer.resolve(result);
			}
		},

		reject: function(promiseOrValue) {

			// result of the function passed to then()
			var result = deferred.error(promiseOrValue);

			if (promiseOrValue && promiseOrValue.then) {
				// we need to wait here until promise resolves
				promiseOrValue.then(function(data) {
					deferred.newDefer.resolve(data);
				});
			}

			else if (typeof deferred.newDefer.reject == "function") {
				// another 'then' was defined
				deferred.newDefer.reject(result);
			}
		},

		fulfilled: function(responseText, status, statusText) { },

		error: function(status, statusText) { },

		// what arguments, when does this fire?
		progress: function() { },

		promise: {

			then: function(fulfilled, error, progress) {

				deferred.fulfilled = typeof fulfilled == "function" ? fulfilled : function() {};
				deferred.error = typeof error == "function" ? error : function() {};
				deferred.progress = typeof progress == "function" ? progress: function() {};

				// This function should return a new promise that is fulfilled when the given
				// fulfilledHandler or errorHandler callback is finished
				deferred.newDefer = new Deferred();
				return deferred.newDefer.promise;
			}
		}
	}

	return deferred;

});

/* **********************************************
     Begin simplyAjax.js
********************************************** */

var simplyAjax = (function () {

	/**
	 * Sets _xhr to either XMLHttpRequest or
	 * the correct version ofActiveXObject
	 * 
	 * @return {Object}
	 */
	function getXHR() {
		var xhr;

		if (typeof XMLHttpRequest !== 'undefined') {
			xhr = new XMLHttpRequest();
		
		} else {
			var versions = ["Microsoft.XmlHttp", "MSXML2.XmlHttp", "MSXML2.XmlHttp.3.0", "MSXML2.XmlHttp.4.0", "MSXML2.XmlHttp.5.0"];
			
			for (var i = 0, len = versions.length; i < len; i++) {
				try {
					xhr = new ActiveXObject(versions[i]);
					break;
				}
				catch (e) {}
			}
		}
		return xhr;
	}

	/**
	 * Attach a script element to the current page
	 * referencing the URL we need to make a GET
	 * request to
	 * @param  {string} url Full URL (with query string)
	 * @return null
	 */
	function crossDomainRequest(url) {
		var script = document.createElement("script");
		script.src = url;
		document.body.appendChild(script);
	}

	return {

		/**
		 * Make a GET request
		 * 
		 * @param  {Object} obj
		 *         url: URL to make the request to
		 *         data: Plain object of key: value pairs to send with the request
		 *         dataType: right now, specifiying jsonp is the only thing that does anything
		 *         success: callback function to fire upon a successful GET request (data, statusCode, statusText)
		 *         fail: callback function to fire upon a failed GET request (statusCode, statusText)
		 * @return null
		 */
		get: function(obj) {

			var deferred = new Deferred();

			if (obj.dataType && obj.dataType.toLowerCase() == "jsonp") {

				// assign success callback to a function on ajax object
				var cb = simplyAjax.utility.randomCallbackName();
				simplyAjax[cb] = deferred.resolve;

				// assign callback in URL
				obj.data.callback = "simplyAjax." + cb;
				var url = obj.url + simplyAjax.utility.createQueryString(obj.data);
				
				crossDomainRequest(url);

			} else {

				var url = obj.url + simplyAjax.utility.createQueryString(obj.data);

				var xhr = getXHR();
				
				xhr.onreadystatechange = function() {

					if (xhr.readyState === 4) {

						if (xhr.status == 200) {
							deferred.resolve(xhr.responseText, xhr.status);
						} else {
							deferred.reject(xhr.status);
						}
					}	
				}
				
				xhr.open("GET", url, true);
				xhr.send(null);
			}

			return deferred.promise;
		
		},

		utility: {
			/**
			 * Create a query string from an object
			 * containing key: value pairs
			 * @param  {Object} object
			 * @return {string} Query string
			 */
			createQueryString: function(object) {

				var queryString = "";
				var amp = false;

				for (var key in object) {
					if (amp) {
						queryString += "&";
					}
					queryString += key + "=" + object[key];

					// start adding the ampersand from now on
					amp = true;
				}

				return "?" + queryString;
			},

			random: function() {
				return Math.floor(Math.random() * (100000 * 100000));
			},

			randomCallbackName: function() {
				var timestamp = new Date().getTime();
				return "simplyAjax_" + simplyAjax.utility.random() + "_" + timestamp + "_" + simplyAjax.utility.random();
			}
		}
	}
})();

/* **********************************************
     Begin hubJS.js
********************************************** */

/**
 * @codekit-prepend "prototypes.js"
 * @codekit-prepend "deferred.js"
 * @codekit-prepend "simplyAjax.js"
 */


var hubJS = (function (ajax) {

	/**
	 * Hub library object for reference inside
	 * return object.
	 */
	var _library;

	/**
	 * Default settings
	 * @type {Object}
	 */
	var _defaultSettings = {
		version: 0,
		key: "",
		error: function() {}
	};


	return {

		/**
		 * User defined settings
		 * @type {Object}
		 */
		userSettings: {},

		/**
		 * API base URL
		 * @type {String}
		 */
		baseUrl: "http://api.hub.jhu.edu/",

		/**
		 * Initialize the Hub library.
		 * 
		 * @param  {object} settings
		 * @return null
		 */
		init: function (settings) {
			_library = this;
			_library.userSettings = _library.utility.extend({}, _defaultSettings, settings);
		},

		/**
		 * Gets a payload from the Hub API
		 * 
		 * @param  {string} 	endpoint  	API endpoint
		 * @param  {object} 	data     	Data to be sent to the server
		 * @return {Object}    	promise
		 */
		get: function(endpoint, data) {

			var data = _library.utility.extend({}, data);
			data.v = _library.userSettings.version;
			data.key = _library.userSettings.key;

			if (data.id) {
				endpoint = endpoint + "/" + data.id;
				delete data.id;
			}

	        return ajax.get({
	            url: _library.baseUrl + endpoint,
	            dataType: "jsonp",
	            data: data
	        });
	    },

	    /**
	     * Articles methods
	     * @type {Object}
	     */
		articles: {

			/**
			 * Find an article or articles
			 * 
			 * @param  {object} 	data     	Data to be sent to the server
			 * @return {Object}    	promise
			 */
			find: function(data) {
				var data = _library.utility.extend({}, data);
				return _library.get("articles", data);
			},

			/**
			 * Convenience method to find recent articles
			 * 
			 * @param  {integer} 	count	 	Number of articles to retrieve
			 * @return {Object}    	promise
			 */
			recent: function(count) {
				var data = { per_page: _library.utility.isNumeric(count) ? count : 5 };
				return _library.articles.find(data);
			},

			/**
			 * Find popular articles
			 * 
			 * @param  {object} 	data     	Data to be sent to the server
			 * @return {Object}    	promise
			 */
			popular: function(data) {
				var data = _library.utility.extend({}, data, { order_by: "score", score: "trending" });
				return _library.get("articles", data);
			},

			/**
			 * Find articles related to a specific article
			 * 
			 * @param  {integer} 	id        	ID of article to lookup other articles against
			 * @param  {object} 	data     	Data to be sent to the server
			 * @return {Object}    	promise
			 */
			related: function(id, data) {

				var deferred = new Deferred();

				// if the user passed additional related IDs, merge them with ours
				var ids = data && data.excluded_ids ? id + "," + data.excluded_ids : id;

				var data = _library.utility.extend({}, data, { excluded_ids: ids });

				// get the article data
				var article = _library.articles.find({id: id});
				var articlePayload;
				
				// find articles with the same tags
				var relatedByTag = article.then(function (payload) {
					articlePayload = payload;
					var tagIds = _library.utility.extractEmbeddedItemIds(articlePayload, "tags");
					var tagData = _library.utility.extend({}, data, { tags: tagIds.join(",") });
					return _library.articles.find(tagData);

				}).then(function (payload) {

					// if there are articles in the payload, return them and continue in down the pipe
					if (payload._embedded.articles) {
						return payload;

					// if there are not articles, get articles related by topic
					} else {
						var topicIds = _library.utility.extractEmbeddedItemIds(articlePayload, "topics");
						var topicData = _library.utility.extend({}, data, { topics: topicIds.join(",") });
						return _library.articles.find(topicData);
					}

				// send the payload back
				}).then(function (payload) {
					deferred.resolve(payload);
				});

				return deferred.promise;
			}
		},

		events: {

			/**
			 * Find an event or events
			 * 
			 * @param  {object} 	data     	Data to be sent to the server
			 * @return {Object}    	promise
			 */
			find: function(data) {
				var data = _library.utility.extend({}, data);
				return _library.get("events", data);
			},

			/**
			 * Convenience method to find recent articles
			 * 
			 * @param  {integer} 	count	 	Number of articles to retrieve
			 * @return {Object}    	promise
			 */
			recent: function(count) {
				var data = { per_page: _library.utility.isNumeric(count) ? count : 5 };
				return _library.events.find(data);
			}
		},

		/**
	     * Utility methods
	     * @type {Object}
	     */
		utility: {

			/**
			 * Extract the IDs of all items in a given
			 * embedded object; for example tags or topics.
			 * 
			 * @param  {object} payload Payload to extract embedded item IDs from
			 * @param  {string} object  Target object (like "tags")
			 * @return {array}        	IDs
			 */
			extractEmbeddedItemIds: function(payload, object) {
				var target = (payload && payload._embedded && payload._embedded[object]) || [];
				return target.map(function (value, i) {
					return value.id
 				});
			},
			/**
			 * Mimics jQuery.extend()
			 * @return new object
			 */
			extend: function() {
				for (var i = 1, len = arguments.length; i < len; i++) {
					for (var key in arguments[i]) {
						if (arguments[i].hasOwnProperty(key)) {
							arguments[0][key] = arguments[i][key];
						}
					}
				}
				return arguments[0];
			},
			isNumeric: function(obj) {
				return !isNaN( parseFloat(obj) ) && isFinite( obj );
			}
		}
	}

})(simplyAjax);

/*
Adapting from hubster to make it more versitle
 */

var lazy = (function ($, hubJS, moment) {

  var _env = window.location.href.split("//")[1].split(".")[0];
  var _urlPrefix = $.inArray(_env, ["local", "staging", "beta"]) > -1 ? _env + "." : "";
  var _apiBase = "http://" + _urlPrefix + "api.hub.jhu.edu/";
  var _apiVersion = 0;

  // hubJS
  hubJS.init({version: 0, key: "70a252dc26486819e5817371a48d6e3b5989cb2a"});
  hubJS.baseUrl = _apiBase;

  return {

    load: function (classes) {

      var sections;

      if (classes instanceof jQuery) {
          sections = classes;
      } else if (classes.length > 0) {
          sections = $(classes.join(","));
      } else {
          sections = $(classes);
      }
            
      sections.each(function (i, section) {

        var $loadTarget = $(section).find(".payload").first();
        var dataAttrs = $(section).data();
        var promise = lazy.createPromise(dataAttrs);

        promise.then(function (data) {

          if (data.return_count < dataAttrs.per_page && dataAttrs.backup) {

            // backup date: endpoint, params
            var backupAttrs = {
              endpoint: dataAttrs.backupEndpoint,
              params: dataAttrs.backupParams,
              per_page: dataAttrs.per_page - data.return_count,
              type: dataAttrs.backupType || dataAttrs.type
            };

            var backupData = $.extend({}, dataAttrs, backupAttrs);
            
            lazy.createPromise(backupData).then(function (backupData) {

              if (data.return_count === 0) {
                data = backupData;
              } else {
                data = lazy.mergeData(data, backupData);
              }

              lazy.render(data, dataAttrs.template, $loadTarget);
              lazy.publishEvent($loadTarget);

            });

          } else {
            lazy.render(data, dataAttrs.template, $loadTarget);
            lazy.publishEvent($loadTarget);
          }
            
        });

        // While we wait for the promises to render...
        $loadTarget.addClass("data-loading");
        $span = $("<span/>").text("Stand by, stuff is loading ...");
        $message = $("<p/>").addClass("loading-message").append($span);
        $loadTarget.append($message);

      });

    },

    /**
     * Merge the embedded data of two API calls
     * @param  {object} data       API call data
     * @param  {object} backupData API call data
     * @return {object}            Merged API call data
     */
    mergeData: function (data, backupData) {

      // find objects that need merging
      var objects = [];
      for (var key in data._embedded) {
        objects.push(key);
      }

      $.each(objects, function (i, v) {

        var fromData = data._embedded[v];
        var fromBackupData = backupData._embedded[v];

        // new data is added to the end of the array
        var newData = $.merge(fromData, fromBackupData);

        // then sorted according to its object type, which right
        // now we are assuming each object is being sorted by
        // its default sort.
        data._embedded[v] = lazy.utility.sort[v](newData);

      });

      return data;
    },

    /**
     * Create a promise object based on the data attribues
     * @param  {object} dataAttrs 
     * @return {promise}
     */
    createPromise: function(dataAttrs) {

      var apiParams = lazy.utility.createApiParams(dataAttrs);
      var loadType = dataAttrs.type;
      var objectType = lazy.utility.getObjectType(dataAttrs.endpoint);
      var promise;

      if (loadType == "related") {
        promise = hubJS[objectType].related(dataAttrs.current_id, apiParams);
      
      } else if (loadType == "recent") {
        promise = hubJS.get(dataAttrs.endpoint, { per_page: apiParams.per_page});
      
      } else if (loadType == "popular") {
        promise = hubJS[objectType].popular(apiParams);
      
      } else {

        // if additional params
        if (dataAttrs.params) {
                    
          var params = dataAttrs.params;

          if (typeof dataAttrs.params === "string") {
            params = $.deparam(dataAttrs.params);
          }
          
          apiParams = $.extend(apiParams, params);
        }

        promise = hubJS[objectType].find(apiParams);
      }

      return promise;
    },

    /**
     * Render the underscore template with the data
     * @param  {object} data     Data to populate the template with
     * @param  {string} template Represents the ID of the template
     * @return {string}          Rendered template
     */
    render: function (data, template, target) {

      if (data.return_count > 0) {
        target.addClass("loaded");
        target.removeClass("data-loading");
              
        var html = lazy.compileTemplate(data, template);
        target.append(html);

      } else {
          target.addClass("loaded");
          target.removeClass("data-loading");
          target.html("<p>Sorry, we did not find any related content.");
      }

    },

    compileTemplate: function (data, template) {

      // add some template helper functions
      data.helpers = underscoreHelpers;

      var templateHtml = $("#" + template).html() || "[Template error]";
      return _.template(templateHtml)(data);

    },

    publishEvent: function (loadTarget) {
      var loadId = loadTarget.attr("id") || loadTarget.parents(".rail").attr("id");
      $.publish("section.loaded", {id: loadId});
    },

    utility: {

      cleanObject: function (object) {
        $.each(object, function (key, value) {
          if (!value) {
            delete object[key];
          }
        });
      },

      // this needs to be reworked, possibly from the manager up
      parseEndpoint: function (endpoint) {
        var parsed = {};
        
        // get the object type (topics/31/articles == articles)
        var parts = endpoint.split("/");
        parsed.objectType = parts.pop();

        // get other parts of the endpoint (topics: 31)
        if (parts.length > 0) {
          parsed[parts.shift()] = parts.shift();
        }

        return parsed;
      },

      getObjectType: function (endpoint) {
        var parsed = lazy.utility.parseEndpoint(endpoint);
        return parsed.objectType;
      },

      getAdditionalParams: function (endpoint) {
        var parsed = lazy.utility.parseEndpoint(endpoint);
        delete parsed.objectType;
        return parsed;
      },

      createApiParams: function (data) {
        var apiParams = {
          per_page: data.per_page,
          excluded_ids: data.excluded_ids,
          ids: data.ids,
          topics: data.topics
        };

        if (data.ids) {
          apiParams.order_by = "list";
        }
        
        // get rid of empty values
        lazy.utility.cleanObject(apiParams);

        // merge additional data from endpoint into apiParams ONLY
        // if ids isn't set so just in case these ids aren't actually
        // taged with a particular topic, tag, etc...
        if (!data.ids) {
          var additionalParams = lazy.utility.getAdditionalParams(data.endpoint);
          $.extend(apiParams, additionalParams);
        }

        return apiParams;
      },

      sort: {

        events: function (events) {
          return events.sort(lazy.utility.sort.byStartDate);
        },

        articles: function (articles) {
          return articles;
        },

        byStartDate: function (a, b) {
          
          var format = "YYYY-MM-DD HH:mm";

          var aDate = a.start_date;
          var aTime = a.start_time || "00:00";
          var aDateTime = moment(aDate + " " + aTime, format).format("X");

          var bDate = b.start_date;
          var bTime = b.start_time || "00:00";
          var bDateTime = moment(bDate + " " + bTime, format).format("X");

          return ((aDate < bDate) ? -1 : ((aDate > bDate) ? 1 : 0));
        }
      }
    }

  }

})(jQuery, hubJS, moment);

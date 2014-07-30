/**
 * Hub Manager application library
 * @version 0.1.0
 */
var manager = (function ($) {
    
    var _env = window.location.href.split("//")[1].split(".")[0];
    var _urlPrefix = $.inArray(_env, ["local", "staging", "beta"]) > -1 ? _env + "." : "";
    var _hubBase = "http://" + _urlPrefix + "hub.jhu.edu/";
    var _factoryBase = _hubBase + "factory/";
    var _apiBase = "http://" + _urlPrefix + "api.hub.jhu.edu/";
    var _templates = { one: "hi" };
    var _isEventsEditForm;

    var _counter = 0; // for incrementing on form fields
    // var _cache = {
    // 	articles: {}
    // };

    // Set up underscore template render functions
    var assignTemplate = function (name, url) {
    	$.ajax({
	    	url: url,
	    	dataType: "text",
	    	success: function (data) {
	    		_templates[name] = _.template(data);
	    	}
	    });
    };

    assignTemplate("article_preview_line", "/assets/js/templates/article-preview-line.template.js");
    assignTemplate("event_preview_line", "/assets/js/templates/event-preview-line.template.js");
    assignTemplate("taxonomy_preview_line", "/assets/js/templates/taxonomy-preview-line.template.js");
    

    return {
    	pages: {
    		global: {
    			init: function () {

    			}
    		},
    		edit: {
    			init: function () {

    				var editForm = $("#editForm");

    				$(".ui-sortable").sortable({
				        placeholder: "ui-dropzone"
				    });

				    manager.loadPreviewLoop();
				    
				    $(".features").on("change", "input[name='feature_queue[]']", manager.loadPreviewOnChange);
				    $(".tags").on("change", "input[name='tags[]']", manager.loadPreviewOnChange);
				    $(".locations").on("change", "input[name='locations[]']", manager.loadPreviewOnChange);

				    $(".input-group").on("click", ".action-delete", manager.removeInput);
				    $(".input-group").on("click", ".action-expand", manager.toggleExpandedDetails);
				    $(".action-add-new").on("click", manager.addNewInput);
				    $(".action-preview").on("click", manager.preview);

				    // Activate tabs on pages/edit template
				    $(".nav-tabs a").each(manager.activateTabs);

				    // Datepicker on pages/edit template
				    $("#published").datetimepicker({
				        ampm: true,
				        minDate: 0,
				        defaultDate: "Now",
				        showOn: "button",
				        buttonImage: "/assets/img/clock.png",
				        buttonImageOnly: true
				    }).next().insertBefore('#published');

				    $(".datetimepicker").datetimepicker({
				    	ampm: true,
				    	defaultDate: "Now"
				    });

				    // Datepicker with standard calendar icon set to the right of the input
				    $(".datepicker").datepicker();

				    editForm.on("focusin", ".datepicker", function() {
				    	$(this).datepicker({
				    		minDate: 0
				    	});
				    });


				    // Event edit form
				    	
				 	if (_isEventsEditForm) {

				 		editForm.find("input, textarea").on("change", function(e) {
					    	$(this).closest("li").addClass("changed");
					    });

					    editForm.on("submit", function(e) {
					    	editForm.find("li:not(.changed)").remove();
					    });
				 	}
    			}
    		}
    	},
    	tinymce: {
    		init: function (textarea) {
    			
    			var editorName;
    			var editors;
    			textarea = $(textarea);

    			var editors = {
    				defaults: {
    					script_url : "/assets/js/vendor/tiny_mce_jquery/tiny_mce.js",

                        // General options
                        theme : "advanced",
                        plugins : "autolink,lists,style,table,preview,searchreplace,paste,visualchars,xhtmlxtras,template,wordcount,advlist",
    				},
					full: {
                        theme_advanced_buttons1 : "bold,italic,underline,|,cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,formatselect,removeformat,|,undo,redo,|,link,unlink,|,code,preview",
                        theme_advanced_buttons2 : "",
                        theme_advanced_buttons3: ""
					},
					mini: {
						// theme: "simple"
                        theme_advanced_buttons1 : "bold,italic,underline,|,cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,code,removeformat",
                        theme_advanced_buttons2 : "",
                        theme_advanced_buttons3: ""
					}
				};

    			editorName = $(textarea).attr("data-editor-name") || "full";
    			textarea.tinymce($.extend(editors.defaults, editors[editorName]));
    		}
    	},
    	api: {
            get: function (endpoint, requestData) {
                return $.ajax({
                    url: _apiBase + endpoint,
                    dataType: "jsonP",
                    data: requestData,
                    suppress_response_codes: true
                });
            }
        },
        loadPreviewLoop: function () {

        	var feature_queue = $(".features input[name='feature_queue[]']");
        	if (feature_queue.length > 0) {

	        	var endpoint = $("fieldset").attr("data-endpoint");
	        	        	
	        	feature_queue.each(function (i, el) {
	        		var id = el.value;
	        		var previewSpan = $(this).parents("li").find(".preview");

	    			manager.loadPreview(endpoint, id, previewSpan, this);
	        	});
	        }

        	var tags = $(".tags input[name='tags[]']");
        	if (tags.length > 0 ) {
        		tags.each(function (i, el) {
	        		var id = el.value;
	        		var previewSpan = $(this).parents("li").find(".preview");

	    			manager.loadPreview("tags", id, previewSpan, this);
	        	});
        	}

        	var locations = $(".locations input[name='locations[]']");
        	if (locations.length > 0 ) {
        		locations.each(function (i, el) {
	        		var id = el.value;
	        		var previewSpan = $(this).parents("li").find(".preview");

	    			manager.loadPreview("locations", id, previewSpan, this);
	        	});
        	}
        },
        loadPreview: function (endpoint, id, target, input) {

        	var id = id;
        	var promise = manager.api.get(endpoint + "/" + id, "v=0&key=70a252dc26486819e5817371a48d6e3b5989cb2a");
        	var shouldTimeout = true;
        	var timeoutIn = 3000;

			promise.done(function (payload) {
				shouldTimeout = false;
				
				if (payload && id) {

					if (endpoint == "articles") {
						payload.edit_url = _factoryBase + "node/" + payload.id + "/edit";
						target.hide().html(_templates.article_preview_line(payload)).fadeIn(500);

					} else if (endpoint == "events") {
						payload.edit_url = _factoryBase + "node/" + payload.id + "/edit";
						target.hide().html(_templates.event_preview_line(payload)).fadeIn(500);

					} else {
						target.hide().html(_templates.taxonomy_preview_line(payload)).fadeIn(500);
					}

				} else {
					// just in case the API payload does not come back, still populate the
					// input with the ID that was previously entered. This prevents issues
					// outlined in Github issue #229:
					// https://github.com/johnshopkins/hub/issues/229
					input.value = id | "";
				}
			});

			setTimeout(function () {
				if (shouldTimeout) {
					if (endpoint == "articles" || endpoint == "events") {
						target.html("<table class='timeout-error'><tr><td><strong>Error loading <a target='_newtab' href='" + _factoryBase + "node/" + id + "/edit'>ID " + id + "</a></strong></td></tr></table>");
					} else if (endpoint == "tags") {
						target.html("<table class='timeout-error'><tr><td><strong>Error loading tag with ID of " + id);
					}
					// just in case the API payload does not come back, still populate the
					// input with the ID that was previously entered. This prevents issues
					// outlined in Github issue #229:
					// https://github.com/johnshopkins/hub/issues/229
					input.value = id || "";
				}
			}, timeoutIn);

        },
        loadPreviewOnChange: function (e) {
        	var id = this.value;
        	var target = $(this).parents("li").find(".preview");
        	var endpoint = $(this).parents("fieldset").data("endpoint");
        	manager.loadPreview(endpoint, id, target, this);
        },
	    removeInput: function (click) {
	        var item = $(this).closest("li");
	        item.fadeOut(300, function () { item.remove(); });
	    },
	    toggleExpandedDetails: function (click) {
	    	var clicked = $(this);
	    	var li = clicked.closest("li");
	    	if (!li.hasClass("initialized")) {
	    		manager.tinymce.init(li.find("textarea.wysiwyg"));
	    		li.addClass("initialized");

	    		if (_isEventsEditForm) {
	    			li.addClass("changed");
	    		}

	    	}
	    	li.find(".control-group").first().toggleClass("active");
	        li.toggleClass("expanded").find(".expanded-details").toggle(500);
	        if (clicked.hasClass("icon")) {
	        	clicked.toggleClass("icon-chevron-down icon-chevron-up");
	        }
	        
	    },
	    addNewInput: function (click) {
	        click.preventDefault();
	        var button = $(this);
	        var fieldset = button.parents("fieldset").first();
	        var group = fieldset.find(".input-group").first();
	        var template = $(fieldset.find(".input-template").first().html());

	        if (button.hasClass("add-group")) {
	            manager.addNewInputGroup(template);
	        }

	        if (button.hasClass("prepend")) {
	        	template.hide().prependTo(group).fadeIn(500);
	        } else {
	        	template.hide().appendTo(group).fadeIn(500);
	        }
	    },
	    addNewInputGroup: function(template) {

	        _counter++;
	        var newNumber = "new-" + _counter;

	        template.find("input, select, textarea").each(function() {

	            var nameValue = $(this).attr("name");

	            nameValue =  nameValue.replace("new", newNumber);
	            $(this).attr("name", nameValue);

	        });
	    },
	    activateTabs: function () {
	        $(this).click(function(e) {
	            e.preventDefault();
	            $(this).tab('show');
	            if ($(this).attr("href") == "#new") {
	                $(".page-header").after('<div id="newVersionAlert" class="alert alert-alert fade in">You are about to create a <em>new</em> version of this page.</div>');
	            } else {
	                $("#newVersionAlert").remove();
	            }
	        });
	    }
	};

})(jQuery);
var tinymcelib = (function ($) {

	return {
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
	}

})(jQuery);
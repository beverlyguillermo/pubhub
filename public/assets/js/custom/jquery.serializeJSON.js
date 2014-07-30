/**
 * serializeJSON jQuery helper
 * 
 * Usage: $("form").serializeJSON();
 *
 * Takes form data from an HTML form and returns a JSON
 * object, parsing inputs with [] into arrays.
 */
 
(function ($) {
    $.fn.serializeJSON = function () {
        var json = {};
        $.map($(this).serializeArray(), function (n, i) {
            var key = n.name;
            var value = n.value;
            var isArray = key.substr(key.length - 2) === "[]";

            key = isArray ? key.substr(0, key.length - 2) : key;

            if (isArray) {
                json[key] = json[key] || [];
                json[key].push(value);
            }
            else {
                json[key] = value;
            }
        });
        return json;
    };
}(jQuery));
function Popup(options) {

	var defaults = {
		onClass: "popup-on"
	};

	this.html = $('<div class="popup"><div class="content center"><img class="subscribe-email-macbook-small" src="/assets/img/subscribe/hub-subscribe-macbook-small.png"><h2 class="message"><span>Johns Hopkins news,</span> <span>delivered to your inbox</span> <i class="icon-chevron-right"></i></h2><a class="link" href="/subscribe"></a><a class="close" href="#"><i class="icon-remove"></i></a></div></a>');

	this.options = $.extend({}, defaults, options);
}

Popup.prototype.initialize = function () {

	var self = this;

	if (window.location.href.split("/").pop() === "subscribe") {
		self.optout();
		return;
	}

	this.html.find(".content").append(this.options.content);
	$("body").append(this.html);

	var closeButton = this.html.find(".close");

	if (Cookies.get(this.options.cookie.name) !== 'opt-out') {
        
        this.activate();

        $(window).on("scroll", function () {
	    	if ( ($(window).scrollTop() <= 100) && (Cookies.get(self.options.cookie.name) !== 'opt-out') ) {
	    		self.activate();
	    	} else if ( $(window).scrollTop() > 100 ) {
	    		self.deactivate();
	    	}
	    });
    }

    closeButton.on("click", function () {

        self.deactivate();
        self.optout();

    });
};


/**
 * Set up DOM events to turn on promo popup
 * @return {[type]}
 */
Popup.prototype.activate = function () {

	this.html.addClass(this.options.onClass);

};

/**
 * Close the popup
 * @return {[type]}
 */
Popup.prototype.deactivate = function () {

	this.html.removeClass(this.options.onClass);

};


/**
 * Deactivate popup permanently
 * @return {[type]}
 */
Popup.prototype.optout = function () {

	Cookies.set(this.options.cookie.name, this.options.cookie.value, this.options.cookie);

};





var popup = new Popup({
    // content: $("<p>").text("This is my popup!"),
    cookie: {
        name: "hub-subscription-popup",
        value: "opt-out",
        path: "/",
        expires: 60 * 60 * 24 * 7 * 26 // 26 weeks
    }
});


$(document).ready( function () {

    popup.initialize();

});
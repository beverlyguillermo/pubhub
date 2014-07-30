$(document).ready(function ($) {
	window.console.log("blahblahblah");
	$(".hubmark").addClass("loading");

	window.setTimeout(function () {
		$(".hubmark").removeClass("loading");
	}, 4000);
});
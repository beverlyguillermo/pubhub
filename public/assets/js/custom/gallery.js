jQuery( document ).ready( function ($) {

    /**
     * Google analytics
     * @type {Object}
     */
    var _gaq = window._gaq || [];

    /**
     * Overall container for the whole gallery
     * and puts a dark background over the page.
     * @type {Object}
     */
    var overlay = $("<div class='gallery-overlay'></div>").hide().appendTo("body");

    // Add the close button to the overlay
    $("<div class='close-gallery'><a id='close-gallery' href='#'><i class='icon-remove'></i></a></div>").appendTo(overlay);
    
    /**
     * The in-article gallery container
     * @type {Object}
     */
    var gallery = $(".js-gallery");
    gallery.addClass("js-on");

    /**
     * Holds information about the gallery
     * @type {Object}
     */
    var state = { gallery: gallery };

    /**
     * Keeps track of whether the gallery is open or not. Aids the script
     * to know if it should fade in the overlay or not.
     * @type {Boolean}
     */
    var galleryOpen = false;

    
    /**
     * URL of only the article - no appended hash or query string. Used
     * to generate the Facebook share URL and the previous/next buttons.
     * @type {string}
     */
    var articleUrl = window.location.protocol + "//" + window.location.hostname + window.location.pathname;

    /**
     * Full URL in the current state. Includes hash. Used when sharing
     * the photo through Twitter.
     * @type {string}
     */
    var fullUrl;


    /**
     * Full URL of the image. Used to set the URL of the image to display
     * and when sharing through Pinterest.
     */
    var imageUrl;


    /**
     * Title of gallery
     * @type {string}
     */
    var galleryTitle = gallery.attr("data-gallery-title");

    /**
     * ID of gallery
     * @type {string}
     */
    var galleryId = gallery.attr("data-gallery-id");

    
    

    /**
     * Runs a callback when the hash changes.
     */
    $(window).hashchange( function() {

        // Update the fullUrl to the new state
        fullUrl = window.location.href;

        // Get the hash object
        var hashOptions = $.deparam.fragment();

        // If there is a gallery key in the hash object
        if (hashOptions.image) {

            // Update the imageUrl based on the hash
            imageUrl = window.location.protocol + "//" + window.location.hostname + "/factory/sites/default/files/styles/hub_large/public/" + hashOptions.image;


            if (galleryOpen) {

                // Gallery is already open, just build the gallery in the overlay
                buildGalleryOverlay( hashOptions.image );

            } else {

                // Gallery is not open, fade the overlay in and build the gallery
                overlay.fadeIn( 200, function () {
                    _gaq.push(["_trackEvent", "Image Gallery", "Opened", ""]);
                    buildGalleryOverlay( hashOptions.image );
                });
            }
            
        }

    });

    /**
     * Trigger the hashchange() event on pageload.
     */
    $(window).hashchange();


    /**
     * Closes the gallery if a user chicks on the
     * gallery overlay or the close button
     */
    $("body").on("click", ".gallery-overlay, #close-gallery", function (e) {
        galleryOpen = false;
        $.bbq.pushState([], 2);
        overlay.fadeOut();
        overlay.find(".gallery-container").remove();
    });

    /**
     * Removes the gallery container to prepare for a
     * new gallery build when someone clicks the navigation.
     */
    $("body").on("click", ".gallery-container", function (e) {
        if ($(e.target).hasClass("gallery-nav")) {
            $(".gallery-container").remove();
        }
        e.stopPropagation();
    })


    /**
     * Creates the HTML for the share buttons
     * @return {string} The HTML
     */
    function getShareHtml() {

        var html = "";

        var hashOptions = $.deparam.fragment();
        var shareUrl = articleUrl + "/gallery/" + galleryId + "/images/" + hashOptions.id + "/" + hashOptions.image;

        // twitter
        var tweetText = "Photo: " + galleryTitle;
        html += '<div class="item twitter"><a href="https://twitter.com/share" class="twitter-share-button" data-url="' + shareUrl + '" data-counturl="' + shareUrl + '" data-text="' + tweetText + '" data-via="HubJHU">Tweet</a></div>';

        // facebook
        html += '<div class="item facebook"><div class="fb-like" data-href="' + shareUrl + '" data-send="false" data-layout="button_count" data-width="80" data-show-faces="false"></div></div>';
        
        // pintrest
        var pinterestDesc = "From " + galleryTitle + " on the Hub";
        html += '<script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>';
        html += '<div class="item pinterest"><a data-pin-config="beside" href="//pinterest.com/pin/create/button/?url=' + encodeURIComponent(articleUrl) + '&media=' + imageUrl + '&description=' + pinterestDesc + '" data-pin-do="buttonPin" ><img src="//assets.pinterest.com/images/pidgets/pin_it_button.png" /></a></div>';
        
        return html;
    }


    /**
     * Builds the gallery on each photo change
     * @param  {string} clickedHref Name of file to load
     * @return null
     */
    function buildGalleryOverlay( clickedHref ) {

        var galleryContainer = $("<div class='gallery-container'></div>").appendTo(overlay);

        var imageContainer = $("<div class='image-container'></div>").appendTo(galleryContainer);
        var imageBox = $("<div class='image-box' id='image-box'></div>").appendTo(imageContainer);

        var image = $("<img class='galleryImage' />");

        var allImages;
        var html;

        if ( !state.total ) {
            state.total = 0;
            state.images = [];
            state.imageNames = [];
            allImages = $(state.gallery).find("a");

            $.each( allImages, function (index, value) {

                var images = $(this).find("img");

                if (images.length > 0) {
                    state.total += 1;
                    i = {};
                    i.image = this;

                    var hash = $.deparam.fragment($(this).parent("a").context.hash)

                    i.href = imageUrl;
                    i.alt = $(this).children().attr("alt");
                    i.title = $(this).children().attr("title");
                    i.credit = $(this).children().attr("data-credit");
                    i.id = hash.id;
                    i.filename = hash.image;

                    state.images.push(i);

                    state.imageNames.push(hash.image);


                } else {
                    return false;
                }
            });
        }

        state.active = $.inArray(clickedHref, state.imageNames);

        state.prev = state.active > 0 ? state.active - 1 : state.images.length - 1;
        state.next = (state.active + 1 < state.imageNames.length) ? state.active + 1 : 0;
    
        $(".image-container").css({ left: "-1000px"});

        galleryNext = $("<a href='##' class='gallery-next gallery-nav'><i class='icon-chevron-right gallery-nav'></i></a>");
        galleryPrev = $ ("<a href='##' class='gallery-prev gallery-nav'><i class='icon-chevron-left gallery-nav'></i></a>");


        var filename = state.images[state.prev].filename;
        var id = state.images[state.prev].id;

        galleryPrev.attr("href", articleUrl + "#image=" + filename + "&id=" + id + "&gid=" + galleryId).prependTo( $(".gallery-container, .image-container") );
        

        filename = state.images[state.next].filename;
        id = state.images[state.next].id;

        galleryNext.attr("href", articleUrl + "#image=" + filename + "&id=" + id + "&gid=" + galleryId).prependTo( $(".gallery-container, .image-container") );

        image.prependTo(imageBox);
        image.load(function() {
            loadImage(imageContainer);
        }).attr("src", imageUrl);
  
    }


    function loadImage(imageContainer) {

        var captionContainer = $("<div class='caption-container force' id='caption-container'></div>").appendTo(imageContainer);
        var captionBox = $("<div class='caption-box' id='caption-box'></div>").appendTo(captionContainer);
        var caption = $("<div class='caption'></div>").appendTo(captionContainer);
        var social = $("<div class='social' id='gallery-social'></div>").appendTo(captionContainer);

        var alt = state.images[state.active].alt;
        var credit = state.images[state.active].credit;

        html = "<span class='caption'>";

        if (alt) {
            html += alt;
        }

        if (credit) {
            html += " <b class='credit'><span class='prefix'>Image: </span>" + credit + "</b>";
        }

        html += "</span>";

        $(caption).html(html);

        $(social).html(getShareHtml());

        // reload FB like button
        FB.XFBML.parse(document.getElementById('gallery-social'));
        
        // reload twitter tweet button
        twttr.widgets.load()
        
        
        calculateSize(imageContainer);
        $(".image-container").css({ left: ""});

    }

    function calculateSize( container ) {

        // Max dimensions the container should be
        var maxWidth = $(window).width() - 120; // leave room for the nav arrows
        var maxHeight = $(window).height() - 250; // allow for the facebook popup and 80 pixels at the top of the screent


        // Container dimensions (how big it wants to render (no image resizing))
        var $container = $(container);

        // Save the elements within the container for later use
        var $image = $container.find("img.galleryImage");
        var $caption = $container.find(".caption-container");

        // Alter the container width to be the width of the image. This will allow
        // us to get an accurate reading on the caption height
        $container.css({
            width: $image.outerWidth(true)
        });


        var origContainerHeight = $container.outerHeight(true);
        var origContainerWidth = $container.outerWidth(true);
        var containerHeight = origContainerHeight;
        var containerWidth = origContainerWidth;



        // Calculate if the image container is over height or width

        var overWidth = containerWidth - maxWidth;
        var overHeight = containerHeight - maxHeight;

        if ( overWidth > 0 || overHeight > 0 ) {

            // Create a container size that will fit within the max height/width

            if ( overWidth > overHeight ) {
                // Landscape
                containerWidth = maxWidth;
                containerHeight = Math.round((containerWidth * containerHeight) / origContainerWidth);
            }
            else {
                // Portrait
                containerHeight = maxHeight;
                containerWidth = Math.round((containerWidth * containerHeight) / origContainerHeight);
            }
        }


        // Use jQuery to change the height and width of the container
        // 
        // Changing the dimensions of the container will alter the image size properly, but it usually
        // renders the caption's box too small. Now because CSS is pretty relazed, it will just render
        // the rest of the caption container that spills outside of the overall container, but this
        // will cause the image and caption to not be centered in the window.
        $container.css({
            width: containerWidth + "px",
            height: containerHeight + "px"
        });


        // Find height of caption and image. The total of which will be the ACTUAL height
        // of the container.
        var captionHeight = $caption.outerHeight();
        var imageHeight = $image.outerHeight();
        var totalHeight = captionHeight + imageHeight;


        // what if the new container height is larger than the max height?!? We'll
        // need to eventually recursivly resize things.

        $container.css({
            width: containerWidth + "px",
            height: totalHeight + "px",
            // marginTop: "-" + (totalHeight / 2) + "px",
            marginLeft: "-" + (containerWidth / 2) + "px"
        });

    }
});
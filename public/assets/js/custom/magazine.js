jQuery( document ).ready( function ($) {
      
  // Variable declarations
  var $tocButton = $("#toc-button"),
    $issueMeta = $(".issue-meta"),
    $mainNav = $("ul#main-navigation"),
    $mainNavItems = $mainNav.children("li"),
    selectBox, cachedSelectBox = false,
    navigationList = false,
    windowX = $(window).width(),
    resizeTimer = new Date(),
    _gaq = window._gaq || [];

/* ================================
 * Open and Close Table of Contents
 * ================================
 */

  var openToc = $(".open-toc"),
    tocHref = openToc.attr("href"),
    tocOverlay = $("#toc-overlay");

  // append "/preview," otherwise the page doesn't exist
  if (preview.isPreview()) {
    tocHref += "/preview";
  }

  if (openToc.length > 0) {
    // Preload the table of contents HTML
    tocOverlay.load( tocHref + ' #toc-load', function ( response, status, xhr ) {

      var loader = tocOverlay.find("#toc-load");

      // the links do not come in with "/preview" appended, so we need
      // to do that now. See: http://api.jquery.com/load/#script-execution
      preview.previewifyLinks(loader);

      loader.prepend("<a id='close-toc' class='close-toc' href='#'>Close</a>")
        .prepend("<div class='hopkins-stripe'></div>")
        .find("h1 span").first().css({"display" : "block"});

      // Replace <h1> with <div> to prevent conflicts on the <h1>
      loader.find("h1").replaceWith("<div class='contents-title'>" + loader.find("h1").html() + "</div>");

    });

    openToc.click( function (e) {
      e.preventDefault();
      var docHeight = $(document).height();
      tocOverlay.css({height: docHeight + "px"}).fadeIn( 200 );
      
      // Track Google Analytics Event
      _gaq.push(['_trackEvent', 'TOC', 'Opened', '']);
      
      return false;
    });
    
    $("#close-toc").live( "click", function (e) {
      tocOverlay.fadeOut(200);
      return false;
    });
    
    tocOverlay.live( "click", function (e) {
      tocOverlay.fadeOut(200);
    });
    
    tocOverlay.find(".toc").live( "click", function (e) {
      e.stopPropagation();
    });
    
    $("body").keydown( function (e) {
      // Close with esc
      if ( e.which === 27 ) {
        tocOverlay.fadeOut(200);
      }
    });
    
    // Apply hover class to make contents button and
    // issue name appear to be one giant link
    $tocButton.hover(
      function () {
        $issueMeta.addClass("hover");
      },
      function () {
        $issueMeta.removeClass("hover");
      }
    );
    
    $issueMeta.hover(
      function () {
        $tocButton.addClass("hover");
      },
      function () {
        $tocButton.removeClass("hover");
      }
    );
  }

  
  
/* Navigation Select Box functionality */
  
  $("select#main-navigation").live( "change", function () {
    window.location.href = $(this).val();
  });
  

  
/* ================================
 * Carousel
 * ================================
 */
  var carousel = $("#magazine-carousel"),
    container = carousel.find(".container"),
    activeSet = carousel.find(".active"),
    nextSet = activeSet.next(),
    prevSet = activeSet.prev(),
    nav = $(".carousel-nav"),
    next = $(".carousel-nav.next"),
    prev = $(".carousel-nav.previous"),
    containerHeight,
    setHeight;
    
    container.find("a").click( function (e) {
      // Track GA Event
      _gaq.push(['_trackEvent', 'Carousel', 'Click', 'User clicked on a story in the carousel']);
    });
    
    container.load( function () {
      var containerHeight = this.height(),
      setHeight = containerHeight > 500 ? containerHeight : 500;
      
      container.css({height: setHeight + "px"});
    });
    
    
    function styleNavLinks() {
      activeSet = carousel.find(".active"),
      nextSet = activeSet.next(),
      prevSet = activeSet.prev();
      
      if ( nextSet.length > 0 ) {
        next.addClass("activate");
      }
      else {
        next.removeClass("activate");
      }
      
      if ( prevSet.length > 0 ) {
        prev.addClass("activate");
      }
      else {
        prev.removeClass("activate");
      }
      
    }
    
    styleNavLinks();
    
    function getContainerWidth() {
      return container.width();
    }
        
    next.click( function (e) {
      activeSet = carousel.find(".active"),
      nextSet = activeSet.next();
      
      if ( nextSet.length === 0 ) {
        nextSet = activeSet.parent(".set").next().children().first();
      }
      
      if ( nextSet.length > 0 ) {
        nextSet.css({position:"absolute"}).fadeIn(500, function () { 
          $(this).addClass("active"); 
        });
        activeSet.fadeOut(500, function () { 
          $(this).removeClass("active").css({position:"static"});
          styleNavLinks();
        });
      }
      
      // Track GA Event
      _gaq.push(['_trackEvent', 'Carousel', 'Next', 'User clicked the next button to load more stories']);
          
      return false;
      
    });
    
    prev.click( function (e) {
      activeSet = carousel.find(".active"),
      prevSet = activeSet.prev();
      
      if ( prevSet.length === 0 ) {
        prevSet = activeSet.parent(".set").prev().children().last();
      }
      
      if ( prevSet.length > 0 ) {
        prevSet.css({position:"absolute"}).fadeIn(500, function () { 
          $(this).addClass("active");
        });
        activeSet.fadeOut(500, function () { 
          $(this).removeClass("active").css({position:"static"}); 
          styleNavLinks();
        }); 
      }
      
      // Track GA Event
      _gaq.push(['_trackEvent', 'Carousel', 'Previous', 'User clicked the previous button to go back to other stories']);
      
      return false;
      
    });





// Golomb's Gambits uses blockquotes for solutions
var gg = $(".department-golombs-gambits"),
  solutions = gg.find("em");

$.each( solutions, function ( index, value ) {
  solution = $(this);
  var solutionText = solution.html();
  solution.css({backgroundColor: "transparent", cursor: "pointer"}).html("Show Solution &raquo;");
  
  solution.click( function (e) {
    $(this).fadeOut(200, function () { 
      $(this).html( solutionText ).fadeIn(200); 
    });
    
    // Track GA Event
    _gaq.push(['_trackEvent', 'Golombs Gambits', 'Solution clicked', 'User viewed this solution: ' + solutionText]);
    
  });
  
});
  
});
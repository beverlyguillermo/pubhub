jQuery(document).ready( function ($) {
	
	$(".videopow").on("click", function () {
			
		var qstring = new Array(), 
			qpairs = new Array(), 
			i, 
			youtubeID = 0,
			playlistId = 0,
			iframe,
			videopowHTML,
			winHeight, winWidth,
			docHeight,
			vidTargetWidth, vidTargetHeight,
			vidWidth, vidHeight,
			boxWidth, boxHeight,
			wfit, hfit, wtoobig, htoobig,
			widthOffset=100, heightOffset=180,
			styleOverlay, styleFrame, styleIFrame, styleClose,
			youTubePlayerOptions, youTubePlayerParams,
			playlist = $(this).hasClass("videopow-playlist")
			;

		vidTargetWidth = this.getAttribute('data-maxwidth') || 1280;
		vidTargetHeight = this.getAttribute('data-maxheight') || 720;
		
		docHeight = $(document).height();
		winHeight = $(window).height();
		winWidth = $(window).width();
		
		//wfit = (winWidth - widthOffset) > vidTargetWidth;
		//hfit = (winHeight - heightOffset) > vidTargetHeight;
		wtoobig = vidTargetWidth - winWidth;
		htoobig = vidTargetHeight - winHeight;
		
		function calculateDims(w,h) {
			if ( w === null ) {
				return (h * vidTargetWidth) / vidTargetHeight;
			}
			else {
				return (w * vidTargetHeight) / vidTargetWidth;
			}
		}
		
		if ( wtoobig > htoobig ) {
			vidWidth = winWidth - widthOffset;
			vidHeight = calculateDims( vidWidth ); // (vidWidth * vidTargetHeight) / vidTargetWidth;
		}
		else {
			vidHeight = winHeight - heightOffset;
			vidWidth = calculateDims( null, vidHeight ); // (vidHeight * vidTargetWidth) / vidTargetHeight;
		}
		
		if ( ( vidHeight > winHeight ) || ( vidWidth > winWidth ) ) {
			vidHeight = vidHeight * 0.8;
			vidWidth = vidWidth * 0.8;
		}
		
		// Setting a hard max-width on the video
		if ( vidWidth > 1050 ) {
			vidWidth = 1050;
			vidHeight = calculateDims( vidWidth );
		}
	
		boxWidth = vidWidth + 0;
		boxHeight = vidHeight + 0;
				
		qstring = this.href.split("?")[1];
		qpairs = qstring.split("&");
		
		for ( i=0; i<qpairs.length; i+=1 ) {
			
			var pair = new Array();
			pair = qpairs[i].split("=");
			
			if ( pair[0] === "list" ) {
				playlistId = pair[1];
				continue;
			}

			if ( pair[0] === "v" ) {
				youtubeID = pair[1];
				continue;
			}
			
		}
		
		if ( youtubeID === 0 ) {
			return false;
		}
		
		styleOverlay = 'style="display: none; text-align: center; width: 100%; height: ' + docHeight + 'px; position: fixed; z-index: 5000; top: 0; left: 0; background: url(http://jhu.edu/~homepage/_assets/images/bg-fff-a90.png) left top repeat !important;" ';
		styleIFrame = 'width="' + vidWidth + '" height="0" style="margin: auto; position: relative; z-index: 5020; top: '+ (vidHeight/2) +'px;" ';
		styleFrame = 'style="background: url(http://jhu.edu/~homepage/_assets/images/bg-000-a10.png) left top repeat; margin: 80px auto; height: '+boxHeight+'px; width: '+boxWidth+'px;" ';
		styleClose = 'style="position: absolute; display: block; width: 28px; height: 28px; top: 10px; right: 10px; z-index: 5555; background: url(http://www.jhu.edu/~homepage/_assets/images/videopow-close.png?v=2) center center no-repeat; text-indent: -9999px;"';
		
		youTubePlayerParams = [
			"autoplay=1",
			"rel=0",
			"wmode=opaque",
			//"controls=0",
			"autohide=1",
			"theme=light",
			"enablejsapi=1",
			"modestbranding=1",
			"playerapiid=ytplayer",
			"showinfo=1"
		];

		if (playlist) {
			youTubePlayerParams.push("listType=playlist");
			youTubePlayerParams.push("list=" + playlistId);
		}

		iframe = '<iframe id="videopow-iframe" ' + styleIFrame + 'src="http://www.youtube.com/embed/' + youtubeID + '?' + youTubePlayerParams.join("&") + '" frameborder="0" allowfullscreen allowTransparency="true"></iframe>';
				
		videopowHTML = '<div id="videopow-overlay" '+styleOverlay+'><div class="videopow-frame" '+styleFrame+'>' + iframe + '<a id="videopow-close" href="#" '+styleClose+'>Close</a></div></div>';
		
		$("body").prepend(videopowHTML);
		$("#videopow-overlay").fadeIn().find("#videopow-iframe").animate({ height: vidHeight, top: '0'}, 800);
		
		return false; 
	});
	
	$("#videopow-overlay").live( 'click', function () {
		var videopow = $(this);
		videopow.fadeOut()
		setTimeout( function () {
			videopow.remove();
		}, 1000);
		
	});
	
	$("#videopow-close").live( 'click', function () {
		var videopow = $('#videopow-overlay');
		videopow.fadeOut()
		setTimeout( function () {
			videopow.remove();
		}, 1000);
		return false;
		
	});
	
	$("#videopow-control-pause").live( 'click', function (e) {
		e.preventDefault();
		pause();
		return false;
	});
	
});

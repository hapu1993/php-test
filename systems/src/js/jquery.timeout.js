var rTimer;
var count = 60;
var ctitle = document.title;
initial_timeout();

// initial timeout is triggered every X minutes ('set' variable)
function initial_timeout(){
	count = 60;
	clearInterval(rTimer);
	
	if (typeof set !== 'undefined') {
		rTimer = setInterval(function(){
			
			// if the click is registered anywhere during the warning message, dismiss the message and extend the server session
			$("body").on("mousemove", function() {
				$.get(SYSTEM_ROOT+"ajax/ajax_extend_session.php");
				$("body").off("mousemove");
				$("div.session_warning").slideUp(300, function(){ $(this).remove(); });
				$("div.sw_overlay").remove();
				
				// restart the timeout
				initial_timeout();
			});
			
			// if we got to 10 seconds, and still no movement, start a real 10 seconds timer
			real_timeout();

		}, set);

	}
}

// if a real timeout was triggered, user needs to click on the screen to continue working
function real_timeout() {
	clearInterval(rTimer);
	rTimer = setInterval(function(){
		
		// if we get to 20 seconds, show the warning message
		$('html, body').animate({ scrollTop: 0 }, 300);
		$("body").off("mousemove");
		if ( !$("div.sw_overlay").length ) $('body').append('<div class="sw_overlay" style="width:'+($(document).width())+'px; height:'+($(document).height())+'px;">&nbsp;</div>');
		$('body').append('<div class="session_warning">Your session is about to expire in <b>'+count+'</b> seconds. Click anywhere to continue working.</div>');
		$('div.session_warning').slideDown(300);
		countdown();
		
		// and bind click
		$("body").on("click", function() {
			$.get(SYSTEM_ROOT+"ajax/ajax_extend_session.php");
			$("body").off("click");
			$("div.session_warning").slideUp(300, function(){ $(this).remove(); });
			$("div.sw_overlay").remove();
			initial_timeout();
			document.title = ctitle;
		});
		
	}, 60000);

}

// if countdown was reached, swap title every second
// if 'count' gets to 0, log out the user
function countdown() {
	clearInterval(rTimer);
	rTimer = setInterval(function(){
		count = count - 1;
		if (count % 2 == 0) document.title = "Session Expiry Warning";
		else document.title = ctitle;
		$("div.session_warning b").html(count);
		if (count == 0) window.location.href = SYSTEM_ROOT+"logout.php?expired_session";
	}, 1000);
}
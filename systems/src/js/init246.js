// initialize various global scope variables
// self_submit is a global function declaration
// gTimer is a global interval reference used to hide gFeedback popups
// tip is a global tooltip variable
// tind, ftbind, posleft, orgfapos are global floating table header references
var self_submit, gTimer, tip, html, tind, ftbind, posleft, orgfapos;

// 'submit button disabled' - set to true during the file upload process
// if false, prevents a form to be submitted
var sbtndis = false;

// set to true when appending more items to the table using the infinity scroll
// if false, prevents the appending which can be continuously triggered on page scroll
var block_load = false;

// the following block is executed only once per page load
$(function(){

	// once the entire javascript is loaded, 'jon' class is added to the body
	// without the .jon class, popup (jbox) links on the page do not work
	// this was done so a user cannot trigger a popup before the javascript is fully loaded
	$("body").addClass("jon");

	// defined in jquery.functions.js
	// builds an apps dropdown menu
	makemyapps();

	// if the page was loaded after a self submit, scroll the window to the previous position
	// the previous position is saved in the input element with form_scroll name parameter
	if ( $("input[name=form_scroll]").val() != undefined ) {
		$("html, body").scrollTop($("input[name=form_scroll]").val());
	}

	/* 	the following block contains all events bindings to the handlers
		handlers do not need to be present on the page by the time this page loads -
		events will be attached as soon as the handlers appears on the page (ajax) */

	// all form submit, both on the page, and in the popup
	$(document).on("click", "*[type='submit']", function(e){
    	e.stopPropagation();
    	e.preventDefault();

		// if there is a file submit in progress, prevent form submit and show a warning message
		// sbtndis is set to true during the ajax file upload process
		// uses: jquery.functions.js
		if (sbtndis) {
			show_hint($(this), "There is a <b>file upload in progress</b>. Please wait until it finishes in order to save the form.");
			return false;
		}

		// if the parent form validates
    	if ( $(this).closest("form").validate().form() == true ){

			// multiple submit buttons, choose which action and posted_actions hidden input should be kept
			// any hidden posted_actions anywhere on the page will be removed, just in case
			if ( $(this).closest("form").find('input[name=posted_actions]').length >= 1) {
				$(this).parents("div.actions").find("#posted_" + $(this).attr("name")).addClass("keep");
				$("input[name=posted_actions]:not(.keep)").remove();
			}

			// if we are submitting a popup form + a chained form, post the form to the previous popup window
			// previous popup window (URL) is saved as the href value of the controls of the last button breadcrumb
			if ( $(this).parents("#jbox_main").find('*[data-jbox-jlink="back"]').length ) {
				new_action = $('#jbox_main *[data-jbox-jlink="back"]').last().attr("href");
				prev_page = $("#jbox_main form").attr("action");

				// pass the previous page (to be removed from session) // pass the page action (to be removed from breadcrumbs)
				$("#jbox_main form").prepend('<input type="hidden" name="prev_page" value="'+prev_page+'" />\
				<input type="hidden" name="through_page" value="'+new_action+'" />');

				// as the page will not be refreshed, change the popup window title by using the last breadcrumb title
				$("#jbox_main *[data-jbox='title']").text($('#jbox_main *[data-jbox-jlink="back"]').last().text());

				// execute a self submit
				// difference between self_submit and submit() is that the self_submit is executed inside the popup
				self_submit($(this), new_action);

			// this is either a normal submit button on the page or in the popup
			// set the form action to the current URL action
			// set the target to the current window (prevents submitting to a different window)
			} else {
				elem = $(this).closest("form");
				elem.attr("action", window.parent.location.href).attr("target", window.parent.window.name);

				// a validation is passed already at this point
				// submitting an element in this way will not trigger a validation again
				if (elem.find(".block_page").length) blockPage("Please wait...");
				elem[0].submit();

    		}
    	}

	// attach a self submit to all input elements which can be 'changed' (i.e. dropdowns)
	// does not attach a self submit to checkboxes (cross browser issue, see next section)
	}).on("change", ".self_submit:not(.checkbox)", function(e){
    	e.stopPropagation();
    	e.preventDefault();
		self_submit($(this), $(this).parents("form").attr("action"));

	// attach a self submit to all elements with both classes 'self_submit' and 'checkbox'
	}).on("click", ".self_submit.checkbox", function(e){
    	e.stopPropagation();
    	e.preventDefault();
    	self_submit($(this), $(this).parents("form").attr("action"));

	// disable default browser form submit on enter
	// causes issue on IE
	}).on("keydown", "form input, form select", function(e){
    	if (e.keyCode == 13) {
			e.stopPropagation();
			e.preventDefault();
			$(this).closest("form").find('input[type="submit"]').trigger("click");
		}

	// save draft popup button click event
	// uses: jquery.functions.js
	}).on("click", "*[data-save-draft]", function(e){
		e.stopPropagation();
		e.preventDefault();
		form = $(this).closest("form");

		// if there is a file submit in progress, prevent form submit and show a warning message
		// sbtndis is set to true during the ajax file upload process
		// uses: jquery.functions.js
		if (sbtndis) {
			show_hint($(this), "There is a <b>file upload in progress</b>. Please wait until it finishes in order to save the draft.");
			return false;
		}

		// prepend hidden elements, used on submit to add the data to the saved popups session
		// if the form has been edited before, it will not create a new draft but overwrite the previous one
		if ( form.find("input[name=wasp]").length ) form.prepend('<input type="hidden" name="paused_action" value="' + form.find("input[name=wasp]").val() + '" />');
		else form.prepend('<input type="hidden" name="paused_action" value="' + form.attr("action") + '" />');

		// append title
		form.prepend('<input type="hidden" name="paused_title" value="' + $("#jbox_main *[data-jbox='title']").text() + '" />');

		// remove all required classes - this is iPad fix for unblocking validation errors
		form.find(".required").removeClass("required");

		// set the action and name and submit the form
		form.attr("action", window.parent.location.href).attr("target", window.parent.window.name).submit();

	// discard saved draft
	// the button is shown once you are editing a previously saved draft
	}).on("click", "*[data-discard-draft]", function(e){
		e.stopPropagation();
		e.preventDefault();
		form = $(this).closest("form");

		// prepend hidden element (discard_action), used on submit to remove the data from the saved popups session
		form.prepend('<input type="hidden" name="discard_action" value="' + form.find("input[name=wasp]").val() + '" />');

		// remove all required classes - this is iPad fix for unblocking validation errors
		form.find(".required").removeClass("required");

		// set the action and name and submit the form
		form.attr("action", window.parent.location.href).attr("target", window.parent.window.name).submit();

	// chained popup forms
	// going backward or forward in the chained popup forms
	// uses: jquery.functions.js
	}).on("click", "*[data-jbox-jlink]", function(e){
		e.preventDefault();
		jbox = $(this).parents("#jbox_main");

		// show 'Please wait...' feedback
		blockPage("Please wait...");

		// change the title of the jbox
		jbox.find("*[data-jbox='title']").text($(this).text());

		// post the data into the new popup
		// URL of the new popup is coming from the href attribute of the link we have clicked on
		// if a data attribute is 'forward', it will be passes as the parameter 'type'
		// in Libhtml, this parameter is used to either put or get the current popup data from the session
	   	$.post( $(this).attr("href"), { "page" : $(this).attr("href") , "type" : $(this).data("jbox-jlink"), "jbox_opened" : true,
			"jbox_id" : jbox.find("*[data-jbox='header']").attr("rel"),
			"prev_page" : jbox.find("form").attr("action"),
			"pass_vars" : jbox.find("form").serialize()
		},
			function(data){

				// once the result is returned, overwrite the current jbox html, resize the jBox and refreshJS
				jbox.find("*[data-jbox='content']").html(data);
               	jBoxResize(( jbox.width() + 100), ( jbox.height() + 100 ));
               	if (typeof refreshJS == 'function') refreshJS();

			}
		);

	// top right application menu, application switcher popup
	}).on("click", "*[data-show-app-switcher]", function(e){
		e.preventDefault();

		// resize the body elements so there is no glitch once the body scrollbars are removed
		jBoxResizeBody();

		// build a html
		// this is like building a custom jBox, but because the header and features are different, only some jBox elements can be reused (like overlay)
		$('html').addClass("hbox");
		$('body').addClass("jboxed");
		apphtml = '<div class="jbox_overlay" data-app-switcher="true" data-close-overlay="true" style="width:'+($(window).width())+'px; height:'+($(window).height() - 100)+'px; top:'+$(window).scrollTop()+'px">'+
		'<div class="jbox_main jbox_app_switch" style="margin:'+($(window).height() / 2 - 160)+'px 0px 0px '+($(window).width() / 2 - 450)+'px;">';

		// show quick access bar only if number of apps is greater then 4 (admin + logout)
		if ( $("div.head_wrap li.hide").length ) {
			apphtml += '<h2 class="top">Quick access</h2>'+
			'<div data-app-switcher-section="quick" class="section">';
				$('div.head_wrap ul.apps li.qacc').each(function(index){
					apphtml += '<div data-app-switcher-app="true" class="app qapp">' + $(this).html() + '</div>';
				});
			apphtml += '</div>'+
			'<h2>All applications</h2>'+
			'<div data-app-switcher-section="all" class="section all_apps">';
				$('div.head_wrap ul.apps li.hide').each(function(){
					apphtml += '<div data-app-switcher-app="true" class="app">'+$(this).html()+'</div>';
				});
			apphtml += '</div>'+
			'<span class="hint"><span class="ico_binfo"><i class="fa fa-info"></i></span>Drag &amp; drop your most used applications to Quick access section</span>';

		} else {
			apphtml += '<h2 class="top">All applications</h2>'+
			'<div data-app-switcher-section="all" class="section">';
				$('div.head_wrap ul.apps li.qacc').each(function(){
					apphtml += '<div data-app-switcher-app="true" class="app">'+$(this).html()+'</div>';
				});
			apphtml += '</div>'+
			'<span class="hint"><span class="ico_binfo"><i class="fa fa-info"></i></span>Drag &amp; drop to change the order of your applications</span>';

		}

		$('body').append(apphtml + '</div>');

		// the following section is used to enable drag + drop sorting of the applications in the quick launch panel
		// 'togo_position' variable holds the index (order) value of the app we are dragging
		togo_position = 0;
		$(document).on("mousedown", "*[data-app-switcher-section='all'] *[data-app-switcher-app]", function(){
			togo_position = $(this).index();
			$(this).attr("title", tip);
			$("span.ui-tooltip").remove();
			$("span.ui-temp-tooltip").remove();
		});

		// sortable is build using two 'connected lists' - 'Quick access' and 'All applications' lists
		// 'quick_start' is set when the dragging (using sortable plugin) starts
		// it is set to true if the application belonged to the 'Quick access' section
		quick_start = false;
		quick_access_num = $('div.head_wrap ul.apps li.qacc').length;

		$("*[data-app-switcher] div.section").sortable({
			connectWith: "div.section",
			tolerance: 'pointer',
			start: function(event, ui){
				// is this starting from the quick list? don't change opacity if it does
				quick_start = ( $(this).find("div.qapp").length ) ? true : false;
			},

			// this event is triggered when an item from a connected sortable list has been dropped into another list
			receive: function(event, ui) {
	            // misc check if the number of items in the quick access section is allowed
				if ( $(this).find("div.togo").length == 0) {
	                $(ui.sender).sortable('cancel');
					show_hint($(".jbox_app_switch"), "You cannot add more apps to the Quick access list, but you can replace the existing apps from the Quick access list.");

                // if ok, move previous quick application icon to position where the new element came from (swap them)
	            } else { // if ( $(this).children().length == quick_access_num )
	            	if (togo_position == 0) $("*[data-app-switcher-section='all']").prepend($(this).find("div.togo"));
	            	else $("*[data-app-switcher-section='all'] *[data-app-switcher-app]:eq("+(togo_position-1)+")").after($(this).find("div.togo"));
	            }

	            // update classes
				$("*[data-app-switcher-section='quick'] *[data-app-switcher-app]").css("opacity", "1").removeClass("togo").addClass("qapp");
				$("*[data-app-switcher-section='all'] *[data-app-switcher-app]").css("opacity", "1").removeClass("togo").removeClass("qapp");

	        },

			// this event is triggered during sorting
	        sort: function(event, ui){
	        	// if the element was in the Quick access panel, don't change opacity
				if ( !quick_start ) {
		        	$("*[data-app-switcher-app].togo").css("opacity", "1").removeClass("togo");
		        	if ( $("*[data-app-switcher-section='quick'] div.ui-sortable-placeholder").length) {
						$("*[data-app-switcher-section='quick'] *[data-app-switcher-app]:eq("+($("*[data-app-switcher-section='quick'] div.ui-sortable-placeholder").index())+")").css("opacity", "0.3").addClass("togo");
		        	}
	        	}
	        },

			// this event is triggered when sorting has stopped
			// this is triggered after receive, it will be executed in all cases
	        stop: function(){

				// misc check if the application popup is opened
	        	if ( $("*[data-app-switcher]").length) {

					// create the array of application icons 'rel' parameters
					// 'rel' parameter is coming from the Libhtml, and contains ID of the app in the database
					var ids = new Array();
					$("*[data-app-switcher-app] > a").each(function() { ids.push($(this).data('app-id')); });
					$.post(SYSTEM_ROOT+'ajax/ajax_sort_apps.php', { 'action' : 'update', 'excupdate' : JSON.stringify(ids)}, function(data){

						// this function is used after a data is received from any ajax file
						// if 'data == no access' it means the session has expired or similar, which will log out the user
						check_access(data);

						// update current applications visiblity in the application menu (top right of the page)
						$("*[data-applications-menu] ul > li").addClass("tmpremove");
						$("*[data-current-app-name]").fadeOut(100);
						$("*[data-applications-menu]").fadeOut(100, function(){
							$("*[data-app-switcher-section='quick'] *[data-app-switcher-app]").each(function(){
								$("*[data-applications-menu] > ul").append('<li class="qacc">'+$(this).html()+'</li>');
							});

							$("*[data-app-switcher-section='all'] *[data-app-switcher-app]").each(function(){
								if ( $("*[data-applications-menu] > ul li.tmpremove").length > 4) $("*[data-applications-menu] > ul").append('<li class="hide">'+$(this).html()+'</li>');
								else $("*[data-applications-menu] > ul").append('<li class="qacc">'+$(this).html()+'</li>');
							});

							if ( $("*[data-applications-menu] > ul li.qacc:not(.tmpremove) a.app-active").length == 0) $("*[data-applications-menu] > ul").append('<li class="othersel">'+$("*[data-applications-menu] > ul li.hide a.app-active").parents("li").html()+'</li>');
							$("*[data-applications-menu] > ul li.tmpremove").remove();
							$("*[data-current-app-name], *[data-applications-menu]").fadeIn(100);

						});
					});
				}
	        }
		});

		return false;

	// the following will stop any other events, but just follow the application URL on click
	}).on("click", "*[data-app-switcher] a", function(e){
		e.stopPropagation();

	// the next section controls all tooltips events
	}).on("mouseover", "html:not(.ipad) .tooltip", function(e){

		// put the title (tooltip text) into the global tip variable
		tip = $(this).attr("title");
	   	if (tip != '' && tip != 'undefined' && $(this).attr("title")) {

			// unset the element real title, so we do not display two tooltips (framework + default browser)0
	   		$(this).attr("title","");

			// remove any previous tooltips on the page
			$("span.ui-tooltip").remove();
			$("span.ui-temp-tooltip").remove();

			// and create a fresh tooltip element, set the top and left position
	   		$("body").append("<span class=\"ui-tooltip\" style=\"top:"+($(this).offset().top-30)+"px; left:"+($(this).offset().left+$(this).width()+5)+"px;\">"+tip+"</span>");

			// if the new left and top positions are making tooltip appear off the page, adjust the tooltip position
			if (($("span.ui-tooltip").offset().left + $("span.ui-tooltip").width()) > ($(window).width() - 20)) {
				$("span.ui-tooltip").css({"left":$(this).offset().left-$("span.ui-tooltip").width() - 20});
    		}
			if ($("span.ui-tooltip").offset().top - $(window).scrollTop() < 10) {
				$("span.ui-tooltip").css({"top": $(window).scrollTop() + "px"});
			}
    	}

	// when the mouse leave the element with the tooltip
	// remove the tooltip element from the page, and return the 'tip' value to the title element of parent element
    }).on("mouseout", ".tooltip", function(){
		$("span.ui-tooltip").remove();
    	$(this).attr("title", tip);

	// this controls if the tooltip should follow the mouse movements
    }).on("mousemove", ".float_tooltip", function(e){
   		if ($("span.ui-tooltip").length) {
   			$("span.ui-tooltip").css({"top": (e.pageY - 20), "left": (e.pageX + 20 )});
   			if (($("span.ui-tooltip").offset().left + $("span.ui-tooltip").width()) > ($(window).width() - 20)) {
				$("span.ui-tooltip").css({"left": (e.pageX - 200) });
			}
   		}

	 // input and textarea limit notices and warning
	 // the following check is executed after every key press
    }).on("keyup", "*[data-limit]", function(){

		// real limit is coming from the limit data attribute
		c_limit = $(this).data("limit");
		val = $(this).val();
		csength = val.length;
		ptr = $(this).parents("tr");

		// this is a warning (red text) which prevents user from typing any more
		if ( csength > c_limit) {
	    	$(this).val( $(this).val().substring(0, c_limit));
	    	ptr.addClass("limit_hit").removeClass("limit_warning");

			// set the current characters counter (i.e. 200/255)
			ptr.find("span.current").html(c_limit);

		} else {

			// set the current characters counter (i.e. 200/255)
			ptr.find("span.current").html(csength);

			// these are notices (purple text) which just highlight / remove the current characters counter
			if ( (csength > c_limit - 20) && (csength < c_limit) ) {
				ptr.addClass("limit_warning").removeClass("limit_hit");
			} else if ( csength >= c_limit) {
				ptr.addClass("limit_warning").removeClass("limit_hit");
			} else {
				ptr.removeClass("limit_hit").removeClass("limit_warning");
			}
		}

	// form help sections accordion
	}).on("click", "*[data-view-table-help]", function(e){
		e.stopPropagation();
		el = $(this).parents("tr").next("*[data-table-help]");
		el.hasClass("hide") ? el.removeClass("hide") : el.addClass("hide");

	// file uploader
	// resize function trigger, which opens a mini jBox allowing an image to be resized
	}).on("click", "*[data-file-upload-resize]", function(e){
		e.preventDefault();

		// set the current image path to a global variable (used in the next function)
		window.cimg = $(this).parents("tr.template-download").find("img");
		jBoxMini(SYSTEM_ROOT+"ajax/ajax_img_resize.php?file="+encodeURIComponent($(this).data("path")), 200, 130, $(this).offset().left, $(this).offset().top);
		return false;

	// file uploader
	// resize function submit button, which submits the resize values to ajax file
	// in ajax file, the image is resized either by width or height
	// uses: jquery.functions.js
	}).on("click", "*[data-file-resize-action]", function(){ // file upload shared functions

		// fade out the uploaded image so there is no glitch when the resized image if finished
		$(window.cimg).fadeOut();
		$.post(SYSTEM_ROOT+"ajax/ajax_img_resize.php", { "file_name": $("form.image_resize input.file_name").val(), "width" : $("form.image_resize input.width").val() , "height" : $("form.image_resize input.height").val() }, function(data) {
			check_access(data);
			$(".jbox_mini").remove();
			$(window.cimg).fadeIn();
			window.cimg = "";
		});
    	return false;

	// right click anywhere on the page
	// uses: jquery.rangy.min.js, jquery.functions.js
	}).on("mousedown", function(e){

		// enable only if the right button is pressed
		if (!$("body").hasClass("disable_rc")){
			if (e.button == 2 ) {

				// uses: jquery.rangy.min.js for a xbrowser get selection
				var range = rangy.createRange();
				range.selectNodeContents(document.body);
				var sel = rangy.getSelection();

				// disable the right click if mouse button is ctrl key is pressed / if the click was inside the popup / or if the selection is not empty
				if (e.ctrlKey == false && $(e.target).parents("#jbox_main").length == 0 && !$(e.target).is("input") && sel == ''){
					pageRightClick(e);
				}

			// if the pressed button is not right button, and if there is a right click menu opened, close the menu
			} else if ( $(e.target).parents("div.rcmenu").length == 0) {
				$(document).unbind("contextmenu");
				$("div.rcmenu").remove();

				// if this right click menu was attached to the row, remove the class from the row
				$("tr.has_rcmenu").removeClass("has_rcmenu");
			}
		}

	 // disable right click menu on inside jbox click
	}).on("mousedown", "#jbox_main *", function(e){
		e.stopPropagation();

	// right click row actions
	// uses: jquery.rangy.min.js for a xbrowser get selection
	}).on("mousedown", "body:not(.disable_rc) table.rc_enabled tr", function(e){

		// if a right click button was clicked on table row with '.rc_enabled' class
		if (e.button == 2 && e.ctrlKey == false){
			var range = rangy.createRange();
			range.selectNodeContents(document.body);
			var sel = rangy.getSelection();

			// if control key was not pressed and no text is selected on the page
			if (sel == '') {

				// 'selected_anchor' is not empty if we have clicked on the link (not jbox trigger)
				selected_anchor = ($(e.target).is("a") && !$(e.target).hasClass("jbox")) ? $(e.target).attr('href') : false;

				// disable the selection by selecting the parent
				$(e.target).closest('td').select();
				e.stopPropagation();

				// as we are displaying all row specific actions in the right click menu
				// are there any actions for that row?
				if ( $(this).find("a.action").length ) {

					// disable browser's right click
					$(document).bind("contextmenu", function(e){
						return false;
					});

					// disable any previous right click menu
					$("tr.has_rcmenu").removeClass("has_rcmenu");

					// add right click menu class to this row
					$(this).addClass("has_rcmenu");

					// remove previous menu
					$("div.rcmenu").remove();

					// build right click menu html
					rchtml = '<div class="rcmenu">';
						if (selected_anchor) rchtml += '<a href="'+selected_anchor+'" class="open_link" target="_blank"><span class="ico_app"><i class="fa fa-external-link-square"></i></span><span class="txt">Open link in new tab</span></a>';

						 // append quick filter action (class click_me)
						if ( $(this).find("a.click_me").length) rchtml += '<a href="'+($(this).find("a.click_me").attr("href"))+'"><span class="ico_app"><i class="fa fa-share"></i></span><span class="txt"><b>'+$(this).find("a.click_me").text()+'</b></span></a>';

						// append show more trigger
						if ( $(this).find("div.text_toggler").length) {
							if ( $(this).find("*[data-text-toggler]").data("text-toggler") == "show" ) rchtml += '<a class="toggle" data-text-toggler="show"><span class="ico"><i class="fa fa-plus"></i><i class="fa fa-minus"></i></span><span class="txt">Show more</span></a>';
							else rchtml += '<a class="toggle hide_toggler" data-text-toggler="hide"><span class="ico"><i class="fa fa-plus"></i><i class="fa fa-minus"></i></span><span class="txt">Show less</span></a>';

						}

						// view jbox image trigger
						if ( $(this).find("a.jbox_img").length) rchtml += '<a class="vimg jbox_img" href="'+$(this).find("a.jbox_img").attr("href")+'"><span class="ico_app"><i class="fa fa-picture-o"></i></span><span class="txt">View image</span></a>';

						// append copy / edit / delete actions
						rchtml += '<span class="subwrap">';
							$( $(this).find("a.action") ).each(function(){ rchtml += $(this).parent().html(); });
						rchtml += '</span>';
					rchtml += '</div>';

					// append, adjust the menu position and refreshJS
					$("body").append(rchtml);
					if (e.clientX + 200 < $(document).width()) pl = e.clientX; else pl = e.clientX - ((e.clientX + 230) - $(document).width() - 20 );
					if (e.clientY + $(window).scrollTop() + $("div.rcmenu").height() < ( $(window).height() + $(window).scrollTop())) pt = e.clientY + $(window).scrollTop();
					else pt = e.clientY - ( (e.clientY + $("div.rcmenu").height()) - ($(window).height() + $(window).scrollTop())) - 15;
					$("div.rcmenu").animate({"top": pt+"px", "left" : pl + "px"}, 0, function(){ $("div.rcmenu").fadeIn(0); });
					if (typeof refreshJS == 'function') refreshJS();

				// if there are no actions, ignore the table and display the standard page right click menu
				} else {
					pageRightClick(e);

				}

			// if user is holding down control key, do not show the rc menu
			} else if ( e.ctrlKey ) {
				// remove previous menu, remove selectied row
				removeRightClickMenu();

			}
		}

	// double click on the row
	}).on("dblclick","table.summary tr", function(e){
		e.preventDefault();

		// if this cell is not a multi toggle or inline edit, and there is an edit action jbox
		// just trigger a click on the edit button
		if (!$(e.target).hasClass("multi_toggle") && $(this).find('a.dblclick_action').length){
			$(this).find("a.dblclick_action").trigger("click");
		}

	//	main page layout, show or hide the title bar (logo, application menu)
	//	the current state is written into the cookie, so the position is remembered on the next page
	}).on("click", "*[data-header-toggle]", function(e){
		e.stopPropagation();
		el = $(this);

		if ( el.data("header-toggle") == "on") {
			$("div.head_wrap").css('display', "none");
			el.data("header-toggle", "off");
			el.addClass("header_off").removeClass("header_on");
			$.cookies.set( "showTopCookie" , "off" );

		} else if ( el.data("header-toggle") == "off") {
			$("div.head_wrap").css('display', "block");
			el.data("header-toggle", "on");
			el.addClass("header_on").removeClass("header_off");
			$.cookies.set( "showTopCookie" , "on" );

		}

	//	main page layout, show or hide the side bar (sitemap, history)
	//	the current state is written into the cookie, so the position is remembered on the next page
	}).on("click", "*[data-toggle-side-menu]", function(e){
		e.stopPropagation();
		el = $(this);

		if ( el.data("toggle-side-menu") == "on") {
			$("td.side_cell").css('display', "none");
			$("div.side_panel").css('display', "none");
			el.data("toggle-side-menu", "off");
			el.addClass("side_off").removeClass("side_on");
			$.cookies.set( "showSideCookie" , "off" );

		} else if ( el.data("toggle-side-menu") == "off") {
			$("td.side_cell").css('display', "table-cell");
			$("div.side_panel").css('display', "block");
			el.data("toggle-side-menu", "on");
			el.addClass("side_on").removeClass("side_off");
			$.cookies.set( "showSideCookie" , "on" );

		}

	//	scroll to top / bottom
	}).on("click", "*[data-scroll]", function(e){
		e.stopPropagation();
		el = $(this);

		if ( el.data("scroll") == "to_top") {
			$('html, body').animate({ scrollTop: 0 }, 300);

		} else if ( el.data("scroll") == "down") {
			$('html, body').animate({ scrollTop: $(window).scrollTop() + $(window).height() - 80}, 300);

		}

		return false;

	// expand / collapse cell details
	}).on("click", ".expand_details", function(e){

		// remove any previously opened details in the parent table
		$(this).closest("table").find("tr.objectDetails div.od_wrap").slideUp("fast", function(){
			$(this).parents("tr.objectDetails").remove();
		});
		$(this).closest("table").find("tr.rdsel").removeClass("rdsel");
		$(this).closest("table").find("td.ddsel").removeClass("ddsel");
		$(this).closest("table").find("div.collapse_details").addClass("expand_details").removeClass("collapse_details").attr("title","Expand details");

		// details to expand can either come from data-url or href attributes
		if ($(this).attr("data-url")){
			elem = $(this);
			expand_href = $(this).attr("data-url");
		} else {
			elem = $(this).parent().find("a");
			expand_href = '#';
			if (elem.attr('href') && elem.attr('href') != '#') expand_href = elem.attr('href');
		}

		// post data
		$.post(SYSTEM_ROOT+'ajax/ajax_object_details.php', { 'href' : expand_href, 'class': $(this).attr('rel'), 'method': $(this).data('method') }, function(data) {
			check_access(data);

			// if ajax script returned a result
			// add the html, styling classes, show the element (slideDown), toggle tooltip text and refreshJS
			if ($.trim(data)) {
				$(elem).parent("td").find("div.expand_details").removeClass("expand_details").addClass("collapse_details").attr("title", "Collapse Details");
				$(elem).closest("tr").addClass("rdsel");
				$(elem).closest("td").addClass("ddsel");
				$(elem).closest("tr").last().after('<tr class="objectDetails"><td class="od" colspan="100%"><div class="od_wrap clearfix">'+data+'</div></td></tr>');
				// $("html, body").stop().animate({ scrollTop: elem.closest("tr").offset().top - 100}, "fast");
				$("tr.objectDetails div.od_wrap").slideDown("fast");
				tip = "Collapse details";
				if (typeof refreshJS == 'function') refreshJS();

			// if there was some issue, show a tooltip with the message
			// uses: jquery.functions.js
			} else {
				show_hint($(elem).parent("td").find("div.expand_details"), "No details could be displayed.");

			}

		});

	// action similar to the expand details action (above)
	// but makes any element in that cell as a trigger, i.e. 'Click to see full details' cell link
	}).on("click", ".expand_url_details", function(e){
		$(this).parent("td").find(".expand_details").trigger("click");
		return false;

	// expand / collapse cell details
	}).on("click", "div.collapse_details", function(e){

		// remove any previously opened details in any table
		$('tr.objectDetails div.od_wrap').slideUp("fast", function(){
			$("tr.objectDetails").remove();
		});

		// adjust the style and change the tooltip
		$(this).addClass("expand_details").removeClass("collapse_details").attr("title", "Expand details");
		$(this).closest("tr.rdsel").removeClass("rdsel");
		$(this).closest("td.ddsel").removeClass("ddsel");
		tip = "Expand details";

	// paused - can be enabled if needed
	// action similar to the collapse details action (above)
	// executed when you click anywhere inside the details row
	// }).on("click", "html:not(.ipad) div.od_wrap", function(e){
		// $('tr.rdsel div.collapse_details').trigger("click");

	// menu preview dropdown menu
	// activated on hover over the table cell elements, but only if 'Show shortcuts menu' user preference option is enabled
	}).on("mouseover", "*[data-load-submenu]", function(e){
		$('div.dropdown_menu').empty();
		e.stopPropagation();
		elem = $(this);

		$.post(SYSTEM_ROOT+'ajax/ajax_dropdown_menu.php', { 'href' : elem.attr('href'), 'name': elem.html(), 'class': elem.data('load-submenu') }, function(data) {
			check_access(data);

			// remove any previous inline menus and adjust the position of a new menu
			$('div.dropdown_menu').empty();
			$('div.dropdown_menu').append("<ul class='dropdown_items'></ul>");

			// the following is returned by the ajax script
			// just append items one after the another
			for (var i=0; i < data.length; i++) {
				if(data[i]['class'].search('details') > 0) {
					$('ul.dropdown_items').append("<li class='normal hasDetails'><a class='"+data[i]['class']+"' href='"+data[i]['url']+"'>"+data[i]['name']+"<span class='arrow'><i class='fa fa-caret-right'></i></span></a></li>");
					$('ul.dropdown_items').append("<li id='details_holder'></li>");
				} else {
					$('ul.dropdown_items').append("<li class='normal'><a class='"+data[i]['class']+"' href='"+data[i]['url']+"'>"+data[i]['name']+"</a></li>");
				}
			}

			if ( $('div.dropdown_parent').offset().top + $('div.dropdown_parent').height() >= $(document).height() ) {
				$('div.dropdown_parent').css( "top" , $('div.dropdown_parent').offset().top - $('div.dropdown_parent').height() );
			}

			$('div.dropdown_parent').css({'display':'block', 'opacity':'1'});
			$('div.dropdown_parent').css('left',elem.offset().left - 5);
			$('div.dropdown_parent').css('top', elem.offset().top + 21);

			if (typeof refreshJS == 'function') refreshJS();

		},"json");
		return false;

	// menu preview dropdown menu
	// close the menu when the mouse pointer leaves the link
	}).on("mouseout", "*[data-load-submenu]", function(){
		$("div.dropdown_parent").stop().delay(500).fadeTo(0, 0);

	// menu preview dropdown menu
	// expand more details link
	}).on("click", "div.dropdown_parent a.details", function(e){

		$.post(SYSTEM_ROOT+'ajax/ajax_class_details.php', { 'href' : $(this).attr('href') }, function(data) {
			check_access(data);
			$('#details_holder').empty().removeClass("hasData");
			$('#details_holder').append(data).addClass("hasData");
			if (typeof refreshJS == 'function') refreshJS();
		});
		return false;

	// menu preview dropdown menu
	// do not hide on mouse over
	}).on("mouseover", "div.dropdown_parent", function(e){
		e.stopPropagation();
		$(this).stop().fadeTo(0, 1);

	// menu preview dropdown menu
	// hide on mouse out
	}).on("mouseout", "div.dropdown_parent", function(e){
		e.stopPropagation();
		$(this).css('display','none');

	// table nudge
	// scroll down the page when you click on the first table cell column
	}).on("click", "td.table_nudge", function(e){
		$('html, body').animate({ scrollTop: $(window).scrollTop()+$(window).height() - 80}, 300);

	 // global table cell toggler, found in the table headers
	}).on("click", "*[data-text-toggler-all]", function(e){
		e.stopPropagation();

		// set the elements, 'cnum' is the index of the table column which is being toggled
		el = $(this);
		ct = el.closest("table");
		cnum = el.parents("th").index() + 1;

		// the current data[text-toggler-all] attribute holds either the value 'show' or 'hide'
		if ( el.data("text-toggler-all") == "show" ) {
			if ( !el.parents("th").hasClass("setwidth")) {
				el.parents("th").addClass("setwidth").width( el.parents("th").innerWidth() - 8 ); // fix width
			}

			el.data("text-toggler-all", "hide");
			el.addClass('hide_all_exp');
			ct.find("td:nth-child("+cnum+") div.text_toggler").addClass("show_toggler");
			ct.find("td:nth-child("+cnum+") div.text_toggler *[data-text-toggler]").data("text-toggler", "hide");

		} else if ( el.data("text-toggler-all") == "hide" ) {
			el.data("text-toggler-all", "show");
			el.removeClass('hide_all_exp');
			ct.find("td:nth-child("+cnum+") div.text_toggler").removeClass("show_toggler");
			ct.find("td:nth-child("+cnum+") div.text_toggler *[data-text-toggler]").data("text-toggler", "show");

		}

	// scroll the page to the table's last row
	}).on("click", "table.list_table th.table_bottom", function(){
		toffset = $(this).parents('table.list_table').offset().top;
    	theight = $(this).parents('table.list_table').height();
    	winheight = $(window).height();
    	$('html, body').animate({ scrollTop: toffset + theight - winheight + 100 }, 300);
    	return false;

	// append more rows
	// triggered when you click on the 'View more' table row
	}).on("click", "table.list_table tr.add_more:not(.loading) td", function(e){
		e.preventDefault();

		// works only if the table is not being appended to the current table at this moment
		// if block_load is false, it prevents the appending which can be continuously triggered on page scroll
		if ( window.block_load == false ){
			elem = $(this);
			tableid = elem.parents("div.table_wrap").attr("id").replace("table_", "");

			// only executed once
			if (typeof window["starting_pos_"+tableid] === "undefined") window["starting_pos_"+tableid] = elem.parents("div.table_ajax_wrap").find("div.paginator div.pages span.wrap a.current b").text();

			// increment the started page
			window["starting_pos_"+tableid] = parseInt(window["starting_pos_"+tableid]) + 1;

			// pass the current table and that table's current page to the appendMore function
			appendMore(elem, window["starting_pos_"+tableid]);
		}

	// tick / cross icons ajax toggler
	}).on("click", "*[data-ajax-toggle]", function(e){
        e.stopPropagation();
		el = $(this);

		$.post(SYSTEM_ROOT+'ajax/ajax_toggle.php', { 'id' : el.attr('id'), 'type' : el.attr("class") }, function(data) {
			check_access(data);
			el.attr('class', data);

			if (data.indexOf("_toggle_on") != -1) el.parents("table.list_table tr").removeClass("c_inactive");
			else if (data.indexOf("_toggle_off") != -1) el.parents("table.list_table tr").addClass("c_inactive");
		});

	// next three sections are used to toggle permissions
	// tick / cross icons toggler, activated on click
	}).on("click", "span.tick", function(){
		togglePermission($(this));

	// drag and drop toggle permissions
	// 'window.psr' just holds the starting position of the drag and drop action
	}).on("mousedown", "table.permissions td", function(){
		window.psr = $(this).parent("tr").index() - 1;

	// drag and drop toggle permissions
	// 'window.per' holds the ending position for drag and drop action
	// for all rows in between two actions, togglePermissions
	}).on("mouseup", "table.permissions td", function(e){
		window.per = $(this).parent("tr").index();
		if (window.psr > window.per) {
			tv = window.psr;
			window.psr = window.per - 1;
			window.per = tv + 1;
		}

		$(this).parents("table.permissions").find("tr:gt("+window.psr+"):lt("+(window.per - window.psr)+")").each(function(){
			togglePermission($(this).find("td span.tick"));
		});

	 // toggle value by clicking on the cell
	}).on("click", "*[data-cell-toggle]", function (e) {
		e.stopPropagation();

		parent_el = $(this);
	    parent_el.addClass('cell_loading');
		el = parent_el.find(".text");

		// just pass the id attribute to ajax file and show the next value in the cell
		$.post(SYSTEM_ROOT+'ajax/ajax_multi_toggle.php', { 'data' : parent_el.attr('id') }, function(data) {
			check_access(data);
			el.html(data);
			parent_el.removeClass('cell_loading');

		});

	// make cell value editable, by clicking on the cell in the table
	// uses: jquery.jeditable.mini.js
	}).on("click, dblclick", "*[data-inline-edit]", function(e){
		e.stopPropagation();
		el = $(this);
		el.find("span.text").editable(SYSTEM_ROOT+'ajax/ajax_inline_edit.php', {
			indicator: 'Saving...'
    	});

	// next few sections are related to table column filters
	// checkbox, radio and a link triggers work by reloading the page with the new URL
	// opening the filter action
    }).on("click", "table.list_table th div.cfilter", function(e){

		// hide any other filter
		$("div.cfilter_show").removeClass("cfilter_show");

		// adjust the appearance of the new filter
		if ( $(this).offset().left < 350 ) $(this).find("div.filter_wrap").css("margin-left", "0px");
		$(this).addClass("cfilter_show");
		return false;

	// table column filters
	// date or a calendar switch toggle function
	}).on("click", "table.list_table th div.cfilter span.dswitch", function(e){

		tfw = $(this).parents("div.filter_wrap");
		if ( $(tfw).find("ul.doptions").hasClass("hide") ) {
			$(tfw).find("div.filter_picker").addClass("hide");
			$(tfw).find("ul.doptions").removeClass("hide");
			$(this).text("Show calendar");

		} else {
			$(tfw).find("div.filter_picker").removeClass("hide");
			$(tfw).find("ul.doptions").addClass("hide");
			$(this).text("Show options");

		}

	// table column filters
	// checkbox links
	}).on("click", "table.list_table th div.cfilter ul li input[type=checkbox]", function(){

		// show 'Please wait...' feedback
		blockPage("Please wait...");
		window.parent.location.href = $(this).parent("li").find("a").attr("href");

	// table column filters
	// radio links
	}).on("click", "table.list_table th div.cfilter ul li input[type=radio]", function(){

		// show 'Please wait...' feedback
		blockPage("Please wait...");
		window.parent.location.href = $(this).parent("li").find("a").attr("href");

	// table column filters
	// text links
	}).on("click", "table.list_table th div.cfilter ul li a", function(){

		// show 'Please wait...' feedback
		blockPage("Please wait...");
		window.parent.location.href = $(this).attr("href");

	// table column filters
	// text links
	}).on("click", "table.list_table th div.cfilter a.link", function(){

		// show 'Please wait...' feedback
		blockPage("Please wait...");
		window.parent.location.href = $(this).attr("href");

	// next few sections are related to quick table filters
	// when user starts typing, it only filters the current table data
	// action is triggered when user clicks on the default (empty) input field
	}).on("focus", '*[data-quick-table-filter]', function(){
		if (this.value == this.defaultValue) {
			$(this).addClass('typing');
			this.value = '';
		}

	// action is triggered when user clicks outside of the input field without changing the default value
	// if user has entered any text, nothing happens - the text stays unchanged
	}).on("blur", '*[data-quick-table-filter]', function() {
		if (this.value == '') {
			$(this).removeClass('typing');
			this.value = this.defaultValue;
			$(this).find('span.reset_quick').css('display','none');
		}

	// action is triggered when user types into the box
	}).on("keyup", '*[data-quick-table-filter]', function(e){
		ptablewrap = $(this).parents("div.table_wrap");
		sval = $(this).val();

		// remove all previously highlighted words in the table
		$(ptablewrap).find("em.lite").each(function(){
			newText = $(this).parent().text().replace('<em class="lite">', '');
			newText = newText.replace('</em>', '');
			$(this).parent().text(newText);
		});

		// if the value of the field is not empty
		// hide the rows that do not contain the entered text, show reset button
		if ( sval != "") {
			$(ptablewrap).find("table tbody tr").not(":icontains("+sval+")").css("display", "none");
			$(ptablewrap).find("table tbody tr:icontains("+sval+")").css("display", "table-row");
			$(ptablewrap).find('table').css('display', 'table');
			$(ptablewrap).find('span.reset_quick').css('display', 'block');

			// for the remaining rows, which contain entered text, highlight matching text
			$(ptablewrap).find("tr:visible td:not(.column_delete):not(.column_edit) *").each(function(){
				orgText = $(this).text();
				re = new RegExp(sval, "gi");
				newText = orgText.replace(re, '<em class="lite">' + sval + '</em>');
				if (newText != orgText) $(this).html(newText);
			});

		// if the value of the field is empty, just show all the rows and hide the reset button
		} else {
			$(ptablewrap).find('table').css('display', 'table');
			$(ptablewrap).find("table tbody tr").css("display", "table-row");
			$(ptablewrap).find('span.reset_quick').css('display', 'none');
		}

		// after we hid the rows that do not contain the entered text
		// if there are no rows left visible, than append a notice there are no rows to display
		if ( $(ptablewrap).find('table tr:visible').length <= 1) {
			$(ptablewrap).find('table').css('display', 'none');

			// if the message does not exist, create it
			// otherwise, just display it but setting css display = block
			if ( $(ptablewrap).find('p.no_rows').length == 0) $(ptablewrap).find('table').after('<p class=\'no_rows\'>No rows to display.</p>');
			$(ptablewrap).find('p.no_rows').css('display','block');

		// or if there are rows, show the parent table, but hide the notice (if it exists)
		} else {
			$(ptablewrap).find('table').css('display', 'table');
			$(ptablewrap).find('p.no_rows').css('display','none');

		}

		// if there is only one row left, and that one row has a link with the class 'click_me' - enable select on enter
		// also show a hint to the user
		if ( $(ptablewrap).find('table tbody tr:visible').length == 1 && $(ptablewrap).find('table tbody tr:visible a.click_me').length){
			$(ptablewrap).find('table tbody tr:visible a.click_me').addClass("unique");
			if (e.keyCode == "13") window.location.href = $(ptablewrap).find('table tbody tr:visible a.unique').attr("href");

			// uses: jquery.functions.js
			show_hint($(ptablewrap).find('table tbody tr:visible a.click_me'), "Press <b>enter</b> to view the item details");

		} else {
			$(ptablewrap).find('table tbody tr a.unique').removeClass("unique");

		}

	// table quick filter reset
	// this element is only available if the user has typed anything into the quick filter input
	}).on("click", '*[data-quick-table-filter-reset]', function(){
		tw = $(this).parents("div.table_wrap");

		// reset the styling, table rows visibility
		$(this).css("display", "none");
		$(tw).find('input.quick_filter').val("");
		$(tw).find("tr").css('display','table-row');
		$(tw).find('input.quick_filter').blur();
		$(tw).find('table').css('display', 'table');
		$(tw).find('p.no_rows').css('display', 'none');
		$("table.fixed_header").css("display", "none");

		// remove all previous highlights from the matched rows
		$(tw).find("em.lite").each(function(){
			newText = $(this).parent().text().replace('<em class="lite">', '');
			newText = newText.replace('</em>', '');
			$(this).parent().text(newText);
		});

	// page block links
	// any link with this attribute, when clicked, will show a 'Please wait...' feedback
	}).on("mouseup", "*[data-page-block]", function(e) {
		e.stopPropagation();
		blockPage("Please wait...");

	// page block feature
	// any form, when submitted, will show a 'Please wait...' feedback
	}).on("submit", "form", function(e) {
		e.stopPropagation();
		blockPage("Please wait...");

	// page block feature
	// any form element which has onchange or onclick event handlers attached (self submit)
	// when changed, will show a 'Please wait...' feedback
   }).on("change", "*[onchange], *[onclick]", function(e) {
		e.stopPropagation();
		blockPage("Please wait...");

	// user defined table view
	// toggle table columns
   }).on("click", 'div.dropdown_view ul.dropdown_columns input', function(e){
		elp = $(this).parents("div.table_wrap");

		// if the column is not visible, but user wants to display it
		if ($(this).is(':checked')){

			// update the table via the ajax file
			$.post(SYSTEM_ROOT + 'ajax/ajax_update_view.php', { 'action' : 'update_visibility', 'view' : elp.attr("id"), 'column' : $(this).attr('name'), 'display' : '1'},
				function(data){
					check_access(data);

					// if the column was hidden, reload the page - so that a new user settings are picked up on page load
					if ( elp.find('.column_' + data).length == 0) {

						// show 'Please wait...' feedback
						blockPage("Please wait...");
						location.reload();

					// but if the column is still visible (i.e. user has turned it off and on without navigating to the other page), remove the class 'no_display'
					} else {
						elp.find('.column_' + data).removeClass('no_display');
					}

			});

		// if the user wants to hide the column
		} else {

			// show 'Please wait...' feedback
			blockPage("Please wait...");

			// add class to hide the column
			elp.find('.column_' + $(this).attr('name')).each(function(){ $(this).addClass('no_display'); });

			// send a table name, column name and visiblitity to ajax file
			$.post(SYSTEM_ROOT + 'ajax/ajax_update_view.php', {'action' : 'update_visibility', 'view' : elp.attr("id"), 'column' : $(this).attr('name'), 'display' : '0'}, function(data){
				check_access(data);

				// unblock the page, hide the 'Please wait...' feedback
				showFeedback("", "", "", 1, true);
			});
		}

		// if there is only one column left visible, disable the last tick
		if ( elp.find('.dropdown_columns input:checked').length != 1 ) elp.find('.dropdown_columns input:checked').attr('disabled', false);
		else elp.find('.dropdown_columns input:checked').attr('disabled', true);

		// if the table was already altered by user, show reset link and update style
		if ( elp.find('.dropdown_columns input:not(:checked)').length ){
			elp.find('a.hide').removeClass("hide");
			elp.find("div.dropdown_view").addClass("in_use");
		} else {
			elp.find("div.dropdown_view").removeClass("in_use");
		}

		// update the table footer, colspan value
		// used for multiselect
		if (elp.find("tfoot").length){
			elp.find("tfoot").find(".multiactions").attr("colspan", elp.find("table.summary tr.header > th:not(.no_display)").length - 1);
		}

	// user defined table view
	// reset table view, send a request to ajax file, and just reload the page
	}).on("click", 'div.dropdown_view li.reset_view span.link', function() {
		$.post(SYSTEM_ROOT + 'ajax/ajax_update_view.php', {'action' : 'reset_all', 'view' : $(this).parents("div.table_wrap").attr("id") }, function(data){
			check_access(data);
			location.reload();
		});

	// ajax pagination
	// event is triggered when any button in the paginator is clicked, apart from turning off the pagination
    }).on("click", "div.paginator li a:not(.ico_pagination_off)", function(e){
		tableid = $(this).parents("div.table_wrap").attr("id").replace("table_", "");

		// ajax only if one table is on the page, because get_enc_page is not working with window.history.replaceState javascript actions
		if ( $("div.table_ajax_wrap").length == 1 ){
			e.preventDefault();

			// update styling
			$(this).parents("div.table_ajax_wrap").find("table.list_table").animate({"opacity": "0.5"}, 100);

			// into the '.table_ajax_wrap' load the target page (2nd, 3rd, etc) but only request a '.table_wrap' element from the target page
			$(this).parents("div.table_ajax_wrap").load( $(this).attr("href") + " .table_wrap", function(data){
				check_access(data);

				// update styling and push the target URL into the URL bar
				$(this).parents("div.table_ajax_wrap").find("table.list_table").animate({"opacity": "1"});

				// update the starting position (current page) which is used on load more infinite scroll action
				window["starting_pos_"+tableid] = parseInt($(this).find("div.paginator div.pages a.current").text());
				window.history.replaceState("", "", $(this).find("div.paginator div.pages a.current").attr("href"));
				if (typeof refreshJS == 'function') refreshJS();
			});
		}

	 // disable scrolling of popup elements like dropdown menus
	 }).on("mousewheel", "div.table_wrap div.cfilter ul.filter_option, div.table_wrap ul.dropdown_columns, div.img_holder, div.uia-wrap, div.ddmenu span.wrap", function(e){
		d = (e.originalEvent.wheelDeltaY > 0) ? 1 : -1;
		if ( $(this).scrollTop() >= $(this)[0].scrollHeight - $(this).height() + e.originalEvent.wheelDeltaY && d < 0){
			e.preventDefault();
			$(this).scrollTop($(this)[0].scrollHeight);

		} else if ($(this).scrollTop() === 0 && d > 0){
			e.preventDefault();
		}

	// file uploader
	// source switch - Library / Computer
	}).on("click", "span.source a:not(.active)", function(e){
		e.preventDefault();
		$(this).parents("th").find("a.active").removeClass("active");
		$(this).addClass("active");

		// trigger the Computer switch, hide the Library switch
		if ($(this).hasClass("fulocal")) {
			$(this).parents("tr").find("div.lib_source").addClass("hide");
			$(this).parents("tr").find("div.row_fileupload").removeClass("hide");

		// trigger the Library switch, hide the Computer switch
		} else if ($(this).hasClass("fulib")) {
			$(this).parents("tr").find("div.row_fileupload").addClass("hide");
			$(this).parents("tr").find("div.lib_source").removeClass("hide");

			// load images thumbs with ajax, depending on the selected tags (if any)
			loadLibImgs($(this));

		}

	// file uploader
	// triggered when an image is selected from the library
	}).on("click", "div.lib_source div.img_holder a.img", function(e){
		e.preventDefault();
		imgval = $(this).data("lid");

		// write the lid value (image id in the library) into the hidden input file
		$(this).parents("tr").find("input.lib_file").val(imgval);
		$(this).parents("div.img_holder").find("a.selected").removeClass("selected");
		$(this).addClass("selected");

		// remove the uploaded file selected with 'Browse', if present, by triggering a delete button
		$(this).parents("tr").find("*[data-file-upload-delete]").each(function(){
			$(this).trigger("click");
		});

	// file uploader
	// remove selected image by clicking on it again
	}).on("click", "div.lib_source div.img_holder a.selected", function(e){
		e.preventDefault();

		// remove the value from the hidden input element
		$(this).parents("tr").find("input.lib_file").val("");
		$(this).removeClass("selected");

	// file uploader
	// uses: jquery.fileupload.js, jquery.fileupload-ui.js, fileupload-templates.js
	// delete single uploaded file
	}).on("click", "*[data-file-upload-delete]", function(e){
		e.preventDefault();

		// action is slightly different for multi file / single file deletion
		if ($(this).parents("div.row_fileupload").data("multi_file")){

			// put all uploaded files into the array
			window.all_files = new Array;
			valfld = $(this).parents("div.row_fileupload").find("input.uploaded_file");
			prev_files = $(valfld).val();

			if (prev_files != "") {
				$.each(JSON.parse(prev_files), function (index, file) {
					window.all_files.push(file);
				});

				// remove the selected file from the array and also from html
				window.all_files.splice( $(this).parents("tr.template-download").index(), 1 );
				$(this).parents("tr.template-download").remove();
				$("span.ui-tooltip").remove();

				// if this was the last file just remove, adjust the style, enable another files to be uploaded again
				if (window.all_files.length == 0) {
					$(valfld).parents("div.row_fileupload").find("input.uploaded_file").val("");
					$(valfld).parents("div.row_fileupload").find("input.file").attr("disabled", false);
					$(valfld).parents("div.row_fileupload").find("td.file_upload").removeClass("uploaded").removeClass("uploaded_showbtn");

				// if there are other files in the array, put them in a JSON string and write it into the hidden file
				} else {
					$(valfld).val(JSON.stringify(window.all_files));

				}
			}

		// single file deletion
		} else {

			// adjust the style, enable another file to be uploaded again
			$(this).parents("div.row_fileupload").find("input.uploaded_file").val("");
			$(this).parents("div.row_fileupload").find("input.file").attr("disabled", false);
			$(this).parents("div.row_fileupload").find("td.file_upload").removeClass("uploaded").removeClass("uploaded_showbtn");
			$(this).parents("tr.template-download").remove();
			$("span.ui-tooltip").remove();

			// remove the file by calling the plugin's delete file
			$.get($(this).attr("href"));

		}

	// file uploader
	// image rotate event
	// uses: jquery.rotate-min.js
	}).on("click", "*[data-file-upload-rotate]", function(e){
		e.preventDefault();
		rel = $(this);
		rel.parents("tr.template-download").find("img").fadeOut();

		// just send a disc path parameter to ajax file, it will handle rotation
		$.post(SYSTEM_ROOT+"ajax/ajax_img_rotate.php", { "file_name" : rel.data("path") }, function(data) {
			check_access(data);

			// rotate the current image thumbnail using the rotate plugin
			rel.parents("tr.template-download").find("img").rotate( rel.data("rcounter") * 90);
			rel.parents("tr.template-download").find("img").fadeIn();
			rel.data("rcounter", rel.data("rcounter") + 1);
		});

	// file uploader
	// plugin specific, (apparently) open download dialogs via iframes, to prevent aborting current uploads
	// disabled, but kept here for a reference if file uploader is needed for older browsers
	// }).on("click", "div.row_fileupload .files a:not([target^=_blank])", function(e){
		// e.preventDefault();
		// $("<iframe style=\"display:none;\"></iframe>").prop("src", $(this).attr("href")).appendTo("body");

	// remove the selected value in the autocompleter
	}).on("click", "*[data-remove-selected]", function(e){
		e.stopPropagation();
		e.preventDefault();

		el = $(this);
		el_parent = el.parents("div.inputwrap");
		el_trigger = el_parent.find("input.ui-autocomplete-input");
		el_value = el_parent.find("input.autocomplete-value");

		// if the autocomplete had a self submit class
		// remove the selected tag values, tooltip and self_submit the parent form
		if ( el_trigger.hasClass("fksubmit")) {
			el_value.val("");
			$('span.ui-tooltip').remove();
			self_submit( el_trigger, el.parents("form").attr("action"));

		// if there is no self submit attached to the autocomplete element, just remove the values
		} else {
			el_value.val("");
			el_trigger.show().val("");
			$('span.ui-tooltip').remove();
			el.parent("span").remove();

		}

	//Header search
	//TODO rewrite as data
	}).on("click", '#hsterms', function() { // header search
		if (this.value == this.defaultValue) { $(this).addClass('typing'); this.value = ''; }

	}).on("blur", '#hsterms', function() {
		if (this.value == '') {$(this).removeClass('typing'); this.value = this.defaultValue; }

	}).on("keyup", '#hsterms', function(e) {

		if ( $(this).val().length >= 2) {
			$.post('../ajax/ajax_total_search.php', { 'term' : $(this).val() },
				function(data){
					if (data) {
						check_access(data);
						$("div.dacwrap").remove();
						$("#hsterms").after(data);
					} else {
						$("div.dacwrap").remove();
						show_hint( $("#hsterms"), "There are no results available for your search term.");
					}
			});
		}

	// file uploader and general tags
	// remove the tag from the list of selected tags
	}).on("click", "*[data-remove-tag]", function(e){
		e.stopPropagation();
		e.preventDefault();

		el = $(this);
		el_parent = el.parents("div.inputwrap");
		el_trigger = el_parent.find("input.ui-autocomplete-input");
		el_value = el_parent.find("input.autocomplete-value");

		// id of the tag to be removed
		var remove_el = $(this).parent().attr('id') + ',';

		// get the list of all selected tags and remove the tag
		var all_selected = el_value.val();
		var reg = new RegExp(remove_el, 'g');
		var new_values = all_selected.replace(reg, '');

		// write back the list of files into the hidden field
		el_value.val(new_values);
		$('span.ui-tooltip').remove();

		// save parent element reference
		pel = el_value;

		// remove tag from html
		$(this).parent("span").remove();

		// re-load all library images again
		if ( el_trigger.data("image-tags") ) loadLibImgs(pel);

		// if the autocomplete had a self submit class
		if ( el_trigger.hasClass("fksubmit")) {
			self_submit( el_trigger, el_trigger.parents("form").attr("action"));
		}

	 // expand all tabs button
	 // this action will put all tabs one below each other on the page
	}).on("click", "*[data-show-all-tabs]", function(){

		// it just adjusts the styling, tabs are 'un-tabbed' using css
		if ($(this).parents(".no_tabs").length){
			$(this).parents(".no_tabs").find("div.tab_title").addClass("hide");
			$(this).parents(".no_tabs").removeClass("no_tabs").addClass('jquery_tabs');
			$(this).html("Show all");
			resizeWindow();
		} else {
			$(this).parents(".jquery_tabs").find("div.tab_title").removeClass("hide");
			$(this).parents(".jquery_tabs").addClass("no_tabs").removeClass('jquery_tabs');
			$(this).html("Show tabs");
			resizeWindow();
		}

	// table multiselect checkboxes
	// used on multi edit, multi delete tables
	// uses: jquery.functions.js
	}).on("click", ".column_multiselect input", function(){
		if ( $(this).is(":checked") ) $(this).parents('tr').addClass('multiselected');
		else $(this).parents('tr').removeClass('multiselected');
		getSelected($(this));

	// table multiselect checkboxes
	// select all function at the bottom of the table
	// uses: jquery.functions.js
	}).on("click", "input.check_all", function(){

		// if the check all has just been ticked, select all rows in the parent table
		if ( $(this).is(":checked") ) {
			$(this).parents("table.list_table").find(".column_multiselect input").prop("checked", true);
			$(this).parents("table.list_table").find("tbody tr").addClass('multiselected');
		} else {
			$(this).parents("table.list_table").find(".column_multiselect input").prop("checked", false);
			$(this).parents("table.list_table").find("tbody tr").removeClass('multiselected');
		}

		getSelected($(this));

	// dropdown menu
	// prevent any actions if the menu was disabled
	}).on("click", "div.ddmenu.disabled", function() {
		return false;

	// dropdown menu
	// add hover class (IE8 fix)
	}).on("mouseover", "div.ddmenu:not(.disabled)", function() {
		$(this).addClass("ddmenu-hover");

	// dropdown menu
	// remove hover class (IE8 fix)
	}).on("mouseout", "div.ddmenu", function() {
		$(this).removeClass("ddmenu-hover");

	// dropdown menu
	// activate a dropdown menu on click by adding a class
	}).on("click", "div.ddmenu:not(.disabled)", function(e) {
		e.stopPropagation();

		// hide any previously opened dropdown menu
		$('div.ddmenu-active').removeClass('ddmenu-active');
		$(this).addClass("ddmenu-active");

	// datepicker
	// remove selected date by clicking on the x icon
	}).on("click", "div.inputwrap a.cleardate", function(e){
		e.stopPropagation();
		$(this).parents("div.inputwrap").find("input").val("");
		$(this).parents("div.inputwrap").removeClass("hasdate");
		$("#ui-datepicker-div").hide();

		// timepicker specific
		$(".ui-timepicker").hide();
		$(this).parents("div.inputwrap").find("span:not(.colon):not(.time_trigger)").text("");

		// if this element had a self_submit class, trigger a self submit function
		if ( $(this).parents("div.inputwrap").find(".self_submit").length ) self_submit($(this), $(this).parents("form").attr("action"));
		return false;

	// show tooltip for form elements
	// on click (focus) on the form elements which have a tooltip attached to them
	}).on("focus", "table.action_form:not(.full_list_search) input, table.action_form:not(.full_list_search) select, table.action_form:not(.full_list_search) textarea", function(e){

		// this function will be triggered only once,
		if ( $(this).closest("div.tr").data("tshown") == undefined) {

			// set a tshown attribute to true, so that the tooltips are not activated again
			$(this).closest("tr").data("tshown", true);
			hint = $(this).closest("tr").find("span.tooltip").attr("title");

			// uses: jquery.functions.js
			if (hint != undefined && hint != 'Required') show_hint($(this).closest("td"), hint, 2000, 5, -25, "ui-form-tooltip");

		}

	// click to copy to clipboard link
	// when a user clicks on the link with this class
	}).on("click", "a.copyme", function(e){
		e.preventDefault();
		$(".copyrange").remove();

		// append a hidden textarea element and write a full download link into it, coming from the href attribute
		$("body").append('<textarea class="copyrange">'+SYSTEM_ROOT + $(this).attr("href")+'</textarea>');

		// use the browser's function select() to highlight the text in the hidden 'copyrange' element
		$('.copyrange').select();

		// user now needs to press CTRL + C to actually copy the link
		// uses: jquery.functions.js
		show_hint($(this), "<b>Almost there!</b><br/>Now press CTRL + C to copy the link to your clipboard.");

	// CMS feature
	// prevent illegal characters in website's pages titles
	// used in combination with the next sections
	}).on("keydown", "input.url_preview", function(e){

		// don't allow these characters to be entered into the input element
		illegalchars = [16, 186, 187, 188, 189, 190, 191, 192, 219, 220, 221, 222, 223];
		illegalnumchars = [48, 49, 50, 51, 52, 53, 54, 55, 56, 57];
		if ($.inArray(e.keyCode, illegalchars) > -1 || (e.shiftKey && $.inArray(e.keyCode, illegalnumchars) > -1)) {

			// uses: jquery.functions.js
			show_hint($(this), "Page URL can only contain letters and digits");

			return false;
		}

	// CMS feature
	// display url preview text below the input element, as you type
	// uses: jquery.functions.js
	}).on("keyup", "input.url_preview", function(e){
		if ( $(this).parents("td").find("span.url_preview").length == 0){
			$(this).parents("td").append('<span class="add url_preview">Page will be published as <b>'+$(this).data("website") + jSeo($(this).val()) + '</b></span>');
		} else {
			$(this).parents("td").find("span.url_preview b").text($(this).data("website") + jSeo($(this).val()));
		}

	// unique fields database check
	// uses: jquery.functions.js
	}).on("keyup", ".unique", function(e){
		checkUnique($(this));

	// print page buttons, uses default browser function print()
	}).on("click", "*[data-page-print]", function(e){
		e.preventDefault();
		print();

	// strip tags from textareas
	// uses: jquery.functions.js
	}).on("blur paste", "textarea:not(.keeptags)", function(e){
		$(this).val( strip_tags($(this).val()));

	// prevent going away from a form that has changed on the page
	// used on forms on pages (not popups)
	// example: timesheets search
	// uses: jquery.functions.js
	}).on("change", ".monitor_ch input, .monitor_ch select, .monitor_ch textarea", function(){

		// if the parent form has an input with name 'haschng', set its value to 1
		// also trigger the function formHasChanged() which will warn user if he has not save the form
		if ( $(this).parents("form").find("input[name=haschng]").val() == "") {
			$(this).parents("form").find("input[name=haschng]").val("1");
			formHasChanged();
			saveNotification();
		}

	// related to the above
	// clicking on the 'No' button closes the prompt
	}).on("click", ".gprompt a[data-prompt='no']", function(){
		$("body").removeClass("prompt");
		$(".gprompt").remove();
		return false;

	// gFeedback
	// on mouse over, the feedback messages will not dissapear automatically
	}).on("mouseover", "div.g_feedback", function(){
		$(this).stop().css("opacity", "1");

		// clearing the interval stops the messages from dissapearing
		clearInterval(gTimer);

	// gFeedback
	// moving mouse away from feedback messages resets the gTimer interval
	}).on("mouseout", "div.g_feedback", function(){
		gRemove();

	// gFeedback
	// if user clicks on the feedback on the page, it will remove it and reset the gTimer interval
	}).on("click", "div.g_feedback", function(){
		if ( $(this).parents("*[data-jbox='content']").length == 0 && !$("body").hasClass("prompt")
		&& !$("body").hasClass("block")) {
			$(this).remove();
			gRemove();
		}

	// side panel search submit
	}).on("click", "div.side_panel div.search span.submit", function(){
		if ( $(this).parents("div.search").find("input.keyword").val() != '') {
			$(this).parent("form").submit();
		} else {
			$(this).parents("div.search").find("input.keyword").animate({"border-color": "red"}, "normal", function(){
				$(this).parents("div.search").find("input.keyword").delay(1000).animate({"border-color": "#ccc"});
			});
		}

	// search forms open / close toggle
	}).on("click", "div.hide_handle", function(e){
		e.stopPropagation();

		// update the visibility
		// but also send a request to ajax file to put the form visibility into the user session
		if ( $(this).parents("div.page_search").find("div.search_wrap:visible").length ) {
			$(this).parents("div.page_search").find("div.search_wrap").slideUp();
			$.post(SYSTEM_ROOT+"ajax/ajax_misc.php", { "action" : "collapse_form" }, function(data){
				check_access(data);
			});

		} else {
			$(this).parents("div.page_search").find("div.search_wrap").slideDown();
			$.post(SYSTEM_ROOT+"ajax/ajax_misc.php", { "action" : "expand_form" }, function(data){
				check_access(data);
			});
		}

	// next two sections are to applications switch tooltips
	}).on("mouseover", "*[data-swap-app-title]", function(e){
		aapp = $("*[data-current-app-name]").html();
		happ = $(this).data("swap-app-title");
		$("*[data-current-app-name]").html(happ);

	// triggered when the mouse leaves the application icon or text
	}).on("mouseout", "*[data-swap-app-title]", function(e){
		$("*[data-current-app-name]").html(aapp);

	// collapsible sections toggle
	// when collapsing / expanding, all html between this section header and the next section header will be hidden / shown
	}).on("click", "div.sh_section", function(e){
		e.stopPropagation();

    	if ( !$(this).hasClass("coll") ) {
    		$(this).nextUntil("div.section_title, div.actions").slideUp(100);
    		$(this).addClass("coll");

		} else {
			$(this).nextUntil("div.section_title, div.actions").stop().slideDown(100);
			$(this).removeClass("coll");

		}

		$("div.dropdown_parent").css("display", "none");

	// misc, table dump
	}).on("click", "table.dump_array tr.hidden", function(e){
		e.preventDefault();
		$(this).find("td:first > div:not(.strigg)").css("display","block");
		$(this).find("td:first > div.strigg").css("display","none");
		$(this).removeClass("hidden");
		return false;

	// misc, table dump, double click
	}).on("dblclick", "table.dump_array tr.hidden", function(e){
		e.preventDefault();
		$(this).find("td:first div:not(.strigg)").css("display","block");
		$(this).find("td:first div.strigg").css("display","none");
		$(this).removeClass("hidden");
		return false;

	// misc, table dump
	}).on("click", "table.dump_array tr:not(.hidden)", function(e){
		e.preventDefault();
		$(this).find("td:first > div:not(.strigg)").css("display","none");
		$(this).find("td:first > div.strigg").css("display","block");
		$(this).addClass("hidden");
		return false;

	// side panel - set home page
	}).on("click", "a.set_home_page", function(e){
		e.stopPropagation();

		// send a request to ajax file, which will update user preferences table with new homepage
		$('.home_page_link').load(SYSTEM_ROOT + 'ajax/ajax_set_home_page.php');
		showFeedback("", "New home page is saved", "success", 5000);
		return false;

	// load any content via ajax into the target div element
	// target div element needs to have a call '.ajax_loading_target'
	}).on("click", "a.ajax_div_loader", function(e){

		// show 'Please wait...' feedback
		blockPage("Please wait...");

		var elem = $(this);
		$.post(elem.attr('href'), { 'id' : elem.attr('id')}, function(data) {
			check_access(data);
			var target = elem.nextAll(".ajax_loading_target");
			target.empty();
			target.append(data);
			$("div.g_feedback").remove();
		});
		return false;

	// alter tablesorter classes so the 'th' element class is updated
	}).on("click", "table.tablesorter th a", function(e){

		// find out which new class (nc) should be applied to the element
		if ( $(this).parents("th").hasClass("headerSortDown") ) nc = "headerSortUp";
		else if ( $(this).parents("th").hasClass("headerSortUp") ) nc = "headerSortDown";
		else nc = "headerSortUp";

		$(this).parents("thead").find("th").each(function(){ $(this).removeClass("headerSortDown").removeClass("headerSortUp"); });
		$(this).parents("th").addClass(nc);

	// table print popup
	}).on("click", "div.table_options a.prnt", function(e){
		e.preventDefault();

		// get the content of the table to be printed
		printContent = $(this).parents("div.table_wrap").find("table.list_table").html();
		printWindow = window.open(null, 'Print table', 'scrollbars=1,left=0,top=0,width=0,height=0');
		printWindow.document.write('<html><head>');

		// load popup specific css for print and screen media
		printWindow.document.write('<link href="'+SYSTEM_ROOT+'css/print_table.css" rel="stylesheet" type="text/css" media="print" />');
		printWindow.document.write('<link href="'+SYSTEM_ROOT+'css/print_table.css" rel="stylesheet" type="text/css" media="screen" />');
		printWindow.document.write('</head><body><div class="table_wrap"><table class="summary">');

		// append the content and focus the window
		printWindow.document.write(printContent);
		printWindow.document.write('</table></div></body></html>');
		printWindow.document.close();
		printWindow.focus();

		// allow one second pause so that the html is copied over
		setTimeout(function(){
			printWindow.print();
		}, 1000);

		return false;

	});
	/* end of the handlers binding block */


	/* the following functions are executed only once, on page load */
	// enable infinity scroll only if there is one main table on the page
	if ( $('table.list_table').length == 1 && $("table.list_table tr.add_more").length == 1) {

		// the following block will be executed on page scroll, over and over (either mousewheel or scrollbar)
		$(window).scroll(function(){

			// if there is not already an appending triggered (block_load would be set to true)
			// and if the window has been scrolled all they way to the bottom
			if  (window.block_load == false && ($(window).scrollTop() == $(document).height() - $(window).height())){
				tableid = $("table.list_table").parents("div.table_wrap").attr("id").replace("table_", "");

				// if there are any more pages at all in the paginator
				if (typeof window["starting_pos_"+tableid] === "undefined") window["starting_pos_"+tableid] = $("table.list_table").parents("div.table_ajax_wrap").find("div.paginator div.pages span.wrap a.current b").text(); // only executed once

				// increment the selected page position
				window["starting_pos_"+tableid] = parseInt(window["starting_pos_"+tableid]) + 1;

				// append more rows
				appendMore( $("table.list_table tr.add_more td.add_link"), window["starting_pos_"+tableid]);
			}
		});
	}

	// allow user to select multiple rows at once, by holding the shift key
	// uses: jquery.shiftselect.js
	$('.column_multiselect input').shiftSelect();

	// click resets
	// triggered whenever user clicks anywhere on the page
	// used to close the menus, filters, popups, or similar
	$("body *").on("click", function(e){

		// close all dropdown menus (if clicked outside)
		if ( !$(this).hasClass("ddmenu") && $(this).parents("div.ddmenu").length == 0 ) {
			$("div.ddmenu-active").removeClass("ddmenu-active");
		}

		// close all filters (if clicked outside)
		if ( !$(this).hasClass("cfilter") && $(this).parents("div.cfilter").length == 0 ) {
			$("div.cfilter_show").removeClass("cfilter_show");
		}

		// close right click menus (if clicked outside)
		if ( !$(this).hasClass("rcmenu") && $(this).parents("div.rcmenu").length == 0 ) {
			$("div.rcmenu").remove();
			$("tr.has_rcmenu").removeClass("has_rcmenu");
			$(document).unbind("contextmenu");
		}

		// remove help panel (if clicked outside)
       	if ( $(this).parents("div.slide_out").length == 0 || $(this).hasClass("slide_close") ) {
       		$("div.slide_out a.handle span.txt").html("View help");
       		$("div.slide_out").stop().animate({"margin-bottom" : "0px"}, 250).removeClass("slide_opened");
       	}

	});

    // tabs
	// uses: jquery-ui.min.js
    $('div.jquery_tabs').tabs({

		// option to remember selected tab position (tab)
		// side panel tab position is written in the separate cookie (stab)
		cache: true,
		create: function(){
			ccnm = ($(this).hasClass("side_options")) ? "stab" : "tab";
			stab = ( $.cookies.get(ccnm) <= $(this).find("ul li ").length) ? $.cookies.get(ccnm) : 0;
			$(this).tabs({ "active" : stab });
			$(this).find(".ui-tabs-hide").removeClass("ui-tabs-hide");
			if ($("form input[name=form_scroll]").val()!=undefined) $("html, body").scrollTop($("form input[name=form_scroll]").val());
		},
		activate: function( event, ui ) {
			ccnm = ($(this).hasClass("side_options")) ? "stab" : "tab";
			$.cookies.set(ccnm, String(ui.newTab.index()) );
		},

		// if this was an ajax tab (https://jqueryui.com/tabs/#ajax) add the class that removes the spinner icon
        load: function(event, ui) {
			$("div.ui-tabs-panel:eq("+ui.newTab.index()+")").addClass("removebg");
		}

   });

	// the next section is related to the floating sections (fixed menu, fixed action bar) & floating table headers
	// what sections on the page are fixed, based on their classes?
	// adjust the top threshold position variable (orgfapos)
	if ( $("div.fixed_menu").length ) orgfapos = $("div.fixed_menu").offset().top;
	else if ( $("div.fixed_actions").length ) orgfapos = $("div.fixed_actions").offset().top;

	// bind the following to every page scroll action (mousewheel or scrollbars)
	$(window).bind("scroll", function(){

		// check if the window scroll has moved enough down to apply a 'fix_title' class
		if ( orgfapos ) {
			if ( $(window).scrollTop()  >= orgfapos ) {

				// add padding to the body of the page, so the main content does not move
				$("body").css("padding-top", $("div.fixed_menu").height() + $("div.fixed_actions").height());

				$("div.content_section").addClass("fix_title");
				if ( $("div.fixed_menu").length && $("div.fixed_actions").length ) $("div.fixed_actions").css("top", $("div.fixed_menu").height());

			// remove the class if it has not (moved upwards)
			} else {
				$('div.fix_title').removeClass("fix_title");
				$("body").css("padding-top", "0px");

			}
		}

		// floating table headers
		// do the following for each table with the floating header
		$("table.float_header").each(function(){
			ftbind = $(this);
			tind = $("table.float_header").index(ftbind);

			// adjust top if there are any fixed elements
			ftop = $(".fix_title .fixed_menu").height() + $(".fix_title .fixed_actions").height();

			// if page was scroll enough down
			if ( ( (ftbind.offset().top) - $(window).scrollTop() - ftop < 0) && ( (ftbind.offset().top + ftbind.height()) - ftop > $(window).scrollTop()) ) {
				ftbind.attr("rel", "fixed-" + tind);

				if ( $("table.fixed_header[rel="+tind+"]").length == 0 ) {
					posleft = ftbind.offset().left - 1;

					// build an html, go through each columns of that table
					thtml = '<table class="fixed_header" rel="'+tind+'" style="width: '+(ftbind.width()+4)+'px; top:'+ftop+'px; left: '+posleft+'px;"><tbody><tr>';
					ftbind.find("thead:eq(0) tr th:not(.no_display)").each(function(){
						if ( $(this).attr("class") != undefined && $(this).attr("class") != 'ui-datepicker-week-end' ) thtml += '<td class="'+$(this).attr("class")+'" style="width: '+$(this).width()+'px">'+$(this).html()+'</td>';
					});
					thtml += '</tr></tbody></table><div class="fhwrap" rel="'+tind+'"></div>';
					ftbind.parents("div.table_parent").append(thtml);
				}

				// if the table is wider than the page, adjust the left position of the floating header
				if ( $("table.body_table").width() > $("div.wrapper").width() ) {
					$('table.fixed_header[rel='+tind+']').css("left", ftbind.offset().left - $(window).scrollLeft());
				}

			} else {
				$('table.fixed_header[rel='+tind+']').remove();
				$('div.fhwrap[rel='+tind+']').remove();
			}
		});

	});

	// if there are any feedback popups start removing the feedback elements
	if ($("body:not(.prompt) div.page_feedback:not(.perm) div.g_info").length) {
		gRemove();
	}

	// if there are any sections that should be collapsed on page load
	// collapse them here
	if ( $("div.coll").length > 0 ) {
		$("div.coll").each(function(){
			$(this).nextUntil("div.section_title").slideUp("fast");
		});
	}

	// attach tablesorter plugin to all tables with 'tablesorter' class
	// uses: jquery.tablesorter.js
	$('table.tablesorter').each(function() {
		$(this).tablesorter();
	});

	// various keyboard triggers
	$('body').on("keydown" , function(e){

		// F1 - show help panel
		if ( $("a.hlptrg").length && e.keyCode == "112" ) {
			 $("a.hlptrg").trigger("click");
			 e.preventDefault();

		// ESC - if the popup is opened
		// uses: jquery.jfull.js
		} else if ( ( $("#jbox_main").length || $("#jbox_image").length || $("*[data-app-switcher]").length || $("body").hasClass("jfulled")) && e.keyCode == "27") {

			// if text area was enlarged, shrink it
			if ( $("body").hasClass("jfulled") ) {
				enlargeField();

			// just close the delete confirmation popups
			// and if there were changes, do not close the popup
			} else {
				if ( $("#jbox_main div.actions input.delete").length ) jBoxClose(true);
				else jBoxClose(!$("#jbox_main").hasClass("changed"));

			}
			e.preventDefault();
		}

	// strip tags when pasted into the textarea without 'keeptags' class
	// uses: jquery.functions.js
    }).on("keyup", function(e){
		setTimeout(function(){
			if ( $(e.target).is("textarea:not(.keeptags)") && e.keyCode == 86 && e.ctrlKey == true) {
				$(e.target).val( strip_tags($(e.target).val()));
			}
		}, 500);

	});

	// check for last one column filter
	$("div.dropdown_view ul.dropdown_columns").each(function(){
		if ( $(this).find('input:checked').length == 1 ) $(this).find('input:checked').attr("disabled", true);
	});

	// side treeview plugin
	// uses: jquery.treeview.js
	$(".treeview ul.apps").treeview({
		collapsed: true,
		unique: true,
		persist: "cookie",
		animated: "fast",
		toggle: function (args) {
			cookieId = "treeview";
			data = $.cookie(cookieId);
			$.cookie(cookieId, null);
			$.cookie(cookieId, data, { path: "/" });
		 }
	});

	// bind resize function
	$(window).bind('resize', resizeWindow);

	// trigger page resize
	resizeWindow();

	// bind all other functions
	refreshJS();

});
// end

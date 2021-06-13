// jbox size
var box_width = 925;
var gallery;
var captions;
var current_image;

$(function() {

	$.fn.jbox = function() {

		$(this).off("click").on("click.jbox", function(e){
			e.preventDefault();
			el = $(this);
			jBoxClose(true);

			// which size?
			ismini = false;
			if ( typeof(el.data("jbox-width")) === "undefined" || el.data("jbox-width") == "") {
				el.data("jbox-width", false);
				
				if (POPUP_SIZE == "Dynamic" && $.cookies.get("jboxsize") == "full") ismini = false;
				else if (POPUP_SIZE == "Dynamic" && $.cookies.get("jboxsize") == "box") ismini = true;
				else if (POPUP_SIZE == "Dynamic" && el.hasClass("jmini") ) ismini = true;
				else if (POPUP_SIZE == "Always compact") ismini = true;
				else if (POPUP_SIZE == "Always maximised") ismini = false;
				else ismini = true;

			}
			
			// resize the body elements
			jBoxResizeBody();

			// can the jBox be closed by clicking on the overlay
			el.data("close-overlay", ( typeof( el.data("close-overlay")) === "undefined") ? false : el.data("close-overlay") );

			// append overlay and jbox container, only if jbox is not opened already
			if ( !$('body').hasClass("jboxed")) {
				$('html').addClass("hbox");
				$('body').addClass("jboxed").append('<div class="jbox_overlay" data-close-overlay="' + el.data("close-overlay") + '" style="width:'+($(window).width() + 50)+'px; height:'+($(window).height() - 100)+'px;">\
					<div id="jbox_main" data-custom-width="'+ el.data("jbox-width") +'" class="jbox_main jbox_loading '+ ( el.hasClass("ecbox") ? 'ecbox' : '') +'">\
						<i class="fa fa-spinner fa-pulse"></i>\
					</div>\
				</div>');
			}

			// jbox can take its content either from URL, or from any other element with ID on the same page
			if ( !el.hasClass("jbox_modal")) {

				// load the content
				$("#jbox_main").load( el.attr("href"), { "page" : el.attr("href") }, function(response, status, xhr){
					if (status == "success") {
						if (ismini) {
							jBoxResize(box_width);
							$("#jbox_main").removeClass("jbox_full").addClass("jbox_box");
						} else if ( el.data("jbox-width") ) {
							jBoxResize( el.data("jbox-width") );
							$("#jbox_main").removeClass("jbox_box").removeClass("jbox_full").addClass("jbox_custom_width");
						} else {
							jBoxResize($(window).width());
							$("#jbox_main").removeClass("jbox_box").addClass("jbox_full");
						}
						$("body").removeClass("block");
						if (typeof refreshJS == 'function') refreshJS(); // and refresh a jBox js content

					} else {
						$("#jbox_main").load( SYSTEM_ROOT + "404_popup.php", { "page" : SYSTEM_ROOT + "404_popup.php" }, function(){
							jBoxResize(box_width);
							$("#jbox_main").removeClass("jbox_full").addClass("jbox_box");
							if (typeof refreshJS == 'function') refreshJS(); // and refresh a jBox js content
						});

					}
				});

			// coming from the element with ID
			} else {

				$("#jbox_main").html('<div data-jbox="header" class="jbox_header clearfix" style="display: block;">\
					<div data-jbox="title" class="title">' + el.attr("title") + '</div>\
					<div data-jbox="close" class="close">\
						<i class="fa fa-times"></i>\
					</div>\
					<div data-jbox="restore" class="restore">\
						<i class="fa fa-square-o"></i>\
						<i class="fa fa-minus"></i>\
					</div>\
				</div>\
				<div data-jbox="content" class="jbox_content" style="display: block;">\
					<div class="jbox_content_inner jbox_allow_horisontal" style="display: block;">'
						+ $(el.attr("href")).html() + '\
						<div class="actions" style="display:block;">\
							<input data-cancel="true" type="button" value="Close" class="btn right">\
						</div>\
					</div>\
				</div>');
				
				if (ismini) {
					jBoxResize(box_width);
					$("#jbox_main").removeClass("jbox_full").addClass("jbox_box");
				} else {
					jBoxResize($(window).width());
					$("#jbox_main").removeClass("jbox_box").addClass("jbox_full");
				}
				$("body").removeClass("block");
				if (typeof refreshJS == 'function') refreshJS(); // and refresh a jBox js content
			}

			// actions
			$('html:not(.ipad) #jbox_main').draggable({
				handle: '*[data-jbox="header"]',
				stop: function(e, ui){
					if ( $(this).offset().top < 0 ) $(this).animate({"top" : "0px", 'margin-top' : "0px"}, 100);
					if ( $(this).offset().left < 0 ) $(this).animate({"left" : "0px", 'margin-left' : "0px"}, 100);
				}
			});
			
			return false;
		});
	}

	// jBoxImage plugin start
	$.fn.jbox_img = function(){

		$(this).off("click").on("click", function(e){
			e.preventDefault();
			el = $(this);

			// check if this is a gallery
			if ( $("a.jbox_img").length > 1 ) {
				gallery = new Array();
				captions = new Array();
				current_image = $('a.jbox_img').index(this);
				$("a.jbox_img").each(function() {
					gallery.push( $(this).attr("href") );
					if ( $(this).attr("title") != "" ) captions.push( $(this).attr("title") );
					else captions.push("");
				});
				
				// resize the body elements
				jBoxResizeBody();
			
				$('html').addClass("hbox");
				$('body').addClass("jboxed").append('<div class="jbox_overlay" data-close-overlay="true" style="width:'+($(window).width() + 50)+'px; height:'+($(window).height() - 100)+'px;">&nbsp;</div>\
				<div id="jbox_image" class="jbox_image jbox_loading">\
					<span class="img">\
						<i class="fa fa-spinner fa-pulse"></i>\
					</span>\
					<a data-jbox="open_external" class="ext tooltip" title="Open image in new window">\
						<i class="fa fa-external-link"></i>\
					</a>\
					<span data-jbox="close" class="close">\
						<i class="fa fa-times"></i>\
					</span>\
					<a data-jbox="previous_image"  class="jimg_control jimg_prev" href="#">\
						<span class="ico">\
							<i class="fa fa-angle-left"></i>\
						</span>\
					</a>\
					<a data-jbox="next_image" class="jimg_control jimg_next" href="#">\
						<span class="ico">\
							<i class="fa fa-angle-right"></i>\
						</span>\
					</a>\
					<span class="caption"></span>\
				</div>');

				if (current_image == 0) $("#jbox_image span.jimg_prev").css("display","none");
				else if (current_image == ($("div.jbox_img").length-1)) $("#jbox_image span.jimg_next").css("display","none");
				loadjBoxImage( gallery[current_image] , captions[current_image]);

				// change size of overlay on resize
				$(window).bind('resize', function() {
					if ( $(window).width() >= 1024) new_width = $(window).width();
					else new_width = $(document).width();
					loadjBoxImage(gallery[current_image], captions[current_image]);
				});
				
			} else {
			
				// resize the body elements
				jBoxResizeBody();
			
				current_image = el.attr('href');
				$('html').addClass("hbox");
				$('body').addClass("jboxed").append('<div class="jbox_overlay" data-close-overlay="true" style="width:'+($(document).width()  + 50)+'px; height:'+($(document).height() - 100)+'px;">&nbsp;</div>\
				<div id="jbox_image" class="jbox_image">\
					<span class="img">&nbsp;</span>\
					<a class="ext tooltip" title="Open image in new window" href="'+current_image+'" target="_blank">\
						<i class="fa fa-external-link"></i>\
					</a>\
					<span data-jbox="close" class="close">\
						<i class="fa fa-times"></i>\
					</span>\
					<span class="caption">&nbsp;</span>\
				</div>');

				loadjBoxImage( current_image , el.attr('title') );
				
				// change size of overlay on resize
				$(window).bind('resize', function(){
					if ( $(window).width() >= 1024) new_width = $(window).width();
					else new_width = $(document).width();
					$('div.jbox_overlay').css({"width" : $(window).width() , "height" : $(window).height() - 100});
					loadjBoxImage( current_image , el.attr('title') );
				});
			}
			
			return false;
		});
	
	}

	// set the 'changed' data attribute to true
	// this data attribute is used for a check if the jbox can be closed without the prompt or not
	$(document).on("change", "#jbox_main div.form_content input, #jbox_main div.form_content select, #jbox_main div.form_content textarea", function(){
		$("#jbox_main").data("changed", true);

	// set the 'changed' data attribute to true
	// this data attribute is used for a check if the jbox can be closed without the prompt or not
	// on keydown event, also remove the prompt
	}).on("keydown", "#jbox_main div.form_content input, #jbox_main div.form_content select, #jbox_main div.form_content textarea", function(){
		$("#jbox_main").data("changed", true);
		$("#jbox_main.prompted").removeClass("prompted");

	// jbox close on overlay
	}).on("click", "*[data-close-overlay]", function(){
		if ( $(this).data("close-overlay") ) jBoxClose(!$("#jbox_main").data("changed"));

	// cancel the warning prompt (Yes)
	}).on("click", "*[data-prompt-cancel]", function(){
		jBoxClose(true);

	// cancel the warning prompt (No)
	}).on("click", "*[data-prompt-back]", function(){
		$("#jbox_main").removeClass("prompted");
		
	// double click header
	}).on("dblclick", "*[data-jbox='header']", function(){
		if ( $("*[data-custom-width='false']").length ) $("*[data-jbox='restore']").trigger("click");
	
	// on max btn click
	}).on("click", "div.jbox_box *[data-jbox='restore']", function(){
		if ( $("*[data-custom-width='false']").length ) {
			jBoxResize($(window).width());
			$('div.jbox_box').addClass("jbox_full").removeClass("jbox_box");
			$.cookies.set("jboxsize", "full");
		}
		
	// on restore btn click
	}).on("click", "div.jbox_full *[data-jbox='restore']", function(){
		if ( $("*[data-custom-width='false']").length ) {
			jBoxResize(box_width);
			$('div.jbox_full').addClass("jbox_box").removeClass("jbox_full");
			$.cookies.set("jboxsize", "box");
		}
		
	// on close btn click
	}).on("click", "*[data-jbox='close']", function(){
		jBoxClose(!$("#jbox_main").data("changed")); 
	
	// on cancel btn click
	}).on("click", "*[data-cancel]", function(){
		jBoxClose(!$("#jbox_main").data("changed"));
		
	// overlay click
	}).on("click", "div.jbox_overlay", function(e){
		if ( $(e.target).parents(".ui-autocomplete").length == 0) $("#jbox_main:not(.jbox_loading)").animate({"background-color": "#DC0000"}, 100).animate({"background-color": "#666"}, 200);
		
	// overlay click
	}).on("click", "#jbox_main", function(e){
		e.stopPropagation();
		
	// self submit
	}).on("click", ".this_submit", function(e){
    	e.preventDefault();
		if ($(this).closest("form").validate().form() == true){
			self_submit($(this), $(this).parents("form").attr("action"));
		}
    
	// on ext image click
	}).on("click", "#jbox_image *[data-jbox='close']", function(){
		jBoxClose(true);
		
	// on ext image click
	}).on("click", "#jbox_image *[data-jbox='open_external']", function(){
		if (typeof gallery[current_image] != undefined) window.open(gallery[current_image]);
	
	// on next image click
	}).on("click", "#jbox_image *[data-jbox='next_image']", function(e){
		e.preventDefault();
		el = $(this);
		
		if ( !el.hasClass("disabled")) {
			current_image++;
			loadjBoxImage(gallery[current_image], captions[current_image]);
			if ( gallery[current_image+1] == undefined ) el.addClass("disabled");
			$('#jbox_image a.jimg_prev').removeClass("disabled");
		}

	// on prev image click
	}).on("click", "#jbox_image *[data-jbox='previous_image']", function(e){
		e.preventDefault();
		el = $(this);

		if ( !el.hasClass("disabled")) {
			current_image--;
			loadjBoxImage(gallery[current_image], captions[current_image]);
			if ( gallery[current_image-1] == undefined ) el.addClass("disabled");
			$('#jbox_image a.jimg_next').removeClass("disabled");
		}

	// tabbed forms
	}).on("click", "div.jbox_tabs a.jtab", function(e){
		e.preventDefault();
		pos = $(this).index();
		fwd = $("#jbox_main div.jbox_content form").width() + 50;
		$("#jbox_main div.form_content:eq("+pos+")").addClass("jtab_visible");
		
		// jtabs refresh function (trigger every time, even on form load)
		$("#jbox_main div.jbox_content form").stop().animate({"margin-left":"-"+(pos * fwd)+"px"}, "fast", function(){

			// remove the previous visible panel
			$("#jbox_main div.form_content").not(":eq("+(pos)+")").removeClass("jtab_visible");
			
			// enable or disable left and right controls
			if (pos == 0) {
				$("#jbox_main a.jtab_prev").addClass("disabled");
				$("#jbox_main a.jtab_next").removeClass("disabled");
				
			} else if ( pos == $("#jbox_main div.jbox_tabs a.jtab").length - 1) {
				$("#jbox_main a.jtab_prev").removeClass("disabled");
				$("#jbox_main a.jtab_next").addClass("disabled");
				
			} else {
				$("#jbox_main a.jtab_prev").removeClass("disabled");
				$("#jbox_main a.jtab_next").removeClass("disabled");
			
			}
			
		});
				
		$("#jbox_main div.jbox_tabs a.active").removeClass("active");
		$(this).addClass("active");
		
	}).on("click", "div.jbox_main *[data-jbox='previous_tab']", function(e){
		e.preventDefault();
		$("div.jbox_tabs a.active").prev("a.jtab").trigger("click");
	
	}).on("click", "div.jbox_main *[data-jbox='next_tab']", function(e){
		e.preventDefault();
		$("div.jbox_tabs a.active").next("a.jtab").trigger("click");
		
	}).on("click", "*[data-jbox='remove_section']", function(e){
		$(this).parents("table.action_form").fadeOut(function(){
			$(this).remove();
		});
	});
    // actions end
	
	// change size of overlay on resize
	$(window).on('resize', function(){
		$('div.jbox_overlay').css({ "width" : $(window).width() , "height" : $(window).height() - 100 });
		
		// resize jbox with min values
		if ( $("*[data-custom-width='false']").length && $("div.jbox_box").length == 0 ){
			if ( $(window).width() > 1145 && $(window).height() > 350 ) jBoxResize( $(window).width());
			else if ( $(window).width() > 1145 ) jBoxResize( $(window).width() );
			else if ( $(window).height() > 350 ) jBoxResize( 1145 );
		}
	});

	// was a jBox popup appended by feedback form?
	if ( $('div[data-jbox="load"]').length ) {
		console.log("has jbox");
		jBoxCreate();
	}
	
});

function jBoxResize(width) {
	jbox = $('#jbox_main:not(.jbox_app_switch)');

	// hide the tinymce so we can reset the width
	($("#jbox_main table.mceLayout").length) ? $("#jbox_main table.mceLayout").css("display", "none") : "";
	
	// format all other dimensions
	jbox.stop().animate({'left' : '50%', 'top' : '100px', 'margin-left' : '-' + (width-100) / 2 + 'px', 'width': (width-100) + 'px' }, 0, function(){
		jbox.find("div.jbox_content").css("display","block");
		jbox.find("div.jbox_content_inner").css("display" , "block");
		jbox.find("div.jbox_header").css("display" , "block");
		jbox.find("div.actions, div.prompt").css("width", (width-122) + "px");
		if ( width > 1000 ) jbox.find("div.form_content table.action_form").css( "width" , "1000px" );
		else jbox.find("div.form_content table.action_form").css( "width" , "100%" );
		jbox.find("input:text:not(.dropdate):not(.dropdate)").first().focus();
		$("div.jbox_overlay").scrollTop($("div.jbox_overlay form input[name=form_scroll]").val());

		// resize and show tinymce
		if  (jbox.find("table.mceLayout").length) {
			jbox.find("table.mceLayout").css("display", "none");
			jbox.find("table.mceLayout").width( jbox.find("table.mceLayout").parents("td").width() );
			jbox.find("table.mceLayout iframe").width( jbox.find("table.mceLayout").parents("td").width()  - 20);
			jbox.find("table.mceLayout").css("display", "table");
		}

		// refresh tabs on resize
		if ( jbox.find("div.jbox_tabs").length ){
			if ($.cookies.get("jtab")) jbox.find(".jbox_tabs a:eq("+$.cookies.get("jtab")+")").trigger("click");
			else jbox.find(".jbox_tabs a.active").trigger("click");
			$.cookies.set("jtab", false);
		}

	}).removeClass("jbox_loading");
	
	// adjust the overlay dimensions
	$('div.jbox_overlay').css({"width" : $(window).width() , "height" : $(window).height() - 100});
	
}

function jBoxResizeBody(){
	$("div.wrapper a.more").css("margin-right", $.scrollbarWidth());
	$("div.body_section table.body_table td.content_cell").css("padding-right", $.scrollbarWidth());
	$("*[data-applications-menu]").css("padding-right", $.scrollbarWidth());
}

function jBoxResetBody(){
	$("div.wrapper a.more").css("margin-right", "0px");
	$("div.body_section table.body_table td.content_cell").css("padding-right", "0px");
	$("*[data-applications-menu]").css("padding-right", "0px");
}
		
function jBoxClose(final_close){
	
	if ( $("#jbox_main").hasClass("ecbox") || $(".jbox_header").hasClass("ecbox") ) final_close = true;
	
	// close any additional popups if opened
	$("#ui-datepicker-div").hide();
	$("div.jbox_mini").remove();
	
	// close only in case this is not a stacked form
	if ( $("#jbox_main div.jbox_inner_controls a.bck").length != 0) {
		$("#jbox_main div.jbox_inner_controls a.bck:eq(0)").trigger("click");
	
	// close without any questions
	} else if (!final_close) {
		$("#jbox_main").addClass("prompted").animate({"background-color": "#DC0000"}, 100).animate({"background-color": "#666"}, 200);
	
	// show are you sure confirmation prompt
	} else {
		$('div.jbox_overlay').remove();
		$('#jbox_main, #app_switch').remove();
		$('#jbox_image').remove();
		$("body").removeClass("jboxed");
		$('html').removeClass("hbox");
		
		// debug overlays
		$("div.dbg_info").css("display", "none");
		$("#dbg_toolbar a.active").removeClass("active");
		
		// resize the body elements
		jBoxResetBody();
		
	}
}

function loadjBoxImage(image, caption) {
	$('#jbox_image *[data-jbox="caption"]').css({"height" : "0px", "padding-top":"0px"});
	
	var objImagePreloader = new Image();
	objImagePreloader.onload = function() {
		w_image_ratio = objImagePreloader.width / objImagePreloader.height;
		h_image_ratio = objImagePreloader.height / objImagePreloader.width;

		if ( (objImagePreloader.width > $(window).width() - 100) || (objImagePreloader.height > $(window).height() - 100)) {
			toWidth = $(window).width() - 100;
			toHeight = toWidth / w_image_ratio;
			if (toHeight > $(window).height() - 100 ) {
				toHeight = $(window).height() - 100;
				toWidth = toHeight / h_image_ratio;
			}
			small_image = false;
		} else if (objImagePreloader.width < 200){
			toWidth = "300";
			toHeight = "300";
			small_image = true;
		} else {
			toWidth = objImagePreloader.width;
			toHeight = objImagePreloader.height;
			small_image = false;
		}

		$('#jbox_image').stop().animate({ 'margin-left' : '-' + toWidth / 2 + 'px', 'margin-top' : '-' + toHeight / 2 + 'px', 'width': toWidth + 'px', 'height': toHeight + 'px'  }, "fast", function(){
			mtop = parseInt((300 - objImagePreloader.height) / 2) + "px";
			if (small_image) $('#jbox_image span.img img').fadeTo("1000" , "1").css({ "width" : "auto", "margin-top" : mtop });
			else $('#jbox_image span.img img').fadeTo("1000" , "1");
			if (caption) $('#jbox_image *[data-jbox="caption"]').stop().animate({ "height" : "30px", "padding-top":"10px"}, 200);
			$('#jbox_image').css('background-image', 'none');
			$('.jbox_image').removeClass('jbox_loading');
			$('#jbox_image *[data-jbox="close"], #jbox_image *[data-jbox="open_external"], #jbox_image *[data-jbox="previous_image"], #jbox_image *[data-jbox="next_image"]').css('display', 'block');
		});
		
		$('#jbox_image span.img').html(objImagePreloader);
		$('#jbox_image *[data-jbox="caption"]').html(caption);
	};
	
	objImagePreloader.src = image;
	
}

function jBoxMini(page, width, height, left, top){
	$('body').append('<div class="jbox_mini" style="left:'+left+'px; top:'+top+'px; width:'+width+'px; height:'+height+'px; ">\
		<span data-jbox="mini_close" class="close">\
			<i class="fa fa-times"></i>\
		</span>\
		<div class="jbox_mini_wrap">&nbsp;</div>\
	</div>');
	
	// load the content
	$("div.jbox_mini_wrap").load( page, function(){
		$('div.jbox_mini div.jbox_content').css({"height": (height - 10) + 'px', "display" : "block"}, 0);
	});
	
	$(document).on("click", ".jbox_mini *[data-jbox='mini_close']", function(){ 
		$('.jbox_mini').remove();
	});
	
}

function jBoxCreate(){
	// resize the body elements
	$("div.wrapper a.moreapps").css("margin-right", $.scrollbarWidth());
	$("div.body_section table.body_table td.content_cell").css("padding-right", $.scrollbarWidth());
		
	// append overlay and jbox container
	$('html').addClass("hbox");
	$('body').addClass("jboxed");
	$('div.jbox_overlay').width($(window).width());
	
	$('html:not(.ipad) #jbox_main').draggable({
		handle: '*[data-jbox="header"]',
		stop: function(){
			if ( $(this).offset().top < 0 ) $(this).stop().animate({"top" : "0px", 'margin-top' : "0px"}, 100);
			if ( $(this).offset().left < 0 ) $(this).stop().animate({"left" : "0px", 'margin-left' : "0px"}, 100);
		}
	});

	// which size?
	if (POPUP_SIZE == "Dynamic" && $.cookies.get("jboxsize") == "full") ismini = false;
	else if (POPUP_SIZE == "Dynamic" && $.cookies.get("jboxsize") == "box") ismini = true;
	else if (POPUP_SIZE == "Dynamic" && $(this).hasClass("jmini") ) ismini = true;
	else if (POPUP_SIZE == "Always compact") ismini = true;
	else if (POPUP_SIZE == "Always maximised") ismini = false;
	else ismini = true;

	if (ismini) {
		jBoxResize(box_width);
		$("#jbox_main").removeClass("jbox_full").addClass("jbox_box");
	} else {
		jBoxResize($(window).width());
		$("#jbox_main").removeClass("jbox_box").addClass("jbox_full");
	}


	if (typeof refreshJS == 'function') refreshJS(); // and refresh a jBox js content
	
	// was jbox hidden, when appended to the page
	if ( $("#jbox_main").hasClass("jbox_hidden")) $("div.jbox_hidden").removeClass("jbox_hidden");
	
}
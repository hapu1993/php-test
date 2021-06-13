$(function() {

	if ( $("#dbg_toolbar").length != 0 ) {

		$("#dbg_toolbar_trigger").on("click", function() {

			debug_top = $.cookies.get("dbg_toolbar_top") ? $.cookies.get("dbg_toolbar_top") : '100%';
			debug_left = $.cookies.get("dbg_toolbar_left") ? $.cookies.get("dbg_toolbar_left") : '7px';

			$("#dbg_toolbar").css({ "display" : "block", "left" : debug_left, "top" : debug_top });

			if ($.cookies.get("dbg_toolbar_type") == "dbg_horizontal") {
				$("#dbg_toolbar").addClass("dbg_horizontal");
				$.cookies.set("dbg_toolbar_type", "dbg_horizontal");

			} else if ($.cookies.get("dbg_toolbar_type") == "dbg_vertical") {
				$("#dbg_toolbar").addClass("dbg_vertical");
				$.cookies.set("dbg_toolbar_type", "dbg_vertical");

			}

			$.cookies.set("dbg_toolbar", "block");
			$(this).animate({"left" : "-30px"});
		});

		$("#dbg_toolbar span.dbg_close").on("click", function(){
			$("#dbg_toolbar").css("display", "none");
			$.cookies.set("dbg_toolbar", "none");
			$("#dbg_toolbar_trigger").animate({"left" : "0px"});
		});
		
		$("#dbg_toolbar").draggable({
			handle : 'div.dbg_head',
			stop: function(ui, pos) {
				$.cookies.set("dbg_toolbar_top", $("#dbg_toolbar").css("top"));
				$.cookies.set("dbg_toolbar_left", $("#dbg_toolbar").css("left"));
			}
		});

		$("#dbg_toolbar span.dbg_type").on("click", function(){
			if ( $("#dbg_toolbar").hasClass("dbg_vertical")) {
				$("#dbg_toolbar").addClass("dbg_horizontal").removeClass("dbg_vertical");
				$.cookies.set("dbg_toolbar_type", "dbg_horizontal");
			} else {
				$("#dbg_toolbar").addClass("dbg_vertical").removeClass("dbg_horizontal");
				$.cookies.set("dbg_toolbar_type", "dbg_vertical");
			}
		});

		$("#dbg_toolbar div.dbg_head").on("dblclick", function(){
			if ( $("#dbg_toolbar").hasClass("dbg_vertical")) {
				$("#dbg_toolbar").addClass("dbg_horizontal").removeClass("dbg_vertical");
				$.cookies.set("dbg_toolbar_type", "dbg_horizontal");
			} else {
				$("#dbg_toolbar").addClass("dbg_vertical").removeClass("dbg_horizontal");
				$.cookies.set("dbg_toolbar_type", "dbg_vertical");
			}
		});

	}

})
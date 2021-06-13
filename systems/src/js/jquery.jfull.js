$(function() {

	$.fn.jFull = function() {
		
		this.each(function (i, elem) {
			span = $('<span class="jftrg"><i class="fa fa-expand"></i><i class="fa fa-compress"></i></span>');
			span.insertBefore($(elem));
			$(elem).off("dblclick").on("dblclick", function(e){ enlargeField($(elem)); });
			span.off("click").on("click", function(e){ enlargeField($(elem)); });
        });

	}
		
});

function enlargeField(field){
	if ( $('body').hasClass("jfulled") || field == undefined ) {
		$('body').removeClass("jfulled");
		$("div.jfull_overlay").remove();
		$(".acfull").removeClass("acfull");

	} else {
		$('body').addClass("jfulled");
		$(field).addClass("acfull");
		$('body').addClass("jfulled");
		$("div.wrapper").prepend('<div class="jfull_overlay" style="width:'+($(window).width() + 50)+'px; height:'+($(window).height() - 100)+'px;"></div>');

	}
}
// self submit global function
// element is used to identify which form should be submitted
// action is changed depending on the target the form should be submitted to
self_submit = function(element, action){

	// show 'Please wait...' feedback
	blockPage("Please wait...");

	// if this is self submit in a jBox
	if ( element.parents("#jbox_main").length ) {

		// was it triggered inside the tabbed forms?
		// if so, remember which tab was active / submitted
		// if so, remember which tab was active / submitted
		if ( element.parents("#jbox_main").find("div.jbox_tabs").length ) {
			stab = element.parents("#jbox_main").find("div.jbox_tabs a.active").index();
			$.cookies.set("jtab", stab);
		}

		// self submit inside the tabbed form's page
		if ( element.parents("#jbox_main").find('div.jbox_inner_controls *[data-jbox-jlink="back"]').length ) {
			$("#jbox_main form").prepend('<input type="hidden" name="page" value="'+action+'" />');
		}

		// save the current scroll position so it can be restored after the submit
		element.parents("form").find("input[name=form_scroll]").val( $(".jbox_overlay").scrollTop() );

		// post the data, but as we are not sending jbox headers again append 'jbox_opened' parameter
		$.post(action, element.parents("form").serialize() + "&self_submit=true&jbox_opened=true",
			function(data){
				check_access(data);

				$("#jbox_main *[data-jbox='content']").html(data);

				// again, as we are not reshowing jbox, trigger resize and refresh functions
				jBoxResize(($('#jbox_main').width()+100), ($('#jbox_main').height()+100));
				if (typeof refreshJS == 'function') refreshJS();

				// unblock the page, hide the 'Please wait...' feedback
				showFeedback("", "", "", 1, true);

				if ( $.cookies.get("gFeedback")) {
					$("body").append($.cookies.get("gFeedback"));
					$('div.page_feedback:not(.perm) div.g_feedback').delay(500).fadeOut(function(){
						$(this).remove();
					});

					// destroy the feedback cookie
					$.cookies.set("gFeedback", null);
				}
			}
		);

	// self submit was triggered on the form not in the popup
	} else {

		// set the 'has changed' flag to 1
		if (element.parents("form").find("input[name=haschng]").length) element.parents("form").find("input[name=haschng]").val("1");

		// remember the scroll position
		element.parents("form").find("input[name=form_scroll]").val( $("body").scrollTop() );

		// set the 'self_submit' variable
		element.parents("form").find("input[name=self_submit]").val(1);

		// remove posted actions so we dont trigger execute post function
		element.parents("form").find("input[name=posted_actions]").remove();

		// do not validate the form
		element.parents("form").validate().cancelSubmit = true;

		// submit the form
		element.parents("form").submit();
	}
}

// used to resize various page elements
function resizeWindow() {

	// if the ownCloud iframe exists, resize the iframe to match the 'body_section' dimensions
	if ( $("iframe.owncloud").length ) {
		$("iframe.owncloud").height($(window).height() - $("div.body_section").offset().top - $("div.menu_wrap").height() - 35);
	}

	// match the body height, so that side panel covers the full screen height
	$("div.content_section").css({ "min-height" : ($(window).height() - $("div.body_section").offset().top ) + "px"});

	// move the autocomplete dropdown list
	if ( $("div.uia-wrap").length && $("input.ui-autocomplete-input").length ) {
		if ( $("div.jbox_main input.ui-autocomplete-input").length ) $("div.uia-wrap").css("left", $("div.jbox_main input.ui-autocomplete-input").offset().left );
		else if ($("input.ui-autocomplete-input").length) $("div.uia-wrap").css("left", $("input.ui-autocomplete-input").offset().left );
	}

	// remove the floated headers elements
	$('table.fixed_header').remove();
	$('div.fhwrap').remove();
	return false;
};

// append more table rows trigg either on click or on page scroll
function appendMore(elem, starting_pos){

	// prevents the function to be triggered over and over at the same time
	window.block_load = true;

	// check if the table has table wrapper and pagination
	if (elem.parents("div.table_wrap").length > 0) {
		tableid = elem.parents("div.table_wrap").attr("id").replace("table_", "");
		next_page = elem.parents("div.table_wrap").find("div.paginator div.pages span.wrap a b:contains("+window["starting_pos_"+tableid]+")").parent("a");

		// create a temp table
		$("body").append("<div class='temp_table'></div>");
		elem.parents("tr.add_more").addClass("loading");
		elem.html('<div class="prgrs"><i class="fa fa-spin fa-refresh"></i></div>');

		// $_POSTed "add_more_action" is not used, here is just in case it is needed sometime
		$("div.temp_table").load( next_page.attr("href") + " #" + elem.parents("div.table_wrap").attr("id"), { "add_more_action" : true }, function(data){
			check_access(data);

			// adjust the style, append new rows, and also remove the previous 'add_more' row
			elem.parents("tr.add_more").removeClass("loading");
			elem.html("View more");
			elem.parents("tr.add_more").before("<tr class='p_section'><td colspan='100%'>Page " + next_page.html()+"</td></tr>" + $("div.temp_table table.list_table tbody").html() );
			elem.parents("table.list_table tr.add_more").last().remove();
			$("div.temp_table").remove();
			if (typeof refreshJS == 'function') refreshJS();
			window.block_load = false;
		});
	}
}

/* the next function has to contain all plugins that can be used in popups, or any other dynamically appended content, that does not exist on a page load */
function refreshJS(){

	// attach jbox plugin, jbox images plugin, jfull plugin (enlarge textareas)
	// uses: jquery.jbox.js, jquery.jfull.js
	$('a.jbox').jbox();
	$('a.jbox_img').jbox_img();
	if ( $('.jFull').length ) $('.jFull').jFull();

	// attach file uploader plugin to each file upload elements
	// uses: jquery.fileupload.js, jquery.fileupload-templates.js, jquery.fileupload-process.js, jquery.fileupload-ui.js
	$("div.row_fileupload").each(function(){
		ful = $(this);

		// set the default parameters, most of them coming from the file upload elements
		$(this).fileupload({
			url: SYSTEM_ROOT+ "ajax/ajax_file_upload.php",
			maxNumberOfFiles: $(ful).data("num"),
			singleFileUploads: (!$(ful).data("multi_file")),
			autoUpload: ($(ful).data("auto_upload")),
			formData : [
				{ name: "folder", value: $(ful).data("folder") },
				{ name: "form_name", value: $(ful).data("form_name") },
				{ name: "accepted_ft" , value: $(ful).data("accepted_ft") },
				{ name: "max_fs" , value: $(ful).data("max_fs") },
				{ name: "keep_file" , value: $(ful).data("keep_file") },
				{ name: "secure_file" , value: $(ful).data("secure_file") },
				{ name: "image" , value: $(ful).data("image") }
			]

		// on file upload start update the style
		}).bind('fileuploadstart', function (e, data) {
			ful = $(e.currentTarget);
			$(ful).find("td.file_upload").addClass("uploaded_showbtn");

			// if this was a single file upload, hide the buttons and also prevent another file to be uploaded
			if ( $(ful).data("hide_button") ) {
				$(ful).find("td.file_upload").addClass("uploaded");
				$(ful).find("input.file").attr("disabled", true);

			// clear the 'uploaded_file' hidden input element
			} else if (!$(ful).data("multi_file")) {
				$(ful).find("table.files tr.template-download").remove();
				$(ful).find("input.uploaded_file").val("");
			}

			// set the global variable which disables form's Submit button
			sbtndis = true;

		}).bind('fileuploaddone', function (e, data) {

			// unblock the page, hide the 'Please wait...' feedback
			showFeedback("", "", "", 1, true);

			// multiple files are stored in array
			if ( $(ful).data("multi_file")) {

				// reset the array
				window.all_files = new Array;

				// get all previously uploaded files
				prev_files = $(ful).find("input.uploaded_file").val();
				if (prev_files != "") {
					$.each(JSON.parse(prev_files), function (index, file) {
						window.all_files.push(file);
					});
				}

				// put all newly uploaded files to the same array
				$.each(data.result.files, function (index, file) {
					window.all_files.push(file.filepath);
				});

				// write the all new array into the hidden input field
				$(ful).find("input.uploaded_file").val(JSON.stringify(window.all_files));

			// normal, single file upload
			} else {
				$.each(data.result.files, function (index, file) {
					// construct the full file name and put it into the hidden input field
					$(ful).find("input.uploaded_file").val(file.filepath);

					// remove the lib file upload value
					$(ful).parents("tr").find("input.lib_file").val("");

					// remove the selected class from lib selection
					$(ful).parents("tr").find("div.img_holder a.selected").removeClass("selected");
				});
			}

			// if there was any validator error, remove it
			if ( $(ful).find("input.uploaded_file").val()) $(ful).find("label.error").remove();

			// enable global Submit variable
			sbtndis = false;
		});

		// the next section is executed on edit forms - attach previously uploaded files to same file upload element
		// but only if there is a value already in the hidden field
		if ( $(this).find("input.uploaded_file").val() != "") {
			fkp = ($(this).data("keep_file")) ? "&keep_file=true" : "";
			mlt = ($(this).data("multi_file")) ? "&multi_file=true" : "";

			// load existing files:
			$.ajax({
				url: SYSTEM_ROOT+ "ajax/ajax_file_upload.php?file="+$(this).find("input.uploaded_file").val()+fkp+mlt,
				dataType: 'json',
				context: $(this)

			// when all files are retrived, reset the plugin's internal file pointers
			}).done(function (data) {
				$(this).fileupload('option', 'done').call(this, null, { result : data });

				// hide and disable the upload button
				if ($(this).data("hide_button")) {
					$(this).find("input.file").attr("disabled", true);
					$(this).find("td.file_upload").addClass("uploaded");
				} else {
					$(this).find("td.file_upload").addClass("uploaded_showbtn");

				}

			});

		}

	});

	// autocomplete fields
	// uses: jquery-ui.min.js
	$("input[data-autocomplete]").each(function(){
		var el = $(this);
		var el_parent = el.parents("div.inputwrap");
		var el_value = el_parent.find("input.autocomplete-value");

		el.autocomplete({
			source: SYSTEM_ROOT + "ajax/ajax_autocomplete.php?x=" + el.data("url"),
			matchContains: false,
			minLength: el.data("min"),
			highlight: false,
			scroll: true,
			appendTo: ( el.parents(".jbox_overlay").length ? $(".jbox_overlay") : $("body") ),

			// callback when the results are returned from the server
			response: function(event, ui){
				el_parent.find(".loading").removeClass("loading");

				if (ui.content[0] && ui.content[0].label) {
					check_access(ui.content[0].label);
				} else {
					show_hint(el, "There are no results available for your search term.");
					$("div.uia-wrap").hide();
				}
			},

			// on mouseover or keyboard select, update the parent field
			focus: function( event, ui ) {
				el.val(ui.item.label);
				return false;
			},

			// executed when the dropdown is appended
			open: function(event, ui) {
				sterm = el.val();
				$('.ui-autocomplete .ui-menu-item:odd a').addClass('odd');

				// the next section highlights matched search term
				$('.ui-autocomplete .ui-menu-item a').each(function(){
					if ( $.isNumeric($(this).parents('.ui-menu-item').data("id"))) {
						orgText = $(this).text();
						re = new RegExp(sterm, "gi");
						newText = orgText.replace(re, '<em class="lite">' + sterm + '</em>');
						if (newText != orgText) $(this).html(newText);
					}
				});

				// add a helper text for adding new tags
				if ( el.attr("data-insert") && !$.isNumeric($('.ui-autocomplete .ui-menu-item:last').data("id"))) $('.ui-autocomplete .ui-menu-item:last a').append('<span class="add">Create new tag</span>');

				// warning message if there are more items in the result
				if ($('.ui-autocomplete .ui-menu-item').length == 10) {
					show_hint( el, "<b>Showing only 10 results</b>, continue typing to narrow down the selection.");
				}

				// reposition the menu
				if (el.parents(".jbox_overlay").length) $(".uia-wrap").css("top", el.offset().top + el.height() + $(".jbox_overlay").scrollTop() - 2);

			},

			// executed when the item from the dropdown is clicked on
			select: function(event, ui) {

				// the same autocomplete plugin is used for the library upload tags search
				// in case this was library tags search, append the selected tag and reload the images
				if ( el.data("tags") ) {
					el_parent.append("<span class=\'one_tag\' id=\""+ui.item.value+"\">"+ui.item.label+"<a data-remove-tag='true' class='remove tooltip' href='#' title='Remove "+ui.item.label+"'><i class='fa fa-times'></i></a></span>");

					// prepend value to hidden field
					el_value.val( el_value.val() + ui.item.value + ',');
					if ( el.data("image-tags") ) loadLibImgs(el);

					if ( el.hasClass("fksubmit") ) {
						self_submit( el, el.parents("form").attr("action"));
					}

				// for the standard autocomplete fields
				} else {

					// append the selected value to a hidden field, remove any errors
					el_value.val(ui.item.value);
					el.parent("div.inputwrap").find("label.error").remove();

					// submit the entire form on select if the auto_select attribute is present
					if ( el.attr("data-auto-select") ) {
						el.parents("form").submit();

					// selfsubmit the form, if the class is present
					} else if ( el.hasClass("fksubmit") ) {
						$(this).hide();
						el_parent.append("<span class=\'one_tag acoption\' id=\""+ui.item.value+"\">"+ui.item.label+"<a data-remove-selected='true' class='remove tooltip' href='#' title='Remove'></a></span>");
						self_submit( el, el.parents("form").attr("action"));

					// all other cases
					// hide the menu, format the selected value with the close button
					} else {
						$(this).hide();
						el_parent.append("<span class=\'one_tag acoption\' id=\""+ui.item.value+"\">"+ui.item.label+"<a data-remove-selected='true' class='remove tooltip' href='#' title='Remove'></a></span>");
						el_value.val(ui.item.label);
					}

				}

			},

			close: function(event, ui){
				el.val("");
			},

			search: function(event, ui){
				el_parent.find(".dd_icon").addClass("loading");
			}

		// prevent enter key
		}).keydown(function(e){
			if (e.keyCode == 13) return false;

		// if a search term was removed from the field, remove the hidden value too
		}).keyup(function(){
			if ( el.val() == "" ) el_value.val("");

		// if this autocomplete is faux dropdown menu (load items on click)
		}).click(function(){
			if ( el.data("dropdown")) el.autocomplete('search', el.val());

		// if this autocomplete is faux dropdown menu (load items on click)
		}).focus(function(){
			if ( el.data("dropdown")) el.autocomplete('search', el.val());

		// format each dropdown menu row
		}).data("ui-autocomplete")._renderItem = function( ul, item ) {
			if (item.label != "expired_session") return $("<div data-id=\""+item.value+"\">").append("<a>" + item.label + "</a>").appendTo(ul);

		};

	});

	// Move actions bar to the bottom of the jbox popup
	if ($("div.jbox_content_inner").length && $("div.jbox_content_inner div.actions").length){
		free_space = $("div.jbox_content_inner").offset().top + $("div.jbox_content_inner").height() - $("div.jbox_content_inner div.actions").offset().top - $("div.jbox_content_inner div.actions").height();
		$("#jbox_main div.actions").css('margin-top', free_space-17);
	}

	// datepicker options
	// uses: jquery-ui.min.js
	$('.dropdate').each(function(){

		// check if the ID is unique
		if ( $("#" + $(this).attr("id")).length > 1 ) {
			console.log("WARNING: ID of the datepicker element is not unique");

		} else {

			// date range datepicker, append more options
			if ( $(this).hasClass("fromto") ) {
				ops = {
					defaultDate: "-1m",
					numberOfMonths: 3,
					showButtonPanel: false
				}
			} else {
				ops = {
					showButtonPanel: true
				}
			}

			if ( $(this).data("min_date")) {
				jQuery.extend(ops, {
					minDate: new Date($(this).data("min_date"))
				});
			}

			if ( $(this).data("max_date")) {
				jQuery.extend(ops, {
					maxDate: new Date($(this).data("max_date"))
				});
			}

			// allows a date picker to be more useful if the input has class "dob"
			if ( $(this).parents("div.inputwrap").find("input.dob").length ) {
				jQuery.extend(ops, {
					yearRange: '1900:' + new Date().getFullYear()
				});
			}


			$(this).datepicker( jQuery.extend(ops, {
				altField: $(this).parents("div.inputwrap").find('.dropdate_trigger'),
				altFormat: "yy-mm-dd",
				showAnim: '',
				firstDay: 1,
				changeMonth: true,
				changeYear: true,
				dateFormat: USER_DATE,
				showOn: 'button',
				buttonText: '',

				// adjust the position of the datepicker popup
				beforeShow: function(input) {
					if ( $(this).parents(".jbox_main").length ) topval = $(".jbox_main").offset().top - $("body").scrollTop() + $(input).position().top + 40;
					else topval = $(input).parent(".inputwrap").position().top;
				},

				onClose: function() {
					$(this).parents("div.inputwrap").find(".dropdate").attr("value", $(this).val());
				},

				onSelect: function(){
					$(this).parents("div.inputwrap").addClass("hasdate");

					// if there was a self submit attached to the datepicker element
					if ( $(this).parents("div.inputwrap").find(".self_submit").length ) {
						self_submit($(this), $(this).parents("form").attr("action"));
					}

					// set the form's 'has changed' variable
					if ( $(this).parents("form").find("input[name=haschng]").val() == "") {
						$(this).parents("form").find("input[name=haschng]").val("1");
						formHasChanged();
						saveNotification();
					}

				}

			})).on("click", function(){
				$(this).parents("div.inputwrap").find(".ui-datepicker-trigger").trigger("click");

			});

		}
	});

	// timepicker (date + time)
	// uses: jquery.timepicker.js
	$('*[data-time-trigger]').timepicker();

	// table column date filters
	// showing inline calendar in the column filters popup
	$("div.filter_picker").each(function(){
		$(this).datepicker({
			firstDay: 1,
			changeMonth: true,
			changeYear: true,
			dateFormat: 'yy-mm-dd',
			defaultDate: $(this).parents().find("input.datef").val(),
			altField: $(this).parents().find("input.datef"),
			onSelect: function(selectedDate){
				$(this).parents().find("form.inline_picker_form").submit();
			}
		});
	});

	// attach validate plugin to all forms
   $("form").each(function(){
	    $(this).validate({
			ignore: [],
			focusCleanup: true,

			// if there are errors in the popup, update the style
			invalidHandler: function(form, validator){
				var valerrors = validator.numberOfInvalids();
	            if (valerrors) {
					$("#jbox_main").animate({"background-color": "#DC0000"}, 100).animate({"background-color": "#666"}, 200);

					// hide all other feedback
					$('div.page_feedback:not(.perm) div.g_feedback').delay(500).fadeOut(function(){
						$(this).remove();
					});

	            } else {
					// show 'Please wait...' feedback
					blockPage("Please wait...");
				};
			},

			// adjust where the error label should be created
			errorPlacement: function(error, element){
	        	error.appendTo( element.parent() );
	      	},

			// alter style on error creation
			highlight: function(element, errorClass){
				$(element).addClass("error elerror");
				$(element).parents("tr").addClass("elerror");
				showErrors();
			},

			// alter style when error is fixed / removed
			unhighlight: function(element, errorClass, validClass){
				if ( !$(element).parents("tr").hasClass("unqerror") ) {
					$(element).removeClass(errorClass).removeClass("elerror").addClass(validClass);
					$(element).parents("tr").removeClass(errorClass).removeClass("elerror").addClass(validClass);
				}
				showErrors();
			},

			submitHandler: function(form){

				// there might be some non-validated unique fields
				// example: unique and soft unique 'Add new user' email checks
				if ( $(form).find(".unique:not(.valid_unique):not(.soft_unique)").length ) {
					$(form).find(".unique:not(.valid_unique):not(.soft_unique)").each(function(){
						checkUnique($(this));
					});

					showErrors();

				// there are custom errors
				} else if  ( $(form).find("label.custom_error:not(.custom_notice)").length ) {
					return false;

				// all is ok, proceed, submit the form
				} else {
					// show 'Please wait...' feedback
					blockPage("Please wait...");
					form.submit();
				}

			}

		});

		// custom regex validation for input fields
		$.validator.addMethod('regex', function (value, element){
			var pattern = new RegExp($(element).attr("regex"));
			return this.optional(element) || pattern.test(value);

		}, function(params, element) {
			return $(element).data("regex-message");

		});

   });

	// attach tinymce editor to all rte textareas
	// uses: jquery.tinymce.min.js
   $('html:not(.ipad) textarea.rte').tinymce({
		script_url : SYSTEM_ROOT+'js/tinymce-3.5.11/jscripts/tiny_mce/tiny_mce.js',
		theme : "advanced",
		plugins : "style,autolink,pagebreak,iespell,inlinepopups,contextmenu,paste,fullscreen,visualchars,nonbreaking,advlist,media,loremipsum,jcode,lists,table,tableDropdown,readMore",
		theme_advanced_buttons1 : "styleselect,link,unlink,bullist,numlist,justifyleft,justifycenter,justifyright,justifyfull,readMore,sub,sup,tableDropdown,forecolor,backcolor,code" +  limce,
		theme_advanced_buttons2 : "",  // "styleselect,formatselect,fontselect,fontsizeselect", //",outdent,indent,|,undo,redo,|,link,unlink,image,|,cleanup,|,forecolor,backcolor,|,code,media",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,
		theme_advanced_resize_horizontal : false,
		theme_advanced_resizing_use_cookie : false,
		media_strict: false,
		apply_source_formatting : false,
		valid_elements : "*[*]",
		extended_valid_elements : "code,xmp,samp,pre",
		style_formats : [
			{title : 'Heading 1', block: 'h1'},
			{title : 'Heading 2', block : 'h2'},
			{title : 'Heading 3', block : 'h3'},
			{title : 'Bold text', inline : 'b'},
			{title : 'Italic text', inline : 'i'},
			{title : 'Underline text', inline : 'u'},
			// {title : 'Link with arrow', selector: 'a', classes : 'warr'},
			// {title : 'Red header', selector: 'p', classes : 'rdhdr'},
			// {title : 'Blue header', selector: 'p', classes : 'blhdr'},
			// {title : 'Table styles'},
			// {title : 'Table row 1', selector : 'tr', classes : 'tablerow1'}
		],
		remove_linebreaks : false,
		tab_focus : ':prev,:next',
		onchange_callback: function(editor) {
			tinyMCE.triggerSave();
			$("textarea.rte").valid();
		},
		table_styles : "Primary=primary;Secondary=secondary",
		paste_auto_cleanup_on_paste : true,
        paste_remove_spans: true,
        paste_remove_styles: true,
        paste_preprocess : function(pl, o) {
            o.content = o.content.replace(/<style><\/style>/gi, "");
            o.content = o.content.replace(/<p> <\/p>/gi, "");
            o.content = o.content.replace(/<p>&nbsp;<\/p>/gi, "");
			o.wordContent = true;
        },

        paste_postprocess : function(pl, o) {
			var ed = pl.editor, dom = ed.dom;
			tinymce.each(dom.select('*', o.node), function(el) {
				if (el.tagName.toLowerCase() != "p" && el.tagName.toLowerCase() != "i" && el.tagName.toLowerCase() != "u" && el.tagName.toLowerCase() != "ul" && el.tagName.toLowerCase() != "ol" && el.tagName.toLowerCase() != "li" && el.tagName.toLowerCase() != "b" && el.tagName.toLowerCase() != "br") {
					dom.remove(el, 1);
				}
				dom.setAttrib(el, 'style', '');
				dom.setAttrib(el, 'class', '');
			});
        },

        setup : function(ed) {
            ed.onLoadContent.add(function(ed, o) {
            	$("div.jbox_overlay").scrollTop($("div.jbox_overlay form input[name=form_scroll]").val());
            });
		 }
	});

	// ajax sort table rows
	// uses: jquery-ui.min.js
	$('table tbody').sortable({
		handle: $('.move_handle'),
		helper: fixHelper,
		axis: "y",

		// just adjust the style
		start: function (e, ui) {
			ui.placeholder.html("<td colspan=\"100%\"><div style=\"height:"+($(ui.item[0]).height())+"px;\"></div></td>");
			$(ui.item[0]).height();
		},

		// on stop, send an ajax request with IDs order
		stop: function(event, ui){

			// show 'Please wait...' feedback
			blockPage("Please wait...");

			ajsids = new Array();
			$(this).parents("table.list_table").find('span.move_handle').each(function() {
				ajsids.push($(this).attr('id'));
			});

			$.post(SYSTEM_ROOT + 'ajax/ajax_sort.php', {'action' : 'update_row_order', 'excupdate' : JSON.stringify(ajsids)}, function(data){
				check_access(data);

				// unblock the page, hide the 'Please wait...' feedback
				showFeedback("", "", "", 1, true);

			});

			// apply new styles
			$(this).find('tr').removeClass('odd even');
			$(this).find('tr:even').addClass('odd');
			$(this).find('tr:odd').addClass('even');

			// fix the column numbers
			column_num = 1;
			$(this).find('td.column_row_number').each(function() { $(this).html(column_num++); });

		}
	});

	// sortable form elements in the popups
	// example: gallery slides
	// uses: jquery-ui.min.js
	$("#jbox_main").sortable({
		items: "table.action_form:not(.separator)",
		handle: "*[data-jbox='sort_section']",
		forceHelperSize: true,
		forcePlaceholderSize: true,
		containment: "parent"
	});

	// all text toggling actions
	$("*[data-text-toggler]").off("click").on("click", function(e){
		e.stopPropagation();
		el = $(this);

		if (el.data("text-toggler") == "show"){
			el.parents("div.text_toggler").addClass("show_toggler");
			el.addClass("hide_toggler"); // rc menu activated
			el.find("span.txt").text("Show less"); // rc menu activated
			el.data("text-toggler", "hide");
		} else if (el.data("text-toggler") == "hide"){
			el.parents("div.text_toggler").removeClass("show_toggler");
			el.removeClass("hide_toggler"); // rc menu activated
			el.find("span.txt").text("Show more");
			el.data("text-toggler", "show");
		}

		// extra style if the text toggler in the right click menu
		if ( el.parents(".rcmenu").length ) {
			$("table tr.has_rcmenu span[data-text-toggler]").trigger("click");
			removeRightClickMenu();
		}

	});

	// unblock the page
	$("body").removeClass("block");

	// monitor if (any) form has changed
	if ($("input[name=haschng]").length && $("input[name=haschng]").val() != "") {
		formHasChanged();
	}

    return false;

}

// used in combination with ajax responses to log out user if his session expired
function check_access(ajax_response){
	if (ajax_response == "expired_session") window.location.reload();
}

// permissioning functions (single click, multi table rows drag n drop)
function togglePermission(element){
	$.post(SYSTEM_ROOT+'ajax/ajax_db_update_level.php', {'id' : element.attr('id') }, function(data, status) {
		check_access(data);
		element.attr('class', data.styleclass);
		element.attr('id', data.new_id);
	}, "json");
	return false;
}

// used in the multiselect action
function getSelected(element){
	selids = new Array();
	counter = 0;
	ptid = $(element).parents("table.list_table");
	$(ptid).find('.column_multiselect input:checked').each(function(){
		selids[counter] = $(this).attr('id');
		counter++;
		$(this).parents('tr').addClass('multiselected');
	});

	$(ptid).find('.column_multiselect input:not(:checked)').each(function(){
		$(this).parents('tr').removeClass('multiselected');
	});

	$('.edit_selected').attr('href', $('.edit_selected').data('uri') + 'ids=' + selids);
	$('.delete_selected').attr('href', $('.delete_selected').data('uri') + 'ids=' + selids);
	$('.copy_selected').attr('href', $('.copy_selected').data('uri') + 'ids=' + selids);

}

// starts removing one by one feedback box
// does not remove red (warning) boxes, only information boxes
function gRemove(){
	gTimer = setInterval(function(){
		if ($("div.page_feedback:not(.perm) div.g_info").length) $("div.page_feedback:not(.perm) div.g_info:first-child").fadeOut(1000, function(){ $(this).remove(); });
		else clearInterval(gTimer);
	}, 1000);
}

// ajax sort helper, used for sortable rows
var fixHelper = function(e, ui) {
	ui.children().each(function() {
		$(this).width($(this).width());
	});
	return ui;
};

// used for validating form in tabbed popups
var trigger_once = true;
function showErrors(){
	if ( $("#jbox_main div.jbox_tabs").length ){
		if (trigger_once) {
			epc = $("#jbox_main input.error").first().parents("div.form_content").index("div.form_content");
			if (epc >= 0) $("#jbox_main div.jbox_tabs a.jtab:eq("+(epc)+")").trigger("click");
			trigger_once = false;
		}

		el_pos = 0;
		$("#jbox_main div.form_content").each(function(){
			if ( $(this).find("tr.elerror").length > 0 ) {
				tab_title = $("#jbox_main div.jbox_tabs a.jtab:eq("+el_pos+") span.txt").text();
				$("#jbox_main div.jbox_tabs a.jtab:eq("+el_pos+")").html('<span class="txt">' + tab_title + '</span><span class="error">' + $(this).find("tr.elerror").length + '</span>');
				$("#jbox_main div.form_content:eq("+el_pos+") tr.tabs_separator h3").html('<span class="txt">' + tab_title + '</span><span class="error">' + $(this).find("tr.elerror").length + '</span>');
			} else {
				$("#jbox_main div.jbox_tabs a.jtab:eq("+el_pos+") span.error").remove();
				$("#jbox_main div.form_content:eq("+el_pos+") tr.tabs_separator h3 span.error").remove();
			}
			el_pos++;
		});
	}
}

// case insensitive :contains - used for quick table filters
jQuery.expr[":"].icontains = jQuery.expr.createPseudo(function(arg) {
    return function( elem ) {
        return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
    };
});

// needed for reading feedback cookies
function urldecode(url) {
	decode = decodeURIComponent(url.replace(/\+/g, ' '));
	return decode;
}

// tooltip, used to drag user's attention to some element on the screen
function show_hint(element, hint, duration, xoffset, yoffset, cls){
	if (duration == undefined) duration = 3000;
	if (xoffset == undefined) xoffset = 0;
	if (yoffset == undefined) yoffset = -40;

	$("span.ui-tooltip").hide();
	$("span.ui-temp-tooltip").remove();
	$("body").append("<span class=\"ui-temp-tooltip "+cls+"\" style=\"top:"+(element.offset().top + yoffset)+"px; left:"+(element.offset().left + xoffset)+"px;\">"+hint+"</span>");
    if (($("span.ui-temp-tooltip").offset().left + $("span.ui-temp-tooltip").width()) > ($(window).width() - 20)) $("span.ui-temp-tooltip").css({"left": element.offset().left - $("span.ui-temp-tooltip").width() - 20});
    if ($("span.ui-temp-tooltip").offset().top - $(window).scrollTop() < 10) $("span.ui-temp-tooltip").css({"top": $(window).scrollTop() + "px"});
	clearInterval(window.shint);
	window.shint = setTimeout(function(){
		$("span.ui-temp-tooltip").fadeOut(function(){
			$("span.ui-temp-tooltip").remove();
		});
	}, duration);
}

// build an apps dropdown menu
function makemyapps(){
	nahtml = '';
	$("div.head_wrap ul.apps li.hide").each(function(){
		nahtml += $(this).html();
		$("div.head_wrap div.more span.dmwrap").html(nahtml + '<a data-show-app-switcher="true" data-swap-app-title="Manage my apps" href="#" class="moreapps">Manage my apps</a>');
	});
}

// make javascript seo title
function jSeo(value){
	val = $.trim(value).replace(/ /g, "-").toLowerCase();
	return (val == "homepage" || val == "home" || val == "index") ? '' : val;
}

// feedback popups
// also used to show 'Please wait...' popups
function showFeedback(position, text, icon, timeout, block){
	if (text == undefined) text = "Please wait...";
	if (icon == undefined) icon = "success";
	if (timeout == undefined) timeout = 0;

	// append wrapper elements
	if (block != undefined) $("body").addClass("block");
	else $("body").append('<div class="page_feedback ' + position + '"><div class="g_feedback"><span class="' + icon + '">&nbsp;</span><p>' + text + '</p></div></div>');

	if (timeout > 0) {
		$("div.page_feedback:not(.perm) div.g_feedback").delay(timeout).fadeTo(500, 0, function (){
			$("div.page_feedback:not(.perm)").remove();
		});

		setTimeout(function(){
			$("body").removeClass("block");
		}, timeout);

	}

}

// wrapper function for showFeedback, only blocking the page showing "Please wait..." message
function blockPage(text){
	showFeedback("", "text", "", 0, true);
}

// used when pasting to textarea, to strip any additional tags
function strip_tags(input, allowed) {
  allowed = (((allowed || '') + '')
    .toLowerCase()
    .match(/<[a-z][a-z0-9]*>/g) || [])
    .join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
  var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
    commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
  return input.replace(commentsAndPhpTags, '')
    .replace(tags, function ($0, $1) {
      return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
    });
}

// used with file upload buttons, to allow library images to be linked
// triggered on initial load and everytime tags have changed
function loadLibImgs(elem){
	if ($(elem).parents("tr").find("div.lib_source")) {

		// group currently selected tags
		tags = [];
		$(elem).parents("tr").find("div.lib_source span.one_tag").each(function(){
			tags.push($(this).attr("id"));
		});

		// was there an image selected previously
		imgsel = ( $(elem).parents("tr").find("div.lib_source input.lib_file").val() != '' ) ? $(elem).parents("tr").find("div.lib_source input.lib_file").val() : '';

		// load the images from the ajax file
		$.post(SYSTEM_ROOT+'ajax/ajax_lib_images.php', { data : tags , selected : imgsel }, function(data){
			check_access(data);
			$(elem).parents("tr").find("div.img_holder").empty().html(data);

			if ( $(elem).parents("tr").find("div.img_holder a.img").length == 0 ) {
				$(elem).parents("tr").find("div.img_holder").addClass("noimgs");
				$(elem).parents("tr").find("div.lib_source input.lib_file").val("");

			} else {
				$(elem).parents("tr").find("div.img_holder").removeClass("noimgs");

			}

			// remove lost tooltips
			$("span.ui-tooltip").remove();
			console.log("A");

		});

	}
	return false;
}

// build page right click
function pageRightClick(e){
	selected_anchor = ( $(e.target).is("a") && !$(e.target).hasClass("jbox") ) ? $(e.target).attr('href') : false;
	if (!selected_anchor) selected_anchor = ( $(e.target).parents("a").length && !$(e.target).hasClass("jbox")) ? $(e.target).parents("a").attr('href') : false;
	e.stopPropagation();

	// disable browser's right click
	$(document).bind("contextmenu", function(e){ return false; });

	// remove previous menu
	$("div.rcmenu").remove();
	$("tr.has_rcmenu").removeClass("has_rcmenu");

	// show the new menu
	$("body").append('<div class="rcmenu"></div>');

	// build html, add actions, apps and application specific shortcuts
	if (selected_anchor) $("div.rcmenu").append('<a href="'+selected_anchor+'" class="open_link" target="_blank"><span class="ico_app"><i class="fa fa-external-link-square"></i></span><span class="txt">Open link in new tab</span></a>');
	$("div.rcmenu").append('<a data-swap-app-title="Go to your Home page" href="'+$("li.home_page_link a").attr('href')+'" class="home"><span class="ico_app"><i class="fa fa-home"></i></span><span class="txt">Home page</span></a>');

	// append all apps
	$( $("div.head_wrap ul.apps > li.qacc > a") ).each(function(){
		$("div.rcmenu").append('<a data-swap-app-title="'+$(this).data("swap-app-title")+'" href="'+$(this).attr("href")+'">' + $(this).html() + '</a>');
	});

	$("div.rcmenu").append($('<div>').append($("div.head_wrap a.moreapps").clone()).html());

	// append shortcuts
	if ( $("div.side_panel div.shortcuts").length ) $("div.rcmenu").append('<span class="subwrap appsubwrap">' + $("div.side_panel div.shortcuts div.s_content").html() + '</div>');
	tmp = '';
	$("body ul.page_options li.submenu_right").each(function(){ tmp = $(this).html() + tmp; });

	// append page options
	$("div.rcmenu").append('<span class="subwrap">'+tmp+'</span>');

	// make position
	if (e.clientX + 200 < $(document).width()) pl = e.clientX; else pl = e.clientX - ((e.clientX + 230) - $(document).width() - 20 );
	if (e.clientY + $(window).scrollTop() + $("div.rcmenu").height() < ( $(window).height() + $(window).scrollTop())) pt = e.clientY + $(window).scrollTop();
	else pt = e.clientY - ( (e.clientY + $("div.rcmenu").height()) - ($(window).height() + $(window).scrollTop())) - 15;
	$("div.rcmenu").animate({"top": pt+"px", "left" : pl + "px"}, 0, function(){ $("div.rcmenu").fadeIn(0); });

	// relink jbox triggers
	$("a.jbox").off(".jbox");
	$("a.jbox").jbox();

}

// removes the right click menu and other classes
function removeRightClickMenu(){
	$("div.rcmenu").remove();
	$("tr.has_rcmenu").removeClass("has_rcmenu");
	$(document).unbind("contextmenu");
}

// used in forms where a check is made against the values in the database
function checkUnique(elmt){

	if ( $(elmt).val() != '') {
		$.post(SYSTEM_ROOT+"ajax/ajax_unique_check.php", { "value" : $(elmt).val(), "data" : $(elmt).data("unique"), "soft_unique" : $(elmt).hasClass("soft_unique") }, function(data) {
			check_access(data);

			if (data != "ok") {

				// prevent submittion
				if ( !$(elmt).hasClass("soft_unique") ) {

					$(elmt).addClass("error").removeClass("valid_unique");
					$(elmt).parents("tr").addClass("elerror unqerror");
					if ( $(elmt).parents("td").find("label.custom_error").length == 0 ) $(elmt).parents("td").append('<label generated="true" class="custom_error">The value you entered is identical to value in one other record.</label>');

				// just show warning
				} else {
					formatted = (data == 1) ? 'one other record' : data + ' other records';
					$(elmt).addClass("valid_unique").addClass("notice");
					if ( $(elmt).parents("td").find("label.custom_error").length == 0) $(elmt).parents("td").append('<label generated="true" class="custom_error custom_notice">The value you entered already exist in '+formatted+'.</label>');
				}

			} else {
				$(elmt).removeClass("error").addClass("valid_unique");
				$(elmt).parents("tr").removeClass("error").removeClass("unqerror");
				$(elmt).parents("td").find("label.custom_error").remove();

			}

		});

	} else { // just add valid classes
		$(elmt).removeClass("error").addClass("valid_unique");
		$(elmt).parents("tr").removeClass("error").removeClass("unqerror");
		$(elmt).parents("td").find("label.custom_error").remove();

	}
}

// used on forms on pages (not popups)
function formHasChanged(){

	// disable jbox
	$("a.jbox").off(".jbox");

	// attach a click listener to all links
	$("a:not(.no-change-monitor):not(.ui-tabs-anchor):not(.cleardate)").on("click.prompt", function(){
		window.togoto = $(this);

		$("body").addClass("prompt");
		$("body").append('<div class="page_feedback gprompt">'+
			'<div class="g_feedback"><p><b>You have unsaved changes.</b></p><p>Are you sure you would like to navigate away from this page?</p>'+
			'<p><a data-prompt="yes" href="#" class="btn blue">Yes</a><a data-prompt="no" href="#" class="btn">No</a></p></div></div>');

		// attach a click to "yes" action
		$(".gprompt a[data-prompt='yes']").on("click", function(){

			$("a").off(".prompt");
			$("input[name=haschng]").val("");
			if ( $(window.togoto).hasClass("jbox")){
				$("body").removeClass("prompt");
				$(".gprompt").remove();
				$("a.jbox").jbox();
			}
			$(window.togoto)[0].click();
			return false;
		});

		return false;
	});

}

// notify user to save form once in a while
function saveNotification(){
	sTimer = setInterval(function(){
		if ( $(".savehint").length == 0) showFeedback("g_centre conclick", "Please remember to occasionally save your work", "info savehint", 10000);
	}, set / 2 - 120000); // minus 2 minutes
}

// scrollbar width plugin
// used to measure the real browser's scrollbar width when showing jbox
(function($,b,a){$.scrollbarWidth=function(){var c,d;if(a===b){c=$('<div style="width:50px;height:50px;overflow:auto"><div/></div>').appendTo("body");d=c.children();a=d.innerWidth()-d.height(99).innerWidth();c.remove()}return a}})(jQuery);

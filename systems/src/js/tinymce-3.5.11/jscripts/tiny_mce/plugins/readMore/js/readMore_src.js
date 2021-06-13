var readMoreDialog = {
	init : function() {
		var f = document.forms[0];
	},

	insert : function() {
		// Insert the contents from the input into the document
		var rm_title = document.getElementById("readmore_title").value;
		var rl_title = document.getElementById("readless_title").value;
		var rm_text = document.getElementById("readmore_text").value;
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, '<div class="collpanel"><div class="pnl">'+rm_text+'</div><a class="rm" href="#">'+rm_title+'</a><a class="rl" href="#">'+rl_title+'</a></div>');
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.requireLangPack();
tinyMCEPopup.onInit.add(readMoreDialog.init, readMoreDialog);
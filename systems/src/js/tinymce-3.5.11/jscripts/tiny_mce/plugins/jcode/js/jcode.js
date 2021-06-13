var jcodeDialog = {
	init : function() {
                var f = document.forms[0];
	},

	insert : function() {
		// Insert the contents from the input into the document
		var text_code = document.getElementById("jcode_code").value;
		console.log(text_code);
		tinyMCEPopup.editor.execCommand('mceInsertContent', false, "<pre>"+text_code+"</pre>");
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.requireLangPack();
tinyMCEPopup.onInit.add(jcodeDialog.init, jcodeDialog);
/**
 * Lorem Ipsum plug-in for TinyMCE version 3.x
 * -------------------------------------------
 *
 * @author     Nikola Bojic
 * @version    $Rev: 10 $
 * @package    readMore
 * @link       http://www.riskpoint.co.uk
 */

(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('readMore');

	tinymce.create('tinymce.plugins.readMore', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('mcereadMore', function() {
				ed.windowManager.open({
					file : url + '/readMore.html',
					width : 547,
					height : 380,
					inline : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			// Register readMore button
			ed.addButton('readMore', {
				title : 'Read More collapsible panel',
				cmd : 'mcereadMore',
				image : url + '/img/icon.gif'
			});

		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'Johnny Read More Plugin',
				author : 'nikola.bojic@riskpoint.co.uk',
				authorurl : 'http://www.riskpoint.co.uk',
				infourl : 'http://www.riskpoint.co.uk',
				version : "0.0.0.0.0.0.0.0.1" // beta beta
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('readMore', tinymce.plugins.readMore);
})();
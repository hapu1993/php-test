$(function(){

	var gridster = $(".gridster > ul").gridster({
		widget_margins: [5, 5],
		widget_base_dimensions: [65, 65],
		min_cols: 24,
		max_cols: 24,

		draggable: {
			handle: '.title_bar',
			stop: function(alert, ui){
				save_dash_layout($(".gridster > ul").attr('id'));
			},
		},
		resize:{
			enabled: true,
			stop: function(alert, ui){
				save_dash_layout($(".gridster > ul").attr('id'));
			},
		},
		serialize_params: function($w, $wgd){
			return { id: $w.data('id'), col: $wgd.col, row: $wgd.row, size_x: $wgd.size_x, size_y: $wgd.size_y }
		},
	}).data('gridster');

	$(document).on('click', ".gridster > ul li.widget .title_bar .remove_widget", function(e){
		widget_id = $(this).parents('li').data('id');
		dash_id = $(".gridster > ul").data('id');
		gridster.remove_widget($(".gridster > ul#"+dash_id+" li[data-id='"+widget_id+"']"));
		save_dash_layout(dash_id);
	});

	function save_dash_layout(id){
		$.post(SYSTEM_ROOT+'ajax/ajax_save_dash.php', {'layout':JSON.stringify(gridster.serialize()), 'id':id});
	}

});

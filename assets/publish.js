jQuery(document).ready(function() {
	var $ = jQuery;
	
	$('.field-daterange').each(function() {
		var field = $(this);
		var from = field.find('input:first');
		var to = field.find('input:last');
		var mode = field.find('select');
		var update = function() {
			if (mode.val() == 'until-date') {
				to.parent().show();
			}
			
			else {
				to.parent().hide();
			}
		};
		
		mode.bind('change', update)
			.bind('keyup', update);
		
		update();
	});
});
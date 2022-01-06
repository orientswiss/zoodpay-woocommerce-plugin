jQuery(document).ready(function() {

	jQuery("body").delegate('.t_c', 'click' , function(){

		var TData = jQuery(this).attr('data');
		var thidData = jQuery("#"+TData).val();
		jQuery("#main-id").html(thidData);
		jQuery('.modal').fadeIn();
	})

	jQuery('.closeX').click(function() {
		jQuery('.modal').fadeOut();
	});
});
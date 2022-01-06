jQuery(document).ready(function(){
	var orderStatus = jQuery("#order_status").val();
	if(orderStatus !== "wc-completed" && orderStatus !== "wc-processing"){
		jQuery(".do-api-refund").remove();
	}
})
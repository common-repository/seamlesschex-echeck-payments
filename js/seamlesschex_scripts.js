jQuery(document).ready(function() {
    jQuery('.subsubsub').append('<button id="schex_status_update_all" class="button wc-action-button wc-action-button-view view" href="#" aria-label="Status Update">Update eCheck statuses</button>');
   
    jQuery('button#schex_status_update_all').click(function() {
        var btn = jQuery(this);
        jQuery.ajax(ajaxurl, {
            type: "post",
            data: {
                action: 'status_update_all_hook',
                _ajax_nonce: ajax_object_name.security
            },
            beforeSend: function() {
                btn.append('<div id="loading" style="display:inline;margin:3px"><img src="images/loading.gif" title="loading" /></div>');
                btn.prop('disabled', true);
            },
            success: function(response) {
                location.reload();
            },
            complete: function() {
                btn.find('#loading').remove();
                btn.prop('disabled', false);
            },
            error: function(err) {
                console.log("Inside error and the error is: " + err.status + " " + err.statusText);
            }
        });
    });
    
    

});

function handleClick(thisCheckbox) {
    jQuery('#seamless-recurring-start-date').datepicker({
        format: 'mm/dd/yyyy', 
        autoHide: true
    });

	if (jQuery(thisCheckbox).attr('id') == 'seamless-recurring') {

        if(jQuery(thisCheckbox).prop("checked")){
    	    jQuery('#seamless-subscription').val('0');
    	    jQuery('#seamless-subscription').prop('checked',false);
           jQuery(thisCheckbox).val('1');	    
        }else{
           jQuery(thisCheckbox).val('0');
           jQuery('#seamless-recurring-cycle').val('');
           jQuery('#seamless-recurring-start-date').val('');
           jQuery('#seamless-recurring-installments').val('');
        }
    	var hiddenList = document.getElementsByClassName("recurring-hidden-block");
        for (var i = 0; i < hiddenList.length; i++) {
            if (jQuery(thisCheckbox).prop("checked")) {
                hiddenList[i].style.display = "block";
            } else {
                hiddenList[i].style.display = "none";
            }
        }

	} else if (jQuery(thisCheckbox).attr('id') == 'seamless-subscription') {

    	if(jQuery(thisCheckbox).prop("checked")){
           jQuery(thisCheckbox).val('1');
    	   jQuery('#seamless-recurring').val('1');
    	   jQuery('#seamless-recurring').prop('checked',false);
        }else{
           jQuery(thisCheckbox).val('0');
    	   jQuery('#seamless-recurring').val('0');    
           jQuery('#seamless-recurring-cycle').val('');
           jQuery('#seamless-recurring-start-date').val('');
           jQuery('#seamless-recurring-installments').val('');
        }	
        var hiddenList = document.getElementsByClassName("recurring-hidden-block");
        for (var i = 0; i < hiddenList.length; i++) {
            if (jQuery(thisCheckbox).prop("checked") && i!=2) {
                hiddenList[i].style.display = "block";
            } else {
                hiddenList[i].style.display = "none";
            }
        }
	}
}

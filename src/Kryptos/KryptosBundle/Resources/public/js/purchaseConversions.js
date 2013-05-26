$(document).ready(function() {
	if ($('#PurchaseConversionsForm_conversions').length) {
		function clearFormItems(){
			$("#PurchaseConversionsForm_cost").val('');
        	$("#PurchaseConversionsForm_vat").val('');
        	$("#PurchaseConversionForm_errors").text('');
		}
		
		var jsonUrl, amount, xhr, previousAmount;
		var ajaxRequests = new Array();
		
		// make sure only numbers are allowed
		$('#PurchaseConversionsForm_conversions').keydown(function(event) {
			// Allow only delete, backspace, tab, enter (end, home, left, right)
			if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 13 || (event.keyCode >= 35 && event.keyCode <= 39)) {
				if ( event.keyCode == 13) {
					$('#PurchaseConversionForm_btn_cacl').trigger('click');
				}
			}
			else {
				// Ensure that it is a number and stop the keypress
				if (!((48 <= event.keyCode && event.keyCode <= 57) || (96 <= event.keyCode && event.keyCode <= 105)) ) {
					event.preventDefault();	
				}	
			}
		});
		
		
		$('#PurchaseConversionsForm_conversions').keyup(function(event) {
			amount = $("#PurchaseConversionsForm_conversions").val();
			if (amount != previousAmount) {
				clearFormItems();
			}
		});
		
		
		$('#PurchaseConversionForm_btn_cacl').click(function() {
			clearFormItems();
        	
        	for (var i = 0; i < ajaxRequests.length; i++) {
        		xhr = ajaxRequests[i];
        		if(typeof yourvar != 'undefined' && typeof xhr.abort === 'function') {
            		xhr.abort()
            	}
        		delete ajaxRequests[i];
        	}
        	
	        amount = $("#PurchaseConversionsForm_conversions").val();
	        if (amount.length != 0) {
	        	jsonUrl = "/purchase/conversions/calculate/rates/" + amount;
	        	xhr = $.ajax({
	        		dataType: "json",
	        		url: jsonUrl,
	        		cache: true,
	        		success: function(result) {
	        			if (result['body']['error'] === undefined) {
		        			if (result['body']['cost'] !== undefined && result['body']['vat'] !== undefined) {
		        				$("#PurchaseConversionsForm_cost").val(result['body']['cost']);
		        				$("#PurchaseConversionsForm_vat").val(result['body']['vat']);
		        			}
	        			}
	        			else {
	        				$("#PurchaseConversionForm_errors").text(result['body']['error']);
	        				// under an error scenario we may still return  the cost and vat. i.e when total is less than £0.01
	        				if (result['body']['cost'] !== undefined && result['body']['vat'] !== undefined) {
		        				$("#PurchaseConversionsForm_cost").val(result['body']['cost']);
		        				$("#PurchaseConversionsForm_vat").val(result['body']['vat']);
		        			}
	        			}
	                }
	        	});
	        	ajaxRequests.push(xhr);
	        	previousAmount =  amount;
	        }
		});
	}
});
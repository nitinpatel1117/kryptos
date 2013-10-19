hideAllFields = function()
{
	$('.form_row.bban1').css('visibility', 'hidden');
	$('.form_row.bban2').css('visibility', 'hidden');
	$('.form_row.bban3').css('visibility', 'hidden');
	$('.form_row.bban4').css('visibility', 'hidden');
	$('.form_row.bban5').css('visibility', 'hidden');
}

removeAllValues = function()
{
	for (var i=1; i<6; i++) {
		$('#ConvertSingleForm_bban'+i).val('');
		//$('#ConvertSingleForm_bban'+i).attr("placeholder", '');
		$('#ConvertSingleForm_bban'+i+'_tip').attr("data-original-title", '');
		$('#ConvertSingleForm_bban'+i+'_tip').attr("data-content", '');
	}
}

placeholderIsSupported = function () {
    var test = document.createElement('input');
    return ('placeholder' in test);
}


if (typeof countrySelected != 'undefined' && '' == countrySelected) {
	hideAllFields();
}

$(document).ready(function() {
	
	changeToCountry = function(bbanMap, countryCode, hideFields)
	{
		if (countryCode in bbanMap) {
			hideAllFields();
			if (true == hideFields) {
				removeAllValues();
			}
			
			$('#ConvertSingleForm_bban1').removeAttr("required");
			$('#ConvertSingleForm_bban2').removeAttr("required");
			$('#ConvertSingleForm_bban3').removeAttr("required");
			$('#ConvertSingleForm_bban4').removeAttr("required");
			$('#ConvertSingleForm_bban5').removeAttr("required");
			
			for( var key in bbanMap[countryCode] ) {
				var fieldName = bbanMap[countryCode][key]['name'];
				var fieldHint = bbanMap[countryCode][key]['hint'];
				
				// $('.form_row.'+key+' label').text(value);
				$('#ConvertSingleForm_'+key).attr("required", "required");
				$('#ConvertSingleForm_'+key).attr("placeholder", fieldName);
				$('#ConvertSingleForm_'+key+'_tip').attr("data-original-title", fieldName);
				$('#ConvertSingleForm_'+key+'_tip').attr("data-content", fieldHint);

				
				if (!placeholderIsSupported()) {
					// $('#ConvertSingleForm_'+key).val(fieldName);
					//$('input[type=text], textarea').placeholder();
					
					$('#ConvertSingleForm_'+key).placeholderEnhanced('destroy');
					$('#ConvertSingleForm_'+key).val('');
					$('#ConvertSingleForm_'+key).placeholderEnhanced();
				}
				
				$('.form_row.'+key).css('visibility', 'visible');
		    }
		}
		
		if ('' == countryCode) {
			hideAllFields();
		}
		
	}
	
	if (typeof bbanMap != 'undefined' && typeof countrySelected != 'undefined') {
		changeToCountry(bbanMap, countrySelected, false);
	}

	if ($('select#ConvertSingleForm_country').length) {
		$('select#ConvertSingleForm_country').change(function() {
			var countryCode, ibanPlaceholder;
			
			countryCode = $('select#ConvertSingleForm_country').val();
			changeToCountry(bbanMap, countryCode, true);
			
			// remove iban value
			if (placeholderIsSupported()) {
				$('#ConvertSingleForm_iban').val('');
			} 
			else {
				ibanPlaceholder = $('#ConvertSingleForm_iban').attr('placeholder');
				$('#ConvertSingleForm_iban').val(ibanPlaceholder);
				$('#ConvertSingleForm_iban').addClass('placeholder');
			}
		});
		/*
		$('select#ConvertSingleForm_country').click(function() {
			countryCode = '';
			changeToCountry(bbanMap, countryCode);
		});
		*/
	}
	
	if ($('input#ConvertSingleForm_iban').length) {
		$('input#ConvertSingleForm_iban').keydown(function() {
			$('select#ConvertSingleForm_country').val('');
			hideAllFields();
			removeAllValues();
		});
	}
	
	// register the popover on the bban fields
	for (var i=1; i<6; i++) {
		if ($('i#ConvertSingleForm_bban'+i+'_tip').length) {
			$('i#ConvertSingleForm_bban'+i+'_tip').popover({ trigger: 'hover', animation: 'true', placement:'right'});
		}
	}
	
});
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
	$('#ConvertSingleForm_bban1').val('');
	$('#ConvertSingleForm_bban2').val('');
	$('#ConvertSingleForm_bban3').val('');
	$('#ConvertSingleForm_bban4').val('');
	$('#ConvertSingleForm_bban5').val('');
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
				var value = bbanMap[countryCode][key];
				// $('.form_row.'+key+' label').text(value);
				$('#ConvertSingleForm_'+key).attr("required", "required");
				$('#ConvertSingleForm_'+key).attr("placeholder", value);
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
			countryCode = $('select#ConvertSingleForm_country').val();
			changeToCountry(bbanMap, countryCode, true);
		});
		/*
		$('select#ConvertSingleForm_country').click(function() {
			countryCode = '';
			changeToCountry(bbanMap, countryCode);
		});
		*/
	}
});
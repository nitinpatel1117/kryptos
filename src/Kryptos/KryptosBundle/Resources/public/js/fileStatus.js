$(document).ready(function() {
	if ($('section#fileStatus').length) {
		var jsonUrl, xhr, timerId;

		function timerMethod() {
			jsonUrl = "/file/status/ajax";
        	xhr = $.ajax({
        		dataType: "html",
        		url: jsonUrl,
        		cache: true,
        		success: function(result) {
        			$('section#fileStatus').html(result);
                }
        	});
		}

		// reload every minute
		timerId = setInterval(timerMethod, 60000);
	}
});
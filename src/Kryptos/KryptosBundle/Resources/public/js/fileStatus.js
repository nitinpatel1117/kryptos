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
        			progressTheBars();
                }
        	});
		}

		// reload every minute
		timerId = setInterval(timerMethod, 60000);
		
		
		
		
		function progressTheBars() {
			$('.fileStatusBar').each(function() {
				
				var bar = $(this);
				
				bar.css('width', bar.attr('progressfrom')+'%').animate({
					width:  bar.attr('progressto')+'%',
				}, {
					 duration: 60000,
				});
				
				console.log(bar.attr('progressfrom'));
				console.log(bar.attr('progressto'));
			});
		}
		
		progressTheBars();
	}
});
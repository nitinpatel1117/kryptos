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
        			//progressTheBars();
                }
        	});
		}

		// reload every minute
		timerId = setInterval(timerMethod, 60000);
		
		
		
		/*
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
		*/
		
		
		
		
		
		function progressTheBars() {
			
			var width = null;
			//var width = bar.width();
			//var parentWidth = bar.parent().width();
			//var percent = 100*width/parentWidth;
			
			var progress = setInterval(function() {
				
				$('.fileStatusBar').each(function() {
					
					var bar = $(this);
					var parentWidth = bar.parent().width();
					
					var interval = (bar.attr('progressto') - bar.attr('progressfrom')) / 100 * parentWidth / 60;
					
					if (null == width) {
						width= bar.width();
					}
					else {
						width = parseFloat(bar.attr('progressWidth'));
						if (isNaN(width)) {
							width= bar.width();
						}
					}
					
			
					var newWidth = width+interval;
					
					
					bar.attr('progressWidth', newWidth);

					
					//console.log(bar.attr('progressto'));
					//console.log(bar.attr('progressfrom'));
					//console.log(parentWidth);
					
					//console.log(interval);
					//console.log(newWidth);
					
					
					bar.width(newWidth);
					
					//console.log(bar.attr('progressfrom'));
					//console.log(bar.attr('progressto'));
					
					//console.log('##################');
				});

			}, 1000);
			
			
			
			
			
			
			
			/*
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
			*/
		}
		
		progressTheBars();
		
		/*
		var progress = setInterval(function() {
		    var $bar = $('.bar');
		    
		    if ($bar.width()==400) {
		        clearInterval(progress);
		        $('.progress').removeClass('active');
		    } else {
		        $bar.width($bar.width()+40);
		    }
		    $bar.text($bar.width()/4 + "%");
		}, 800);
		*/
	}
});
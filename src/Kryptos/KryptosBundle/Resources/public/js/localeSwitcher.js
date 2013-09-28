$(document).ready(function() {
	if ($('ul#locale_switcher').length) {
		$('ul#locale_switcher li a').click(function(event) {
			event.preventDefault();
			
			var jsonUrl = $(this).attr('data-link');
			$.ajax({
				dataType: "json",
				url: jsonUrl,
				cache: false,
				success: function(result) {
					if ("success" == result.body.status) {
						location.reload();
					}
				}
			});
		});
	}
});
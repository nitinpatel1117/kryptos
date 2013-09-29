$(document).ready(function() {
	$("input[rel=popover]")
	    .popover()
	    .click(function(e) { 
	        e.preventDefault(); 
	    });
});
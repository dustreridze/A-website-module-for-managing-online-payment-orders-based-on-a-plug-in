$(document).ready(function(){
	/*ripple effect for buttons*/
	$.ripple(".btn-ripple", {
		debug: false, // Turn Ripple.js logging on/off
		on: 'mouseenter', // The event to trigger a ripple effect

		opacity: 0.4, // The opacity of the ripple
		color: "auto", // Set the background color. If set to "auto", it will use the text color
		multi: true, // Allow multiple ripples per element

		duration: 0.6, // The duration of the ripple

		easing: 'linear' // The CSS3 easing function of the ripple
	});
	/**/
});
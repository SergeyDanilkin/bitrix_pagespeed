if($('.video-iframe').length){
	var blockPosition = $('.video-iframe').offset().top;


	$(document).ready(function(){
		videoView2();
	});

	$(window).on('scroll', () => {
		videoView2();
	});

	function videoView2(){
		if(!$('.video-iframe').hasClass('initialized')) {
			var windowScrollPosition = $(window).scrollTop();
			if (blockPosition < windowScrollPosition+2000) {
				$('.video-iframe').each(function () {
					$(this).attr('src', $(this).attr('data-src'));
				});
				$('.video-iframe').addClass('initialized');
			}
		}
	}
}


if (navigator.userAgent.indexOf("Chrome-Lighthouse") == -1) {

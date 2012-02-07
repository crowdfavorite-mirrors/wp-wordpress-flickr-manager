
(function($) {
	$(document).ready(function() {
		var shareBox;
		
		$("a.flickr-image").hover(
			function () {
				var link = $(this);
				var image = link.find('img:first');
				
				shareBox = $('<span class="shareBox" style="background-color: #fff;" />').share({
					url: link.attr('href')
					,title: link.attr('title')
					,description: image.attr('title')
					,tags: ''
					,services: WFM_ShareServices
				}).click(function(e) {
					e.stopPropagation();
				}).css({
					position: 'absolute'
					,left: image.offset().left + 'px'
					,top: image.offset().top + 'px'
					,display: 'block'
					,lineHeight: '1em'
				}).appendTo(link);
			}, 
			function () {
				shareBox.remove();
			}
		);
	});
})(jQuery)
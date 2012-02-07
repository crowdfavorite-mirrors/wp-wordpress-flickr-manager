
(function($) {
	
	$(document).ready(function() {
		$('a[rel*=flickr-mgr]').click(function() {
			var link = $(this);
			var image = link.find('img:first');
			
			var idRegex = /http\:\/\/.*\/([0-9]+)_.*/;
			var photoid = image.attr('src').match(idRegex)[1];
			var loc = window.location.href.match(/^([^#]*)/)[0];
            
			if(WFM_Permalinks) {
				loc += (/\/$/.test(loc)) ? '' : '/';
				window.location = loc + 'flickr/{0}/'.format(photoid);
			} else {
				loc += (/\?/.test(loc)) ? '&' : '?';
				window.location = loc + 'flickrid=' + photoid;
			}
			
			return false;
		});
	});
	
})(jQuery)

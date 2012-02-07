
(function($) {
	$(document).ready(function() {
		
		// Load theme
		Galleria.loadTheme(WFM_PluginDir + '/js/galleria.classic.min.js');
		
		$('.flickrGallery').each(function() {
			var set = $(this);
			
			$(this).galleria({
				width: set.width()
				,height: 480
				,showInfo: true
				,debug: true
				,dataSelector: "a[rel*=flickr-mgr]"
				,dataConfig: function(a) {
			        // a is now the anchor element
			        // the function should return an object with the new data
			        return {
			            image: GetFlickrHref(a) // tell Galleria that the href is the main image,
			            ,title: GetTitle(a) // use the anchor text for title
						,description: GetDescription(a)
			        }
			    }
			});
		});
		
	});
	
})(jQuery)

	
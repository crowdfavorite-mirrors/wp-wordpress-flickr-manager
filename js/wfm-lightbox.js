
(function($) {
	
	$(document).ready(function() {
	
		PrepareImages();
	
	});
	
	function PrepareImages() {
		
		$('a[rel*=flickr-mgr]').click(function() {
		
			var link = $(this);
		
			if(link.attr("rel") == "flickr-mgr") {	// Individual Photo
			
				var origUrl = link.attr("href");
				var origTitle = link.attr('title');
				
				var caption = GetTitle(link);
				caption += '<br /><span class="description">{0}</span>'.format(GetDescription(link));
				
				link.attr('title', caption);
				link.attr("href", GetFlickrHref(link));
				link.attr("rel", '');
				
				link.lightBox({
					imageLoading:	WFM_PluginDir + '/images/loading-3.gif'
					,imageBtnClose:	WFM_PluginDir + '/images/closelabel.gif'
					,imageBlank: 	WFM_PluginDir + '/images/blank.gif'
				}).click();
				
				
				setTimeout(function() {
					link.attr("rel","flickr-mgr");
					link.attr("href", origUrl);
					link.attr('title', origTitle);
			
					PrepareImages();
				}, 100);
			
			} else {
				
				if(link.attr('lbclick') == 'true') {
					return false;
				}
				
				//var origUrls = [];
				var setRel = link.attr("rel");
				
				$('a[rel*="'+setRel+'"]').each(function() {
					var testLink = $(this);
					
					testLink.attr('origURL', testLink.attr('href'));
					testLink.attr('origTitle', testLink.attr('title'));
					
					var caption = GetTitle(testLink);
					
					caption += '<br /><span class="description">@Description</span>'
										.replace(/@Description/g, GetDescription(testLink));
					
					testLink.attr("title", caption);
					testLink.attr('lbclick', 'true');
					testLink.attr("href", GetFlickrHref(testLink));
				}).lightBox({
					imageLoading:		WFM_PluginDir + '/images/loading-3.gif'
					,imageBtnClose:		WFM_PluginDir + '/images/closelabel.gif'
					,imageBlank: 		WFM_PluginDir + '/images/blank.gif'
					,imageBtnPrev: 		WFM_PluginDir + '/images/prevlabel.gif'
					,imageBtnNext: 		WFM_PluginDir + '/images/nextlabel.gif'
				});
				
				link.click();
				
				// Delay changing the URL's back because Internet Explorer doesn't wait for execution to finish
				setTimeout(function() {
					$('a[rel*="'+setRel+'"]').each(function(){
						var setLink = $(this);
						setLink.attr('href', setLink.attr('origURL'));
						setLink.attr('title', setLink.attr('origTitle'));
						setLink.attr('lbclick','false');
					});
					
					PrepareImages();
				}, 100);
				
			}
			
			return false;
		
		});
		
	}
	
})(jQuery);

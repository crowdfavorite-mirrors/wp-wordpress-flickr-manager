// Flickr Manager Highslide Integration
(function($) {

	var groupRegex = /^flickr\-mgr\[(.*)\]$/i;
	var groups = [];
	
	$(document).ready(function() {
		
		hs.graphicsDir = WFM_PluginDir + '/images/highslide/';
		hs.outlineType = 'rounded-white';
		hs.transitions = ['expand', 'crossfade'];
		hs.fadeInOut = true;
		
		PrepareImages();
	});
	
	function RegisterGroup(gid) {
		if(groups.indexOf(gid) == -1) {
			hs.addSlideshow({
				slideshowGroup:  gid.toString(),
				interval: 5000,
				repeat: false,
				useControls: true,
				fixedControls: 'fit',
				overlayOptions: {
					opacity: .75,
					position: 'bottom center',
					hideOnMouseOut: true
				}
			});
			
			groups.push(gid);
		}
	}
	
	function PrepareImages() 
	{
		$('a[rel*=flickr-mgr]').each(function () {
			
			var link = $(this);
				
			var caption = GetTitle(link);
			caption += '<br /><span class="description">{0}</span>'.format(GetDescription(link));
			
			var rel = link.attr('rel');
			var image = link.find('img');
			
			if(groupRegex.test(rel)) {
				// Image group
				var gID = 'g' + rel.match(groupRegex)[1];
				
				RegisterGroup(gID);
			
				this.onclick = function() {
					return hs.expand(this, { 
						slideshowGroup: gID 
						,captionText: caption
						,src: GetFlickrHref(link)
						,numberPosition: "caption"
						,lang: {
							number: "Image %1 of %2"
						}
					});
				}
				
			} else if (image.hasClass('flickr-video_player')) {
				// Flash Player
				this.onclick = function() {
					return hs.htmlExpand(this, {
						objectType: 'swf'
						, objectWidth: 640
						, objectHeight: 480
						, src: GetFlickrHref(link)
						, allowSizeReduction: false
						, width: 640
					} );
				}
				
			} else if (image.hasClass('flickr-site_mp4')) {
				// HTML 5 Video
				this.onclick = function() {
					return hs.htmlExpand(this, {
						objectType: 'iframe'
						, objectLoadTime: 'before'
						, wrapperClassName: 'no-footer'
						, src:  '{0}?vid={1}'.format(WFM_iFrameSRC, GetPhotoID(image))
						, width: 700
						, height: 520
					} );
				}
				
			} else {
				// Single Image
				this.onclick = function() {
					return hs.expand(this, {
						captionText: caption
						,src: GetFlickrHref(link)
					});
				}
				
			}
			
		});
	}
	
})(jQuery);

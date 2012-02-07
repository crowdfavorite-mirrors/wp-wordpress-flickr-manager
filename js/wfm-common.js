var wfmJS = jQuery.noConflict();

String.prototype.format = function() {
    var formatted = this;
    
    wfmJS.each(arguments, function(k, v) {
    	formatted = formatted.replace("{" + k + "}", v);
    });
    
    return formatted;
};

function GetFlickrHref(link) 
{
	var image = wfmJS('img:first', link);
	var href = '';
	
	if(image.hasClass('flickr-original') || image.hasClass('flickr-site_mp4') || image.hasClass('flickr-video_player')) {
		href = image.attr('longdesc');
	} else {
		var imageSize = '';
		
		if(image.hasClass('flickr-large')) {
			imageSize = '_b';
		} else if(image.hasClass('flickr-small')) {
			imageSize = '_m';
		} else if(image.hasClass('flickr-medium_640')) {
			imageSize = '_z'
		} 
		
		href = image.attr('src').replace(/(_[stmzb])?\.jpg$/, '{0}.jpg'.format(imageSize));
	}
	
	return href;
}

function GetTitle(link)
{
	var anchor = wfmJS(link);
	var caption = anchor.attr('title');
	if(WFM_CaptionLink == 'true') {
			caption += ' <a href="{0}" title="{1}"><img src="{2}/images/flickr-media.gif" alt="{3}" /></a>'
								.format(anchor.attr("href"), WFM_ViewOnFlickr, WFM_PluginDir, WFM_ViewOnFlickr);
	}
	
	return caption;
}

function GetDescription(link) {
	var image = wfmJS('img:first',link);
	var title = image.attr('title');
	var license = wfmJS('#license-' + GetPhotoID(image));
	if(license.size() > 0) {
		title +=  license.html();
	}
	
	return title; 
}

function GetPhotoID(image) {
	return /\/([^_\/]+)[^\/]*$/g.exec(wfmJS(image).attr('src'))[1];
}

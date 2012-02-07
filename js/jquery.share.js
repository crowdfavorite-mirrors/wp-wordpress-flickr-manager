/* Copyright (c) 2010 Christian Pfeiffer (christian.pfeiffer.k@gmail.com || http://www.fancydesign.de)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Last Change: 2010-11-18
 * Version: 1.01
 * 
 */


 
(function($){
	$.fn.share = function(options) {
		var defaults = {			
			url: 			window.location,								// url of social bookmark, default is current url, use 'http://www.example.com' for individual url	
			title:			$('title').html(),								// title of social bookmark, default is window title, use 'example' for individual title	
			description: 	$('meta[name=description]').attr("content"),	// description of social bookmark, default is <meta>-description, use 'example' for individual description							
			tags:			$('meta[name=keywords]').attr("content"),		// tags of social bookmark, default is <meta>-keywords, use 'example' for individual tags
			services:		['all'], 										// 'all' for [all] services, or ['facebook', 'delicious', ..] for specific services	
			img_size:		16,												// width and height of <img>-tag (px)
			img_alt: 		'$service',										// alt-text of <img>-tag, $service will be replaced by the name of the service
			a_target:		'_blank',										// target-attribute of <a>-tag
			a_title:		'share on $service'								// title-attribute of <a>-tag, $service will be replaced by the name of the service							
   		};			
   		var options = $.extend(defaults, options);			
   		return this.each(function() {			
			var target = $(this);			
			var services = {
				facebook:	['http://www.facebook.com/sharer.php?u=' + escape(options.url) + '&t=' + options.title, 'http://static.ak.fbcdn.net/rsrc.php/z7/r/5875srnzL-I.ico'],
				twitter:	['http://twitter.com/home?status=' + options.title + ':+' + options.url, 'http://a1.twimg.com/a/1289607957/images/favicon.ico'],
				delicious: 	['http://del.icio.us/post?url=' + options.url + '&title=' + options.title + '&tags=' + options.tags + '&notes=' + options.description, 'http://www.delicious.com/favicon.ico'],	
				digg:		['http://digg.com/submit?phase=2&url=' + options.url + '&title=' + options.title, 'http://cdn1.diggstatic.com/img/favicon.a015f25c.ico'],			
				google:		['http://www.google.com/bookmarks/mark?op=add&bkmk=' + options.url + '&title=' + options.title + '&labels=' + options.tags + '&annotation=' + options.description, 'http://www.google.com/favicon.ico'],
				yahoo:		['http://bookmarks.yahoo.com/toolbar/savebm?u=' + options.url + '&t=' + options.title + "&d=" + options.description, 'http://l.yimg.com/i/i/eu/aut/favic1.ico'],
				misterwong:	['http://www.mister-wong.com/index.php?action=addurl&bm_url=' + options.url + '&bm_description=' + options.title + '&bm_tags=' + options.tags + '&bm_notice=' + options.description, 'http://www.mister-wong.de/favicon.ico'],
				netvibes:	['http://www.netvibes.com/share?url=' + options.url + '&title=' + options.title, 'http://cdn.netvibes.com/favicon.ico'],
				linkedin:	['http://www.linkedin.com/shareArticle?mini=true&url=' + options.url + '&title=' + options.title + '&source=&summary=' + options.description, 'http://www.linkedin.com/img/favicon_v2.ico'],				
				stumbleupon:['http://www.stumbleupon.com/submit?url=' + options.url + '&title=' + options.title, 'http://cdn.stumble-upon.com/favicon.ico']
			}		
			if(options.services == "all") { 
				options.services = new Array();
			    for(n in services) options.services.push(n);				
			}		
			$.each(options.services, function(index, service) { 				
				if(services[service] != undefined) {
					var content = '<a href="' + services[service][0] + '"';
					if(options.target != "") content += ' target="' + options.a_target + '"';
					if(options.title != "") content += ' title="' + (options.a_title).replace(/\$service/g, this) + '"';
					content += '><img width="' + options.img_size + '" height="' + options.img_size + '" border="0" src="' + services[service][1] + '"';  
					if(options.alt != "") content += ' alt="' + (options.img_alt).replace(/\$service/g, this) + '"';						
					content += ' /></a>';										
					target.append(content);
				}
			});
  		});
 	};  
})(jQuery);
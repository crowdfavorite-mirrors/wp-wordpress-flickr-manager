
jQuery(function($) {

    $(document).ready(function() {
        var imgRel = '';
        var siteURL = WFM_PluginDir.substr(0, WFM_PluginDir.indexOf('/wp-content'));
        tb_pathToImage = siteURL + tb_pathToImage.substr(2);
        tb_closeImage  = siteURL + tb_closeImage.substr(2);
        
        var oldremove = window.tb_remove;
        window.tb_remove = function() {
            oldremove();
            
            $("a[rel='myImageGroup']").each(function() {
                var temp = $(this);
                temp.attr('href', temp.attr('temp')).attr('rel', imgRel);
            });
        }
        
        $('a[rel*=flickr-mgr]').click(function() {
            var link = $(this);
            var title = GetTitle(link) + ' ' + GetDescription(link);
            imgRel = link.attr('rel');
            
            if(/\[([^\]]*)\]$/.test(imgRel)) {
                // photo sets
                
                link.closest('div').find("a[rel='" + imgRel + "']").each(function() {
                    var temp = $(this);
                    temp.attr('temp', temp.attr('href')).attr('href', GetFlickrHref(this)).attr('rel', 'myImageGroup');
                    
                });
                
                tb_show(title,GetFlickrHref(link),'myImageGroup');
                
            } else {
                // individual photos and videos
                
                var href = GetFlickrHref(link);
                                        
                var image = link.find('img:first');
                
                if (image.hasClass('flickr-video_player')) {
                    // Flash video
                    
                    var content = '<object type="application/x-shockwave-flash" data="' + href + '" width="640" height="480">';
                    content += '<param name="movie" value="' + href + '" />';
                    content += '<param name="allowFullScreen" value="true"></param>';
                    content += '<param name="quality" value="best" />';
                    content += '<param name="scale" value="noScale" />';
                    content += '<param name="salign" value="TL" />';
                    content += '<param name="FlashVars" value="playerMode=embedded" />';
                    content += '<param name="wmode" value="transparent" />';
                    content += '<p>You don\'t have a flash player so you can\'t view the video</p>';
                    content += '</object>';
                    
                    $('body').remove('#myFlashMovie').append('<div id="myFlashMovie" style="display:none;">' + content + '</div>');
                    href = '#TB_inline?width=650&height=490&inlineId=myFlashMovie';
                } else if(image.hasClass('flickr-site_mp4')) {
                    // HTML 5 video
                    
                    href = WFM_PluginDir + 'overlays/HighslideOverlay.php?width=650&height=490&vid=' + GetPhotoID(image);
                    
                }
                
                tb_show(title, href,false);
            }
            
            return false;
        });
    }); 
});

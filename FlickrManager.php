<?php
/*
Plugin Name: Flickr Manager
Plugin URI: http://tgardner.net/wordpress-flickr-manager/
Description: Handles uploading, modifying images on Flickr, and insertion into posts.
Version: 3.0.1
Author: Trent Gardner
Author URI: http://tgardner.net/

Copyright 2007  Trent Gardner  (email : trent.gardner@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/ 


if(version_compare(PHP_VERSION, '4.4.0') < 0) 
	wp_die(sprintf(__('You are currently running %s and you must have at least PHP 4.4.x in order to use Flickr Manager!', 'flickr-manager'), PHP_VERSION));

define('WFM_OVERLAY_DIR','overlays');

if(!class_exists('FlickrManager')) :

// Load Dependencies
require_once(dirname(__FILE__) . '/lib/inc.flickr.php');
require_once(dirname(__FILE__) . '/BasePlugin.php');

class FlickrManager extends BasePlugin
{
	var $flickr;
	var $cache_table;
	var $cache_dir;
	
	function FlickrManager() 
	{
		parent::__construct();
		
		$this->ApplyDefaults();
		$this->CreateFlickrHandler($this->settings['api_key'], $this->settings['secret']);
		
		add_action('init', array(&$this, 'RegisterWidgets'));
		
		//Additional links on the plugin page
		add_filter('plugin_row_meta', array(&$this, 'RegisterPluginLinks'),10,2);
		
		// Clear previous version cache
		register_activation_hook( __FILE__, array(&$this, 'ClearCache') );
		
		// Clean up after our self
		register_deactivation_hook( __FILE__, array(&$this, 'ClearCache') );
		
		// Register the photo shortcodes
		$this->CreateShortcodes();
		
		if(!is_admin() && !empty($this->settings['photo_share'])) {
			$this->EnablePhotoSharing();
		}
	}
	
	function ApplyDefaults() 
	{
		$defaults = array(
			'api_key' => '0d3960999475788aee64408b64563028'
			,'secret' => 'b1e94e2cb7e1ff41'
			,'per_page' => 5
            ,'cache' => 'db'
            ,'recent_widget' => array(
									'title' => __('Recent Photos', 'flickr-manager')
									,'photos' => 10
									,'viewer' => (!empty($this->settings['lightbox_default'])) ? $this->settings['lightbox_default'] : ''
								)
		);
		
		foreach($defaults as $k => $v) {
			if(empty($this->settings[$k])) {
				$this->settings[$k] = $v;
			}
		}
		
		global $wpdb;
		$this->cache_table = $wpdb->prefix . "flickr";
		$this->cache_dir = dirname(__FILE__) . '/cache/';
	}
	
	function CreateFlickrHandler($api_key, $secret) 
	{
		global $wpdb;
		
		$this->flickr = new phpFlickr($api_key, $secret);
        
        if($this->settings['cache'] == 'fs') {
        	// Enable file system caching
            $this->flickr->enableCache('fs', $this->cache_dir);
        } else {
        	// Enable database caching
            if ( isset($wpdb->charset) && !empty($wpdb->charset) ) {
				$charset = ' DEFAULT CHARSET=' . $wpdb->charset;
			} elseif ( defined(DB_CHARSET) && DB_CHARSET != '' ) {
				$charset = ' DEFAULT CHARSET=' . DB_CHARSET;
			} else {
				$charset = '';
			}
			
            $query = " CREATE TABLE IF NOT EXISTS `$this->cache_table` (
							`request` CHAR( 35 ) NOT NULL ,
							`response` MEDIUMTEXT NOT NULL ,
							`expiration` DATETIME NOT NULL ,
							INDEX ( `request` )
						) " . $charset;
            
            $wpdb->query($query);
            
            $this->flickr->enableCache('custom', array(array(&$this, 'CacheGet'), array(&$this, 'CacheSet')));
            
        }
        
		$this->flickr->setToken($this->GetSetting('token'));
	}
	
	function ClearCache() {
		global $wpdb;
		
		$wpdb->query("DROP TABLE `$this->cache_table`");
		
		if ($dir = opendir($this->cache_dir)) {
			while ($file = readdir($dir)) {
				if (substr($file, -6) == '.cache') {
					unlink($this->cache_dir . '/' . $file);
				}
			}
		}
	}
	
	function CacheGet($key) {
		global $wpdb;
		$result = $wpdb->get_row(sprintf('SELECT * FROM `%s` WHERE request = "%s" AND expiration >= NOW()'
											, $this->cache_table
											, $wpdb->escape($key)));
		
		if ( is_null($result) ) return false;
		return $result->response;
	}
	
	function CacheSet($key, $value, $expire) {
		global $wpdb;
		$query = '
			INSERT INTO `' . $this->cache_table . '`
				(
					request, 
					response, 
					expiration
				)
			VALUES
				(
					"' . $wpdb->escape($key) . '", 
					"' . $wpdb->escape($value) . '", 
					FROM_UNIXTIME(' . (time() + (int) $expire) . ')
				)
			ON DUPLICATE KEY UPDATE 
				response = VALUES(response),
				expiration = VALUES(expiration)
			
		';
		$wpdb->query($query);
	}
	
	function CreateShortcodes()
	{
		
		add_shortcode('flickr', array(&$this, 'RenderFlickrShortcode'));
		add_shortcode('flickrset', array(&$this, 'RenderFlickrsetShortcode'));
		
	}
	
	function EnablePhotoSharing() {
		
		add_action('init', array(&$this, 'LoadSharingJavascript'));
		add_action('wp_head', array(&$this, 'RenderPhotoSharing'));
		
	}
	
	function LoadSharingJavascript() {
		
		wp_enqueue_script('jquery-sharing',plugins_url('/js/jquery.share.js', __FILE__), array('jquery'));
		wp_enqueue_script('wfm-sharing',plugins_url('/js/wfm-share.js', __FILE__), array('jquery'));
	
	}
	
	function RegisterWidgets()
	{
		// Register recent photo's widget
		if(function_exists('register_sidebar_widget'))
			register_sidebar_widget('Recent Flickr Photos', array(&$this, 'RenderRecentPhotoWidget'));
			
		if(function_exists('register_widget_control'))
			register_widget_control ( 'Recent Flickr Photos', array(&$this, 'RenderRecentPhotoWidgetControl'));
	}
	
	
	function RegisterPluginLinks($links, $file) {
		$base = $this->GetBaseName();
		
		if($file == $base) {
			$links[] = sprintf('<a href="%s">%s</a>', admin_url("/options-general.php?page=" . $base), __('Settings','flickr-manager'));
			$links[] = '<a href="http://support.tgardner.net/" title="Support">Support</a>';
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=trent%2egardner%40gmail%2ecom&amp;lc=AU&amp;currency_code=AUD">Donate</a>';
		}
		
		return $links;
	}
	
	function RenderPhotoSharing()
	{
		$services = sprintf("['%s']",implode("','", $this->settings['photo_share']));
		echo "<script type='text/javascript'>\n//<![CDATA[\n";
		echo sprintf("var WFM_ShareServices = %s;\n", $services);
		echo "//]]>\n</script>";
	}
	
	function GetPhotos($page, $owner, $filter = null) 
	{
		$params = array(
			'extras' => 'license,owner_name,original_format'
			,'per_page' => $this->settings['per_page']
			,'page' => $page
			,'auth_token' => $this->settings['token']
			,'text' => $filter
			,'user_id' => $owner
			,'media' => (!empty($owner)) ? 'all' : 'photos'
		);
		
		if($owner != null && $this->settings['privacy_filter'] == 'true')
			$params['privacy_filter'] = 1;
		
		// Disable caching incase of new photos
		$this->flickr->request("flickr.photos.search", $params, true);
		return $this->flickr->parsed_response ? $this->flickr->parsed_response['photos'] : false;
	}
	
	function SavePhoto($photoid, $title, $description, $tags) 
	{
		if(empty($photoid)) return 0;
		
		$this->flickr->photos_setMeta($photoid, stripcslashes($title), stripcslashes($description));
		$this->flickr->photos_setTags($photoid, $tags);
		
		return $photoid;
	}
	
	function GetBaseName() 
	{
		return plugin_basename(__FILE__);
	}
	
	function GetSignature($params) {
		ksort($params);
		
		$api_sig = $this->settings['secret'];
		
		foreach ($params as $k => $v){
			$api_sig .= $k . $v;
		}
		
		return md5($api_sig);
	}
	
	function RenderFlickrShortcode($args) {
		$photo = $this->flickr->photos_getInfo($args['id']);
		$photo = $photo['photo'];
		
		$rel = '';
		if($args['overlay'] == 'true') {
			if(empty($args['group'])) {
				$rel = 'flickr-mgr';
			} else {
				$rel = sprintf('flickr-mgr[%s]', $args['group']);
			}
		}
		
		$markup = '';
		
		if($photo['media'] == 'video' && in_array($args['thumbnail'], array('video_player','site_mp4'))) {
			$markup = $this->RenderVideo($args['id'], ($args['thumbnail'] == 'site_mp4') ? 'html5': 'flash');
		} else {
			$url = $photo['urls']['url'][0]['_content'];
			$original = ($args['size'] == 'original') ? $this->flickr->buildPhotoURL($photo, 'original') : '';
			
            $class = ($args['overlay'] == 'true') ? sprintf('flickr-%s', $args['size']) : '';
            if(!empty($args['align']) && $args['align'] != 'none') 
                $class .= " align" . $args['align'];
            
			if(in_array($args['size'], array('video_player','site_mp4','mobile_mp4'))) {
				
				$sizes = $this->flickr->photos_getSizes($args['id']);
				foreach($sizes as $size) {
					if(strtolower(str_replace(' ', '_', $size['label'])) == $args['size'] && $args['size'] == 'mobile_mp4') {
						$url = $size['source'];
						$rel = '';
						break;
					} elseif(strtolower(str_replace(' ', '_', $size['label'])) == $args['size']) {
						$original = $size['source'];
						$rel = (!empty($rel)) ? 'flickr-mgr' : '';
						break;
					}
				}
			} 
			
			
			$settings = array(
				'url' => $url
				,'title' => $photo['title']
				,'rel' => $rel
				,'thumbnail' => $this->flickr->buildPhotoURL($photo, $args['thumbnail'])
				,'class' => $class
				,'description' => $photo['description']
				,'original' => $original
			);
			
			$markup = $this->RenderPhoto($settings);
			
			if($photo['owner']['nsid'] != $this->settings['nsid']) {
				$licenses = $this->flickr->photos_licenses_getInfo();
				
				foreach($licenses as $license) {
					if($license['id'] == $photo['license']) {
						$markup .= sprintf('<br /><small id="license-%s"><a href="%s" title="%s" rel="license" onclick="return false;"><img src="%s" alt="%s" /></a> 
									by %s</small>'	, $photo['id']
													, $license['url']
													, $license['name']
													, plugins_url('/images/creative_commons_bw.gif', __FILE__)
													, $license['name']
													, $photo['owner']['username']);
					}
				}
			}
		}
		
		return $markup;
	}
	
	function RenderFlickrsetShortcode($args) 
	{
		$priv = ($this->settings['privacy_filter'] == 'true') ? 1 : null;
		$photoset = $this->flickr->photosets_getPhotos($args['id'], 'original_format,description', $priv, $args['photos']);
		
		$markup = '';
		foreach ($photoset['photoset']['photo'] as $photo) {
			$settings = array(
				'url' => sprintf('http://www.flickr.com/photos/%s/%s/',$photoset['photoset']['owner'],$photo['id'])
				,'title' => $photo['title']
				,'rel' => ($args['overlay'] == 'true') ? sprintf('flickr-mgr[%s]',$args['id']) : ''
				,'thumbnail' => $this->flickr->buildPhotoURL($photo, $args['thumbnail'])
				,'class' => ($args['overlay'] == 'true') ? sprintf('flickr-%s', $args['size']) : ''
				,'description' => $photo['description']
				,'original' => ($args['size'] == 'original') ? $this->flickr->buildPhotoURL($photo, 'original') : ''
			);
			
			$markup .= $this->RenderPhoto($settings);
		}
		
		return sprintf('<div class="flickrGallery">%s</div>', $markup);
	}
	
	function RenderPhoto($info) 
	{
		$markup = $this->settings['before_wrap'];
		
		$markup .= sprintf('<a href="%s" title="%s" rel="%s" class="flickr-image">'
								, htmlspecialchars($info['url'])
								, htmlspecialchars($info['title'])
								, $info['rel']);
		
		$markup .= sprintf('<img src="%s" alt="%s" class="%s" title="%s" longdesc="%s" />'
								, $info['thumbnail']
								, htmlspecialchars($info['title'])
								, $info['class']
								, htmlspecialchars($info['description'])
								, $info['original']);
		
		$markup .= '</a>' . $this->settings['after_wrap'];
		
		return $markup;
	}
	
	function RenderVideo($vid, $type = 'flash', $sizes = null) {
		if(empty($sizes)) {
			$sizes = $this->flickr->photos_getSizes($vid);
		}
		
		if($type == 'html5') {
			
			$video = array();
			foreach($sizes as $v) {
				if($v['label'] == 'Site MP4') {
					$video = $v;
					break;
				}
			}
			
			return sprintf('<video width="%s" height="%s" controls><source src="%s" type="video/mp4">%s</video>'
							, $video['width']
							, $video['height']
							, $video['source']
							, $this->RenderVideo($vid, 'flash', $sizes));
			
		} else {
		
			$video = array();
			foreach($sizes as $v) {
				if($v['label'] == 'Video Player') {
					$video = $v;
					break;
				}
			}
			
			return sprintf('<object width="%s" height="%s" data="%s" type="application/x-shockwave-flash" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">
								<param name="flashvars" value="flickr_show_info_box=false"></param>
								<param name="movie" value="%s"></param>
								<param name="allowFullScreen" value="true"></param>
							</object>', $video['width'], $video['height'], $video['source'], $video['source']);
								
		}
	}
	
	function RenderRecentPhotoWidget($args) 
	{
		$settings = $this->settings['recent_widget'];
		
		extract($args);
		
		$title = '';
		if(!empty($settings['title'])) {
			$title = sprintf('%s<a href="http://www.flickr.com/photos/%s/"><img src="%s" border="0" alt="Flickr" /></a> %s%s'
								,$before_title
								,$this->settings['nsid']
								,plugins_url('/images/flickr-media.gif', __FILE__)
								,$settings['title']
								,$after_title);
		}
		
		$photos = $this->flickr->people_getPublicPhotos($this->settings['nsid'], null, 'icon_server,original_format', $settings['photos'], 1);
		
		$markup = '';
		$rel = (!empty($settings['viewer'])) ? 'flickr-mgr[recent]' : '';
		
		foreach ($photos['photos']['photo'] as $photo) {
			$settings = array(
				'url' => sprintf('http://www.flickr.com/photos/%s/%s/',$photo['owner'],$photo['id'])
				,'title' => $photo['title']
				,'rel' => $rel
				,'thumbnail' => $this->flickr->buildPhotoURL($photo, 'square')
				,'class' => (!empty($settings['viewer'])) ? sprintf('flickr-%s', $settings['viewer']) : ''
				,'description' => $photo['description']
				,'original' => ($args['size'] == 'original') ? $this->flickr->buildPhotoURL($photo, 'original') : ''
			);
			
			$markup .= $this->RenderPhoto($settings);
		}
		
		$markup = $before_widget . $title . sprintf('<div style="text-align: center" id="wfm-recent-widget">%s</div>', $markup) . $after_widget;
		
		echo $markup;
	}
	
	function RenderRecentPhotoWidgetControl() 
	{
		$settings = $this->settings['recent_widget'];
		
		if(isset($_REQUEST['flickr-title'])) {
			$settings['title'] = $_REQUEST['flickr-title'];
			$settings['photos'] = (is_numeric($_REQUEST['flickr-photos'])) ? $_REQUEST['flickr-photos'] : 10;
			$settings['viewer'] = $_REQUEST['flickr-viewer'];
		}
		
		$this->SaveSetting('recent_widget', $settings);
		
		$options = array( ''		=> __('Disable', 'flickr-manager'),
						  'small'	=> __('Small', 'flickr-manager'), 
						  'medium'	=> __('Medium', 'flickr-manager'), 
						  'large'	=> __('Large', 'flickr-manager'));
		
		if($this->settings['is_pro'] == '1') 
			$options = array_merge($options, array('original' => __('Original', 'flickr-manager')));
		
		
		$markup = sprintf('<p><label for="flickr-title">%s:</label>
							<input id="flickr-title" class="widefat" type="text" value="%s" name="flickr-title" /></p>'
								, __('Title', 'flickr-manager')
								,htmlspecialchars($settings['title']));
		
		$markup .= sprintf('<p><label for="flickr-photos">%s:</label>
							<input id="flickr-photos" class="widefat" type="text" value="%s" name="flickr-photos" /></p>'
								, __('# Photos', 'flickr-manager')
								, htmlspecialchars($settings['photos']));
	
		$markup .= sprintf('<p><label for="flickr-viewer">%s:</label>
							<select name="flickr-viewer" class="widefat" id="flickr-viewer">'
								, __('Image Viewer', 'flickr-manager'));
		
		foreach ($options as $k => $v) {
			$markup .=  sprintf('<option value="%s" %s >%s</option>'
											,$k
											,($settings['viewer'] == $k) ? 'selected="selected"' : ''
											, htmlspecialchars($v));
		}
		
		$markup .= sprintf('</select><small>%s</small></p>'
								, __('This option will determine the image loaded into the Javascript viewer.', 'flickr-manager'));
								
		echo $markup;
	}
}

endif;



// Load plugin
global $flickr_manager, $flickr_panel, $flickr_admin, $flickr_overlay;
$flickr_manager = new FlickrManager();

// Load Modules
if(is_admin()) {
	// Load Media Panel
	require_once(dirname(__FILE__) . '/FlickrPanel.php');
	$flickr_panel = new FlickrPanel();
	
	// Load Administration Pages
	require_once(dirname(__FILE__) . '/FlickrAdmin.php');
	$flickr_admin = new FlickrAdmin();
} else {
	// Load Image Viewer
	require_once(dirname(__FILE__) . '/OverlayLoader.php');
	$flickr_overlay = new OverlayLoader(WFM_OVERLAY_DIR);
}
?>
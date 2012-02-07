<?php
// Load Dependencies
require_once(dirname(__FILE__) . '/lib/inc.templater.php');

// Load JSON support for PHP < 5.2
include_once(dirname(__FILE__) . '/lib/inc.json.php');

if(!class_exists('FlickrPanel')) :

class FlickrPanel
{

	function __construct() 
	{
		add_action('init', array(&$this, 'Initialize'));
	}
	
	function Initialize() 
	{
		
		add_action('media_buttons', array(&$this, 'RenderMediaButton'), 20);
		add_action('media_upload_flickr', array(&$this, 'CreateMediaFrame'));
		add_action('media_upload_flickr_public', array(&$this, 'CreateMediaFrame'));
		add_action('media_upload_flickr_upload', array(&$this, 'CreateMediaFrame'));
		add_action('media_upload_flickr_sets', array(&$this, 'CreateMediaFrame'));
		
		add_action('admin_print_styles-media-upload-popup', array(&$this, 'LoadCSS'));
		add_action('admin_print_scripts-media-upload-popup', array(&$this, 'LoadJavascript'));
		
		add_filter('wp_handle_upload', array(&$this, 'UploadPhoto'));
		
	}
	
	function LoadCSS()
	{
		global $type;
		
		if($type == 'flickr') {
			
			wp_admin_css('css/media');
			wp_enqueue_style('wfm-media-panel-css', plugins_url("/css/media_panel.css", __FILE__));
			
		}
	}
	
	function LoadJavascript()
	{
		global $flickr_manager, $type;
		
		if($type == 'flickr') {
		
			wp_enqueue_script('wfm-common', plugins_url('/js/wfm-common.js', __FILE__), array('jquery'), '20110429');
			wp_enqueue_script('wfm-media-panel', plugins_url('/js/MediaPanel.js', __FILE__), array('jquery'), '20110429');
			
			$params = array(
				'WFMPath' => $flickr_manager->absoluteURL
				,'WFMAjaxURL' => plugins_url(basename(__FILE__), __FILE__)
				,'WFMAjaxNonce' => wp_create_nonce('wfm_ajax-action')
				
				,'loadingImage' => plugins_url('/images/loading.gif', __FILE__)
				,'loadingText' => __('Loading...', 'flickr-manager')
				,'deleteConfirm' => __('Are you sure you want to delete this?', 'flickr-manager')
				,'wrapBefore' => $flickr_manager->settings["before_wrap"]
				,'wrapAfter' => $flickr_manager->settings["after_wrap"]
			);
			
			$scriptTag = "<script type='text/javascript'>\n// <![CDATA[\n";
			foreach($params as $k => $v) {
				$scriptTag .= sprintf("var %s = '%s';\n", $k, $v);
			}
			$scriptTag .= "//]]>\n</script>";
			
			echo $scriptTag;
		}
	}
	
	function CreateMediaFrame()
	{
		
		wp_iframe(array(&$this, 'RenderMediaFrame'));
		
	}
	
	
	function RenderMediaButton() 
	{
		global $post_ID, $temp_ID, $flickr_manager;
		
		$uploading_iframe_ID = (int) ($post_ID == 0 ? $temp_ID : $post_ID);
		$media_upload_iframe_src = "media-upload.php?post_id=$uploading_iframe_ID";
		$flickr_upload_iframe_src = apply_filters('media_flickr_iframe_src', "$media_upload_iframe_src&amp;type=flickr");
		
		$flickr_title = __('Add Flickr Photo', 'flickr-manager');

		$media_button = '<a href="%s&amp;tab=flickr&amp;TB_iframe=true&amp;height=500&amp;width=640" 
						class="thickbox" title="%s"><img src="%s/images/flickr-media.gif" alt="%s" /></a>';

		echo sprintf($media_button, $flickr_upload_iframe_src, $flickr_title, $flickr_manager->absoluteURL, $flickr_title);
		
	}
	
	function RenderMediaFrame()
	{
		global $flickr_manager, $tab, $type;
	
		add_filter('media_upload_tabs', array(&$this, 'GetMediaTitles'));
		
		if(!$flickr_manager->flickr->auth_checkToken()) {
			$error = new Page('templates/Error.tpl');
			echo $error->render(array(
				'Error' => sprintf('%s <a href="%s">Settings->Flickr</a>'
									,__('Error: Please authenticate through ', 'flickr-manager')
									,admin_url("/options-general.php?page=" . $flickr_manager->GetBaseName()))
			));
			return;
		}
		
		switch ($tab) {
			case 'flickr_upload':
				$this->MediaUploadPanel();
				break;
			default:
				$this->MediaPhotoPanel();
				break;
		}
		
	}
	
	
	function GetMediaTitles() {
		return array(
			'flickr' =>  __('Photo stream', 'flickr-manager'),
			'flickr_sets' => __('Albums', 'flickr-manager'),
			'flickr_public' => __('Everyone', 'flickr-manager'),
			'flickr_upload' => __('Upload', 'flickr-manager')
		);
	}
	
	function MediaPhotoPanel()
	{
		global $tab, $flickr_manager;
		$titles = $this->GetMediaTitles();
		
		media_upload_header();
		
		$params = array(
			'Title' => $titles[$tab]
			,'Search' => __('Search', 'flickr-manager')
			,'AbsoluteURL' => $flickr_manager->absoluteURL
			,'Tab' => $tab
		);
		
		$page = new Page('templates/MediaPanel.tpl');
		echo $page->render($params);
	}
	
	function MediaUploadPanel() 
	{
		global $tab, $flickr_manager;
		$titles = $this->GetMediaTitles();
		
		add_filter('media_upload_form_url', array(&$this, 'UploadFormFilter'));
		
		media_upload_image();
	}
	
	function UploadFormFilter($url) {
		$patterns = array('/type=[^&]+/i', '/tab=[^&]+/i');
		$replacements = array('type=flickr', 'tab=flickr_upload');
		
		return preg_replace($patterns, $replacements, $url);
	}
	
	function UploadPhoto($file) {
		global $flickr_manager;
		$type = $_REQUEST['type'];
		
		if($type == "flickr") {
			$pid = $flickr_manager->flickr->sync_upload($file['file'], basename($file['file']), '', '');
			// Delete file
			unlink($file['file']);
			
			if(!empty($pid) && !$flickr_manager->flickr->error_code) {
				$file['error'] = "flickr_$pid";
			} else {
				return wp_handle_upload_error($file,  __('An error occurred while trying to upload your photo', 'flickr-manager'));
			}
		}
		
		return $file;
	}
	
	function func_GetPhotos() {
		global $flickr_manager;
		
		$page = (empty($_POST['page'])) ? 1 : $_POST['page'];
		$filter = (empty($_POST['filter'])) ? null : $_POST['filter'];
		$owner = ($_POST['tab'] == 'flickr_public') ? null : $flickr_manager->settings['nsid'];
		
		$photos = $flickr_manager->GetPhotos($page, $owner, $filter);
		$images = array();
		
		if(is_array($photos['photo']) && !empty($photos['photo'])) {
			foreach($photos['photo'] as $photo) {
				array_push($images, array(
					'square' => $flickr_manager->flickr->buildPhotoURL($photo, 'square')
					,'title' => htmlspecialchars($photo['title'])
				));
			}
		}
		
		$data = array(
			'photos' => $images
			,'pages' => $photos['pages']
		);
		
		echo json_encode($data);
	}
	
	function func_GetPhotoset($photoset) {
		global $flickr_manager;
		$photosets = $flickr_manager->flickr->photosets_getList($flickr_manager->settings['nsid']);
		
		if(empty($photoset) && !empty($photosets['photoset'])) {
			$photoset = $photosets['photoset'][0]['id'];
		}
		
		$priv = ($flickr_manager->settings['privacy_filter'] == 'true') ? 1 : NULL;
		$page = (empty($_POST['page'])) ? 1 : $_POST['page'];
		
		$photos = $flickr_manager->flickr->photosets_getPhotos($photoset
																,'original_format,date_upload,owner_name'
																,$priv
																,$flickr_manager->settings['per_page']
																,$page);
		
		$images = array();
		
		if(is_array($photos['photoset']) && !empty($photos['photoset'])) {
			foreach ($photos['photoset']['photo'] as $photo) {
				array_push($images, array(
					'square' => $flickr_manager->flickr->buildPhotoURL($photo, 'square')
					,'title' => htmlspecialchars($photo['title'])
				));
			}
		}
		
		$data = array(
			'photos' => $images
			,'pages' => $photos['photoset']['pages']
			,'photosets' => $photosets['photoset']
			,'insertText' => __('Insert Set','flickr-manager')
		);
		
		echo json_encode($data);
	}
	
	function func_PhotoInfo($pid)
	{
		global $flickr_manager;
		
		if(!empty($pid)) {
			
			$page = new Page('templates/InsertPhoto.tpl');
			
			// Get photo info and bypass caching in case the image has been changed
			$flickr_manager->flickr->request('flickr.photos.getInfo',  array('photo_id' => $pid), true);
			$photo = $flickr_manager->flickr->parsed_response ? $flickr_manager->flickr->parsed_response : false;
			
			$sizes = $flickr_manager->flickr->photos_getSizes($pid);
			$photo = $photo['photo'];
			
			$tags = "";
			foreach($photo['tags']['tag'] as $tag) {
				$tags .= sprintf("%s ", $tag['raw']);
			}
			
			$sizeMarkup = FlickrPanel::GetSizeOptions('imageSize', $sizes, 'thumbnail');
			$overlayOptions = FlickrPanel::GetSizeOptions('overlaySize', $sizes, $flickr_manager->settings['lightbox_default']);
			
			$params = array(
				'Thumbnail' => $flickr_manager->flickr->buildPhotoURL($photo, 'thumbnail')
				,'Title' =>  htmlspecialchars($photo['title'])
				,'File' => basename($flickr_manager->flickr->buildPhotoURL($photo, 'medium'))
				,'Uploaded' => htmlspecialchars(date('Y-m-d H:i:s', intval($photo['dateuploaded'])))
				,'TitleLabel' => __('Title', 'flickr-manager')
				,'TagsLabel' => __('Tags', 'flickr-manager')
				,'Tags' => $tags
				,'SpaceSeparated' => __('Space separated list', 'flickr-manager')
				,'DescriptionLabel' => __('Description', 'flickr-manager')
				,'Description' => htmlspecialchars(trim($photo['description']))
				,'LinkLabel' => __('Link URL', 'flickr-manager')
				,'Link' => htmlspecialchars($photo['urls']['url'][0]['_content'])
				,'AlignmentLabel' =>  __('Alignment', 'flickr-manager')
				,'AlignNone' => __('None', 'flickr-manager')
				,'AlignLeft' => __('Left', 'flickr-manager')
				,'AlignCentre' => __('Center', 'flickr-manager')
				,'AlignRight' => __('Right', 'flickr-manager')
				,'SizeLabel' => __('Size', 'flickr-manager')
				,'Sizes' => $sizeMarkup
				,'InsertLabel' => __('Insert into Post', 'flickr-manager')
				,'OwnerControls' => FlickrPanel::GetOwnerControls()
				,'DefaultOverlay' => ($flickr_manager->settings['lightbox_enable'] == "true") ? "checked" : ''
				,'OverlaySizes' => $overlayOptions
				,'EnableOverlay' => __('Enable Viewer', 'flickr-manager')
				,'OverlaySizeLabel' => __('Viewer Size', 'flickr-manager')
				,'SetName' => ''
				,'SetLabel' => __('Image Group', 'flickr-manager')
			);
			
			echo $page->render($params);
		}
	}
	
	function func_PhotosetInfo($photoset)
	{
		global $flickr_manager;
		
		if(!empty($photoset)) {
			$set = $flickr_manager->flickr->photosets_getInfo($photoset);
  			$buildSize = create_function('$id,$desc', 'return array("id"=>$id,"label"=>$desc);');
			
			$sizes = array( $buildSize('square', __('Square', 'flickr-manager')),
							$buildSize('thumbnail', __('Thumbnail', 'flickr-manager')), 
							$buildSize('small', __('Small', 'flickr-manager')), 
							$buildSize('medium', __('Medium', 'flickr-manager')), 
							$buildSize('large', __('Large', 'flickr-manager')));
				
			if($flickr_manager->settings['is_pro'] == '1') 
				$sizes[] = $buildSize('original', __('Original', 'flickr-manager'));
			
			$sizeMarkup = FlickrPanel::GetSizeOptions('imageSize', $sizes, 'thumbnail');
			$overlayOptions = FlickrPanel::GetSizeOptions('overlaySize', $sizes, $flickr_manager->settings['lightbox_default']);
			
			$page = new Page('templates/InsertPhotoset.tpl');
			
			$params = array(
				'TitleLabel' => __('Title', 'flickr-manager')
				,'DescriptionLabel' => __('Description', 'flickr-manager')
				,'SizeLabel' => __('Size', 'flickr-manager')
				,'EnableOverlay' => __('Enable Viewer', 'flickr-manager')
				,'OverlaySizeLabel' => __('Viewer Size', 'flickr-manager')
				,'NumPhotos' => __('* Leave blank to display the entire album.', 'flickr-manager')
				,'PhotosLabel' => __('Number of Photos', 'flickr-manager')
				,'Title' => htmlspecialchars($set['title'])
				,'Description' => htmlspecialchars($set['description'])
				,'Sizes' => $sizeMarkup
				,'OverlaySizes' => $overlayOptions
				,'DefaultOverlay' => ($flickr_manager->settings['lightbox_enable'] == "true") ? 'checked="checked"' : ''
				,'InsertLabel' => __('Insert into Post', 'flickr-manager')
			);
			
			echo $page->render($params);
			
		}
	}
	
	function GetOwnerControls() 
	{
		$markup = '<input class="button savePhoto" type="button" value="%s" />';
		$markup .= '<input class="button deletePhoto" type="button" value="%s" />';
		
		return sprintf($markup
						,__('Save', 'flickr-manager')
						,__('Delete', 'flickr-manager'));
	}
	
	function GetSizeOptions($name, $sizes, $default) {
		$html = '';
		
		foreach($sizes as $size) {
			$option = '<div class="image-size-item">
						<input id="[Name]-[ID]" type="radio" value="[ID]" name="[Name]" [Checked] />
						<label for="[Name]-[ID]">[Text]</label>
						<label class="help" for="[Name]-[ID]">[Desc]</label>
					</div>';
			
			if(empty($size['id'])) {
				$size['id'] = str_replace(" ","_",strtolower($size['label']));
			}
			
			$html .= str_replace(array('[ID]','[Text]','[Desc]','[Checked]','[Name]')
								,array(
									$size['id']
									,$size['label']
									,!empty($size['width']) ? sprintf("(%s &times; %s)", $size['width'], $size['height']) : ''
									,(strtolower($size['id']) == $default) ? 'checked="checked"' : ''
									,$name
								),$option);
			
		}
		
		return $html;
	}
	
	function func_SaveInfo() 
	{
		global $flickr_manager;
		echo $flickr_manager->SavePhoto($_POST['pid']
									,$_POST['title']
									,$_POST['description']
									,$_POST['tags']);
	}
	
	function func_DeletePhoto($photoid) 
	{
		global $flickr_manager;
		$flickr_manager->flickr->photos_delete($photoid);
	}
}

endif;

if(strtoupper($_SERVER['REQUEST_METHOD']) == "POST" && basename($_SERVER['REQUEST_URI']) == basename(__FILE__))
{
	// AJAX Handler
	error_reporting(E_ERROR);
	
	header("Cache-Control: no-cache");
  	header("Pragma: no-cache");

	// Load Wordpress Core
	require_once( dirname(__FILE__) . '/../../../wp-load.php');
	$wp->init();
	
	check_ajax_referer('wfm_ajax-action');
	
	switch($_POST['func']) {
		case 'func.getPhotos':
			FlickrPanel::func_GetPhotos();
			break;
		case 'func.photoInfo':
			FlickrPanel::func_PhotoInfo($_POST['pid']);
			break;
		case 'func.saveInfo':
			FlickrPanel::func_SaveInfo();
			break;
		case 'func.deletePhoto':
			FlickrPanel::func_DeletePhoto($_POST['pid']);
			break;
		case 'func.getPhotoset':
			FlickrPanel::func_GetPhotoset($_POST['photoset']);
			break;
		case 'func.photosetInfo':
			FlickrPanel::func_PhotosetInfo($_POST['photoset']);
			break;
		default:
		
			die(__("Invalid function call or reference!", 'flickr-manager'));
			
			break;
	}
}
?>
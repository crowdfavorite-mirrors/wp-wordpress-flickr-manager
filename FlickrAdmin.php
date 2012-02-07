<?php
if(!class_exists('FlickrAdmin')) :

class FlickrAdmin 
{
	
	function __construct() 
	{
		add_action('init', array(&$this, 'Initialize'));
	}
	
	function Initialize() 
	{
		
		add_action('admin_menu', array(&$this, 'CreateMenus'));
		
	}
	
	function CreateMenus() 
	{
		global $flickr_manager;
		
		// Add a new submenu under Options
		$page = add_options_page('Flickr Options', 'Flickr', 5, $flickr_manager->GetBaseName(), array(&$this, 'SettingsPage'));
		add_action("load-$page" , array(&$this, 'LoadSettingsDependencies'));
		add_action("admin_head-$page", array(&$this, 'PrintSettingsScripts'));
	}
	
	function LoadSettingsDependencies() {
		
		// Load Styles
		wp_enqueue_style('dashboard');
		wp_enqueue_style('global');
		wp_enqueue_style('wp-admin');
		
		// Load Scripts
		wp_enqueue_script('postbox');
		wp_enqueue_script('dashboard');
		
	}
	
	function PrintSettingsScripts() {
		?>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(function($) {
				var oldAPI;
				
				function FormAction(control, action) {
					var form = $(control).closest('form');
					
					form.find('input[name="action"]').val(action);
					form.submit();
				}
				
				$(document).ready(function() {
					$('#resetapi').click(function() {
						FormAction(this, 'resetapi');
						
						return false;
					});
					
					$('.logoutButton').click(function() {
						FormAction(this, 'logout');
						
						return false;	
					});
					
					$('#apikey_override').change(function() {
						var jAPI = $('#apikey');
						
						if($(this).is(':checked')) {
							oldAPI = jAPI.html();
							var apiFields = '<label><?php _e('Key', 'flickr-manager'); ?>: <input type="text" name="wfm-api_key" id="wfm-api_key" value="" style="width: 300px;" /></label><br />';
							apiFields += '<label><?php _e('Secret', 'flickr-manager'); ?>: <input type="text" name="wfm-secret" value="" style="width: 300px;" /></label>';
							jAPI.html(apiFields);
						} else {
							jAPI.html(oldAPI);
						}
					});
				});
			});
			//]]>
		</script>
		
		<style type="text/css">
			.loginButton {
				 background: url( images/fade-butt.png ); 
				 border: 3px double #999; 
				 border-left-color: #ccc; 
				 border-top-color: #ccc; 
				 color: #333; 
				 padding: 0.25em; 
				 font-size: 1.5em;
			}
		</style>
		<?php
	}
	
	function SettingsPage() 
	{
		global $flickr_manager;
		
		// Unset the session token for phpFlickr 
		unset($_SESSION['phpFlickr_auth_token']);
		
		$notification = '';
		
		if(!empty($_POST['action'])) {
			if(function_exists('check_admin_referer'))
				check_admin_referer('wfm_settings_page');
				
			switch($_POST['action']) {
				case 'save':
					if(is_array($_SESSION["wfm-settings"])) {
						foreach($_SESSION["wfm-settings"] as $setting) {
							$santized_name = preg_replace('/[\[\]]/i', '', $setting);
							$flickr_manager->SaveSetting($santized_name, $_POST['wfm-' . $santized_name]);
						}
					}
					
					$notification .= __('Options Saved!', 'flickr-manager');
					break;
				
				case 'logout':
					
					$flickr_manager->settings = array(
						'api_key' => $flickr_manager->settings['api_key']
						,'secret' => $flickr_manager->settings['secret']
					);
					
					update_option($flickr_manager->plugin_option, $flickr_manager->settings);
					$flickr_manager->flickr->setToken('');
					
					break;
				
				case 'token': 
					
					if(!empty($flickr_manager->settings['frob'])) {
						$token = $flickr_manager->flickr->auth_getToken($flickr_manager->settings['frob']);
						if(!$flickr_manager->flickr->error_code) {
							$flickr_manager->SaveSetting('token', $token['token']);
							$flickr_manager->SaveSetting('nsid', $token['user']['nsid']);
							$flickr_manager->SaveSetting('username', $token['user']['username']);
							$flickr_manager->flickr->setToken($token['token']);
						}
					}
					
					break;
					
				case 'resetapi':
					
					$flickr_manager->settings = array();
					update_option($flickr_manager->plugin_option, $flickr_manager->settings);
					
					$flickr_manager->ApplyDefaults();
					$flickr_manager->CreateFlickrHandler($flickr_manager->settings['api_key'], $flickr_manager->settings['secret']);
					
					$notification .= __('API key has been reset to default!', 'flickr-manager');
					
					break;
			}
		}
		
		// Define constant arrays
		$viewerSizes = array(
							"small" => __('Small', 'flickr-manager') 
							,"medium" => __('Medium', 'flickr-manager') 
							,"large" => __('Large', 'flickr-manager')
						);
					
		if($flickr_manager->settings['is_pro'] == '1') 
			$viewerSizes['original'] = __("Original", 'flickr-manager');
		
		require_once(dirname(__FILE__) . '/OverlayLoader.php');
		$overlayLoader = new OverlayLoader(WFM_OVERLAY_DIR);
		$overlays = array_merge(array(__('Disabled', 'flickr-manager')), $overlayLoader->GetOverlays());
		unset($overlayLoader);
		
		$header = '<form class="wrap" method="post" action="%s">
		<a href="http://tgardner.net/wordpress-flickr-manager/">
			<div id="icon-options-general" class="icon32"><br /></div>
		</a>
		<h2>%s</h2>';
		
		echo sprintf($header, admin_url('/options-general.php?page=' . $flickr_manager->GetBaseName())
							, __('Flickr Manager Settings', 'flickr-manager'));
		
		wp_nonce_field('wfm_settings_page');
		
		if(!$flickr_manager->flickr->auth_checkToken()) {
			$this->LoginPage();
			return;
		}
		
		echo '<input type="hidden" name="action" value="save" />';
		
		$notification = apply_filters('wfm_notification', $notification);
		
		if(!empty($notification)) {
			echo sprintf('<div id="message" class="updated fade"><p><strong>%s</strong></p></div>', $notification);
		}
		
		echo $this->RenderPostboxColumn(array(
			array(
				'title' => __('Global Settings', 'flickr-manager')
				,'content' => $this->RenderFormTable(
									array(
										array(
											'label' => __('API Information', 'flickr-manager')
											,'name' => 'api_key'
											,'type' => 'custom'
											,'param' => str_replace(array('[KeyText]','[APIKey]','[SecretText]','[APISecret]','[OverrideText]')
																	,array(
																		__('Key', 'flickr-manager')
																		,$flickr_manager->settings['api_key']
																		,__('Secret', 'flickr-manager')
																		,$flickr_manager->settings['secret']
																		,__('Override API Key. You will need to authenticate with Flickr again.','flickr-manager')
																	)
																	,'<div id="apikey">
																		[KeyText]: [APIKey]<br />
																		[SecretText]: [APISecret]
																		<input type="hidden" name="wfm-api_key" value="[APIKey]" />
																		<input type="hidden" name="wfm-secret" value="[APISecret]" />
																	</div>
																	
																	<input type="checkbox" id="apikey_override" />
																	<label for="apikey_override">[OverrideText]</label>')
											,'extras' => array('secret')
										),
                                        array(
                                            'label' => __('Cache Type', 'flickr-manager')
                                            ,'name' => 'cache'
                                            ,'type' => 'select'
                                            ,'param' => array(
                                                            'db' => __('Database', 'flickr-manager')
                                                            ,'fs' => __('File System', 'flickr-manager')
                                                        )
                                        ),
										array(
											'label' => __('Open Flickr pages in a new window', 'flickr-manager')
											,'name' => 'new_window'
											,'type' => 'checkbox'
										),
										array(
											'label' => __('Image Viewer', 'flickr-manager')
											,'name' => 'image_viewer'
											,'type' => 'select'
											,'param' => $overlays
										),
										array(
											'label' => __('Include Flickr link in caption', 'flickr-manager')
											,'name' => 'flickr_link'
											,'type' => 'checkbox'
										),
										array(
											'label' => __('Before Image', 'flickr-manager')
											,'name' => 'before_wrap'
											,'type' => 'textarea' 
										),
										array(
											'label' => __('After Image', 'flickr-manager')
											,'name' => 'after_wrap'
											,'type' => 'textarea' 
										),
										array(
											'label' => __('Enable Photo Sharing', 'flickr-manager')
											,'name' => 'photo_share[]'
											,'type' => 'multiple'
											,'param' => array(
															'facebook' => 'Facebook'
															,'twitter' => 'Twitter'
															,'delicious' => 'Delicious'
															,'digg' => 'Digg'
															,'google' => 'Google Bookmarks'
															,'yahoo' => 'Yahoo Bookmarks'
															,'misterwong' => 'Mister Wong'
															,'netvibes' => 'Netvibes'
															,'linkedin' => 'Linkedin'
															,'stumbleupon' => 'StumbleUpon'
														)
										)
									))
			),
			array(
				'title' => __('Media Panel Settings', 'flickr-manager')
				,'content' => $this->RenderFormTable(
									array(
										array(
											'label' => __('Hide private photos', 'flickr-manager')
											,'name' => 'privacy_filter'
											,'type' => 'checkbox'
										),
										array(
											'label' => __('Photos per page', 'flickr-manager')
											,'name' => 'per_page'
											,'type' => 'text'
											,'style' => 'width: 50px;'
										),
//										array(
//											'label' => __('Hide public copyright information when browsing', 'flickr-manager')
//											,'name' => 'hide_copyright'
//											,'type' => 'checkbox'
//										),
										array(
											'label' => __('Enable image viewer by default', 'flickr-manager')
											,'name' => 'lightbox_enable'
											,'type' => 'checkbox'
										),
										array(
											'label' => __('Default image viewer size', 'flickr-manager')
											,'name' => 'lightbox_default'
											,'type' => 'select'
											,'param' => $viewerSizes
										)
									))
			)
		), 65);
		
		$info = $flickr_manager->flickr->people_getInfo($flickr_manager->settings['nsid']);
		if($info['ispro'] != 0) {			
			$info['username'] .= sprintf(' <img src="%s" alt="Pro" style="vertical-align: middle;" />', plugins_url('/images/badge_pro.gif', __FILE__)); 
		}
		
		echo $this->RenderPostboxColumn(array(
			array(
				'title' => __('User Information', 'flickr-manager')
				,'content' => $this->RenderFormTable(
									array(
										array(
											'label' => __('Username', 'flickr-manager')
											,'type' => 'custom'
											,'param' => $info['username']
										),
										array(
											'label' => __('User ID', 'flickr-manager')
											,'type' => 'custom'
											,'param' => $info['nsid']
										),
										array(
											'label' => __('Real Name', 'flickr-manager')
											,'type' => 'custom'
											,'param' => $info['realname']
										),
										array(
											'label' => __('Photo URL', 'flickr-manager')
											,'type' => 'custom'
											,'param' => sprintf('<a href="%s">%s</a>', $info['photosurl'], $info['photosurl'])
										),
										array(
											'label' => __('# Photos', 'flickr-manager')
											,'type' => 'custom'
											,'param' => $info['photos']['count']
										)
									), false) 
									. sprintf('<div class="alignright"><input class="button-primary logoutButton" type="button" value="%s" /></div>', __('Logout &raquo;', 'flickr-manager'))
			)
			,array(
				'title' => __('About', 'flickr-manager')
				,'content' => '<h2 style="margin-top: 0px; padding-top:0px;">Like this plugin?</h2>
								Why not do any of the following:
								<ul style="list-style-position: outside; list-style: disc; margin: 10px 20px; vertical-align: top; padding-left: 5px;">
									<li>
										<a href="http://tgardner.net/wordpress-flickr-manager/">Link</a> to it so other folks can find out about it.
									</li>
									<li>
										<a href="http://wordpress.org/extend/plugins/wordpress-flickr-manager/">Give it a good rating</a> on WordPress.org.
									</li>
									<li>
										<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=trent%2egardner%40gmail%2ecom&amp;lc=AU&amp;currency_code=AUD">
											Donate a token of your appreciation</a>.
									</li>
								</ul>
								
								<h2>Need Support?</h2>
								<p>
									If you have any problems or good ideas, please talk about them in the 
									<a href="http://support.tgardner.net">support forums</a>.
								</p>'
			)
		), 30);
		
		echo '</form>';
	}
	
	function LoginPage() 
	{
		global $flickr_manager;
		
		// Begin Authentication
		$frob = $flickr_manager->flickr->auth_getFrob();
		$flickr_manager->SaveSetting('frob', $frob);
		
		$params = array(
			'api_key' => $flickr_manager->settings['api_key'], 
			'perms' => 'delete', 
			'frob' => $frob
		);
		
		$params['api_sig'] = $flickr_manager->GetSignature($params);
		$title = __('Authenticate', 'flickr-manager');
		
		$tpl_param = array(
			'[StepText]' => __('Step', 'flickr-manager')
			,'[AuthText]' => $title
			,'[AuthURL]' => sprintf("http://flickr.com/services/auth/?%s", http_build_query($params))
			,'[FinishText]' => __('Finish &raquo;', 'flickr-manager')
			,'[ResetText]' => __('If you have modified your API Key and would like to reset it back to default, please click <a href="#" id="resetapi">here</a>.', 'flickr-manager')
		);
		
		$content = "
			<div style='text-align: center;'>
				<h4>[StepText] 1:</h4>
				<input type='button' value='[AuthText]' onclick=\"window.open('[AuthURL]')\" class='loginButton' />
				
				<h4>[StepText] 2:</h4>
				<input type='hidden' name='action' value='token' />
				<input type='submit' value='[FinishText]' class='loginButton' />
			</div>
			
			<p class='clear'>
				&nbsp;<br />
				[ResetText]
			</p>";
		
		$boxes = array(
			array(
				'title' => $title
				,'content' => str_replace(array_keys($tpl_param), array_values($tpl_param), $content)
			)
		);
		
		echo $this->RenderPostboxColumn($boxes, 100);
	}
	
	function RenderPostboxColumn($boxes, $width) 
	{
		$column = "
		<div class='postbox-container' style='width:$width%;'>
			<div class='metabox-holder'>
				<div class='meta-box-sortables'>";
			
		foreach($boxes as $box) {
			$column .= $this->RenderPostbox($box, $showSubmit);
		}
			
		$column .= '
				</div>
			</div>
		</div>';
		
		return $column;
	}
	
	function RenderPostbox($box) 
	{
		
		$postbox = '
		<div class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
				<h3 class="hndle">%s</h3>
				<div class="inside">
					%s
					<br class="clear" />
				</div>
		</div>';
		
		return sprintf($postbox, $box['title'], $box['content']);
		
	}
	
	function RenderFormTable($options, $showSubmit = true) 
	{
		$table = '<table class="form-table"><tbody>';
		
		foreach($options as $option) {
			$table .= '<tr valign="top"><th scope="row">';
			$table .= (!empty($option['name'])) ? sprintf('<label for="wfm-%s">%s</label>', $option['name'], $option['label']) : $option['label'];
			$table .= '</th><td>';
			$table .= $this->RenderControl($option);
			$table .= '</td></tr>';
		}
		
		$table .= '</tbody></table>';
		
		if($showSubmit) {
			$table .= sprintf('<div class="alignright"><input class="button-primary" type="submit" value="%s" /></div>', __('Save Changes', 'flickr-manager'));
		}
		
		return $table;
	}
	
	function RenderControl($option)
	{
		global $flickr_manager;
		
		$result = '';
		
		if(empty($_SESSION["wfm-settings"]))
			$_SESSION["wfm-settings"] = array();
		
		switch($option['type']) {
			case 'custom':
				$result = $option['param'];
				
				if(!empty($option['extras'])) {
					foreach($option['extras'] as $control) {
						if(!in_array($control, $_SESSION["wfm-settings"])) 
							array_push($_SESSION["wfm-settings"], $control);
					}
				}
				
				break;
			case 'checkbox':
				$result = sprintf('<input type="checkbox" name="wfm-%s" id="wfm-%s" value="true" %s />'	, $option['name']
																										, $option['name']
																										, ($flickr_manager->settings[$option['name']] == 'true') 
																											? 'checked="checked"' : '');
				
				break;
			
			case 'select':
				$result = sprintf('<select name="wfm-%s" id="wfm-%s">', $option['name'], $option['name']);
				
				$isAssoc = (array_keys($option['param']) !== range(0, count($option['param']) - 1));
				
				foreach($option['param'] as $k => $v) {
					if(!$isAssoc) $k = strtolower($v);
					
					$result .= sprintf('<option value="%s" %s>%s</option>', $k, ($flickr_manager->settings[$option['name']] == $k) ? 'selected="selected"' : '', ucwords($v));
				}
				
				$result .= '</select>';
				
				break;
			case 'textarea':
				$result = sprintf('<textarea name="wfm-%s" id="wfm-%s" style="overflow: auto; height: 100px; width: 250px;">%s</textarea>', $option['name']
																																		, $option['name']
																																		, $flickr_manager->settings[$option['name']]);
			
				break;
			case 'multiple':
				$result = sprintf('<select name="wfm-%s" id="wfm-%s" style="height: 150px; width: 250px;" multiple="multiple">', $option['name'], $option['name']);
				
				$isAssoc = (array_keys($option['param']) !== range(0, count($option['param']) - 1));
				$santized_name = preg_replace('/[\[\]]/i', '', $option['name']);
				
				foreach($option['param'] as $k => $v) {
					if(!$isAssoc) $k = strtolower($v);
					
					$selected = '';
					if(!empty($flickr_manager->settings[$santized_name]) && in_array($k,$flickr_manager->settings[$santized_name]))
						$selected = 'selected="selected"';
					
					$result .= sprintf('<option value="%s" %s>%s</option>', $k, $selected, ucwords($v));
				}
				
				$result .= '</select>';
			
				break;
			case 'text':
				$result = sprintf('<input type="text" name="wfm-%s" id="wfm-%s" value="%s" style="%s" />', $option['name']
																										, $option['name']
																										, $flickr_manager->settings[$option['name']]
																										, $option['style']);
			
				break;
		}
		
		if(!empty($option['name']) && !in_array($option['name'], $_SESSION["wfm-settings"]))
			array_push($_SESSION["wfm-settings"], $option['name']);
		
		return $result;
	}
	
}

endif;
?>
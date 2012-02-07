<?php
/*
 *  The template for displaying Flickr photo pages.
 *
 *	Copy this file into your current active theme's directory to customize this template
 */

global $flickr_manager;
if (!is_object($flickr_manager)) wp_die(__('Wordpress Flickr Manager is not installed / activated!', 'flickr-manager'));

get_header();

?>
<div id="container"> 
	<div id="content" class="narrowcolumn">
		<h2 class="entry-title">
			<a href="<?php echo 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; ?>" title="Permalink to <?php echo $photo['title']; ?>" rel="bookmark"><?php echo $photo['title']; ?></a>
		</h2>
	
	
	<!-- Load photo content -->
	<?php
	
	if($photo['media'] == 'video') {
		echo $flickr_manager->RenderVideo($photo['id'], 'html5');
	} else {
		
		$settings = array(
			'url' => $photo['urls']['url'][0]['_content']
			,'title' => $photo['title']
			,'rel' => ''
			,'thumbnail' => $flickr_manager->flickr->buildPhotoURL($photo, 'medium')
			,'class' => ''
			,'description' => $photo['description']
			,'original' => $flickr_manager->flickr->buildPhotoURL($photo, 'original')
		);
		
		echo $flickr_manager->RenderPhoto($settings);
	}
	?>
	
	<?php if (is_object($flickr_manager)):?>
	<div class="flickr-meta-links">
	Powered by the <a href="http://tgardner.net/wordpress-flickr-manager/">Flickr Manager</a> plugin for WordPress.
	</div>
	<?php endif; ?>
		
	</div>
</div>

<?php
get_sidebar();

get_footer();
?>
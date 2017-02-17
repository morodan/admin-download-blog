<?php

/*
Plugin Name: Admin Download Blog
Plugin URI: https://github.com/morodan/admin-download-blog
Description: Display download blog for editors
Author: Morodan Gheorghe - Morro
Version: 0.9.6
Tested up to: 4.7.1
Author URI: http://morodan.com/cv
WDP ID: 137
Text Domain: admin_download_blog
*/

/*
Copyright 2016 Morro (http://morodan.com/cv)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

global $admin_dp_settings_page;


if ( version_compare($wp_version, '3.0.9', '>') ) {
	$admin_dp_settings_page = 'settings.php';
} else {
	$admin_dp_settings_page = 'ms-admin.php';
}

$admin_dp_settings_page = "admin.php";

//---Hook-----------------------------------------------------------------//

	add_action('init', 'admin_dp_init');
	add_action('admin_menu', 'admin_dp_menus');
	add_action('admin_notices', 'admin_dp_banner_output');

if ( !is_multisite() ) {
	add_action('admin_notices', 'admin_dp_admin_notices');
} else {
	add_action('init', 'dp_init_database');
//	add_action('admin_menu', 'admin_dp_menus');
	add_action('network_admin_menu', 'admin_dp_menus');
//	add_action('admin_notices', 'admin_dp_banner_output');
	add_action('network_admin_notices', 'admin_dp_banner_output');
}


//---Functions------------------------------------------------------------//

function admin_dp_init() {
	admin_dp_redirect();
	if (isset($_POST['dp_blgmg_redirects'])){
		admin_dp_save_redirects();
	}
}

function dp_init_database() {
	global $wpdb, $table_prefix;
	
	$table_name = 'wp_mg_redirects';
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
     //table not in database. Create new table
		$mg_query = "CREATE TABLE `" . $table_name . "` (
		  `redirect_ID` bigint(20) unsigned NOT NULL auto_increment,
		  `redirect_directory` TEXT NOT NULL,
		  `redirect_destination` varchar(200) NOT NULL,
		  PRIMARY KEY  (`redirect_ID`)
		) ENGINE=MyISAM;";
		$wpdb->query( $mg_query );
	}
}

function admin_dp_admin_notices() {
	if ( !is_multisite() ) {
		?>
		<div class="updated">
        	<p>The Admin Download Blog plugin is only compatible with WordPress Multisite.</p>
        </div>
        <?php
	}
}

function admin_dp_banner_output() {
	$admin_dp_data = get_site_option('admin_dp_data');
	if ( !empty($admin_dp_data) && $admin_dp_data != 'empty' ){
		echo '<div class="wpmu-notice">'.stripslashes( $admin_dp_data ).'</div>';
	}
}


function admin_dp_menus() {
	global $_wp_last_object_menu, $wpdb, $wp_roles, $current_user, $wp_version, $admin_dp_settings_page;

	$_wp_last_object_menu++;
	if ($_wp_last_object_menu < 2) $_wp_last_object_menu = 6;
	add_menu_page( 'Admin Download Blog', 'Download Blog', 'manage_options', 'admin-download-blog', 'admin_dp_page_main_output', 'dashicons-tickets', $_wp_last_object_menu );
	add_submenu_page('admin-download-blog', 'Admin Download Blog', 'Download Blog', 'manage_options', 'admin-download-blog', 'admin_dp_page_main_output');
	//add_action('load-' . $DL, 'admin_dp_page_main_output');

	if ( version_compare($wp_version, '3.0.9', '>') ) {
		if ( is_network_admin() ) {
			add_submenu_page('admin-download-blog', 'Download Blog Settings', 'Settings', 'manage_settings_download_page', 'admin-download-blog-settings', 'admin_dp_settings_page_main_output');
			add_submenu_page('admin-download-blog', 'Blogs Already Redirected', 'Redirects', 'manage_redirections', 'admin-download-blog-redirects', 'admin_dp_redirects_main_output');
			//add_action('load-' . $nda, 'admin_dp_settings_page_main_output');
		} 
	} else {
		if ( is_super_admin() ) {
			add_submenu_page('admin-download-blog', 'Download Blog Settings', 'Settings', 'manage_settings_download_page', 'admin-download-blog-settings', 'admin_dp_settings_page_main_output');
			add_submenu_page('admin-download-blog', 'Blogs Already Redirected', 'Redirects', 'manage_redirections', 'admin-download-blog-redirects', 'admin_dp_redirects_main_output');
			//add_action('load-' . $nda, 'admin_dp_settings_page_main_output');
		}
	}
}


//---Page Output Functions------------------------------------------------//

function admin_dp_settings_page_main_output() {
	global $wpdb, $wp_roles, $current_user, $admin_dp_settings_page;

	if(!current_user_can('manage_settings_download_page')) {
		echo "<p>Wrong Try...</p>";  //If accessed properly, this message doesn't appear.
		return;
	}
	if (isset($_GET['updated'])) {
		?><div id="message" class="updated fade"><p><?php _e(urldecode($_GET['updatedmsg']), 'admin_download_blog') ?></p></div><?php
	}
	echo '<div class="wrap">';
	$action = isset($_GET[ 'action' ])?$_GET[ 'action' ]:'default';
	switch( $action ) {
		default:
			$admin_dp_data = get_site_option('admin_dp_data');
			if ( $admin_dp_data == 'empty' ) {
				$admin_dp_data = '';
			}
			?>
			<h2><?php _e('Admin Download Blog Settings', 'admin_download_blog') ?></h2>
            <form method="post" action="<?php print $admin_dp_settings_page; ?>?page=admin-download-blog-settings&action=update">
            <table class="form-table">
            <tr valign="top">
            <th scope="row"><?php _e('Banner on all pages code', 'admin_download_blog') ?></th>
            <td>
            <textarea name="admin_dp_data" type="text" rows="5" wrap="soft" id="admin_dp_data" style="width: 95%"/><?php echo $admin_dp_data; ?></textarea>
            <br /><?php _e('Tip: Use HTML markup around the code to make it centered on the page.', 'admin_download_blog') ?></td>
            </tr>
            </table>
            <p class="submit">
            <input class="button button-primary" type="submit" name="Submit" value="<?php _e('Save Changes', 'admin_download_blog') ?>" />
			<input class="button button-secondary" type="submit" name="Reset" value="<?php _e('Reset', 'admin_download_blog') ?>" />
            </p>
            </form>
			<?php
		break;
		case "update":
			if ( isset( $_POST[ 'Reset' ] ) ) {
				update_site_option( "admin_dp_data", "empty" );
				echo "
				<SCRIPT LANGUAGE='JavaScript'>
				window.location='{$admin_dp_settings_page}?page=admin-download-blog-settings&updated=true&updatedmsg=" . urlencode(__('Settings cleared.', 'admin_download_blog')) . "';
				</script>
				";
			} else {
				$admin_dp_data = $_POST[ 'admin_dp_data' ];
				if ( $admin_dp_data == '' ) {
					$admin_dp_data = 'empty';
				}
				update_site_option( "admin_dp_data", stripslashes($admin_dp_data) );
				echo "
				<SCRIPT LANGUAGE='JavaScript'>
				window.location='{$admin_dp_settings_page}?page=admin-download-blog-settings&updated=true&updatedmsg=" . urlencode(__('Settings saved.', 'admin_download_blog')) . "';
				</script>
				";
			}
		break;
		case "temp":
		break;
	}
	echo '</div>';
}

function admin_dp_page_main_output() {
	global $wpdb, $wp_roles, $current_user, $admin_dp_settings_page;

	if(!current_user_can('manage_options')) {
		echo "<p>Wrong Try...</p>";  //If accessed properly, this message doesn't appear.

		return;
	}
	if (isset($_GET['updated'])) {
		?><div id="message" class="updated fade"><p><?php _e(urldecode($_GET['updatedmsg']), 'admin_download_blog') ?></p></div><?php
	}

/*	if (isset($_POST['do_what'])) {
		?><div id="message" class="updated fade"><p><?php echo urldecode($_POST['do_what']) ?></p></div><?php
	}*/

	if (isset($_POST['do_what']) && $_POST['do_what'] == 'redirect') {
		//$destination = 'https://'.urldecode($_POST['redirecturl']).'.wordpress.com';
		$destination = urldecode($_POST['redirecturl']);
		$directory = $current_user->user_login;
		$redirects = dp_get_list_of_redirects();
		if (!empty($redirects)) {
			$redirects[$directory] = $destination; 
		} else {
			$redirects = array();
			$redirects[$directory] = $destination; 
		}
		dp_set_list_of_redirects($redirects);
		?><div id="message" class="updated fade"><p>Redirection for '<?php echo $current_user->user_login;?>' saved!</p></div><?php
	}

	echo '<div class="wrap">';
	$you_website_url_text = dp_get_redirect_by_current_user();
	/*if ($you_website_url_text == ''){
		$you_website_url_text = 'YOUR_WEBSITE_URL';
	}*/
	?>
		<form method="post" action="<?php print $admin_dp_settings_page; ?>?page=admin-download-blog&action=update">
		<h1>Our Blog is closing December 1, 2017<br><small>You can move your blog</small></h1>
		<br>
		<p>Sadly, we will be closing the Blog site, this is not because of your blog. Your blog is great and we would love to have you continue blogging about your experience. Therefore, we would like to offer you to download your blog and set it up on another free blogging service such as <a href="https://www.wordpress.com" target="_blank">https://www.wordpress.com</a> or in any other blog platform you preference.  We will also offer grace period with service for your current visitors and search engines to find new address of your blog so you won’t be losing your visitors. 
</p><p>
To get started, follow steps on the guide below to download and setup blog on Wordpress.com. 
</p>
 <p>
We would like to urge you to move your data to a new blog <strong>before December 1</strong>. If you haven’t, we will remind you again in the end of November. On December 15, we will remove blogs that have not be redirected.
</p>
<p>Kind regards,<br>
Support Team </p>
		<br><form>
		<ul>
		<li><h3>Step 1 - Setup new Wordpress blog</h3>
		You can create a new website for free in <a href="https://wordpress.com" target="_blank">wordpress.com</a>. <br><hr></li>

		<li><h3>Step 2 - Download your blog content</h3>
		Click the button below to download your blog content.<br>
		<a href="/<?php echo $current_user->user_login ;?>/wp-admin/export.php?download=true&content=all&cat=0&post_author=0&post_start_date=0&post_end_date=0&post_status=0&page_author=0&page_start_date=0&page_end_date=0&page_status=0&attachment_start_date=0&attachment_end_date=0&submit=Download+Export+File" class="button button-primary">DOWNLOAD the Blog</a>
		<br>
		<hr></li>

		<li><h3>Step 3 - Import your blog </h3>
		Go to https://YOURWEBSITENAME.wordpress.com/wp-admin/admin.php?import=wordpress and import the file you downloaded on previous step.<br><hr></li>

		<li><h3>Step 4 - Verify that the site is working</h3>
		Go to https://YOURWEBSITENAME.wordpress.com/ and verify that the site is working.<br><hr></li>

		<li><h3>Step 5 - Redirect your visitors to the new address</h3>
		<input type="text" name="redirecturl" size="20" value="<?php echo $you_website_url_text;?>" style="width:300px; height: 30px;" placeholder="http://YOURSITE.wordpress.com" /> 
			<input class="button button-primary" type="submit" name="Redirect" value="<?php _e('Save redirect url', 'admin_download_blog') ?>"  onClick="jQuery('#do_what').val('redirect')" />
			<input class="button button-secondary" type="submit" name="Reset" value="<?php _e('Reset', 'admin_download_blog') ?>" />

			

		<hr></li>
		</ul>
		<p class="submit">
			<input type="hidden" name="do_what" id="do_what" value="" />
		    <!--// <input class="button button-primary" type="submit" name="Download" value="<?php _e('Download', 'admin_download_blog') ?>" onClick="jQuery('#do_what').val('download')" /> //-->
		    

			

		</p>

		</form>
	<?php
	echo '</div>';
}

function admin_dp_expand_redirects() {
	$redirects = dp_get_list_of_redirects(); 
	$output = '';
	if (!empty($redirects)) {
		foreach ($redirects as $directory => $destination) {
			$output .= '
			
			<tr>
				<td><input type="hidden" name="dp_blgmg_redirects[directory][]" value="'.$directory.'"  />/' . $directory . '/</td>
				<td>&raquo;</td>
				<td><input type="hidden" name="dp_blgmg_redirects[destination][]" value="'.$destination.'" />' . $destination . '</td>
				<td><span class="dprd-delete">Delete</span></td>
			</tr>
			
			';
		}
	} // end if
	return $output;
}

function admin_dp_save_redirects() {
	global $wpdb;

	if ( !current_user_can('admin-download-blog-redirects') )  { 
		echo "<b>Nice try...</b>";
		return;
	}
	check_admin_referer( 'admin_dp_save_redirects', 'redirects_nonce' );
	
	$data = $_POST['dp_blgmg_redirects'];

	$mgquery = "DELETE FROM wp_mg_redirects;";
	$wpdb->query( $mgquery );

	$redirects = array();
	
	for($i = 0; $i < sizeof($data['directory']); ++$i) {
		$mgquery = '';
		$directory = trim( sanitize_text_field( $data['directory'][$i] ) );
		$destination = trim( sanitize_text_field( $data['destination'][$i] ) );
	
		if ($directory == '' && $destination == '') { continue; }
		else {
			//$redirects[$directory] = $destination; 
			$mgquery = "INSERT INTO wp_mg_redirects (redirect_directory, redirect_destination) VALUES ('$directory', '$destination');";
			$wpdb->query($mgquery);
		}
	}	
	
//	update_option('dp_blgmg_redirects', $redirects);
}

function dp_get_redirect_by_current_user(){
	global $wpdb, $current_user;

	$mgquery = "SELECT redirect_destination FROM wp_mg_redirects WHERE redirect_directory = '". $current_user->user_login . "' LIMIT 0,1;";
	$destination = $wpdb->get_row($mgquery);

	if (null !== $destination) {
		return $destination->redirect_destination;
	} else {
		return '';
	}	
}

function dp_set_list_of_redirects($data){
	global $wpdb;

	foreach($data as $directory => $destination) {
		$mgquery = "INSERT INTO wp_mg_redirects (redirect_directory, redirect_destination) VALUES ('$directory', '$destination');";
		$wpdb->query($mgquery);
	}
}

function admin_dp_redirect() {
	// this is what the user asked for (strip out home portion, case insensitive)
	$home = get_option('home');
	$userrequest = str_ireplace($home,'',admin_dp_get_address());
	$userrequest = rtrim($userrequest,'/');
	
	$redirects = dp_get_list_of_redirects();
	if (!empty($redirects)) {
		$do_redirect = '';
		
		// compare user request to each redirect stored in the db
		foreach ($redirects as $storeddirectory => $destination) {
			if (strpos($storeddirectory,'*') !== false) {
				
				// don't allow people to accidentally lock themselves out of admin
				if ( strpos($userrequest, '/wp-login') !== 0 && strpos($userrequest, '/wp-admin') !== 0 ) {
					// Make sure it gets all the proper decoding and rtrim action
					$storeddirectory = str_replace('*','(.*)',$storeddirectory);
					$pattern = '/^' . str_replace( '/', '\/', rtrim( $storeddirectory, '/' ) ) . '/';
					$destination = str_replace('*','$1',$destination);
					$output = preg_replace($pattern, $destination, $userrequest);
					if ($output !== $userrequest) {
						// pattern matched, perform redirect
						$do_redirect = $output;
					}
				}
			}
			elseif(strpos(urldecode($userrequest), rtrim($storeddirectory,'/')) === 0) {
				// simple comparison redirect
				$do_redirect = $destination;
			}
			
			// redirect. the second condition here prevents redirect loops as a result of wildcards.
			if ($do_redirect !== '' && trim($do_redirect,'/') !== trim($userrequest,'/')) {
				// check if destination needs the domain prepended
				if (strpos($do_redirect,'/') === 0){
					$do_redirect = home_url().$do_redirect;
				}
				/*header ('HTTP/1.1 301 Moved Permanently');
				header ('Location: ' . $do_redirect);*/
				wp_redirect($do_redirect);
				exit();
			}
			else { 
				unset($redirects); 
			}
		}

// the next if condition will be activated when we want to disable the blog entirely, and all blogs without redirection will be redirected to home 
/*
		if ( !is_admin() && !is_login_page()) {
     		wp_redirect( get_option('home'), 301 );
     		exit;
		}
*/		
	}
}

function is_login_page() {
    return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
}

function dp_get_list_of_redirects(){
	global $wpdb;

	$mgquery = "SELECT redirect_directory, redirect_destination FROM wp_mg_redirects order by redirect_directory;";
	$mgredirects = $wpdb->get_results($mgquery);
	$redirects = array();
	foreach($mgredirects as $redirect) {
		$redirects[$redirect->redirect_directory] = $redirect->redirect_destination;
	}

	return $redirects;
}

function admin_dp_get_address() {
	$protocol = 'http';
	// check for https
	if ( isset( $_SERVER["HTTPS"] ) && strtolower( $_SERVER["HTTPS"] ) == "on" ) {
		$protocol .= "s";
	}
	return $protocol . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
} 

function admin_dp_redirects_main_output() {
	?>
	<div class="wrap dp_style_redirects">
		<script>
			jQuery(document).ready(function(){
				jQuery('span.dprd-delete').html('Delete').css({'color':'red','cursor':'pointer'}).click(function(){
					var confirm_delete = confirm('Delete This Redirect?');
					if (confirm_delete) {
						
						// remove element and submit
						jQuery(this).parent().parent().remove();
						jQuery('#dp_redirects_form').submit();
						
					}
				});
			});
		</script>

	<?php
	if (isset($_POST['dp_blgmg_redirects'])) {
		echo '<div id="message" class="updated"><p>Settings saved</p></div>';
	}
	?>

		<h2>Blogs Already Redirected</h2>
		
		<form method="post" id="dp_redirects_form" action="<?php print $admin_dp_settings_page; ?>?page=admin-download-blog-redirects&savedata=true">
		
		<?php wp_nonce_field( 'admin_dp_save_redirects', 'redirects_nonce' ); ?>

		<table class="widefat">
			<thead>
				<tr>
					<th colspan="2">Blog directory</th>
					<th colspan="2">Destination url</th>
				</tr>
			</thead>
			<tbody>
				<?php echo admin_dp_expand_redirects(); ?>
				<tr>
					<td style="width:35%;"><input type="text" name="dp_blgmg_redirects[directory][]" value="" style="width:99%;" /></td>
					<td style="width:2%;">&raquo;</td>
					<td style="width:60%;"><input type="text" name="dp_blgmg_redirects[destination][]" value="" style="width:99%;" /></td>
					<td><span class="dprd-delete"></span></td>
				</tr>
			</tbody>
		</table>
		<p class="submit"><input type="submit" name="submit_dp_redirect" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
		</form>
		</div>
	<?php
}


// this is here for php4 compatibility
if(!function_exists('str_ireplace')){
  function str_ireplace($search,$replace,$subject){
    $token = chr(1);
    $haystack = strtolower($subject);
    $needle = strtolower($search);
    while (($pos=strpos($haystack,$needle))!==FALSE){
      $subject = substr_replace($subject,$token,$pos,strlen($search));
      $haystack = substr_replace($haystack,$token,$pos,strlen($search));
    }
    $subject = str_replace($token,$replace,$subject);
    return $subject;
  }
}
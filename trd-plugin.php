<?php
/*
Plugin Name: TrenDemon
Plugin URI: http://trendemon.com
Description: Boost website conversions (sales, newsletter signups, community connects, social shares, etc.) using personalized content recommendations and call to actions.
Version: 1.6
Author: TrenDemon
Author URI: http://trendemon.com
License: GPL
*/

/* определяем переменые */
define('TRD203_LOGO', "http://trendemon.com/wp/trendy.png");
define('TRD203_BASE_URL', "http://prod.trendemon.com/");
define('TRD203_API_URL', "http://prod.trendemon.com/apis/wp_api");

require_once dirname( __FILE__ ) . '/accountconfig.php';


/*admin functions */
function trd203_create_menu() {
	add_menu_page('TrenDemon Account Configuration', 'TrenDemon', 'access_trd', 'trd203_dashboard', 'trd203_dashboard', TRD203_LOGO);
		
	if(get_option("trd203_logged") || isset($_GET["settings"])){
		add_submenu_page( "trd203_dashboard", 'TrenDemon Widget Settings', "Dashboard", 'access_trd', 'trd203_dashboard', 'trd203_dashboard' );
		add_submenu_page( "trd203_dashboard", 'TrenDemon Widget Settings', "Settings", 'access_trd', 'trd203_settings_config', 'trd203_settings_config' );
	}	
	//call register settings function
	add_action( 'admin_init', 'register_trd203_settings' );
	add_action( 'admin_init', 'trd203_plugin_admin_init' );  /* load css only for plugin's page */
}

function add_trd203_caps() {
	$role = get_role('administrator');
	$role->add_cap('access_trd');
}



/* adding TrenDemon script to every page of the website */
function add_trd203_script() {
	if (get_option("trd203_logged")){
?>
<!-- TrenDemon Code -->
<script type="text/javascript" id="trd-flame-load">
     var JsDomain = "https://prod.trendemon.com/apis/loadflame/mainflamejs";
     var param = "aid=<?php echo get_option("trd203_aid"); ?>&uid=<?php echo get_option("trd203_uid"); ?>&baseurl=https%3A%2F%2Fprod.trendemon.com%2F&appid=208770359181748";
     (function (w, d) {
      function go() {
       setTimeout(function () {            
        var bi = document.createElement('script'); bi.type = 'text/javascript'; bi.async = true;
        bi.src = JsDomain + '?' +param;
        bi.id  = 'trdflame';
        var s  = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(bi, s);
       }, 500);
      }
      if (w.addEventListener) { w.addEventListener("load", go, false); }
      else if (w.attachEvent) { w.attachEvent("onload", go); }
     }(window, document));
</script>
<!-- End of TrenDemon Code -->

<?php
	}
}

//connect to TrenDemon account
function trd203_login_check($user, $password, $url="", $type="login"){	
	if($type=="reg"){ // registration
	
	$parse = parse_url($url);
	$name = $parse['host'];	
		$args = array(
			'email'       => $user,
			'passwd'    => $password,
			'name'		=>$name,
			'url'		=>$url	
		);

	} else { // connect to account that already exists
		$args = array(
			'email'       => $user,
			'passwd'    => $password
		);
			
	}
	
	$api_response_array = trd203_get_api_answer($args, TRD203_API_URL);
	
	if($api_response_array["error"]=="1"){
		if($api_response_array["msg"]=="Non unique Email" || $api_response_array["msg"]=="Non unique Name"){
			return "A user already exists with this email address. Please login.";
		}
		
		return false;	
	}
	
	$url = $api_response_array["url"];
		
	activate_trd();	
	update_option("trd203_mail", $user);
	update_option("trd203_url", $url);	
	update_option("trd203_logged", true);
	update_option("trd203_aid", $api_response_array["advertiser_id"]);
	update_option("trd203_uid", $api_response_array["user_id"]);
	update_option("trd203_token", $api_response_array["token"]);
		
	return true;
}	


/* adding trendemon widget to the end of each post */
function add_trd203_show_div($text){
	if (is_single()){
		if(get_option('trd203_logged')=="1"){
			$text .="<div id='trd-articleslideshow'></div>";
		}
	}
	return $text;
}


// Register the option settings we will be using
function register_trd203_settings() {
	register_setting('trd203_settings', 'trd203_logged');
	register_setting('trd203_settings', 'trd203_step'); 
	register_setting('trd203_settings', 'trd203_url'); // iframe url 
	register_setting('trd203_settings', 'trd203_mail'); // email from trendemon 
	register_setting('trd203_settings', 'trd203_token');
 
}

/* get answer from api
		$args - array of parameters
		$url - api url
*/
function trd203_get_api_answer($args, $url){					
	$api_response = wp_remote_post($url, array(
		'method' => 'POST',
		'timeout' => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking' => true,
		'headers' => array(),
		'body' => $args,
		'cookies' => array()
		)
	);			
	return json_decode ($api_response["body"], true);
}



/* Trendemon setting screen */
function trd203_settings_config() {
?>
<div class="wrap">
<h2>TrenDemon General Settings</h2>
<p>Thank you for installing the TrenDemon WordPress plugin! From here you can view and modify some of your account's basic settings.</p>
<br/>
<?php 
add_thickbox();

/* user connection modify */
if($_POST["trd-relogin"]){
	if (is_admin()) {
		$trd203_user = $_POST["trd-user"];
		$trd203_pass = filter_var($_POST["trd-pass"], FILTER_SANITIZE_STRING);
		$re_error ="";

		if ($trd203_user!= "" && $trd203_pass != "") {	
			if (!filter_var($trd203_user, FILTER_VALIDATE_EMAIL) || !trd203_login_check($trd203_user, $trd203_pass)) {
				$re_error = "<b>Could not log in TrenDemon. Please check your login details.</b>";
			}
		} else {
			$re_error = "<b>Could not log in TrenDemon. Please check your login details.</b>";
		}	
	
		if($re_error!=""){
	?>
			<script type="text/javascript">
				jQuery(window).load(function() {
					tb_show("Modify Account","#TB_inline?width=600&height=270&inlineId=trd_reconnect");
				});
			</script>
	<?php 	
		}
	} // if wp-admin		
} //if POST
?>

<script type="text/javascript">
	jQuery(window).load(function() {
		jQuery("#trd_modify").click(function(){
			tb_show("Modify Account","#TB_inline?width=600&height=270&inlineId=trd_reconnect");
		})
		
	});			
</script>

<fieldset>
	<legend>Status</legend>
	<p class="account_connect">Account <b><?php echo get_option("trd203_mail"); ?></b> is successfully connected to TrenDemon. <span id="trd_modify">modify</span></p>
</fieldset>
<div class="wrapper"></div>

<form method="post">
	<fieldset>
	<legend>Basic:</legend>
		<?php
		$args = array(
		'token'       => get_option('trd203_token')
		);
				
		$response = trd203_get_api_answer($args, TRD203_API_URL);				
		$trd203_show = (int)$response['show'];	
		$trd203_lift = (int)$response['lift'];
		$trd203_mobile_lift = (int)$response['moblift'];
		$trd203_hidelogo = (int)$response['hidelogo'];
		?>			
		
		<div class="show_lift">
			Which recommendation interface would you like to use:
		</div>
		
		<div class="show_lift">		
			<p>
			<input type="radio" name="show_lift" value="show" <?php if($trd203_show=="1"){?>checked="checked" <?php } ?>>SHOW (embedded article slideshow at the end of the post)
			<span class="tooltip tooltip-effect-1">
				<span class="tooltip-item"><img src="<?php echo get_site_url(); ?>/wp-content/plugins/trendemon-revenue-booster/img/i.png"/></span>
				<span class="tooltip-content clearfix"><img src="<?php echo get_site_url(); ?>/wp-content/plugins/trendemon-revenue-booster/img/img2_anim.gif" id="img1"/></span>
			</span>
			</p>
			<p>
			<input type="radio" name="show_lift" value="lift" <?php if($trd203_lift=="1"){?>checked="checked" <?php } ?>>LIFT (a rising content feed at the end of the article)
			<span class="tooltip tooltip-effect-1">
				<span class="tooltip-item"><img src="<?php echo get_site_url(); ?>/wp-content/plugins/trendemon-revenue-booster/img/i.png"/></span>
				<span class="tooltip-content clearfix"><img src="<?php echo get_site_url(); ?>/wp-content/plugins/trendemon-revenue-booster/img/img1_anim.gif" id="img1"/></span>
			</span>
			</p>
		</div>
		<div class="show_lift show_lift_error">	
			
		</div>
		<div style="clear:both"></div>
		
		<table class="settings-table">        
			<tr valign="top">
			<td scope="row">Enable <b>Mobile Interface</b> (the mobile content discovery unit at the end of the article)?
			<span class="tooltip tooltip-effect-1">
					<span class="tooltip-item"><img src="<?php echo get_site_url(); ?>/wp-content/plugins/trendemon-revenue-booster/img/i.png"/></span>
					<span class="tooltip-content clearfix"><img src="<?php echo get_site_url(); ?>/wp-content/plugins/trendemon-revenue-booster/img/img3_anim.gif" id="img1"/></span>
			</span>
			</td>
			
		
			<td>
				<p class="field switch">
					<input type="radio" id="trd_mobile_lift_1" name="trd203_mobile_lift" value="1" class="trd_on" <?php if($trd203_mobile_lift=="1"){?>checked="checked" <?php } ?> />
					<input type="radio" id="trd_mobile_lift_2" name="trd203_mobile_lift" value="0" class="trd_off" <?php if($trd203_mobile_lift=="0"){?>checked="checked" <?php } ?>/>
					<label for="trd_mobile_lift_1" class="cb-enable <?php if($trd203_mobile_lift=="1"){?>selected<?php } ?>"><span>On</span></label>
					<label for="trd_mobile_lift_2" class="cb-disable <?php if($trd203_mobile_lift=="0"){?>selected<?php } ?>"><span>Off</span></label>
				</p>
			</td>
			<td class="set_error" rowspan="2">
			</td>
			</tr>
			<tr valign="top">
			<td scope="row">Hide Trendy (TrenDemon's logo) and miss out on extra earnings from our referral program
			<span class="tooltip tooltip-effect-1">
					<span class="tooltip-item"><img src="<?php echo get_site_url(); ?>/wp-content/plugins/trendemon-revenue-booster/img/i.png"/></span>
					<span class="tooltip-content clearfix"><span class="tooltip-text">TrenDemon's partner program enables you to earn 20% from referrals (sign ups to marketer plans).</span></span>
			</span>
			</td>
			
			
			
			<td>
				<p class="field switch">
					<input type="radio" id="trd_hidelogo_1" name="trd203_hidelogo" value="1" class="trd_on" <?php if($trd203_hidelogo=="1"){?>checked="checked" <?php } ?> />
					<input type="radio" id="trd_hidelogo_2" name="trd203_hidelogo" value="0" class="trd_off" <?php if($trd203_hidelogo=="0"){?>checked="checked" <?php } ?>/>
					<label for="trd_hidelogo_1" class="cb-enable <?php if($trd203_hidelogo=="1"){?>selected<?php } ?>"><span>On</span></label>
					<label for="trd_hidelogo_2" class="cb-disable <?php if($trd203_hidelogo=="0"){?>selected<?php } ?>"><span>Off</span></label>
				</p>
			</td>
			</tr>
		</table>
    </fieldset>
	<div style="clear:both"></div>
</form>
	
	<div class="start_now">
		<a href="admin.php?page=trd203_dashboard&go=1">Proceed to Dashboard</a>
	</div>
	<p>To help you get started, here is a link to our <a href="http://trendemon.com/resources/userguide.pdf" target="_blank">User Guide</a> in our 
	<a href="http://support.trendemon.com" target="_blank">Support Section</a>.
	</p>

</div> <!-- //wrap -->


<div id="trd_reconnect" style="display:none">
	<div id="trd_form_content">
		<p class="trd_error"><?php echo $re_error; ?></p>
		<form method="post" action="admin.php?page=trd203_dashboard&settings=1" name="trd_login_form">
			<input type="hidden" name="action" value="login">
			<table class="trd_form_table">
				<tr valign="top">
					<td>TrenDemon Username (Email)</td>
					<td ><input type="text" name="trd-user" id="trd-user" value="" /></td>
				</tr>

				<tr valign="top">
					<td>TrenDemon Password</td>
					<td><input type="password" name="trd-pass" id="trd-pass" value="" /></td>
				</tr>
			
				<tr valign="top">
					<td></td>
					<td><input type="submit" name="trd-relogin" value="CONNECT" id="trd_submit" onclick="formvalidation('trd_login_form');return false;" /> 
					</td>
				</tr>
			</table>
		</form>
	</div> <!-- trd_form_content-->
</div>

<?php

} 



/* load css only for plugin's page */
function trd203_plugin_admin_init() {
    wp_enqueue_style( 'trd203_style', plugins_url('trd.css', __FILE__) );
	wp_enqueue_style( 'trd203_tooltip_style', plugins_url('tooltip-classic.css', __FILE__) );
}

/* plugin activation */
function activate_trd(){
	add_option('trd203_do_activation_redirect', true);
}

/* plugin deactivation */
function deactivate_trd(){
}

function trd203_activation_redirect(){
	if (get_option('trd203_do_activation_redirect', false)) {
        delete_option('trd203_do_activation_redirect');
        if(!is_network_admin()){
			wp_redirect("admin.php?page=trd203_dashboard");
		}
    }
}

function uninstall_trd(){
	//delete_option("trd203_lift");
	//delete_option("trd203_mobile_lift");
	//delete_option("trd203_show");	
	delete_option("trd203_mail");
	delete_option("trd203_url");	
	delete_option("trd203_aid");
	delete_option("trd203_uid");
	delete_option("trd203_logged");
	delete_option("trd203_token");	
	delete_option("trd203_step");	
	
}


function trd203_update_javascript() { ?>
	<script type="text/javascript" >	
	jQuery(".cb-enable").click(function(){
		var span = jQuery(this);
        var parent = jQuery(this).parents('.switch');
		
		var data_name = jQuery("input", parent).attr("name");						
		var data = {
			'action': 'trd203_update'
		};
		data[data_name]=1;
		jQuery.post(ajaxurl, data, function(response) {
			if(response!="ok"){		
				jQuery(".set_error").html("Oops... We ran into some problems updating your settings.<br/>Try changing them from the Settings page in your dashboard, thank you.");				
			} else {
				jQuery('.cb-disable',parent).removeClass('selected');
				span.addClass('selected');
				jQuery('.trd_on',parent).attr('checked', true);
				jQuery('.trd_off',parent).attr('checked', false);
				jQuery(".set_error").html("");				
			}
		
		});
			       
    });
	
    jQuery(".cb-disable").click(function(){
		var span = jQuery(this);
        var parent = jQuery(this).parents('.switch');
		
		
		var data_name = jQuery("input", parent).attr("name");					
		var data = {
			'action': 'trd203_update'
		};
		data[data_name]=0;
		jQuery.post(ajaxurl, data, function(response) {
			if(response!="ok"){		
				jQuery(".set_error").html("Oops... We ran into some problems updating your settings.<br/>Try changing them from the Settings page in your dashboard, thank you.");				
			} else {
				jQuery('.cb-enable',parent).removeClass('selected');
				span.addClass('selected');
				jQuery('.trd_off',parent).attr('checked', true);
				jQuery('.trd_on',parent).attr('checked', false);
				jQuery(".set_error").html("");				
			}
		
		});
     
    });
	
	jQuery("input[name=show_lift]:radio").change(function(){
		var choosen_type = jQuery("input:radio[name=show_lift]:checked").val();	
		var data = {
			'action': 'trd203_update'
		};
		
		if(choosen_type=="show"){
			data["trd203_show"]=1;
			data["trd203_lift"]=0;
		} else {
			data["trd203_show"]=0;
			data["trd203_lift"]=1;
		}

		
		jQuery.post(ajaxurl, data, function(response) {
			if(response!="ok"){		
				jQuery(".show_lift_error").html("Oops... We ran into some problems updating your settings.<br/>Try changing them from the Settings page in your dashboard, thank you.");		
			} else {
				jQuery(".show_lift_error").html("");	
			}
		});
		
		
	})
	
	
	</script> 
<?php
}


register_activation_hook(__FILE__, 'activate_trd');
register_deactivation_hook(__FILE__, 'deactivate_trd');
register_uninstall_hook(__FILE__, 'uninstall_trd');



/* admin */
add_action('admin_menu', 'add_trd203_caps');
add_action('admin_menu', 'trd203_create_menu');
add_action('admin_init', 'trd203_activation_redirect');
add_action('admin_footer', 'trd203_update_javascript' ); // ajax js
add_action('wp_ajax_trd203_update', 'trd203_update_callback' ); // ajax callback


function trd203_update_callback() {
	global $wpdb;
	
	$args = array(
		'token'  => get_option("trd203_token")
	);
		
	if (isset($_POST["trd203_lift"])){
		$args["lift"]=(int)$_POST["trd203_lift"];
	}
	
	if (isset($_POST["trd203_show"])){
		$args["show"]=(int)$_POST["trd203_show"];
	}
	if (isset($_POST["trd203_mobile_lift"])){
		$args["moblift"]=(int)$_POST["trd203_mobile_lift"];
	}
	
	if (isset($_POST["trd203_hidelogo"])){
		$args["hidelogo"]=(int)$_POST["trd203_hidelogo"];
	}
	
	
	
	
	$api_response_array = trd203_get_api_answer($args, TRD203_API_URL);
	
	if($api_response_array["error"]=="1"){
		echo "error";
	} else {	
		echo "ok";
	}	

	die(); 
}

/* website */
if (!is_admin()) {
  add_action('wp_footer', 'add_trd203_script', 999);
  add_action('the_content', 'add_trd203_show_div',0);
}

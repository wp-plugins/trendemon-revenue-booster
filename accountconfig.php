<?php
// Settings page in the admin panel
function trd203_dashboard() {
	global $usernameToCodeURL, $languagesURL, $current_user;
	
	function generatePassword($length = 8) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$count = mb_strlen($chars);

		for ($i = 0, $result = ''; $i < $length; $i++) {
			$index = rand(0, $count - 1);
			$result .= mb_substr($chars, $index, 1);
		}

		return $result;
	}

?>
<div class="wrap">
<?php
$error = ""; $reg_error = ""; $message = "";
/* login button click */
if($_POST["trd-login"]){
	if (is_admin()) {
		$trd203_user = $_POST["trd-user"];
		$trd203_pass = filter_var($_POST["trd-pass"], FILTER_SANITIZE_STRING);

		if ($trd203_user!= "" && $trd203_pass != "") {	
			if (!filter_var($trd203_user, FILTER_VALIDATE_EMAIL)) {
				$error = "<b>Could not log in TrenDemon. Please check your login details.</b>";
			} else if(trd203_login_check($trd203_user, $trd203_pass)===true){
				update_option("trd203_logged", true);
				if (!get_option("trd203_step")){
					update_option("trd203_step", 3);
				}
			} else {
				$error = "<b>Could not log in TrenDemon. Please check your login details.</b>";
			}	
		} else {
			$error = "<b>Could not log in TrenDemon. Please check your login details.</b>";
		}
	}	
}


if($_POST["trd-regiter"]){
if (is_admin()) {
	$trd203_user = $_POST["trd-user"];
	$trd203_web = $_POST["trd-web"];
	$trd203_pass = generatePassword();
	

	if ($trd203_user!= "" && $trd203_web != "") {	
		if (!filter_var($trd203_user, FILTER_VALIDATE_EMAIL)) {
			$reg_error = "<b>Please enter a valid email address</b>";
		} else if (!filter_var($trd203_web, FILTER_VALIDATE_URL)) {
			$reg_error = "<b>Please enter a valid website url</b>";
		} else {
			
			$msg = trd203_login_check($trd203_user, $trd203_pass, $trd203_web, "reg");
			
			if($msg===true){
				update_option("trd203_logged", true);
				if (!get_option("trd203_step")){
					update_option("trd203_step", 3);
				}
			} else {
				$reg_error = $msg;
			}						
			
		}
	} else {
		$reg_error = "<b>Please fill in all fields</b>";
	}
}		
}


?>

<?php if(get_option("trd203_logged")){ ?>
<!-- step 2-->
<?php if(isset($_GET["settings"])){
	trd203_settings_config();
} else {
?>
<iframe src="<?php echo get_option("trd203_url"); ?>" style="width:100%; height:2000px"></iframe>
<?php
} 
?>
<?php } else { ?>
<h2>Set up your TrenDemon Account</h2>

<div id="trd_form">
	<h3>You've successfully installed the TrenDemon WordPress plugin! To start using TrenDemon, please create an account or sign in:</h3>
	<div class="trd_form trd_left_form">
		<form method="post" action="admin.php?page=trd203_dashboard&settings=1" name="trd_register_form">
			<input type="hidden" name="action" value="register">
			<h4>New Users, create your free account here:</h4>
				
			<div class="trd_inputs">
				<p>Your Website:</p>
				<input name="trd-web" class="trd_input" onfocus="if (this.value=='Your Website') this.value = ''" onblur="if (this.value=='') this.value = 'Your Website'" type="text" value="<?php echo get_site_url(); ?>" id="trd-web" />
				<p>Your Email:</p>
				<input name="trd-user" class="trd_input" onfocus="if (this.value=='Your Email') this.value = ''" onblur="if (this.value=='') this.value = 'Your Email'" type="text" value="<?php echo get_option("admin_email"); ?>" id="trd-user" />
				<input type="submit" name="trd-regiter" value="CREATE FREE" class="trd_submit" onclick="formvalidation('trd_register_form');return false;" /> 
			</div>				
		</form>
		<div style="clear:both"></div>
		<p class="trd_reg_error"><?php echo $reg_error; ?></p>
		<div style="clear:both"></div>
		<p>By signing up, you agree to our <a href="http://trendemon.com/T&C/TDTC.pdf" target="_blank">Terms of Service</a></p>
	</div>
	<div class="trd_form">
		
		<form method="post" action="admin.php?page=trd203_dashboard&settings=1" name="trd_login_form">
			<input type="hidden" name="action" value="login">
			<h4>Existing Users, log in here:</h4>
			<div class="trd_inputs">
				<p>Your Username (Email):</p>
				<input name="trd-user" class="trd_input" onfocus="if (this.value=='Your TrenDemon Username (email)') this.value = ''" onblur="if (this.value=='') this.value = 'Your TrenDemon Username (email)'" type="text" value="Your TrenDemon Username (email)" id="trd-user" />
				<p>Your Password:</p>
				<input name="trd-pass" class="trd_input" onfocus="if (this.value=='Your Password') this.value = ''" onblur="if (this.value=='') this.value = 'Your Password'" type="password" value="Your Password" id="trd-pass" />
				<a href="http://prod.trendemon.com/cm/forgotpassword" target="_blank" style="float:right">Forgot your password?</a>
				<div style="clear:both"></div>
				<input type="submit" name="trd-login" value="LOG IN" class="trd_submit" onclick="formvalidation('trd_login_form');return false;" /> 
			</div>
			<div style="clear:both"></div>
		</form>
		<p class="trd_error"><?php echo $error; ?></p>
	</div>
	<div style="clear:both"></div>
	
	
	
</div> <!-- trd_form -->



<script type="text/javascript">
function formvalidation(formname){
	document.getElementById("trd-error").innerHTML = "";
	var trd_user = document.getElementById("trd-user").value;
	var trd_pass = document.getElementById("trd-pass").value;
	
	if (trd_user=="" || trd_pass==""){
		document.getElementById("trd-error").innerHTML = "Please fill in all fields";
		return false;
	}
	
	if (!isValidEmailAddress(trd_user)){
		document.getElementById("trd-error").innerHTML = "Email address is invalid";
		return false;
	}
	
	
	formname.submit();

}

function isValidEmailAddress(emailAddress) {
    var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][\d]\.|1[\d]{2}\.|[\d]{1,2}\.))((25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\.){2}(25[0-5]|2[0-4][\d]|1[\d]{2}|[\d]{1,2})\]?$)/i);
    return pattern.test(emailAddress);
};

</script>
<?php } ?>
</div>
<?php } ?>
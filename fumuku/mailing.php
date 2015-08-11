<?php
// OPTIONS - PLEASE CONFIGURE THESE BEFORE USE!

$yourEmail = "jonjon1234.github@gmail.com"; // the email address you wish to receive these mails through
$yourWebsite = "FUMUKU International Website"; // the name of your website
$thanksPage = ''; // URL to 'thanks for sending mail' page; leave empty to keep message on the same page 
$maxPoints = 6; // max points a person can hit before it refuses to submit - recommend 4
$requiredFields = "name,email,comments"; // names of the fields you'd like to be required as a minimum, separate each field with a comma


// DO NOT EDIT BELOW HERE
$error_msg = array();
$result = null;

$requiredFields = explode(",", $requiredFields);

function clean($data) {
	$data = trim(stripslashes(strip_tags($data)));
	return $data;
}
function isBot() {
	$bots = array("Indy", "Blaiz", "Java", "libwww-perl", "Python", "OutfoxBot", "User-Agent", "PycURL", "AlphaServer", "T8Abot", "Syntryx", "WinHttp", "WebBandit", "nicebot", "Teoma", "alexa", "froogle", "inktomi", "looksmart", "URL_Spider_SQL", "Firefly", "NationalDirectory", "Ask Jeeves", "TECNOSEEK", "InfoSeek", "WebFindBot", "girafabot", "crawler", "www.galaxy.com", "Googlebot", "Scooter", "Slurp", "appie", "FAST", "WebBug", "Spade", "ZyBorg", "rabaz");

	foreach ($bots as $bot)
		if (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false)
			return true;

	if (empty($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] == " ")
		return true;
	
	return false;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	if (isBot() !== false)
		$error_msg[] = "No bots please! UA reported as: ".$_SERVER['HTTP_USER_AGENT'];
		
	// lets check a few things - not enough to trigger an error on their own, but worth assigning a spam score.. 
	// score quickly adds up therefore allowing genuine users with 'accidental' score through but cutting out real spam :)
	$points = (int)0;
	
	$badwords = array("adult", "beastial", "bestial", "blowjob", "clit", "cum", "cunilingus", "cunillingus", "cunnilingus", "cunt", "ejaculate", "fag", "felatio", "fellatio", "fuck", "fuk", "fuks", "gangbang", "gangbanged", "gangbangs", "hotsex", "hardcode", "jism", "jiz", "orgasim", "orgasims", "orgasm", "orgasms", "phonesex", "phuk", "phuq", "pussies", "pussy", "spunk", "xxx", "viagra", "phentermine", "tramadol", "adipex", "advai", "alprazolam", "ambien", "ambian", "amoxicillin", "antivert", "blackjack", "backgammon", "texas", "holdem", "poker", "carisoprodol", "ciara", "ciprofloxacin", "debt", "dating", "porn", "link=", "voyeur", "content-type", "bcc:", "cc:", "document.cookie", "onclick", "onload", "javascript");

	foreach ($badwords as $word)
		if (
			strpos(strtolower($_POST['comments']), $word) !== false || 
			strpos(strtolower($_POST['name']), $word) !== false
		)
			$points += 2;
	
	if (strpos($_POST['comments'], "http://") !== false || strpos($_POST['comments'], "www.") !== false)
		$points += 2;
	if (isset($_POST['nojs']))
		$points += 1;
	if (preg_match("/(<.*>)/i", $_POST['comments']))
		$points += 2;
	if (strlen($_POST['name']) < 3)
		$points += 1;
	if (strlen($_POST['comments']) < 15 || strlen($_POST['comments'] > 1500))
		$points += 2;
	if (preg_match("/[bcdfghjklmnpqrstvwxyz]{7,}/i", $_POST['comments']))
		$points += 1;
	// end score assignments

	foreach($requiredFields as $field) {
		trim($_POST[$field]);
		
		if (!isset($_POST[$field]) || empty($_POST[$field]) && array_pop($error_msg) != "Please fill in all the required fields and submit again.\r\n")
			$error_msg[] = "Please fill in all the required fields and submit again.";
	}

	if (!empty($_POST['name']) && !preg_match("/^[a-zA-Z-'\s]*$/", stripslashes($_POST['name'])))
		$error_msg[] = "The name field must not contain special characters.\r\n";
	if (!empty($_POST['email']) && !preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i', strtolower($_POST['email'])))
		$error_msg[] = "That is not a valid e-mail address.\r\n";
	if (!empty($_POST['url']) && !preg_match('/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i', $_POST['url']))
		$error_msg[] = "Invalid website url.\r\n";
	
	if ($error_msg == NULL && $points <= $maxPoints) {
		$subject = "Automatic Form Email";
		
		$message = "You received this e-mail message through your website: \n\n";
		foreach ($_POST as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $subval) {
					$message .= ucwords($key) . ": " . clean($subval) . "\r\n";
				}
			} else {
				$message .= ucwords($key) . ": " . clean($val) . "\r\n";
			}
		}
		$message .= "\r\n";
		$message .= 'IP: '.$_SERVER['REMOTE_ADDR']."\r\n";
		$message .= 'Browser: '.$_SERVER['HTTP_USER_AGENT']."\r\n";
		$message .= 'Points: '.$points;

		if (strstr($_SERVER['SERVER_SOFTWARE'], "Win")) {
			$headers   = "From: $yourEmail\r\n";
		} else {
			$headers   = "From: $yourWebsite <$yourEmail>\r\n";	
		}
		$headers  .= "Reply-To: {$_POST['email']}\r\n";

		if (mail($yourEmail,$subject,$message,$headers)) {
			if (!empty($thanksPage)) {
				header("Location: $thanksPage");
				exit;
			} else {
				$result = 'Your mail was successfully sent.';
				$disable = true;
			}
		} else {
			$error_msg[] = 'Your mail could not be sent this time. ['.$points.']';
		}
	} else {
		if (empty($error_msg))
			$error_msg[] = 'Your mail looks too much like spam, and could not be sent this time. ['.$points.']';
	}
}
function get_data($var) {
	if (isset($_POST[$var]))
		echo htmlspecialchars($_POST[$var]);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>My Email Form</title>
	
	<style type="text/css">
		p.error, p.success {
			font-weight: bold;
			padding: 10px;
			border: 1px solid;
		}
		p.error {
			background: #ffc0c0;
			color: #900;
		}
		p.success {
			background: #b3ff69;
			color: #4fa000;
		}
	</style>
</head>
<body>
<!-- Copyright (c) OpenGlobal. GNU/GPL V3 licence. You may copy and modify this, providing the link to http://www.openglobal.co.uk remains intact. -->
<div id="openglobal_privacy_widget" style="display: inline; text-align:right; font-size: 13px; line-height: 100%; position: fixed; top: 0; right: 0; margin: 0; padding: 0 0 0 3px; background: #dddddd; z-index: 100000; opacity:0.9; filter: alpha(opacity=90);">
Accept <a title="This website uses cookies to store information on your computer. Some of these cookies are used for visitor analysis, others may be necessary for the website to function properly. You should configure your browser to only accept the cookies you wish to approve, or leave this website." rel="privacy" href="privacypolicy.html">Cookies</a>?
<button id="openglobal_privacy_accept" style="vertical-align: middle;" onclick="openglobal_privacy_accept();return false;">Yes</button>
<button id="openglobal_privacy_wait" style="vertical-align: middle;" onclick="clearTimeout(openglobal_privacy_timer);return false;">Wait</button>
<button id="openglobal_privacy_leave" style="vertical-align: middle;" onclick="window.location='http://www.change.org/petitions/stop-the-eu-s-legal-war-on-web-cookies';">Leave</button>
<br />
<span style="font-size: 9px">Provided by <a href="http://www.openglobal.co.uk" title="OpenGlobal e-commerce web design and promotion">OpenGlobal E-commerce</a></span>
</div>
<script type="text/javascript">
//<![CDATA[
var openglobal_privacy_timeout = 0;
var openglobal_privacy_functions = [];

var openglobal_privacy_widget = document.getElementById('openglobal_privacy_widget');
var results = document.cookie.match ( '(^|;) ?openglobal_privacy_widget=([^;]*)(;|$)' );
if (results) {
  if (1 == unescape(results[2])) {
    openglobal_privacy_accept();
  }
} else {
  window.onload = function() {
    for (var i = 0; i < document.links.length; i++) {
      var link_href = document.links[i].getAttribute('href');
      if ('privacy' != document.links[i].getAttribute('rel') && (!/^[\w]+:/.test(link_href) || (new RegExp('^[\\w]+://[\\w\\d\\-\\.]*' + window.location.host)).test(link_href))) {
        var current_onclick = document.links[i].onclick;
document.links[i].onclick = function() {openglobal_privacy_accept();if (Object.prototype.toString.call(current_onclick) == '[object Function]') {current_onclick();}};
      }
    }
  };
}

var openglobal_privacy_timer;
if (openglobal_privacy_timeout > 0) {
   openglobal_privacy_timer = setTimeout('openglobal_privacy_tick()', 1000);
} else {
  var openglobal_privacy_wait = document.getElementById('openglobal_privacy_wait');
  if (null != openglobal_privacy_wait) {
    openglobal_privacy_wait.parentNode.removeChild(openglobal_privacy_wait);
  }
}
function openglobal_privacy_tick() {
  if (0 >= --openglobal_privacy_timeout) {
    openglobal_privacy_accept();
    return;
  }
  var openglobal_privacy_accept_button = document.getElementById('openglobal_privacy_accept');
  if (null != openglobal_privacy_accept_button) {
    openglobal_privacy_accept_button.innerHTML = 'Yes (' + openglobal_privacy_timeout + ')';
    openglobal_privacy_timer = setTimeout('openglobal_privacy_tick()', 1000);
  }
}

function openglobal_privacy_accept() {
  clearTimeout(openglobal_privacy_timer);
  document.cookie = 'openglobal_privacy_widget=1; path=/; expires=Mon, 18 Jan 2038 03:14:00 GMT';
  openglobal_privacy_widget.parentNode.removeChild(openglobal_privacy_widget);
  for (var i = 0; i < openglobal_privacy_functions.length; i++) {
    openglobal_privacy_functions[i]();
  }
}
//]]>
</script>

<!--
	Free PHP Mail Form v2.4.4 - Secure single-page PHP mail form for your website
	Copyright (c) Jem Turner 2007-2014
	http://jemsmailform.com/

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	To read the GNU General Public License, see http://www.gnu.org/licenses/.
-->

<?php
if (!empty($error_msg)) {
	echo '<p class="error">ERROR: '. implode("<br />", $error_msg) . "</p>";
}
if ($result != NULL) {
	echo '<p class="success">'. $result . "</p>";
}
?>



<form action="<?php echo basename(__FILE__); ?>" method="post">
<noscript>
		<p><input type="hidden" name="nojs" id="nojs" /></p>
</noscript>
<p>
	<label for="name">Name: *</label> 
		<input type="text" name="name" id="name" value="<?php get_data("name"); ?>" /><br />
	
	<label for="email">E-mail: *</label> 
		<input type="text" name="email" id="email" value="<?php get_data("email"); ?>" /><br />
	
	<label for="url">Website URL:</label> 
		<input type="text" name="url" id="url" value="<?php get_data("url"); ?>" /><br />
		
	<label for="location">Location:</label>
		<input type="text" name="location" id="location" value="<?php get_data("location"); ?>" /><br />
	
	<label for="comments">Comments: *</label>
		<textarea name="comments" id="comments" rows="5" cols="20"><?php get_data("comments"); ?></textarea><br />
</p>
<p>
	<input type="submit" name="submit" id="submit" value="Send" <?php if (isset($disable) && $disable === true) echo ' disabled="disabled"'; ?> />
</p>
</form>

<p>Powered by <a href="http://jemsmailform.com/">Jem's PHP Mail Form</a></p>


</body>
</html>

<?php

require_once dirname(__FILE__) . "/lib/fireeagle.php";

session_start();
ob_start();

function check_fe_token($tok) {
	return isset($tok['oauth_token'])
		&& is_string($tok['oauth_token'])
		&& isset($tok['oauth_token_secret'])
		&& is_string($tok['oauth_token_secret']);
}

function main() {
	require_once dirname(__FILE__) . "/fe_config.php";
	
	ob_start();
	session_start();

	if (@$_GET['f'] == 'start') {
		$fe = new FireEagle($fe_key, $fe_secret);
		$tok = $fe->getRequestToken();
		if (!check_fe_token($tok)) {
			echo "ERROR! FireEagle::getRequestToken() returned an invalid response.";
		}
		else {
			$_SESSION['auth_state'] = "start";
			$_SESSION['request_token'] = $token = $tok['oauth_token'];
			$_SESSION['request_secret'] = $tok['oauth_token_secret'];

			$callback_url = "http://" . $_SERVER['HTTP_HOST'];
			$callback_url .= "/wp-admin/plugins.php?page=eagle_eye.php";
			$callback_url .= "&f=callback";

			$url = $fe->getAuthorizeURL($token);
			$url .= "&oauth_callback=".urlencode($callback_url);

			header("Location: ".$url);
		}
	}
}

main();

?>

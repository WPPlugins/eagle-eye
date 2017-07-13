<?php

/*
 * Plugin Name: Eagle Eye
 * Plugin URI: http://blog.adonis.net/?page_id=726
 * Description: Displays information about your whereabouts in the sidebar.
 * Version: 1.0
 * Author: Patrick Morris
 * Author URI: http://blog.adonis.net
 */

ob_start();
session_start();

require_once dirname(__FILE__) . "/fe_config.php";
require_once dirname(__FILE__) . "/lib/fireeagle.php";

/*
 * Function to execute when plugin is added
 */
register_activation_hook( __FILE__, 'eagle_eye_activate');

function eagle_eye_activate() {
	$ee_options = array(
		"ee_enable_map" => TRUE,
		"ee_enable_streetview" => TRUE,
		"ee_enable_widget" => FALSE,
		"fe_interval" => 60,
		// "gm_api_url" => "http://maps.google.com/maps?file=api&v=2.x",
		"gm_api_key" => "",
		"gm_enable_map" => TRUE,
		"gm_use_static_map" => TRUE,
		"gm_use_dynamic_map" => TRUE,
		"gm_map_width" => 200,
		"gm_map_height" => 200,
		"gm_map_zoom" => 10,
		"gm_enable_streetview" => TRUE,
		// "ig_api_url" => "http://www.google.com/ig/api",
		"ig_enable_weather" => TRUE,
		"ig_enable_weather_icon" => TRUE,
		"ig_weather_icon_url" => "http://www.google.com/ig",
		"ig_weather_icon_height" => 40,
		"ig_weather_icon_width" => 40,
		"widget_title" => "Eagle Eye"
	);
	add_option("ee_options", $ee_options, '', 'yes');
}

/*
 * Function to execute when plugin is removed
 */
register_deactivation_hook( __FILE__, 'eagle_eye_deactivate' );

function eagle_eye_deactivate() {
	delete_option("ee_options");
}

/*
 * Add Eagle Eye to Plugin menu
 */
function eagle_eye_add_menu() {
	if (function_exists('add_options_page')) {
		add_submenu_page('plugins.php',
		'Eagle Eye Options',
		'Eagle Eye',
		8,
		basename(__FILE__),
		'eagle_eye_options_page');
	}
}
add_action('admin_menu', 'eagle_eye_add_menu');

function eagle_eye_options_page() {
	include dirname(__FILE__) . "/fe_config.php";

	$ee_options = get_option("ee_options");

	if(@$_GET['f'] == 'callback') {
		/*
		 * Return from request for Fire Eagle authorization
		 */
		if (@$_SESSION['auth_state'] != "start") {
		   echo "Out of sequence.";
		}
		if ($_GET['oauth_token'] != $_SESSION['request_token']) {
			echo "Token mismatch.";
		}

		$fe = new FireEagle(
			$fe_key,
			$fe_secret, 
			$_SESSION['request_token'],
			$_SESSION['request_secret']);

		try {
			$tok = $fe->getAccessToken();
		} catch (FireEagleException $e) {
			echo $e->getMessage();
		}

		if (isset($tok['oauth_token'])
				&& is_string($tok['oauth_token'])
				&& isset($tok['oauth_token_secret'])
				&& is_string($tok['oauth_token_secret'])) {

			$ee_options['fe_access_token'] = $tok['oauth_token'];
			$ee_options['fe_access_secret'] = $tok['oauth_token_secret'];
			$ee_options['fe_auth_state'] = 'done';

			$_SESSION['fe_access_token'] = $tok['oauth_token'];
			$_SESSION['fe_access_secret'] = $tok['oauth_token_secret'];
			$_SESSION['fe_auth_state'] = 'done';

			$ee_options['ee_enable_widget'] = 1;
			$ee_options['fe_last_updated'] = time();

			update_option('ee_options', $ee_options);
		}
	}

	if ($_POST['ee_send']) {
		/*
		 * Save form variables
		 */
		$ee_options['fe_interval'] = $_POST['ee_fe_interval'];
		// $ee_options['gm_api_url'] = $_POST['ee_gm_api_url'];
		$ee_options['gm_api_key'] = $_POST['ee_gm_api_key'];
		$ee_options['gm_enable_map'] = $_POST['ee_gm_enable_map'];
		$ee_options['gm_enable_streetview'] = $_POST['ee_gm_enable_streetview'];
		$ee_options['gm_use_static_map'] = $_POST['ee_gm_use_static_map'];
		$ee_options['gm_use_dynamic_map'] = $_POST['ee_gm_use_dynamic_map'];
		$ee_options['gm_map_width'] = $_POST['ee_gm_map_width'];
		$ee_options['gm_map_height'] = $_POST['ee_gm_map_height'];
		$ee_options['gm_map_zoom'] = $_POST['ee_gm_map_zoom'];
		// $ee_options['ig_api_url'] = $_POST['ee_ig_api_url'];
		$ee_options['ig_enable_weather'] = $_POST['ee_ig_enable_weather'];
		$ee_options['ig_enable_weather_icon'] = $_POST['ee_ig_enable_weather_icon'];
		$ee_options['ig_weather_icon_url'] = $_POST['ee_ig_weather_icon_url'];
		$ee_options['ig_weather_icon_height'] = $_POST['ee_ig_weather_icon_height'];
		$ee_options['ig_weather_icon_width'] = $_POST['ee_ig_weather_icon_width'];
		$ee_options['widget_title']  = $_POST['ee_widget_title'];


		update_option('ee_options', $ee_options);
		print '<div class="updated fade" id="message" style="background-color: rgb(255, 251, 204);"><p><strong>Settings saved.</strong></p></div>';
	}
?>

<div class="wrap">
	<h2>Eagle Eye Configuration</h2>
	<form id="eagle_eye" class="form-table" method="post" action="">
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="widget_title">Widget Title</label>
					</th>
					<td>
						<input type="text" name="ee_widget_title" id="ee_widget_title" value="<?php echo $ee_options['widget_title'] ?>" /><br>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="fire_eagle_auth">Authorize Fire Eagle</label>
					</th>
					<td>
<?php
		if ($ee_options['fe_auth_state'] == 'done') {
?>
						Done (<a href="http://<?php print $_SERVER['HTTP_HOST'] ?>/wp-content/plugins/eagle-eye/fe_redirect.php?f=start">Edit</a>)
<?php						
		} else {
?>
						<a href="http://<?php print $_SERVER['HTTP_HOST'] ?>/wp-content/plugins/eagle-eye/fe_redirect.php?f=start">Register Eagle Eye with Fire Eagle</a>
<?php
		}
?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_fe_interval">Fire Eagle Update Interval (in minutes)</label>
					</th>
					<td>
						<input type="text" name="ee_fe_interval" id="ee_fe_interval" value="<?php echo $ee_options['fe_interval'] ?>" /><br>
						How often (in minutes) to poll Fire Eagle for your current location.
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_gm_api_key">Google Maps API Key</label>
					</th>
					<td>
						<input type="text" name="ee_gm_api_key" id="ee_gm_api_key" size="100" value="<?php echo $ee_options['gm_api_key'] ?>" /><br>
						To obtain a Google Maps API key, <a href="http://code.google.com/apis/maps/signup.html" target="_blank">click here</a>.
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_gm_enable_map">Enable Map</label>
					</th>
					<td>
						<input type="checkbox" name="ee_gm_enable_map" id="ee_gm_enable_map" value="1" <?php if ($ee_options['gm_enable_map'] == 1) print 'checked="checked"'; ?>/>
						Display a map of your current location.
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_gm_use_static_map">Use Static Maps</label>
					</th>
					<td>
						<input type="checkbox" name="ee_gm_use_static_map" id="ee_gm_use_static_map" value="1" <?php if ($ee_options['gm_use_static_map'] == 1) print 'checked="checked"'; ?>/>
						Use static map images from Google Maps
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_gm_use_dynamic_map">Use Dynamic Maps</label>
					</th>
					<td>
						<input type="checkbox" name="ee_gm_use_dynamic_map" id="ee_gm_use_dynamic_map" value="1" <?php if ($ee_options['gm_use_dynamic_map'] == 1) print 'checked="checked"'; ?>/>
						Use dynamic maps from Google Maps
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_gm_map_height">Map Height</label>
					</th>
					<td>
						<input type="text" name="ee_gm_map_height" id="ee_gm_map_height" value="<?php echo $ee_options['gm_map_height'] ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_gm_map_width">Map Width</label>
					</th>
					<td>
						<input type="text" name="ee_gm_map_width" id="ee_gm_map_width" value="<?php echo $ee_options['gm_map_width'] ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_gm_map_zoom">Map Zoom</label>
					</th>
					<td>
						<input type="text" name="ee_gm_map_zoom" id="ee_gm_map_zoom" value="<?php echo $ee_options['gm_map_zoom'] ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ee_gm_enable_streetview">Enable Street View</label>
					</th>
					<td>
						<input type="checkbox" name="ee_gm_enable_streetview" id="ee_gm_enable_streetview" value="1" <?php if ($ee_options['gm_enable_streetview'] == 1) print 'checked="checked"'; ?> />
						Display a street-level view of your current location, when available.
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ee_ig_enable_weather">Enable Weather Display</label>
					</th>
					<td>
						<input type="checkbox" name="ee_ig_enable_weather" id="ee_ig_enable_weather" value="1" <?php if ($ee_options['ig_enable_weather'] == 1) print 'checked="checked"'; ?> />
						Display information about the weather at your current location.
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ee_ig_enable_weather_icon">Display Weather Icons</label>
					</th>
					<td>
						<input type="checkbox" name="ee_ig_enable_weather_icon" id="ee_ig_enable_weather_icon" value="1" <?php if ($ee_options['ig_enable_weather_icon'] == 1) print 'checked="checked"'; ?> />
						Display icon along with weather information when weather display is enabled.
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ee_ig_weather_icon_url">URL for Weather Icons</label>
					</th>
					<td>
						<input type="text" name="ee_ig_weather_icon_url" id="ee_ig_weather_icon_url" size="60" value="<?php echo $ee_options['ig_weather_icon_url'] ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ee_ig_weather_icon_height">Height of Weather Icons</label>
					</th>
					<td>
						<input type="text" name="ee_ig_weather_icon_height" id="ee_ig_weather_icon_height" value="<?php echo $ee_options['ig_weather_icon_height'] ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ee_ig_weather_icon_width">Width of Weather Icons</label>
					</th>
					<td>
						<input type="text" name="ee_ig_weather_icon_width" id="ee_ig_weather_icon_width" value="<?php echo $ee_options['ig_weather_icon_width'] ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="ee_send" id="ee_send" value="true" />
		<p class="submit"><input type="submit" value="Save Changes" /></p>
	</form>
</div>

<?php

}

function eagle_eye() {
	$ee_options = get_option('ee_options');
	$ee = new EagleEye(
			$ee_options['fe_access_token'],
			$ee_options['fe_access_secret'],
			$ee_options['gm_api_key'],
			$ee_options['fe_interval'],
			$ee_options['gm_enable_map'],
			$ee_options['gm_enable_streetview'],
			$ee_options['ig_enable_weather'],
			$ee_options['ig_enable_weather_icon'],
			$ee_options['gm_map_height'],
			$ee_options['gm_map_width'],
			$ee_options['gm_use_static_map'],
			$ee_options['gm_use_dynamic_map'],
			$ee_options['gm_map_zoom'],
			$ee_options['ig_weather_icon_url'],
			$ee_options['ig_weather_icon_height'],
			$ee_options['ig_weather_icon_width']);

	if (!$ee->error) {
		$ee->display();
	}
}

class EagleEye {
	private	$enableMap;
	private $enableSv;
	private $enableWeather;
	private $enableWeatherIcon;
	private $feAccessToken;
	private $feAccessSecret;
	private $feInterval;
	private $feKey;
	private $feLastUpdated;
	private $feLoc;
	private $feSecret;
	private $gmApiKey;
	private $mapHeight;
	private $mapWidth;
	private $wiHeight;
	private $wiWidth;
	private $wiUrl;

	public $error;

	function __construct(
			$feAccessToken,
			$feAccessSecret,
			$gmApiKey,
			$feInterval = 60,
			$enableMap = TRUE,
			$enableSv = TRUE,
			$enableWeather = TRUE,
			$enableWeatherIcon = TRUE,
			$mapHeight = 200,
			$mapWidth = 200,
			$useStaticMap = TRUE,
			$useDynamicMap = TRUE,
			$mapZoom = 10,
			$wiUrl = "http://www.google.com/ig",
			$wiHeight = 40,
			$wiWidth = 40) {

		include dirname(__FILE__) . "/fe_config.php";

		$this->feKey = $fe_key;
		$this->feSecret = $fe_secret;
		
		if (empty($gmApiKey) 
				|| empty($feAccessToken)
				|| empty($feAccessSecret)) {
			$this->error = true; 
		} else {
			$this->feAccessToken = $feAccessToken;
			$this->feAccessSecret = $feAccessSecret;
			$this->gmApiKey = $gmApiKey;

			$this->feInterval = $feInterval;
			$this->enableMap = $enableMap;
			$this->enableSv = $enableSv;
			$this->enableWeather = $enableWeather;
			$this->enableWeatherIcon = $enableWeatherIcon;
			$this->mapHeight = $mapHeight;
			$this->mapWidth = $mapWidth;
			$this->useStaticMap = $useStaticMap;
			$this->useDynamicMap = $useDynamicMap;
			$this->mapZoom = $mapZoom;
			$this->wiUrl = $wiUrl;
			$this->wiHeight = $wiHeight;
			$this->wiWidth = $wiWidth;

			$this->feLastUpdated = $ee_options['fe_last_updated'];
		}
		
	}
	function display() {
		$current_timestamp = time();
		$ref_time = $this->feLastUpdated + ($this->feInterval * 60);
	
		if ($current_timestamp > $ref_time) {
			$this->updateLocation();
		}

		$ee_options = get_option('ee_options');

		$this->printLocation();

		if ($this->enableMap) {
			$this->printMapDiv();
		}
		if ($this->enableSv) {
			$this->printSvDiv();
		}
		if ($this->enableSv) {
			$this->printWeather();
		}
		$this->printJavascript();
	}

	function printJavascript() {
		$ee_options = get_option('ee_options');
?>
<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php print $this->gmApiKey ?>" type="text/javascript"></script>

<script type="text/javascript">
function WindowOnload(func) {
	var prev = window.onload;
	window.onload = function() { 
		if(prev) prev(); 
		func(); 
	}
}

function ee_onload() {
<?php
		if($this->enableMap) {
?>
	if (GBrowserIsCompatible()) {
		var div = document.getElementById("ee_map");
		var map = new GMap2(div);
		var loc = new GLatLng(<?php print $ee_options['loc_latitude'].','.$ee_options['loc_longitude'] ?>);

		map.setCenter(loc, <?php print $this->mapZoom ?>);
		map.addOverlay(new GMarker(loc));
		map.addControl(new GSmallMapControl());
		map.addControl(new GMapTypeControl());
<?php
		}
		if($this->enableSv) {
?>
		function processReturnedData(panoData) {
			if (panoData.code != 200) {
  				//GLog.write('showPanoData: Server rejected with code: ' + panoData.code);
  				return;
			}

			myPano.setLocationAndPOV(panoData.location.latlng);
			$('pano').style.display = 'block';
		}
									  
		var myPano = new GStreetviewPanorama(document.getElementById("ee_sv"));
		panoClient = new GStreetviewClient();
		panoClient.getNearestPanorama(loc, processReturnedData);
<?php
		}
?>
	}
}

WindowOnload( ee_onload );

</script>
<?php
	}

	function printLocation() {
		$ee_options = get_option('ee_options');

?>
	<b><?php print $ee_options['loc_name'] ?></b>
<?php
	}

	function printMapDiv() {
		$ee_options = get_option('ee_options');

		$surl = 'http://maps.google.com/staticmap?center=';
		$surl .= $ee_options['loc_latitude'];
		$surl .= ',';
		$surl .= $ee_options['loc_longitude'];
?>
	<center><div id="ee_map" style="width: <?php print $this->mapWidth ?>px; height: <?php print $this->mapHeight ?>px; border: solid 1px; #ccc;">
<?php
		if ($this->useStaticMap) {
			$surl = 'http://maps.google.com/staticmap?markers=';
			$surl .= $ee_options['loc_latitude'];
			$surl .= ',';
			$surl .= $ee_options['loc_longitude'];
			$surl .= '&zoom=' . $this->mapZoom;
			$surl .= '&size=' . $this->mapWidth;
			$surl .= 'x' . $this->mapHeight;
			$surl .= '&key=' . $this->gmApiKey;

			print '<img src="' . $surl . '" style="border: solid 1px; #ccc;">';
		}
?>
	</div></center>
<?php
	}

	function printSvDiv() {
?>
	<center><div id="ee_sv" style="width: <?php print $this->mapWidth ?>px; ?>px; margin-top: 5px; border: solid 1px; #ccc;"></div></center>
<?php
	}

	function printWeather() {
		$ee_options = get_option('ee_options');

		$url = "http://www.google.com/ig/api?weather=";
		$url .= urlencode($ee_options['loc_location']);
		$ch = curl_init($url);
		$tmpnam = tempnam('/tmp', 'weather.xml');
		$fp = fopen($tmpnam, "w");
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		$xml = simplexml_load_file($tmpnam);
		unlink ($tmpnam);

		$tempc = $xml->weather->current_conditions->temp_c['data'];
		$tempf = $xml->weather->current_conditions->temp_f['data'];
		$cond = $xml->weather->current_conditions->condition['data'];
		$icon = $xml->weather->current_conditions->icon['data'];
?>
<center><table width="<?php print $this->mapWidth ?>" height="<?php print $this->wiHeight ?>" border="0" cellpadding="0" cellspacing="0" style="margin-top: 5px;">
	<tr>
		<td>
			<img src="<?php print $this->wiUrl . $icon ?>">
		</td>
		<td width="100%">
			<table height="<?php print $this->wiHeight ?>" width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td align="right" valign="top">Temperature: <?php print $tempf; ?>&deg;F, <?php print $tempc; ?>&deg;C</td>
				</tr>
				<tr>
					<td align="right" valign="top"><?php print $cond ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table></center>
<?php
	}

	function updateLocation() {
		$ee_options = get_option('ee_options');

		$fe = new FireEagle( 
			$this->feKey,
			$this->feSecret,
			$this->feAccessToken,
			$this->feAccessSecret);

		$this->feLoc = $fe->user();

		$ee_options['loc_longitude'] = $this->feLoc->user->best_guess->longitude;
		$ee_options['loc_latitude'] = $this->feLoc->user->best_guess->latitude;
		$ee_options['loc_name'] = htmlspecialchars($this->feLoc->user->best_guess->name);
		$ee_options['loc_location'] = htmlspecialchars($this->feLoc->user->location_hierarchy[2]->name);
		$ee_options['fe_last_updated'] = $this->feLastUpdated = time();

		update_option('ee_options', $ee_options);
	}
}

/*
 * Eagle Eye Widget
 */

function widget_eagle_eye_init() {
	if (!function_exists('register_sidebar_widget')) {
		return;
	}

	function widget_eagle_eye($args) {
		extract($args);
		$ee_options = get_option('ee_options');
		$title = $ee_options['widget_title'];
	?>
		<?php echo $before_widget; ?>
			<?php echo $before_title
				. $title
				. $after_title; ?>
				<ul id="ee_display">
					<?php eagle_eye(); ?>
				</ul>
		<?php echo $after_widget; ?>
	<?php
	}

	register_sidebar_widget('Eagle Eye', 'widget_eagle_eye');

	function widget_eagle_eye_control() {
		$ee_options = get_option('ee_options');
		$title = $ee_options['widget_title'];

		if (!empty($_POST['ee_widget_title'])) {
			$title = strip_tags(stripslashes($_POST['ee_widget_title']));
			$ee_options['widget_title'] = $title;
			update_option('ee_optionss', $ee_options);
		}

		$title = htmlspecialchars($title, ENT_QUOTES);
		?>
			<p>
				<label for="ee_widget_title">
					Title:
					<input type="text" id="ee_widget_title" name="ee_widget_title" value="<?php echo $title; ?>" />
				</label>
			</p>
		<?php
	}
	register_widget_control('Eagle Eye', widget_eagle_eye_control, 200, 500);
}

add_action('widgets_init', 'widget_eagle_eye_init');

function eagle_eye_head() {
	$ee_options = get_option('ee_options');
}

add_action('wp_head', 'eagle_eye_head');

?>

<?Php
/**
* Plugin Name: Prelude Version
* Plugin URI: http://www.prelude-prod.fr
* Description: Permet de récupérer les informations de version de WordPress ainsi que des plugins installés.
* Version: 1.1.0
* Author: Jean-François RENAULD
* Author URI: http://www.prelude-prod.fr
* License: GPL2
**/


// patch URL
define('PRELUDE_VERSION_BASENAME', plugin_basename(__FILE__));
define('PRELUDE_VERSION_DIR_URL', plugins_url('', PRELUDE_VERSION_BASENAME));

// internationnalisation
load_plugin_textdomain('prelude-version', false, PRELUDE_VERSION_BASENAME.'/languages' );

function prelude_version_init() {
	global $wp_rewrite;
	$prelude_version_options = get_option('prelude_version_plugin_options');
	add_feed($prelude_version_options['url'], 'prelude_version_get');
	$wp_rewrite->flush_rules();
}

add_action('init', 'prelude_version_init', 10, 0);

if(function_exists('get_plugins') === FALSE) {
	require_once ABSPATH.'wp-admin/includes/plugin.php';
}
/**
 * Et c'est là que tout se passe
 */
function prelude_version_get() {
	if(isset($_REQUEST['pass']) !== FALSE) {
		$password = $_REQUEST['pass'];
		
	} else {
		$password = '';
	}
	$prelude_version_options = get_option('prelude_version_plugin_options');
	$newLine = "\n";
	
	// si pas le bon mot de passe, affichage d'une mauvaise version
	if($prelude_version_options['password'] != $password) {
		$info = '4.1';
		$plugins = array('Hello Dolly' => array('Name' => 'Hello Dolly', 'Version' => '1.6'));
		
	} else {
		$info = get_bloginfo('version');
		$allPlugins = get_plugins();
		
		// recherche des "slugs" de chaque plugin actif
		$plugins = array();
		foreach($allPlugins as $key => $plugin) {
			if(is_plugin_active($key) === TRUE) {
				$plugins[$key] = $plugin;
			}
		}		
	}

	
	$theme = wp_get_theme();
	
	header('Content-Type: text/xml');
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 10 Jul 1990 05:00:00 GMT");
	
	$xml = '<?xml version="1.0" encoding="UTF-8"?>'.$newLine;
	$xml .= '<items>'.$newLine;
	$xml .= '	<version>'.$info.'</version>'.$newLine;
	$xml .= '	<theme>'.$newLine;
	$xml .= '		<name>'.$theme->get('Name').'</name>'.$newLine;
	$xml .= '		<version>'.$theme->get('Version').'</version>'.$newLine;
	$xml .= '	</theme>'.$newLine;
	$xml .= '	<plugins>'.$newLine;
	foreach($plugins as $key => $ePlugin) {
		$pluginURI = $ePlugin['PluginURI'];
		$pluginURI = str_replace('&', '&amp;', $pluginURI);
		$xml .= '		<plugin>'.$newLine;
		$xml .= '			<slug>'.$key.'</slug>'.$newLine;
		$xml .= '			<name>'.$ePlugin['Name'].'</name>'.$newLine;
		$xml .= '			<version>'.$ePlugin['Version'].'</version>'.$newLine;
		$xml .= '			<uri>'.$pluginURI.'</uri>'.$newLine;
		$xml .= '		</plugin>'.$newLine;
	}
	$xml .= '	</plugins>'.$newLine;
	$xml .= '</items>'.$newLine;
	echo $xml;
}

function prelude_version_plugin_cb() {

	// Rappel option

}

/**
 * Génère le formulaire de champ de saisie des paramètres [url]
 */
function prelude_version_url_html() {
	$prelude_version_options = get_option('prelude_version_plugin_options');
	echo "<input name='prelude_version_plugin_options[url]' type='text' value='{$prelude_version_options['url']}'/>";
}

/**
 * Génère le formulaire de champ de saisie des paramètres [password]
 */
function prelude_version_password_html() {
	$prelude_version_options = get_option('prelude_version_plugin_options');
	echo "<input name='prelude_version_plugin_options[password]' type='text' value='{$prelude_version_options['password']}'/>";
}

/**
 * Tous les paramètres et la configuration des champs utilisé dans wordpress
 */
function prelude_version_register_settings_and_fields() {

	// $option_group, $option_name, $sanitize_callback
	register_setting('prelude_version_plugin_options','prelude_version_plugin_options');

	// $id, $title, $callback, $page
	add_settings_section('prelude_version_plugin_main_section', __('Réglages principaux', 'prelude-version'), 'prelude_version_plugin_cb', __FILE__);

	// $id, $title, $callback, $page, $section, $args
	
	add_settings_field('url', __('URL : ', 'prelude-url'), 'prelude_version_url_html', __FILE__, 'prelude_version_plugin_main_section');

	// $id, $title, $callback, $page, $section, $args
	add_settings_field('password', __('Mot de passe : ', 'prelude-version'), 'prelude_version_password_html', __FILE__, 'prelude_version_plugin_main_section');
}

add_action('admin_init', 'prelude_version_register_settings_and_fields');


/**
 * Génére le code HTML de la page des options principales
 */
function prelude_version_options_page_html() {
	$prelude_version_options = get_option('prelude_version_plugin_options');
	$urlBlog = get_bloginfo('url');
	// url de type /?feed=
	$urlAtom = get_bloginfo('atom_url');
	if(strpos($urlAtom, '?') !== FALSE) {
		$urlXML = $urlBlog.'/?feed='.$prelude_version_options['url'].'&';
		
	} else {
		$urlXML = $urlBlog.'/'.$prelude_version_options['url'].'/?';
	}
	
	if($prelude_version_options['password'] == '') {
		$urlXML = substr($urlXML, 0, strlen($urlXML) - 1);
		
	} else {
		$urlXML = $urlXML.'pass='.$prelude_version_options['password'];
	}
	?>
	<div class="wrap">
		<h2>Prélude Version Options</h2>
		<p><?php _e('Le fichier XML sera accessible à l\'URL ci-dessous.', 'prelude-version'); ?></p>
		<p><?php _e('Vous devez rajouter le mot de passe à la fin :', 'prelude-version'); ?></p>
		
		<p><strong><?php echo $urlXML; ?></strong></p>
		<form method="post" action="options.php" enctype="multipart/form-data">
			<?php
			// $option_group
			settings_fields('prelude_version_plugin_options');
                
			// $page
			do_settings_sections( __FILE__ );
			?>
			<p class="submit">
				<input type="submit" class="button-primary" name="submit" value="<?php _e('Sauvegarder', 'prelude-version'); ?>">
			</p>
		</form>
	</div>
    <?php
}


/**
 * Menu Admin Activation
 */
function prelude_version_options_init() {
	// page_title,  menu_title, capability, menu_slug, function
	add_options_page(__('Options de Prélude Version', 'prelude-version'), __('Prelude Version Options', 'prelude-version'), 'administrator', __FILE__, 'prelude_version_options_page_html');
}
add_action('admin_menu', 'prelude_version_options_init');

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'prelude_version_plugin_add_settings_link');
function prelude_version_plugin_add_settings_link($links){
	$settings = '<a href="'.esc_url( get_admin_url(null, 'options-general.php?page='.PRELUDE_VERSION_BASENAME)). '">' . __( 'Settings', 'prelude-version' ) . '</a>';
	array_push($links, $settings);
	return $links;
}


/**
 * Activation et vérification des paramètres si ils existent.
 */
function prelude_version_activate() {
	$password = '';
	for($num = 0; $num < 10; $num++) {
		if(mt_rand(0, 99) < 33) {
			$password .= chr(ord('a') + mt_rand(0, 25));
			
		} else if(mt_rand(0, 99) < 66) {
			$password .= chr(ord('A') + mt_rand(0, 25));
			
		} else {
			$password .= chr(ord('0') + mt_rand(0, 9));
		}
	}
	
	$defaults = array(
			'url' => 'pp-version-'.mt_rand(1, 999),
			'password' => $password
	);

	if(get_option('prelude_version_plugin_options')) return;

	add_option('prelude_version_plugin_options', $defaults);
}

register_activation_hook(__FILE__, 'prelude_version_activate');




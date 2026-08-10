<?php
/**
 * Plugin Name: PS Hyphenate
 * Description: Improves wrapping of long compound words with native hyphenation and optional render-time soft hyphen exceptions.
 * Version: 1.0.5
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Author: Per Soderlind
 * Text Domain: ps-hyphenate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PS_HYPHENATE_VERSION', '1.0.5' );
define( 'PS_HYPHENATE_FILE', __FILE__ );
define( 'PS_HYPHENATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PS_HYPHENATE_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( PS_HYPHENATE_PATH . 'vendor/autoload.php' ) ) {
	require_once PS_HYPHENATE_PATH . 'vendor/autoload.php';
}

require_once PS_HYPHENATE_PATH . 'includes/class-settings.php';
require_once PS_HYPHENATE_PATH . 'includes/class-hyphenator.php';
require_once PS_HYPHENATE_PATH . 'includes/class-html-processor.php';
require_once PS_HYPHENATE_PATH . 'includes/class-render-filter.php';
require_once PS_HYPHENATE_PATH . 'includes/class-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		\PS_Hyphenate\Plugin::instance()->register();
	}
);

// GitHub Updater.
use Soderlind\WordPress\GitHubUpdater;

if ( class_exists( GitHubUpdater::class ) ) {
	GitHubUpdater::init(
		github_url:   'https://github.com/soderlind/ps-hyphenate',
		plugin_file:  __FILE__,
		plugin_slug:  'ps-hyphenate',
		name_regex:   '/ps-hyphenate\.zip/',
		branch:       'main',
		check_period: 6,
	);
}
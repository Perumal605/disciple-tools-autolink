<?php

namespace DT\Autolink;

use DT\Autolink\CodeZone\Router\Middleware\Stack;
use DT\Autolink\Illuminate\Container\Container;
use DT\Autolink\Providers\PluginServiceProvider;
use DT\Autolink\Services\Template;
/**
 * This is the entry-object for the plugin.
 * Handle any setup and bootstrapping here.
 */
class Plugin {
	/**
	 * The minimum required version of DT
	 * @var string
	 */
	const REQUIRED_DT_VERSION = '1.19';

	/**
	 * The route for the plugin's home page
	 * @var string
	 */
	const HOME_ROUTE = 'autolink';

	/**
	 * The instance of the plugin
	 * @var Plugin
	 */
	public static Plugin $instance;

	/**
	 * The container
	 * @see https://laravel.com/docs/10.x/container
	 * @var Container
	 */
	public Container $container;

	/**
	 * The service provider
	 * @see https://laravel.com/docs/10.x/providers
	 * @var PluginServiceProvider
	 */
	public PluginServiceProvider $provider;

	/**
	 * The template service
	 * @var Template
	 */
	public Template $template;

	/**
	 * Plugin constructor.
	 *
	 * @param Container $container
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
		$this->template  = $container->make( Template::class );
		$this->provider  = $container->make( PluginServiceProvider::class );
	}

	/**
	 * Get the instance of the plugin
	 * @return void
	 */
	public function init() {
		static::$instance = $this;
		$this->provider->register();
		add_action( 'wp_loaded', [ $this, 'wp_loaded' ], 20 );
		add_filter( 'dt_plugins', [ $this, 'dt_plugins' ] );
		add_action( 'init', [ $this, 'rewrite_rules' ] );
		add_action( 'query_vars', [ $this, 'query_vars' ] );
		add_action( 'template_redirect', [ $this, 'template_redirect' ], );
	}


	/**
	 * Add query vars
	 * @param array $vars
	 * @return array
	 */
	public function query_vars( array $vars ): array {
		$vars[] = 'disciple-tools-autolink';

		return $vars;
	}

	/**
	 * Perform template redirect based on query var 'dt_autolink'.
	 *
	 * @return void
	 */
	public function template_redirect(): void {
		if ( ! get_query_var( 'disciple-tools-autolink' ) ) {
			return;
		}

		$response = apply_filters( namespace_string( 'middleware' ), $this->container->make( Stack::class ) )
			->run();

		if ( ! $response ) {
			wp_die( esc_attr( __( "The page could not be found.", 'disciple-tools-autolink' ) ), 404 );
		}

		if ( ! $response->isSuccessful() ) {
			wp_die( esc_attr( $response->statusText() ), esc_attr( $response->getStatusCode() ) );
		}

		$path = get_theme_file_path( 'template-blank.php' );
		include $path;

		die();
	}

	/**
	 * Runs after_theme_setup
	 * @return void
	 */
	public function wp_loaded(): void {
		if ( ! $this->is_dt_version() ) {
			add_action( 'admin_notices', [ $this, 'admin_notices' ] );
			add_action( 'wp_ajax_dismissed_notice_handler', [ $this, 'ajax_notice_handler' ] );

			return;
		}

		if ( ! $this->is_dt_theme() ) {
			return;
		}

		if ( ! defined( 'DT_FUNCTIONS_READY' ) ) {
			require_once get_template_directory() . '/dt-core/global-functions.php';
		}



		$this->provider->boot();
	}

	/**
	 * Rewrite rules method.
	 *
	 * This method is responsible for adding any custom rewrite rules to the plugin.
	 * We'll use this method to add a custom rewrite rule for the all routes prefixed
	 * with the plugin's home route. Subsequent routes will be handled by the plugin's
	 * router.
	 *
	 * @return void
	 */
	public function rewrite_rules(): void {
		add_rewrite_rule( '^' . self::HOME_ROUTE . '/?', 'index.php?disciple-tools-autolink=true', 'top' );
	}

	/**
	 * is DT up-to-date?
	 * @return bool
	 */
	public function is_dt_version(): bool {
		if ( ! $this->is_dt_theme() ) {
			return false;
		}
		$wp_theme = wp_get_theme();

		return version_compare( $wp_theme->version, self::REQUIRED_DT_VERSION, '>=' );
	}

	/**
	 * Is the DT Theme installed?
	 * @return bool
	 */
	protected function is_dt_theme(): bool {
		return class_exists( 'Disciple_Tools' );
	}

	/**
	 * Register the plugin with disciple.tools
	 * @return array
	 */
	public function dt_plugins(): array {
		$plugin_data = get_file_data( __FILE__, [
			'Version'     => "1.1.0",
			'Plugin Name' => 'Disciple.Tools - Autolink',
		], false );

		$plugins['disciple-tools-autolink'] = [
			'plugin_url' => trailingslashit( plugin_dir_url( __FILE__ ) ),
			'version'    => $plugin_data['Version'] ?? null,
			'name'       => $plugin_data['Plugin Name'] ?? null,
		];

		return $plugins;
	}
}

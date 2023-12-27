<?php

namespace DT\Plugin\Providers;

use DT\Plugin\Services\ResponseRenderer;
use DT\Plugin\Services\Router;
use function DT\Plugin\routes_path;

class AdminServiceProvider extends ServiceProvider {
	/**
	 * Do any setup needed before the theme is ready.
	 * DT is not yet registered.
	 */
	public function register(): void {
		add_filter( 'dt/plugin/routes', [ $this, 'register_routes' ] );
		add_action( 'admin_menu', [ $this, 'register_menu' ], 99 );
	}

	/**
	 * Register the admin menu
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page( 'dt_extensions',
			__( 'DT Plugin', 'dt_plugin' ),
			__( 'DT Plugin', 'dt_plugin' ),
			'manage_dt',
			'dt_plugin',
			[ $this, 'register_router' ]
		);
	}

	/**
	 * Register the admin router
	 *
	 * @return void
	 */
	public function register_router(): void {
		$router   = $this->container->make( Router::class );
		$response = $router->run();
		$this->container->make( ResponseRenderer::class )->handle( $response );
	}

	/**
	 * Register the admin routes
	 *
	 * @return void
	 */
	public function register_routes( $r ): void {
		include routes_path( 'admin.php' );
	}

	/**
	 * Do any setup after services have been registered and the theme is ready
	 */
	public function boot(): void {
		if ( ! is_admin() ) {
			return;
		}

		/*
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		$plugins = [
			[
				'name'     => 'Disciple.Tools Dashboard',
				'slug'     => 'disciple-tools-dashboard',
				'source'   => 'https://github.com/DiscipleTools/disciple-tools-dashboard/releases/latest/download/disciple-tools-dashboard.zip',
				'required' => false,
			],
			[
				'name'     => 'Disciple.Tools Genmapper',
				'slug'     => 'disciple-tools-genmapper',
				'source'   => 'https://github.com/DiscipleTools/disciple-tools-genmapper/releases/latest/download/disciple-tools-genmapper.zip',
				'required' => true,
			],
			[
				'name'     => 'Disciple.Tools Autolink',
				'slug'     => 'disciple-tools-autolink',
				'source'   => 'https://github.com/DiscipleTools/disciple-tools-genmapper/releases/latest/download/disciple-tools-autolink.zip',
				'required' => true,
			],
		];

		/*
		 * Array of configuration settings. Amend each line as needed.
		 *
		 * Only uncomment the strings in the config array if you want to customize the strings.
		 */
		$config = [
			'id'           => 'disciple_tools',
			// Unique ID for hashing notices for multiple instances of TGMPA.
			'default_path' => '/partials/plugins/',
			// Default absolute path to bundled plugins.
			'menu'         => 'tgmpa-install-plugins',
			// Menu slug.
			'parent_slug'  => 'plugins.php',
			// Parent menu slug.
			'capability'   => 'manage_options',
			// Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true,
			// Show admin notices or not.
			'dismissable'  => true,
			// If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => 'These are recommended plugins to complement your Disciple.Tools system.',
			// If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => true,
			// Automatically activate plugins after installation or not.
			'message'      => '',
			// Message to output right before the plugins table.
		];

		tgmpa( $plugins, $config );
	}
}

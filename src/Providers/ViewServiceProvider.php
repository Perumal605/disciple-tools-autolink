<?php

namespace DT\Plugin\Providers;

use DT\Plugin\League\Plates\Engine;
use DT\Plugin\Services\Plates\Escape;
use function DT\Plugin\views_path;

/**
 * Register the plates view engine
 * @see https://platesphp.com/
 */
class ViewServiceProvider extends ServiceProvider {
	/**
	 * Register the view engine singleton and any extensions
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Engine::class, function ( $container ) {
			return new Engine( views_path() );
		} );
		$this->container->make( Engine::class )->loadExtension(
			$this->container->make( Escape::class )
		);
	}

	/**
	 * Do any setup needed before the theme is ready.
	 * DT is not yet registered.
	 */
	public function boot(): void {
	}
}

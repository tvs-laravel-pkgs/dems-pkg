<?php

namespace Uitoux\EYatra;

use Illuminate\Support\ServiceProvider;

class EYatraServiceProvider extends ServiceProvider {
	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot() {
		$this->loadRoutesFrom(__DIR__ . '/routes/web.php');
		$this->loadMigrationsFrom(__DIR__ . '/migrations');
		$this->loadViewsFrom(__DIR__ . '/views', 'eyatra');

		$this->publishes([
			__DIR__ . '/config/eyatra.php' => config_path('eyatra.php'),
		], 'config');

		$this->publishes([
			__DIR__ . '/public' => public_path(''),
		], 'public');
	}

	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->make('Uitoux\EYatra\EmployeeController');
	}
}

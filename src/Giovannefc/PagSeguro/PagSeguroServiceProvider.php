<?php namespace Giovannefc\PagSeguro;

use Illuminate\Support\ServiceProvider;

class PagSeguroServiceProvider extends ServiceProvider {

	public function register()
	{

		$this->app['pagseguro'] = $this->app->share(function($app)
		{
			$storage = $app['session'];
			$validator = $app['validator'];
			$config = $app['config'];

			return new \Giovannefc\PagSeguro\PagSeguro($storage, $validator, $config);

		});

	}

	public function boot()
	{

		if (! $this->app->routesAreCached())
		{
			require __DIR__.'/routes.php';
		}

		$this->publishes([
			__DIR__.'/config/pagseguro.php' => config_path('pagseguro.php'),
		]);

		$this->publishes([
			__DIR__.'/assets/images' => config_path('/public/vendor/pagseguro/images'),
		], 'public');

		$this->loadViewsFrom(__DIR__.'/views', 'pagseguro');

	}

}
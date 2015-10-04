<?php
namespace Giovannefc\PagSeguro;

use Illuminate\Support\ServiceProvider;

class PagSeguroServiceProvider extends ServiceProvider
{

	public function register()
	{

		$this->app->singleton('pagseguro', function($app)
		{
			$session = $app['session'];
			$validator = $app['validator'];
			$config = $app['config'];
			$log = $app['log'];

			return new \Giovannefc\PagSeguro\PagSeguro($session, $validator, $config, $log);
		});
	}

	public function boot()
	{
		$this->publishes([
			__DIR__.'/config/pagseguro.php' => config_path('pagseguro.php'),
		]);

		$this->publishes([
			__DIR__.'/assets/images' => public_path('assets/vendor/pagseguro/images'),
		], 'public');

		$this->loadViewsFrom(__DIR__.'/views', 'pagseguro');
	}
}
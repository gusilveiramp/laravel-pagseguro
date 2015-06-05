<?php namespace Giovannefc\PagSeguro;

use Illuminate\Support\ServiceProvider;
use App;

class PagSeguroServiceProvider extends ServiceProvider {

	public function register()
	{
		App::bind('pagseguro', function()
		{
			return new \Giovannefc\PagSeguro\PagSeguro;
		});
	}

}
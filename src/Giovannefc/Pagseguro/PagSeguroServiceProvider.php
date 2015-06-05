<?php namespace Giovannefc\Pagseguro;

use Illuminate\Support\ServiceProvider;
use App;

class PagSeguroServiceProvider extends ServiceProvider {

	public function register()
	{
		App::bind('pagseguro', function()
		{
			return new \Giovannefc\Pagseguro\PagSeguro;
		});
	}

}

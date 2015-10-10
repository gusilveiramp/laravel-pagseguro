<?php

if (! function_exists('pagseguro_status'))
{
	function pagseguro_status()
	{
		return app('pagseguro')->listStatus();
	}
}

if (!function_exists('price_br')) {
	function price_br($price)
	{
		return number_format($price, '2', ',', '.');
	}
}

if (!function_exists('pagseguro_js')) {
	function pagseguro_js()
	{
		return app('pagseguro')->getUrlApiJava();
	}
}
<?php

if (! function_exists('pagseguro_status'))
{
	function pagseguro_status()
	{
		return app('pagseguro')->listStatus();
	}
}
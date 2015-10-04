<?php

return [

	/*
	/ Define as configurações da conta do PagSeguro
	*/

	'email' => env('PAGSEGURO_EMAIL', ''),
	'token_sandbox' => env('PAGSEGURO_TOKEN_SANDBOX', ''),
	'token_production' => env('PAGSEGURO_TOKEN_PRODUCTION', ''),

];
<?php

return [

	/*
	/ Define as configurações da conta do PagSeguro
	*/

	'env'	=> env('PAGSEGURO_ENV', 'sandbox'),
	'email' => env('PAGSEGURO_EMAIL', ''),
	'token' => env('PAGSEGURO_TOKEN', ''),

	/*
	* Define a route que vai chamar a função que envia o pagamento (send)
	*/

	'send_route' => env('PAGSEGURO_SENDROUTE', 'enviaPagamento')

];
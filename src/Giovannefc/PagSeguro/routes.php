<?php

/*
|--------------------------------------------------------------------------
| PagSeguro Laravel 5 Routes
|--------------------------------------------------------------------------
|
*/

Route::get('/services/pagseguro/get/payment-methods', ['as' => 'getPaymentMethods', function(){

	return view('pagseguro.getPaymentMethods');

}]);

Route::post('/services/pagseguro/ajax/payment-methods', ['as' => 'PagSeguroAjaxPaymentMethods', function(){

	$data = Request::input('data');

	Session::put('pagseguro.paymentMethods', $data);

}]);


Route::post('/services/pagseguro/ajax/sender-hash', ['as' => 'PagSeguroAjaxSenderHash', function(){

	$data = Request::input('data');
        
	Session::put('pagseguro.senderHash', $data);

}]);

Route::post('/services/pagseguro/ajax/credit-card/token', ['as' => 'PagSeguroAjaxCreditCardToken', function(){

	$data = Request::input('data');
        
    Session::put('pagseguro.creditCardToken', $data);

}]);

Route::post('/services/pagseguro/ajax/credit-card/info-holder', ['as' => 'PagSeguroAjaxInfoHolder', function(){

	$name = Request::input('holderName');
	$cpf = Request::input('holderCpf');
	$birthDate = Request::input('holderBirthDate');

	Session::put('pagseguro.holderName', $name);
	Session::put('pagseguro.holderCpf', $cpf);
	Session::put('pagseguro.holderBirthDate', $birthDate);

}]);
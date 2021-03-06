# laravel-pagseguro (Laravel 5.1)

Pacote de integração do sistema transparente de pagamento do PagSeguro

## Instalação

Adicione no require do arquivo composer.json de seu projeto:

```php
"giovannefc/laravel-pagseguro": "dev-master"
```

E rode um:

```
$ composer update giovannefc/laravel-pagseguro
```

Atualize o arquivo config/app.php de seu projeto, adicionando o ServiceProvider:
```php
Giovannefc\PagSeguro\PagSeguroServiceProvider::class,
```

E o Facade:
```php
'PagSeguro' => Giovannefc\PagSeguro\PagSeguroFacade::class,
```

## Configuração

Para publicar o arquivo de configuração, execute:

```
$ php artisan vendor:publish
```

Isso também fará a publicação de imagens da bandeira do PagSeguro usadas na view. 

No arquivo de configuração .env de sua aplicação, coloque as linhas:

```php
PAGSEGURO_EMAIL=seu@email.com
PAGSEGURO_TOKEN_SANDBOX=seu_token_de_testes
PAGSEGURO_TOKEN_PRODUCTION=seu_token_de_produção
```

Só será assumido o token de produção (production) quando sua aplicação estiver setada/configurada em production no arquivo
de configuração de ambiente .env, por exemplo:
```php
APP_ENV=production
```
Qualquer outra configuração, como APP_ENV=local por exemplo, será assumido o token de testes (sandbox).

## Enviando uma transação:

```php
$senderInfo = array(
	'nome' 		=> 'Nome e Sobrenome',
	'email'		=> 'email@provedor.com'
	'cpf' 		=> '22233344455',
	'telefone' 	=> '11 33884466'
);
$senderAddress = array(
	'rua' 			=> 'Rua Fulano de Tal',
	'numero' 		=> '555',
	'complemento' 	=> 'Opcional',
	'bairro' 		=> 'Bairro',
	'cep' 			=> '14222060',
	'cidade' 		=> 'Sao Paulo',
	'uf' 			=> 'SP'
);

$items = array(
	'item1' => [
		'id' 		=> '1',
		'name' 		=> 'Nome do Produto ou Serviço',
		'price' 	=> '120.50',
		'quantity' 	=> 1
	]
);

PagSeguro::setSenderInfo($senderInfo)
->setSenderAddress($senderAddress)
->setItems($items)
->setTotalAmount('120.50')
->sendCreditCard();
```

## View

A view contém um formulário para pagamento com cartão de crédito e um botão para pagamento com boleto.
O código HTML utiliza os padrões CSS do bootstrap. Então para visualizar corretamente é necessário carregar o css/js do mesmo em seu template:
```php
https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css
https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js
```
E claro, não esquecer de carregar o SDK javascript do PagSeguro:

```php
<script src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>
```

Na sua view (blade), para incluir o formulário, use:
```php
@include('pagseguro::form')
```
E para o javascript:
```php
@include('pagseguro::js')
```

*Em desenvolvimento. Estou criando a documentação aos poucos enquanto vou testando o código.
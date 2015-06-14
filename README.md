# laravel-pagseguro (Laravel 5)

Pacote de integração do sistema transparente de pagamento do PagSeguro

## Instalação

Adicione no require do arquivo composer.json de seu projeto:

```"giovannefc/laravel-pagseguro": "dev-master"```

E rode um:

```composer update```

Atualize o arquivo config/app.php de seu projeto, adicionando o ServiceProvider:
```
...
'Giovannefc\PagSeguro\PagSeguroServiceProvider',
```

E o Facade:
```
....
'PagSeguro'	=> 'Giovannefc\PagSeguro\PagSeguroFacade'
...
```

Em desenvolvimento. Logo começarei a disponibilizar a documentação completa aqui.
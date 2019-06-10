# TCC Backend

## Dependências
 - PHP 7+
    * php-cli
    * php-mbstring
    * php-xml
    * php-curl
    * php-zip
 - composer

## Deploy
```bash
$ composer install
$ git submodule init
$ ./bin/build-frontend [prod]
$ ./bin/build-jsmpeg [prod]
```

## Configurar sudoers
Para que o backend consiga controlar os serviços linux é necessários adicionar a permissão `NOPASSWD` ao usuário do servidor HTTP. Para isso, execute os comando:

```
$ sudo visudo
```

Então adicione a linha:
```
www-data  ALL=(ALL) NOPASSWD:ALL
```

# LaravelVueCrud
Repositório que através do php artisan cria um comando automatiza a criação de Cruds 

1 - Faça o download da biblioteca: composer require dianodev/laravel-vue-crud:dev-main

2 - Importe os arquivos: php artisan vendor:publish --tag=laravel-vue-crud
  - Caso deseje importar apenas 1 modulo:
    - Blade: php artisan vendor:publish --tag=laravel-blade
    - View Component: php artisan vendor:publish --tag=laravel-view
    - Sass: php artisan vendor:publish --tag=laravel-sass

3 - Copie as configurações do vendor/dianodev/vite.config.js

4 - Copie as configurações do vendor/dianodev/src/resources/js/app.js

5 - Copie as dependencias dev do vendor/dianodev/package.json

6 - Instale as dependências: npm install

7 - Gere um módulo:
    - php artisan make:module Pessoa (O nome do módulo deve começar em maiusculo e se for mais de uma palavra separar com maiusculo, Ex: PessoaCasaImovel)
    - digite o nome da tabela no banco de dados
    - escolha quais campos vao para o formúlario separando por virgula (ex: 1,2,5,7)
    - escolha quais campos vao para a grid separando por virgula (ex: 4,5,8)

8 - Seu modulo esta gerado, acesse o url /{modulo} para ter acesso aos recursos de CRUD.

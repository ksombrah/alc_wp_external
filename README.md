# alc_wp_external
Autenticação Externa - Swagger UI

Plugin para Autenticação Externa - Swagger UI

Forma de utilização:
- alterar $alc_wp_api_base_url para o caminho de login da api
- instalar o plugin usando o código ou pelo arquivo zip do respositório

Como funciona:
- Inicialmente faz a autenticação pelo WP normalmente, se retornar usuário cadastrado logo normal
- Caso não esteja cadastrado no sistema WP irá fazer a requisição na API externa cadastrada
- Inclui-se na base do WP os dados do usuário, desde que devidamente autenticado pela API extrena.
- Loga-se normalmente
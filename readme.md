Este projeto foi elaborado para testar docker + php + mysql + frontend (CSS,HTML5,AngularJS e BootStrap)

baixe tudo
instale o docker e o docker-compose


container mysql com o banco de dados de acordo com o script /mysql_ini/criar_banco.sql
container servidor web (nginx) e container php agora com o slim instalado para poder fazer a apiphp restfull
para instalar o slim framework no php isso esta no /php/dockerfile conforme o tutorial em <https://www.slimframework.com/>

e para coloca-lo no meu projeto foi feito o seguinte:
  ```cd /code
  mkdir slim
  cd slim
  composer require slim/slim "^3.0"``` 

para criar o ambinete do docker, va para o terminal e no diretorio raiz faça:

   ```docker-compose build```
 
se deu tudo certo o docker baixa as imagens e monta os containers
precisamos entao levantar o ambiente criado fazendo
  
   ```docker-compose up -d```

up -> subir o ambiente (se quiser para o ambiente faça: docker-compose down)
-d -> deamon (rodar em segundo plano, isso libera o terminal)

espere um pouco pois ele pode demorar a subir todos os serviços
no seu navegador acesse: <http://localhost:8080>
uma tela bem tosca, deve aparecer com 2 usuarios listados o admin e o mozar.

este atualizacao contem a apiphp de com o CRUD do usuario

para testar faça:

listar os usuarios cadastrados
	metodo get - localhost:8080/apiphp/usuarios
	```ex: curl localhost:8080/apiphp/usuarios```

obter um usuario especifico
	metodo get - localhost:8080/apiphp/usuarios/{id}
	```ex: curl localhost:8080/apiphp/usuarios/1```

inserir um novo usuario
	metodo post - localhost:8080/apiphp/usuarios/inserir
        corpo do post- {"apelido":"novo","nome":"novo","email":"novo.silva@gmail.com","senha":"novo"}
	```ex: curl -H "Content-Type: application/json" -X POST -d '{"apelido":"novo","nome":"novo","email":"novo.silva@gmail.com","senha":"novo"}' localhost:8080/apiphp/usuarios/inserir```

alterar um novo usuario
	metodo post ou put - localhost:8080/apiphp/usuarios/{id}
        corpo do post/put- {"apelido":"novo","nome":"novo","email":"novo.silva@gmail.com","senha":"novo"}
	```ex: curl -H "Content-Type: application/json" -X POST -d {"apelido":"joca","nome":"Jocirlei","email":"jocirlei.silva@gmail.com","senha":"123"}' localhost:8080/apiphp/usuarios/2

	    ou

	    curl -H "Content-Type: application/json" -X PUT -d '{"apelido":"joca","nome":"Jocirlei","email":"jocirlei.silva@gmail.com","senha":"123"}' localhost:8080/apiphp/usuarios/2```

remover um usuario
	metodo delete - localhost:8080/apiphp/usuarios/{id}
        ```ex: curl -X DELETE localhost:8080/apiphp/usuarios/2```

eu utilizei o postman para realizar os testes e afim de facilitar coloquei o arquivo do postman na raiz para que vc possa importa-lo 
```Viagem.postman_collection.json```


foi adiconada a interface web com angular e bootstrap. Alem disso temos mais 3 novos serviços na apiphp, ativar, desativar e resetar senha do usuario. 
no banco de dados, foi inserido o campo ativo na tabela de usuario.







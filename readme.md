Este projeto foi elaborado para testar docker + php + mysql + frontend (CSS,HTML5,AngularJS e BootStrap)

baixe tudo
instale do docker e o docker-compose


para o docker baixar e montar o ambiente (container mysql, container servidor web (nginx) e container php)
este ja cria o banco de dados de acordo com o script /mysql_ini/criar_banco.sql
va para o terminal e no diretorio raiz faca:

   docker-compose build
 
agora se deu tudo certo o docker baixa as imagens e monta os containers
precisamos entao levantar o ambiente criado fazendo
  
   docker-compose up -d

up -> subir o ambiente (se quiser para o ambiente faça: docker-compose down)
-d -> deamon (rodar em segundo plano, isso libera o terminal)

espere um pouco pois ele pode demorar a subir todos os serviços
no seu navegador acesse: http://localhost:8080
uma tela bem tosca, deve aparecer com 2 usuarios listados o admin e o mozar.



   

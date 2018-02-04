// Register `phoneList` component, along with its associated controller and template

angular.
  module('viagem').
  component('compUsuarios', {
    templateUrl: 'components/usuarios.template.html',
    controller: function ($scope, $rootScope, $http) {
      $scope.orderProp = 'nome';
      $scope.ativos = true;
      $scope.inativos = false;
      $scope.filtro = "";
      $scope.erro="";
      $scope.info="";
      $scope.atualizar = function(){
          $http.get($rootScope.baseapi + '/usuarios').then(function(response) {
          $scope.usuarios = response.data; });
      }
      $rootScope.update = $scope.atualizar;


      $scope.atualizar();

      $scope.filtrar = function (usuario) {
        var resp = (usuario.ativo==1 && $scope.ativos) || (usuario.ativo==0 && $scope.inativos);
        if (resp && $scope.filtro!="")
        {
          var reg = new RegExp($scope.filtro, "i");
          resp = reg.test(usuario.apelido) || reg.test(usuario.nome) || reg.test(usuario.email) || reg.test(usuario.data);
        }
        return resp;
      };      

      $scope.alterarfiltro = function (numero) {
        if (numero == 1) this.ativos = !this.ativos;
        if (numero == 2) this.inativos = !this.inativos;  
      }
              
      $scope.novo = function () {
        $scope.textoacao = "Novo";
        $scope.btnconfirmar = "Inserir";
        $scope.usuario = { id: 0, apelido: "", nome: "", email: "", senha: "", data: "" };
        $scope.usuarioIdSelecionado = $scope.usuario.Id;
        $scope.info = "";
        $scope.status =  0;
      }
  
      $scope.editar = function (usuario) {
        $scope.textoacao = "Editar";
        $scope.btnconfirmar = "Alterar";
        $scope.usuario = usuario;
        $scope.usuarioIdSelecionado = usuario.id;
        $scope.info = "";
        $scope.status =  0;
      }
  
      $scope.remover = function (usuario) {

        $scope.textoacao = "Remover";
        $scope.btnconfirmar = "Remover";
        $scope.usuario = usuario;
        $scope.usuarioIdSelecionado = usuario.id;
        $scope.info = "";
        $scope.status =  0;
        /*
        var confirm = window.confirm("Confirma remover?");
        if(confirm){
        $http({ method: 'delete', url: $rootScope.baseapi + '/usuarios/' + $usuario.id })
            .then(function (response, status, headers) {
                $scope.usuario = response;
                $location.path('/frontend/index.html');
            });
          }*/
      }

      $scope.ativar = function (usuario) {
          var url = usuario.ativo==1?$rootScope.baseapi + '/usuarios/desativar/':$rootScope.baseapi + '/usuarios/ativar/';
          $http({ method: 'post', url: url + usuario.id })
          .then(function (response, status, headers) {
            $scope.info = response.data.message;
            $scope.status =  response.data.status;
            $http.get($rootScope.baseapi + '/usuarios').then(function(response) {
              $scope.usuarios = response.data; });
          })          
          .catch( function (response, status, headers) {
            $scope.erro = response.data.message;
            $scope.status =  response.data.status;
          });
      }

      $scope.resetar = function (usuario) {
          $http({ method: 'post', url: $rootScope.baseapi + '/usuarios/resetar/' + usuario.id})
          .then(function (response, status, headers) {
            $scope.info = response.data.message;
            $scope.status =  response.data.status;
            $http.get($rootScope.baseapi + '/usuarios').then(function(response) {
              $scope.usuarios = response.data; });
          })          
          .catch( function (response, status, headers) {
            $scope.erro = response.data.message;
            $scope.status =  response.data.status;
          });
      }
          
      $scope.confirmar = function () {
        if ( $scope.btnconfirmar == "Inserir")
        {
          $http({ method: 'post', url: $rootScope.baseapi + '/usuarios/inserir', data: $scope.usuario })
          .then(function (response, status, headers) {
            $scope.info = response.data.message;
            $scope.status =  response.data.status;
            $scope.atualizar();
          })          
          .catch( function (response, status, headers) {
            $scope.erro = response.data.message;
            $scope.status =  response.data.status;
          });
        }
        else if ( $scope.btnconfirmar == "Alterar")
        {
          $http({ method: 'post', url: $rootScope.baseapi + '/usuarios/' + $scope.usuarioIdSelecionado, data: $scope.usuario })
          .then(function (response, status, headers) {
            $scope.info = response.data.message;
            $scope.status =  response.data.status;
            $scope.atualizar();
          })          
          .catch( function (response, status, headers) {
            $scope.erro = response.data.message;
            $scope.status =  response.data.status;
          });
        }
        else if ( $scope.btnconfirmar == "Remover")
        {
          $http({ method: 'delete', url: $rootScope.baseapi + '/usuarios/' + $scope.usuarioIdSelecionado })
          .then(function (response, status, headers) {
            $scope.info = response.data.message;
            $scope.status =  response.data.status;
            $scope.atualizar();
          })          
          .catch( function (response, status, headers) {
            $scope.erro = response.data.message;
            $scope.status =  response.data.status;
          });
        }
      }
    }  
  });




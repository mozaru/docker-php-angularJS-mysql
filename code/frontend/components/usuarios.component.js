// Register `phoneList` component, along with its associated controller and template

angular.
  module('viagem').
  component('compUsuarios', {
    templateUrl: 'components/usuarios.template.html',
    controller: function ($scope, $http) {
      $scope.orderProp = 'nome';
      $scope.ativos = true;
      $scope.inativos = false;
      $scope.filtro = "";

      $http.get('/apiphp/usuarios').then(function(response) {
        $scope.usuarios = response.data; 
      });

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
      }
  
      $scope.editar = function (usuario) {
        $scope.textoacao = "Editar";
        $scope.btnconfirmar = "Alterar";
        $scope.usuario = usuario;
        $scope.usuarioIdSelecionado = usuario.id;
      }
  
      $scope.remover = function (usuario) {

        $scope.textoacao = "Remover";
        $scope.btnconfirmar = "Remover";
        $scope.usuario = usuario;
        $scope.usuarioIdSelecionado = usuario.id;
        /*
        var confirm = window.confirm("Confirma remover?");
        if(confirm){
        $http({ method: 'delete', url: '/apiphp/usuarios/' + $usuario.id })
            .then(function (response, status, headers) {
                $scope.usuario = response;
                $location.path('/frontend/index.html');
            });
          }*/
      }

      $scope.ativar = function (usuario) {
          var url = usuario.ativo==1?'/apiphp/usuarios/desativar/':'/apiphp/usuarios/ativar/';
          $http({ method: 'post', url: url + usuario.id })
          .then(function (response, status, headers) {
            $http.get('/apiphp/usuarios').then(function(response) {
              $scope.usuarios = response.data; });
          });
      }

      $scope.resetar = function (usuario) {
          $http({ method: 'post', url: '/apiphp/usuarios/resetar/' + usuario.id})
          .then(function (response, status, headers) {
            $http.get('/apiphp/usuarios').then(function(response) {
              $scope.usuarios = response.data; });
          });
      }
          
      $scope.confirmar = function () {
        if ( $scope.btnconfirmar == "Inserir")
        {
          $http({ method: 'post', url: '/apiphp/usuarios/inserir', data: $scope.usuario })
          .then(function (response, status, headers) {
            $http.get('/apiphp/usuarios').then(function(response) {
              $scope.usuarios = response.data; });
          });
        }
        else if ( $scope.btnconfirmar == "Alterar")
        {
          $http({ method: 'post', url: '/apiphp/usuarios/' + $scope.usuarioIdSelecionado, data: $scope.usuario })
          .then(function (response, status, headers) {
            $http.get('/apiphp/usuarios').then(function(response) {
              $scope.usuarios = response.data; });
          });
        }
        else if ( $scope.btnconfirmar == "Remover")
        {
          $http({ method: 'delete', url: '/apiphp/usuarios/' + $scope.usuarioIdSelecionado })
          .then(function (response, status, headers) {
            $http.get('/apiphp/usuarios').then(function(response) {
              $scope.usuarios = response.data; });
          });
        }
      }
    }  
  });




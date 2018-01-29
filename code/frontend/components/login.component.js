// Register `phoneList` component, along with its associated controller and template
function obtervalor($cookies, chave, valorpadrao){
    var valor = $cookies.get(chave);
    return (valor != null && valor !== undefined)?valor:valorpadrao;
};

var getUrlParameter = function getUrlParameter(sParam) {
  var sPageURL = decodeURIComponent(window.location.search.substring(1)),
      sURLVariables = sPageURL.split('&'),
      sParameterName,
      i;

  for (i = 0; i < sURLVariables.length; i++) {
      sParameterName = sURLVariables[i].split('=');

      if (sParameterName[0] === sParam) {
          return sParameterName[1] === undefined ? true : sParameterName[1];
      }
  }
  return "";
};

angular.
  module('viagem').
  component('compLogin', {
    templateUrl: 'components/login.template.html',
    controller: function ($scope, $http, $cookies, $location) {
      $scope.login = { email: "", senha: "", lembrarsenha: false };
      $scope.usuario = { apelido:"", nome:"", email: "", senha: "", confirmasenha: "", codigo: getUrlParameter('codigo') };
      $scope.login.email = obtervalor($cookies, 'email' , '');
      $scope.login.senha = obtervalor($cookies,'senha', '');
      $scope.login.lembrarsenha = obtervalor($cookies, 'lembrarsenha', false) == 'true';
      $scope.redirect = getUrlParameter('url');
      if ($scope.redirect == "" )
        $scope.redirect = "http://localhost:8080/frontend/usuarios.php";
      $scope.erro="";
      $scope.info="";
      $scope.urlbase = "http://localhost:8080/frontend/index.php";

      $op = getUrlParameter('op');
      if ($op == "registrar")
        $('#modalRegistrar').modal('show');
      else if ($op == "lembrarsenha")
        $('#modalLembrarSenha').modal('show');
      else if ($op == "reativar")
        $('#modalReativar').modal('show');
      else 
        $('#modalLogin').modal('show');
      
      $scope.logar = function (login) {
        var url = '/apiphp/login/logar';
          $http({ method: 'post', url: url, data: login})
          .then(function (response, status, headers) {    
            if (login.lembrarsenha)
            {
              var expireDate = new Date();
              expireDate.setTime(2144232732000);
              $cookies.put('email', login.email,{'expires': expireDate} );
              $cookies.put('senha', login.senha,{'expires': expireDate} );
              $cookies.put('lembrarsenha',login.lembrarsenha, {'expires': expireDate} );
            } 
            else
            {
              $cookies.remove('email');
              $cookies.remove('senha');
              $cookies.remove('lembrarsenha');              
            }      
            $scope.info = response.data.message;
            $scope.status =  response.data.status;
            window.location = $scope.redirect;
          })
          .catch( function (response, status, headers) {
            $scope.erro = response.data.message;
            $scope.status =  response.data.status;
          });
      }
  
      $scope.esquecisenha = function (login) {
        var url = '/apiphp/login/lembrarsenha?email='+login.email;
          $http({ method: 'get', url: url})
          .then(function (response, status, headers) {
            $scope.info = response.data.message;
            $scope.status =  response.data.status;
          })
          .catch( function (response, status, headers) {
            $scope.erro = response.data.message;
            $scope.status =  response.data.status;     
          });
      }

      $scope.cadastrar = function(login) {
        var url = '/apiphp/login/registrar?email='+encodeURI(login.email);
        $http({ method: 'get', url: url})
        .then(function (response, status, headers) {
          $scope.info = response.data.message;
          $scope.status =  response.data.status;         
        })
        .catch( function (response, status, headers) {
          $scope.erro = response.data.message;
          $scope.status =  response.data.status;    
        });
      }

      $scope.registrar = function() {
        $http({ method: 'post', url: '/apiphp/login/registrar', data: $scope.usuario })
        .then(function (response, status, headers) {
          $scope.info = response.data.message;
          $scope.status =  response.data.status;
          window.location = $scope.urlbase;
        })
        .catch( function (response, status, headers) {
          $scope.erro = response.data.message;
          $scope.status =  response.data.status;      
        });
      }

      $scope.alterarsenha = function() {
        $http({ method: 'post', url: '/apiphp/login/lembrarsenha', data: $scope.usuario })
        .then(function (response, status, headers) {
          $scope.info = response.data.message;
          $scope.status =  response.data.status;
          window.location = $scope.urlbase;
        })
        .catch( function (response, status, headers) {
          $scope.erro = response.data.message;
          $scope.status =  response.data.status;    
        });
      }

      $scope.reativar = function() {
        $http({ method: 'post', url: '/apiphp/login/reativar', data: $scope.usuario })
        .then(function (response, status, headers) {
          $scope.info = response.data.message;
          $scope.status =  response.data.status;
          window.location = $scope.urlbase;
        })
        .catch( function (response, status, headers) {
          $scope.erro = response.data.message;
          $scope.status =  response.data.status;      
        });
      }

      $scope.revelarsenha = function() {
            if($('#ctr_senha input').attr("type") == "text"){
                $('#ctr_senha input').attr('type', 'password');
                $('#ctr_senha i').removeClass( "fa-eye-slash" );
                $('#ctr_senha i').addClass( "fa-eye" );
            }else if($('#ctr_senha input').attr("type") == "password"){
                $('#ctr_senha input').attr('type', 'text');
                $('#ctr_senha i').addClass( "fa-eye-slash" );
                $('#ctr_senha i').removeClass( "fa-eye" );
            }
        }
    }  
  });



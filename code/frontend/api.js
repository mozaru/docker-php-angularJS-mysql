angular.module('demo', [])
.controller('ListarUsuarios', function($scope, $http) {
    $http.get('/apiphp/usuarios').
        then(function(response) {
            $scope.usuarios = response.data;
        });

    $scope.remove = function () {

        var confirm = window.confirm("Confirma remover?");
        if(confirm){
        $http({ method: 'delete', url: '/usuarios/' + $scope.pessoa._id })
            .success(function (response, status, headers) {
                $scope.pessoa = response;
                $location.path('/usuarios');
            });
        }
    }
})
.controller('Usuario', function($scope, $http, $routeParams, $location) {
    var _id = $routeParams.id;
    if (_id){
    $http.get('/apiphp/usuario/'+_id).
        then(function(response) {
            $scope.usuario = response.data;
        });
        $scope.btnLabel = "Alterar";
    }
    else
       $scope.btnLabel = "Inserir";

    $scope.save = function () {
        var json = angular.toJson($scope.usuario);
        if($scope.usuario._id){
            $http({ method: 'PUT', url: '/apiphp/usuarios/'+ $scope.usuario.id, data: json })
                .success(function (response, status, headers) {
                    $scope.usuario = response;
                    $location.path('/usuarios/');
                });
        }else{
            $http({ method: 'POST', url: '/apiphp/usuarios', data: json })
                .success(function (response, status, headers) {
                    $scope.pessoa = response;
                    $location.path('/usuarios/' + $scope.usuario.id);
                });
        }
    }

    $scope.remove = function () {

        var confirm = window.confirm("Confirma remover?");
        if(confirm){
        $http({ method: 'delete', url: '/usuarios/' + $scope.pessoa._id })
            .success(function (response, status, headers) {
                $scope.pessoa = response;
                $location.path('/usuarios');
            });
        }
    }
});
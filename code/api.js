angular.module('demo', [])
.controller('ListarUsuarios', function($scope, $http) {
    $http.get('usuarios.php').
        then(function(response) {
            $scope.usuarios = response.data;
        });
});


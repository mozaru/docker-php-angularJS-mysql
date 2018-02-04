var app = angular.module('viagem', ['ngCookies'])
    .run(function ($rootScope) {
        $rootScope.baseapi = '/apiphp'; 
    });

$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
});
function obterNomeApi(api)
{
    if (api=='apiphp')      return 'PHP';
    if (api=='apinode')     return 'Node';
    if (api=='apipython')   return 'Python';
}
app.controller('apicontroler', function($scope, $rootScope, $cookies) {
    $scope.api = $cookies.get('api');
    if ($scope.api == null || $scope.api == undefined)
      $scope.api = 'apiphp';
    $scope.nomeapi = obterNomeApi($scope.api);
    $rootScope.baseapi = '/' + $scope.api; 

    $scope.RadioChange = function (valor) {
        if ($scope.api!=valor)
        {
            $scope.api = valor;
            $scope.nomeapi = obterNomeApi($scope.api);
            $rootScope.baseapi = '/' + $scope.api; 
            var expireDate = new Date();
            expireDate.setTime(2144232732000);
            $cookies.put('api', $scope.api,{'expires': expireDate} );
            if ($rootScope.update!=null && $rootScope.update!=undefined)
                $rootScope.update();
        }
    };
});
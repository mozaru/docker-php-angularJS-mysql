var app = angular.module('viagem', ['ngCookies'])
    .factory('myHttpInterceptor', function($q, $injector, $rootScope, $cookies) 
    { 
        return { 
            'request': function(config) {
                var exclude = $rootScope.baseapi+'/login/';
                var include = $rootScope.baseapi+'/';
                if(config.url.startsWith(include) && !config.url.startsWith(exclude)) {
                    config.headers['Authorization'] = 'Bearer '+$rootScope.accesstoken;
                    config.headers['Accept'] = 'application/json;odata=verbose';
                }
                return config || $q.when(config);
            }, 
            'requestError': function(rejection) { 
                // do something on error 
                
                if (canRecover(rejection)) { 
                    return responseOrNewPromise; 
                } 
                return $q.reject(rejection); 
            }, 
            'response': function(response) { 
                if(response.config.url.endsWith("logar") || response.config.url.endsWith("refreshtoken")) {
                    $rootScope.accesstoken = response.data['access_token'];
                    $rootScope.refreshtoken = response.data['refresh_token'];
                    $cookies.put('accesstoken', $rootScope.accesstoken );
                    $cookies.put('refreshtoken', $rootScope.refreshtoken );
               }
                return response || $q.when(response); 
            }, 
            'responseError': function(rejection) { 
                //$scope.erro = rejection.data.message;
                //$scope.status =  rejection.data.status;
                /*if (canRecover(rejection)) { 
                    return responseOrNewPromise; 
                }*/
                if (rejection.data.message=="Token Expirado")
                {
                    if(rejection.config.url.endsWith("refreshtoken"))
                        window.location="/login.html?url=/usuarios.html"; 
                    else
                        return refresh($rootScope, $injector.get("$http"), rejection.config);
                }
                return $q.reject(rejection); 
            } 
        }; 
    });
    
refresh = function ($rootScope, $http, config) {
        var url = $rootScope.baseapi + '/login/refreshtoken';
        var corpo= { "grant_type": "refresh_token",
                     "client_id":"viagem",
                     "client_secret":"123",
                     "scope":"admin",
                     "refresh_token":$rootScope.refreshtoken};
          $http({ method: 'post', url: url, data: corpo})
          .then(function (response, status, headers) {
            return $http(config); 
          })
          .catch( function (response, status, headers) {
          });
};

app.config(function ($httpProvider) {
    $httpProvider.interceptors.push('myHttpInterceptor');
    });

function obtervalor($cookies, chave, valorpadrao){
    var valor = $cookies.get(chave);
    return (valor != null && valor !== undefined)?valor:valorpadrao;
};
    
app.run(function ($rootScope, $cookies) {
        $rootScope.baseapi = '/apiphp'; 
        $rootScope.accesstoken = obtervalor($cookies, 'accesstoken' , '');
        $rootScope.refreshtoken = obtervalor($cookies, 'refreshtoken' , '');
        //$httpProvider.interceptors.push('myHttpInterceptor');
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
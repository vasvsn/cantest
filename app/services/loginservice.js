var app = angular.module('p2p');

app.factory('loginService', function($http, $location, $rootScope, sessionService){
    return {
        login: function(data, scope) {
            var $promise = $http.post('app/api/users.php?function=GetUser&param=', data);
            $promise.then(function(msg){
                var uid = msg.data;
                if(uid.studentID) {
                    sessionService.set('uid', uid.studentID);
                    document.body.style.background = "#f2f2f2";
                    $location.path('home');
                } else {
                    $rootScope.error = ("Incorrect Username or Paassword !");
                }
            });
        },
        logout: function(){
            sessionService.destroy('uid');
            $location.path('login');
        },
        isLoggedIn: function(){

           if(sessionService.get('uid')){
                return true;
           } else {
               return false;
           }
        }
    }
});

'use strict';

var app = angular.module("p2p", ["ngRoute", "ngTagsInput"]);

app.config(['$routeProvider','$locationProvider', function($routeProvider, $locationProvider, loginService){
    $routeProvider
    .when("/login", {
        resolve: {
            "check": function($location, loginService){
                if(!loginService.isLoggedIn){
                    $location.path('/login');
                }
            }
        },
        templateUrl: "app/views/loginview.html",
        controller: "logincontroller"
    })
    .when("/home", {
        resolve: {
            "check": function($location, loginService){
                if(!loginService.isLoggedIn()){
                    $location.path('/login');
                }
            }
        },
        templateUrl: "app/views/homeview.html",
        controller: "homecontroller"
    })
    .when("/member/:id/:name", {
        resolve: {
            "check": function($location, loginService){
                if(!loginService.isLoggedIn()){
                    $location.path('/login');
                }
            }
        },
        templateUrl: "app/views/memberview.html",
        controller: "membercontroller"
    })
    .when("/profile", {
        resolve: {
            "check": function($location, loginService){
                if(!loginService.isLoggedIn()){
                    $location.path('/login');
                }
            }
        },
        templateUrl: "app/views/profileview.html",
        controller: "profilecontroller"
    })
     .when("/grades", {
         resolve: {
            "check": function($location, loginService){
                if(!loginService.isLoggedIn()){
                    $location.path('/login');
                }
            }
        },
        templateUrl: "app/views/gradesview.html",
        controller: "gradecontroller"
    })
    .otherwise({
        redirectTo: "/login"
    });
}]);

app.run(function($rootScope, $location, loginService){
    // var routespermissions = ['/home'];
    // $rootScope.$on('$routeChangeStart', function(){
    //     if(routespermissions.indexOf($location.path()) != -1){
    //         loginService.isLogged() == false
    //         $location.path('/login');
    //     }
    // })
})

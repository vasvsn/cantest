var app = angular.module("p2p");

app.controller("logincontroller", function($scope, $location, $rootScope, loginService) {
    $scope.login = function(){
        loginService.login($scope.user, $scope);
        if($rootScope.error != null){$('.alert').show(); $rootScope.error = null;};
    }

    $('body').css('background-image', 'url(assets/img/background.jpeg)');
    $('body').css('background-size', 'cover');
    $('body').css('background-repeat', 'no-repeat');
});

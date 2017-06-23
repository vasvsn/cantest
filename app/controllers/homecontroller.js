var app = angular.module("p2p");

app.controller("homecontroller", function($scope, $location, $http, loginService, sessionService){
    var userid = sessionService.get("uid");
    $scope.students = [];
    $scope.notifications = [];

    $scope.showGrading = function(name, id) {
        if(name){
            var values = name;
            var n = name.replace(/ /g,"-");
            $location.path("/member/" + id + "/" + n.toLowerCase());
        }
    }

    $scope.logout = function(){
        loginService.logout();
    }

    var $promise = $http.get("app/api/users.php?function=GetAllUsers&param=" + userid);
    $promise.then(function(res){
        var students = res.data;
        students.forEach(function(student) {
            if (student.studentID == userid){
                 var index = students.indexOf(student);
                students.splice(index, 1);
            }
        }, this);

        $scope.students = students;
    });
});

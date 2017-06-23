var app = angular.module("p2p");

app.controller("membercontroller", function($scope, $http, $routeParams, $location, loginService, sessionService){

    var name =  $routeParams.name;
    var id = $routeParams.id;
    var assessorid = sessionService.get('uid');

    $scope.logout = function(){
        loginService.logout();
    }

    var req = {
        "assessorid": assessorid,
        "studentid": id
    };

    $scope.name = "";
    $name_arry = name.split("-");
    for (x in $name_arry) {
        if(x == $name_arry.length -1){
            $scope.name += $name_arry[x].replace(/\b\w/g, l => l.toUpperCase()) ;
        } else {
            $scope.name += $name_arry[x].replace(/\b\w/g, l => l.toUpperCase()) + " " ;
        }

    }

    GetGradedFacts();

    $scope.facts = [];
    var $promise = $http.post("app/api/users.php?function=GetStudentUngraded&param=", req);
    $promise.then(function(msg){
        var data = msg.data;
        for(var i in data ){
            var item = data[i];
            $scope.facts.push({
                "factId": item.factID,
                "description": item.description,
                "weekNumber": item.weekNr,
                "lastGrade": item.grade.grade,
                "learningGoal": item.shortText,
                "frameworks": item.frameworks,
                "hashTags": item.hashtags,
                "status": item.status
                }
            )
        }
    });

    function GetGradedFacts() {
        $scope.gradedfacts = [];
        var $promise = $http.post("app/api/users.php?function=GetStudentAssessments&param=", req);
        $promise.then(function(msg){
            var data = msg.data;
            for(var i in data ){
                var item = data[i];
                $scope.gradedfacts.push({
                    "factId": item.factID,
                    "description": item.description,
                    "weekNumber": item.weekNr,
                    "lastGrade": item.grade.grade,
                    "learningGoal": item.shortText,
                    "frameworks": item.frameworks,
                    "hashTags": item.hashtags,
                    "status": item.status,
                    "studentGrades": item.studentGrades
                    }
                )
            }
        });
    }

    $scope.formateText = function formateText(status){
        switch(status) {
            case "Normalized":
                return "text-success";
                break;
            case "Graded":
                return "text-primary";
                break;
            case "Ungraded":
                return "text-danger";
                break;
            default:
                return "text-muted";
        }
    };

    $scope.formateIcon = function formateIcon(icon){
        switch(icon) {
            case "Field":
                return "glyphicon glyphicon-tent";
                break;
            case "Library":
                return "glyphicon glyphicon-sunglasses";
                break;
            case "Workshop":
                return "glyphicon glyphicon-scissors";
                break;
            case "Lab":
                return "glyphicon glyphicon-tint";
                break;
            case "Showroom":
                return "glyphicon glyphicon-blackboard";
                break;
        }
    };

    var gradeButton = document.getElementById("gradeBtn");
    var radios = document.getElementsByName("grade");
    var gradeForm = document.getElementById("gradeFrm");

    $scope.showFact = function(fact){
        $('#myModal').modal('show');
        $scope.fact = fact;

        for(var i=0;i < radios.length;i++) {
            radios[i].checked = false;
        }
        gradeButton.disabled = true;
        gradeForm.reset();
    }

    $scope.submitGrade = function(fact) {

        var factid = fact.factId;
        var studentid = assessorid;
        var grade = $('input[name=grade]:checked', '#gradeFrm').val();
        var comment = $('input#comment').val();

        var assessment = {
            "studentid": studentid,
            "factid": factid,
            "grade": grade,
            "comment": comment
        };

        var $promise = $http.post("app/api/users.php?function=GradeStudentFact&param=", assessment);
        $promise.then(function(msg){
            var index = $scope.facts.indexOf(fact);
            $scope.facts.splice(index, 1);
            GetGradedFacts();
            $('#myModal').modal('hide');
        });
    }

    $('#gradeFrm').on('change', function() {

        if(gradeButton.disabled){
          gradeButton.disabled = false;
        }
    });
});

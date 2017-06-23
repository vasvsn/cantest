var app = angular.module('p2p');

app.controller('gradecontroller', function($scope, $http, loginService, sessionService){
    $scope.weeknr = null;
    $scope.totals = null;
    $scope.graded = [];
    var uid = sessionService.get('uid');

    $scope.logout = function(){
        loginService.logout();
    }

    var $promise = $http.post("app/api/users.php?function=GetStudentLearningGoals&param=" + uid);
    $promise.then(function(res){
        $scope.grades = res.data;
        if($scope.grades){
          calculateTotals($scope.grades);
        }
    });

    $scope.getWeekly = function() {
      var data = {"studentid" : uid, "weeknr": $scope.weeknr };
      var $promise = $http.post("app/api/users.php?function=GetAllStudentGrades&param=", data );
      $promise.then(function(res){
          $scope.weeklyGrades = res.data;
      });

      var $promise = $http.post("app/api/users.php?function=GetStudentFactsPerWeek&param=", data);
      $promise.then(function(res){
          $scope.weeklyFacts = res.data;
      });
    }

    var $promise = $http.get("app/api/users.php?function=GetListOfWeeks&param=");
    $promise.then(function(res){
        $scope.weeks = res.data;
    });

    function calculateTotals(g){
        $scope.totals = { "factSum" : 0, "gradeSum": 0, "percentageSum": 0 };
        g.forEach(function(e) {
            $scope.totals.factSum += parseInt(e.factCount);
            $scope.totals.gradeSum += parseInt(e.grade);
            $scope.totals.percentageSum += (parseInt(e.grade) * 10);
        });
    }

    $scope.setIcon = function(goalname){
        switch (goalname) {
            case "Enterprise Information Systems":
                return "./assets/img/icons/enterprise_info_systems.png";
                break;
            case "Data Visualization & KPIs":
                return "./assets/img/icons/datavis_kpis.png";
                break;
            case "Applied Big Data":
                return "./assets/img/icons/applied_big_data.png";
                break;
            case "Work Ethic":
                return "./assets/img/icons/work_ethic.png";
                break;
            case "Synthesis of Solutions":
                return "./assets/img/icons/synthesis_solutions.png";
                break;
            case "Teamwork":
                return "./assets/img/icons/teamwork.png";
                break;
            case "Data Driven Applications":
                return "./assets/img/icons/data_driven_applications.png";
                break;
            case "Professionality":
                return "./assets/img/icons/professionality.png";
                break;
            case "Adanced Research":
                return "./assets/img/icons/advanced_research.png";
                break;
            case "BI Statistics":
                return "./assets/img/icons/bi_statistics.png";
                break;
            case "Organization & Planning":
                return "./assets/img/icons/organization_planning.png";
                break;
            case "Communication & BRD":
                return "./assets/img/icons/communication.png";
                break;

            default:
                break;
        }
    }

    $scope.setGrade = function setGrade(grade){
        switch(grade) {
            case "0":
                return "nutral-grade";
                break;
            case "1":
                return "good-grade";
                break;
            case "2":
                return "great-grade";
                break;
            case "-1":
                return "bad-grade";
                break;
        }
    };
});

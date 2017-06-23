var app = angular.module("p2p");

app.controller("profilecontroller", function($scope, $http, loginService, sessionService){

    showFacts();

    $scope.facts = [];
    $scope.frameworks =[];
    $scope.tags = [];
    $scope.goals = null;
    $scope.goal = null;

    var comments = document.getElementById("modal-comments");
    var inner = document.getElementById("modal-inner");
    var back = document.getElementById("backbtn");
    var title = $(".modal-title");
    var footer = $(".modal-footer");

    $('#myModal').on('hide.bs.modal', function (e) {
        $(this).data('bs.modal', null);
        clearData();
    })

    $('#myModal').on('shown.bs.modal', function (e) {
    	title.html("New Fact");
        clearData();
    })

    $("#factFrm").submit(function(){
       if (validateForm() == true) {
            addFact();
       }
    });

    $scope.logout = function(){
        loginService.logout();
    }

    $scope.togglecontent = function(fact){
        inner.style.display = 'block';
        comments.style.display = 'none';
        back.style.display = 'none';
        title.html(fact.description);
    }

    $scope.showAssessment = function(assessment) {
        inner.style.display = 'none';
        comments.style.display = 'block';
        back.style.display = 'inline';
        $scope.assessment = assessment;
        title.html("<img class='img-circle' src='." + assessment.studentImageUrl + "' height='25' /> " + assessment.studentName);
    }

    function addFact() {

        $scope.fact.factid = null ;
        $scope.fact.studentid = sessionService.get('uid');
        $scope.fact.avggrade = 0.0;
        $scope.fact.status = "Ungraded";
        $scope.fact.goalid = $scope.goal.goalID;
        $scope.fact.tags = $scope.tags.map(function(tag) { return "#" + tag.text; });
        $scope.fact.frameworks = $scope.frameworks.map(function(tag) { return tag.text; });
        var $promise = $http.post("app/api/users.php?function=InsertStudentFact&param=", $scope.fact);
        $promise.then(function(res){
            $('#myModal').modal('hide');
            $scope.facts = [];
            showFacts();
        });
    }

    function addToList(item) {
        $scope.facts.push({
                    "factId": item.factID,
                    "description": item.description,
                    "weekNumber": item.weekNr,
                    "oldGrade": item.oldgrade,
                    "lastGrade": item.grade,
                    "learningGoal": item.shortText,
                    "frameworks": item.frameworks,
                    "hashTags": item.hashtags,
                    "status": item.status,
                    "avgGrade": item.avgGrade,
                    "studentGrades": item.studentGrades
                    }
            )
    }
    
    var $promise = $http.get("app/api/users.php?function=GetStudentGoals&param=" + sessionService.get('uid'));
        $promise.then(function(msg){
            $scope.goals = msg.data
    });

    function showFacts() {
        var $promise = $http.post("app/api/users.php?function=GetStudentFacts&param=" + sessionService.get('uid'));
        $promise.then(function(msg){
            var data = msg.data;
            for(var i in data ){
                var item = data[i];
                addToList(item);
            }
        });
    }

    $scope.viewGraded = function() {
    	
        var $promise = $http.post("app/api/users.php?function=GetAllGradedFactsPerWeekAlt&param=" + sessionService.get('uid'));
        $promise.then(function(res){
            var graded = res.data;
            $scope.submissions = graded;
            title.html("Grade Submission");
            $('#normalizeModal').modal('show');
        });
    }

    $scope.normalizeGrades = function() {
        var data = {"studentid":  sessionService.get('uid')};
        var $promise = $http.post("app/api/users.php?function=InsertWeekGoalGrade&param=", data);
        $promise.then(function(res){
            var normalized = res;
            $('#normalizeModal').modal('hide');
            $scope.facts = [];
            showFacts();
        });
    }

    $scope.viewFact = function(fact){
        $('#viewModal').modal('show');
        $scope.fact = fact;
        inner.style.display = 'block';
        comments.style.display = 'none';
        back.style.display = 'none';
        title.html(fact.description);
    }

    $scope.deleteFact = function (fact) {
        var data = {
            "studentid" : sessionService.get('uid'),
            "factid" : fact.factId
        }

        var $promise = $http.post("app/api/users.php?function=DeleteFact&param=", data);
        $promise.then(function(msg){
            var index = $scope.facts.indexOf(fact);
            $scope.facts.splice(index, 1);
        });
    }

    function clearData() {
        $scope.tags = [];
        $scope.frameworks =[];
        document.getElementById("factFrm").reset();
        $(".required, select").each(function(){$(this).removeClass("highlight");});
        $('.alert').hide();
    }

    function validateForm(){
        var isFormValid = true;

        $(".required, select").each(function(){

            if ($.trim($(this).val()).length == 0){
                $(this).addClass("highlight");
                isFormValid = false;
            }
            else{
                $(this).removeClass("highlight");
            }
        });

        if (!isFormValid) { $('.alert').show();}
        return isFormValid;
    }

    $scope.loadFrameworks = function(query) {
        var frameworks = [
            { text: 'Library' },
            { text: 'Showroom' },
            { text: 'Lab' },
            { text: 'Field' },
            { text: 'Workshop'}
        ];
        return frameworks;
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

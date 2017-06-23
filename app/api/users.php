<?php
    include 'connection.php';
    $instance = mysqlconnection::getInstance();
    $conn = $instance->getConnection();

    $function_name = "";
    $parameters = "";

    if (isset($_GET)) {
        $function_name = $_GET["function"];
        $parameters = $_GET["param"];
        call_user_func($function_name, $parameters, $conn);
    }

    //start session
    function setUserSession($user){
        session_start();
        $_SESSION["user"] = $user;
    }

     // Get a single student object
     function GetUser($args, $conn){
        $user = json_decode(file_get_contents('php://input'));
        $email = $user->username;
        $pasword = $user->password;
        $md5_password = md5($pasword);
        try {

            $str = $conn->prepare("SELECT * FROM students WHERE studentEmail = :email AND studentPassword = :password LIMIT 1");
            $str->bindParam(':email', $email );
            $str->bindParam(':password', $md5_password );
            $str->execute();

            if ($str->rowCount() == 1) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                $user = json_encode($str->fetch());
                setUserSession($user);
                echo($user);
            } else {
                echo "User doesnt exist";
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    // Get all registered students as objects
    function GetAllUsers($args, $conn){

        try {

            $str = $conn->prepare("SELECT * FROM students");
            $str->execute();

            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                $response = $str->fetchAll();

                $notifications = (array) CountUngradedFacts($args, $conn);
                foreach($response as $key=>$value) {
                    if(isset($notifications) && sizeof($notifications) > 0){
                      foreach($notifications as $n) {
                          if($n["studentID"] == $value["studentID"]){
                              $response[$key]["notifications"] = $n["NumberOfFacts"];
                          }
                      }
                    }
                }
               echo (json_encode($response));
            } else {
                    echo "Users are not registered";
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }
    // Get all student facts
    function GetAllFacts($args, $conn){
        try {

            $str = $conn->prepare("SELECT facts.factID, facts.description, facts.weekNr, goals.shortText  FROM facts JOIN goals ON (facts.goalID = goals.goalID) GROUP BY goals.goalID");
            $str->execute();

            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                $facts = json_encode($str->fetchAll());
                echo($facts);
            } else {
                echo "No Facts Exit";
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    // Get a fact
    function GetStudentFact($args, $conn){
        try {

            $str = $conn->prepare("SELECT facts.factID, facts.description, concat('Week ', facts.weekNr) AS 'weekNr', facts.avgGrade, goals.shortText, facts.status, facts.studentID , facts.goalID
                                   FROM facts JOIN goals
                                   ON (facts.goalID = goals.goalID)
                                   JOIN grades
                                   ON (facts.goalID = grades.goalID)
                                   WHERE facts.factId = :id  AND facts.studentID = grades.studentID
                                   ");
            $str->bindParam(':id', $args );
            $str->execute();

            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);

                   $values = $str->fetchAll();

                    $id = $values[0]["factID"];
                    $week = $values[0]["weekNr"];
                    $goalid = $values[0]["goalID"];
                    $studentid = $values[0]["studentID"];

                    $weekNr = substr($week, 5);
                    $tags = GetStudentFactTags($id, $conn);
                    $oldgrade = GetPreviousGoalGrade(array($studentid, $goalid , $weekNr), $conn);


                    $hashtags =  array();
                    $frameworks = array();

                    if (sizeof($tags) > 0){
                        $tags_output = json_encode($tags[0]);
                        $tags_encode = json_decode($tags_output);
                        $hashtags = explode(" ", $tags_encode->hashtags);
                        $frameworks = explode(" ", $tags_encode ->frameworks);
                    }
                    $values[0]["grade"] = $oldgrade;
                    $values[0]["frameworks"] = $frameworks;
                    $values[0]["hashtags"] = $hashtags;
                    $values[0]["studentGrades"] = GetGradedFactStudents($id, $conn);


               return json_encode($values[0]);
            } else {
                echo "Fact doesnt Exist ";
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    // Get a student facts
    function GetStudentFacts($args, $conn){
        try {

           /* $str = $conn->prepare("SELECT facts.factID, facts.description, concat('Week ', facts.weekNr) AS 'weekNr', facts.avgGrade, goals.shortText, facts.status, grades.grade
                                   FROM facts JOIN goals
                                   ON (facts.goalID = goals.goalID)
                                   JOIN grades
                                   ON (facts.goalID = grades.goalID)
                                   WHERE facts.studentID = :id  AND facts.studentID = grades.studentID
                                   ");*/

            $str = $conn->prepare("SELECT facts.factID, facts.description, concat('Week ', facts.weekNr) AS 'weekNr', facts.avgGrade, goals.shortText, facts.status , facts.goalID, facts.weekNr As 'week'
                                   FROM facts JOIN goals
                                   ON (facts.goalID = goals.goalID)
                                   WHERE facts.studentID = :id
                                   ORDER BY facts.weekNr ASC");

            $str->bindParam(':id', $args );
            $str->execute();

            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);

                $values = $str->fetchAll();
                foreach($values as $key => $value) {
                    $id = $value["factID"];
                    $tags = GetStudentFactTags($id, $conn);

                    $hashtags =  array();
                    $frameworks = array();

                    if (sizeof($tags) > 0){
                        $tags_output = json_encode($tags[0]);
                        $tags_encode = json_decode($tags_output);
                        $hashtags = explode(" ", $tags_encode->hashtags);
                        $frameworks = explode(" ", $tags_encode ->frameworks);
                    }

                    $grade = GetWeekGoalGrade(array($args, $value["goalID"], $value["week"] ), $conn);
                    $oldgrade = GetPreviousGoalGrade(array($args, $value["goalID"], $value["week"]), $conn);

                    $values[$key]["grade"] = $grade["grade"];
                    $values[$key]["oldgrade"] = $oldgrade["grade"];
                    $values[$key]["frameworks"] = $frameworks;
                    $values[$key]["hashtags"] = $hashtags;
                    $values[$key]["studentGrades"] = GetGradedFactStudents($id, $conn);
                }

               echo json_encode($values);
            } else {
                //echo "No facts exist for student ";
                return [];
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }


    function GetStudentFactsPerWeek($args, $conn){
        $req = json_decode(file_get_contents('php://input'));
        $weekid = $req->weeknr;
        $studentid = $req->studentid;
        try {

            $str = $conn->prepare("SELECT facts.factID, facts.description, concat('Week ', facts.weekNr) AS 'weekNr', facts.avgGrade, goals.shortText, facts.status , facts.goalID, facts.weekNr As 'week'
                                   FROM facts JOIN goals
                                   ON (facts.goalID = goals.goalID)
                                   WHERE facts.studentID = :studentid AND facts.weekNr = :weeknr
                                   ORDER BY facts.weekNr ASC");

            $str->bindParam(':studentid', $studentid);
            $str->bindParam(':weeknr', $weekid);
            $str->execute();

            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);

                $values = $str->fetchAll();
                foreach($values as $key => $value) {
                    $id = $value["factID"];
                    $tags = GetStudentFactTags($id, $conn);

                    $hashtags =  array();
                    $frameworks = array();

                    if (sizeof($tags) > 0){
                        $tags_output = json_encode($tags[0]);
                        $tags_encode = json_decode($tags_output);
                        $hashtags = explode(" ", $tags_encode->hashtags);
                        $frameworks = explode(" ", $tags_encode ->frameworks);
                    }

                    $grade = GetWeekGoalGrade(array($args, $value["goalID"], $value["week"] ), $conn);
                    $oldgrade = GetPreviousGoalGrade(array($args, $value["goalID"], $value["week"]), $conn);

                    $values[$key]["grade"] = $grade["grade"];
                    $values[$key]["oldgrade"] = $oldgrade["grade"];
                    $values[$key]["frameworks"] = $frameworks;
                    $values[$key]["hashtags"] = $hashtags;
                    $values[$key]["studentGrades"] = GetGradedFactStudents($id, $conn);
                }

               echo json_encode($values);
            } else {
                //echo "No facts exist for student ";
                return [];
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }


    // get ungrade facts for a student
    function GetStudentUngraded($args, $conn) {
        $req = json_decode(file_get_contents('php://input'));
        $assessor = $req->assessorid;
        $student = $req->studentid;
        try {

            $str = $conn->prepare("SELECT * FROM facts
                                  WHERE NOT EXISTS
                                  (SELECT factID FROM assessors
                                  WHERE assessors.factId = facts.factID
                                  AND assessors.studentID = :assessorid)
                                  AND facts.studentID = :studentid");
            $str->bindParam(':studentid', $student );
            $str->bindParam(':assessorid', $assessor );
            $str->execute();
            $response = array();
            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                foreach( $str->fetchall() as $key => $value){
                    array_push($response, json_decode(getStudentFact($value["factID"], $conn)));
                }
                print json_encode($response);
            } else {
                return [];
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }

    }

    // get Student Assessment of a fact
    function GetStudentAssessments($args, $conn){
        $req = json_decode(file_get_contents('php://input'));
        $assessor = $req->assessorid;
        $student = $req->studentid;
        try {

            $str = $conn->prepare("SELECT COUNT(assessors.factID) AS 'GradeCounter', assessors.factID
                                   FROM facts JOIN assessors
                                   ON (facts.factID = assessors.factID)
                                   WHERE assessors.factid IN
                                   (SELECT factid FROM facts WHERE studentid = :studentid)
                                   AND assessors.studentID = :assessorid
                                   GROUP BY assessors.factid");
            $str->bindParam(':studentid', $student );
            $str->bindParam(':assessorid', $assessor );
            $str->execute();
            $response = array();
            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                foreach( $str->fetchAll() as $key => $value){
                    //array_push($response, json_decode(getStudentFact($value["factID"], $conn)));
                    $fact = json_decode(GetStudentFact($value["factID"], $conn));
                    $fact->studentGrades = GetGradedFactStudents($value["factID"], $conn);
                 array_push($response, $fact);
                }
            print json_encode($response);
            } else {
                return [];
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    //Get a list of all students who have graded a fact
    function GetGradedFactStudents($args, $conn) {
        try {
             $str = $conn->prepare("SELECT assessors.studentID, assessors.comment, assessors.grade, students.studentName, students.studentImageUrl
                                    FROM assessors JOIN students
                                    ON (assessors.studentID = students.studentID)
                                    WHERE assessors.factID = :factID
                                    ORDER BY assessors.studentID ASC");
            $str->bindParam(':factID', $args);
            $str->execute();
             if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                return $str->fetchAll();
            } else {
                return [];
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    // Get a student fact tags (frameworks, hashtags)
    function GetStudentFactTags($args, $conn){
        try {

            $str = $conn->prepare("SELECT hashtags, frameworks FROM tags
                                       WHERE factId = :id ");
            $str->bindParam(':id', $args );
            $str->execute();
            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                return $str->fetchall();
            } else {
                return [];
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

     // Insert a new grade for a student fact
    function GradeStudentFact($args, $conn){
        $assessment = json_decode(file_get_contents('php://input'));
        $studentid = $assessment->studentid;
        $factid = $assessment->factid;
        $grade = $assessment->grade;
        $comment = $assessment->comment;
        date_default_timezone_set('Europe/Amsterdam');
        $timestamp = date('Y-m-d H:m:s');

        try {
            $gradeCount = IsGraded($factid, $conn);
            $numberOfGrades = $gradeCount["NumberOfGrades"];
            $totalOfGrades = $gradeCount["Total"];

            if($numberOfGrades  < 5) {
                if (($numberOfGrades + 1) == 5) {
                    $str = $conn->prepare("INSERT INTO assessors
                                    VALUES(:studentid, :factid, :grade, :comment, :gradedate)");
                    $str->bindParam(':studentid', $studentid  );
                    $str->bindParam(':factid', $factid );
                    $str->bindParam(':grade', $grade );
                    $str->bindParam(':comment', $comment );
                    $str->bindParam(':gradedate', $timestamp );
                    $str->execute();

                    $totalOfGrades += $grade;
                    $avg = $totalOfGrades/5;

                    $obj = new stdClass();
                    $obj->factId = $factid;
                    $obj->status = "Graded";
                    $obj->avgGrade = $avg;
                    UpdateStudentFacts($obj, $conn);

                } else {
                    $str = $conn->prepare("INSERT INTO assessors
                                    VALUES(:studentid, :factid, :grade, :comment, :gradedate)");
                    $str->bindParam(':studentid', $studentid  );
                    $str->bindParam(':factid', $factid );
                    $str->bindParam(':grade', $grade );
                    $str->bindParam(':comment', $comment );
                    $str->bindParam(':gradedate', $timestamp );
                    $str->execute();
                }
            }
        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    // Check if a fact has been graded by all assessors
    function IsGraded($args, $conn) {
        try {

            $str = $conn->prepare("SELECT COUNT(*) as 'NumberOfGrades', SUM(grade) as Total FROM assessors WHERE factID = :factid ");
            $str->bindParam(':factid', $args );
            $str->execute();
            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                $numberofgrades = $str->fetch();

                return $numberofgrades;
            } else {
                return 0;
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    // Update student facts
    function UpdateStudentFacts($args, $conn){
        //$updates_json = json_decode(file_get_contents('php://input'));
        $updates_arr = (array) $args;

        try {
            $data = ["factId" => 1, "avgGrade" => 6, "studentID" => 1];
            $sql_before = "UPDATE facts SET ";
            $sql_after = "WHERE factId = :factId";
            $sql_params = null;
            $j = 1;
            foreach($updates_arr as $key=>$value){
                if($j < sizeof($updates_arr)) {
                    $sql_params  .= $key . " = :" . $key . ", ";
                } else {
                    $sql_params  .= $key . " = :" . $key . " " ;
                }
                $j++;
            }
            $sql_conc = $sql_before.$sql_params.$sql_after;
            $str = $conn->prepare($sql_conc);

            $bindings = [];
            foreach($updates_arr as $key=>$value){
                $bindings[":".$key] = $value;
            }
            $str->execute($bindings);


        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    //Insert new student Fact
    function InsertStudentFact($args, $conn){
        $fact_json = json_decode(file_get_contents('php://input'));
        $conn->beginTransaction();
        try {

            $str = $conn->prepare("INSERT INTO facts VALUES(:factId, :studentId, :goalId, :weekNr, :description, :avgGrade, :status) ");
            $str->bindParam(':factId', $fact_json->factid );
            $str->bindParam(':studentId', $fact_json->studentid );
            $str->bindParam(':goalId', $fact_json->goalid );
            $str->bindParam(':weekNr', $fact_json->weeknr );
            $str->bindParam(':description', $fact_json->description);
            $str->bindParam(':avgGrade', $fact_json->avggrade );
            $str->bindParam(':status', $fact_json->status );
            $str->execute();

            $factid = $conn->lastInsertId();
            $hashtags = implode(" ", (array) $fact_json->tags);
            $frameworks = implode(" ", (array) $fact_json->frameworks);

            $str = $conn->prepare("INSERT INTO tags VALUES(:factId, :hashtags, :frameworks)");
            $str->bindParam(':factId', $factid);
            $str->bindParam(':hashtags', $hashtags);
            $str->bindParam(':frameworks', $frameworks);
            $str->execute();

            $conn->commit();

            $response = GetStudentFact($factid, $conn);
            echo ($response);

        } catch(PDOException $e) {
            echo ($e->getMessage());
            $conn->rollBack();
        }
    }

    function CountUngradedFacts($args, $conn) {
        //$studentid = $args[0];
        $assessorid = $args;

        try {

            $str = $conn->prepare("SELECT COUNT(f.factID) as 'NumberOfFacts', f.studentID
                                   FROM facts f WHERE f.studentID <> :assessorid
                                   AND NOT EXISTS
                                        (SELECT 1 FROM assessors WHERE factID = f.factID AND studentID = :assessorid)
                                   GROUP BY studentID");
            //$str->bindParam(':studentid', $studentid);
            $str->bindParam(':assessorid', $assessorid);

            $str->execute();
            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                $numberofgrades = $str->fetchAll();
                return $numberofgrades;
            } else {
                return 0;
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function InsertWeekGrade($args, $conn) {
        $grade_json = json_decode(file_get_contents('php://input'));
        $studentid = $grade_json->studentid;
        $goalid = $grade_json->goalid;
        $weeknr = $grade_json->weeknr;
        $grade = $grade_json->grade;

        try {

            $str = $conn->prepare("INSERT INTO grades VALUES(:studentID, :goalID, :weekNr, :grade)");
            $str->bindParam(":studentID", $studentid);
            $str->bindParam(":goalID", $goalid);
            $str->bindParam(":weekNr", $weeknr);
            $str->bindParam(":grade", $grade);

            $str->execute();
        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function DeleteFact($args, $conn){
        $fact_json = json_decode(file_get_contents('php://input'));
        $studentid = $fact_json->studentid;
        $factid = $fact_json->factid;
         try {

            $str = $conn->prepare("DELETE FROM facts WHERE studentID = :studentid AND factID = :factid");
            $str->bindParam(":studentid", $studentid);
            $str->bindParam(":factid", $factid);
            $str->execute();

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function GetStudentLearningGoals($args, $conn) {
         try {

            $str = $conn->prepare("SELECT DISTINCT g.goalID, g.grade, gl.type , gl.shortText,(SELECT COUNT(factID) FROM facts WHERE goalID = g.goalID AND studentID = :studentid) AS 'factCount'
                                   FROM grades g JOIN goals gl
                                   ON (g.goalID = gl.goalID)
                                   WHERE g.studentID = :studentid
                                   AND g.grade IN
                                                (SElECT v.grade FROM grades v WHERE v.studentId = :studentid AND v.weekNr =
                                                    ( SELECT MAX(b.weekNr) FROM grades b WHERE b.studentID = :studentid AND b.goalID = g.goalID))
                                   GROUP BY  g.grade");
            /*$str = $conn->prepare("SELECT DISTINCT g.goalID, g.grade, gl.type , gl.shortText,(SELECT COUNT(factID) FROM facts WHERE goalID = g.goalID AND studentID = :studentid) AS 'factCount'
                                   FROM grades g JOIN goals gl
                                   ON (g.goalID = gl.goalID)
                                   WHERE g.studentID = :studentid
                                   AND g.grade IN
                                                (SElECT v.grade FROM grades v WHERE v.studentId = :studentid AND v.weekNr =
                                                    ( SELECT MAX(b.weekNr) FROM grades b WHERE b.studentID = :studentid AND b.goalID = g.goalID))
                                   ");*/
            $str->bindParam(":studentid", $args);
            $str->execute();
            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);

                echo json_encode($str->fetchAll());
            } else {
                return 0;
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function GetAllGradedFactsPerWeek($args, $conn) {
        try {

            $str = $conn->prepare("SELECT DISTINCT(weekNr), facts.goalID, AVG(avgGrade) AS average, COUNT(factID) as 'facts', goals.shortText as 'goalName'
                                   FROM facts JOIN goals
                                   ON (facts.goalID = goals.goalID)
                                   WHERE status = 'Graded' AND studentID = :studentid
                                   GROUP BY  weekNr, facts.goalID");
            $str->bindParam(":studentid", $args);
            $str->execute();

            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                return json_encode($str->fetchAll());
            } else {
                return 0;
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function GetAllGradedFactsPerWeekAlt($args, $conn) {
        try {

            $str = $conn->prepare("SELECT DISTINCT(weekNr), facts.goalID, AVG(avgGrade) AS average, COUNT(factID) as 'facts', goals.shortText as 'goalName'
                                   FROM facts JOIN goals
                                   ON (facts.goalID = goals.goalID)
                                   WHERE status = 'Graded' AND studentID = :studentid
                                   GROUP BY  weekNr, facts.goalID");
            $str->bindParam(":studentid", $args);
            $str->execute();

            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                echo json_encode($str->fetchAll());
            } else {
                return 0;
            }

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function InsertWeekGoalGrade($args, $conn) {
        $normalize_json = json_decode(file_get_contents('php://input'));
        $studentid = $normalize_json->studentid;

        try {
            $newgrades = GetAllGradedFactsPerWeek($studentid, $conn);
            $newgrades_arry = json_decode($newgrades);

            foreach($newgrades_arry as $key=>$value){
                $lastgrade = GetLatestGoalGrade(array($studentid, $value->goalID), $conn);
                $goalid = $value->goalID;
                $weeknr = $value->weekNr;
                $avg = $value->average;
                $grade = $lastgrade["grade"] + $avg;
                $gradeid = null;
                
                $str = $conn->prepare("INSERT INTO grades VALUES(:gradeid, :studentid, :goalid, :weeknr, :grade)");
                $str->bindParam(":gradeid", $gradeid);
                $str->bindParam(":studentid", $studentid);
                $str->bindParam(":goalid", $goalid);
                $str->bindParam(":weeknr", $weeknr);
                $str->bindParam(":grade", $grade);
                $str->execute();
            }

            $str = $conn->prepare("UPDATE facts SET status = 'Normalized' WHERE studentID = :studentid AND status = 'Graded'");
            $str->bindParam(":studentid", $studentid);
            $str->execute();

        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function GetLatestGoalGrade($args, $conn) {
        // $grade_json = json_decode(file_get_contents('php://input'));
        // $studentid = $grade_json->studentid;
        // $goalid = $grade_json->goalid;

        $studentid = $args[0];
        $goalid = $args[1];
        try {

            $str = $conn->prepare("SELECT grade FROM grades
                                    WHERE studentID = :studentid
                                    AND goalID = :goalid AND WeekNr =
                                    (SELECT MAX(weekNr) FROM grades WHERE studentID = :studentid AND goalID = :goalid)");
            $str->bindParam(":studentid", $studentid);
            $str->bindParam(":goalid", $goalid);

            $str->execute();

             if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                return $str->fetch();
            } else {
                return 0;
            }
        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

     function GetWeekGoalGrade($args, $conn) {
        // $grade_json = json_decode(file_get_contents('php://input'));
        // $studentid = $grade_json->studentid;
        // $goalid = $grade_json->goalid;

        $studentid = $args[0];
        $goalid = $args[1];
        $weeknr = $args[2];
        try {

            $str = $conn->prepare("SELECT grade FROM grades
                                    WHERE studentID = :studentid
                                    AND goalID = :goalid AND WeekNr = :weeknr");
            $str->bindParam(":studentid", $studentid);
            $str->bindParam(":goalid", $goalid);
            $str->bindParam(":weeknr", $weeknr);

            $str->execute();

             if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                return $str->fetch();
            } else {
                return 0;
            }
        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function GetPreviousGoalGrade($args, $conn) {
        // $grade_json = json_decode(file_get_contents('php://input'));
        // $studentid = $grade_json->studentid;
        // $goalid = $grade_json->goalid;

        $studentid = $args[0];
        $goalid = $args[1];
        $weekid = $args[2];
        try {

            /*$str = $conn->prepare("SELECT grade FROM grades
                                    WHERE studentID = :studentid
                                    AND goalID = :goalid AND WeekNr =
                                    (SELECT IFNULL(MAX(g.weekNr), (SELECT weekNr from grades where studentID = :studentid AND goalID = :goalid)) AS 'weekNr'
                                      FROM grades g WHERE g.studentID = :studentid AND g.goalID = :goalid AND g.weekNr <
                                      (SELECT MAX(weekNr) FROM grades WHERE studentID = g.studentID AND goalID = g.goalID))");*/
            /*$str = $conn->prepare("SELECT MAX(grade) as 'grade'
                            FROM grades 
                            WHERE studentID = :studentid 
                            AND goalID = :goalid 
                            AND WeekNr = :weekid");*/
            $str = $conn->prepare("SELECT MAX(grade) as 'grade'
                                    FROM grades 
                                    WHERE studentID = :studentid 
                                    AND goalID = :goalid 
                                    AND WeekNr = (Select MAX(g.weekNr) 
                                                    FROM grades g 
                                                    WHERE g.weekNr < :weekid
                                                    AND g.studentid = :studentid 
                                                    AND g.goalID = :goalid AND g.grade IS NOT NULL)");
            $str->bindParam(":studentid", $studentid);
            $str->bindParam(":goalid", $goalid);
            $str->bindParam(":weekid", $weekid);

            $str->execute();

             if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                return $str->fetch();
            } else {
                return 0;
            }
        } catch(PDOException $e) {
            echo ($e->getMessage());
        }
    }

    function GetAllStudentGrades($args, $conn) {
        $json_out = json_decode(file_get_contents('php://input'));
        $studentid = $json_out->studentid;
        $weeknr = $json_out->weeknr;
       try {

           $str = $conn->prepare("SELECT weekNr, grade, goals.shortText, (SELECT AVG(avgGrade) as 'average' FROM facts WHERE studentID = :studentid AND weekNr = :weeknr AND goalID = goals.goalID) as 'average'
                                  FROM grades JOIN goals
                                  ON (goals.goalID = grades.goalID)
                                    WHERE studentID = :studentid AND weekNr = :weeknr
                                   ORDER BY weekNr");
           $str->bindParam(":studentid", $studentid);
           $str->bindParam(":weeknr", $weeknr);
           $str->execute();

            if ($str->rowCount() > 0) {
               $results = $str->setFetchMode(PDO::FETCH_ASSOC);
               echo json_encode($str->fetchAll());
           } else {
               return [];
           }
       } catch(PDOException $e) {
           echo ($e->getMessage());
       }
   }

   function GetListOfWeeks($args, $conn) {
      try {

          $str = $conn->prepare("SELECT DISTINCT(weekNr) from grades GROUP BY weekNr ORDER BY weekNr ASC");
          $str->execute();

           if ($str->rowCount() > 0) {
              $results = $str->setFetchMode(PDO::FETCH_ASSOC);
              echo json_encode($str->fetchAll());
          } else {
              return [];
          }
      } catch(PDOException $e) {
          echo ($e->getMessage());
      }
  }

  function GetStudentGoals($args, $conn) {

        try {

            $str = $conn->prepare("SELECT DISTINCT goals.goalID, goals.shortText 
                                    FROM goals JOIN grades 
                                    ON (goals.goalID = grades.goalID) 
                                    WHERE grades.studentID = :id 
                                    GROUP BY grades.goalID ");
            $str->bindParam(":id", $args);
            $str->execute();  

            if ($str->rowCount() > 0) {
                $results = $str->setFetchMode(PDO::FETCH_ASSOC);
                echo json_encode($str->fetchAll());
            } else {
                return 0;
            }          
        } catch(PDOException $e) {
            echo ($e->getMessage());
        }  
    }
?>

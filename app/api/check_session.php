<?php 
    session_start();
    if(isset($_SESSION["user"])){
        echo "authenticated";
    } else {
        echo "logged-out";
    }
?>
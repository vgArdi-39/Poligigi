<?php
require_once 'assets/session.php';

$session = new Session();
$session->destroy();
header("Location: index.php");
exit(); 
?>
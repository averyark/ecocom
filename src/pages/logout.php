<?php
session_start();
session_destroy();
header("Location: ../pages/signin/index.php");
exit;
?>
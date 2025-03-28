<?php
require 'config.php';
session_unset(); // Remove all session variables
session_destroy();
header("Location: home.php");
exit;

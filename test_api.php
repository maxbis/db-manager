<?php
// Simple test to check API syntax
$test_code = file_get_contents('api.php');

// Check for syntax errors
$syntax_check = shell_exec('php -l api.php 2>&1');
echo "Syntax check result:\n";
echo $syntax_check;
?>

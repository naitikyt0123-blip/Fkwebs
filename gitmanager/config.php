<?php
// config.php

// Token direct file me nahi likhna hai! 
// getenv() Railway ke variables se token uthayega.
define('GITHUB_PAT', getenv('GITHUB_PAT'));
define('GITHUB_USERNAME', getenv('GITHUB_USERNAME'));

define('GITHUB_USER_AGENT', 'My-Private-Repo-Manager');
?>

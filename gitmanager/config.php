<?php
// config.php - Securely load your PAT
// Railway dashboard me 'GITHUB_PAT' aur 'GITHUB_USERNAME' env variables set karna
define('GITHUB_PAT', getenv('GITHUB_PAT') ?: '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');
define('GITHUB_USERNAME', getenv('GITHUB_USERNAME') ?: 'naitikyt0123-blip');

// User Agent is required by GitHub API
define('GITHUB_USER_AGENT', 'My-Private-Repo-Manager');
?>

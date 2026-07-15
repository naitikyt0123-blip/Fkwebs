<?php
// config.php - Securely load your PAT
// Railway dashboard me 'GITHUB_PAT' aur 'GITHUB_USERNAME' env variables set karna
define('GITHUB_PAT', getenv('GITHUB_PAT') ?: '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');
define('GITHUB_USERNAME', getenv('GITHUB_USERNAME') ?: 'naitikyt0123-blip');

// User Agent is required by GitHub API
define('GITHUB_USER_AGENT', 'My-Private-Repo-Manager');
?>

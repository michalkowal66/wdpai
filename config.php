<?php

// Database connection constants pulled from environment variables
define('USERNAME', getenv('POSTGRES_USER') ?: 'postgres');
define('PASSWORD', getenv('POSTGRES_PASSWORD') ?: 'postgres');
define('HOST', getenv('POSTGRES_HOST') ?: 'db');
define('DATABASE', getenv('POSTGRES_DB') ?: 'hotdesk');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true');
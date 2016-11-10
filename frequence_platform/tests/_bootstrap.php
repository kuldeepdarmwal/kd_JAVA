<?php
// This is global bootstrap for autoloading

// Require the environment-specific file,
// or load some defaults
if (file_exists(__DIR__.'/_bootstrap.env.php'))
{
	// to create this file, copy definitions from the `else` block and update the values
	require_once(__DIR__.'/_bootstrap.env.php');
}
else
{
	define('TEST_PASSWORD', 'freQ487devT4');
	define('TEST_PASSWORD_HASH', '$2a$08$eVjbb5HiRuNCkYB8WnRr6uLTX3fuDbb8Ax76mMcViMCTZ6TC4TLmu');
	define('SUBDOMAIN', ''); // if defined, requires a preceding "." dot
	define('TEST_ENV_PREFIX', '');
}

define('TEST_PREFIX', 'ZZ_TEST_CODECEPTION ' . TEST_ENV_PREFIX);

\Codeception\Util\Autoload::registerSuffix('Page', __DIR__.DIRECTORY_SEPARATOR.'_pages');

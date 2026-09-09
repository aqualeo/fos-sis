<?php
/**
 * The base configurations of RosarioSIS
 *
 * @package RosarioSIS
 */

// Set working directory to web root
chdir(__DIR__);
$RosarioPath = __DIR__ . '/';

// Database type
$DatabaseType = 'postgresql';

// Database connection settings
$DatabaseServer = getenv('PGHOST') ?: ($_ENV['PGHOST'] ?? 'localhost');
$DatabasePort = getenv('PGPORT') ?: ($_ENV['PGPORT'] ?? '5432');
$DatabaseUsername = getenv('PGUSER') ?: ($_ENV['PGUSER'] ?? 'postgres');
$DatabasePassword = getenv('PGPASSWORD') ?: ($_ENV['PGPASSWORD'] ?? '');
$DatabaseName = getenv('PGDATABASE') ?: ($_ENV['PGDATABASE'] ?? 'railway');

// Path to wkhtmltopdf (leave empty to render in HTML)
$wkhtmltopdfPath = '';

// Default school year
$DefaultSyear = '2026';

// Notification & Error addresses
$RosarioNotifyAddress = 'fos@aqualeo.co';
$RosarioErrorsAddress = 'devops@aqualeo.co';

// Locales
$RosarioLocales = [ 'en_GB.utf8' ];

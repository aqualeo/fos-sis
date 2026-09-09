<?php
/**
 * The base configurations of RosarioSIS
 *
 * @package RosarioSIS
 */

// Database type
$DatabaseType = 'postgresql';

// Database server hostname
$DatabaseServer = getenv('PGHOST') ?: ($_ENV['PGHOST'] ?? 'localhost');

// Database port
$DatabasePort = getenv('PGPORT') ?: ($_ENV['PGPORT'] ?? '5432');

// Database username
$DatabaseUsername = getenv('PGUSER') ?: ($_ENV['PGUSER'] ?? 'postgres');

// Database password
$DatabasePassword = getenv('PGPASSWORD') ?: ($_ENV['PGPASSWORD'] ?? '');

// Database name
$DatabaseName = getenv('PGDATABASE') ?: ($_ENV['PGDATABASE'] ?? 'railway');

// Path to wkhtmltopdf (empty string renders reports in HTML)
$wkhtmltopdfPath = '';

// Default school year
$DefaultSyear = '2026';

// Notification & Error addresses
$RosarioNotifyAddress = 'fos@aqualeo.co';
$RosarioErrorsAddress = 'devops@aqualeo.co';

// Locales
$RosarioLocales = [ 'en_GB.utf8' ];

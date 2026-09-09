<?php
/**
 * The base configurations of RosarioSIS
 *
 * @package RosarioSIS
 */

// Database type
$DatabaseType = 'postgresql';

// Database server hostname
$DatabaseServer = getenv('PGHOST') ?: 'localhost';

// Database port
$DatabasePort = getenv('PGPORT') ?: '5432';

// Database username
$DatabaseUsername = getenv('PGUSER') ?: 'postgres';

// Database password
$DatabasePassword = getenv('PGPASSWORD') ?: '';

// Database name
$DatabaseName = getenv('PGDATABASE') ?: 'railway';

// PDF path (empty string renders in HTML)
$wkhtmltopdfPath = '';

// Default school year
$DefaultSyear = '2026';

// Notification & Error addresses
$RosarioNotifyAddress = 'fos@aqualeo.co';
$RosarioErrorsAddress = 'devops@aqualeo.co';

// Locales
$RosarioLocales = [ 'en_GB.utf8' ];

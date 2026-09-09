<?php
/**
 * Database functions
 *
 * @package RosarioSIS
 */

/**
 * Establish DB connection
 *
 * @global string $DatabaseServer   Database server hostname
 * @global string $DatabaseUsername Database username
 * @global string $DatabasePassword Database password
 * @global string $DatabaseName     Database name
 * @global string $DatabasePort     Database port
 * @global string $DatabaseType     Database type: mysql or postgresql
 *
 * @param  bool $show_error Show error and die. Optional, defaults to true.
 * @return mixed PostgreSQL or MySQL connection resource
 */
function db_start( $show_error = true )
{
	global $DatabaseServer,
		$DatabaseUsername,
		$DatabasePassword,
		$DatabaseName,
		$DatabasePort,
		$DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		mysqli_report( MYSQLI_REPORT_OFF );

		$db_connection = mysqli_connect(
			$DatabaseServer,
			$DatabaseUsername,
			$DatabasePassword,
			$DatabaseName,
			$DatabasePort
		);
	}
	else
	{
		$connectstring = 'host=' . $DatabaseServer . ' ';

		if ( isset( $DatabasePort ) && $DatabasePort !== '5432' && ! empty( $DatabasePort ) )
		{
			$connectstring .= 'port=' . $DatabasePort . ' ';
		}

		$connectstring .= 'dbname=' . $DatabaseName . ' user=' . $DatabaseUsername;

		if ( $DatabasePassword !== '' && ! empty( $DatabasePassword ) )
		{
			$connectstring .= ' password=' . $DatabasePassword;
		}

		$db_connection = @pg_connect( $connectstring );
	}

	if ( $db_connection === false && $show_error )
	{
		db_show_error(
			'',
			sprintf( "Could not Connect to Database Server '%s'.", $DatabaseServer ),
			( $DatabaseType === 'mysql' ? mysqli_connect_error() : ( error_get_last()['message'] ?? 'Check database credentials' ) )
		);
	}

	return $db_connection;
}

/**
 * Execute DB query
 */
function db_query( $sql, $show_error = true )
{
	global $db_connection, $DatabaseType;

	if ( ! isset( $db_connection ) || ! $db_connection )
	{
		$db_connection = db_start( $show_error );
	}

	if ( ! $db_connection )
	{
		return false;
	}

	if ( $DatabaseType === 'mysql' )
	{
		$result = mysqli_multi_query( $db_connection, $sql );

		if ( $result )
		{
			$result = mysqli_store_result( $db_connection );

			while ( mysqli_more_results( $db_connection ) )
			{
				if ( mysqli_next_result( $db_connection ) )
				{
					$result = mysqli_store_result( $db_connection );
				}
			}

			if ( ! $result && ! mysqli_errno( $db_connection ) )
			{
				$result = null;
			}
		}
	}
	else
	{
		$result = @pg_exec( $db_connection, $sql );
	}

	if ( $result === false && $show_error )
	{
		db_show_error(
			$sql,
			'DB Execute Failed.',
			( $DatabaseType === 'mysql' ? mysqli_errno( $db_connection ) . ' ' . mysqli_error( $db_connection ) : pg_last_error( $db_connection ) )
		);
	}

	return $result;
}

/**
 * SQL query filter
 */
function db_sql_filter( $sql )
{
	if ( stripos( $sql, 'INSERT INTO ' ) !== false )
	{
		$sql = preg_replace( "/([,\(])[\r\n\t ]*''(?!')/", '\\1NULL', $sql );
	}

	$sql = preg_replace( "/(<>|=)[\r\n\t ]*''(?!'|\w|\d)/", '\\1NULL', $sql );

	$sql = str_ireplace( [ '<>NULL', '!=NULL' ], ' IS NOT NULL', $sql );

	return $sql;
}

/**
 * DBQuery wrapper
 */
function DBQuery( $sql )
{
	$sql = db_sql_filter( $sql );

	$result = db_query( $sql );

	if ( function_exists( 'do_action' ) )
	{
		do_action( 'database.inc.php|dbquery_after', [ $sql, $result ] );
	}

	return $result;
}

/**
 * Return next row
 */
function db_fetch_row( $result )
{
	global $DatabaseType;

	$return = false;

	if ( $DatabaseType === 'mysql' )
	{
		if ( $result instanceof mysqli_result )
		{
			$return = mysqli_fetch_assoc( $result );
		}
	}
	else
	{
		$return = @pg_fetch_array( $result, null, PGSQL_ASSOC );
	}

	return is_array( $return ) ? array_change_key_case( $return, CASE_UPPER ) : $return;
}

/**
 * Error display helper
 */
function db_show_error( $sql, $msg, $error )
{
	echo '<div style="font-family:sans-serif; background:#fee; border:1px solid #f99; padding:15px; margin:20px; border-radius:4px;">';
	echo '<h3 style="color:#c00; margin-top:0;">Database Error</h3>';
	echo '<p><strong>' . htmlspecialchars( $msg ) . '</strong></p>';
	if ( $error )
	{
		echo '<p><em>Error:</em> ' . htmlspecialchars( $error ) . '</p>';
	}
	if ( $sql )
	{
		echo '<pre style="background:#fff; padding:10px; border:1px solid #ddd;">' . htmlspecialchars( $sql ) . '</pre>';
	}
	echo '</div>';
	exit;
}

<?php
/**
 * Database functions
 *
 * @package RosarioSIS
 */

/**
 * Establish DB connection
 */
function db_start( $show_error = true )
{
	global $DatabaseServer,
		$DatabaseUsername,
		$DatabasePassword,
		$DatabaseName,
		$DatabasePort,
		$DatabaseType;

	if ( empty( $DatabaseType ) )
	{
		$DatabaseType = 'postgresql';
	}

	if ( $DatabaseType === 'mysql' )
	{
		mysqli_report( MYSQLI_REPORT_OFF );

		$db_connection = mysqli_connect(
			$DatabaseServer,
			$DatabaseUsername,
			$DatabasePassword,
			$DatabaseName,
			$DatabasePort ?: 3306
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
 * Escape String for SQL queries
 */
function DBEscapeString( $str )
{
	global $db_connection, $DatabaseType;

	if ( ! isset( $db_connection ) || ! $db_connection )
	{
		$db_connection = db_start( false );
	}

	if ( ! is_string( $str ) )
	{
		return $str;
	}

	if ( $DatabaseType === 'mysql' )
	{
		return $db_connection ? mysqli_real_escape_string( $db_connection, $str ) : addslashes( $str );
	}

	return $db_connection ? pg_escape_string( $db_connection, $str ) : addslashes( $str );
}

/**
 * Escape SQL Identifier (table, column names)
 */
function DBEscapeIdentifier( $identifier )
{
	global $db_connection, $DatabaseType;

	if ( ! isset( $db_connection ) || ! $db_connection )
	{
		$db_connection = db_start( false );
	}

	if ( $DatabaseType === 'mysql' )
	{
		return '`' . str_replace( '`', '``', $identifier ) . '`';
	}

	// Lowercase identifier for PostgreSQL compatibility
	$identifier_lower = strtolower( $identifier );

	return $db_connection ? pg_escape_identifier( $db_connection, $identifier_lower ) : '"' . str_replace( '"', '""', $identifier_lower ) . '"';
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
 * Return SQL query results as a comma-separated list
 */
function DBSQLCommaSeparatedResult( $sql, $delimiter = ',' )
{
	$result = DBQuery( $sql );

	$list = [];

	if ( $result )
	{
		while ( $row = db_fetch_row( $result ) )
		{
			$val = reset( $row );
			if ( $val !== false && $val !== null && $val !== '' )
			{
				$list[] = is_numeric( $val ) ? $val : "'" . DBEscapeString( $val ) . "'";
			}
		}
	}

	if ( empty( $list ) )
	{
		return '0';
	}

	return implode( $delimiter, $list );
}

/**
 * SQL CASE statement helper
 */
function db_case( $when_array, $else = "''" )
{
	$sql = "CASE ";
	foreach ( $when_array as $when => $then )
	{
		$sql .= "WHEN " . $when . " THEN " . $then . " ";
	}
	if ( $else !== '' )
	{
		$sql .= "ELSE " . $else . " ";
	}
	$sql .= "END";
	return $sql;
}

/**
 * SQL CONCAT helper
 */
function db_concat( $array )
{
	global $DatabaseType;
	if ( $DatabaseType === 'mysql' )
	{
		return "CONCAT(" . implode( ", ", $array ) . ")";
	}
	return implode( " || ", $array );
}

/**
 * Sequence next value
 */
function db_seq_nextval( $seqname )
{
	return "nextval('" . $seqname . "')";
}

function DBSeqNextval( $seqname )
{
	return db_seq_nextval( $seqname );
}

/**
 * Last Insert ID
 */
function DBLastInsertID()
{
	global $db_connection, $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return mysqli_insert_id( $db_connection );
	}

	$res = db_query( "SELECT LASTVAL() AS id" );
	$row = db_fetch_row( $res );
	return $row['ID'] ?? 0;
}

/**
 * Transactions
 */
function db_trans_start( $connection = null )
{
	global $db_connection;
	$conn = $connection ?: $db_connection ?: db_start();
	db_query( "BEGIN" );
}

function db_trans_commit( $connection = null )
{
	db_query( "COMMIT" );
}

function db_trans_rollback( $connection = null )
{
	db_query( "ROLLBACK" );
}

/**
 * Error display helper
 */
function db_show_error( $sql, $msg, $error = '' )
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

<?php
require_once 'env.migration.php';

//db config
$db['hostname'] = PHP_MIGRATION_DB_HOST;
$db['username'] = PHP_MIGRATION_DB_USERNAME;
$db['password'] = PHP_MIGRATION_DB_PASSWORD;
$db['database'] = PHP_MIGRATION_DB_NAME;

abstract class CI_DB_driver {

    /**
     * Data Source Name / Connect string
     *
     * @var	string
     */
    public $dsn;

    /**
     * Username
     *
     * @var	string
     */
    public $username;

    /**
     * Password
     *
     * @var	string
     */
    public $password;

    /**
     * Hostname
     *
     * @var	string
     */
    public $hostname;

    /**
     * Database name
     *
     * @var	string
     */
    public $database;

    /**
     * Database driver
     *
     * @var	string
     */
    public $dbdriver		= 'mysqli';

    /**
     * Sub-driver
     *
     * @used-by	CI_DB_pdo_driver
     * @var	string
     */
    public $subdriver;

    /**
     * Table prefix
     *
     * @var	string
     */
    public $dbprefix		= '';

    /**
     * Character set
     *
     * @var	string
     */
    public $char_set		= 'utf8';

    /**
     * Collation
     *
     * @var	string
     */
    public $dbcollat		= 'utf8_general_ci';

    /**
     * Encryption flag/data
     *
     * @var	mixed
     */
    public $encrypt			= FALSE;

    /**
     * Swap Prefix
     *
     * @var	string
     */
    public $swap_pre		= '';

    /**
     * Database port
     *
     * @var	int
     */
    public $port			= '';

    /**
     * Persistent connection flag
     *
     * @var	bool
     */
    public $pconnect		= FALSE;

    /**
     * Connection ID
     *
     * @var	object|resource
     */
    public $conn_id			= FALSE;

    /**
     * Result ID
     *
     * @var	object|resource
     */
    public $result_id		= FALSE;

    /**
     * Debug flag
     *
     * Whether to display error messages.
     *
     * @var	bool
     */
    public $db_debug		= FALSE;

    /**
     * Benchmark time
     *
     * @var	int
     */
    public $benchmark		= 0;

    /**
     * Executed queries count
     *
     * @var	int
     */
    public $query_count		= 0;

    /**
     * Bind marker
     *
     * Character used to identify values in a prepared statement.
     *
     * @var	string
     */
    public $bind_marker		= '?';

    /**
     * Save queries flag
     *
     * Whether to keep an in-memory history of queries for debugging purposes.
     *
     * @var	bool
     */
    public $save_queries		= TRUE;

    /**
     * Queries list
     *
     * @see	CI_DB_driver::$save_queries
     * @var	string[]
     */
    public $queries			= array();

    /**
     * Query times
     *
     * A list of times that queries took to execute.
     *
     * @var	array
     */
    public $query_times		= array();

    /**
     * Data cache
     *
     * An internal generic value cache.
     *
     * @var	array
     */
    public $data_cache		= array();

    /**
     * Transaction enabled flag
     *
     * @var	bool
     */
    public $trans_enabled		= TRUE;

    /**
     * Strict transaction mode flag
     *
     * @var	bool
     */
    public $trans_strict		= TRUE;

    /**
     * Transaction depth level
     *
     * @var	int
     */
    protected $_trans_depth		= 0;

    /**
     * Transaction status flag
     *
     * Used with transactions to determine if a rollback should occur.
     *
     * @var	bool
     */
    protected $_trans_status	= TRUE;

    /**
     * Transaction failure flag
     *
     * Used with transactions to determine if a transaction has failed.
     *
     * @var	bool
     */
    protected $_trans_failure	= FALSE;

    /**
     * Cache On flag
     *
     * @var	bool
     */
    public $cache_on		= FALSE;

    /**
     * Cache directory path
     *
     * @var	bool
     */
    public $cachedir		= '';

    /**
     * Cache auto-delete flag
     *
     * @var	bool
     */
    public $cache_autodel		= FALSE;

    /**
     * DB Cache object
     *
     * @see	CI_DB_cache
     * @var	object
     */
    public $CACHE;

    /**
     * Protect identifiers flag
     *
     * @var	bool
     */
    protected $_protect_identifiers		= TRUE;

    /**
     * List of reserved identifiers
     *
     * Identifiers that must NOT be escaped.
     *
     * @var	string[]
     */
    protected $_reserved_identifiers	= array('*');

    /**
     * Identifier escape character
     *
     * @var	string
     */
    protected $_escape_char = '"';

    /**
     * ESCAPE statement string
     *
     * @var	string
     */
    protected $_like_escape_str = " ESCAPE '%s' ";

    /**
     * ESCAPE character
     *
     * @var	string
     */
    protected $_like_escape_chr = '!';

    /**
     * ORDER BY random keyword
     *
     * @var	array
     */
    protected $_random_keyword = array('RAND()', 'RAND(%d)');

    /**
     * COUNT string
     *
     * @used-by	CI_DB_driver::count_all()
     * @used-by	CI_DB_query_builder::count_all_results()
     *
     * @var	string
     */
    protected $_count_string = 'SELECT COUNT(*) AS ';

    // --------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param	array	$params
     * @return	void
     */
    public function __construct($params)
    {
        if (is_array($params))
        {
            foreach ($params as $key => $val)
            {
                $this->$key = $val;
            }
        }

        $this->db_connect();
//        echo ('Database Driver Class Initialized');
    }

    // --------------------------------------------------------------------

    /**
     * Initialize Database Settings
     *
     * @return	bool
     */
    public function initialize()
    {
        /* If an established connection is available, then there's
         * no need to connect and select the database.
         *
         * Depending on the database driver, conn_id can be either
         * boolean TRUE, a resource or an object.
         */
        if ($this->conn_id)
        {
            return TRUE;
        }

        // ----------------------------------------------------------------

        // Connect to the database and set the connection ID
        $this->conn_id = $this->db_connect($this->pconnect);

        // No connection resource? Check if there is a failover else throw an error
        if ( ! $this->conn_id)
        {
            // Check if there is a failover set
            if ( ! empty($this->failover) && is_array($this->failover))
            {
                // Go over all the failovers
                foreach ($this->failover as $failover)
                {
                    // Replace the current settings with those of the failover
                    foreach ($failover as $key => $val)
                    {
                        $this->$key = $val;
                    }

                    // Try to connect
                    $this->conn_id = $this->db_connect($this->pconnect);

                    // If a connection is made break the foreach loop
                    if ($this->conn_id)
                    {
                        break;
                    }
                }
            }

            // We still don't have a connection?
            if ( ! $this->conn_id)
            {
                echo ('Unable to connect to the database');

                if ($this->db_debug)
                {
                    $this->display_error('db_unable_to_connect');
                }

                return FALSE;
            }
        }

        // Now we set the character set and that's all
        return $this->db_set_charset($this->char_set);
    }

    // --------------------------------------------------------------------

    /**
     * DB connect
     *
     * This is just a dummy method that all drivers will override.
     *
     * @return	mixed
     */
    public function db_connect()
    {
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Persistent database connection
     *
     * @return	mixed
     */
    public function db_pconnect()
    {
        return $this->db_connect(TRUE);
    }

    // --------------------------------------------------------------------

    /**
     * Reconnect
     *
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout.
     *
     * This is just a dummy method to allow drivers without such
     * functionality to not declare it, while others will override it.
     *
     * @return	void
     */
    public function reconnect()
    {
    }

    // --------------------------------------------------------------------

    /**
     * Select database
     *
     * This is just a dummy method to allow drivers without such
     * functionality to not declare it, while others will override it.
     *
     * @return	bool
     */
    public function db_select()
    {
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Last error
     *
     * @return	array
     */
    public function error()
    {
        return array('code' => NULL, 'message' => NULL);
    }

    // --------------------------------------------------------------------

    /**
     * Set client character set
     *
     * @param	string
     * @return	bool
     */
    public function db_set_charset($charset)
    {
        if (method_exists($this, '_db_set_charset') && ! $this->_db_set_charset($charset))
        {
            echo ('Unable to set database connection charset: '.$charset);

            if ($this->db_debug)
            {
                $this->display_error('db_unable_to_set_charset', $charset);
            }

            return FALSE;
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * The name of the platform in use (mysql, mssql, etc...)
     *
     * @return	string
     */
    public function platform()
    {
        return $this->dbdriver;
    }

    // --------------------------------------------------------------------

    /**
     * Database version number
     *
     * Returns a string containing the version of the database being used.
     * Most drivers will override this method.
     *
     * @return	string
     */
    public function version()
    {
        if (isset($this->data_cache['version']))
        {
            return $this->data_cache['version'];
        }

        if (FALSE === ($sql = $this->_version()))
        {
            return ($this->db_debug) ? $this->display_error('db_unsupported_function') : FALSE;
        }

        $query = $this->query($sql)->row();
        return $this->data_cache['version'] = $query->ver;
    }

    // --------------------------------------------------------------------

    /**
     * Version number query string
     *
     * @return	string
     */
    protected function _version()
    {
        return 'SELECT VERSION() AS ver';
    }

    // --------------------------------------------------------------------

    /**
     * Execute the query
     *
     * Accepts an SQL string as input and returns a result object upon
     * successful execution of a "read" type query. Returns boolean TRUE
     * upon successful execution of a "write" type query. Returns boolean
     * FALSE upon failure, and if the $db_debug variable is set to TRUE
     * will raise an error.
     *
     * @param	string	$sql
     * @param	array	$binds = FALSE		An array of binding data
     * @param	bool	$return_object = NULL
     * @return	mixed
     */
    public function query($sql, $binds = FALSE, $return_object = NULL)
    {
        if ($sql === '')
        {
            echo ('Invalid query: '.$sql);
            return ($this->db_debug) ? $this->display_error('db_invalid_query') : FALSE;
        }
        elseif ( ! is_bool($return_object))
        {
            $return_object = ! $this->is_write_type($sql);
        }

        // Verify table prefix and replace if necessary
        if ($this->dbprefix !== '' && $this->swap_pre !== '' && $this->dbprefix !== $this->swap_pre)
        {
            $sql = preg_replace('/(\W)'.$this->swap_pre.'(\S+?)/', '\\1'.$this->dbprefix.'\\2', $sql);
        }

        // Compile binds if needed
        if ($binds !== FALSE)
        {
            $sql = $this->compile_binds($sql, $binds);
        }

        // Is query caching enabled? If the query is a "read type"
        // we will load the caching class and return the previously
        // cached query if it exists
        if ($this->cache_on === TRUE && $return_object === TRUE && $this->_cache_init())
        {
            $this->load_rdriver();
            if (FALSE !== ($cache = $this->CACHE->read($sql)))
            {
                return $cache;
            }
        }

        // Save the query for debugging
        if ($this->save_queries === TRUE)
        {
            $this->queries[] = $sql;
        }

        // Start the Query Timer
        $time_start = microtime(TRUE);

        // Run the Query
        if (FALSE === ($this->result_id = $this->simple_query($sql)))
        {
            if ($this->save_queries === TRUE)
            {
                $this->query_times[] = 0;
            }

            // This will trigger a rollback if transactions are being used
            if ($this->_trans_depth !== 0)
            {
                $this->_trans_status = FALSE;
            }

            // Grab the error now, as we might run some additional queries before displaying the error
            $error = $this->error();

            // Log errors
            echo ('Query error: '.$error['message'].' - Invalid query: '.$sql);

            if ($this->db_debug)
            {
                // We call this function in order to roll-back queries
                // if transactions are enabled. If we don't call this here
                // the error message will trigger an exit, causing the
                // transactions to remain in limbo.
                while ($this->_trans_depth !== 0)
                {
                    $trans_depth = $this->_trans_depth;
                    $this->trans_complete();
                    if ($trans_depth === $this->_trans_depth)
                    {
                        echo ('Database: Failure during an automated transaction commit/rollback!');
                        break;
                    }
                }

                // Display errors
                return $this->display_error(array('Error Number: '.$error['code'], $error['message'], $sql));
            }

            return FALSE;
        }

        // Stop and aggregate the query time results
        $time_end = microtime(TRUE);
        $this->benchmark += $time_end - $time_start;

        if ($this->save_queries === TRUE)
        {
            $this->query_times[] = $time_end - $time_start;
        }

        // Increment the query counter
        $this->query_count++;

        // Will we have a result object instantiated? If not - we'll simply return TRUE
        if ($return_object !== TRUE)
        {
            // If caching is enabled we'll auto-cleanup any existing files related to this particular URI
            if ($this->cache_on === TRUE && $this->cache_autodel === TRUE && $this->_cache_init())
            {
                $this->CACHE->delete();
            }

            return TRUE;
        }

        // Load and instantiate the result driver
        $driver		= $this->load_rdriver();
        $RES		= new $driver($this);

        // Is query caching enabled? If so, we'll serialize the
        // result object and save it to a cache file.
        if ($this->cache_on === TRUE && $this->_cache_init())
        {
            // We'll create a new instance of the result object
            // only without the platform specific driver since
            // we can't use it with cached data (the query result
            // resource ID won't be any good once we've cached the
            // result object, so we'll have to compile the data
            // and save it)
            $CR = new CI_DB_result($this);
            $CR->result_object	= $RES->result_object();
            $CR->result_array	= $RES->result_array();
            $CR->num_rows		= $RES->num_rows();

            // Reset these since cached objects can not utilize resource IDs.
            $CR->conn_id		= NULL;
            $CR->result_id		= NULL;

            $this->CACHE->write($sql, $CR);
        }

        return $RES;
    }

    // --------------------------------------------------------------------

    /**
     * Load the result drivers
     *
     * @return	string	the name of the result class
     */
    public function load_rdriver()
    {
        $driver = 'CI_DB_'.$this->dbdriver.'_result';

        if ( ! class_exists($driver, FALSE))
        {
            require_once('DB_result.php');
            require_once($this->dbdriver.'_result.php');
        }

        return $driver;
    }

    // --------------------------------------------------------------------

    /**
     * Simple Query
     * This is a simplified version of the query() function. Internally
     * we only use it when running transaction commands since they do
     * not require all the features of the main query() function.
     *
     * @param	string	the sql query
     * @return	mixed
     */
    public function simple_query($sql)
    {
        if ( ! $this->conn_id)
        {
            if ( ! $this->initialize())
            {
                return FALSE;
            }
        }

        return $this->_execute($sql);
    }

    // --------------------------------------------------------------------

    /**
     * Disable Transactions
     * This permits transactions to be disabled at run-time.
     *
     * @return	void
     */
    public function trans_off()
    {
        $this->trans_enabled = FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Enable/disable Transaction Strict Mode
     *
     * When strict mode is enabled, if you are running multiple groups of
     * transactions, if one group fails all subsequent groups will be
     * rolled back.
     *
     * If strict mode is disabled, each group is treated autonomously,
     * meaning a failure of one group will not affect any others
     *
     * @param	bool	$mode = TRUE
     * @return	void
     */
    public function trans_strict($mode = TRUE)
    {
        $this->trans_strict = is_bool($mode) ? $mode : TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Start Transaction
     *
     * @param	bool	$test_mode = FALSE
     * @return	bool
     */
    public function trans_start($test_mode = FALSE)
    {
        if ( ! $this->trans_enabled)
        {
            return FALSE;
        }

        return $this->trans_begin($test_mode);
    }

    // --------------------------------------------------------------------

    /**
     * Complete Transaction
     *
     * @return	bool
     */
    public function trans_complete()
    {
        if ( ! $this->trans_enabled)
        {
            return FALSE;
        }

        // The query() function will set this flag to FALSE in the event that a query failed
        if ($this->_trans_status === FALSE OR $this->_trans_failure === TRUE)
        {
            $this->trans_rollback();

            // If we are NOT running in strict mode, we will reset
            // the _trans_status flag so that subsequent groups of
            // transactions will be permitted.
            if ($this->trans_strict === FALSE)
            {
                $this->_trans_status = TRUE;
            }

            echo ('DB Transaction Failure');
            return FALSE;
        }

        return $this->trans_commit();
    }

    // --------------------------------------------------------------------

    /**
     * Lets you retrieve the transaction flag to determine if it has failed
     *
     * @return	bool
     */
    public function trans_status()
    {
        return $this->_trans_status;
    }

    // --------------------------------------------------------------------

    /**
     * Begin Transaction
     *
     * @param	bool	$test_mode
     * @return	bool
     */
    public function trans_begin($test_mode = FALSE)
    {
        if ( ! $this->trans_enabled)
        {
            return FALSE;
        }
        // When transactions are nested we only begin/commit/rollback the outermost ones
        elseif ($this->_trans_depth > 0)
        {
            $this->_trans_depth++;
            return TRUE;
        }

        // Reset the transaction failure flag.
        // If the $test_mode flag is set to TRUE transactions will be rolled back
        // even if the queries produce a successful result.
        $this->_trans_failure = ($test_mode === TRUE);

        if ($this->_trans_begin())
        {
            $this->_trans_status = TRUE;
            $this->_trans_depth++;
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Commit Transaction
     *
     * @return	bool
     */
    public function trans_commit()
    {
        if ( ! $this->trans_enabled OR $this->_trans_depth === 0)
        {
            return FALSE;
        }
        // When transactions are nested we only begin/commit/rollback the outermost ones
        elseif ($this->_trans_depth > 1 OR $this->_trans_commit())
        {
            $this->_trans_depth--;
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Rollback Transaction
     *
     * @return	bool
     */
    public function trans_rollback()
    {
        if ( ! $this->trans_enabled OR $this->_trans_depth === 0)
        {
            return FALSE;
        }
        // When transactions are nested we only begin/commit/rollback the outermost ones
        elseif ($this->_trans_depth > 1 OR $this->_trans_rollback())
        {
            $this->_trans_depth--;
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Compile Bindings
     *
     * @param	string	the sql statement
     * @param	array	an array of bind data
     * @return	string
     */
    public function compile_binds($sql, $binds)
    {
        if (empty($this->bind_marker) OR strpos($sql, $this->bind_marker) === FALSE)
        {
            return $sql;
        }
        elseif ( ! is_array($binds))
        {
            $binds = array($binds);
            $bind_count = 1;
        }
        else
        {
            // Make sure we're using numeric keys
            $binds = array_values($binds);
            $bind_count = count($binds);
        }

        // We'll need the marker length later
        $ml = strlen($this->bind_marker);

        // Make sure not to replace a chunk inside a string that happens to match the bind marker
        if ($c = preg_match_all("/'[^']*'|\"[^\"]*\"/i", $sql, $matches))
        {
            $c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i',
                str_replace($matches[0],
                    str_replace($this->bind_marker, str_repeat(' ', $ml), $matches[0]),
                    $sql, $c),
                $matches, PREG_OFFSET_CAPTURE);

            // Bind values' count must match the count of markers in the query
            if ($bind_count !== $c)
            {
                return $sql;
            }
        }
        elseif (($c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i', $sql, $matches, PREG_OFFSET_CAPTURE)) !== $bind_count)
        {
            return $sql;
        }

        do
        {
            $c--;
            $escaped_value = $this->escape($binds[$c]);
            if (is_array($escaped_value))
            {
                $escaped_value = '('.implode(',', $escaped_value).')';
            }
            $sql = substr_replace($sql, $escaped_value, $matches[0][$c][1], $ml);
        }
        while ($c !== 0);

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Determines if a query is a "write" type.
     *
     * @param	string	An SQL query string
     * @return	bool
     */
    public function is_write_type($sql)
    {
        return (bool) preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD|COPY|ALTER|RENAME|GRANT|REVOKE|LOCK|UNLOCK|REINDEX|MERGE)\s/i', $sql);
    }

    // --------------------------------------------------------------------

    /**
     * Calculate the aggregate query elapsed time
     *
     * @param	int	The number of decimal places
     * @return	string
     */
    public function elapsed_time($decimals = 6)
    {
        return number_format($this->benchmark, $decimals);
    }

    // --------------------------------------------------------------------

    /**
     * Returns the total number of queries
     *
     * @return	int
     */
    public function total_queries()
    {
        return $this->query_count;
    }

    // --------------------------------------------------------------------

    /**
     * Returns the last query that was executed
     *
     * @return	string
     */
    public function last_query()
    {
        return end($this->queries);
    }

    // --------------------------------------------------------------------

    /**
     * "Smart" Escape String
     *
     * Escapes data based on type
     * Sets boolean and null types
     *
     * @param	string
     * @return	mixed
     */
    public function escape($str)
    {
        if (is_array($str))
        {
            $str = array_map(array(&$this, 'escape'), $str);
            return $str;
        }
        elseif (is_string($str) OR (is_object($str) && method_exists($str, '__toString')))
        {
            return "'".$this->escape_str($str)."'";
        }
        elseif (is_bool($str))
        {
            return ($str === FALSE) ? 0 : 1;
        }
        elseif ($str === NULL)
        {
            return 'NULL';
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Escape String
     *
     * @param	string|string[]	$str	Input string
     * @param	bool	$like	Whether or not the string will be used in a LIKE condition
     * @return	string
     */
    public function escape_str($str, $like = FALSE)
    {
        if (is_array($str))
        {
            foreach ($str as $key => $val)
            {
                $str[$key] = $this->escape_str($val, $like);
            }

            return $str;
        }

        $str = $this->_escape_str($str);

        // escape LIKE condition wildcards
        if ($like === TRUE)
        {
            return str_replace(
                array($this->_like_escape_chr, '%', '_'),
                array($this->_like_escape_chr.$this->_like_escape_chr, $this->_like_escape_chr.'%', $this->_like_escape_chr.'_'),
                $str
            );
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Escape LIKE String
     *
     * Calls the individual driver for platform
     * specific escaping for LIKE conditions
     *
     * @param	string|string[]
     * @return	mixed
     */
    public function escape_like_str($str)
    {
        return $this->escape_str($str, TRUE);
    }

    // --------------------------------------------------------------------

    /**
     * Platform-dependent string escape
     *
     * @param	string
     * @return	string
     */
    protected function _escape_str($str)
    {
        return str_replace("'", "''", remove_invisible_characters($str, FALSE));
    }

    // --------------------------------------------------------------------

    /**
     * Primary
     *
     * Retrieves the primary key. It assumes that the row in the first
     * position is the primary key
     *
     * @param	string	$table	Table name
     * @return	string
     */
    public function primary($table)
    {
        $fields = $this->list_fields($table);
        return is_array($fields) ? current($fields) : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * "Count All" query
     *
     * Generates a platform-specific query string that counts all records in
     * the specified database
     *
     * @param	string
     * @return	int
     */
    public function count_all($table = '')
    {
        if ($table === '')
        {
            return 0;
        }

        $query = $this->query($this->_count_string.$this->escape_identifiers('numrows').' FROM '.$this->protect_identifiers($table, TRUE, NULL, FALSE));
        if ($query->num_rows() === 0)
        {
            return 0;
        }

        $query = $query->row();
        $this->_reset_select();
        return (int) $query->numrows;
    }

    // --------------------------------------------------------------------

    /**
     * Returns an array of table names
     *
     * @param	string	$constrain_by_prefix = FALSE
     * @return	array
     */
    public function list_tables($constrain_by_prefix = FALSE)
    {
        // Is there a cached result?
        if (isset($this->data_cache['table_names']))
        {
            return $this->data_cache['table_names'];
        }

        if (FALSE === ($sql = $this->_list_tables($constrain_by_prefix)))
        {
            return ($this->db_debug) ? $this->display_error('db_unsupported_function') : FALSE;
        }

        $this->data_cache['table_names'] = array();
        $query = $this->query($sql);

        foreach ($query->result_array() as $row)
        {
            // Do we know from which column to get the table name?
            if ( ! isset($key))
            {
                if (isset($row['table_name']))
                {
                    $key = 'table_name';
                }
                elseif (isset($row['TABLE_NAME']))
                {
                    $key = 'TABLE_NAME';
                }
                else
                {
                    /* We have no other choice but to just get the first element's key.
                     * Due to array_shift() accepting its argument by reference, if
                     * E_STRICT is on, this would trigger a warning. So we'll have to
                     * assign it first.
                     */
                    $key = array_keys($row);
                    $key = array_shift($key);
                }
            }

            $this->data_cache['table_names'][] = $row[$key];
        }

        return $this->data_cache['table_names'];
    }

    // --------------------------------------------------------------------

    /**
     * Determine if a particular table exists
     *
     * @param	string	$table_name
     * @return	bool
     */
    public function table_exists($table_name)
    {
        return in_array($this->protect_identifiers($table_name, TRUE, FALSE, FALSE), $this->list_tables());
    }

    // --------------------------------------------------------------------

    /**
     * Fetch Field Names
     *
     * @param	string	$table	Table name
     * @return	array
     */
    public function list_fields($table)
    {
        // Is there a cached result?
        if (isset($this->data_cache['field_names'][$table]))
        {
            return $this->data_cache['field_names'][$table];
        }

        if (FALSE === ($sql = $this->_list_columns($table)))
        {
            return ($this->db_debug) ? $this->display_error('db_unsupported_function') : FALSE;
        }

        $query = $this->query($sql);
        $this->data_cache['field_names'][$table] = array();

        foreach ($query->result_array() as $row)
        {
            // Do we know from where to get the column's name?
            if ( ! isset($key))
            {
                if (isset($row['column_name']))
                {
                    $key = 'column_name';
                }
                elseif (isset($row['COLUMN_NAME']))
                {
                    $key = 'COLUMN_NAME';
                }
                else
                {
                    // We have no other choice but to just get the first element's key.
                    $key = key($row);
                }
            }

            $this->data_cache['field_names'][$table][] = $row[$key];
        }

        return $this->data_cache['field_names'][$table];
    }

    // --------------------------------------------------------------------

    /**
     * Determine if a particular field exists
     *
     * @param	string
     * @param	string
     * @return	bool
     */
    public function field_exists($field_name, $table_name)
    {
        return in_array($field_name, $this->list_fields($table_name));
    }

    // --------------------------------------------------------------------

    /**
     * Returns an object with field data
     *
     * @param	string	$table	the table name
     * @return	array
     */
    public function field_data($table)
    {
        $query = $this->query($this->_field_data($this->protect_identifiers($table, TRUE, NULL, FALSE)));
        return ($query) ? $query->field_data() : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @param	mixed
     * @return	mixed
     */
    public function escape_identifiers($item)
    {
        if ($this->_escape_char === '' OR empty($item) OR in_array($item, $this->_reserved_identifiers))
        {
            return $item;
        }
        elseif (is_array($item))
        {
            foreach ($item as $key => $value)
            {
                $item[$key] = $this->escape_identifiers($value);
            }

            return $item;
        }
        // Avoid breaking functions and literal values inside queries
        elseif (ctype_digit($item) OR $item[0] === "'" OR ($this->_escape_char !== '"' && $item[0] === '"') OR strpos($item, '(') !== FALSE)
        {
            return $item;
        }

        static $preg_ec = array();

        if (empty($preg_ec))
        {
            if (is_array($this->_escape_char))
            {
                $preg_ec = array(
                    preg_quote($this->_escape_char[0], '/'),
                    preg_quote($this->_escape_char[1], '/'),
                    $this->_escape_char[0],
                    $this->_escape_char[1]
                );
            }
            else
            {
                $preg_ec[0] = $preg_ec[1] = preg_quote($this->_escape_char, '/');
                $preg_ec[2] = $preg_ec[3] = $this->_escape_char;
            }
        }

        foreach ($this->_reserved_identifiers as $id)
        {
            if (strpos($item, '.'.$id) !== FALSE)
            {
                return preg_replace('/'.$preg_ec[0].'?([^'.$preg_ec[1].'\.]+)'.$preg_ec[1].'?\./i', $preg_ec[2].'$1'.$preg_ec[3].'.', $item);
            }
        }

        return preg_replace('/'.$preg_ec[0].'?([^'.$preg_ec[1].'\.]+)'.$preg_ec[1].'?(\.)?/i', $preg_ec[2].'$1'.$preg_ec[3].'$2', $item);
    }

    // --------------------------------------------------------------------

    /**
     * Generate an insert string
     *
     * @param	string	the table upon which the query will be performed
     * @param	array	an associative array data of key/values
     * @return	string
     */
    public function insert_string($table, $data)
    {
        $fields = $values = array();

        foreach ($data as $key => $val)
        {
            $fields[] = $this->escape_identifiers($key);
            $values[] = $this->escape($val);
        }

        return $this->_insert($this->protect_identifiers($table, TRUE, NULL, FALSE), $fields, $values);
    }

    // --------------------------------------------------------------------

    /**
     * Insert statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @param	string	the table name
     * @param	array	the insert keys
     * @param	array	the insert values
     * @return	string
     */
    protected function _insert($table, $keys, $values)
    {
        return 'INSERT INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';
    }

    // --------------------------------------------------------------------

    /**
     * Generate an update string
     *
     * @param	string	the table upon which the query will be performed
     * @param	array	an associative array data of key/values
     * @param	mixed	the "where" statement
     * @return	string
     */
    public function update_string($table, $data, $where)
    {
        if (empty($where))
        {
            return FALSE;
        }

        $this->where($where);

        $fields = array();
        foreach ($data as $key => $val)
        {
            $fields[$this->protect_identifiers($key)] = $this->escape($val);
        }

        $sql = $this->_update($this->protect_identifiers($table, TRUE, NULL, FALSE), $fields);
        $this->_reset_write();
        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @param	string	the table name
     * @param	array	the update data
     * @return	string
     */
    protected function _update($table, $values)
    {
        foreach ($values as $key => $val)
        {
            $valstr[] = $key.' = '.$val;
        }

        return 'UPDATE '.$table.' SET '.implode(', ', $valstr)
            .$this->_compile_wh('qb_where')
            .$this->_compile_order_by()
            .($this->qb_limit ? ' LIMIT '.$this->qb_limit : '');
    }

    // --------------------------------------------------------------------

    /**
     * Tests whether the string has an SQL operator
     *
     * @param	string
     * @return	bool
     */
    protected function _has_operator($str)
    {
        return (bool) preg_match('/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i', trim($str));
    }

    // --------------------------------------------------------------------

    /**
     * Returns the SQL string operator
     *
     * @param	string
     * @return	string
     */
    protected function _get_operator($str)
    {
        static $_operators;

        if (empty($_operators))
        {
            $_les = ($this->_like_escape_str !== '')
                ? '\s+'.preg_quote(trim(sprintf($this->_like_escape_str, $this->_like_escape_chr)), '/')
                : '';
            $_operators = array(
                '\s*(?:<|>|!)?=\s*',             // =, <=, >=, !=
                '\s*<>?\s*',                     // <, <>
                '\s*>\s*',                       // >
                '\s+IS NULL',                    // IS NULL
                '\s+IS NOT NULL',                // IS NOT NULL
                '\s+EXISTS\s*\(.*\)',        // EXISTS(sql)
                '\s+NOT EXISTS\s*\(.*\)',    // NOT EXISTS(sql)
                '\s+BETWEEN\s+',                 // BETWEEN value AND value
                '\s+IN\s*\(.*\)',            // IN(list)
                '\s+NOT IN\s*\(.*\)',        // NOT IN (list)
                '\s+LIKE\s+\S.*('.$_les.')?',    // LIKE 'expr'[ ESCAPE '%s']
                '\s+NOT LIKE\s+\S.*('.$_les.')?' // NOT LIKE 'expr'[ ESCAPE '%s']
            );

        }

        return preg_match('/'.implode('|', $_operators).'/i', $str, $match)
            ? $match[0] : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Enables a native PHP function to be run, using a platform agnostic wrapper.
     *
     * @param	string	$function	Function name
     * @return	mixed
     */
    public function call_function($function)
    {
        $driver = ($this->dbdriver === 'postgre') ? 'pg_' : $this->dbdriver.'_';

        if (FALSE === strpos($driver, $function))
        {
            $function = $driver.$function;
        }

        if ( ! function_exists($function))
        {
            return ($this->db_debug) ? $this->display_error('db_unsupported_function') : FALSE;
        }

        return (func_num_args() > 1)
            ? call_user_func_array($function, array_slice(func_get_args(), 1))
            : call_user_func($function);
    }

    // --------------------------------------------------------------------

    /**
     * Set Cache Directory Path
     *
     * @param	string	the path to the cache directory
     * @return	void
     */
    public function cache_set_path($path = '')
    {
        $this->cachedir = $path;
    }

    // --------------------------------------------------------------------

    /**
     * Enable Query Caching
     *
     * @return	bool	cache_on value
     */
    public function cache_on()
    {
        return $this->cache_on = TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Disable Query Caching
     *
     * @return	bool	cache_on value
     */
    public function cache_off()
    {
        return $this->cache_on = FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Delete the cache files associated with a particular URI
     *
     * @param	string	$segment_one = ''
     * @param	string	$segment_two = ''
     * @return	bool
     */
    public function cache_delete($segment_one = '', $segment_two = '')
    {
        return $this->_cache_init()
            ? $this->CACHE->delete($segment_one, $segment_two)
            : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Delete All cache files
     *
     * @return	bool
     */
    public function cache_delete_all()
    {
        return $this->_cache_init()
            ? $this->CACHE->delete_all()
            : FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Initialize the Cache Class
     *
     * @return	bool
     */
    protected function _cache_init()
    {
        if ( ! class_exists('CI_DB_Cache', FALSE))
        {
            require_once(BASEPATH.'database/DB_cache.php');
        }
        elseif (is_object($this->CACHE))
        {
            return TRUE;
        }

        $this->CACHE = new CI_DB_Cache($this); // pass db object to support multiple db connections and returned db objects
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Close DB Connection
     *
     * @return	void
     */
    public function close()
    {
        if ($this->conn_id)
        {
            $this->_close();
            $this->conn_id = FALSE;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Close DB Connection
     *
     * This method would be overridden by most of the drivers.
     *
     * @return	void
     */
    protected function _close()
    {
        $this->conn_id = FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Display an error message
     *
     * @param	string	the error message
     * @param	string	any "swap" values
     * @param	bool	whether to localize the message
     * @return	string	sends the application/views/errors/error_db.php template
     */
    public function display_error($error = '', $swap = '', $native = FALSE)
    {
        $LANG =& load_class('Lang', 'core');
        $LANG->load('db');

        $heading = $LANG->line('db_error_heading');

        if ($native === TRUE)
        {
            $message = (array) $error;
        }
        else
        {
            $message = is_array($error) ? $error : array(str_replace('%s', $swap, $LANG->line($error)));
        }

        // Find the most likely culprit of the error by going through
        // the backtrace until the source file is no longer in the
        // database folder.
        $trace = debug_backtrace();
        foreach ($trace as $call)
        {
            if (isset($call['file'], $call['class']))
            {
                // We'll need this on Windows, as APPPATH and BASEPATH will always use forward slashes
                if (DIRECTORY_SEPARATOR !== '/')
                {
                    $call['file'] = str_replace('\\', '/', $call['file']);
                }

                if (strpos($call['file'], BASEPATH.'database') === FALSE && strpos($call['class'], 'Loader') === FALSE)
                {
                    // Found it - use a relative path for safety
                    $message[] = 'Filename: '.str_replace(array(APPPATH, BASEPATH), '', $call['file']);
                    $message[] = 'Line Number: '.$call['line'];
                    break;
                }
            }
        }

        $error =& load_class('Exceptions', 'core');
        echo $error->show_error($heading, $message, 'error_db');
        exit(8); // EXIT_DATABASE
    }

    // --------------------------------------------------------------------

    /**
     * Protect Identifiers
     *
     * This function is used extensively by the Query Builder class, and by
     * a couple functions in this class.
     * It takes a column or table name (optionally with an alias) and inserts
     * the table prefix onto it. Some logic is necessary in order to deal with
     * column names that include the path. Consider a query like this:
     *
     * SELECT hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @param	string
     * @param	bool
     * @param	mixed
     * @param	bool
     * @return	string
     */
    public function protect_identifiers($item, $prefix_single = FALSE, $protect_identifiers = NULL, $field_exists = TRUE)
    {
        if ( ! is_bool($protect_identifiers))
        {
            $protect_identifiers = $this->_protect_identifiers;
        }

        if (is_array($item))
        {
            $escaped_array = array();
            foreach ($item as $k => $v)
            {
                $escaped_array[$this->protect_identifiers($k)] = $this->protect_identifiers($v, $prefix_single, $protect_identifiers, $field_exists);
            }

            return $escaped_array;
        }

        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix. There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        //
        // Added exception for single quotes as well, we don't want to alter
        // literal strings. -- Narf
        if (strcspn($item, "()'") !== strlen($item))
        {
            return $item;
        }

        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace('/\s+/', ' ', trim($item));

        // If the item has an alias declaration we remove it and set it aside.
        // Note: strripos() is used in order to support spaces in table names
        if ($offset = strripos($item, ' AS '))
        {
            $alias = ($protect_identifiers)
                ? substr($item, $offset, 4).$this->escape_identifiers(substr($item, $offset + 4))
                : substr($item, $offset);
            $item = substr($item, 0, $offset);
        }
        elseif ($offset = strrpos($item, ' '))
        {
            $alias = ($protect_identifiers)
                ? ' '.$this->escape_identifiers(substr($item, $offset + 1))
                : substr($item, $offset);
            $item = substr($item, 0, $offset);
        }
        else
        {
            $alias = '';
        }

        // Break the string apart if it contains periods, then insert the table prefix
        // in the correct location, assuming the period doesn't indicate that we're dealing
        // with an alias. While we're at it, we will escape the components
        if (strpos($item, '.') !== FALSE)
        {
            $parts = explode('.', $item);

            // Does the first segment of the exploded item match
            // one of the aliases previously identified? If so,
            // we have nothing more to do other than escape the item
            //
            // NOTE: The ! empty() condition prevents this method
            //       from breaking when QB isn't enabled.
            if ( ! empty($this->qb_aliased_tables) && in_array($parts[0], $this->qb_aliased_tables))
            {
                if ($protect_identifiers === TRUE)
                {
                    foreach ($parts as $key => $val)
                    {
                        if ( ! in_array($val, $this->_reserved_identifiers))
                        {
                            $parts[$key] = $this->escape_identifiers($val);
                        }
                    }

                    $item = implode('.', $parts);
                }

                return $item.$alias;
            }

            // Is there a table prefix defined in the config file? If not, no need to do anything
            if ($this->dbprefix !== '')
            {
                // We now add the table prefix based on some logic.
                // Do we have 4 segments (hostname.database.table.column)?
                // If so, we add the table prefix to the column name in the 3rd segment.
                if (isset($parts[3]))
                {
                    $i = 2;
                }
                // Do we have 3 segments (database.table.column)?
                // If so, we add the table prefix to the column name in 2nd position
                elseif (isset($parts[2]))
                {
                    $i = 1;
                }
                // Do we have 2 segments (table.column)?
                // If so, we add the table prefix to the column name in 1st segment
                else
                {
                    $i = 0;
                }

                // This flag is set when the supplied $item does not contain a field name.
                // This can happen when this function is being called from a JOIN.
                if ($field_exists === FALSE)
                {
                    $i++;
                }

                // Verify table prefix and replace if necessary
                if ($this->swap_pre !== '' && strpos($parts[$i], $this->swap_pre) === 0)
                {
                    $parts[$i] = preg_replace('/^'.$this->swap_pre.'(\S+?)/', $this->dbprefix.'\\1', $parts[$i]);
                }
                // We only add the table prefix if it does not already exist
                elseif (strpos($parts[$i], $this->dbprefix) !== 0)
                {
                    $parts[$i] = $this->dbprefix.$parts[$i];
                }

                // Put the parts back together
                $item = implode('.', $parts);
            }

            if ($protect_identifiers === TRUE)
            {
                $item = $this->escape_identifiers($item);
            }

            return $item.$alias;
        }

        // Is there a table prefix? If not, no need to insert it
        if ($this->dbprefix !== '')
        {
            // Verify table prefix and replace if necessary
            if ($this->swap_pre !== '' && strpos($item, $this->swap_pre) === 0)
            {
                $item = preg_replace('/^'.$this->swap_pre.'(\S+?)/', $this->dbprefix.'\\1', $item);
            }
            // Do we prefix an item with no segments?
            elseif ($prefix_single === TRUE && strpos($item, $this->dbprefix) !== 0)
            {
                $item = $this->dbprefix.$item;
            }
        }

        if ($protect_identifiers === TRUE && ! in_array($item, $this->_reserved_identifiers))
        {
            $item = $this->escape_identifiers($item);
        }

        return $item.$alias;
    }

    // --------------------------------------------------------------------

    /**
     * Dummy method that allows Query Builder class to be disabled
     * and keep count_all() working.
     *
     * @return	void
     */
    protected function _reset_select()
    {
    }

}

class CI_MYSQL_DB_driver extends CI_DB_driver {

    /**
     * Database driver
     *
     * @var	string
     */
    public $dbdriver = 'mysqli';

    /**
     * Compression flag
     *
     * @var	bool
     */
    public $compress = FALSE;

    /**
     * DELETE hack flag
     *
     * Whether to use the MySQL "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     *
     * @var	bool
     */
    public $delete_hack = TRUE;

    /**
     * Strict ON flag
     *
     * Whether we're running in strict SQL mode.
     *
     * @var	bool
     */
    public $stricton;

    // --------------------------------------------------------------------

    /**
     * Identifier escape character
     *
     * @var	string
     */
    protected $_escape_char = '`';

    // --------------------------------------------------------------------

    /**
     * MySQLi object
     *
     * Has to be preserved without being assigned to $conn_id.
     *
     * @var	MySQLi
     */
    protected $_mysqli;

    // --------------------------------------------------------------------

    /**
     * Database connection
     *
     * @param	bool	$persistent
     * @return	object
     */
    public function db_connect($persistent = FALSE)
    {
        // Do we have a socket path?
        if ($this->hostname[0] === '/')
        {
            $hostname = NULL;
            $port = NULL;
            $socket = $this->hostname;
        }
        else
        {
            $hostname = ($persistent === TRUE)
                ? 'p:'.$this->hostname : $this->hostname;
            $port = empty($this->port) ? NULL : $this->port;
            $socket = NULL;
        }

        $client_flags = ($this->compress === TRUE) ? MYSQLI_CLIENT_COMPRESS : 0;
        $this->_mysqli = mysqli_init();

        $this->_mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        if (isset($this->stricton))
        {
            if ($this->stricton)
            {
                $this->_mysqli->options(MYSQLI_INIT_COMMAND, 'SET SESSION sql_mode = CONCAT(@@sql_mode, ",", "STRICT_ALL_TABLES")');
            }
            else
            {
                $this->_mysqli->options(MYSQLI_INIT_COMMAND,
                    'SET SESSION sql_mode =
					REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
					@@sql_mode,
					"STRICT_ALL_TABLES,", ""),
					",STRICT_ALL_TABLES", ""),
					"STRICT_ALL_TABLES", ""),
					"STRICT_TRANS_TABLES,", ""),
					",STRICT_TRANS_TABLES", ""),
					"STRICT_TRANS_TABLES", "")'
                );
            }
        }

        if (is_array($this->encrypt))
        {
            $ssl = array();
            empty($this->encrypt['ssl_key'])    OR $ssl['key']    = $this->encrypt['ssl_key'];
            empty($this->encrypt['ssl_cert'])   OR $ssl['cert']   = $this->encrypt['ssl_cert'];
            empty($this->encrypt['ssl_ca'])     OR $ssl['ca']     = $this->encrypt['ssl_ca'];
            empty($this->encrypt['ssl_capath']) OR $ssl['capath'] = $this->encrypt['ssl_capath'];
            empty($this->encrypt['ssl_cipher']) OR $ssl['cipher'] = $this->encrypt['ssl_cipher'];

            if ( ! empty($ssl))
            {
                if (isset($this->encrypt['ssl_verify']))
                {
                    if ($this->encrypt['ssl_verify'])
                    {
                        defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT') && $this->_mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, TRUE);
                    }
                    // Apparently (when it exists), setting MYSQLI_OPT_SSL_VERIFY_SERVER_CERT
                    // to FALSE didn't do anything, so PHP 5.6.16 introduced yet another
                    // constant ...
                    //
                    // https://secure.php.net/ChangeLog-5.php#5.6.16
                    // https://bugs.php.net/bug.php?id=68344
                    elseif (defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT'))
                    {
                        $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
                    }
                }

                $client_flags |= MYSQLI_CLIENT_SSL;
                $this->_mysqli->ssl_set(
                    isset($ssl['key'])    ? $ssl['key']    : NULL,
                    isset($ssl['cert'])   ? $ssl['cert']   : NULL,
                    isset($ssl['ca'])     ? $ssl['ca']     : NULL,
                    isset($ssl['capath']) ? $ssl['capath'] : NULL,
                    isset($ssl['cipher']) ? $ssl['cipher'] : NULL
                );
            }
        }

        if ($this->_mysqli->real_connect($hostname, $this->username, $this->password, $this->database, $port, $socket, $client_flags))
        {
            // Prior to version 5.7.3, MySQL silently downgrades to an unencrypted connection if SSL setup fails
            if (
                ($client_flags & MYSQLI_CLIENT_SSL)
                && version_compare($this->_mysqli->client_info, '5.7.3', '<=')
                && empty($this->_mysqli->query("SHOW STATUS LIKE 'ssl_cipher'")->fetch_object()->Value)
            )
            {
                $this->_mysqli->close();
                $message = 'MySQLi was configured for an SSL connection, but got an unencrypted connection instead!';
                echo ($message);
                return ($this->db_debug) ? $this->display_error($message, '', TRUE) : FALSE;
            }

            return $this->_mysqli;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Reconnect
     *
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout
     *
     * @return	void
     */
    public function reconnect()
    {
        if ($this->conn_id !== FALSE && $this->conn_id->ping() === FALSE)
        {
            $this->conn_id = FALSE;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Select the database
     *
     * @param	string	$database
     * @return	bool
     */
    public function db_select($database = '')
    {
        if ($database === '')
        {
            $database = $this->database;
        }

        if ($this->conn_id->select_db($database))
        {
            $this->database = $database;
            $this->data_cache = array();
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Set client character set
     *
     * @param	string	$charset
     * @return	bool
     */
    protected function _db_set_charset($charset)
    {
        return $this->conn_id->set_charset($charset);
    }

    // --------------------------------------------------------------------

    /**
     * Database version number
     *
     * @return	string
     */
    public function version()
    {
        if (isset($this->data_cache['version']))
        {
            return $this->data_cache['version'];
        }

        return $this->data_cache['version'] = $this->conn_id->server_info;
    }

    // --------------------------------------------------------------------

    /**
     * Execute the query
     *
     * @param	string	$sql	an SQL query
     * @return	mixed
     */
    protected function _execute($sql)
    {
        return $this->conn_id->query($this->_prep_query($sql));
    }

    // --------------------------------------------------------------------

    /**
     * Prep the query
     *
     * If needed, each database adapter can prep the query string
     *
     * @param	string	$sql	an SQL query
     * @return	string
     */
    protected function _prep_query($sql)
    {
        // mysqli_affected_rows() returns 0 for "DELETE FROM TABLE" queries. This hack
        // modifies the query so that it a proper number of affected rows is returned.
        if ($this->delete_hack === TRUE && preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql))
        {
            return trim($sql).' WHERE 1=1';
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Begin Transaction
     *
     * @return	bool
     */
    protected function _trans_begin()
    {
        $this->conn_id->autocommit(FALSE);
        return is_php('5.5')
            ? $this->conn_id->begin_transaction()
            : $this->simple_query('START TRANSACTION'); // can also be BEGIN or BEGIN WORK
    }

    // --------------------------------------------------------------------

    /**
     * Commit Transaction
     *
     * @return	bool
     */
    protected function _trans_commit()
    {
        if ($this->conn_id->commit())
        {
            $this->conn_id->autocommit(TRUE);
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Rollback Transaction
     *
     * @return	bool
     */
    protected function _trans_rollback()
    {
        if ($this->conn_id->rollback())
        {
            $this->conn_id->autocommit(TRUE);
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Platform-dependent string escape
     *
     * @param	string
     * @return	string
     */
    protected function _escape_str($str)
    {
        return $this->conn_id->real_escape_string($str);
    }

    // --------------------------------------------------------------------

    /**
     * Affected Rows
     *
     * @return	int
     */
    public function affected_rows()
    {
        return $this->conn_id->affected_rows;
    }

    // --------------------------------------------------------------------

    /**
     * Insert ID
     *
     * @return	int
     */
    public function insert_id()
    {
        return $this->conn_id->insert_id;
    }

    // --------------------------------------------------------------------

    /**
     * List table query
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param	bool	$prefix_limit
     * @return	string
     */
    protected function _list_tables($prefix_limit = FALSE)
    {
        $sql = 'SHOW TABLES FROM '.$this->escape_identifiers($this->database);

        if ($prefix_limit !== FALSE && $this->dbprefix !== '')
        {
            return $sql." LIKE '".$this->escape_like_str($this->dbprefix)."%'";
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Show column query
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @param	string	$table
     * @return	string
     */
    protected function _list_columns($table = '')
    {
        return 'SHOW COLUMNS FROM '.$this->protect_identifiers($table, TRUE, NULL, FALSE);
    }

    // --------------------------------------------------------------------

    /**
     * Returns an object with field data
     *
     * @param	string	$table
     * @return	array
     */
    public function field_data($table)
    {
        if (($query = $this->query('SHOW COLUMNS FROM '.$this->protect_identifiers($table, TRUE, NULL, FALSE))) === FALSE)
        {
            return FALSE;
        }
        $query = $query->result_object();

        $retval = array();
        for ($i = 0, $c = count($query); $i < $c; $i++)
        {
            $retval[$i]			= new stdClass();
            $retval[$i]->name		= $query[$i]->Field;

            sscanf($query[$i]->Type, '%[a-z](%d)',
                $retval[$i]->type,
                $retval[$i]->max_length
            );

            $retval[$i]->default		= $query[$i]->Default;
            $retval[$i]->primary_key	= (int) ($query[$i]->Key === 'PRI');
        }

        return $retval;
    }

    // --------------------------------------------------------------------

    /**
     * Error
     *
     * Returns an array containing code and message of the last
     * database error that has occurred.
     *
     * @return	array
     */
    public function error()
    {
        if ( ! empty($this->_mysqli->connect_errno))
        {
            return array(
                'code'    => $this->_mysqli->connect_errno,
                'message' => $this->_mysqli->connect_error
            );
        }

        return array('code' => $this->conn_id->errno, 'message' => $this->conn_id->error);
    }

    // --------------------------------------------------------------------

    /**
     * FROM tables
     *
     * Groups tables in FROM clauses if needed, so there is no confusion
     * about operator precedence.
     *
     * @return	string
     */
    protected function _from_tables()
    {
        if ( ! empty($this->qb_join) && count($this->qb_from) > 1)
        {
            return '('.implode(', ', $this->qb_from).')';
        }

        return implode(', ', $this->qb_from);
    }

    // --------------------------------------------------------------------

    /**
     * Close DB Connection
     *
     * @return	void
     */
    protected function _close()
    {
        $this->conn_id->close();
    }

}

class CI_DB_mysqli_result extends CI_DB_result {

    /**
     * Number of rows in the result set
     *
     * @return	int
     */
    public function num_rows()
    {
        return is_int($this->num_rows)
            ? $this->num_rows
            : $this->num_rows = $this->result_id->num_rows;
    }

    // --------------------------------------------------------------------

    /**
     * Number of fields in the result set
     *
     * @return	int
     */
    public function num_fields()
    {
        return $this->result_id->field_count;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch Field Names
     *
     * Generates an array of column names
     *
     * @return	array
     */
    public function list_fields()
    {
        $field_names = array();
        $this->result_id->field_seek(0);
        while ($field = $this->result_id->fetch_field())
        {
            $field_names[] = $field->name;
        }

        return $field_names;
    }

    // --------------------------------------------------------------------

    /**
     * Field data
     *
     * Generates an array of objects containing field meta-data
     *
     * @return	array
     */
    public function field_data()
    {
        $retval = array();
        $field_data = $this->result_id->fetch_fields();
        for ($i = 0, $c = count($field_data); $i < $c; $i++)
        {
            $retval[$i]			= new stdClass();
            $retval[$i]->name		= $field_data[$i]->name;
            $retval[$i]->type		= static::_get_field_type($field_data[$i]->type);
            $retval[$i]->max_length		= $field_data[$i]->max_length;
            $retval[$i]->primary_key	= (int) ($field_data[$i]->flags & MYSQLI_PRI_KEY_FLAG);
            $retval[$i]->default		= $field_data[$i]->def;
        }

        return $retval;
    }

    // --------------------------------------------------------------------

    /**
     * Get field type
     *
     * Extracts field type info from the bitflags returned by
     * mysqli_result::fetch_fields()
     *
     * @used-by	CI_DB_mysqli_result::field_data()
     * @param	int	$flags
     * @return	string
     */
    private static function _get_field_type($flags)
    {
        static $map;
        isset($map) OR $map = array(
            MYSQLI_TYPE_DECIMAL     => 'decimal',
            MYSQLI_TYPE_BIT         => 'bit',
            MYSQLI_TYPE_TINY        => 'tinyint',
            MYSQLI_TYPE_SHORT       => 'smallint',
            MYSQLI_TYPE_INT24       => 'mediumint',
            MYSQLI_TYPE_LONG        => 'int',
            MYSQLI_TYPE_LONGLONG    => 'bigint',
            MYSQLI_TYPE_FLOAT       => 'float',
            MYSQLI_TYPE_DOUBLE      => 'double',
            MYSQLI_TYPE_TIMESTAMP   => 'timestamp',
            MYSQLI_TYPE_DATE        => 'date',
            MYSQLI_TYPE_TIME        => 'time',
            MYSQLI_TYPE_DATETIME    => 'datetime',
            MYSQLI_TYPE_YEAR        => 'year',
            MYSQLI_TYPE_NEWDATE     => 'date',
            MYSQLI_TYPE_INTERVAL    => 'interval',
            MYSQLI_TYPE_ENUM        => 'enum',
            MYSQLI_TYPE_SET         => 'set',
            MYSQLI_TYPE_TINY_BLOB   => 'tinyblob',
            MYSQLI_TYPE_MEDIUM_BLOB => 'mediumblob',
            MYSQLI_TYPE_BLOB        => 'blob',
            MYSQLI_TYPE_LONG_BLOB   => 'longblob',
            MYSQLI_TYPE_STRING      => 'char',
            MYSQLI_TYPE_VAR_STRING  => 'varchar',
            MYSQLI_TYPE_GEOMETRY    => 'geometry'
        );

        foreach ($map as $flag => $name)
        {
            if ($flags & $flag)
            {
                return $name;
            }
        }

        return $flags;
    }

    // --------------------------------------------------------------------

    /**
     * Free the result
     *
     * @return	void
     */
    public function free_result()
    {
        if (is_object($this->result_id))
        {
            $this->result_id->free();
            $this->result_id = FALSE;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Data Seek
     *
     * Moves the internal pointer to the desired offset. We call
     * this internally before fetching results to make sure the
     * result set starts at zero.
     *
     * @param	int	$n
     * @return	bool
     */
    public function data_seek($n = 0)
    {
        return $this->result_id->data_seek($n);
    }

    // --------------------------------------------------------------------

    /**
     * Result - associative array
     *
     * Returns the result set as an array
     *
     * @return	array
     */
    protected function _fetch_assoc()
    {
        return $this->result_id->fetch_assoc();
    }

    // --------------------------------------------------------------------

    /**
     * Result - object
     *
     * Returns the result set as an object
     *
     * @param	string	$class_name
     * @return	object
     */
    protected function _fetch_object($class_name = 'stdClass')
    {
        return $this->result_id->fetch_object($class_name);
    }

}

class CI_DB_result {

    /**
     * Connection ID
     *
     * @var	resource|object
     */
    public $conn_id;

    /**
     * Result ID
     *
     * @var	resource|object
     */
    public $result_id;

    /**
     * Result Array
     *
     * @var	array[]
     */
    public $result_array			= array();

    /**
     * Result Object
     *
     * @var	object[]
     */
    public $result_object			= array();

    /**
     * Custom Result Object
     *
     * @var	object[]
     */
    public $custom_result_object		= array();

    /**
     * Current Row index
     *
     * @var	int
     */
    public $current_row			= 0;

    /**
     * Number of rows
     *
     * @var	int
     */
    public $num_rows;

    /**
     * Row data
     *
     * @var	array
     */
    public $row_data;

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @param	object	$driver_object
     * @return	void
     */
    public function __construct(&$driver_object)
    {
        $this->conn_id = $driver_object->conn_id;
        $this->result_id = $driver_object->result_id;
    }

    // --------------------------------------------------------------------

    /**
     * Number of rows in the result set
     *
     * @return	int
     */
    public function num_rows()
    {
        if (is_int($this->num_rows))
        {
            return $this->num_rows;
        }
        elseif (count($this->result_array) > 0)
        {
            return $this->num_rows = count($this->result_array);
        }
        elseif (count($this->result_object) > 0)
        {
            return $this->num_rows = count($this->result_object);
        }

        return $this->num_rows = count($this->result_array());
    }

    // --------------------------------------------------------------------

    /**
     * Query result. Acts as a wrapper function for the following functions.
     *
     * @param	string	$type	'object', 'array' or a custom class name
     * @return	array
     */
    public function result($type = 'object')
    {
        if ($type === 'array')
        {
            return $this->result_array();
        }
        elseif ($type === 'object')
        {
            return $this->result_object();
        }
        else
        {
            return $this->custom_result_object($type);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Custom query result.
     *
     * @param	string	$class_name
     * @return	array
     */
    public function custom_result_object($class_name)
    {
        if (isset($this->custom_result_object[$class_name]))
        {
            return $this->custom_result_object[$class_name];
        }
        elseif ( ! $this->result_id OR $this->num_rows === 0)
        {
            return array();
        }

        // Don't fetch the result set again if we already have it
        $_data = NULL;
        if (($c = count($this->result_array)) > 0)
        {
            $_data = 'result_array';
        }
        elseif (($c = count($this->result_object)) > 0)
        {
            $_data = 'result_object';
        }

        if ($_data !== NULL)
        {
            for ($i = 0; $i < $c; $i++)
            {
                $this->custom_result_object[$class_name][$i] = new $class_name();

                foreach ($this->{$_data}[$i] as $key => $value)
                {
                    $this->custom_result_object[$class_name][$i]->$key = $value;
                }
            }

            return $this->custom_result_object[$class_name];
        }

        is_null($this->row_data) OR $this->data_seek(0);
        $this->custom_result_object[$class_name] = array();

        while ($row = $this->_fetch_object($class_name))
        {
            $this->custom_result_object[$class_name][] = $row;
        }

        return $this->custom_result_object[$class_name];
    }

    // --------------------------------------------------------------------

    /**
     * Query result. "object" version.
     *
     * @return	array
     */
    public function result_object()
    {
        if (count($this->result_object) > 0)
        {
            return $this->result_object;
        }

        // In the event that query caching is on, the result_id variable
        // will not be a valid resource so we'll simply return an empty
        // array.
        if ( ! $this->result_id OR $this->num_rows === 0)
        {
            return array();
        }

        if (($c = count($this->result_array)) > 0)
        {
            for ($i = 0; $i < $c; $i++)
            {
                $this->result_object[$i] = (object) $this->result_array[$i];
            }

            return $this->result_object;
        }

        is_null($this->row_data) OR $this->data_seek(0);
        while ($row = $this->_fetch_object())
        {
            $this->result_object[] = $row;
        }

        return $this->result_object;
    }

    // --------------------------------------------------------------------

    /**
     * Query result. "array" version.
     *
     * @return	array
     */
    public function result_array()
    {
        if (count($this->result_array) > 0)
        {
            return $this->result_array;
        }

        // In the event that query caching is on, the result_id variable
        // will not be a valid resource so we'll simply return an empty
        // array.
        if ( ! $this->result_id OR $this->num_rows === 0)
        {
            return array();
        }

        if (($c = count($this->result_object)) > 0)
        {
            for ($i = 0; $i < $c; $i++)
            {
                $this->result_array[$i] = (array) $this->result_object[$i];
            }

            return $this->result_array;
        }

        is_null($this->row_data) OR $this->data_seek(0);
        while ($row = $this->_fetch_assoc())
        {
            $this->result_array[] = $row;
        }

        return $this->result_array;
    }

    // --------------------------------------------------------------------

    /**
     * Row
     *
     * A wrapper method.
     *
     * @param	mixed	$n
     * @param	string	$type	'object' or 'array'
     * @return	mixed
     */
    public function row($n = 0, $type = 'object')
    {
        if ( ! is_numeric($n))
        {
            // We cache the row data for subsequent uses
            is_array($this->row_data) OR $this->row_data = $this->row_array(0);

            // array_key_exists() instead of isset() to allow for NULL values
            if (empty($this->row_data) OR ! array_key_exists($n, $this->row_data))
            {
                return NULL;
            }

            return $this->row_data[$n];
        }

        if ($type === 'object') return $this->row_object($n);
        elseif ($type === 'array') return $this->row_array($n);
        else return $this->custom_row_object($n, $type);
    }

    // --------------------------------------------------------------------

    /**
     * Assigns an item into a particular column slot
     *
     * @param	mixed	$key
     * @param	mixed	$value
     * @return	void
     */
    public function set_row($key, $value = NULL)
    {
        // We cache the row data for subsequent uses
        if ( ! is_array($this->row_data))
        {
            $this->row_data = $this->row_array(0);
        }

        if (is_array($key))
        {
            foreach ($key as $k => $v)
            {
                $this->row_data[$k] = $v;
            }
            return;
        }

        if ($key !== '' && $value !== NULL)
        {
            $this->row_data[$key] = $value;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Returns a single result row - custom object version
     *
     * @param	int	$n
     * @param	string	$type
     * @return	object
     */
    public function custom_row_object($n, $type)
    {
        isset($this->custom_result_object[$type]) OR $this->custom_result_object($type);

        if (count($this->custom_result_object[$type]) === 0)
        {
            return NULL;
        }

        if ($n !== $this->current_row && isset($this->custom_result_object[$type][$n]))
        {
            $this->current_row = $n;
        }

        return $this->custom_result_object[$type][$this->current_row];
    }

    // --------------------------------------------------------------------

    /**
     * Returns a single result row - object version
     *
     * @param	int	$n
     * @return	object
     */
    public function row_object($n = 0)
    {
        $result = $this->result_object();
        if (count($result) === 0)
        {
            return NULL;
        }

        if ($n !== $this->current_row && isset($result[$n]))
        {
            $this->current_row = $n;
        }

        return $result[$this->current_row];
    }

    // --------------------------------------------------------------------

    /**
     * Returns a single result row - array version
     *
     * @param	int	$n
     * @return	array
     */
    public function row_array($n = 0)
    {
        $result = $this->result_array();
        if (count($result) === 0)
        {
            return NULL;
        }

        if ($n !== $this->current_row && isset($result[$n]))
        {
            $this->current_row = $n;
        }

        return $result[$this->current_row];
    }

    // --------------------------------------------------------------------

    /**
     * Returns the "first" row
     *
     * @param	string	$type
     * @return	mixed
     */
    public function first_row($type = 'object')
    {
        $result = $this->result($type);
        return (count($result) === 0) ? NULL : $result[0];
    }

    // --------------------------------------------------------------------

    /**
     * Returns the "last" row
     *
     * @param	string	$type
     * @return	mixed
     */
    public function last_row($type = 'object')
    {
        $result = $this->result($type);
        return (count($result) === 0) ? NULL : $result[count($result) - 1];
    }

    // --------------------------------------------------------------------

    /**
     * Returns the "next" row
     *
     * @param	string	$type
     * @return	mixed
     */
    public function next_row($type = 'object')
    {
        $result = $this->result($type);
        if (count($result) === 0)
        {
            return NULL;
        }

        return isset($result[$this->current_row + 1])
            ? $result[++$this->current_row]
            : NULL;
    }

    // --------------------------------------------------------------------

    /**
     * Returns the "previous" row
     *
     * @param	string	$type
     * @return	mixed
     */
    public function previous_row($type = 'object')
    {
        $result = $this->result($type);
        if (count($result) === 0)
        {
            return NULL;
        }

        if (isset($result[$this->current_row - 1]))
        {
            --$this->current_row;
        }
        return $result[$this->current_row];
    }

    // --------------------------------------------------------------------

    /**
     * Returns an unbuffered row and move pointer to next row
     *
     * @param	string	$type	'array', 'object' or a custom class name
     * @return	mixed
     */
    public function unbuffered_row($type = 'object')
    {
        if ($type === 'array')
        {
            return $this->_fetch_assoc();
        }
        elseif ($type === 'object')
        {
            return $this->_fetch_object();
        }

        return $this->_fetch_object($type);
    }

    // --------------------------------------------------------------------

    /**
     * The following methods are normally overloaded by the identically named
     * methods in the platform-specific driver -- except when query caching
     * is used. When caching is enabled we do not load the other driver.
     * These functions are primarily here to prevent undefined function errors
     * when a cached result object is in use. They are not otherwise fully
     * operational due to the unavailability of the database resource IDs with
     * cached results.
     */

    // --------------------------------------------------------------------

    /**
     * Number of fields in the result set
     *
     * Overridden by driver result classes.
     *
     * @return	int
     */
    public function num_fields()
    {
        return 0;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch Field Names
     *
     * Generates an array of column names.
     *
     * Overridden by driver result classes.
     *
     * @return	array
     */
    public function list_fields()
    {
        return array();
    }

    // --------------------------------------------------------------------

    /**
     * Field data
     *
     * Generates an array of objects containing field meta-data.
     *
     * Overridden by driver result classes.
     *
     * @return	array
     */
    public function field_data()
    {
        return array();
    }

    // --------------------------------------------------------------------

    /**
     * Free the result
     *
     * Overridden by driver result classes.
     *
     * @return	void
     */
    public function free_result()
    {
        $this->result_id = FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Data Seek
     *
     * Moves the internal pointer to the desired offset. We call
     * this internally before fetching results to make sure the
     * result set starts at zero.
     *
     * Overridden by driver result classes.
     *
     * @param	int	$n
     * @return	bool
     */
    public function data_seek($n = 0)
    {
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Result - associative array
     *
     * Returns the result set as an array.
     *
     * Overridden by driver result classes.
     *
     * @return	array
     */
    protected function _fetch_assoc()
    {
        return array();
    }

    // --------------------------------------------------------------------

    /**
     * Result - object
     *
     * Returns the result set as an object.
     *
     * Overridden by driver result classes.
     *
     * @param	string	$class_name
     * @return	object
     */
    protected function _fetch_object($class_name = 'stdClass')
    {
        return new $class_name();
    }

}

Class migration extends CI_MYSQL_DB_driver
{
    private $migration_table = PHP_MIGRATION_TABLE_NAME;
    private $_migration_path = PHP_MIGRATION_PATH;
    private $_migration_regex = '/^\d{14}_(\w+)$/';

    private $all_migrations = array();

    function setup()
    {
        if ( ! $this->table_exists($this->migration_table))
        {
            $query = "CREATE TABLE IF NOT EXISTS $this->migration_table (
              `id` int(10) UNSIGNED NOT NULL,
              `version` bigint(20) UNSIGNED NOT NULL
            ) ENGINE=InnoDB;";
            $this->query($query);

            $query = "ALTER TABLE $this->migration_table
              ADD PRIMARY KEY (`id`);";
            $this->query($query);

            $query = "ALTER TABLE $this->migration_table
              MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;";
            $this->query($query);
        }

    if (!file_exists($this->_migration_path)) {
        mkdir($this->_migration_path, 0777, true);
    }

        echo "\nMigration initialised\n";
    }

    public function run(){

        $rows = $this->query("select version from $this->migration_table order by 'version'")->result_array();

        $finished_migration_versions = array_map (function($value){
            return $value['version'];
        } , $rows);

        $this->all_migrations = ($this->find_migrations());
        $all_migration_versions = array_keys($this->all_migrations);

        if (!empty($all_migration_versions))
        {
            $pending_migrations = array_diff($all_migration_versions, $finished_migration_versions);

            foreach ($pending_migrations as $version)
            {
                if(!$this->migrate($version)){
                    echo "\nFailed to migrate version: " . $version;
                } else {
                    //update custom migration table
//                    $this->insert($this->migration_version_table, array('version' => $version));
                    $this->query("INSERT INTO `migration_versions`(`id`, `version`) VALUES (null, $version)");
                }
            }
        }

        echo "\nMigrated to latest \n";
    }

    public function create($filename)
    {
        $migration_folder = $this->_migration_path;
        $name = date('YmdHis', time()) . '_' . $filename . ".php";

        $file = fopen($migration_folder . $name, "a") or die("Unable to open file!");

        $body = $this->migration_boiler_plate($filename);

        fwrite($file, $body);

        fclose($file);

        echo "Created " . $name . " \n";
    }

    private function migration_boiler_plate($filename)
    {
        $content = '<?php
class Migration_' . ucfirst($filename) . ' extends CI_MYSQL_DB_driver {

    public function up()
    {
        try
        {
            $this->query(\'START TRANSACTION\');

            //Only add one sql statement per query.
            $query = "";

            $result = $this->query($query);

            if ($result)
            {
                $this->query(\'COMMIT\');
                return true;
            }
            else
            {
                $this->query(\'ROLLBACK\');
                return false;
            }
        }
        catch (Exception $exception)
        {
            $this->query(\'ROLLBACK\');
            return false;
        }
    }

    public function down()
    {
    }
}';
        return $content;
    }

    private function migrate($version)
    {
        $file = $this->all_migrations[$version];
        $method = 'up';

        include_once($file);
        $class = 'Migration_'.ucfirst(strtolower($this->_get_migration_name(basename($file, '.php'))));

        // Validate the migration file structure
        if ( ! class_exists($class, FALSE))
        {
            return FALSE;
        }
        elseif ( ! is_callable(array($class, $method)))
        {
            return FALSE;
        }

        $migration = array($class, $method);
        //db config
        $db['hostname'] = PHP_MIGRATION_DB_HOST;
        $db['username'] = PHP_MIGRATION_DB_USERNAME;
        $db['password'] = PHP_MIGRATION_DB_PASSWORD;
        $db['database'] = PHP_MIGRATION_DB_NAME;

        $migration[0] = new $migration[0]($db);
        return call_user_func($migration);
    }

    protected function _get_migration_name($migration)
    {
        $parts = explode('_', $migration);
        array_shift($parts);
        return implode('_', $parts);
    }

    private function find_migrations()
    {
        $migrations = array();

        // Load all *_*.php files in the migrations path
        foreach (glob($this->_migration_path.'*_*.php') as $file)
        {
            $name = basename($file, '.php');

            // Filter out non-migration files
            if (preg_match($this->_migration_regex, $name))
            {
                $number = $this->_get_migration_number($name);

                // There cannot be duplicate migration numbers
                if (isset($migrations[$number]))
                {
                    echo 'Duplicate migration number: ' . $number;
                    exit;
                }

                $migrations[$number] = $file;
            }
        }

        ksort($migrations);
        return $migrations;
    }

    protected function _get_migration_number($migration)
    {
        return sscanf($migration, '%[0-9]+', $number)
            ? $number : '0';
    }
}

$my_db = new migration($db);

//function to call
$function = $argv[1];

if (!empty($argv[2])) $my_db->$function($argv[2]); else $my_db->$function();

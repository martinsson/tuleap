<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stig Bakken <ssb@php.net>                                   |
// |          Tomas V.V.Cox <cox@idecnet.com>                             |
// | Maintainer: Daniel Convissor <danielc@php.net>                       |
// +----------------------------------------------------------------------+
//
// $Id: DB.php,v 1.6 2004/06/21 08:39:37 rurban Exp $
//
// Database independent query interface.


require_once 'PEAR.php';

// {{{ constants
// {{{ error codes

/*
 * The method mapErrorCode in each DB_dbtype implementation maps
 * native error codes to one of these.
 *
 * If you add an error code here, make sure you also add a textual
 * version of it in DB::errorMessage().
 */
define('DB_OK', 1);
define('DB_ERROR', -1);
define('DB_ERROR_SYNTAX', -2);
define('DB_ERROR_CONSTRAINT', -3);
define('DB_ERROR_NOT_FOUND', -4);
define('DB_ERROR_ALREADY_EXISTS', -5);
define('DB_ERROR_UNSUPPORTED', -6);
define('DB_ERROR_MISMATCH', -7);
define('DB_ERROR_INVALID', -8);
define('DB_ERROR_NOT_CAPABLE', -9);
define('DB_ERROR_TRUNCATED', -10);
define('DB_ERROR_INVALID_NUMBER', -11);
define('DB_ERROR_INVALID_DATE', -12);
define('DB_ERROR_DIVZERO', -13);
define('DB_ERROR_NODBSELECTED', -14);
define('DB_ERROR_CANNOT_CREATE', -15);
define('DB_ERROR_CANNOT_DELETE', -16);
define('DB_ERROR_CANNOT_DROP', -17);
define('DB_ERROR_NOSUCHTABLE', -18);
define('DB_ERROR_NOSUCHFIELD', -19);
define('DB_ERROR_NEED_MORE_DATA', -20);
define('DB_ERROR_NOT_LOCKED', -21);
define('DB_ERROR_VALUE_COUNT_ON_ROW', -22);
define('DB_ERROR_INVALID_DSN', -23);
define('DB_ERROR_CONNECT_FAILED', -24);
define('DB_ERROR_EXTENSION_NOT_FOUND', -25);
define('DB_ERROR_ACCESS_VIOLATION', -26);
define('DB_ERROR_NOSUCHDB', -27);
define('DB_ERROR_CONSTRAINT_NOT_NULL', -29);


// }}}
// {{{ prepared statement-related


/*
 * These constants are used when storing information about prepared
 * statements (using the "prepare" method in DB_dbtype).
 *
 * The prepare/execute model in DB is mostly borrowed from the ODBC
 * extension, in a query the "?" character means a scalar parameter.
 * There are two extensions though, a "&" character means an opaque
 * parameter.  An opaque parameter is simply a file name, the real
 * data are in that file (useful for putting uploaded files into your
 * database and such). The "!" char means a parameter that must be
 * left as it is.
 * They modify the quote behavoir:
 * DB_PARAM_SCALAR (?) => 'original string quoted'
 * DB_PARAM_OPAQUE (&) => 'string from file quoted'
 * DB_PARAM_MISC   (!) => original string
 */
define('DB_PARAM_SCALAR', 1);
define('DB_PARAM_OPAQUE', 2);
define('DB_PARAM_MISC', 3);


// }}}
// {{{ binary data-related


/*
 * These constants define different ways of returning binary data
 * from queries.  Again, this model has been borrowed from the ODBC
 * extension.
 *
 * DB_BINMODE_PASSTHRU sends the data directly through to the browser
 * when data is fetched from the database.
 * DB_BINMODE_RETURN lets you return data as usual.
 * DB_BINMODE_CONVERT returns data as well, only it is converted to
 * hex format, for example the string "123" would become "313233".
 */
define('DB_BINMODE_PASSTHRU', 1);
define('DB_BINMODE_RETURN', 2);
define('DB_BINMODE_CONVERT', 3);


// }}}
// {{{ fetch modes


/**
 * This is a special constant that tells DB the user hasn't specified
 * any particular get mode, so the default should be used.
 */
define('DB_FETCHMODE_DEFAULT', 0);

/**
 * Column data indexed by numbers, ordered from 0 and up
 */
define('DB_FETCHMODE_ORDERED', 1);

/**
 * Column data indexed by column names
 */
define('DB_FETCHMODE_ASSOC', 2);

/**
 * Column data as object properties
 */
define('DB_FETCHMODE_OBJECT', 3);

/**
 * For multi-dimensional results: normally the first level of arrays
 * is the row number, and the second level indexed by column number or name.
 * DB_FETCHMODE_FLIPPED switches this order, so the first level of arrays
 * is the column name, and the second level the row number.
 */
define('DB_FETCHMODE_FLIPPED', 4);

/* for compatibility */
define('DB_GETMODE_ORDERED', DB_FETCHMODE_ORDERED);
define('DB_GETMODE_ASSOC', DB_FETCHMODE_ASSOC);
define('DB_GETMODE_FLIPPED', DB_FETCHMODE_FLIPPED);


// }}}
// {{{ autoPrepare()-related

/*
 * Used by autoPrepare()
 */
define('DB_AUTOQUERY_INSERT', 1);
define('DB_AUTOQUERY_UPDATE', 2);


// }}}
// {{{ portability modes


/**
 * Portability: turn off all portability features.
 * @see DB_common::setOption()
 */
define('DB_PORTABILITY_NONE', 0);

/**
 * Portability: convert names of tables and fields to lower case
 * when using the get*(), fetch*() methods.
 * @see DB_common::setOption()
 */
define('DB_PORTABILITY_LOWERCASE', 1);

/**
 * Portability: right trim the data output by get*() and fetch*().
 * @see DB_common::setOption()
 */
define('DB_PORTABILITY_RTRIM', 2);

/**
 * Portability: force reporting the number of rows deleted.
 * @see DB_common::setOption()
 */
define('DB_PORTABILITY_DELETE_COUNT', 4);

/**
 * Portability: enable hack that makes numRows() work in Oracle.
 * @see DB_common::setOption()
 */
define('DB_PORTABILITY_NUMROWS', 8);

/**
 * Portability: makes certain error messages in certain drivers compatible
 * with those from other DBMS's.
 *
 * + mysql, mysqli:  change unique/primary key constraints
 *   DB_ERROR_ALREADY_EXISTS -> DB_ERROR_CONSTRAINT
 *
 * + odbc(access):  MS's ODBC driver reports 'no such field' as code
 *   07001, which means 'too few parameters.'  When this option is on
 *   that code gets mapped to DB_ERROR_NOSUCHFIELD.
 *
 * @see DB_common::setOption()
 */
define('DB_PORTABILITY_ERRORS', 16);

/**
 * Portability: convert null values to empty strings in data output by
 * get*() and fetch*().
 * @see DB_common::setOption()
 */
define('DB_PORTABILITY_NULL_TO_EMPTY', 32);

/**
 * Portability: turn on all portability features.
 * @see DB_common::setOption()
 */
define('DB_PORTABILITY_ALL', 63);

// }}}


// }}}
// {{{ class DB

/**
 * The main "DB" class is simply a container class with some static
 * methods for creating DB objects as well as some utility functions
 * common to all parts of DB.
 *
 * The object model of DB is as follows (indentation means inheritance):
 *
 * DB           The main DB class.  This is simply a utility class
 *              with some "static" methods for creating DB objects as
 *              well as common utility functions for other DB classes.
 *
 * DB_common    The base for each DB implementation.  Provides default
 * |            implementations (in OO lingo virtual methods) for
 * |            the actual DB implementations as well as a bunch of
 * |            query utility functions.
 * |
 * +-DB_mysql   The DB implementation for MySQL.  Inherits DB_common.
 *              When calling DB::factory or DB::connect for MySQL
 *              connections, the object returned is an instance of this
 *              class.
 *
 */
class DB
{
    public static function connect()
    {
        include_once __DIR__ . '/DB/mysql_pdo.php';
        $obj = new DB_mysql_pdo();

        $err = $obj->connect();
        if (DB::isError($err)) {
            return $err;
        }

        return $obj;
    }

    // }}}
    // {{{ apiVersion()

    /**
     * Return the DB API version
     *
     * @return int the DB API version number
     *
     * @access public
     */
    function apiVersion()
    {
        return 2;
    }

    // }}}
    // {{{ isError()

    /**
     * Tell whether a result code from a DB method is an error
     *
     * @param int $value result code
     *
     * @return bool whether $value is an error
     *
     * @access public
     */
    function isError($value)
    {
        return is_a($value, 'DB_Error');
    }

    // }}}
    // {{{ isConnection()

    /**
     * Tell whether a value is a DB connection
     *
     * @param mixed $value value to test
     *
     * @return bool whether $value is a DB connection
     *
     * @access public
     */
    function isConnection($value)
    {
        return (is_object($value) &&
                is_subclass_of($value, 'db_common') &&
                method_exists($value, 'simpleQuery'));
    }

    // }}}
    // {{{ isManip()

    /**
     * Tell whether a query is a data manipulation query (insert,
     * update or delete) or a data definition query (create, drop,
     * alter, grant, revoke).
     *
     * @access public
     *
     * @param string $query the query
     *
     * @return bool whether $query is a data manipulation query
     */
    public static function isManip($query)
    {
        $manips = 'INSERT|UPDATE|DELETE|LOAD DATA|'.'REPLACE|CREATE|DROP|'.
                  'ALTER|GRANT|REVOKE|'.'LOCK|UNLOCK';
        if (preg_match('/^\s*"?('.$manips.')\s+/i', $query)) {
            return true;
        }
        return false;
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Return a textual error message for a DB error code
     *
     * @param int $value error code
     *
     * @return string error message, or false if the error code was
     * not recognized
     */
    function errorMessage($value)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                DB_ERROR                    => 'unknown error',
                DB_ERROR_ALREADY_EXISTS     => 'already exists',
                DB_ERROR_CANNOT_CREATE      => 'can not create',
                DB_ERROR_CANNOT_DELETE      => 'can not delete',
                DB_ERROR_CANNOT_DROP        => 'can not drop',
                DB_ERROR_CONSTRAINT         => 'constraint violation',
                DB_ERROR_CONSTRAINT_NOT_NULL=> 'null value violates not-null constraint',
                DB_ERROR_DIVZERO            => 'division by zero',
                DB_ERROR_INVALID            => 'invalid',
                DB_ERROR_INVALID_DATE       => 'invalid date or time',
                DB_ERROR_INVALID_NUMBER     => 'invalid number',
                DB_ERROR_MISMATCH           => 'mismatch',
                DB_ERROR_NODBSELECTED       => 'no database selected',
                DB_ERROR_NOSUCHFIELD        => 'no such field',
                DB_ERROR_NOSUCHTABLE        => 'no such table',
                DB_ERROR_NOT_CAPABLE        => 'DB backend not capable',
                DB_ERROR_NOT_FOUND          => 'not found',
                DB_ERROR_NOT_LOCKED         => 'not locked',
                DB_ERROR_SYNTAX             => 'syntax error',
                DB_ERROR_UNSUPPORTED        => 'not supported',
                DB_ERROR_VALUE_COUNT_ON_ROW => 'value count on row',
                DB_ERROR_INVALID_DSN        => 'invalid DSN',
                DB_ERROR_CONNECT_FAILED     => 'connect failed',
                DB_OK                       => 'no error',
                DB_ERROR_NEED_MORE_DATA     => 'insufficient data supplied',
                DB_ERROR_EXTENSION_NOT_FOUND=> 'extension not found',
                DB_ERROR_NOSUCHDB           => 'no such database',
                DB_ERROR_ACCESS_VIOLATION   => 'insufficient permissions',
                DB_ERROR_TRUNCATED          => 'truncated'
            );
        }

        if (DB::isError($value)) {
            $value = $value->getCode();
        }

        return isset($errorMessages[$value]) ? $errorMessages[$value] : $errorMessages[DB_ERROR];
    }

    // }}}
    // {{{ assertExtension()

    /**
     * Load a PHP database extension if it is not loaded already.
     *
     * @access public
     *
     * @param string $name the base name of the extension (without the .so or
     *                     .dll suffix)
     *
     * @return bool true if the extension was already or successfully
 * loaded, false if it could not be loaded
     */
    function assertExtension($name)
    {
        return extension_loaded($name);
    }
    // }}}
}

// }}}
// {{{ class DB_Error

/**
 * DB_Error implements a class for reporting portable database error
 * messages.
 *
 */
class DB_Error extends PEAR_Error
{
    // {{{ constructor

    /**
     *
     *
     * @param mixed   $code   DB error code, or string with error message.
     * @param int $mode what "error mode" to operate in
     * @param int $level what error level to use for $mode & PEAR_ERROR_TRIGGER
     * @param mixed   $debuginfo  additional debug info, such as the last query
     *
     * @access public
     *
     * @see PEAR_Error
     */
    function __construct(
        $code = DB_ERROR,
        $mode = PEAR_ERROR_RETURN,
        $level = E_USER_NOTICE,
        $debuginfo = null
    ) {
        if (is_int($code)) {
            parent::__construct('DB Error: ' . DB::errorMessage($code), $code, $mode, $level, $debuginfo);
        } else {
            parent::__construct("DB Error: $code", DB_ERROR, $mode, $level, $debuginfo);
        }
    }
    // }}}
}

// }}}
// {{{ class DB_result

/**
 * This class implements a wrapper for a DB result set.
 * A new instance of this class will be returned by the DB implementation
 * after processing a query that returns data.
 *
 */
class DB_result
{
    // {{{ properties

    var $dbh;
    var $result;
    var $row_counter = null;

    /**
     * for limit queries, the row to start fetching
     * @var int
     */
    var $limit_from  = null;

    /**
     * for limit queries, the number of rows to fetch
     * @var int
     */
    var $limit_count = null;

    // }}}
    // {{{ constructor

    /**
     *
     * @param resource &$dbh   DB object reference
     * @param resource $result  result resource id
     * @param array    $options assoc array with optional result options
     */
    function __construct(&$dbh, $result, $options = array())
    {
        $this->dbh = &$dbh;
        $this->result = $result;
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
        $this->limit_type  = $dbh->features['limit'];
        $this->autofree    = $dbh->options['autofree'];
        $this->fetchmode   = $dbh->fetchmode;
        $this->fetchmode_object_class = $dbh->fetchmode_object_class;
    }

    function setOption($key, $value = null)
    {
        switch ($key) {
            case 'limit_from':
                $this->limit_from = $value;
                break;
            case 'limit_count':
                $this->limit_count = $value;
                break;
        }
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row of data and return it by reference into an array.
     *
     * The type of array returned can be controlled either by setting this
     * method's <var>$fetchmode</var> parameter or by changing the default
     * fetch mode setFetchMode() before calling this method.
     *
     * There are two options for standardizing the information returned
     * from databases, ensuring their values are consistent when changing
     * DBMS's.  These portability options can be turned on when creating a
     * new DB object or by using setOption().
     *
     *   + <samp>DB_PORTABILITY_LOWERCASE</samp>
     *     convert names of fields to lower case
     *
     *   + <samp>DB_PORTABILITY_RTRIM</samp>
     *     right trim the data
     *
     * @param int $fetchmode  how the resulting array should be indexed
     * @param int $rownum     the row number to fetch
     *
     * @return array  a row of data, null on no more rows or PEAR_Error
     *                object on error
     *
     * @see DB_common::setOption(), DB_common::setFetchMode()
     * @access public
     */
    function &fetchRow($fetchmode = DB_FETCHMODE_DEFAULT, $rownum = null)
    {
        if ($fetchmode === DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($fetchmode === DB_FETCHMODE_OBJECT) {
            $fetchmode = DB_FETCHMODE_ASSOC;
            $object_class = $this->fetchmode_object_class;
        }
        if ($this->limit_from !== null) {
            if ($this->row_counter === null) {
                $this->row_counter = $this->limit_from;
                // Skip rows
                if ($this->limit_type == false) {
                    $i = 0;
                    while ($i++ < $this->limit_from) {
                        $this->dbh->fetchInto($this->result, $arr, $fetchmode);
                    }
                }
            }
            if ($this->row_counter >= (
                    $this->limit_from + $this->limit_count)) {
                if ($this->autofree) {
                    $this->free();
                }
                $tmp = null;
                return $tmp;
            }
            if ($this->limit_type == 'emulate') {
                $rownum = $this->row_counter;
            }
            $this->row_counter++;
        }
        $res = $this->dbh->fetchInto($this->result, $arr, $fetchmode, $rownum);
        if ($res === DB_OK) {
            if (isset($object_class)) {
                // default mode specified in DB_common::fetchmode_object_class property
                if ($object_class == 'stdClass') {
                    $arr = (object) $arr;
                } else {
                    $arr = new $object_class($arr);
                }
            }
            return $arr;
        }
        if ($res == null && $this->autofree) {
            $this->free();
        }
        return $res;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row of data into an array which is passed by reference.
     *
     * The type of array returned can be controlled either by setting this
     * method's <var>$fetchmode</var> parameter or by changing the default
     * fetch mode setFetchMode() before calling this method.
     *
     * There are two options for standardizing the information returned
     * from databases, ensuring their values are consistent when changing
     * DBMS's.  These portability options can be turned on when creating a
     * new DB object or by using setOption().
     *
     *   + <samp>DB_PORTABILITY_LOWERCASE</samp>
     *     convert names of fields to lower case
     *
     *   + <samp>DB_PORTABILITY_RTRIM</samp>
     *     right trim the data
     *
     * @param array &$arr       (reference) array where data from the row
     *                          should be placed
     * @param int   $fetchmode  how the resulting array should be indexed
     * @param int   $rownum     the row number to fetch
     *
     * @return mixed  DB_OK on success, null on no more rows or
     *                a DB_Error object on error
     *
     * @see DB_common::setOption(), DB_common::setFetchMode()
     * @access public
     */
    function fetchInto(&$arr, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum = null)
    {
        if ($fetchmode === DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($fetchmode === DB_FETCHMODE_OBJECT) {
            $fetchmode = DB_FETCHMODE_ASSOC;
            $object_class = $this->fetchmode_object_class;
        }
        if ($this->limit_from !== null) {
            if ($this->row_counter === null) {
                $this->row_counter = $this->limit_from;
                // Skip rows
                if ($this->limit_type == false) {
                    $i = 0;
                    while ($i++ < $this->limit_from) {
                        $this->dbh->fetchInto($this->result, $arr, $fetchmode);
                    }
                }
            }
            if ($this->row_counter >= (
                    $this->limit_from + $this->limit_count)) {
                if ($this->autofree) {
                    $this->free();
                }
                return null;
            }
            if ($this->limit_type == 'emulate') {
                $rownum = $this->row_counter;
            }

            $this->row_counter++;
        }
        $res = $this->dbh->fetchInto($this->result, $arr, $fetchmode, $rownum);
        if ($res === DB_OK) {
            if (isset($object_class)) {
                // default mode specified in DB_common::fetchmode_object_class property
                if ($object_class == 'stdClass') {
                    $arr = (object) $arr;
                } else {
                    $arr = new $object_class($arr);
                }
            }
            return DB_OK;
        }
        if ($res == null && $this->autofree) {
            $this->free();
        }
        return $res;
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the the number of columns in a result set.
     *
     * @return int the number of columns, or a DB error
     *
     * @access public
     */
    function numCols()
    {
        return $this->dbh->numCols($this->result);
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set.
     *
     * @return int the number of rows, or a DB error
     *
     * @access public
     */
    function numRows()
    {
        return $this->dbh->numRows($this->result);
    }

    // }}}
    // {{{ nextResult()

    /**
     * Get the next result if a batch of queries was executed.
     *
     * @return bool true if a new result is available or false if not.
     *
     * @access public
     */
    function nextResult()
    {
        return $this->dbh->nextResult($this->result);
    }

    // }}}
    // {{{ free()

    /**
     * Frees the resources allocated for this result set.
     * @return  int error code
     *
     * @access public
     */
    function free()
    {
        $err = $this->dbh->freeResult($this->result);
        if (DB::isError($err)) {
            return $err;
        }
        $this->result = false;
        return true;
    }

    // }}}
    // {{{ getRowCounter()

    /**
     * returns the actual row number
     * @return int
     */
    function getRowCounter()
    {
        return $this->row_counter;
    }
    // }}}
}

// }}}
// {{{ class DB_row

/**
 * Pear DB Row Object
 * @see DB_common::setFetchMode()
 */
class DB_row
{
    // {{{ constructor

    /**
     * constructor
     *
     * @param resource row data as array
     */
    function __construct(&$arr)
    {
        foreach ($arr as $key => $value) {
            $this->$key = &$arr[$key];
        }
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

<?php
/**
 * @author Karen Ziv <karen@perlessence.com>
 **/
class DAOFactory {

    const DIR_CONNECTORS = '/connectors/';
    const DIR_RESOURCES  = '/resources/';

    protected static $connectors = array();
    protected static $resources = array();
    
    public static $dsn2connector = array(); // DSN -> connector mappings
    public static $db2dsn        = array(); // database -> DSN mappings
    
    public static $test_factory;  // This is a unit-testing hook. Do NOT use it for anything else    
        
    /**
     * Creates a connector object
     * Looks for an existing connector instance matching the given connection string. If
     * one does not exist, loads the correct scheme class and stores an instance of the
     * connector. This allows the
     * @param  {str}  Connection string in format scheme://user:pass@host:port/(db | path )
     * @return {bool} Success or failure
     **/
    public static function connect($connection_string) {

        // Unit testing static method
        if (self::$test_factory && method_exists(self::$test_factory, __METHOD__)) {
            $f = self::$test_factory;
            return $f->__METHOD__($connection_string);
        }

        $connection_string = trim($connection_string);
        if (empty($connection_string)) {
            return FALSE;
        }

        // If an existing version of this connector exists, no need to recreate
        if (isset(self::$connectors[$connection_string])) {
            return TRUE;
        }

        // Create and store a new connector
        $scheme = parse_url($connection_string, PHP_URL_SCHEME);
        if (empty($scheme)) {
            return FALSE;
        }

        // Unit testing static method
        $connector = self::getConnector($connection_string);
        if (!$connector) {
            return FALSE;
        }
        if (!$connector->connect($connection_string)) {
            $error = $connector->getError();
            error_log("Can't connect: " . $error['message']);
            return FALSE;
        }
        self::$connectors[$connection_string] = $connector;
        return TRUE;
    }

    public static function getConnector($connection_string) {

        if (empty($connection_string)) {
            return FALSE;
        }
        
        // Unit testing static method
        if (self::$test_factory && method_exists(self::$test_factory, 'getConnector')) {
            $f = self::$test_factory;
            return $f->getConnector($connection_string);
        }
        
        $scheme = parse_url($connection_string, PHP_URL_SCHEME);
        $class_name = 'Connector_' . $scheme;
        if (!class_exists($class_name)) {
            $connector_path = dirname(__FILE__) . self::DIR_CONNECTORS . $scheme . '.connector.php';
            @include $connector_path;
            if (!class_exists($class_name)) {
                error_log("Connector '" . $class_name . "' not found in '$connector_path'");
                return FALSE;
            }
        }
        return new $class_name();
    }

    /**
     * Creates a connection to a data resource
     * @param {str} daonut resource string in format DB.Table
     * @param {str} Query type (default: 'select')
     **/
    public static function create($resource, $type = 'select') {
        
        $resource = trim($resource);
        if (empty($resource)) {
            error_log("No table resource defined.");
            return FALSE;
        }

        // Make sure query is of valid type
        $allowed_query_types = array('select', 'update', 'insert', 'delete','info');
        $type = strtolower($type);
        if (!in_array($type, $allowed_query_types)) {
            error_log("Invalid query type '" . $type . "' for resource '" . $resource . "'");
            return FALSE;
        }

        $resource = strtolower($resource);
        // If a data resource file exists, load its info
        $class_name = 'DAO_' . str_replace('.', '_', $resource); 
        if (!class_exists($class_name)) {
            @include dirname(__FILE__) . self::DIR_RESOURCES . str_replace('.', DIRECTORY_SEPARATOR, $resource) . '.dao.php';
        }
        // If the correct DAO exists in a file, use it
        if (class_exists($class_name)) {
            $dao = new $class_name();
            if (!$dao instanceof DynamicDao) {
                error_log("Resource '" . $resource . "' is not a valid DynamicDao");
                return FALSE;
            }
            
        }
        else {  // No resource file found for this resource string

            // Create a generic resource based on the resource string
            $dao = new DynamicDao();
            list($dao->db, $dao->table) = explode('.', $resource);
        }
        
        // Create a new connection if one doesn't exist
        $connection_string = self::getConnectionString(self::getDSN($dao->db));
        if (empty($connection_string)) {
            error_log("No connection string found for resource '" . $resource . "'");
            return FALSE;
        }
        if (!self::connect($connection_string)) {
            return FALSE;
        }
        $dao->setConnector(self::$connectors[$connection_string]);
        if (!$dao->connector->usedb($dao->db)) {
            error_log("Could not connect to DB '" . $dao->db . "'");
            return FALSE;
        }

        return $dao;
    }

    /**
     * Gets the connection string for a DSN alias
     * Loads the DSN mapping information and returns the connection string for the given
     * DSN alias.
     * @param  {str} DSN Alias (e.g. 'foo')
     * @return {str} Connection string in parse_url format
     **/
    public static function getConnectionString($alias, $file = 'dsn2connector.inc.php') {

        // Unit testing static method
        if (self::$test_factory && method_exists(self::$test_factory, __METHOD__)) {
            $f = self::$test_factory;
            return $f->__METHOD__($alias, $file);
        }
        
        if (empty(self::$dsn)) {
            @include dirname(__FILE__) . DIRECTORY_SEPARATOR . $file ;
            if (!isset($map)) {
                return FALSE;
            }
            self::$dsn2connector = $map;
            unset($map);
        }
        return isset(self::$dsn2connector[$alias])
            ? self::$dsn2connector[$alias]
            : FALSE;

    }

    /**
     * Gets the DSN alias for a database
     * Loads the database mapping information and returns the DSN alias
     * for the given database.
     **/
    public static function getDSN($alias, $file = 'db2dsn.inc.php') {
        if (empty(self::$db2dsn)) {
            @include dirname(__FILE__) . DIRECTORY_SEPARATOR . $file ;
            if (!isset($map)) {
                return FALSE;
            }
            self::$db2dsn = $map;
            unset($map);
        }
        
        return isset(self::$db2dsn[$alias])
            ? self::$db2dsn[$alias]
            : FALSE;
    }
    
}

class DynamicDao {

    public $db;
    public $table;
    public $fields = array();

    public $connector;
    protected $sql;
    
    public function setConnector(Connector $connector) {
        if (!$connector instanceof Connector) {
            return FALSE;
        }
        $this->connector = $connector;
    }

    public function query($sql) {
        if (empty($sql)) {
            return FALSE;
        }
        $this->sql = $sql;
        return TRUE;
    }

    public function execute() {
        if (empty($this->connector)) {
            return FALSE;
        }
        return $this->connector->query($this->sql);
    }

    /**
     * Magic field doohickey
     * @param {str} Name of method
     **/
    public function __call($m, $a) {
        
        if (method_exists($this->connector, $m)) {
            return call_user_func_array(array($this->connector, $m), $a);
        }
        else {
            throw new DynamicDaoException("Method '$m' does not exist.");
        }

    }
    
}

class DynamicDaoException extends Exception {}
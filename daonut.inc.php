<?php
/**
 * @author Karen Ziv <karen@perlessence.com>
 * @see http://github.com/foobarbazquux/daonut/wikis/daofactory
 **/
if (!class_exists('DynamicDao')) {
    require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dynamicdao.inc.php';
}

class DAOFactory {

    const DIR_CONNECTORS = '/connectors/'; // TODO : can slashes be turned to DIRECTORY_SEPARATOR?
    const DIR_RESOURCES  = '/resources/';  // TODO : can slashes be turned to DIRECTORY_SEPARATOR?
    const QUERY_BUILDER  = 'querybuilder.inc.php';
    
    protected static $connectors = array(); // Pool of existing connectors for reuse
    protected static $resources = array();  // Pool of existing resources for reuse
    
    public static $dsn2connector = array(); // DSN -> connector mappings
    public static $db2dsn        = array(); // database -> DSN mappings
    
    public static $test_factory;  // This is a unit-testing hook. Do NOT use it for anything else    

    /**
     * Gets a connector object based on a daonut resource string
     * This method is shorthand for getConnector(getConnectionString(getDSN('DB.Table')))
     * @param {str} daonut resource string in format DB.Table
     * @return {obj} New instance of connector class
     **/
    public static function connect($resource) {

        $resource = trim($resource);
        if (empty($resource)) {
            error_log("Invalid resource string '$resource'");
            return FALSE;
        }
        list($db, $table) = explode('.', $resource);
        
        $dsn = self::getDSN($db);
        if (!$dsn) {
            error_log("No DSN alias found for DB '$db'");
            return FALSE;
        }

        $connection_string = self::getConnectionString($dsn);
        if (!$connection_string) {
            error_log("No connection string found for DSN alias '$dsn'");
            return FALSE;
        }

        $connector = self::getConnector($connection_string);
        if (!$connector) {
            error_log("Could not create connector for connection string '$connection_string'");
            return FALSE;
        }
        if (!$connector->connect($connection_string)) {
            $error = $connector->getError();
            error_log("Can't connect: " . $error['message']);
            return FALSE;
        }

        return $connector;
    }
    
    /**
     * Gets a connector object based on a connection string
     * @param {str} Connection string in parse_url format
     * @return {obj} New instance of connector class
     **/
    public static function getConnector($connection_string) {

        if (empty($connection_string)) {
            return FALSE;
        }

        // If an existing version of this connector exists, no need to recreate
        if (isset(self::$connectors[$connection_string])) {
            return self::$connectors[$connection_string];
        }
        
        // Unit testing static method
        if (self::$test_factory && method_exists(self::$test_factory, 'getConnector')) {
            $f = self::$test_factory;
            return $f->getConnector($connection_string);
        }

        // Create and store a new connector
        $scheme = parse_url($connection_string, PHP_URL_SCHEME);
        if (empty($scheme)) {
            error_log("No scheme type in connection string '" . $connection_string . "'");
            return FALSE;
        }
        $class_name = 'Connector_' . $scheme;
        if (!class_exists($class_name)) {
            $connector_path = dirname(__FILE__) . self::DIR_CONNECTORS . $scheme . '.connector.php';
            @include $connector_path;
            if (!class_exists($class_name)) {
                error_log("Connector '" . $class_name . "' not found in '$connector_path'");
                return FALSE;
            }
        }
        self::$connectors[$connection_string] = new $class_name();
        return self::$connectors[$connection_string];
    }

    /**
     * Creates a connection to a data resource
     * @param {str} daonut resource string in format DB.Table
     * @param {str} Query type (default: 'select')
     * @return {obj} Instance of a DynamicDao class
     * @todo Reuse a DynamicDao if it already exists
     **/
    public static function create($resource, $type = 'select') {

        if (!class_exists('DynamicDao')) {
            error_log("Base class 'DynamicDao' has not been defined");
        }
        
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

        $connector = self::connect($resource);
        
        // Create a QueryBuilder for the DynamicDao
        if (class_exists('QueryBuilder')) {
            $dao->setQueryBuilder(new QueryBuilder(array(get_class($connector), 'escape')));
            $dao->querytype($type);
            $dao->from($dao->table);
        }
        else {
            // Try to include the QueryBuilder class file
            @include dirname(__FILE__) . DIRECTORY_SEPARATOR . self::QUERY_BUILDER;
            if (class_exists('QueryBuilder')) {
                $dao->setQueryBuilder(new QueryBuilder(array(get_class($connector), 'escape')));
                $dao->querytype($type);
                $dao->from($dao->table);
            }
        }
        
        
        $dao->connector = $connector;
        $dao->usedb($dao->db);
        return $dao;
    }

    /**
     * Gets the connection string for a DSN alias
     * Loads the DSN mapping information and returns the connection string for the given
     * DSN alias.
     * @param  {str} DSN Alias
     * @param  {str} Mapping file name
     * @return {str} Connection string in parse_url format
     **/
    public static function getConnectionString($alias, $file = 'dsn2connector.inc.php') {

        // Unit testing static method
        if (self::$test_factory && method_exists(self::$test_factory, __METHOD__)) {
            $f = self::$test_factory;
            return $f->__METHOD__($alias, $file);
        }
        
        if (empty(self::$dsn)) {
            if (substr($file, 0, 1) == '/') {
                @include $file;
            }
            else {
                @include dirname(__FILE__) . DIRECTORY_SEPARATOR . $file ;
            }
            
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
     * @param {str} Database name
     * @param {str} 
     **/
    public static function getDSN($db, $file = 'db2dsn.inc.php') {
        if (empty(self::$db2dsn)) {
            if (substr($file, 0, 1) == '/') {
                @include $file;
            }
            else {
                @include dirname(__FILE__) . DIRECTORY_SEPARATOR . $file ;
            }
            if (!isset($map)) {
                return FALSE;
            }
            self::$db2dsn = $map;
            unset($map);
        }
        
        return isset(self::$db2dsn[$db])
            ? self::$db2dsn[$db]
            : FALSE;
    }
    
}

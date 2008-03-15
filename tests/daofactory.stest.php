<?php
include_once dirname(dirname(__FILE__)) . '/daonut.inc.php';

/**
 * 
 **/
class Connector_MySQL {

}

class DAO_Test_Invalid {

}

class DAO_Test_Valid extends DynamicDao {

}

class DAO_Test_Valid_Insert extends DAO_Test_Valid {

}

/**  ======================================== 
 * TESTS
 ======================================== **/

/**
 * DAOFactory class tests
 **/
class DAOFactory_Test extends Snap_UnitTestCase {

    public function setUp() {}

    public function tearDown() {}

    public function testClassExists() {
        return $this->assertTrue(class_exists('DAOFactory'));
    }

}

/**
 * DAOFactory::getConnector()
 **/
class DAOFactory_getConnector_Test extends Snap_UnitTestCase {

    // Test data for connectors
    protected $connectors = array('db'   => 'mysql://user:pass@localhost:3306/test_db',
                                  'http' => 'http://news.google.com/nwshp?hl=en&tab=wn&output=rss',
                                  'bad'  => 'noconnector://user:pass@localhost:3306/test_db',
                                  );
    
    public function setUp() {}

    public function tearDown() {}

    public function testPassingEmptyArgumentsReturnsFalse() {
        $this->willError();
        return $this->assertFalse(DaoFactory::getConnector());
    }
    
    public function testConnectorClassNotFoundReturnsFalse() {
        $this->willError();
        return $this->assertFalse(DaoFactory::getConnector($this->connectors['bad']));
    }

    // Success case
    public function testConnectorCreatedReturnsConnector() {
        return $this->assertIsA(DaoFactory::getConnector($this->connectors['db']), 'Connector_MySQL');
    }
}

/**
 * DAOFactory::connect()
 **/
class DAOFactory_connect_Test extends Snap_UnitTestCase {

    // Test data for connectors
    protected $connectors = array('db'   => 'mysql://user:pass@localhost:3306/test_db',
                                  'http' => 'http://news.google.com/nwshp?hl=en&tab=wn&output=rss',
                                  'bad'  => 'noconnector://user:pass@localhost:3306/test_db',
                                  );


    public function setUp() {}

    public function tearDown() {}

    // Success case
    public function testConnectReturnsTrue() {
        return $this->assertTrue(DaoFactory::connect($this->connectors['db']));
    }

    public function testPassingEmptyArgumentReturnsFalse() {
        $this->willError();
        return $this->assertFalse(DaoFactory::connect());
    }
    
}

/**
 * DAOFactory::connect() - connector class usage
 **/

class DAOFactory_connect_badConnector_Test extends Snap_UnitTestCase {

    protected $connector = 'mysql://user:pass@localhost:3306/test_db';
    
    public function setUp() {
        $connector = new Connector_MySQL();
        $mock_method = $this->mock('DaoFactory')
            ->setReturnValue('getConnector', FALSE)
            ->construct();
        DaoFactory::$test_factory = $mock_method;
    }

    public function tearDown() {
        DaoFactory::$test_factory = NULL;
        unset($connector);
    }
    
    public function testNoConnectorCreatedReturnsFalse() {
        $this->notImplemented();
        return $this->assertFalse(DaoFactory::connect($this->connector));
    }
    
    
}

/**
 * DAOFactory::connect() - connector class created
 **/
class DAOFactory_connect_goodConnector_Test extends Snap_UnitTestCase {

    protected $connector = 'mysql://user:pass@localhost:3306/test_db';
    
    public function setUp() {
        $connector = new Connector_MySQL();
        $this->mock_method = $this->mock('DaoFactory')
            ->setReturnValue('getConnector', $connector)
            ->construct();
        DaoFactory::$test_factory = $this->mock_method;
    }

    public function tearDown() {
        DaoFactory::$test_factory = NULL;
        unset($connector);
    }

    public function testSameConnectorCreatedExactlyOnce() {
        $this->notImplemented();
        DaoFactory::connect($this->connector);
        DaoFactory::connect($this->connector);        
        return $this->assertCallCount($this->mock_method, 'getConnector', 1);
    }

}

/**
 * DaoFactory::getConnectionString
 **/
class DaoFactory_getConnectionString_Test extends Snap_UnitTestCase {

    protected $test_map_file;
    protected $map;
    
    public function setUp() {
        $this->test_map_file = basename(realpath('.')) . DIRECTORY_SEPARATOR . 'test_dsn2connector.inc.php';
        @include dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $this->test_map_file;
        $this->map = $map;
    }
    
    public function tearDown() {
        unset($this->test_map_file, $map);
    }

    public function testNoConnectionStringFoundReturnsFalse() {
        return $this->assertFalse(DaoFactory::getConnectionString('no_matching_dsn', $this->test_map_file));
    }
    
    // Success case
    public function testFoundAliasReturnsConnectionString() {
        return $this->assertIdentical($this->map['test_mysql'], DaoFactory::getConnectionString('test_mysql', $this->test_map_file));
    }

}


/**
 * DaoFactory::getDSN
 **/
class DaoFactory_getDSN_Test extends Snap_UnitTestCase {

    protected $test_map_file;

    public function setUp() {
        $this->test_map_file = basename(realpath('.')) . DIRECTORY_SEPARATOR . 'test_db2dsn.inc.php';
        @include dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $this->test_map_file;
        $this->map = $map;
    }
    
    public function tearDown() {
        unset($this->test_map_file, $map);
    }

    public function testNoConnectionStringFoundReturnsFalse() {
        return $this->assertFalse(DaoFactory::getDSN('no_matching_database', $this->test_map_file));
    }

    // Success case
    public function testFoundAliasReturnsConnectionString() {
        return $this->assertIdentical($this->map['test_db'], DaoFactory::getConnectionString('test_db', $this->test_map_file));
    }
    
}

/**
 * DaoFactory::create
 **/
class DAOFactory_create_Test extends Snap_UnitTestCase {

    public function setUp() {}

    public function tearDown() {}

    public function testNoParamsReturnsFalse() {
        return $this->assertFalse(DaoFactory::create('  '));
    }

    public function testInvalidTypeReturnsFalse() {
        return $this->assertFalse(DaoFactory::create('Foo.Bar', 'baz'));
    }

}

class DaoFactory_create_generic_Test extends Snap_UnitTestCase {

    protected $dao;
    
    public function setUp() {
        $this->dao = DaoFactory::create('TestDB.TestTable');
    }

    public function tearDown() {}
    
    public function testNoResourceFileCreatesGenericDynamicDao() {
        return $this->assertIdentical();
    }

}
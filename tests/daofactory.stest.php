<?php
include_once dirname(dirname(__FILE__)) . '/daonut.inc.php';

/**
 * 
 **/
class Connector_MySQL {

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
class DAOFactory_getConnector_Text extends Snap_UnitTestCase {

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

class DAOFactory_connect_connector_Test extends Snap_UnitTestCase {

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
    }

    public function testConnectorClassFailsConnectReturnsFalse() {
        return $this->assertFalse(DaoFactory::connect($this->connector));
    }
    
    
}

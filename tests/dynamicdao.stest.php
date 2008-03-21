<?php
include_once dirname(dirname(__FILE__)) . '/daonut.inc.php';

class DAO_Test extends DynamicDao {
  
  public $db = 'foo';
  public $table = 'bar';

}

/**  ======================================== 
 * TESTS
 ======================================== **/

/**
 * DynamicDao class tests		
 **/
class DynamicDao_Test extends Snap_UnitTestCase {

    public function setUp() {}
    
    public function tearDown() {}
    
    public function testClassExists() {
        return $this->assertTrue(class_exists('DynamicDao'));
    }
    
    public function testTestClassIsADynamicDao() {
        return $this->assertIsA(new DAO_Test, 'DynamicDao');
    }
}

class DynamicDao_setConnector_Test extends Snap_UnitTestCase {

    public function setUp() {
        $this->dao = new DAO_Test();
    }

    public function tearDown() {}

    public function testParamIsNotInstanceOfConnectorReturnsFalse() {
        $this->willError();
        return $this->assertFalse($this->dao->setConnector());
    }
}

class DynamicDao_query_Test extends Snap_UnitTestCase {

    public function setUp() {
        $this->dao = new DAO_Test();
    }

    public function tearDown() {}

    public function testNoConnectorSetReturnsFalse() {
        return $this->assertFalse($this->dao->query('SELECT * FROM bar'));
    }
    
    public function testNoParamReturnsFalse() {
        $this->notImplemented();
    }
    
}

<?php
/**
 * Test dsn2connector map file
 * Data goes in format $dsn_alias => $connection_string
 * where $connection_string is in valid parse_url format
 **/
$map =
array(
      'test_mysql' => 'mysql://username:password@localhost',
      );
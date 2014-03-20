<?php
class CacheFileTest extends PHPUnit_Framework_TestCase
{
  public function testGetReturnsNullWhenNotFound()
  {
    $file = $this->getMockBuilder('\\Pimf\\Cache\\Storages\\File')
      ->setConstructorArgs(array('/path/to/nirvana/'))
      ->setMethods(array('retrieve'))
      ->getMock();

    $file->expects($this->once())->method('retrieve')->with($this->equalTo('foobar'))->will($this->returnValue(null));

    $this->assertNull($file->get('foobar'));
  }

  public function testfValueIsReturned()
  {
    $file = $this->getMockBuilder('\\Pimf\\Cache\\Storages\\File')
      ->setConstructorArgs(array('/path/to/nirvana/'))
      ->setMethods(array('get'))
      ->getMock();

    $file->expects($this->once())->method('get')->will($this->returnValue('bar'));

    $this->assertEquals('bar', $file->get('/path/to/nirvana/'));
  }

  public function testSetMethodProperlyCalls()
  {
    $file = $this->getMockBuilder('\\Pimf\\Cache\\Storages\\File')
      ->setConstructorArgs(array('/path/to/nirvana/'))
      ->setMethods(array('put'))
      ->getMock();

    $file->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60));

    $file->put('foo', 'bar', 60);
  }

  public function testStoreItemForeverProperlyCalls()
  {
    $file = $this->getMockBuilder('\\Pimf\\Cache\\Storages\\File')
      ->setConstructorArgs(array('/path/to/nirvana/'))
      ->setMethods(array('put'))
      ->getMock();

    $file->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'));

    $file->forever('foo', 'bar');
  }

  public function testForgetMethodProperlyCalls()
  {
    $file = $this->getMockBuilder('\\Pimf\\Cache\\Storages\\File')
      ->setConstructorArgs(array('/path/to/nirvana/'))
      ->setMethods(array('forget'))
      ->getMock();

    $file->expects($this->once())->method('forget')->with($this->equalTo('foo'));

    $file->forget('foo');
  }

  public function testSmokeTestingToPutAndRetrieveAndForget()
  {
    $handle = fopen($file = dirname(__FILE__) . '/_drafts/a.cool.key.here', "w+");
    @fclose($handle); @chmod($file, 0777); @touch($file);

    $cache = new \Pimf\Cache\Storages\File(dirname(__FILE__) . '/_drafts/');

    $this->assertNull( $cache->put('a.cool.key.here', 'cool data', 0)  );

    $this->assertNull( $cache->put('a.cool.key.here', 'cool data', '')  );
    $this->assertNull( $cache->put('a.cool.key.here', 'cool data', null)  );

    $this->assertNotNull( $cache->put('a.cool.key.here', 'cool data', 1)  );

    $this->assertEquals( 'cool data', $cache->get('a.cool.key.here')  );

    $this->assertNull( $cache->get('a.bad.bad.key.here')  );

    $this->assertTrue( $cache->forget('a.cool.key.here') );

    $this->assertFalse( $cache->forget('a.bad.bad.key.here') );

    $handle = fopen($file = dirname(__FILE__) . '/_drafts/a.cool.key.here', "w+");
    @fclose($handle); @chmod($file, 0777); @touch($file);
  }
}
 
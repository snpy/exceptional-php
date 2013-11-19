<?php

namespace OBV\Component\Exceptional\Tests;

use OBV\Component\Exceptional\Exceptional;
use OBV\Component\Exceptional\Data;
use OBV\Component\Exceptional\Exception\PhpNotice;
use PHPUnit_Framework_TestCase;

require_once 'PHPUnit/Autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

class ExceptionalTest extends PHPUnit_Framework_TestCase
{
    private $data;
    private $request = array();

    protected function setUp()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function testGetParameters()
    {
        $_GET['a'] = 'GET works';

        $this->createExceptionData();

        $this->assertEquals($this->request['parameters']['a'], 'GET works');
    }

    public function testPostParameters()
    {
        $_POST['b'] = 'POST works';

        $this->createExceptionData();

        $this->assertEquals($this->request['parameters']['b'], 'POST works');
    }

    public function testBlacklist()
    {
        $_POST['password']                 = 'test123';
        $_POST['user']['creditcardnumber'] = 1234;
        $_POST['zipcode']                  = 55555;

        Exceptional::blacklist(array('password', 'creditcardnumber'));
        $this->createExceptionData();

        $this->assertEquals($this->request['parameters']['password'], '[FILTERED]');
        $this->assertEquals($this->request['parameters']['user']['creditcardnumber'], '[FILTERED]');
        $this->assertEquals($this->request['parameters']['zipcode'], 55555);
    }

    public function testControllerAndAction()
    {
        Exceptional::setController('home');
        Exceptional::setAction('index');

        $this->createExceptionData();

        $this->assertEquals($this->request['controller'], 'home');
        $this->assertEquals($this->request['action'], 'index');
    }

    public function testSessionFilter()
    {
        $session_name = md5(rand());
        $session_id   = md5(rand());

        ini_set('session.name', $session_name);
        $_SERVER['HTTP_Cookie'] = "$session_name=$session_id";

        $this->createExceptionData();

        $this->assertEquals($this->request['headers']['Cookie'], "$session_name=[FILTERED]");
    }

    private function createExceptionData()
    {
        $notice        = new PhpNotice('Test', 0, '', 0);
        $this->data    = new Data($notice);
        $this->request = $this->data->getData()['request'];
    }
}

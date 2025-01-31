<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Zend_Mail
 */
require_once 'Zend/Mail.php';

/**
 * Zend_Mail_Protocol_Smtp
 */
require_once 'Zend/Mail/Protocol/Smtp.php';

/**
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Mail
 */
class Zend_Mail_SmtpProtocolTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Zend_Mail_Protocol_Smtp
     */
    protected $_protocol;

    public function setUp()
    {
        $this->_protocol = new ProtocolMock();
    }

    public function testEhlo()
    {
        $this->_connectAndEhlo(); // expects 250 response

        $this->assertEquals(array(
            '220 example.com ESMTP welcome',
            'EHLO 127.0.0.1',
            '250 Hello 127.0.0.1, go ahead'
        ), $this->_protocol->dialog);
    }

    public static $dependsTestHeloIsOnlyAllowedOncePerSession = array('testEhlo');
    public function testHeloIsOnlyAllowedOncePerSession($arg)
    {
        $this->expectException('Zend_Mail_Protocol_Exception');
        $this->_connectAndEhlo(); // do it once
        $this->_protocol->helo(); // do it again
    }

    public static $dependsTestEhloFallsBackToHelo = array('testEhlo');
    public function testEhloFallsBackToHelo($arg)
    {
        $this->_protocol->responseBuffer = array(
            '220 example.com ESMTP welcome',
            '500 Unrecognized', /* 500 or 502 error on unrecognized EHLO */
            '250 Hello 127.0.0.1, go ahead'
        );

        $this->_protocol->connect();
        $this->_protocol->helo();

        $this->assertEquals(array(
            '220 example.com ESMTP welcome',
            'EHLO 127.0.0.1', // tries EHLO
            '500 Unrecognized', // .. which fails
            'HELO 127.0.0.1', // continues to HELO
            '250 Hello 127.0.0.1, go ahead' // success
        ), $this->_protocol->dialog);
    }

    public static $dependsTestMail = array('testEhlo');
    public function testMail($arg)
    {
        $p = $this->_protocol;
        $expectedDialog = $this->_connectAndEhlo();

        $expectedDialog[] = 'MAIL FROM:<from@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '250 Sender accepted';
        $this->_protocol->mail('from@example.com');

        $this->assertEquals($expectedDialog, $this->_protocol->dialog);
    }

    public static $dependsTestRcptExpects250 = array('testMail');
    public function testRcptExpects250($arg)
    {
        $p = $this->_protocol;
        $expectedDialog = $this->_connectAndEhlo();

        $expectedDialog[] = 'MAIL FROM:<from@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '250 Sender accepted';
        $this->_protocol->mail('from@example.com');

        $expectedDialog[] = 'RCPT TO:<to@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '250 Recipient OK';
        $this->_protocol->rcpt('to@example.com');

        $this->assertEquals($expectedDialog, $this->_protocol->dialog);
    }

    public static $dependsTestRcptExpects251 = array('testMail');
    public function testRcptExpects251($arg)
    {
        $p = $this->_protocol;
        $expectedDialog = $this->_connectAndEhlo();

        $expectedDialog[] = 'MAIL FROM:<from@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '250 Sender accepted';
        $this->_protocol->mail('from@example.com');

        $expectedDialog[] = 'RCPT TO:<to@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '251 Recipient OK';
        $this->_protocol->rcpt('to@example.com');

        $this->assertEquals($expectedDialog, $this->_protocol->dialog);
    }

    public static $dependsTestData = array('testRcptExpects250');
    public function testData($arg)
    {
        $p = $this->_protocol;
        $expectedDialog = $this->_connectAndEhlo();

        $expectedDialog[] = 'MAIL FROM:<from@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '250 Sender accepted';
        $this->_protocol->mail('from@example.com');

        $expectedDialog[] = 'RCPT TO:<to@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '250 Recipient OK';
        $this->_protocol->rcpt('to@example.com');

        $expectedDialog[] = 'DATA';
        $expectedDialog[] = $p->responseBuffer[] = '354 Go ahead';
        $expectedDialog[] = 'foo';
        $expectedDialog[] = '.'; // end of data marker
        $expectedDialog[] = $p->responseBuffer[] = '250 Accepted';
        $this->_protocol->data('foo');

        $this->assertEquals($expectedDialog, $this->_protocol->dialog);
    }

    public static $dependsTestRset = array('testEhlo');
    public function testRset($arg)
    {
        $expectedDialog = $this->_connectAndEhlo();

        $this->_protocol->responseBuffer = array('250 OK');
        $expectedDialog[] = 'RSET';
        $expectedDialog[] = '250 OK';

        $this->_protocol->rset();

        $this->assertEquals($expectedDialog, $this->_protocol->dialog);
    }

    public static $dependsTestRsetExpects220 = array('testEhlo');
    /**
     * @group ZF-1377
     */
    public function testRsetExpects220($arg)
    {
        $expectedDialog = $this->_connectAndEhlo();

        // Microsoft ESMTP server responds to RSET with 220 rather than 250
        $this->_protocol->responseBuffer = array('220 OK');
        $expectedDialog[] = 'RSET';
        $expectedDialog[] = '220 OK';

        $this->_protocol->rset();

        $this->assertEquals($expectedDialog, $this->_protocol->dialog);
    }

    public static $dependsTestQuit = array('testEhlo');
    public function testQuit($arg)
    {
        $p = $this->_protocol;
        $expectedDialog = $this->_connectAndEhlo();

        $expectedDialog[] = 'QUIT';
        $expectedDialog[] = $p->responseBuffer[] = '221 goodbye';

        $this->_protocol->quit();

        $this->assertEquals($expectedDialog, $this->_protocol->dialog);
    }

    public static $dependsTestMultilineResponsesAreNotTruncated = array('testMail');
    /**
     * @group ZF-8511
     */
    public function testMultilineResponsesAreNotTruncated($arg)
    {
        $this->_connectAndEhlo();

        $this->_protocol->responseBuffer[] = '550-line one';
        $this->_protocol->responseBuffer[] = '550 line two';

        try {
            $this->_protocol->mail('from@example.com');
            $this->fail('Expected exception on 550 response');
        } catch (Zend_Mail_Protocol_Exception $e) {
            $this->assertEquals('line one line two', $e->getMessage());
        }
    }

    public static $dependsTestExceptionCodeIsSmtpStatusCode = array('testMail');
    /**
     * @group ZF-10249
     */
    public function testExceptionCodeIsSmtpStatusCode($arg)
    {
        $p = $this->_protocol;
        $this->_connectAndEhlo();

        $p->responseBuffer[] = '550 failure';

        try {
            $this->_protocol->mail('from@example.com');
            $this->fail('Expected exception on 550 response');
        } catch (Zend_Mail_Protocol_Exception $e) {
            $this->assertEquals(550, $e->getCode());
        }
    }

    public static $dependsTestRcptThrowsExceptionOnUnexpectedResponse = array('testMail');
    public function testRcptThrowsExceptionOnUnexpectedResponse($arg)
    {
        $this->expectException('Zend_Mail_Protocol_Exception');

        $p = $this->_protocol;
        $expectedDialog = $this->_connectAndEhlo();

        $expectedDialog[] = 'MAIL FROM:<from@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '250 Sender accepted';
        $this->_protocol->mail('from@example.com');

        $expectedDialog[] = 'RCPT TO:<to@example.com>';
        $expectedDialog[] = $p->responseBuffer[] = '500 error';
        $this->_protocol->rcpt('to@example.com');
    }


    public function testMailBeforeHeloThrowsException()
    {
        try {
            $this->_protocol->mail('from@example.com');
            $this->fail('mail() before helo() should throw exception');
        } catch (Zend_Mail_Protocol_Exception $e) {
            $this->assertEquals('A valid session has not been started', $e->getMessage());
        }
    }

    public static $dependsTestRcptBeforeMailThrowsException = array('testEhlo');
    public function testRcptBeforeMailThrowsException($arg)
    {
        $this->_connectAndEhlo();

        try {
            $this->_protocol->rcpt('to@example.com');
            $this->fail('rcpt() before mail() should throw exception');
        } catch (Zend_Mail_Protocol_Exception $e) {
            $this->assertEquals('No sender reverse path has been supplied', $e->getMessage());
        }
    }

    public static $dependsTestDataBeforeRcptThrowsException = array('testEhlo');
    public function testDataBeforeRcptThrowsException($arg)
    {
        $this->expectException('Zend_Mail_Protocol_Exception');

        $this->_connectAndEhlo();

        $this->_protocol->data('foo');
    }

    /**
     * Performs the initial EHLO dialog
     */
    protected function _connectAndEhlo()
    {
        $this->_protocol->responseBuffer = array(
            '220 example.com ESMTP welcome',
            '250 Hello 127.0.0.1, go ahead'
        );

        $this->_protocol->connect();
        $this->_protocol->helo();
        return $this->_protocol->dialog;
    }
}


class ProtocolMock extends Zend_Mail_Protocol_Smtp
{
    public $dialog = array();
    public $responseBuffer = array();

    /**
     * Override connect function to use local file for testing
     *
     * @param string $remote
     */
    protected function _connect($remote)
    {
        $this->_socket = tmpfile();
    }

    protected function _send($request)
    {
        $this->dialog[] = $request;
    }

    protected function _receive($timeout = null)
    {
        $line = array_shift($this->responseBuffer);
        $this->dialog[] = $line;
        return $line;
    }
}

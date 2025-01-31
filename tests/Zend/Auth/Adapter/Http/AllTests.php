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
 * @package    Zend_Auth
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

require_once 'Zend/Auth/Adapter/Http/AuthTest.php';
require_once 'Zend/Auth/Adapter/Http/ObjectTest.php';
require_once 'Zend/Auth/Adapter/Http/ProxyTest.php';
require_once 'Zend/Auth/Adapter/Http/Resolver/AllTests.php';

/**
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Auth
 */
class Zend_Auth_Adapter_Http_AllTests
{
    /**
     * Creates and returns this test suite
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Zend Framework - Zend_Auth_Adapter_Http');

        $suite->addTestSuite('Zend_Auth_Adapter_Http_AuthTest');
        $suite->addTestSuite('Zend_Auth_Adapter_Http_ObjectTest');
        $suite->addTestSuite('Zend_Auth_Adapter_Http_ProxyTest');
        $suite->addTest(Zend_Auth_Adapter_Http_Resolver_AllTests::suite());

        return $suite;
    }
}

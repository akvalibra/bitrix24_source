<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Respect\Validation\Rules;

use Respect\Validation\TestCase;
use stdClass;

function class_exists($className)
{
    if (isset($GLOBALS['class_exists'][$className])) {
        return $GLOBALS['class_exists'][$className];
    }

    return \class_exists($className);
}

/**
 * @group  rule
 * @covers Respect\Validation\Rules\Email
 * @covers Respect\Validation\Exceptions\EmailException
 */
class EmailTest extends TestCase
{
    private function setEmailValidatorExists($value)
    {
        $GLOBALS['class_exists']['Egulias\EmailValidator\EmailValidator'] = (bool) $value;
    }

    private function resetClassExists()
    {
        unset($GLOBALS['class_exists']);
    }

    private function getEmailValidatorMock()
    {
        $emailValidatorMock = $this
            ->getMockBuilder('Egulias\\EmailValidator\\EmailValidator')
            ->disableOriginalConstructor()
            ->getMock();

        return $emailValidatorMock;
    }

    protected function setUp()
    {
        $this->setEmailValidatorExists(false);
    }

    protected function tearDown()
    {
        $this->resetClassExists();
    }

    public function testShouldAcceptInstanceOfEmailValidatorOnConstructor()
    {
        $this->resetClassExists();

        $emailValidator = $this->getEmailValidatorMock();

        $rule = new Email($emailValidator);

        $this->assertSame($emailValidator, $rule->getEmailValidator());
    }

    public function testShouldHaveADefaultInstanceOfEmailValidator()
    {
        $this->resetClassExists();

        $rule = new Email();

        $this->assertInstanceOf('Egulias\\EmailValidator\\EmailValidator', $rule->getEmailValidator());
    }

    public function testShouldUseEmailValidatorWhenDefined()
    {
        $this->resetClassExists();

        $input = 'example@example.com';

        $emailValidator = $this->getEmailValidatorMock();
        $emailValidator
            ->expects($this->once())
            ->method('isValid')
            ->with($input)
            ->will($this->returnValue(true));

        $rule = new Email($emailValidator);

        $this->assertTrue($rule->validate($input));
    }

    /**
     * @dataProvider providerForValidEmail
     */
    public function testValidEmailShouldPass($validEmail)
    {
        $validator = new Email();
        $this->assertTrue($validator->__invoke($validEmail));
        $this->assertTrue($validator->check($validEmail));
        $this->assertTrue($validator->assert($validEmail));
    }

    /**
     * @dataProvider providerForInvalidEmail
     * @expectedException Respect\Validation\Exceptions\EmailException
     */
    public function testInvalidEmailsShouldFailValidation($invalidEmail)
    {
        $validator = new Email();
        $this->assertFalse($validator->__invoke($invalidEmail));
        $this->assertFalse($validator->assert($invalidEmail));
    }

    public function providerForValidEmail()
    {
        return [
            ['test@test.com'],
            ['mail+mail@gmail.com'],
            ['mail.email@e.test.com'],
            ['a@a.a'],
        ];
    }

    public function providerForInvalidEmail()
    {
        return [
            [''],
            ['test@test'],
            ['test'],
            ['test@����.��'],
            ['@test.com'],
            ['mail@test@test.com'],
            ['test.test@'],
            ['test.@test.com'],
            ['test@.test.com'],
            ['test@test..com'],
            ['test@test.com.'],
            ['.test@test.com'],
            [[]],
            [new stdClass()],
            [null],
            [tmpfile()],
        ];
    }
}

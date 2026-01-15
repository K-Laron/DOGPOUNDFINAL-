<?php

/**
 * Validator Unit Tests
 * Tests the Validator utility class
 * 
 * @package AnimalShelter\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // ==========================================
    // REQUIRED VALIDATION TESTS
    // ==========================================

    public function testRequiredPassesWithValue(): void
    {
        $this->validator->setData(['name' => 'John']);
        $this->validator->required('name');

        $this->assertTrue($this->validator->passes());
    }

    public function testRequiredFailsWithEmptyString(): void
    {
        $this->validator->setData(['name' => '']);
        $this->validator->required('name');

        $this->assertTrue($this->validator->fails());
    }

    public function testRequiredFailsWithMissingField(): void
    {
        $this->validator->setData([]);
        $this->validator->required('name');

        $this->assertTrue($this->validator->fails());
    }

    public function testRequiredFailsWithNull(): void
    {
        $this->validator->setData(['name' => null]);
        $this->validator->required('name');

        $this->assertTrue($this->validator->fails());
    }

    // ==========================================
    // EMAIL VALIDATION TESTS
    // ==========================================

    public function testEmailPassesWithValidEmail(): void
    {
        $this->validator->setData(['email' => 'test@example.com']);
        $this->validator->email('email');

        $this->assertTrue($this->validator->passes());
    }

    public function testEmailFailsWithInvalidEmail(): void
    {
        $this->validator->setData(['email' => 'not-an-email']);
        $this->validator->email('email');

        $this->assertTrue($this->validator->fails());
    }

    public function testEmailFailsWithMissingAt(): void
    {
        $this->validator->setData(['email' => 'testexample.com']);
        $this->validator->email('email');

        $this->assertTrue($this->validator->fails());
    }

    // ==========================================
    // MIN LENGTH VALIDATION TESTS
    // ==========================================

    public function testMinLengthPassesWithExactLength(): void
    {
        $this->validator->setData(['password' => '12345678']);
        $this->validator->minLength('password', 8);

        $this->assertTrue($this->validator->passes());
    }

    public function testMinLengthPassesWithLongerString(): void
    {
        $this->validator->setData(['password' => '123456789012']);
        $this->validator->minLength('password', 8);

        $this->assertTrue($this->validator->passes());
    }

    public function testMinLengthFailsWithShortString(): void
    {
        $this->validator->setData(['password' => '1234']);
        $this->validator->minLength('password', 8);

        $this->assertTrue($this->validator->fails());
    }

    // ==========================================
    // MAX LENGTH VALIDATION TESTS
    // ==========================================

    public function testMaxLengthPassesWithExactLength(): void
    {
        $this->validator->setData(['username' => '12345']);
        $this->validator->maxLength('username', 5);

        $this->assertTrue($this->validator->passes());
    }

    public function testMaxLengthFailsWithLongerString(): void
    {
        $this->validator->setData(['username' => '123456789012']);
        $this->validator->maxLength('username', 5);

        $this->assertTrue($this->validator->fails());
    }

    // ==========================================
    // NUMERIC VALIDATION TESTS
    // ==========================================

    public function testNumericPassesWithInteger(): void
    {
        $this->validator->setData(['age' => 25]);
        $this->validator->numeric('age');

        $this->assertTrue($this->validator->passes());
    }

    public function testNumericPassesWithFloat(): void
    {
        $this->validator->setData(['price' => 19.99]);
        $this->validator->numeric('price');

        $this->assertTrue($this->validator->passes());
    }

    public function testNumericPassesWithNumericString(): void
    {
        $this->validator->setData(['quantity' => '100']);
        $this->validator->numeric('quantity');

        $this->assertTrue($this->validator->passes());
    }

    public function testNumericFailsWithNonNumeric(): void
    {
        $this->validator->setData(['age' => 'twenty-five']);
        $this->validator->numeric('age');

        $this->assertTrue($this->validator->fails());
    }

    // ==========================================
    // ERROR MESSAGE TESTS
    // ==========================================

    public function testGetErrorsReturnsAllErrors(): void
    {
        $this->validator->setData(['email' => 'invalid', 'name' => '']);
        $this->validator->required('name');
        $this->validator->email('email');

        $errors = $this->validator->getErrors();

        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testCustomErrorMessage(): void
    {
        $this->validator->setData(['name' => '']);
        $this->validator->required('name', 'Please enter your name');

        $errors = $this->validator->getErrors();

        $this->assertEquals('Please enter your name', $errors['name']);
    }

    // ==========================================
    // CHAINING TESTS
    // ==========================================

    public function testMethodChaining(): void
    {
        $result = $this->validator
            ->setData(['email' => 'test@example.com', 'password' => '12345678'])
            ->required('email')
            ->email('email')
            ->required('password')
            ->minLength('password', 8);

        $this->assertInstanceOf(Validator::class, $result);
        $this->assertTrue($this->validator->passes());
    }
}

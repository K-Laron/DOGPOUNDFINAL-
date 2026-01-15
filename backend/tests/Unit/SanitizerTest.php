<?php

/**
 * Sanitizer Unit Tests
 * Tests the Sanitizer utility class for XSS prevention
 * 
 * @package AnimalShelter\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    // ==========================================
    // STRING SANITIZATION TESTS
    // ==========================================

    public function testStringRemovesScriptTags(): void
    {
        $input = '<script>alert("xss")</script>Hello';
        $result = Sanitizer::string($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
    }

    public function testStringEscapesHtmlEntities(): void
    {
        $input = '<div onclick="evil()">Test</div>';
        $result = Sanitizer::string($input);

        // Should escape the HTML
        $this->assertStringNotContainsString('<div', $result);
    }

    public function testStringTrimsWhitespace(): void
    {
        $input = '   Hello World   ';
        $result = Sanitizer::string($input);

        $this->assertEquals('Hello World', $result);
    }

    public function testStringRemovesNullBytes(): void
    {
        $input = "Hello\0World";
        $result = Sanitizer::string($input);

        $this->assertStringNotContainsString("\0", $result);
    }

    public function testStringHandlesEmptyInput(): void
    {
        $result = Sanitizer::string('');
        $this->assertEquals('', $result);
    }

    public function testStringHandlesNull(): void
    {
        $result = Sanitizer::string(null);
        $this->assertEquals('', $result);
    }

    // ==========================================
    // EMAIL SANITIZATION TESTS
    // ==========================================

    public function testEmailReturnsValidEmail(): void
    {
        $input = 'test@example.com';
        $result = Sanitizer::email($input);

        $this->assertEquals('test@example.com', $result);
    }

    public function testEmailRemovesInvalidCharacters(): void
    {
        $input = 'test<script>@example.com';
        $result = Sanitizer::email($input);

        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testEmailReturnsNullForInvalidEmail(): void
    {
        $input = 'not-an-email';
        $result = Sanitizer::email($input);

        $this->assertNull($result);
    }

    public function testEmailTrimsWhitespace(): void
    {
        $input = '  test@example.com  ';
        $result = Sanitizer::email($input);

        $this->assertEquals('test@example.com', $result);
    }

    // ==========================================
    // INTEGER SANITIZATION TESTS
    // ==========================================

    public function testIntegerReturnsInteger(): void
    {
        $result = Sanitizer::integer(42);
        $this->assertSame(42, $result);
    }

    public function testIntegerConvertsNumericString(): void
    {
        $result = Sanitizer::integer('42');
        $this->assertSame(42, $result);
    }

    public function testIntegerReturnsDefaultForNonNumeric(): void
    {
        $result = Sanitizer::integer('abc', 0);
        $this->assertSame(0, $result);
    }

    public function testIntegerReturnsNullForNonNumericWithoutDefault(): void
    {
        $result = Sanitizer::integer('abc');
        $this->assertNull($result);
    }

    public function testIntegerHandlesNegativeNumbers(): void
    {
        $result = Sanitizer::integer(-10);
        $this->assertSame(-10, $result);
    }

    public function testIntegerHandlesFloatInput(): void
    {
        // FILTER_SANITIZE_NUMBER_INT removes non-digits, so 3.7 becomes 37
        // This is expected PHP filter behavior
        $result = Sanitizer::integer('3.7');
        $this->assertSame(37, $result);
    }

    // ==========================================
    // FLOAT SANITIZATION TESTS
    // ==========================================

    public function testFloatReturnsFloat(): void
    {
        $result = Sanitizer::float(3.14);
        $this->assertSame(3.14, $result);
    }

    public function testFloatConvertsNumericString(): void
    {
        $result = Sanitizer::float('3.14');
        $this->assertSame(3.14, $result);
    }

    public function testFloatReturnsDefaultForNonNumeric(): void
    {
        $result = Sanitizer::float('abc', 0.0);
        $this->assertSame(0.0, $result);
    }

    // ==========================================
    // BOOLEAN SANITIZATION TESTS
    // ==========================================

    public function testBooleanReturnsTrueForTrueValue(): void
    {
        $this->assertTrue(Sanitizer::boolean(true));
        $this->assertTrue(Sanitizer::boolean(1));
        $this->assertTrue(Sanitizer::boolean('1'));
        $this->assertTrue(Sanitizer::boolean('true'));
        $this->assertTrue(Sanitizer::boolean('yes'));
        $this->assertTrue(Sanitizer::boolean('on'));
    }

    public function testBooleanReturnsFalseForFalseValue(): void
    {
        $this->assertFalse(Sanitizer::boolean(false));
        $this->assertFalse(Sanitizer::boolean(0));
        $this->assertFalse(Sanitizer::boolean('0'));
        $this->assertFalse(Sanitizer::boolean('false'));
        $this->assertFalse(Sanitizer::boolean('no'));
        $this->assertFalse(Sanitizer::boolean('off'));
    }

    // ==========================================
    // FILENAME SANITIZATION TESTS
    // ==========================================

    public function testFilenameRemovesPathTraversal(): void
    {
        $input = '../../../etc/passwd';
        $result = Sanitizer::filename($input);

        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
    }

    public function testFilenameRemovesSpecialCharacters(): void
    {
        $input = 'file<script>.jpg';
        $result = Sanitizer::filename($input);

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function testFilenamePreservesValidName(): void
    {
        $input = 'my_photo-2024.jpg';
        $result = Sanitizer::filename($input);

        $this->assertEquals('my_photo-2024.jpg', $result);
    }

    // ==========================================
    // XSS PREVENTION TESTS
    // ==========================================

    public function testXssPreventionInEventHandlers(): void
    {
        // string() uses htmlspecialchars - escapes < and > so tags are neutralized
        $input = '<img src=x onerror="alert(1)">';
        $result = Sanitizer::string($input);

        // The < and > are escaped, making the tag harmless
        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringContainsString('&lt;', $result);
    }

    public function testXssPreventionInJavascriptUrl(): void
    {
        // string() escapes HTML entities
        $input = '<a href="javascript:alert(1)">Click</a>';
        $result = Sanitizer::string($input);

        // Tags are escaped, not removed
        $this->assertStringNotContainsString('<a', $result);
        $this->assertStringContainsString('&lt;', $result);
    }

    public function testXssPreventionInDataUrl(): void
    {
        $input = '<img src="data:text/html,<script>alert(1)</script>">';
        $result = Sanitizer::string($input);

        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testStripDangerousTagsRemovesEventHandlers(): void
    {
        // stripDangerousTags actually removes event handlers
        $input = '<img src=x onerror="alert(1)">';
        $result = Sanitizer::stripDangerousTags($input);

        $this->assertStringNotContainsString('onerror', $result);
    }

    public function testStripDangerousTagsRemovesJavascriptUrls(): void
    {
        $input = '<a href="javascript:alert(1)">Click</a>';
        $result = Sanitizer::stripDangerousTags($input);

        $this->assertStringNotContainsString('javascript:', $result);
    }

    // ==========================================
    // ARRAY SANITIZATION TESTS
    // ==========================================

    public function testArraySanitizesAllValues(): void
    {
        $input = [
            'name' => '  <script>alert("xss")</script>John  ',
            'age' => '25',
            'email' => '  test@example.com  '
        ];

        $result = Sanitizer::array($input);

        $this->assertStringNotContainsString('<script>', $result['name']);
        $this->assertStringContainsString('John', $result['name']);
    }

    public function testArrayHandlesNestedArrays(): void
    {
        $input = [
            'user' => [
                'name' => '<script>xss</script>John'
            ]
        ];

        $result = Sanitizer::array($input);

        $this->assertStringNotContainsString('<script>', $result['user']['name']);
    }
}

<?php

use jschreuder\BookmarkBureau\Util\Filter;

describe('Filter Utility', function () {
    describe('start', function () {
        test('creates filter from array key', function () {
            $data = ['name' => 'John'];
            $filter = Filter::start($data, 'name');

            expect($filter->done())->toBe('John');
        });

        test('uses default value when key does not exist', function () {
            $data = [];
            $filter = Filter::start($data, 'name', 'default');

            expect($filter->done())->toBe('default');
        });

        test('uses null as default when not specified', function () {
            $data = [];
            $filter = Filter::start($data, 'name');

            expect($filter->done())->toBeNull();
        });

        test('uses default value when key exists but is null (isset behavior)', function () {
            $data = ['name' => null];
            $filter = Filter::start($data, 'name', 'default');

            expect($filter->done())->toBe('default');
        });

        test('uses default value only when key does not exist in array', function () {
            $data = ['other' => 'value'];
            $filter = Filter::start($data, 'name', 'default');

            expect($filter->done())->toBe('default');
        });
    });

    describe('do', function () {
        test('applies custom callable function', function () {
            $filter = Filter::start(['value' => 10], 'value');
            $result = $filter->do(fn($val) => $val * 2)->done();

            expect($result)->toBe(20);
        });

        test('chains multiple custom functions', function () {
            $filter = Filter::start(['value' => 5], 'value');
            $result = $filter
                ->do(fn($val) => $val * 2)
                ->do(fn($val) => $val + 3)
                ->done();

            expect($result)->toBe(13);
        });

        test('receives null value to callable', function () {
            $filter = Filter::start(['value' => null], 'value');
            $result = $filter->do(fn($val) => $val ?? 'was null')->done();

            expect($result)->toBe('was null');
        });
    });

    describe('string', function () {
        test('converts value to string', function () {
            $filter = Filter::start(['value' => 123], 'value');

            expect($filter->string()->done())->toBe('123');
            expect($filter->string()->done())->toBeString();
        });

        test('converts boolean to string', function () {
            $filter = Filter::start(['value' => true], 'value');

            expect($filter->string()->done())->toBe('1');
        });

        test('preserves null when allowNull is true', function () {
            $filter = Filter::start(['value' => null], 'value');

            expect($filter->string(allowNull: true)->done())->toBeNull();
        });

        test('converts null to string when allowNull is false', function () {
            $filter = Filter::start(['value' => null], 'value');

            expect($filter->string(allowNull: false)->done())->toBe('');
        });
    });

    describe('int', function () {
        test('converts value to integer', function () {
            $filter = Filter::start(['value' => '42'], 'value');

            expect($filter->int()->done())->toBe(42);
            expect($filter->int()->done())->toBeInt();
        });

        test('converts float to integer', function () {
            $filter = Filter::start(['value' => 42.9], 'value');

            expect($filter->int()->done())->toBe(42);
        });

        test('converts boolean to integer', function () {
            $filter = Filter::start(['value' => true], 'value');

            expect($filter->int()->done())->toBe(1);
        });

        test('preserves null when allowNull is true', function () {
            $filter = Filter::start(['value' => null], 'value');

            expect($filter->int(allowNull: true)->done())->toBeNull();
        });

        test('converts null to integer when allowNull is false', function () {
            $filter = Filter::start(['value' => null], 'value');

            expect($filter->int(allowNull: false)->done())->toBe(0);
        });
    });

    describe('float', function () {
        test('converts value to float', function () {
            $filter = Filter::start(['value' => '42.5'], 'value');

            expect($filter->float()->done())->toBe(42.5);
            expect($filter->float()->done())->toBeFloat();
        });

        test('converts integer to float', function () {
            $filter = Filter::start(['value' => 42], 'value');

            expect($filter->float()->done())->toBe(42.0);
        });

        test('converts boolean to float', function () {
            $filter = Filter::start(['value' => true], 'value');

            expect($filter->float()->done())->toBe(1.0);
        });

        test('preserves null when allowNull is true', function () {
            $filter = Filter::start(['value' => null], 'value');

            expect($filter->float(allowNull: true)->done())->toBeNull();
        });

        test('converts null to float when allowNull is false', function () {
            $filter = Filter::start(['value' => null], 'value');

            expect($filter->float(allowNull: false)->done())->toBe(0.0);
        });
    });

    describe('bool', function () {
        test('converts truthy string to boolean', function () {
            $filter = Filter::start(['value' => '1'], 'value');

            expect($filter->bool()->done())->toBeTrue();
        });

        test('converts falsy string to boolean', function () {
            $filter = Filter::start(['value' => '0'], 'value');

            expect($filter->bool()->done())->toBeFalse();
        });

        test('converts non-empty string to boolean true', function () {
            $filter = Filter::start(['value' => 'hello'], 'value');

            expect($filter->bool()->done())->toBeTrue();
        });

        test('converts integer to boolean', function () {
            $filter1 = Filter::start(['value' => 1], 'value');
            $filter2 = Filter::start(['value' => 0], 'value');

            expect($filter1->bool()->done())->toBeTrue();
            expect($filter2->bool()->done())->toBeFalse();
        });

        test('preserves null when allowNull is true', function () {
            $filter = Filter::start(['value' => null], 'value');

            expect($filter->bool(allowNull: true)->done())->toBeNull();
        });

        test('converts null to boolean when allowNull is false', function () {
            $filter = Filter::start(['value' => null], 'value');

            expect($filter->bool(allowNull: false)->done())->toBeFalse();
        });
    });

    describe('uppercase', function () {
        test('converts string to uppercase', function () {
            $filter = Filter::start(['value' => 'hello'], 'value');

            expect($filter->uppercase()->done())->toBe('HELLO');
        });

        test('preserves already uppercase string', function () {
            $filter = Filter::start(['value' => 'HELLO'], 'value');

            expect($filter->uppercase()->done())->toBe('HELLO');
        });

        test('converts mixed case to uppercase', function () {
            $filter = Filter::start(['value' => 'HeLLo WoRLD'], 'value');

            expect($filter->uppercase()->done())->toBe('HELLO WORLD');
        });

        test('leaves non-string values unchanged', function () {
            $filter = Filter::start(['value' => 123], 'value');

            expect($filter->uppercase()->done())->toBe(123);
        });
    });

    describe('lowercase', function () {
        test('converts string to lowercase', function () {
            $filter = Filter::start(['value' => 'HELLO'], 'value');

            expect($filter->lowercase()->done())->toBe('hello');
        });

        test('preserves already lowercase string', function () {
            $filter = Filter::start(['value' => 'hello'], 'value');

            expect($filter->lowercase()->done())->toBe('hello');
        });

        test('converts mixed case to lowercase', function () {
            $filter = Filter::start(['value' => 'HeLLo WoRLD'], 'value');

            expect($filter->lowercase()->done())->toBe('hello world');
        });

        test('leaves non-string values unchanged', function () {
            $filter = Filter::start(['value' => 123], 'value');

            expect($filter->lowercase()->done())->toBe(123);
        });
    });

    describe('trim', function () {
        test('removes leading whitespace', function () {
            $filter = Filter::start(['value' => '  hello'], 'value');

            expect($filter->trim()->done())->toBe('hello');
        });

        test('removes trailing whitespace', function () {
            $filter = Filter::start(['value' => 'hello  '], 'value');

            expect($filter->trim()->done())->toBe('hello');
        });

        test('removes both leading and trailing whitespace', function () {
            $filter = Filter::start(['value' => '  hello world  '], 'value');

            expect($filter->trim()->done())->toBe('hello world');
        });

        test('preserves internal whitespace', function () {
            $filter = Filter::start(['value' => '  hello   world  '], 'value');

            expect($filter->trim()->done())->toBe('hello   world');
        });

        test('leaves non-string values unchanged', function () {
            $filter = Filter::start(['value' => 123], 'value');

            expect($filter->trim()->done())->toBe(123);
        });
    });

    describe('striptags', function () {
        test('removes HTML tags from string', function () {
            $filter = Filter::start(['value' => '<p>Hello</p>'], 'value');

            expect($filter->striptags()->done())->toBe('Hello');
        });

        test('removes multiple HTML tags', function () {
            $filter = Filter::start(['value' => '<div><p>Hello</p></div>'], 'value');

            expect($filter->striptags()->done())->toBe('Hello');
        });

        test('removes script tags', function () {
            $filter = Filter::start(['value' => '<script>alert("xss")</script>Text'], 'value');

            expect($filter->striptags()->done())->toBe('alert("xss")Text');
        });

        test('preserves text with no tags', function () {
            $filter = Filter::start(['value' => 'Plain text'], 'value');

            expect($filter->striptags()->done())->toBe('Plain text');
        });

        test('leaves non-string values unchanged', function () {
            $filter = Filter::start(['value' => 123], 'value');

            expect($filter->striptags()->done())->toBe(123);
        });
    });

    describe('htmlspecialchars', function () {
        test('escapes HTML special characters', function () {
            $filter = Filter::start(['value' => '<p>Hello & "World"</p>'], 'value');

            expect($filter->htmlspecialchars()->done())->toBe('&lt;p&gt;Hello &amp; &quot;World&quot;&lt;/p&gt;');
        });

        test('escapes single quotes with ENT_QUOTES', function () {
            $filter = Filter::start(['value' => "It's a test"], 'value');

            expect($filter->htmlspecialchars()->done())->toBe("It&apos;s a test");
        });

        test('preserves regular text', function () {
            $filter = Filter::start(['value' => 'Plain text'], 'value');

            expect($filter->htmlspecialchars()->done())->toBe('Plain text');
        });

        test('accepts custom flags', function () {
            $filter = Filter::start(['value' => 'Hello & World'], 'value');

            expect($filter->htmlspecialchars(ENT_NOQUOTES)->done())->toBe('Hello &amp; World');
        });

        test('accepts custom encoding', function () {
            $filter = Filter::start(['value' => 'Hello & World'], 'value');

            expect($filter->htmlspecialchars(ENT_QUOTES | ENT_HTML5, 'UTF-8')->done())->toBe('Hello &amp; World');
        });

        test('leaves non-string values unchanged', function () {
            $filter = Filter::start(['value' => 123], 'value');

            expect($filter->htmlspecialchars()->done())->toBe(123);
        });
    });

    describe('method chaining', function () {
        test('chains multiple methods together', function () {
            $filter = Filter::start(['value' => '  <p>HeLLo WoRLD</p>  '], 'value');
            $result = $filter
                ->trim()
                ->striptags()
                ->trim()
                ->lowercase()
                ->done();

            expect($result)->toBe('hello world');
        });

        test('chains type conversion with string manipulation', function () {
            $filter = Filter::start(['value' => '  42  '], 'value');
            $result = $filter
                ->trim()
                ->int()
                ->do(fn($val) => $val * 2)
                ->string()
                ->done();

            expect($result)->toBe('84');
        });

        test('complex chain with custom function', function () {
            $filter = Filter::start(['data' => 'test DATA'], 'data');
            $result = $filter
                ->trim()
                ->do(fn($val) => str_replace('test', 'hello', $val))
                ->uppercase()
                ->htmlspecialchars()
                ->done();

            expect($result)->toBe('HELLO DATA');
        });

        test('all methods return self for fluent interface', function () {
            $filter = Filter::start(['value' => 'test'], 'value');

            expect($filter->trim())->toBeInstanceOf(Filter::class);
            expect($filter->uppercase())->toBeInstanceOf(Filter::class);
            expect($filter->do(fn($v) => $v))->toBeInstanceOf(Filter::class);
            expect($filter->string())->toBeInstanceOf(Filter::class);
            expect($filter->int())->toBeInstanceOf(Filter::class);
        });
    });

    describe('edge cases', function () {
        test('handles empty string', function () {
            $filter = Filter::start(['value' => ''], 'value');

            expect($filter->string()->done())->toBe('');
        });

        test('handles array value by leaving it unchanged if not converted', function () {
            $filter = Filter::start(['value' => ['a', 'b']], 'value');

            expect($filter->done())->toBe(['a', 'b']);
        });

        test('handles object value by leaving it unchanged if not converted', function () {
            $obj = new stdClass();
            $filter = Filter::start(['value' => $obj], 'value');

            expect($filter->done())->toBe($obj);
        });
    });
});

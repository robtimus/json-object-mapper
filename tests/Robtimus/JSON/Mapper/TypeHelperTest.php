<?php
namespace Robtimus\JSON\Mapper;

use stdClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use PHPUnit\Framework\TestCase;

class TypeHelperTest extends TestCase {

    // resolveType

    public function testResolveTypeForArray() {
        $type = 'int[]';
        $this->assertEquals($type, TypeHelper::resolveType($type));
    }

    public function testResolveTypeForScalar() {
        $type = 'int';
        $this->assertEquals($type, TypeHelper::resolveType($type));
    }

    public function testResolveTypeForRelativeClassNoNamespace() {
        $type = 'stdClass';
        $class = new ReflectionClass('\stdClass');
        $this->assertEquals('\stdClass', TypeHelper::resolveType($type, $class));
    }

    public function testResolveTypeForRelativeClassNoRelativeClass() {
        $type = 'stdClass';
        $this->assertEquals('\stdClass', TypeHelper::resolveType($type));
    }

    public function testResolveTypeForAbsoluteClass() {
        $type = '\stdClass';
        $this->assertEquals($type, TypeHelper::resolveType($type));
    }

    public function testResolveTypeForRelativeClass() {
        $type = 'JSONParseException';
        $class = new ReflectionClass(new ObjectMapper());
        $this->assertEquals('\\Robtimus\\JSON\\Mapper\\' . $type, TypeHelper::resolveType($type, $class));
    }

    public function testResolveTypeForNonExistingClass() {
        $type = '\JSONParseException';
        $class = new ReflectionClass(new ObjectMapper());
        try {
            TypeHelper::resolveType($type, $class);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Class not found: \JSONParseException", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }
}

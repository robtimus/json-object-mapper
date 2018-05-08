<?php
namespace Robtimus\JSON\Mapper;

use stdClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Robtimus\JSON\Mapper\Descriptors\ClassDescriptor;

class ObjectMapperTest extends TestCase {

    // TODO: add more tests

    // orderProperties

    public function testOrderPropertiesNoAnnotation() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $classAnnotations = array();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, $classAnnotations);

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('z', 'y', 'x'), array_keys($properties));
    }

    public function testOrderPropertiesAlphabetical() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $classAnnotations = array('JSONPropertyOrder' => array('ALPHABETICAL'));

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, $classAnnotations);

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('x', 'y', 'z'), array_keys($properties));
    }

    public function testOrderPropertiesSpecified() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $classAnnotations = array('JSONPropertyOrder' => array('y, x, z'));

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, $classAnnotations);

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('y', 'x', 'z'), array_keys($properties));
    }

    // parseAnnotations

    public function testParseAnnotations() {
        $docComment = <<<DOCCOMMENT
Here is some text

@param int \$x some number  
@return string return value  
@return int second return value  
@JSONProperty  
DOCCOMMENT;

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'parseAnnotations');
        $method->setAccessible(true);

        $parsed = $method->invoke($mapper, $docComment);

        $expected = array(
            'param'        => array('int $x some number'),
            'return'       => array('string return value', 'int second return value'),
            'JSONProperty' => array('')
        );

        $this->assertEquals($expected, $parsed);
    }

    // createInstance

    public function testCreateInstanceWithNoConstructor() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\\JSON\\Mapper\\ClassWithNoConstructor');

        $instance = $method->invoke($mapper, $class);

        $this->assertInstanceOf($class->getName(), $instance);
    }

    public function testCreateInstanceWithPublicNonArgConstructor() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\\JSON\\Mapper\\ClassWithPublicNonArgConstructor');

        $instance = $method->invoke($mapper, $class);

        $this->assertInstanceOf($class->getName(), $instance);
    }

    public function testCreateInstanceWithPublicConstructorWithOptionalArgument() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\\JSON\\Mapper\\ClassWithPublicConstructorWithOptionalArgument');

        $instance = $method->invoke($mapper, $class);

        $this->assertInstanceOf($class->getName(), $instance);
    }

    public function testCreateInstanceWithPublicConstructorWithRequiredArgument() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\\JSON\\Mapper\\ClassWithPublicConstructorWithRequiredArgument');

        $instance = $method->invoke($mapper, $class);

        $this->assertInstanceOf($class->getName(), $instance);
    }

    public function testCreateInstanceWithPrivateNonArgConstructor() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\\JSON\\Mapper\\ClassWithPrivateNonArgConstructor');

        if (method_exists('\ReflectionClass', 'newInstanceWithoutConstructor')) {
            $instance = $method->invoke($mapper, $class);

            $this->assertInstanceOf($class->getName(), $instance);
        } else {
            try {
                $method->invoke($mapper, $class);
            } catch (JSONMappingException $e) {
                $this->assertEquals("Class Robtimus\\JSON\\Mapper\\ClassWithPrivateNonArgConstructor does not have a non-argument constructor", $e->getMessage());
                return;
            }
            $this->fail('Expected a JSONMappingException');
        }
    }

    public function testCreateInstanceWithPrivateConstructorWithOptionalArgument() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\\JSON\\Mapper\\ClassWithPrivateConstructorWithOptionalArgument');

        if (method_exists('\ReflectionClass', 'newInstanceWithoutConstructor')) {
            $instance = $method->invoke($mapper, $class);

            $this->assertInstanceOf($class->getName(), $instance);
        } else {
            try {
                $method->invoke($mapper, $class);
            } catch (JSONMappingException $e) {
                $this->assertEquals("Class Robtimus\\JSON\\Mapper\\ClassWithPrivateConstructorWithOptionalArgument does not have a non-argument constructor", $e->getMessage());
                return;
            }
            $this->fail('Expected a JSONMappingException');
        }
    }

    public function testCreateInstanceWithPrivateConstructorWithRequiredArgument() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\\JSON\\Mapper\\ClassWithPrivateConstructorWithRequiredArgument');

        if (method_exists('\ReflectionClass', 'newInstanceWithoutConstructor')) {
            $instance = $method->invoke($mapper, $class);

            $this->assertInstanceOf($class->getName(), $instance);
        } else {
            try {
                $method->invoke($mapper, $class);
            } catch (JSONMappingException $e) {
                $this->assertEquals("Class Robtimus\\JSON\\Mapper\\ClassWithPrivateConstructorWithRequiredArgument does not have a non-argument constructor", $e->getMessage());
                return;
            }
            $this->fail('Expected a JSONMappingException');
        }
    }

    // getAccessorType

    public function testGetAccessorTypeNotPresent() {
        $mapper = new ObjectMapper();

        $annotations = getAnnotations($mapper, new ReflectionClass(new ClassWithNoAccessorType()));

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        $accessorType = $method->invoke($mapper, $annotations);

        $this->assertEquals('PUBLIC_MEMBER', $accessorType);
    }

    public function testGetAccessorTypeWithValidAccessorType() {
        $mapper = new ObjectMapper();

        $annotations = getAnnotations($mapper, new ReflectionClass(new ClassWithValidAccessorType()));

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        $accessorType = $method->invoke($mapper, $annotations);

        $this->assertEquals('PROPERTY', $accessorType);
    }

    public function testGetAccessorTypeWithInvalidAccessorType() {
        $mapper = new ObjectMapper();

        $annotations = getAnnotations($mapper, new ReflectionClass(new ClassWithInvalidAccessorType()));

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $annotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid value for @JSONAccessorType: FIELD", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // getJSONPropertyName

    public function testGetJSONPropertyNameNoAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $name = $method->invoke($mapper, 'name', array());

        $this->assertEquals('name', $name);
    }

    public function testGetJSONPropertyNameEmptyAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $name = $method->invoke($mapper, 'name', array('JSONProperty' => array('')));

        $this->assertEquals('name', $name);
    }

    public function testGetJSONPropertyNameNonemptyAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $name = $method->invoke($mapper, 'name', array('JSONProperty' => array('otherName')));

        $this->assertEquals('otherName', $name);
    }

    // getSerializer

    public function testGetSerializerNotPresent() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array();

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mapper, $member, $memberAnnotations);

        $this->assertNull($serializer);
    }

    public function testGetSerializerMissingType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONSerializer' => array('')
        );

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, $memberAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @JSONSerializer type for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerScalarType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONSerializer' => array('int')
        );

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, $memberAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid @JSONSerializer type for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerArrayType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONSerializer' => array('\stdClass[]')
        );

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, $memberAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid @JSONSerializer type for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerIncompatibleType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONSerializer' => array('\stdClass')
        );

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, $memberAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid @JSONSerializer type for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializer() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONSerializer' => array('DummySerializer')
        );

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mapper, $member, $memberAnnotations);

        $this->assertInstanceOf('Robtimus\\JSON\\Mapper\\JSONSerializer', $serializer);

        $secondSerializer = $method->invoke($mapper, $member, $memberAnnotations);

        $this->assertSame($serializer, $secondSerializer);
    }

    // getDeserializer

    public function testGetDeserializerNotPresent() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array();

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        $deserializer = $method->invoke($mapper, $member, $memberAnnotations);

        $this->assertNull($deserializer);
    }

    public function testGetDeserializerMissingType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONDeserializer' => array('')
        );

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, $memberAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @JSONDeserializer type for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerScalarType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONDeserializer' => array('int')
        );

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, $memberAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid @JSONDeserializer type for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerArrayType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONDeserializer' => array('\stdClass[]')
        );

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, $memberAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid @JSONDeserializer type for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerIncompatibleType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONDeserializer' => array('\stdClass')
        );

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, $memberAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid @JSONDeserializer type for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializer() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $memberAnnotations = array(
            'JSONDeserializer' => array('DummyDeserializer')
        );

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        $deserializer = $method->invoke($mapper, $member, $memberAnnotations);

        $this->assertInstanceOf('Robtimus\\JSON\\Mapper\\JSONDeserializer', $deserializer);

        $secondDeserializer = $method->invoke($mapper, $member, $memberAnnotations);

        $this->assertSame($deserializer, $secondDeserializer);
    }

    // includeProperty

    public function testIncludePropertyIgnored() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');
        $propertyAnnotations = array(
            'JSONIgnore' => array('')
        );

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'PROPERTY', $propertyAnnotations);

        $this->assertEquals(false, $include);
    }

    public function testIncludePropertyIncluded() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');
        $propertyAnnotations = array(
            'JSONProperty' => array('')
        );

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'NONE', $propertyAnnotations);

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypePublicMemberWithPublicProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'publicProperty');
        $propertyAnnotations = array();

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'PUBLIC_MEMBER', $propertyAnnotations);

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypePublicMemberWithNonPublicProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');
        $propertyAnnotations = array();

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'PUBLIC_MEMBER', $propertyAnnotations);

        $this->assertEquals(false, $include);
    }

    public function testIncludePropertyAccessorTypeProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');
        $propertyAnnotations = array();

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'PROPERTY', $propertyAnnotations);

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypeNonProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');
        $propertyAnnotations = array();

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'METHOD', $propertyAnnotations);

        $this->assertEquals(false, $include);
    }

    // getPropertyType

    public function testGetPropertyTypeNoAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'notAnnotated');
        $docComment = $testProperty->getDocComment();

        $propertyAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testProperty, $propertyAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @var type for property 'notAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetPropertyTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'properlyAnnotated');
        $docComment = $testProperty->getDocComment();

        $propertyAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testProperty, $propertyAnnotations);

        $this->assertEquals('string', $type);
    }

    public function testGetPropertyTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'incorrectlyAnnotated');
        $docComment = $testProperty->getDocComment();

        $propertyAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testProperty, $propertyAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @var type for property 'incorrectlyAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // includeMethod

    public function testIncludeMethodIgnored() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');
        $methodAnnotations = array(
            'JSONIgnore' => array('')
        );

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'METHOD', $methodAnnotations);

        $this->assertEquals(false, $include);
    }

    public function testIncludeMethodIncluded() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');
        $methodAnnotations = array(
            'JSONProperty' => array('')
        );

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'NONE', $methodAnnotations);

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypePublicMemberWithPublicMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getPublicProperty');
        $methodAnnotations = array();

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'PUBLIC_MEMBER', $methodAnnotations);

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypePublicMemberWithNonPublicMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');
        $methodAnnotations = array();

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'PUBLIC_MEMBER', $methodAnnotations);

        $this->assertEquals(false, $include);
    }

    public function testIncludeMethodAccessorTypeMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');
        $methodAnnotations = array();

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'METHOD', $methodAnnotations);

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypeNonMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');
        $methodAnnotations = array();

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'PROPERTY', $methodAnnotations);

        $this->assertEquals(false, $include);
    }

    // extractPropertyNameFromMethod

    public function testExtractPropertyNameFromMethodOnlyPrefix() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'extractPropertyNameFromMethod');
        $method->setAccessible(true);

        $propertyName = $method->invoke($mapper, 'get', 3);

        $this->assertEquals('', $propertyName);
    }

    public function testExtractPropertyNameFromMethodSnakeCase() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'extractPropertyNameFromMethod');
        $method->setAccessible(true);

        $propertyName = $method->invoke($mapper, 'get_some_value', 3);

        $this->assertEquals('some_value', $propertyName);
    }

    public function testExtractPropertyNameFromMethodCamelCase() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'extractPropertyNameFromMethod');
        $method->setAccessible(true);

        $propertyName = $method->invoke($mapper, 'getSomeValue', 3);

        $this->assertEquals('someValue', $propertyName);
    }

    public function testExtractPropertyNameFromMethodUpperCase() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'extractPropertyNameFromMethod');
        $method->setAccessible(true);

        $propertyName = $method->invoke($mapper, 'getSOMEVALUE', 3);

        $this->assertEquals('somevalue', $propertyName);
    }

    // getReturnType

    public function testGetReturnTypeNoAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getNotAnnotated');
        $docComment = $testMethod->getDocComment();

        $methodAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $methodAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @return type for method 'getNotAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetReturnTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getProperlyAnnotated');
        $docComment = $testMethod->getDocComment();

        $methodAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $methodAnnotations);

        $this->assertEquals('string', $type);
    }

    public function testGetReturnTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getIncorrectlyAnnotated');
        $docComment = $testMethod->getDocComment();

        $methodAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $methodAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @return type for method 'getIncorrectlyAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // getParameterType

    public function testGetParameterTypeNoAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getNotAnnotated');
        $docComment = $testMethod->getDocComment();

        $methodAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $methodAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @param type for parameter 'x' of method 'getNotAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetParameterTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getProperlyAnnotated');
        $docComment = $testMethod->getDocComment();

        $methodAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $methodAnnotations);

        $this->assertEquals('int', $type);
    }

    public function testGetParameterTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getIncorrectlyAnnotated');
        $docComment = $testMethod->getDocComment();

        $methodAnnotations = getAnnotations($mapper, $docComment);

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $methodAnnotations);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @param type for parameter 'x' of method 'getIncorrectlyAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // parseParameters

    public function testParseParametersParametersPresent() {
        $mapper = new ObjectMapper();

        $methodAnnotations = array(
            'param' => array(
                'int $x some number',
                'string $s some string',
                'bool $b'
            )
        );

        $method = new ReflectionMethod($mapper, 'parseParameters');
        $method->setAccessible(true);

        $parsed = $method->invoke($mapper, $methodAnnotations);

        $expected = array(
            'x' => 'int',
            's' => 'string',
            'b' => 'bool'
        );

        $this->assertEquals($expected, $parsed);
    }

    public function testParseParametersParametersNotPresent() {
        $mapper = new ObjectMapper();

        $methodAnnotations = array();

        $method = new ReflectionMethod($mapper, 'parseParameters');
        $method->setAccessible(true);

        $parsed = $method->invoke($mapper, $methodAnnotations);

        $expected = array();

        $this->assertEquals($expected, $parsed);
    }
}

// utility functions

function getAnnotations(ObjectMapper $mapper, $docComment) {
    $method = new ReflectionMethod($mapper, 'parseAnnotations');
    $method->setAccessible(true);

    return $method->invoke($mapper, $docComment);
}

// utility classes

class ClassWithNoConstructor {
}

class ClassWithPublicNonArgConstructor {
    public function __construct() {}
}

class ClassWithPublicConstructorWithOptionalArgument {
    public function __construct($x = 0) {}
}

class ClassWithPublicConstructorWithRequiredArgument {
    public function __construct($x) {}
}

class ClassWithPrivateNonArgConstructor {
    private function __construct() {}
}

class ClassWithPrivateConstructorWithOptionalArgument {
    private function __construct($x = 0) {}
}

class ClassWithPrivateConstructorWithRequiredArgument {
    private function __construct($x) {}
}

class ClassWithFieldAndMethods {
    private $x;

    public function __construct($x = 0) {
        $this->x = $x;
    }

    private function getX() {
        return $this->x;
    }

    private function setX($x) {
        $this->x = $x;
    }

    public function assertX(TestCase $testCase, $value) {
        $testCase->assertEquals($value, $this->x);
    }
}

class ClassWithNoAccessorType {
}

/**
 * @JSONAccessorType PROPERTY
 */
class ClassWithValidAccessorType {
}

/**
 * @JSONAccessorType FIELD
 */
class ClassWithInvalidAccessorType {
}

class ClassWithAnnotations {

    protected $notAnnotated;

    /**
     * @var string
     */
    protected $properlyAnnotated;

    /**
     * @var
     */
    protected $incorrectlyAnnotated;

    protected function getNotAnnotated($x) {
    }

    /**
     * @param int $x
     * @return string
     */
    protected function getProperlyAnnotated($x) {
    }

    /**
     * @param
     * @return
     */
    protected function getIncorrectlyAnnotated($x) {
    }
}

class ClassWithPublicAndNonPublicPropertiesAndMethods {

    public $publicProperty;

    protected $nonPublicProperty;

    public function getPublicProperty() {
    }

    protected function getNonPublicProperty() {
    }
}

class DummySerializer implements JSONSerializer {
    public function toJSON($value) {
        return $value;
    }
}

class DummyDeserializer implements JSONDeserializer {
    public function fromJSON($value, $type) {
        return $value;
    }
}

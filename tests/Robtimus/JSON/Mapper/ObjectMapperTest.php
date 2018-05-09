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

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, '');

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('z', 'y', 'x'), array_keys($properties));
    }

    public function testOrderPropertiesEmptyAnnotation() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, '/** @JSONPropertyOrder( ) */');

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('z', 'y', 'x'), array_keys($properties));
    }

    public function testOrderPropertiesAlphabetical() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, '/** @JSONPropertyOrder ( alphabetical = true ) */');

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('x', 'y', 'z'), array_keys($properties));
    }

    public function testOrderPropertiesAlphabeticalFalse() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, '/** @JSONPropertyOrder ( alphabetical = false ) */');

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('z', 'y', 'x'), array_keys($properties));
    }

    public function testOrderPropertiesSpecified() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, '/** @JSONPropertyOrder ( properties = { "y", \'x\', "z" } ) */');

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('y', 'x', 'z'), array_keys($properties));
    }

    public function testOrderPropertiesEmptySpecified() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $classDescriptor, '/** @JSONPropertyOrder ( properties = { } ) */');
        } catch (JSONMappingException $e) {
            $this->assertEquals('Properties must include all properties for class stdClass; expected: z,y,x, given: ', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testOrderPropertiesWithInvalidlyFormattedJSONPropertyOrder() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $classDescriptor, '/** @JSONPropertyOrder ( properties = {  ) */');
        } catch (JSONMappingException $e) {
            $this->assertEquals('Incorrectly formatted @JSONPropertyOrder: @JSONPropertyOrder ( properties = {  )', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
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

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        $accessorType = $method->invoke($mapper, '');

        $this->assertEquals('PUBLIC_MEMBER', $accessorType);
    }

    public function testGetAccessorTypeWithValidAccessorType() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        $accessorType = $method->invoke($mapper, '/** @JSONAccessorType ( value = "PROPERTY" ) */');

        $this->assertEquals('PROPERTY', $accessorType);

        $accessorType = $method->invoke($mapper, '/** @JSONAccessorType ( "PROPERTY" ) */');

        $this->assertEquals('PROPERTY', $accessorType);
    }

    public function testGetAccessorTypeWithInvalidAccessorType() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, "/** @JSONAccessorType(value='FIELD') */");
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid value for @JSONAccessorType: FIELD", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetAccessorTypeWithAccessorTypeWithoutValue() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, '/** @JSONAccessorType */');
        } catch (JSONMappingException $e) {
            $this->assertEquals('Incorrectly formatted @JSONAccessorType: @JSONAccessorType', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetAccessorTypeWithInvalidlyFormattedAccessorType() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, '/** @JSONAccessorType(type="PROPERTY") */');
        } catch (JSONMappingException $e) {
            $this->assertEquals('Incorrectly formatted @JSONAccessorType: @JSONAccessorType(type="PROPERTY")', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // getJSONPropertyName

    public function testGetJSONPropertyNameNoAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $name = $method->invoke($mapper, 'name', '');

        $this->assertEquals('name', $name);
    }

    public function testGetJSONPropertyNameEmptyAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $name = $method->invoke($mapper, 'name', '/** @JSONProperty */');

        $this->assertEquals('name', $name);

        $name = $method->invoke($mapper, 'name', '/** @JSONProperty ( ) */');

        $this->assertEquals('name', $name);
    }

    public function testGetJSONPropertyNameNonEmptyAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $name = $method->invoke($mapper, 'name', '/** @JSONProperty ( name = "otherName" ) */');

        $this->assertEquals('otherName', $name);
    }

    public function testGetJSONPropertyNameInvalidAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, 'name', '/** @JSONProperty ( value = "otherName" ) */');
        } catch (JSONMappingException $e) {
            $this->assertEquals('Incorrectly formatted @JSONProperty: @JSONProperty ( value = "otherName" )', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // getSerializer

    public function testGetSerializerNotPresent() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mapper, $member, '');

        $this->assertNull($serializer);
    }

    public function testGetSerializerMissingType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, '/** @JSONSerialize(using=" ") */');
        } catch (JSONMappingException $e) {
            $this->assertEquals('Incorrectly formatted @JSONSerialize: @JSONSerialize(using=" ")', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerScalarType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, '/** @JSONSerialize(using="int")');
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @JSONSerialize.uses for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerArrayType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, '/** @JSONSerialize(using="\stdClass[]") */');
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @JSONSerialize.uses for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerIncompatibleType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, '/** @JSONSerialize(using="\stdClass") */');
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @JSONSerialize.uses for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializer() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $docComment = '/** @JSONSerialize ( using = "DummySerializer" ) */';

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mapper, $member, $docComment);

        $this->assertInstanceOf('Robtimus\\JSON\\Mapper\\JSONSerializer', $serializer);

        $secondSerializer = $method->invoke($mapper, $member, $docComment);

        $this->assertSame($serializer, $secondSerializer);
    }

    // getDeserializer

    public function testGetDeserializerNotPresent() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        $deserializer = $method->invoke($mapper, $member, '');

        $this->assertNull($deserializer);
    }

    public function testGetDeserializerMissingType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, '/** @JSONDeserialize(using=" ") */');
        } catch (JSONMappingException $e) {
            $this->assertEquals('Incorrectly formatted @JSONDeserialize: @JSONDeserialize(using=" ")', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerScalarType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, '/** @JSONDeserialize(using="int") */');
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @JSONDeserialize.uses for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerArrayType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, '/** @JSONDeserialize(using="\stdClass[]") */');
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @JSONDeserialize.uses for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerIncompatibleType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member, '/** @JSONDeserialize(using="\stdClass") */');
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @JSONDeserialize.uses for property 'x' of class Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializer() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithFieldAndMethods', 'x');
        $docComment = '/** @JSONDeserialize ( using = "DummyDeserializer" ) */';

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        $deserializer = $method->invoke($mapper, $member, $docComment);

        $this->assertInstanceOf('Robtimus\\JSON\\Mapper\\JSONDeserializer', $deserializer);

        $secondDeserializer = $method->invoke($mapper, $member, $docComment);

        $this->assertSame($deserializer, $secondDeserializer);
    }

    // includeProperty

    public function testIncludePropertyIgnored() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'PROPERTY', '/** @JSONIgnore */');

        $this->assertEquals(false, $include);
    }

    public function testIncludePropertyIncluded() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'NONE', '/** @JSONProperty */');

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyIncludedWithName() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'NONE', '/** @JSONProperty(name="property") */');

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypePublicMemberWithPublicProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'publicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'PUBLIC_MEMBER', '');

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypePublicMemberWithNonPublicProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'PUBLIC_MEMBER', '');

        $this->assertEquals(false, $include);
    }

    public function testIncludePropertyAccessorTypeProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'PROPERTY', '');

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypeNonProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, 'METHOD', '');

        $this->assertEquals(false, $include);
    }

    // getPropertyType

    public function testGetPropertyTypeNoAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'notAnnotated');

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testProperty, $testProperty->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @var type for property 'notAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetPropertyTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'properlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testProperty, $testProperty->getDocComment());

        $this->assertEquals('string', $type);
    }

    public function testGetPropertyTypeWithMinimalAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'minimallyAnnotated');

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testProperty, $testProperty->getDocComment());

        $this->assertEquals('string', $type);
    }

    public function testGetPropertyTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'incorrectlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testProperty, $testProperty->getDocComment());
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

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'METHOD', '/** @JSONIgnore */');

        $this->assertEquals(false, $include);
    }

    public function testIncludeMethodIncluded() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'NONE', '/** @JSONProperty */');

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodIncludedWithName() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'NONE', '/** @JSONProperty(name="property") */');

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypePublicMemberWithPublicMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'PUBLIC_MEMBER', '');

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypePublicMemberWithNonPublicMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'PUBLIC_MEMBER', '');

        $this->assertEquals(false, $include);
    }

    public function testIncludeMethodAccessorTypeMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'METHOD', '');

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypeNonMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, 'PROPERTY', '');

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

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $testMethod->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @return type for method 'getNotAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetReturnTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getProperlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $testMethod->getDocComment());

        $this->assertEquals('string', $type);
    }

    public function testGetReturnTypeWithMinimalAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getMinimallyAnnotatedReturn');

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $testMethod->getDocComment());

        $this->assertEquals('string', $type);
    }

    public function testGetReturnTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getIncorrectlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $testMethod->getDocComment());
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

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $testMethod->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @param type for parameter 'x' of method 'getNotAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetParameterTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getProperlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $testMethod->getDocComment());

        $this->assertEquals('int', $type);
    }

    public function testGetParameterTypeWithMinimalAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getMinimallyAnnotatedParam');

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $testMethod->getDocComment());

        $this->assertEquals('int', $type);
    }

    public function testGetParameterTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\\JSON\\Mapper\\ClassWithAnnotations', 'getIncorrectlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $testMethod->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @param type for parameter 'x' of method 'getIncorrectlyAnnotated' of class Robtimus\\JSON\\Mapper\\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

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

class ClassWithAnnotations {

    protected $notAnnotated;

    /**
     * @var string
     */
    protected $properlyAnnotated;

    /** @var string */
    protected $minimallyAnnotated;

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

    /** @param int $x */
    protected function getMinimallyAnnotatedParam($x) {
    }

    /** @return string */
    protected function getMinimallyAnnotatedReturn($x) {
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

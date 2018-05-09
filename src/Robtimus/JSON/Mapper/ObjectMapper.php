<?php
namespace Robtimus\JSON\Mapper;

use stdClass;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Robtimus\JSON\Mapper\Descriptors\ClassDescriptor;
use Robtimus\JSON\Mapper\Descriptors\PropertyDescriptor;

/**
 * ObjectMapper provides functionality for converting objects to and from JSON.
 */
class ObjectMapper {

    private static $ACCESSOR_TYPE_METHOD        = 'METHOD';
    private static $ACCESSOR_TYPE_PROPERTY      = 'PROPERTY';
    private static $ACCESSOR_TYPE_PUBLIC_MEMBER = 'PUBLIC_MEMBER';
    private static $ACCESSOR_TYPE_NONE          = 'NONE';

    /**
     * The class descriptors per fully-qualified class name
     * @var array[string][ClassDescriptor]
     */
    private $classes = array();

    /**
     * A cache for instances, indexed by fully-qualified class names
     * @var array[string][object]
     */
    private $instanceCache = array();

    /**
     * The default serializers per type.
     * @var array[string][JSONSerializer]
     */
    private $defaultSerializers = array();

    /**
     * The default deserializers per type.
     * @var array[string][JSONDeserializer]
     */
    private $defaultDeserializers = array();

    /*********************/
    /* Public properties */
    /*********************/

    /**
     * If false, throw an exception if an undefined property is encountered in JSON. Ignored when formatting.
     * Default value: false
     * @var bool
     */
    public $allowUndefinedProperties = false;

    /**
     * If true, null values are omitted from generated JSON. Ignored when parsing.
     * Default value: false
     * @var bool
     */
    public $omitNullValues = false;

    /**
     * If true, empty arrays are omitted from generated JSON. Ignored when parsing.
     * Default value: false
     * @var bool
     */
    public $omitEmptyArrays = false;

    /***************************************/
    /* default serializers / deserializers */
    /***************************************/

    /**
     * Sets the default JSON serializer for the given type.
     * This will be used for the type unless a JSON serializer is defined for a property.
     * If the given JSON serializer is null, then the default will be unregistered instead.
     * @param ReflectionClass|string $type
     * @param JSONSerializer|null $serializer
     */
    public function setDefaultSerializer($type, JSONSerializer $serializer = null) {
        $type = TypeHelper::normalizeType($type);

        if (!is_null($serializer)) {
            $this->defaultSerializers[$type] = $serializer;
        } else if (array_key_exists($type, $this->defaultSerializers)) {
            unset($this->defaultSerializers[$type]);
        }
    }

    /**
     * Sets the default JSON deserializer for the given type.
     * This will be used for the type unless a JSON deserializer is defined for a property.
     * If the given JSON deserializer is null, then the default will be unregistered instead.
     * @param ReflectionClass|string $type
     * @param JSONDeserializer|null $deserializer
     */
    public function setDefaultDeserializer($type, JSONDeserializer $deserializer = null) {
        $type = TypeHelper::normalizeType($type);

        if (!is_null($deserializer)) {
            $this->defaultDeserializers[$type] = $deserializer;
        } else if (array_key_exists($type, $this->defaultDeserializers)) {
            unset($this->defaultDeserializers[$type]);
        }
    }

    /***********************/
    /* Manual registration */
    /***********************/

    /**
     * Registers a class descriptor, to prevent its class from being automatically inspected
     * @param ClassDescriptor $classDescriptor
     */
    public function registerClass(ClassDescriptor $classDescriptor) {
        $this->classes[$classDescriptor->className()] = $classDescriptor;
    }

    /******************/
    /* object -> JSON */
    /******************/

    /**
     * Converts the given object to JSON.
     * @param object|array object The object or array to convert.
     * @param int options The serialization options. See <a href="http://php.net/manual/en/function.json-encode.php">json_encode</a> for more information.
     * @return string
     * @throws InvalidArgumentException If the argument is not an object, or if it's a stdClass
     * @throws JSONMappingException If the class could not be processed correctly.
     * @throws JSONGenerationException If the class could not be converted to JSON.
     */
    public function toJSON($object, $options = 0) {
        if (!is_array($object) && !is_object($object)) {
            throw new InvalidArgumentException('Only objects and arrays are supported');
        }

        // to prevent loops
        $converted = array();

        if (is_array($object) || is_a($object, 'stdClass')) {
            $jsonObject = $this->toJSONValue($object, $converted);
        } else {
            $jsonObject = $this->toJSONObject($object, $converted);
        }

        $result = json_encode($jsonObject, $options);
        if ($result === false) {
            throw JSONGenerationException::fromLastJSONError();
        }
        return $result;
    }

    private function toJSONObject($object, &$converted) {
        $class = new ReflectionClass($object);
        $className = $class->getName();
        if (!array_key_exists($className, $this->classes)) {
            $this->inspectClass($class);
        }
        if (in_array($object, $converted, true)) {
            throw new JSONGenerationException('Recursion detected');
        }

        $converted[] = $object;

        $jsonObject = new stdClass();

        $classDescriptor = $this->classes[$className];

        $this->addPropertiesToJSONObject($object, $classDescriptor, $jsonObject, $converted);
        $this->addPropertiesFromAnyGetters($object, $classDescriptor, $jsonObject, $converted);

        return $jsonObject;
    }

    private function addPropertiesToJSONObject($object, ClassDescriptor $classDescriptor, stdClass $jsonObject, &$converted) {
        foreach ($classDescriptor->properties() as $name => $property) {
            $getter = $property->getter();
            if (!is_null($getter)) {
                $value = $getter($object);

                $serializer = $property->serializer();
                if (is_null($serializer)){
                    $type = $property->type();
                    if (array_key_exists($type, $this->defaultSerializers)) {
                        $value = $this->defaultSerializers[$type]->toJSON($value);
                    } else {
                        $value = $this->toJSONValue($value, $converted);
                    }
                } else {
                    $value = $serializer->toJSON($value);
                }
                if (!$this->omitValue($value)) {
                    $jsonObject->{$name} = $value;
                }
            }
        }
    }

    private function addPropertiesFromAnyGetters($object, ClassDescriptor $classDescriptor, stdClass $jsonObject, &$converted) {
        foreach ($classDescriptor->anyGetters() as $anyGetter) {
            foreach ($anyGetter($object) as $name => $value) {
                $value = $this->toJSONValue($value, $converted);
                if (!$this->omitValue($value)) {
                    $jsonObject->{$name} = $value;
                }
            }
        }
    }

    private function omitValue(&$value) {
        return (is_null($value) && $this->omitNullValues)
            || (is_array($value) && count($value) === 0 && $this->omitEmptyArrays);
    }

    private function toJSONValue(&$value, &$converted) {
        if (is_array($value)) {
            $jsonValue = array();
            foreach ($value as $val) {
                $jsonValue[] = $this->toJSONValue($val, $converted);
            }
            return $jsonValue;
        }
        if (is_object($value)) {
            if (is_a($value, '\stdClass')) {
                $jsonValue = new stdClass();
                foreach ($value as $name => $val) {
                    $jsonValue->{$name} = $this->toJSONValue($val, $converted);
                }
                return $jsonValue;
            }
            return $this->toJSONObject($value, $converted);
        }
        return $value;
    }

    /******************/
    /* JSON -> object */
    /******************/

    /**
     * Converts the given JSON string to an object.
     * @param string $json The JSON string to convert.
     * @param \ReflectionClass|string the class to convert to. If the JSON string represents an array this must be the element type.
     * @return object
     * @throws JSONMappingException If the class could not be processed correctly.
     * @throws JSONParseException If the class could not be converted to JSON.
     */
    public function fromJSON($json, $class) {
        if (is_string($class)) {
            $class = new ReflectionClass($class);
        }
        if (!is_object($class) || !is_a($class, '\ReflectionClass')) {
            throw new InvalidArgumentException('class must be a class name or ReflectionClass');
        }

        $jsonObject = json_decode($json);
        if (is_null($jsonObject) || $jsonObject === false) {
            throw JSONParseException::fromLastJSONError();
        }
        if (is_array($jsonObject)) {
            return $this->fromJSONValue($jsonObject, $class->getName() . '[]');
        }

        return $this->fromJSONObject($jsonObject, $class);
    }

    private function fromJSONObject(stdClass $jsonObject, ReflectionClass $class) {
        if (!array_key_exists($class->getName(), $this->classes)) {
            $this->inspectClass($class);
        }

        $className = $class->getName();
        $classDescriptor = $this->classes[$className];

        $object = $this->createInstance($class);

        foreach ($jsonObject as $name => $val) {
            $properties = $classDescriptor->properties();

            $setter = array_key_exists($name, $properties) ? $properties[$name]->setter() : null;
            $anySetter = $classDescriptor->anySetter();
            if (!is_null($setter)) {
                $type = $properties[$name]->type();
                $deserializer = $properties[$name]->deserializer();
                if (is_null($deserializer)) {
                    if (array_key_exists($type, $this->defaultDeserializers)) {
                        $value = $this->defaultDeserializers[$type]->fromJSON($val, $type);
                    } else {
                        $value = $this->fromJSONValue($val, $type);
                    }
                } else {
                    $value = $deserializer->fromJSON($val, $type);
                }
                $setter($object, $value);
            } else if (!is_null($anySetter)) {
                $anySetter($object, $name, $val);
            } else if (!$this->allowUndefinedProperties) {
                throw new JSONParseException("Undefined property for class $className: $name");
            }
        }

        return $object;
    }

    private function fromJSONValue(&$value, $type) {
        if (is_null($value)) {
            return $value;
        }

        $value = $this->validateAndNormalizeType($value, $type);
        if (is_array($value)) {
            $elementType = substr($type, 0, -2);
            $array = array();
            foreach ($value as $val) {
                $val = $this->validateAndNormalizeType($val, $elementType);
                $array[] = $this->fromJSONValue($val, $elementType);
            }
            return $array;
        }
        if (is_object($value)) {
            // must be stdClass
            return $this->fromJSONObject($value, new ReflectionClass($type));
        }
        return $value;
    }

    private function validateAndNormalizeType(&$value, $type) {
        if (substr($type, -2) === '[]') {
            $this->validateIsArray($value, $type);
            return $value;
        }
        if (class_exists($type)) {
            $this->validateIsObject($value, $type);
            return $value;
        }
        if ($type === 'boolean' || $type === 'bool') {
            return $this->validateIsBoolean($value);
        }
        if ($type === 'integer' || $type === 'int') {
            return $this->validateIsInt($value);
        }
        if ($type === 'float' || $type === 'double') {
            return $this->validateIsDouble($value);
        }
        if ($type === 'string') {
            return $this->validateIsString($value);
        }
        throw new JSONParseException("Unsupported type: $type");
    }

    private function validateIsArray(&$value, $type) {
        if (!is_array($value)) {
            throw $this->couldNotConvertException($value, $type);
        }
    }

    private function validateIsObject(&$value, $type) {
        if (!is_object($value)) {
            throw $this->couldNotConvertException($value, $type);
        }
    }

    private function validateIsBoolean(&$value) {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        throw $this->couldNotConvertException($value, 'boolean');
    }

    private function validateIsInt(&$value) {
        if (is_int($value)) {
            return $value;
        }
        if (ctype_digit(strval($value))) {
            return intval($value);
        }
        throw $this->couldNotConvertException($value, 'ineger');
    }

    private function validateIsDouble(&$value) {
        if (is_double($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return doubleval($value);
        }
        throw $this->couldNotConvertException($value, 'double');
    }

    private function validateIsString(&$value) {
        if (is_string($value)) {
            return $value;
        }
        if (is_scalar($value)) {
            return strval($value);
        }
        throw $this->couldNotConvertException($value, 'string');
    }

    private function couldNotConvertException(&$value, $type) {
        $valueType = gettype($value);
        if (is_scalar($value)) {
            throw new JSONParseException("Could convert value '$value' of type $valueType to $type");
        } else {
            throw new JSONParseException("Could not convert value of type $valueType to $type");
        }
    }

    /**************/
    /* inspection */
    /**************/

    private function inspectClass(ReflectionClass $class) {
        $className = $class->getName();
        if (array_key_exists($className, $this->classes)) {
            // already inspecting the class, prevent infinite recursion
            return;
        }

        $parentClass = $class->getParentClass();
        if (is_a($parentClass, '\ReflectionClass')) {
            $this->inspectClass($parentClass);
            $classDescriptor = ClassDescriptor::copy($this->classes[$parentClass->getName()], $class);
        } else {
            $classDescriptor = new ClassDescriptor($class);
        }
        $this->classes[$className] = $classDescriptor;

        $classDocComment = $class->getDocComment();
        $accessorType = $this->getAccessorType($classDocComment);

        $this->inspectProperties($class, $classDescriptor, $accessorType);
        $this->inspectMethods($class, $classDescriptor, $accessorType);
        $this->orderProperties($classDescriptor, $classDocComment);
    }

    private function inspectProperties(ReflectionClass $class, ClassDescriptor $classDescriptor, $accessorType) {
        foreach ($class->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() === $classDescriptor->className()) {
                $propertyDocComment = $property->getDocComment();
                if ($this->includeProperty($property, $accessorType, $propertyDocComment)) {
                    $name = $this->getJSONPropertyName($property->getName(), $propertyDocComment);
                    // overwrite what was already specified
                    $classDescriptor->addProperty($name, $this->getPropertyType($property, $propertyDocComment))
                        ->withGetter($this->getPropertyGetter($property, $propertyDocComment))
                        ->withSetter($this->getPropertySetter($property, $propertyDocComment))
                        ->withSerializer($this->getSerializer($property, $propertyDocComment))
                        ->withDeserializer($this->getDeserializer($property, $propertyDocComment));
                }
            }
        }
    }

    private function getPropertyGetter(ReflectionProperty $property, $propertyDocComment) {
        if ($this->hasMarkerAnnotation($propertyDocComment, 'JSONWriteOnly')) {
            return null;
        }
        $property->setAccessible(true);
        return array($property, 'getValue');
    }

    private function getPropertySetter(ReflectionProperty $property, $propertyDocComment) {
        if ($this->hasMarkerAnnotation($propertyDocComment, 'JSONReadOnly')) {
            return null;
        }
        $property->setAccessible(true);
        return array($property, 'setValue');
    }

    private function inspectMethods(ReflectionClass $class, ClassDescriptor $classDescriptor, $accessorType) {
        $anyGetter = null;
        $anySetter = null;

        foreach ($class->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $classDescriptor->className()) {
                $methodDocComment = $method->getDocComment();
                if (!$method->isStatic()) {
                    if ($this->includeMethod($method, $accessorType, $methodDocComment)) {
                        $this->updatePropertyIfNeeded($classDescriptor, $method, $methodDocComment);
                    }
                    if ($this->hasMarkerAnnotation($methodDocComment, 'JSONAnyGetter')) {
                        $this->validateAnyGetterMethod($method, $anyGetter);
                        $anyGetter = $method;
                    }
                    if ($this->hasMarkerAnnotation($methodDocComment, 'JSONAnySetter')) {
                        $this->validateAnySetterMethod($method, $anySetter);
                        $anySetter = $method;
                    }
                }
            }
        }
        if (!is_null($anyGetter)) {
            $classDescriptor->withAnyGetter($this->getMethodCallable($anyGetter));
        }
        if (!is_null($anySetter)) {
            $classDescriptor->withAnySetter($this->getMethodCallable($anySetter));
        }
    }

    private function updatePropertyIfNeeded(ClassDescriptor $classDescriptor, ReflectionMethod $method, $methodDocComment) {
        $methodName = $method->getName();
        if (preg_match('/^(is|get|set)[_A-Z].*/', $methodName, $matches)) {
            if ($matches[1] === 'is' && $method->getNumberOfParameters() === 0) {
                $name = $this->extractPropertyNameFromMethod($methodName, 2);
                $name = $this->getJSONPropertyName($name, $methodDocComment);
                $type = $this->getReturnType($method, $methodDocComment);
                $this->updateProperty($classDescriptor, $method, $name, $type, $methodDocComment, $method, null);
            } else if ($matches[1] === 'get' && $method->getNumberOfParameters() === 0) {
                $name = $this->extractPropertyNameFromMethod($methodName, 3);
                $name = $this->getJSONPropertyName($name, $methodDocComment);
                $type = $this->getReturnType($method, $methodDocComment);
                $this->updateProperty($classDescriptor, $method, $name, $type, $methodDocComment, $method, null);
            } else if ($matches[1] === 'set' && $method->getNumberOfParameters() === 1) {
                $name = $this->extractPropertyNameFromMethod($methodName, 3);
                $name = $this->getJSONPropertyName($name, $methodDocComment);
                $type = $this->getParameterType($method, $methodDocComment);
                $this->updateProperty($classDescriptor, $method, $name, $type, $methodDocComment, null, $setter);
            }
        }
    }

    private function updateProperty(ClassDescriptor $classDescriptor, ReflectionMethod $method, $name, $type, $methodDocComment, ReflectionMethod $getter = null, ReflectionMethod $setter = null) {
        $propertyDescriptor = $classDescriptor->ensureProperty($name, $type);
        if (!is_null($getter)) {
            $propertyDescriptor->withGetter($this->getMethodCallable($getter));
            $propertyDescriptor->withSerializer($this->getSerializer($method, $methodDocComment));
        }
        if (!is_null($setter)) {
            $propertyDescriptor->withSetter($this->getMethodCallable($setter));
            $propertyDescriptor->withDeserializer($this->getDeserializer($method, $methodDocComment));
        }
    }

    private function validateAnyGetterMethod(ReflectionMethod $method, ReflectionMethod $existingAnyGetter = null) {
        if ($method->getNumberOfParameters() !== 0) {
            $methodName = $method->getName();
            $className = $method->getDeclaringClass()->getName();
            throw new JSONMappingException("@JSONAnyGetter method '$methodName' of class $className should not have any parameters");
        }
        if (!is_null($existingAnyGetter)) {
            throw new JSONMappingException("Only one method annotated with @JSONAnyGetter allowed for class $className");
        }
    }

    private function validateAnySetterMethod(ReflectionMethod $method, ReflectionMethod $existingAnySetter = null) {
        if ($method->getNumberOfParameters() !== 2) {
            $methodName = $method->getName();
            $className = $method->getDeclaringClass()->getName();
            throw new JSONMappingException("@JSONAnySetter method '$methodName' of class $className should have two parameters");
        }
        if (!is_null($existingAnySetter)) {
            throw new JSONMappingException("Only one method annotated with @JSONAnyGetter allowed for class $className");
        }
    }

    private function getMethodCallable(ReflectionMethod $method) {
        $method->setAccessible(true);
        return array($method, 'invoke');
    }

    private function orderProperties(ClassDescriptor $classDescriptor, $classDocComment) {
        if (preg_match('/@JSONPropertyOrder(?=\s|\()(?:\s*\((.*?)\))?/m', $classDocComment, $annotationMatches)) {
            $annotation = $annotationMatches[0];

            if (count($annotationMatches) > 1) {
                if (preg_match('/^\s*$/m', $annotationMatches[1])) {
                    // empty annotation, allow
                    return;
                }
                if (preg_match('/^\s*alphabetical\s*=\s*(true|false)\s*$/m', $annotationMatches[1], $matches)) {
                    if ($matches[1] === 'true') {
                        $classDescriptor->orderProperties();
                    }
                    return;
                }
                if (preg_match('/^\s*properties\s*=\s*\{\s*([^}]*)\s*\}\s*$/m', $annotationMatches[1], $matches)) {
                    if (preg_match_all('/(?<=^|,)\s*(["\'])(.*?)\1\s*(?=,|$)/m', $matches[1], $matches)) {
                        $propertyNames = $matches[2];
                    } else {
                        $propertyNames = array();
                    }
                    $classDescriptor->orderProperties($propertyNames);
                    return;
                }
            }
            throw new JSONMappingException("Incorrectly formatted @JSONPropertyOrder: $annotation");
        }
    }

    /******************/
    /* helper methods */
    /******************/

    // generic

    private function hasMarkerAnnotation($docComment, $annotationName) {
        if (preg_match('/@' . $annotationName . '(?=\s|\()(?:\s*\(\s*(\S+)\s*\))?/m', $docComment, $annotationMatches)) {
            $annotation = $annotationMatches[0];

            if (count($annotationMatches) > 1) {
                throw new JSONMappingException("Incorrectly formatted @$annotationName: $annotation");
            }

            return true;
        }
        return false;
    }

    // reflection

    private function createInstance(ReflectionClass $class) {
        $constructor = $class->getConstructor();
        if (is_null($constructor) || ($constructor->getNumberOfRequiredParameters() === 0 && $constructor->isPublic())) {
            return $class->newInstanceArgs();
        }
        if (method_exists('\ReflectionClass', 'newInstanceWithoutConstructor')) {
            $instance = $class->newInstanceWithoutConstructor();
            if ($constructor->getNumberOfRequiredParameters() === 0) {
                $constructor->setAccessible(true);
                $constructor->invoke($instance);
            }
            return $instance;
        }
        $className = $class->getName();
        throw new JSONMappingException("Class $className does not have a non-argument constructor");
    }

    // classes

    private function getAccessorType($classDocComment) {
        if (preg_match('/@JSONAccessorType(?=\s|\()(?:\s*\((.*)\))?/m', $classDocComment, $annotationMatches)) {
            $annotation = $annotationMatches[0];

            if (count($annotationMatches) > 1 && preg_match('/^\s*(?:value\s*=\s*)?(["\'])(.*?)\1\s*$/m', $annotationMatches[1], $matches)) {
                $accessorType = $matches[2];
                $validAccessorTypes = array(
                    self::$ACCESSOR_TYPE_METHOD,
                    self::$ACCESSOR_TYPE_PROPERTY,
                    self::$ACCESSOR_TYPE_PUBLIC_MEMBER,
                    self::$ACCESSOR_TYPE_NONE,
                );
                if (in_array($accessorType, $validAccessorTypes)) {
                    return $accessorType;
                }
                throw new JSONMappingException("Invalid value for @JSONAccessorType: $accessorType");
            }
            throw new JSONMappingException("Incorrectly formatted @JSONAccessorType: $annotation");
        }
        return self::$ACCESSOR_TYPE_PUBLIC_MEMBER;
    }

    // members

    private function includeMember($member, $accessorType, $memberDocComment, $expectedMemberAccessorType) {
        if ($this->hasMarkerAnnotation($memberDocComment, 'JSONIgnore')) {
            return false;
        }
        if (preg_match('/@JSONProperty(?=\s|\()(?:\s*\(.*\))?/m', $memberDocComment)) {
            return true;
        }
        return ($accessorType === self::$ACCESSOR_TYPE_PUBLIC_MEMBER && $member->isPublic()) || $accessorType === $expectedMemberAccessorType;
    }

    private function getJSONPropertyName($candidate, $memberDocComment) {
        if (preg_match('/@JSONProperty(?=\s|\()(?:\s*\((.*)\))?/m', $memberDocComment, $annotationMatches)) {
            $annotation = $annotationMatches[0];

            if (count($annotationMatches) > 1) {
                if (preg_match('/^\s*name\s*=\s*(["\'])(.+?)\1\s*$/m', $annotationMatches[1], $matches)) {
                    return $matches[2];
                }
                if (!preg_match('/^\s*$/m', $annotationMatches[1])) {
                    throw new JSONMappingException("Incorrectly formatted @JSONProperty: $annotation");
                }
                // else empty annotation, use the candidate
            }
        }
        return $candidate;
    }

    private function getSerializer($member, $memberDocComment) {
        return $this->getSerializerOrDeserializer($member, $memberDocComment, 'JSONSerialize', 'JSONSerializer');
    }

    private function getDeserializer($member, $memberDocComment) {
        return $this->getSerializerOrDeserializer($member, $memberDocComment, 'JSONDeserialize', 'JSONDeserializer');
    }

    private function getSerializerOrDeserializer($member, $memberDocComment, $annotationName, $interface) {
        if (preg_match('/@' . $annotationName . '(?=\s|\()(?:\s*\((.*)\))?/m', $memberDocComment, $annotationMatches)) {
            $annotation = $annotationMatches[0];

            if (count($annotationMatches) > 1) {
                if (preg_match('/^\s*using\s*=\s*(["\'])(\S+?)\1\s*$/m', $annotationMatches[1], $matches)) {
                    $type = $matches[2];
                    $type = explode('|', $type)[0];
                    $type = TypeHelper::resolveType($type, $member->getDeclaringClass());
                    if (TypeHelper::isScalarType($type) || substr($type, -2) === '[]') {
                        $memberType = is_a('\ReflectionMethod', $member) ? 'method' : 'property';
                        $memberName = $member->getName();
                        $className = $member->getDeclaringClass()->getName();
                        throw new JSONMappingException("Invalid type in @$annotationName.uses for $memberType '$memberName' of class $className");
                    }

                    $class = new ReflectionClass($type);
                    if (!$class->implementsInterface('Robtimus\\JSON\\Mapper\\' . $interface)) {
                        $memberType = is_a('\ReflectionMethod', $member) ? 'method' : 'property';
                        $memberName = $member->getName();
                        $className = $member->getDeclaringClass()->getName();
                        throw new JSONMappingException("Invalid type in @$annotationName.uses for $memberType '$memberName' of class $className");
                    }

                    if (!array_key_exists($type, $this->instanceCache)) {
                        $this->instanceCache[$type] = $this->createInstance($class);
                    }
                    return $this->instanceCache[$type];
                }
            }
            throw new JSONMappingException("Incorrectly formatted @$annotationName: $annotation");
        }
        return null;
    }

    // properties

    private function includeProperty($property, $accessorType, $propertyDocComment) {
        return $this->includeMember($property, $accessorType, $propertyDocComment, self::$ACCESSOR_TYPE_PROPERTY);
    }

    private function getPropertyType($property, $propertyDocComment) {
        if (preg_match('/@var(?:\s+(\S+))?/m', $propertyDocComment, $annotationMatches)) {
            $type = $annotationMatches[1];
            $type = explode('|', $type)[0];
            if ($type !== '' && !preg_match('/^\*+\/$/', $type)) {
                return TypeHelper::resolveType($type, $property->getDeclaringClass());
            }
        }
        $propertyName = $property->getName();
        $className = $property->getDeclaringClass()->getName();
        throw new JSONMappingException("Missing @var type for property '$propertyName' of class $className");
    }

    // methods

    private function includeMethod($method, $accessorType, $methodDocComment) {
        return $this->includeMember($method, $accessorType, $methodDocComment, self::$ACCESSOR_TYPE_METHOD);
    }

    private function extractPropertyNameFromMethod($methodName, $prefixLength) {
        $result = substr($methodName, $prefixLength);
        while (substr($result, 0, 1) === '_') {
            $result = substr($result, 1);
        }
        if (strlen($result) > 1 && strtoupper($result) === $result) {
            // fully uppercase, turn into fully lowercase
            return strtolower($result);
        }
        return $result === '' ? '' : strtolower(substr($result, 0, 1)) . substr($result, 1);
    }

    private function getReturnType(ReflectionMethod $method, $methodDocComment) {
        if (preg_match('/@return(?:\s+(\S+))?/m', $methodDocComment, $annotationMatches)) {
            $type = $annotationMatches[1];
            $type = explode('|', $type)[0];
            if ($type !== '' && !preg_match('/^\*+\/$/', $type)) {
                return TypeHelper::resolveType($type, $method->getDeclaringClass());
            }
        }
        $methodName = $method->getName();
        $className = $method->getDeclaringClass()->getName();
        throw new JSONMappingException("Missing @return type for method '$methodName' of class $className");
    }

    private function getParameterType(ReflectionMethod $method, $methodDocComment) {
        $parameterName = $method->getParameters()[0]->getName();
        if (preg_match('/@param\s+(\S+)\s+\$' . $parameterName . '/m', $methodDocComment, $annotationMatches)) {
            $type = $annotationMatches[1];
            $type = explode('|', $type)[0];
            if ($type !== '' && !preg_match('/^\*+\/$/', $type)) {
                return TypeHelper::resolveType($type, $method->getDeclaringClass());
            }
        }
        $methodName = $method->getName();
        $className = $method->getDeclaringClass()->getName();
        throw new JSONMappingException("Missing @param type for parameter '$parameterName' of method '$methodName' of class $className");
    }
}

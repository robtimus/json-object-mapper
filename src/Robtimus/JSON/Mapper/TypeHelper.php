<?php
namespace Robtimus\JSON\Mapper;

use ReflectionClass;

/**
 * @internal Helper class for types.
 */
final class TypeHelper {

    static function normalizeType($type, ReflectionClass $relativeTo = null) {
        if (is_a($type, '\ReflectionClass')) {
            return $type->getName();
        }
        if (!is_string($type)) {
            throw new InvalidArgumentException('type must be a type name or ReflectionClass');
        }
        return TypeHelper::resolveType($type, $relativeTo);
    }

    static function resolveType($type, ReflectionClass $relativeTo = null) {
        if (substr($type, -2) === '[]') {
            return self::resolveType(substr($type, 0, -2), $relativeTo) . '[]';
        }
        if (self::isScalarType($type)) {
            return $type;
        }
        if (substr($type, 0, 1) !== '\\') {
            $namespace = is_null($relativeTo) ? '' : $relativeTo->getNamespaceName();
            $prefix = $namespace === '' ? '' : ('\\' . $namespace);
            $type = $prefix . '\\' . $type;
        }
        if (class_exists($type)) {
            return $type;
        }
        throw new JSONMappingException("Class not found: $type");
    }

    static function isScalarType($type) {
        static $simpleTypes = array('boolean', 'bool', 'integer', 'int', 'float', 'double', 'string');
        return in_array($type, $simpleTypes, true);
    }
}

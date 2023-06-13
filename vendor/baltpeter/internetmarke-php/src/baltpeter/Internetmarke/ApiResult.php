<?php

namespace baltpeter\Internetmarke;

abstract class ApiResult {
    public static function fromStdObject($std_object) {
        $class_name = get_called_class();
        // You might call this a rather convoluted way of achieving the intended result...
        $object = new $class_name(...array_fill(0, ((new \ReflectionMethod($class_name, '__construct'))->getNumberOfParameters()), null));

        if($std_object) {
            foreach($std_object as $property => $value) {
                // taken from https://github.com/doctrine/inflector by the Doctrine Project, Inflector::tableize()
                $snake_property = mb_strtolower(preg_replace('~(?<=\\w)([A-Z])~u', '_$1', $property));

                if(is_object($value)) {
                    // taken from https://github.com/doctrine/inflector by the Doctrine Project, Inflector::classify()
                    $value_class_name = __NAMESPACE__ . '\\' . str_replace([' ', '_', '-'], '', ucwords($property, ' _-'));
                    if(class_exists($value_class_name) && is_subclass_of($value_class_name, get_class())) {
                        $value = $value_class_name::fromStdObject($value);
                    }
                }

                if(property_exists(get_called_class(), $property)) {
                    $object->$property = $value;
                } elseif(property_exists(get_called_class(), $snake_property)) {
                    $object->$snake_property = $value;
                }
            }
        }

        return $object;
    }
}

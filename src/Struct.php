<?php

namespace Tommyknocker\Struct;

use \Exception;


abstract class Struct
{
    /**
     * Strict mode flag
     * @var bool
     */
    protected $strict = true;

    /**
     * @var array
     */
    protected $template = [];

    /**
     * @var array
     */
    protected $data = [];


    public function __construct(array $data = [])
    {
        if ($data) {
            $this->loadFromArray($data);
        }
    }

    /**
     * Get data from container
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Load data and check it by defined template
     * @param array $data
     * @throws Exception
     */
    private function loadFromArray(array $data)
    {
        $this->recurciveLoad($this->template, $data, $this->data);
    }

    /**
     * @param array $template
     * @param array $data
     * @param array $storage
     * @param string $fullKey
     * @throws Exception
     */
    private function recurciveLoad($template, $data, &$storage, $fullKey = '')
    {
        foreach ($template as $templateKey => $templateValue) {
            $currentKey = $fullKey ? $fullKey . '.' . $templateKey : $templateKey;

            $storage[$templateKey] = null;

            $canBeNull = false;
            if (!is_array($templateValue) && strpos($templateValue, '?') === 0) {
                $canBeNull = true;
                $templateValue = substr($templateValue, 1);
            }

            if (array_key_exists($templateKey, $data)) {
                if (is_array($templateValue)) {
                    $this->recurciveLoad($templateValue, $data[$templateKey], $storage[$templateKey], $currentKey);
                } else {
                    $value = $data[$templateKey];

                    if ($canBeNull && is_null($value)) {
                        continue;
                    }

                    switch ($templateValue) {
                        case 'bool':
                            $castedValue = (bool) $value;
                            $isCorrect = $this->strict ? is_bool($value) : $castedValue == $value;
                            break;
                        case 'int':
                            $castedValue = (int) $value;
                            $isCorrect = $this->strict ? is_int($value) : $castedValue == $value;
                            break;
                        case 'float':
                            $castedValue = (float) $value;
                            $isCorrect = $this->strict ? is_float($value) : $castedValue == $value;
                            break;
                        case 'string':
                            $castedValue = (string) $value;
                            $isCorrect = $this->strict ? is_string($value) : $castedValue == $value;
                            break;
                        case 'array':
                            $castedValue = $value;
                            $isCorrect = is_array($value);
                            break;
                        case 'object':
                            $castedValue = $value;
                            $isCorrect = is_object($value);
                            break;
                        default:
                            throw new Exception('Unsupported type ' . $templateValue);
                    }

                    if (!$isCorrect) {
                        throw new Exception('Value for key ' . $currentKey . ' must be ' . $templateValue);
                    }

                    $storage[$templateKey] = $this->strict ? $value : $castedValue;
                }
            } else {
                if(!$canBeNull) {
                    throw new Exception('Given data does not have required key in path ' . $currentKey);
                }
            }
        }
    }
}
<?php

declare(strict_types=1);

/**
 * Copyright (c) 2013-2017 OpenCFP
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/opencfp/opencfp
 */

namespace OpenCFP\Http\Form;

abstract class Form
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var string[]
     */
    protected $messages;

    /**
     * @var array
     */
    protected $cleanData;

    /**
     * @var array
     */
    protected $taintedData;

    /**
     * @var \HTMLPurifier
     */
    protected $purifier;

    /**
     * @var string[]
     */
    protected $fieldList = [];

    /**
     * @param $data array of form data
     * @param \HTMLPurifier $purifier
     * @param $options
     */
    public function __construct($data, \HTMLPurifier $purifier, array $options = [])
    {
        $this->purifier    = $purifier;
        $this->options     = $options;
        $this->messages    = [];
        $this->cleanData   = [];
        $this->taintedData = [];

        $this->populate($data);
    }

    /**
     * Populates the form with default data, clears previously set sanitized data
     *
     * @param array $data
     */
    public function populate(array $data)
    {
        $this->taintedData = $data;
        $this->cleanData   = null;
    }

    /**
     * Updates the form's data.
     *
     * @param array $data
     */
    public function update(array $data)
    {
        // The $taintedData property might have been already set by
        // the populate() method.
        $this->taintedData = \array_merge($this->taintedData, (array) $data);
    }

    /**
     * Method that validates that we have all required
     * fields in our submitted data
     *
     * @return bool
     */
    public function hasRequiredFields(): bool
    {
        $dataKeys    = \array_keys($this->taintedData);
        $foundFields = \array_intersect($this->fieldList, $dataKeys);

        return $foundFields == $this->fieldList;
    }

    /**
     * Returns the clean data.
     *
     * @array array $keys The wanted data
     *
     * @param array $keys
     *
     * @return array The cleaned data
     */
    public function getCleanData(array $keys = [])
    {
        if (empty($keys)) {
            return $this->cleanData;
        }

        $data = [];

        foreach ($keys as $key) {
            if (isset($this->cleanData[$key])) {
                $data[$key] = $this->cleanData[$key];
            }
        }

        return $data;
    }

    /**
     * Returns the value of a tainted data by its name.
     *
     * @param string $name    The tainted value name
     * @param mixed  $default The default value to return if not set
     *
     * @return null|mixed
     */
    public function getTaintedField($name, $default = null)
    {
        return $this->taintedData[$name] ?? $default;
    }

    /**
     * Returns the tainted data.
     *
     * @return array
     */
    public function getTaintedData(): array
    {
        return $this->taintedData;
    }

    /**
     * Returns the value of a form's option if set.
     *
     * @param string     $name    The option name
     * @param null|mixed $default The default value
     *
     * @return mixed The option value
     */
    public function getOption($name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Validates the form's submitted data.
     */
    abstract public function validateAll($action = 'create');

    /**
     * Returns the list of error messages.
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        return $this->messages;
    }

    /**
     * Returns whether or not the form has error messages.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return \count($this->messages) > 0;
    }

    /**
     * Method that adds error message to our class attribute, making sure to
     * not add anything that is in there already
     *
     * @param string $message The error messages to add to the list
     */
    protected function addErrorMessage($message)
    {
        if (!\in_array($message, $this->messages)) {
            $this->messages[] = $message;
        }
    }

    /**
     * Method that sanitizes all data
     */
    public function sanitize()
    {
        $this->cleanData = $this->internalSanitize($this->taintedData);
    }

    /**
     * Sanitizes all fields that were submitted.
     *
     * @param array $taintedData The tainted data
     *
     * @return array Sanitized data
     */
    protected function internalSanitize(array $taintedData): array
    {
        $purifier = $this->purifier;
        $filtered = \array_map(
            function ($field) use ($purifier) {
                return $purifier->purify($field);
            },
            $taintedData
        );

        return $filtered;
    }
}

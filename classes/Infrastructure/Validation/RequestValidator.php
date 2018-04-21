<?php

declare(strict_types=1);

/**
 * Copyright (c) 2013-2018 OpenCFP
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/opencfp/opencfp
 */

namespace OpenCFP\Infrastructure\Validation;

use Illuminate\Validation\Factory;
use OpenCFP\Domain\ValidationException;
use Symfony\Component\HttpFoundation\Request;

class RequestValidator
{
    /**
     * @var Factory
     */
    private $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param Request $request
     * @param array   $rules
     * @param array   $messages
     *
     * @throws ValidationException
     */
    public function validate(Request $request, array $rules, array $messages = [])
    {
        $data = $request->query->all() + $request->request->all() + $request->files->all();

        $validator = $this->factory->make(
            $data,
            $rules,
            $messages
        );

        if ($validator->fails()) {
            throw ValidationException::withErrors(array_flatten($validator->errors()->toArray()));
        }
    }
}

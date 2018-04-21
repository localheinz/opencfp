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

namespace OpenCFP\Test\Unit\Infrastructure\Validation;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;
use Localheinz\Test\Util\Helper;
use OpenCFP\Domain\ValidationException;
use OpenCFP\Infrastructure\Validation\RequestValidator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

final class RequestValidatorTest extends TestCase
{
    use Helper;

    /**
     * @test
     */
    public function requestIsValid()
    {
        $request = new Request();
        $request->request->set('foo', 'bar');

        $validator = $this->prophesize(Validator::class);
        $validator->fails()->willReturn(false);

        $factory = $this->prophesize(Factory::class);
        $factory->make(['foo' => 'bar'], ['foo' => 'required'])
            ->willReturn($validator->reveal());

        $requestValidator = new RequestValidator($factory->reveal());
        $requestValidator->validate($request, ['foo' => 'required']);

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function requestIsInvalid()
    {
        $request = new Request();
        $request->request->set('foo', 'bar');

        $validator = $this->prophesize(Validator::class);
        $validator->fails()->willReturn(true);
        $validator->errors()->willReturn(new MessageBag());

        $factory = $this->prophesize(Factory::class);
        $factory->make(['foo' => 'bar'], ['foo' => 'required'])
            ->willReturn($validator->reveal());

        $requestValidator = new RequestValidator($factory->reveal());

        $this->expectException(ValidationException::class);
        $requestValidator->validate($request, ['foo' => 'required']);
    }

    /**
     * @test
     */
    public function messagesCanBeSpecified()
    {
        $faker = $this->faker();

        $request = new Request();

        $request->query = new ParameterBag(\array_combine(
            $faker->words,
            $faker->sentences
        ));

        $request->request = new ParameterBag(\array_combine(
            $faker->words,
            $faker->sentences
        ));

        $request->files = new ParameterBag(\array_combine(
            $faker->words,
            $faker->sentences
        ));

        $rules = \array_combine(
            $faker->words,
            $faker->sentences
        );

        $messages = \array_combine(
            $faker->words,
            $faker->sentences
        );

        $validator = $this->prophesize(Validator::class);

        $validator
            ->fails()
            ->shouldBeCalled()
            ->willReturn(false);

        $factory = $this->prophesize(Factory::class);

        $data = $request->query->all() + $request->request->all() + $request->files->all();

        $factory
            ->make(
                Argument::is($data),
                Argument::is($rules),
                Argument::is($messages)
            )
            ->shouldBeCalled()
            ->willReturn($validator->reveal());

        $requestValidator = new RequestValidator($factory->reveal());

        $requestValidator->validate(
            $request,
            $rules,
            $messages
        );
    }
}

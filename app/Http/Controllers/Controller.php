<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

abstract class Controller
{
    /**
     * Validate request using a Data object
     * 
     * @template T of Data
     * @param Request $request
     * @param class-string<T> $dataClass
     * @return T
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateWith(Request $request, string $dataClass): Data
    {
        if (!is_subclass_of($dataClass, Data::class)) {
            throw new \InvalidArgumentException("Class {$dataClass} must extend " . Data::class);
        }

        return $dataClass::validate($request->all());
    }

    /**
     * Override Laravel's validate method to encourage Data object usage
     * 
     * @deprecated Use validateWith() with a Data class instead
     * @throws \BadMethodCallException
     */
    public function validate(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        throw new \BadMethodCallException(
            'Direct validation is deprecated. Use Data Transfer Objects for validation. ' .
            'Example: $data = CreateOrderData::validateAndCreate($request); ' .
            'See CLAUDE.md for Laravel-Data usage requirements.'
        );
    }

    /**
     * Wrap data for API response
     * 
     * @param mixed $data
     * @param string|null $wrap
     * @return array
     */
    protected function wrapForApi(mixed $data, ?string $wrap = 'data'): array
    {
        if ($data instanceof Data) {
            return $data->wrap($wrap)->toArray();
        }

        return $wrap ? [$wrap => $data] : (array) $data;
    }
}

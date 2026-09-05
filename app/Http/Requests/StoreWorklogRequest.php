<?php

namespace App\Http\Requests;

use App\Support\DurationParser;
use App\Support\WorklogDateParser;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

final class StoreWorklogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(DurationParser $durationParser, WorklogDateParser $dateParser): array
    {
        return [
            'ticket' => ['required', 'string', 'regex:/^[A-Za-z][A-Za-z0-9]+-[0-9]+$/'],
            'duration' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($durationParser): void {
                    try {
                        $durationParser->parse((string) $value);
                    } catch (InvalidArgumentException) {
                        $fail('The duration is invalid.');
                    }
                },
            ],
            'date' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($dateParser): void {
                    try {
                        $dateParser->parse((string) $value, '00:00');
                    } catch (InvalidArgumentException) {
                        $fail('The date is invalid.');
                    }
                },
            ],
            'time' => [
                'nullable',
                'required_with:date',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($dateParser): void {
                    try {
                        $dateParser->parse(null, (string) $value);
                    } catch (InvalidArgumentException) {
                        $fail('The time is invalid.');
                    }
                },
            ],
        ];
    }
}

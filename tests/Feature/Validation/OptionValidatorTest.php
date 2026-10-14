<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use RVxLab\Metronome\Validation\OptionValidator;

it('can validate correctly', function (): void {
    $validator = new OptionValidator();

    expect(fn (): Illuminate\Contracts\Validation\Validator => $validator->validate([
        'tick-rate' => 1.0,
    ]))->not->toThrow(ValidationException::class);
});

it('rejects invalid tick rates', function (mixed $value, string $expectedMessage): void {
    $validator = new OptionValidator();

    try {
        $validator->validate([
            'tick-rate' => $value,
        ]);
    } catch (ValidationException $validationException) {
        $errors = $validationException->errors();

        expect($errors)->toEqual([
            'tick-rate' => [ $expectedMessage ],
        ]);

        return;
    }

    $this->fail(sprintf('Failed to assert that a validation exception was thrown for %s', json_encode($value)));
})->with([
    'zero' => [ 0, 'The tick rate must be at least 0.01.' ],
    'negative integer' => [ -1, 'The tick rate must be at least 0.01.' ],
    'negative float' => [ -0.5, 'The tick rate must be at least 0.01.' ],
    'null' => [ null, 'The tick rate is required.' ],
    'non-numeric string' => [ 'fast', 'The tick rate must be a number.' ],
    'boolean' => [ true, 'The tick rate must be a number.' ],
    'array' => [ [ 1 ], 'The tick rate must be a number.' ],
]);

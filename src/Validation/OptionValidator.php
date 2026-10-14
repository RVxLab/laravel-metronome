<?php

declare(strict_types=1);

namespace RVxLab\Metronome\Validation;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

/**
 * @internal
 */
final class OptionValidator
{
    /**
     * @param array{
     *     tick-rate: int|float,
     * } $data
     */
    public function validate(array $data): Validator
    {
        $validator = ValidatorFacade::make($data, [
            'tick-rate' => [ 'required', 'numeric', 'min:0.01' ],
        ], [
            'tick-rate' => 'The tick rate is required.',
            'tick-rate.numeric' => 'The tick rate must be a number.',
            'tick-rate.min' => 'The tick rate must be at least :min.',
        ]);

        $validator->validate();

        return $validator;
    }
}

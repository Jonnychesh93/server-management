<?php

namespace App\Http\Requests\Crons;

use App\Models\Cron;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCronRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [Cron::class, $this->route('server')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'command' => ['required', 'string', 'max:1000'],
            'user' => ['required', 'string', 'max:255'],
            'schedule' => ['required', 'string', 'max:255', 'regex:/^(\S+\s+){4}\S+$/'],
        ];
    }
}

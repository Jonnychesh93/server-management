<?php

namespace App\Http\Requests\Daemons;

use App\Models\Daemon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDaemonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [Daemon::class, $this->route('server')]);
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
            'directory' => ['required', 'string', 'max:255'],
            'user' => ['required', 'string', 'max:255'],
            'processes' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}

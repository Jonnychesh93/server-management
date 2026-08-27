<?php

namespace App\Http\Requests\Databases;

use App\Models\Database;
use App\Models\Server;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDatabaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', [Database::class, $this->route('server')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Server $server */
        $server = $this->route('server');

        return [
            'name' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('databases')->where('server_id', $server->id),
            ],
            'username' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('databases')->where('server_id', $server->id),
            ],
        ];
    }
}

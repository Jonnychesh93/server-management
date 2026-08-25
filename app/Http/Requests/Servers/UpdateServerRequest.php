<?php

namespace App\Http\Requests\Servers;

use App\Models\Server;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('server'));
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
                'max:255',
                Rule::unique(Server::class)->where('team_id', $server->team_id)->ignore($server->id),
            ],
            'ip_address' => ['required', 'ip'],
            'ssh_port' => ['required', 'integer', 'between:1,65535'],
            'ssh_user' => ['required', 'string', 'max:255'],
        ];
    }
}

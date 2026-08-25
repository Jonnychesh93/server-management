<?php

namespace App\Http\Requests\Servers;

use App\Enums\BootstrapCredentialType;
use App\Enums\ServerOs;
use App\Models\Server;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Server::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Server::class)->where('team_id', $this->user()->current_team_id),
            ],
            'ip_address' => ['required', 'ip'],
            'ssh_port' => ['required', 'integer', 'between:1,65535'],
            'ssh_user' => ['required', 'string', 'max:255'],
            'os' => ['required', Rule::enum(ServerOs::class)],
            'bootstrap_credential_type' => ['required', Rule::enum(BootstrapCredentialType::class)],
            'bootstrap_credential' => ['required', 'string'],
        ];
    }
}

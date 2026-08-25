<?php

namespace App\Http\Requests\Sites;

use App\Models\Server;
use App\Models\Site;
use App\Services\Provisioning\Steps\InstallPhp;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Site::class);
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
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))+$/i',
                Rule::unique(Site::class)->where('server_id', $server->id),
            ],
            'php_version' => ['required', Rule::in(InstallPhp::SUPPORTED_VERSIONS)],
            'repository' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
        ];
    }
}

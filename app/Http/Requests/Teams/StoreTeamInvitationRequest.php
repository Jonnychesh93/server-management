<?php

namespace App\Http\Requests\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTeamInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('addMember', $this->route('team'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(TeamInvitation::class)->where('team_id', $team->id),
                function (string $attribute, mixed $value, callable $fail) use ($team) {
                    if ($team->users()->where('email', $value)->exists()) {
                        $fail(__('This user already belongs to the team.'));
                    }
                },
            ],
            'role' => ['required', Rule::enum(TeamRole::class)->except([TeamRole::Owner])],
        ];
    }
}

import type { User } from '@/types/auth';

export type TeamRole = 'owner' | 'admin' | 'member';

export type GithubInstallation = {
    id: number;
    team_id: number;
    installation_id: number;
    account_login: string;
    account_type: string;
};

export type GithubRepository = {
    full_name: string;
    default_branch: string;
};

export type Team = {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
    github_installation?: GithubInstallation | null;
};

export type TeamMember = User & {
    pivot: {
        role: TeamRole;
    };
};

export type TeamInvitation = {
    id: number;
    team_id: number;
    email: string;
    role: TeamRole;
    invited_by_user_id: number;
    token: string;
    expires_at: string;
    created_at: string;
    updated_at: string;
    team?: Team;
    invited_by?: User;
};

export type CommandStatus = 'queued' | 'running' | 'success' | 'failed';

export type Command = {
    id: number;
    uuid: string;
    team_id: number;
    site_id: number;
    user_id: number | null;
    command: string;
    status: CommandStatus;
    output: string | null;
    exit_code: number | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string;
    site?: { id: number; uuid: string; domain: string };
};

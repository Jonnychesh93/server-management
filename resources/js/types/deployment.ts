export type DeploymentStatus = 'queued' | 'running' | 'success' | 'failed';

export type DeploymentTriggerType = 'user' | 'webhook';

export type Deployment = {
    id: number;
    uuid: string;
    team_id: number;
    site_id: number;
    status: DeploymentStatus;
    triggered_by_type: DeploymentTriggerType;
    triggered_by_user_id: number | null;
    commit_sha: string | null;
    commit_message: string | null;
    output: string | null;
    failed_step: string | null;
    exit_code: number | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string;
    triggered_by_user?: { id: number; name: string } | null;
    site?: { id: number; uuid: string; domain: string };
};

export type DaemonStatus = 'pending' | 'active' | 'failed';

export type Daemon = {
    id: number;
    server_id: number;
    command: string;
    directory: string;
    user: string;
    processes: number;
    status: DaemonStatus;
};

export type CronStatus = 'pending' | 'active' | 'failed';

export type Cron = {
    id: number;
    server_id: number;
    command: string;
    user: string;
    schedule: string;
    status: CronStatus;
};

import type { Deployment } from '@/types/deployment';
import type { Server } from '@/types/server';

export type SiteStatus = 'provisioning' | 'active' | 'failed' | 'disabled';

export type SslStatus = 'none' | 'pending' | 'active' | 'failed';

export type GitProvider = 'manual' | 'github_app';

export type SshKey = {
    id: number;
    name: string;
    public_key: string;
};

export type GitConnection = {
    id: number;
    site_id: number;
    provider: GitProvider;
    repository: string;
    branch: string;
    deploy_key_id: number | null;
    deploy_key?: SshKey;
};

export type Site = {
    id: number;
    team_id: number;
    server_id: number;
    domain: string;
    document_root: string;
    php_version: string;
    deploy_script: string;
    status: SiteStatus;
    provisioning_failed_step: string | null;
    provisioning_output: string | null;
    ssl_status: SslStatus;
    ssl_certificate_expires_at: string | null;
    last_deployed_at: string | null;
    created_at: string;
    updated_at: string;
    git_connection?: GitConnection | null;
    deployments?: Deployment[];
    server?: Server;
};

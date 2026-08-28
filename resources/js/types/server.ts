export type ServerOs = 'ubuntu-22.04' | 'ubuntu-24.04';

export type ProvisioningStatus =
    'pending' | 'connecting' | 'installing' | 'active' | 'failed';

export type ConnectionStatus = 'online' | 'offline' | 'unknown';

export type BootstrapCredentialType = 'password' | 'private_key';

export type Server = {
    id: number;
    uuid: string;
    team_id: number;
    name: string;
    ip_address: string;
    ssh_port: number;
    ssh_user: string;
    os: ServerOs;
    ssh_public_key: string | null;
    provisioning_status: ProvisioningStatus;
    provisioning_failed_step: string | null;
    provisioning_output: string | null;
    connection_status: ConnectionStatus;
    last_heartbeat_at: string | null;
    cpu_usage: number | null;
    memory_usage: number | null;
    disk_usage: number | null;
    installed_php_versions: string[] | null;
    created_at: string;
    updated_at: string;
};

export type ServerOsOption = {
    value: ServerOs;
    label: string;
};

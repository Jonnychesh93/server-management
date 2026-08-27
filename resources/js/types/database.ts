export type DatabaseStatus = 'pending' | 'active' | 'failed';

export type Database = {
    id: number;
    server_id: number;
    name: string;
    username: string;
    password: string | null;
    status: DatabaseStatus;
};

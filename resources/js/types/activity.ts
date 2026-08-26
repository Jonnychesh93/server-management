export type ActivityLogEntry = {
    id: number;
    team_id: number;
    user_id: number | null;
    subject_type: string;
    subject_id: number;
    action: string;
    description: string;
    created_at: string;
};

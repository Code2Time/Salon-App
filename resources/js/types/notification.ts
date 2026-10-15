export interface Notice {
    id: string;
    title: string;
    message: string;
    path: string | null;
    date: string | null;
    read_at: string | null;
    created_at: string;
}

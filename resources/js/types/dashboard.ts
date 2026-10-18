export interface DashboardSummary {
    today_appointments: number;
    today_confirmed: number;
    today_completed: number;
    today_cancelled: number;
    today_expired: number;
    active_customers: number;
    active_employees: number;
    active_services: number;
}

export interface DashboardAppointment {
    id: number;
    status: "pending" | "confirmed";
    starts_at: string;
    customer: { name: string; mobile: string };
    services: string[];
    employees: string[];
}

export interface DashboardEmployee {
    membership_id: number;
    name: string;
    schedule_status: "present" | "absent" | "not_set";
    periods: Array<{ starts_at: string; ends_at: string }>;
}

export interface DashboardData {
    date: string;
    summary: DashboardSummary;
    upcoming_appointments: DashboardAppointment[];
    employees: DashboardEmployee[];
}

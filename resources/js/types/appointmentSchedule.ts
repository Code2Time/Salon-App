export type PlanningPeriod = "weekly" | "monthly";

export interface WorkingPeriod {
    id?: number;
    starts_at: string;
    ends_at: string;
}

export interface SalonWorkingDay {
    id: number;
    date: string;
    is_open: boolean;
    title: string | null;
    description: string | null;
    periods: WorkingPeriod[];
}

export interface AppointmentSettings {
    planning_period: PlanningPeriod;
    is_recurring: boolean;
    timezone: string;
    slot_interval_minutes: number;
    cancellation_notice_hours: number;
    deposit_enabled: boolean;
    deposit_type: "fixed" | "percentage" | null;
    deposit_value: number | null;
}

export interface AppointmentSchedule {
    settings: AppointmentSettings;
    days: SalonWorkingDay[];
}

export interface WorkingDayInput {
    is_open: boolean;
    title: string | null;
    description: string | null;
    periods: WorkingPeriod[];
    confirm_employee_impact?: boolean;
}

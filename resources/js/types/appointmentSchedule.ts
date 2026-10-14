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
}

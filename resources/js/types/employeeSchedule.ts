import type { WorkingPeriod } from "./appointmentSchedule";

export interface EmployeeScheduleDay {
    date: string;
    salon_is_open: boolean;
    salon_title: string | null;
    salon_periods: WorkingPeriod[];
    is_present: boolean;
    periods: WorkingPeriod[];
}
export interface EmployeeOption { membership_id: number; name: string; image_url: string | null; }
export interface EmployeeSchedule { employee: EmployeeOption; planning_period: "weekly" | "monthly"; days: EmployeeScheduleDay[]; }

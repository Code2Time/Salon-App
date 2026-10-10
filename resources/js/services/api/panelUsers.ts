import type { PaginatedPanelUsers, PanelUser, PanelUserInput } from "../../types/panelUser";
import { api, initializeCsrf } from "./client";

export interface PanelUserFilters {
    search?: string;
    role?: string;
    status?: string;
    page?: number;
}

export async function fetchPanelUsers(filters: PanelUserFilters): Promise<PaginatedPanelUsers> {
    const { data } = await api.get<PaginatedPanelUsers>("/panel/users", { params: filters });
    return data;
}

export async function createPanelUser(input: PanelUserInput & { mobile: string }): Promise<PanelUser> {
    await initializeCsrf();
    const { data } = await api.post<{ data: PanelUser }>("/panel/users", toFormData(input));
    return data.data;
}

export async function updatePanelUser(id: number, input: PanelUserInput): Promise<PanelUser> {
    await initializeCsrf();
    const formData = toFormData(input);
    formData.append("_method", "PUT");
    const { data } = await api.post<{ data: PanelUser }>(`/panel/users/${id}`, formData);
    return data.data;
}

function toFormData(input: PanelUserInput & { mobile?: string }): FormData {
    const data = new FormData();
    data.append("first_name", input.first_name);
    data.append("last_name", input.last_name);
    if (input.mobile) data.append("mobile", input.mobile);
    if (input.email) data.append("email", input.email);
    data.append("role", input.role);
    data.append("is_active", input.is_active ? "1" : "0");
    input.permissions.forEach((permission) => data.append("permissions[]", permission));
    if (input.image) data.append("image", input.image);
    return data;
}

export async function deletePanelUser(id: number): Promise<void> {
    await initializeCsrf();
    await api.delete(`/panel/users/${id}`);
}

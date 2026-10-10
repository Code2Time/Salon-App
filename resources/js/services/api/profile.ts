import type { AuthUser } from "../../types/auth";
import { api, initializeCsrf } from "./client";

export interface ProfileInput {
    first_name: string;
    last_name: string;
    email: string | null;
    birth_date: string | null;
    image?: File;
}

export async function updateProfile(input: ProfileInput): Promise<AuthUser> {
    await initializeCsrf();
    const data = new FormData();
    data.append("first_name", input.first_name);
    data.append("last_name", input.last_name);
    if (input.email) data.append("email", input.email);
    if (input.birth_date) data.append("birth_date", input.birth_date);
    if (input.image) data.append("image", input.image);

    const response = await api.post<{ data: AuthUser }>("/panel/profile", data);
    return response.data.data;
}

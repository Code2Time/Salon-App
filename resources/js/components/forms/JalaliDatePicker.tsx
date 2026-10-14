import { DatePicker } from "@mui/x-date-pickers/DatePicker";
import { useState } from "react";

interface JalaliDatePickerProps {
    label: string;
    value: string;
    onChange: (value: string) => void;
    disableFuture?: boolean;
}

function parseGregorianDate(value: string): Date | null {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) return null;

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
        ? date
        : null;
}

function toGregorianDateString(value: Date | null): string {
    if (!value || Number.isNaN(value.getTime())) return "";

    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, "0");
    const day = String(value.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

export default function JalaliDatePicker({
    label,
    value,
    onChange,
    disableFuture = false,
}: JalaliDatePickerProps) {
    const [open, setOpen] = useState(false);
    return (
        <DatePicker
            label={label}
            value={parseGregorianDate(value)}
            onChange={(date) => onChange(toGregorianDateString(date))}
            disableFuture={disableFuture}
            open={open}
            onOpen={() => setOpen(true)}
            onClose={() => setOpen(false)}
            format="yyyy/MM/dd"
            slotProps={{
                field: { clearable: true },
                textField: { fullWidth: true, onClick: () => setOpen(true) },
                desktopPaper: { className: "ara-date-picker" },
                mobilePaper: { className: "ara-date-picker" },
            }}
        />
    );
}

import { useState } from "react";
import { TimePicker } from "@mui/x-date-pickers/TimePicker";

interface StyledTimePickerProps {
    label: string;
    value: string;
    onChange: (value: string) => void;
}

function parseTime(value: string): Date | null {
    const match = /^(\d{2}):(\d{2})$/.exec(value);
    if (!match) return null;
    const date = new Date(2000, 0, 1, Number(match[1]), Number(match[2]));
    return Number(match[1]) < 24 && Number(match[2]) < 60 ? date : null;
}

function formatTime(value: Date | null): string {
    if (!value || Number.isNaN(value.getTime())) return "";
    return `${String(value.getHours()).padStart(2, "0")}:${String(value.getMinutes()).padStart(2, "0")}`;
}

export default function StyledTimePicker({ label, value, onChange }: StyledTimePickerProps) {
    const [open, setOpen] = useState(false);
    return <TimePicker
        label={label}
        value={parseTime(value)}
        onChange={(time) => onChange(formatTime(time))}
        open={open}
        onOpen={() => setOpen(true)}
        onClose={() => setOpen(false)}
        ampm={false}
        minutesStep={5}
        slotProps={{
            textField: { fullWidth: true, onClick: () => setOpen(true) },
            desktopPaper: { className: "ara-time-picker" },
            mobilePaper: { className: "ara-time-picker" },
        }}
    />;
}

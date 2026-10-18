import { Button, IconButton, Tooltip } from "@mui/material";
import { Moon, Sun } from "lucide-react";
import { useColorMode } from "../../theme/ColorModeContext";

export default function ColorModeToggle({ compact = false }: { compact?: boolean }) {
    const { mode, toggleMode } = useColorMode();
    const dark = mode === "dark";
    const label = dark ? "فعال‌کردن تم روشن" : "فعال‌کردن تم تیره";
    const icon = dark ? <Sun size={18} /> : <Moon size={18} />;

    if (compact) {
        return (
            <Tooltip title={label}>
                <IconButton className="color-mode-toggle" aria-label={label} onClick={toggleMode}>
                    {icon}
                </IconButton>
            </Tooltip>
        );
    }

    return (
        <Button className="sidebar-theme-toggle" color="inherit" onClick={toggleMode}>
            <span>{dark ? "تم روشن" : "تم تیره"}</span> {icon}
        </Button>
    );
}

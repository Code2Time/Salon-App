import { NavLink } from "react-router";
import type { NavigationItem } from "../../routes/navigation";

interface NavigationProps {
    items: NavigationItem[];
    label: string;
    className?: string;
    onNavigate?: () => void;
}

export default function Navigation({
    items,
    label,
    className = "",
    onNavigate,
}: NavigationProps) {
    return (
        <nav aria-label={label} className={className}>
            {items.map(({ path, label: title, icon: Icon }) => (
                <NavLink
                    key={path}
                    to={path}
                    end
                    title={title}
                    onClick={onNavigate}
                    className={({ isActive }) =>
                        `nav-item${isActive ? " active" : ""}`
                    }
                >
                    <Icon size={21} strokeWidth={1.7} aria-hidden="true" />
                    <span>{title}</span>
                </NavLink>
            ))}
        </nav>
    );
}

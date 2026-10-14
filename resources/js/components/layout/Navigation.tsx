import { ChevronDown } from "lucide-react";
import { useEffect, useState } from "react";
import { NavLink, useLocation } from "react-router";
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
    const { pathname } = useLocation();
    const [openGroups, setOpenGroups] = useState<string[]>(() =>
        items.filter((item) => item.children?.some((child) => child.path === pathname)).map((item) => item.path),
    );

    useEffect(() => {
        const activeGroups = items
            .filter((item) => item.children?.some((child) => child.path === pathname))
            .map((item) => item.path);
        if (activeGroups.length) {
            setOpenGroups((current) => [...new Set([...current, ...activeGroups])]);
        }
    }, [items, pathname]);

    return (
        <nav aria-label={label} className={className}>
            {items.map(({ path, label: title, icon: Icon, children }) => {
                if (!children?.length) return (
                    <NavLink key={path} to={path} end title={title} onClick={onNavigate} className={({ isActive }) => `nav-item${isActive ? " active" : ""}`}>
                        <Icon size={21} strokeWidth={1.7} aria-hidden="true" /><span>{title}</span>
                    </NavLink>
                );
                const expanded = openGroups.includes(path);
                const active = children.some((child) => child.path === pathname);
                return (
                    <div className={`nav-group${active ? " active" : ""}`} key={path}>
                        <button type="button" className="nav-item nav-group-trigger" title={title} aria-expanded={expanded} onClick={() => setOpenGroups((current) => expanded ? current.filter((value) => value !== path) : [...current, path])}>
                            <Icon size={21} strokeWidth={1.7} aria-hidden="true" /><span>{title}</span><ChevronDown className="nav-group-chevron" size={16} />
                        </button>
                        <div className="nav-children" hidden={!expanded}>
                            {children.map((child) => <NavLink key={child.path} to={child.path} end onClick={onNavigate} className={({ isActive }) => `nav-child${isActive ? " active" : ""}`}><child.icon size={16} /><span>{child.label}</span></NavLink>)}
                        </div>
                    </div>
                );
            })}
        </nav>
    );
}

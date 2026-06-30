import {
    Ban,
    Check,
    ChevronDown,
    CircleCheck,
    CircleDashed,
    CircleDot,
    CircleX,
    Clock,
    type LucideIcon,
    Send,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { toast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import { type SelectOption } from '@/types';

/** Icon + accent colour per ProjectStatus / TaskStatus value. */
const META: Record<string, { icon: LucideIcon; className: string }> = {
    Pending: { icon: Clock, className: 'text-amber-500' },
    'In Progress': { icon: CircleDashed, className: 'text-blue-500' },
    Submitted: { icon: Send, className: 'text-indigo-500' },
    Approved: { icon: CircleCheck, className: 'text-emerald-500' },
    Rejected: { icon: CircleX, className: 'text-rose-500' },
    Cancelled: { icon: Ban, className: 'text-destructive' },
};

/**
 * Compact status picker: a small outline button (icon + label + chevron) opening
 * a dropdown of statuses, each with its icon. The new value is applied
 * optimistically; `onSelect` performs the write and the control reverts + toasts
 * on rejection. `relative z-10` keeps it clickable above a card's stretched link.
 */
export default function StatusDropdown({
    value,
    options,
    onSelect,
    disabled,
    variant = 'outline',
    iconOnly = false,
    className,
}: {
    value: string;
    options: SelectOption[];
    onSelect: (next: string) => Promise<unknown>;
    disabled?: boolean;
    variant?: 'outline' | 'ghost';
    iconOnly?: boolean;
    className?: string;
}) {
    const [status, setStatus] = useState(value);
    const [saving, setSaving] = useState(false);

    // Keep in sync if the server-sent value changes (e.g. after a reload), so the
    // control never shows a stale status from a prior optimistic edit.
    useEffect(() => {
        setStatus(value);
    }, [value]);

    const select = (next: string) => {
        if (next === status) {
            return;
        }

        const previous = status;
        setStatus(next);
        setSaving(true);
        onSelect(next)
            .then(() => {
                toast({
                    title: 'Status updated',
                    description: `Set to ${
                        options.find((o) => String(o.value) === next)?.label ??
                        next
                    }.`,
                });
            })
            .catch(() => {
                setStatus(previous);
                toast({
                    title: 'Could not update status',
                    description: 'Please try again.',
                    variant: 'destructive',
                });
            })
            .finally(() => setSaving(false));
    };

    const meta = META[status] ?? { icon: CircleDot, className: '' };
    const Icon = meta.icon;
    const label =
        options.find((o) => String(o.value) === status)?.label ?? status;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant={variant}
                    size={iconOnly ? 'icon' : 'sm'}
                    disabled={disabled || saving}
                    aria-label={iconOnly ? `Status: ${label}` : undefined}
                    title={iconOnly ? label : undefined}
                    className={cn(
                        'relative z-10',
                        iconOnly ? 'h-9 w-9' : 'h-8 gap-1.5',
                        className,
                    )}
                >
                    <Icon
                        className={cn(
                            iconOnly ? 'h-5 w-5' : 'h-3.5 w-3.5',
                            meta.className,
                        )}
                    />
                    {!iconOnly && label}
                    {!iconOnly && (
                        <ChevronDown className="h-3.5 w-3.5 opacity-50" />
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
                {options.map((o) => {
                    const optionValue = String(o.value);
                    const optionMeta = META[optionValue] ?? {
                        icon: CircleDot,
                        className: '',
                    };
                    const OptionIcon = optionMeta.icon;

                    return (
                        <DropdownMenuItem
                            key={optionValue}
                            onClick={() => select(optionValue)}
                            className="gap-2"
                        >
                            <OptionIcon
                                className={cn('h-4 w-4', optionMeta.className)}
                            />
                            <span className="flex-1">{o.label}</span>
                            {optionValue === status && (
                                <Check className="h-4 w-4" />
                            )}
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

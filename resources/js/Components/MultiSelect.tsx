import { ChevronsUpDown, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';
import { type SelectOption } from '@/types';

interface Props {
    options: SelectOption[];
    /** Selected option values (coerced to strings). */
    selected: string[];
    onChange: (values: string[]) => void;
    id?: string;
    placeholder?: string;
    title?: string;
    description?: string;
    emptyText?: string;
    className?: string;
}

/**
 * A searchable multi-select built from a Dialog + Checkbox list (no command/
 * combobox primitive exists in this project). Renders a trigger with the
 * selected count, removable chips, and a filterable picker dialog. Selection
 * updates immediately through onChange.
 */
export default function MultiSelect({
    options,
    selected,
    onChange,
    id,
    placeholder = 'Select…',
    title = 'Select options',
    description,
    emptyText = 'No options found.',
    className,
}: Props) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    const byValue = useMemo(() => {
        const map = new Map<string, SelectOption>();
        options.forEach((o) => map.set(String(o.value), o));
        return map;
    }, [options]);

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return options;
        return options.filter((o) => o.label.toLowerCase().includes(q));
    }, [options, search]);

    const toggle = (value: string) => {
        if (selected.includes(value)) {
            onChange(selected.filter((v) => v !== value));
        } else {
            onChange([...selected, value]);
        }
    };

    return (
        <div className={className}>
            <Button
                type="button"
                variant="outline"
                id={id}
                onClick={() => setOpen(true)}
                className="w-full justify-between font-normal"
            >
                <span
                    className={cn(
                        selected.length === 0 && 'text-muted-foreground',
                    )}
                >
                    {selected.length > 0
                        ? `${selected.length} selected`
                        : placeholder}
                </span>
                <ChevronsUpDown className="h-4 w-4 opacity-50" />
            </Button>

            {selected.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-1.5">
                    {selected.map((value) => (
                        <Badge
                            key={value}
                            variant="secondary"
                            className="gap-1"
                        >
                            {byValue.get(value)?.label ?? value}
                            <button
                                type="button"
                                onClick={() => toggle(value)}
                                className="rounded-full outline-none ring-offset-background hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"
                                aria-label={`Remove ${byValue.get(value)?.label ?? value}`}
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    ))}
                </div>
            )}

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        {description && (
                            <DialogDescription>{description}</DialogDescription>
                        )}
                    </DialogHeader>

                    <div className="relative">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search…"
                            className="pl-8"
                            autoFocus
                        />
                    </div>

                    <div className="max-h-72 space-y-1 overflow-y-auto">
                        {filtered.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                {emptyText}
                            </p>
                        ) : (
                            filtered.map((o) => {
                                const value = String(o.value);
                                return (
                                    <label
                                        key={value}
                                        className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                                    >
                                        <Checkbox
                                            checked={selected.includes(value)}
                                            onCheckedChange={() =>
                                                toggle(value)
                                            }
                                        />
                                        <span className="flex-1">
                                            {o.label}
                                        </span>
                                    </label>
                                );
                            })
                        )}
                    </div>

                    <DialogFooter>
                        <Button type="button" onClick={() => setOpen(false)}>
                            Done
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

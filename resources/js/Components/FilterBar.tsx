import { FormEventHandler, ReactNode } from 'react';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { type SelectOption } from '@/types';

/** Sentinel for the "no filter" select option — Radix Select forbids value="". */
export const ALL = '__all__';

interface FilterBarProps {
    /** Called when the search form is submitted (button or Enter). */
    onSubmit: () => void;
    children: ReactNode;
    className?: string;
}

/**
 * Declarative list-page toolbar. Wraps its children in a submit form so text
 * inputs commit on Enter/button; toggles and selects act live via their own
 * handlers. Compose with the FilterBar.Search/Select/Checkbox subcomponents.
 */
function FilterBar({ onSubmit, children, className }: FilterBarProps) {
    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        onSubmit();
    };

    return (
        <form
            onSubmit={handleSubmit}
            className={`flex flex-wrap items-end gap-2 ${className ?? ''}`}
        >
            {children}
        </form>
    );
}

interface SearchProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
    /** Render the submit button (default true). */
    withButton?: boolean;
}

function Search({
    value,
    onChange,
    placeholder = 'Search…',
    className = 'w-64',
    withButton = true,
}: SearchProps) {
    return (
        <>
            <Input
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className={className}
            />
            {withButton && (
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            )}
        </>
    );
}

interface SelectFilterProps {
    /** Current value, or '' / undefined when unset (shown as the "all" item). */
    value: string | undefined;
    onChange: (value: string | undefined) => void;
    options: SelectOption[];
    placeholder?: string;
    /** Label for the "no filter" option; pass null to omit it (required field). */
    allLabel?: string | null;
    className?: string;
}

function SelectFilter({
    value,
    onChange,
    options,
    placeholder = 'All',
    allLabel = 'All',
    className = 'w-44',
}: SelectFilterProps) {
    return (
        <Select
            value={value ? value : ALL}
            onValueChange={(v) => onChange(v === ALL ? undefined : v)}
        >
            <SelectTrigger className={className}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {allLabel !== null && (
                    <SelectItem value={ALL}>{allLabel}</SelectItem>
                )}
                {options.map((option) => (
                    <SelectItem
                        key={String(option.value)}
                        value={String(option.value)}
                    >
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

interface CheckboxFilterProps {
    checked: boolean;
    onChange: (checked: boolean) => void;
    label: string;
}

function CheckboxFilter({ checked, onChange, label }: CheckboxFilterProps) {
    return (
        <label className="flex h-10 items-center gap-2 text-sm text-muted-foreground">
            <Checkbox
                checked={checked}
                onCheckedChange={(c) => onChange(Boolean(c))}
            />
            {label}
        </label>
    );
}

FilterBar.Search = Search;
FilterBar.Select = SelectFilter;
FilterBar.Checkbox = CheckboxFilter;

export default FilterBar;

import AsyncSelect from '@/Components/AsyncSelect';

interface Props {
    /** Selected organization token, or undefined when none/cleared. */
    value: string | undefined;
    onChange: (value: string | undefined) => void;
    id?: string;
    /** Trigger text when nothing is selected (form mode). */
    placeholder?: string;
    /** Offer a "clear" entry that resets to undefined (filter mode). */
    allowClear?: boolean;
    /** Trigger/clear-entry text for the empty state when allowClear (e.g. "All organizations"). */
    allLabel?: string;
    invalid?: boolean;
    className?: string;
}

/**
 * Async organization picker — a thin wrapper over <AsyncSelect> bound to the
 * organizations.options endpoint. Scales to millions of organizations: searches
 * on demand and pages more in on scroll. See <AsyncSelect> for the mechanics.
 */
export default function OrganizationSelect({
    placeholder = 'Select an organization',
    ...props
}: Props) {
    return (
        <AsyncSelect
            {...props}
            placeholder={placeholder}
            routeName="organizations.options"
            dialogTitle="Select organization"
            searchPlaceholder="Search organizations…"
            emptyText="No organizations found."
        />
    );
}

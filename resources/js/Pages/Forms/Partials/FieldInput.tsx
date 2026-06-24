import InputError from '@/Components/InputError';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { type AnswerValue, type FormField } from '@/types';

interface Props {
    field: FormField;
    value: AnswerValue;
    onChange: (value: AnswerValue) => void;
    error?: string;
    disabled?: boolean;
}

/**
 * Renders a single form field as a real input. Shared by the fill page
 * (editable) and the Show preview (disabled).
 */
export default function FieldInput({
    field,
    value,
    onChange,
    error,
    disabled,
}: Props) {
    const { config } = field;

    return (
        <div className="rounded-lg border p-4">
            <Label className="flex items-center gap-1 text-sm font-medium">
                {field.label || 'Untitled field'}
                {field.required && <span className="text-destructive">*</span>}
            </Label>
            {field.description && (
                <p className="mt-1 text-sm text-muted-foreground">
                    {field.description}
                </p>
            )}

            <div className="mt-2">
                {field.type === 'text' && (
                    <Input
                        value={(value as string) ?? ''}
                        onChange={(e) => onChange(e.target.value)}
                        placeholder={config.placeholder}
                        disabled={disabled}
                    />
                )}

                {field.type === 'paragraph' && (
                    <Textarea
                        value={(value as string) ?? ''}
                        onChange={(e) => onChange(e.target.value)}
                        placeholder={config.placeholder}
                        rows={3}
                        disabled={disabled}
                    />
                )}

                {field.type === 'date' && (
                    <Input
                        type={config.include_time ? 'datetime-local' : 'date'}
                        value={(value as string) ?? ''}
                        onChange={(e) => onChange(e.target.value)}
                        disabled={disabled}
                    />
                )}

                {field.type === 'duration' && (
                    <Input
                        type="time"
                        value={(value as string) ?? ''}
                        onChange={(e) => onChange(e.target.value)}
                        disabled={disabled}
                    />
                )}

                {field.type === 'range' && (
                    <div>
                        <input
                            type="range"
                            className="w-full accent-primary"
                            min={config.min ?? 0}
                            max={config.max ?? 10}
                            step={config.step ?? 1}
                            value={Number(value ?? config.min ?? 0)}
                            onChange={(e) => onChange(Number(e.target.value))}
                            disabled={disabled}
                        />
                        <div className="flex justify-between text-xs text-muted-foreground">
                            <span>{config.min_label ?? config.min ?? 0}</span>
                            <span className="font-medium text-foreground">
                                {value ?? config.min ?? 0}
                            </span>
                            <span>{config.max_label ?? config.max ?? 10}</span>
                        </div>
                    </div>
                )}

                {field.type === 'list' && config.multiple && (
                    <div className="space-y-2">
                        {(config.items ?? []).map((item, i) => {
                            const selected = Array.isArray(value) ? value : [];
                            return (
                                <label
                                    key={`${item}-${i}`}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        checked={selected.includes(item)}
                                        disabled={disabled}
                                        onCheckedChange={(checked) =>
                                            onChange(
                                                checked
                                                    ? [...selected, item]
                                                    : selected.filter(
                                                          (i) => i !== item,
                                                      ),
                                            )
                                        }
                                    />
                                    {item}
                                </label>
                            );
                        })}
                    </div>
                )}

                {field.type === 'list' && !config.multiple && (
                    <div className="space-y-2">
                        {(config.items ?? []).map((item, i) => (
                            <label
                                key={`${item}-${i}`}
                                className="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="radio"
                                    className="accent-primary"
                                    name={field.id}
                                    value={item}
                                    checked={value === item}
                                    disabled={disabled}
                                    onChange={() => onChange(item)}
                                />
                                {item}
                            </label>
                        ))}
                    </div>
                )}
            </div>

            {error && <InputError message={error} className="mt-2" />}
        </div>
    );
}

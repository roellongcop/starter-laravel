import { Plus, X } from 'lucide-react';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { type FormField, type FormFieldConfig } from '@/types';

interface Props {
    field: FormField;
    onChange: (patch: Partial<FormField>) => void;
}

/**
 * The expanded settings panel for one field: a shared description plus the
 * type-specific config inputs.
 */
export default function FieldEditor({ field, onChange }: Props) {
    const config = field.config;
    const setConfig = (patch: Partial<FormFieldConfig>) =>
        onChange({ config: { ...config, ...patch } });

    return (
        <div className="space-y-4 border-t bg-muted/30 p-4">
            <div>
                <Label className="text-xs">Description (optional)</Label>
                <Textarea
                    value={field.description ?? ''}
                    onChange={(e) => onChange({ description: e.target.value })}
                    rows={2}
                    className="mt-1"
                    placeholder="Helper text shown under the label"
                />
            </div>

            {(field.type === 'text' || field.type === 'paragraph') && (
                <div>
                    <Label className="text-xs">Placeholder</Label>
                    <Input
                        value={config.placeholder ?? ''}
                        onChange={(e) =>
                            setConfig({ placeholder: e.target.value })
                        }
                        className="mt-1"
                    />
                </div>
            )}

            {field.type === 'date' && (
                <label className="flex items-center justify-between rounded-md border bg-background p-3">
                    <span className="text-sm">Include time</span>
                    <Switch
                        checked={Boolean(config.include_time)}
                        onCheckedChange={(v) => setConfig({ include_time: v })}
                    />
                </label>
            )}

            {field.type === 'duration' && (
                <p className="text-sm text-muted-foreground">
                    Respondents enter a duration as hours:minutes.
                </p>
            )}

            {field.type === 'range' && (
                <div className="grid grid-cols-3 gap-3">
                    <div>
                        <Label className="text-xs">Min</Label>
                        <Input
                            type="number"
                            value={config.min ?? 0}
                            onChange={(e) =>
                                setConfig({ min: Number(e.target.value) })
                            }
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label className="text-xs">Max</Label>
                        <Input
                            type="number"
                            value={config.max ?? 10}
                            onChange={(e) =>
                                setConfig({ max: Number(e.target.value) })
                            }
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label className="text-xs">Step</Label>
                        <Input
                            type="number"
                            min={1}
                            value={config.step ?? 1}
                            onChange={(e) =>
                                setConfig({ step: Number(e.target.value) })
                            }
                            className="mt-1"
                        />
                    </div>
                    <div className="col-span-3 grid grid-cols-2 gap-3">
                        <div>
                            <Label className="text-xs">Min label</Label>
                            <Input
                                value={config.min_label ?? ''}
                                onChange={(e) =>
                                    setConfig({ min_label: e.target.value })
                                }
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label className="text-xs">Max label</Label>
                            <Input
                                value={config.max_label ?? ''}
                                onChange={(e) =>
                                    setConfig({ max_label: e.target.value })
                                }
                                className="mt-1"
                            />
                        </div>
                    </div>
                </div>
            )}

            {field.type === 'list' && (
                <div className="space-y-3">
                    <label className="flex items-center justify-between rounded-md border bg-background p-3">
                        <span className="text-sm">
                            Allow multiple selections (checkboxes)
                        </span>
                        <Switch
                            checked={Boolean(config.multiple)}
                            onCheckedChange={(v) => setConfig({ multiple: v })}
                        />
                    </label>

                    <div>
                        <Label className="text-xs">Items</Label>
                        <div className="mt-1 space-y-2">
                            {(config.items ?? []).map((item, i) => (
                                <div
                                    key={i}
                                    className="flex items-center gap-2"
                                >
                                    <Input
                                        value={item}
                                        onChange={(e) => {
                                            const items = [
                                                ...(config.items ?? []),
                                            ];
                                            items[i] = e.target.value;
                                            setConfig({ items });
                                        }}
                                        placeholder={`Option ${i + 1}`}
                                    />
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        aria-label="Remove item"
                                        onClick={() =>
                                            setConfig({
                                                items: (
                                                    config.items ?? []
                                                ).filter((_, j) => j !== i),
                                            })
                                        }
                                    >
                                        <X className="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setConfig({
                                        items: [...(config.items ?? []), ''],
                                    })
                                }
                            >
                                <Plus className="h-4 w-4" /> Add item
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

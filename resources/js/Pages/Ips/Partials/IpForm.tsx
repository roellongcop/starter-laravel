import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { type AdminIp, type SelectOption } from '@/types';

interface Props {
    ip?: AdminIp;
    listTypes: SelectOption[];
    onSuccess?: () => void;
}

export default function IpForm({ ip, listTypes, onSuccess }: Props) {
    const editing = Boolean(ip);

    const { data, setData, post, patch, processing, errors } = useForm({
        ip_address: ip?.ip_address ?? '',
        list_type: ip?.list_type ?? String(listTypes[0]?.value ?? 'Blacklist'),
        description: ip?.description ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess };
        if (editing && ip) {
            patch(route('ips.update', ip.token), options);
        } else {
            post(route('ips.store'), options);
        }
    };

    return (
        <form onSubmit={submit} className="max-w-xl space-y-4">
            <div>
                <Label htmlFor="ip_address" required>
                    IP address
                </Label>
                <Input
                    id="ip_address"
                    value={data.ip_address}
                    onChange={(e) => setData('ip_address', e.target.value)}
                    className="mt-1"
                    placeholder="203.0.113.10"
                />
                <InputError message={errors.ip_address} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="list_type" required>
                    List type
                </Label>
                <Select
                    value={data.list_type}
                    onValueChange={(v) => setData('list_type', v)}
                >
                    <SelectTrigger id="list_type" className="mt-1">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {listTypes.map((o) => (
                            <SelectItem key={o.value} value={String(o.value)}>
                                {o.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.list_type} className="mt-1" />
            </div>

            <div>
                <Label htmlFor="description">Description</Label>
                <Input
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="mt-1"
                />
                <InputError message={errors.description} className="mt-1" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create entry'}
            </Button>
        </form>
    );
}

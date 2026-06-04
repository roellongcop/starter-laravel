import 'cropperjs/dist/cropper.css';

import axios from 'axios';
import { Camera, ImageIcon, Upload } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Cropper, type ReactCropperElement } from 'react-cropper';

import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

export interface PickedImage {
    id: number;
    url: string;
}

interface PhotoItem {
    id: number;
    name: string;
    url: string;
    created_at: string | null;
}

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onPicked: (image: PickedImage) => void;
    /** Lock the crop box to this ratio (e.g. 1 for square avatars). Omit = free. */
    aspectRatio?: number;
    title?: string;
}

const hasCamera =
    typeof navigator !== 'undefined' && !!navigator.mediaDevices?.getUserMedia;

/** Upload a (cropped) blob to the generic /media endpoint → { id, url }. */
async function uploadBlob(blob: Blob): Promise<PickedImage> {
    const form = new FormData();
    form.append(
        'file',
        new File([blob], `image-${Date.now()}.jpg`, {
            type: 'image/jpeg',
        }),
    );
    const { data } = await axios.post(route('media.store'), form);
    return { id: data.id, url: data.url };
}

/**
 * Generic image picker: upload / pick-existing / camera, with a Cropper.js
 * editor. Always resolves to an uploaded File id (+ display url) so any page can
 * just store the id. Reusable beyond avatars via the optional aspectRatio.
 */
export default function ImagePicker({
    open,
    onOpenChange,
    onPicked,
    aspectRatio,
    title = 'Choose image',
}: Props) {
    const [imageSrc, setImageSrc] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const cropperRef = useRef<ReactCropperElement>(null);

    const reset = useCallback(() => setImageSrc(null), []);

    const close = useCallback(() => {
        reset();
        onOpenChange(false);
    }, [reset, onOpenChange]);

    const confirmCrop = () => {
        const cropper = cropperRef.current?.cropper;
        if (!cropper) return;
        setBusy(true);
        cropper.getCroppedCanvas({ maxWidth: 1600, maxHeight: 1600 }).toBlob(
            async (blob) => {
                if (!blob) {
                    setBusy(false);
                    return;
                }
                try {
                    onPicked(await uploadBlob(blob));
                    close();
                } finally {
                    setBusy(false);
                }
            },
            'image/jpeg',
            0.9,
        );
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(o) => (o ? onOpenChange(o) : close())}
        >
            <DialogContent className="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>
                        Upload a new image, pick one you uploaded before, or use
                        your camera — then crop it.
                    </DialogDescription>
                </DialogHeader>

                {imageSrc ? (
                    <div className="space-y-4">
                        <Cropper
                            ref={cropperRef}
                            src={imageSrc}
                            className="max-h-[28rem] w-full"
                            aspectRatio={aspectRatio}
                            viewMode={1}
                            background={false}
                            autoCropArea={1}
                            responsive
                            checkOrientation={false}
                        />
                        <DialogFooter>
                            <Button variant="outline" onClick={reset}>
                                Back
                            </Button>
                            <Button onClick={confirmCrop} disabled={busy}>
                                {busy ? 'Saving…' : 'Use photo'}
                            </Button>
                        </DialogFooter>
                    </div>
                ) : (
                    <Tabs defaultValue="upload">
                        <TabsList className="grid w-full grid-cols-3">
                            <TabsTrigger value="upload">
                                <Upload className="mr-1 h-4 w-4" /> Upload
                            </TabsTrigger>
                            <TabsTrigger value="existing">
                                <ImageIcon className="mr-1 h-4 w-4" /> Existing
                            </TabsTrigger>
                            <TabsTrigger value="camera" disabled={!hasCamera}>
                                <Camera className="mr-1 h-4 w-4" /> Camera
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="upload">
                            <UploadTab onImage={setImageSrc} />
                        </TabsContent>
                        <TabsContent value="existing">
                            <ExistingTab
                                onEdit={setImageSrc}
                                onUseAsIs={(image) => {
                                    onPicked(image);
                                    close();
                                }}
                            />
                        </TabsContent>
                        <TabsContent value="camera">
                            <CameraTab onCapture={setImageSrc} active={open} />
                        </TabsContent>
                    </Tabs>
                )}
            </DialogContent>
        </Dialog>
    );
}

function UploadTab({ onImage }: { onImage: (src: string) => void }) {
    const onFile = (file?: File) => {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => onImage(String(reader.result));
        reader.readAsDataURL(file);
    };

    return (
        <label className="flex h-48 cursor-pointer flex-col items-center justify-center gap-2 rounded-md border border-dashed text-muted-foreground hover:bg-muted/50">
            <Upload className="h-6 w-6" />
            <span className="text-sm">Click to choose an image</span>
            <input
                type="file"
                accept="image/*"
                className="hidden"
                onChange={(e) => onFile(e.target.files?.[0])}
            />
        </label>
    );
}

function ExistingTab({
    onEdit,
    onUseAsIs,
}: {
    onEdit: (src: string) => void;
    onUseAsIs: (image: PickedImage) => void;
}) {
    const [photos, setPhotos] = useState<PhotoItem[]>([]);
    const [next, setNext] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [selected, setSelected] = useState<PhotoItem | null>(null);

    const load = useCallback((cursor?: string) => {
        setLoading(true);
        axios
            .get(route('profile.photos'), { params: { cursor } })
            .then((r) => {
                setPhotos((prev) =>
                    cursor ? [...prev, ...r.data.data] : r.data.data,
                );
                setNext(r.data.next_cursor);
            })
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    if (!loading && photos.length === 0) {
        return (
            <p className="py-10 text-center text-sm text-muted-foreground">
                No images uploaded yet.
            </p>
        );
    }

    return (
        <div className="space-y-3">
            <div className="grid max-h-96 grid-cols-6 gap-2 overflow-y-auto">
                {photos.map((p) => (
                    <button
                        key={p.id}
                        type="button"
                        onClick={() => setSelected(p)}
                        className={
                            'aspect-square overflow-hidden rounded-md border-2 ' +
                            (selected?.id === p.id
                                ? 'border-primary'
                                : 'border-transparent')
                        }
                        title={p.name}
                    >
                        <img
                            src={p.url}
                            alt={p.name}
                            className="h-full w-full object-cover"
                        />
                    </button>
                ))}
            </div>

            {next && (
                <div className="text-center">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => load(next)}
                        disabled={loading}
                    >
                        Load more
                    </Button>
                </div>
            )}

            <DialogFooter>
                <Button
                    variant="outline"
                    disabled={!selected}
                    onClick={() =>
                        selected &&
                        onEdit(
                            route('media.img', { file: selected.id, w: 800 }),
                        )
                    }
                >
                    Crop
                </Button>
                <Button
                    disabled={!selected}
                    onClick={() =>
                        selected &&
                        onUseAsIs({ id: selected.id, url: selected.url })
                    }
                >
                    Use this photo
                </Button>
            </DialogFooter>
        </div>
    );
}

function CameraTab({
    onCapture,
    active,
}: {
    onCapture: (src: string) => void;
    active: boolean;
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let cancelled = false;

        const start = async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                });
                if (cancelled) {
                    stream.getTracks().forEach((t) => t.stop());
                    return;
                }
                streamRef.current = stream;
                if (videoRef.current) videoRef.current.srcObject = stream;
            } catch {
                setError('Could not access the camera. Check permissions.');
            }
        };

        if (active) start();

        return () => {
            cancelled = true;
            streamRef.current?.getTracks().forEach((t) => t.stop());
            streamRef.current = null;
        };
    }, [active]);

    const capture = () => {
        const video = videoRef.current;
        if (!video) return;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d')?.drawImage(video, 0, 0);
        onCapture(canvas.toDataURL('image/jpeg', 0.92));
    };

    if (error) {
        return (
            <p className="py-10 text-center text-sm text-destructive">
                {error}
            </p>
        );
    }

    return (
        <div className="space-y-3">
            <div className="overflow-hidden rounded-md bg-muted">
                <video
                    ref={videoRef}
                    autoPlay
                    playsInline
                    muted
                    className="h-[28rem] w-full object-cover"
                />
            </div>
            <DialogFooter>
                <Button onClick={capture}>
                    <Camera className="mr-1 h-4 w-4" /> Capture
                </Button>
            </DialogFooter>
        </div>
    );
}

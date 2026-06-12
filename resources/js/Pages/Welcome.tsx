import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    ArrowUpRight,
    Code2,
    Gamepad2,
    Github,
    Linkedin,
    Mail,
    MapPin,
    Phone,
    Send,
    Server,
    Smartphone,
} from 'lucide-react';
import { FormEventHandler, ReactNode, useState } from 'react';

import InputError from '@/Components/InputError';
import { useTheme } from '@/Components/ThemeProvider';
import YinYang from '@/Components/YinYang';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { useIdleLogout } from '@/hooks/use-idle-logout';
import { useReveal } from '@/hooks/use-reveal';
import { cn } from '@/lib/utils';
import { type PageProps } from '@/types';

const NAV = [
    { id: 'about', label: 'About' },
    { id: 'skills', label: 'Skills' },
    { id: 'work', label: 'Work' },
    { id: 'contact', label: 'Contact' },
];

const BACKEND_SKILLS = [
    'PHP',
    'Laravel',
    'Yii2',
    'CodeIgniter',
    'Python / Django',
    'Node.js',
    'Express',
    'MySQL',
    'PostgreSQL',
    'Firebase',
    'Docker',
    'Git',
    'AWS (SST)',
    'WordPress',
];

const FRONTEND_SKILLS = [
    'React',
    'Vue.js',
    'Angular',
    'React Native',
    'Ionic',
    'TypeScript',
    'JavaScript',
    'jQuery',
    'Tailwind CSS',
    'HTML & CSS',
];

const EXPERIENCE = [
    {
        role: 'Freelance Web Developer',
        place: 'Remote',
        period: '2023 — Present',
        points: [
            'Build and maintain custom full-stack web applications tailored to client needs.',
            'Work across React, Vue, Laravel, Yii2, Node.js, Express, Postgres and MySQL.',
            'Own end-to-end client relationships — scope, deadlines and deliverables.',
            'Test, debug and optimize for quality and performance.',
        ],
    },
    {
        role: 'Web Developer',
        place: 'Filweb Asia Inc. · San Pedro, Laguna',
        period: '2018 — 2023',
        points: [
            'Designed, developed and shipped custom WordPress themes and plugins.',
            'Built intuitive CMS interfaces for effective site administration.',
            'Hardened site security with regular audits, updates and vulnerability scans.',
            'Collaborated with multidisciplinary teams to improve UX and performance.',
        ],
    },
];

const PROJECTS = [
    {
        name: 'Resume Builder',
        blurb: 'Platform for dynamic resume creation.',
        href: 'https://resumebuilder.resume4dummies.com',
        host: 'resumebuilder.resume4dummies.com',
    },
    {
        name: 'Filworx',
        blurb: 'Global job platform connecting employers with job seekers.',
        href: 'https://filworx.com',
        host: 'filworx.com',
    },
    {
        name: 'Best10 Resume',
        blurb: 'Site highlighting premier resume-writing services.',
        href: 'https://best10resumewriters.com',
        host: 'best10resumewriters.com',
    },
    {
        name: 'GENNAKAR — Municipal Information System',
        blurb: 'Healthcare assistance, maps, incident & disaster management, chats and profiling.',
        href: 'https://accessgov.ph',
        host: 'accessgov.ph',
    },
];

const GITHUB_PROJECTS = [
    'Accounting Management System',
    'Catering System',
    'Printing Management System',
    'Clinic Management System',
    'Project Management System',
    'Barangay Information System',
    'Health Care Management System',
];

const MOBILE_APPS = [
    {
        name: 'Patroller App',
        Icon: Smartphone,
        stack: ['Ionic', 'Angular', 'Capacitor', 'Maps'],
        blurb: 'Field patrol & biodiversity app — start and log patrols, track trees and animals, browse a searchable flora & fauna library, and follow patrol routes on a real-time map (with offline sync).',
        shots: [
            '/portfolio/patroller/1.jpg',
            '/portfolio/patroller/2.jpg',
            '/portfolio/patroller/3.jpg',
            '/portfolio/patroller/4.jpg',
        ],
    },
    {
        name: 'Pokémon Game',
        Icon: Gamepad2,
        stack: ['React Native', 'PokéAPI'],
        blurb: 'A Pokédex catcher game powered by the PokéAPI — browse wild Pokémon, dive into stats, abilities and moves, then "capture" them to your collection and climb the top-catcher leaderboard.',
        shots: [
            '/portfolio/pokemon/1.jpg',
            '/portfolio/pokemon/2.jpg',
            '/portfolio/pokemon/3.jpg',
            '/portfolio/pokemon/4.jpg',
        ],
    },
];

const SOCIALS = {
    linkedin: 'https://linkedin.com/in/roel-longcop/',
    github: 'https://github.com/roellongcop',
    email: 'longcoproel@gmail.com',
    phone: '+63 938 407 6957',
    phoneHref: 'tel:+639384076957',
    location: 'GMA, Cavite, Philippines',
};

function scrollToId(id: string) {
    document
        .getElementById(id)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/** Fades + lifts its children into view on first scroll (no-op for reduced motion). */
function Reveal({
    children,
    className,
    delay = 0,
}: {
    children: ReactNode;
    className?: string;
    delay?: number;
}) {
    const { ref, visible } = useReveal<HTMLDivElement>();
    return (
        <div
            ref={ref}
            style={{ transitionDelay: visible ? `${delay}ms` : '0ms' }}
            className={cn(
                'transition-all duration-700 ease-out motion-reduce:transition-none',
                visible
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-6 opacity-0',
                className,
            )}
        >
            {children}
        </div>
    );
}

function SectionHeading({ kicker, title }: { kicker: string; title: string }) {
    return (
        <div className="mb-10">
            <div className="mb-2 font-mono text-xs uppercase tracking-[0.3em] text-muted-foreground">
                {kicker}
            </div>
            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                {title}
            </h2>
        </div>
    );
}

type Props = {
    canLogin: boolean;
    canRegister: boolean;
};

export default function Welcome({
    canLogin,
    canRegister,
    auth,
}: PageProps<Props>) {
    const { toggleTheme } = useTheme();
    const user = auth.user;
    const [lightbox, setLightbox] = useState<string | null>(null);

    // This page has no layout, so mount the idle-logout watcher directly. A
    // logged-in visitor on `/` would otherwise never be timed out client-side.
    useIdleLogout();

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        message: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('contact.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title="Roel R. Longcop — Full Stack Software Developer" />

            <header className="sticky top-0 z-50 border-b border-border/60 bg-background/80 backdrop-blur">
                <div className="container flex h-16 items-center justify-between">
                    <button
                        onClick={() =>
                            window.scrollTo({ top: 0, behavior: 'smooth' })
                        }
                        className="flex items-center gap-2 font-bold tracking-tight"
                    >
                        <YinYang className="h-6 w-6" />
                        <span>RL</span>
                    </button>

                    <nav className="hidden items-center gap-7 text-sm font-medium text-muted-foreground md:flex">
                        {NAV.map((item) => (
                            <button
                                key={item.id}
                                onClick={() => scrollToId(item.id)}
                                className="transition-colors hover:text-foreground"
                            >
                                {item.label}
                            </button>
                        ))}
                    </nav>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={toggleTheme}
                            title="Toggle yin / yang"
                            aria-label="Toggle light and dark theme"
                        >
                            <YinYang className="h-5 w-5" />
                        </Button>
                        {user ? (
                            <Button asChild size="sm">
                                <Link href={route('dashboard')}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                {canLogin && (
                                    <Button
                                        asChild
                                        variant="ghost"
                                        size="sm"
                                        className="hidden sm:inline-flex"
                                    >
                                        <Link href={route('login')}>
                                            Log in
                                        </Link>
                                    </Button>
                                )}
                                {canRegister && (
                                    <Button asChild size="sm">
                                        <Link href={route('register')}>
                                            Register
                                        </Link>
                                    </Button>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main>
                <section className="container grid items-center gap-12 py-20 md:grid-cols-2 md:py-28">
                    <Reveal>
                        <Badge variant="outline" className="mb-5 font-mono">
                            Full Stack · 8 years
                        </Badge>
                        <h1 className="text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">
                            Roel R. Longcop
                        </h1>
                        <p className="mt-4 max-w-md text-lg text-muted-foreground">
                            Full Stack Software Developer uniting{' '}
                            <span className="font-semibold text-foreground">
                                backend
                            </span>{' '}
                            engineering and{' '}
                            <span className="font-semibold text-foreground">
                                frontend
                            </span>{' '}
                            craft — yin and yang — into reliable, intuitive web
                            applications.
                        </p>

                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button onClick={() => scrollToId('work')}>
                                View work <ArrowRight className="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => scrollToId('contact')}
                            >
                                Get in touch
                            </Button>
                        </div>

                        <div className="mt-8 flex items-center gap-4 text-muted-foreground">
                            <a
                                href={SOCIALS.linkedin}
                                target="_blank"
                                rel="noreferrer"
                                aria-label="LinkedIn"
                                className="transition-colors hover:text-foreground"
                            >
                                <Linkedin className="h-5 w-5" />
                            </a>
                            <a
                                href={SOCIALS.github}
                                target="_blank"
                                rel="noreferrer"
                                aria-label="GitHub"
                                className="transition-colors hover:text-foreground"
                            >
                                <Github className="h-5 w-5" />
                            </a>
                            <a
                                href={`mailto:${SOCIALS.email}`}
                                aria-label="Email"
                                className="transition-colors hover:text-foreground"
                            >
                                <Mail className="h-5 w-5" />
                            </a>
                            <a
                                href={SOCIALS.phoneHref}
                                aria-label="Phone"
                                className="transition-colors hover:text-foreground"
                            >
                                <Phone className="h-5 w-5" />
                            </a>
                        </div>
                    </Reveal>

                    <Reveal delay={120} className="flex justify-center">
                        <div className="relative">
                            <YinYang className="h-64 w-64 animate-[spin_28s_linear_infinite] drop-shadow-xl motion-reduce:animate-none sm:h-80 sm:w-80" />
                            <span className="absolute -left-2 top-8 font-mono text-xs uppercase tracking-widest text-muted-foreground">
                                yin · backend
                            </span>
                            <span className="absolute -right-2 bottom-8 font-mono text-xs uppercase tracking-widest text-muted-foreground">
                                yang · frontend
                            </span>
                        </div>
                    </Reveal>
                </section>

                <section id="about" className="border-t border-border/60 py-20">
                    <div className="container">
                        <Reveal>
                            <SectionHeading
                                kicker="About"
                                title="Balance in every build"
                            />
                            <p className="max-w-3xl text-lg leading-relaxed text-muted-foreground">
                                Innovative Full Stack Software Developer with{' '}
                                <span className="font-semibold text-foreground">
                                    8 years of experience
                                </span>{' '}
                                building comprehensive and reliable web
                                applications. Skilled across the stack — pairing
                                robust back-end engineering with intuitive,
                                responsive front-ends. Dedicated to continuous
                                learning, effective problem-solving, and
                                delivering high-quality applications that
                                enhance the user experience.
                            </p>
                        </Reveal>
                    </div>
                </section>

                <section
                    id="skills"
                    className="border-t border-border/60 py-20"
                >
                    <div className="container">
                        <Reveal>
                            <SectionHeading
                                kicker="Skills"
                                title="Two halves, one whole"
                            />
                        </Reveal>
                        <Reveal delay={100}>
                            <div className="grid overflow-hidden rounded-2xl border border-border shadow-sm md:grid-cols-2">
                                <div className="bg-black p-8 text-white sm:p-10">
                                    <div className="mb-6 flex items-center gap-3">
                                        <Server className="h-5 w-5" />
                                        <h3 className="text-xl font-semibold">
                                            Backend
                                        </h3>
                                        <span className="font-mono text-xs uppercase tracking-widest text-white/50">
                                            yin
                                        </span>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {BACKEND_SKILLS.map((s) => (
                                            <span
                                                key={s}
                                                className="rounded-md border border-white/20 px-3 py-1 font-mono text-sm text-white/90"
                                            >
                                                {s}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                                <div className="bg-white p-8 text-black sm:p-10">
                                    <div className="mb-6 flex items-center gap-3">
                                        <Code2 className="h-5 w-5" />
                                        <h3 className="text-xl font-semibold">
                                            Frontend
                                        </h3>
                                        <span className="font-mono text-xs uppercase tracking-widest text-black/40">
                                            yang
                                        </span>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {FRONTEND_SKILLS.map((s) => (
                                            <span
                                                key={s}
                                                className="rounded-md border border-black/20 px-3 py-1 font-mono text-sm text-black/90"
                                            >
                                                {s}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </Reveal>
                    </div>
                </section>

                <section className="border-t border-border/60 py-20">
                    <div className="container">
                        <Reveal>
                            <SectionHeading
                                kicker="Experience"
                                title="Where I've built"
                            />
                        </Reveal>
                        <div className="space-y-6">
                            {EXPERIENCE.map((job, i) => (
                                <Reveal key={job.role} delay={i * 80}>
                                    <Card>
                                        <CardContent className="pt-6">
                                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                                <h3 className="text-lg font-semibold">
                                                    {job.role}
                                                </h3>
                                                <span className="font-mono text-sm text-muted-foreground">
                                                    {job.period}
                                                </span>
                                            </div>
                                            <div className="mb-4 text-sm text-muted-foreground">
                                                {job.place}
                                            </div>
                                            <ul className="space-y-2 text-sm text-muted-foreground">
                                                {job.points.map((p) => (
                                                    <li
                                                        key={p}
                                                        className="flex gap-2"
                                                    >
                                                        <span className="mt-2 h-1 w-1 shrink-0 rounded-full bg-foreground" />
                                                        {p}
                                                    </li>
                                                ))}
                                            </ul>
                                        </CardContent>
                                    </Card>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                <section id="work" className="border-t border-border/60 py-20">
                    <div className="container">
                        <Reveal>
                            <SectionHeading
                                kicker="Work"
                                title="Selected projects"
                            />
                        </Reveal>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {PROJECTS.map((p, i) => (
                                <Reveal key={p.name} delay={i * 80}>
                                    <a
                                        href={p.href}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="group block h-full"
                                    >
                                        <Card className="h-full transition-all hover:-translate-y-1 hover:shadow-md">
                                            <CardContent className="flex h-full flex-col pt-6">
                                                <div className="flex items-start justify-between gap-3">
                                                    <h3 className="font-semibold">
                                                        {p.name}
                                                    </h3>
                                                    <ArrowUpRight className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                                                </div>
                                                <p className="mt-2 flex-1 text-sm text-muted-foreground">
                                                    {p.blurb}
                                                </p>
                                                <span className="mt-4 font-mono text-xs text-muted-foreground">
                                                    {p.host}
                                                </span>
                                            </CardContent>
                                        </Card>
                                    </a>
                                </Reveal>
                            ))}
                        </div>

                        <Reveal className="mt-12">
                            <h3 className="mb-4 font-mono text-sm uppercase tracking-widest text-muted-foreground">
                                Mobile apps
                            </h3>
                            <div className="grid gap-4 lg:grid-cols-2">
                                {MOBILE_APPS.map((app) => (
                                    <Card key={app.name}>
                                        <CardContent className="pt-6">
                                            <div className="flex items-center gap-2">
                                                <app.Icon className="h-5 w-5" />
                                                <h4 className="font-semibold">
                                                    {app.name}
                                                </h4>
                                            </div>
                                            <div className="mt-3 flex flex-wrap gap-2">
                                                {app.stack.map((s) => (
                                                    <Badge
                                                        key={s}
                                                        variant="secondary"
                                                        className="font-normal"
                                                    >
                                                        {s}
                                                    </Badge>
                                                ))}
                                            </div>
                                            <p className="mt-3 text-sm text-muted-foreground">
                                                {app.blurb}
                                            </p>
                                            <div className="mt-4 flex gap-3 overflow-x-auto pb-1">
                                                {app.shots.map((src, i) => (
                                                    <button
                                                        key={src}
                                                        onClick={() =>
                                                            setLightbox(src)
                                                        }
                                                        className="shrink-0 overflow-hidden rounded-2xl border border-border transition-transform hover:-translate-y-1"
                                                        aria-label={`View ${app.name} screenshot ${i + 1}`}
                                                    >
                                                        <img
                                                            src={src}
                                                            alt={`${app.name} screenshot ${i + 1}`}
                                                            loading="lazy"
                                                            className="h-72 w-auto object-cover"
                                                        />
                                                    </button>
                                                ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </Reveal>

                        <Reveal className="mt-12">
                            <h3 className="mb-4 font-mono text-sm uppercase tracking-widest text-muted-foreground">
                                More on GitHub
                            </h3>
                            <div className="flex flex-wrap gap-2">
                                {GITHUB_PROJECTS.map((g) => (
                                    <Badge
                                        key={g}
                                        variant="secondary"
                                        className="font-normal"
                                    >
                                        {g}
                                    </Badge>
                                ))}
                            </div>
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="mt-5"
                            >
                                <a
                                    href={SOCIALS.github}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Github className="h-4 w-4" /> View GitHub
                                </a>
                            </Button>
                        </Reveal>
                    </div>
                </section>

                <section className="border-t border-border/60 py-20">
                    <div className="container">
                        <Reveal>
                            <Card className="overflow-hidden">
                                <CardContent className="flex flex-col items-center gap-6 px-6 py-12 text-center sm:px-12">
                                    <YinYang className="h-12 w-12" />
                                    <h2 className="max-w-2xl text-2xl font-bold tracking-tight sm:text-3xl">
                                        Step behind the curtain
                                    </h2>
                                    <p className="max-w-xl text-muted-foreground">
                                        The yin to this page's yang: a full
                                        Laravel + React admin platform I built —
                                        roles, exports, imports, backups,
                                        observability and more. Create an
                                        account to take a read-only tour of the
                                        live backend.
                                    </p>
                                    <div className="flex flex-wrap justify-center gap-3">
                                        {user ? (
                                            <Button asChild>
                                                <Link href={route('dashboard')}>
                                                    Go to dashboard{' '}
                                                    <ArrowRight className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        ) : (
                                            <>
                                                {canRegister && (
                                                    <Button asChild>
                                                        <Link
                                                            href={route(
                                                                'register',
                                                            )}
                                                        >
                                                            Register to explore{' '}
                                                            <ArrowRight className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                )}
                                                {canLogin && (
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={route(
                                                                'login',
                                                            )}
                                                        >
                                                            Log in
                                                        </Link>
                                                    </Button>
                                                )}
                                            </>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </Reveal>
                    </div>
                </section>

                <section
                    id="contact"
                    className="border-t border-border/60 py-20"
                >
                    <div className="container grid gap-12 md:grid-cols-2">
                        <Reveal>
                            <SectionHeading
                                kicker="Contact"
                                title="Let's build something"
                            />
                            <p className="mb-8 max-w-md text-muted-foreground">
                                Have a project in mind or a role to fill? Send a
                                message and I'll get back to you.
                            </p>
                            <ul className="space-y-4 text-sm">
                                <li className="flex items-center gap-3">
                                    <Mail className="h-5 w-5 text-muted-foreground" />
                                    <a
                                        href={`mailto:${SOCIALS.email}`}
                                        className="hover:underline"
                                    >
                                        {SOCIALS.email}
                                    </a>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Phone className="h-5 w-5 text-muted-foreground" />
                                    <a
                                        href={SOCIALS.phoneHref}
                                        className="hover:underline"
                                    >
                                        {SOCIALS.phone}
                                    </a>
                                </li>
                                <li className="flex items-center gap-3">
                                    <MapPin className="h-5 w-5 text-muted-foreground" />
                                    {SOCIALS.location}
                                </li>
                            </ul>
                        </Reveal>

                        <Reveal delay={100}>
                            <Card>
                                <CardContent className="pt-6">
                                    <form
                                        onSubmit={submit}
                                        className="space-y-5"
                                    >
                                        <div>
                                            <Label htmlFor="name">Name</Label>
                                            <Input
                                                id="name"
                                                className="mt-1"
                                                value={data.name}
                                                onChange={(e) =>
                                                    setData(
                                                        'name',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errors.name}
                                                className="mt-1"
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="email">Email</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                className="mt-1"
                                                value={data.email}
                                                onChange={(e) =>
                                                    setData(
                                                        'email',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errors.email}
                                                className="mt-1"
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="message">
                                                Message
                                            </Label>
                                            <Textarea
                                                id="message"
                                                rows={5}
                                                className="mt-1"
                                                value={data.message}
                                                onChange={(e) =>
                                                    setData(
                                                        'message',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errors.message}
                                                className="mt-1"
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full"
                                        >
                                            <Send className="h-4 w-4" /> Send
                                            message
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>
                        </Reveal>
                    </div>
                </section>
            </main>

            <footer className="border-t border-border/60 py-10">
                <div className="container flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <YinYang className="h-5 w-5" />
                        <span>
                            © {new Date().getFullYear()} Roel R. Longcop · Built
                            with Laravel + React
                        </span>
                    </div>
                    <div className="flex items-center gap-4 text-muted-foreground">
                        <a
                            href={SOCIALS.linkedin}
                            target="_blank"
                            rel="noreferrer"
                            aria-label="LinkedIn"
                            className="transition-colors hover:text-foreground"
                        >
                            <Linkedin className="h-5 w-5" />
                        </a>
                        <a
                            href={SOCIALS.github}
                            target="_blank"
                            rel="noreferrer"
                            aria-label="GitHub"
                            className="transition-colors hover:text-foreground"
                        >
                            <Github className="h-5 w-5" />
                        </a>
                        {!user && canLogin && (
                            <Link
                                href={route('login')}
                                className="text-sm transition-colors hover:text-foreground"
                            >
                                Log in
                            </Link>
                        )}
                    </div>
                </div>
            </footer>

            <Dialog
                open={lightbox !== null}
                onOpenChange={(o) => !o && setLightbox(null)}
            >
                <DialogContent className="max-w-md border-none bg-transparent p-0 shadow-none">
                    <DialogTitle className="sr-only">
                        App screenshot
                    </DialogTitle>
                    {lightbox && (
                        <img
                            src={lightbox}
                            alt="App screenshot"
                            className="mx-auto max-h-[85vh] w-auto rounded-2xl border border-border"
                        />
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}

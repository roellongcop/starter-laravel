/** Classic yin-yang mark — backend (yin) and frontend (yang) in one symbol. */
export default function YinYang({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 100 100" className={className} aria-hidden="true">
            <circle
                cx="50"
                cy="50"
                r="49"
                className="fill-white stroke-black"
            />
            <path
                d="M50 1 a49 49 0 0 1 0 98 a24.5 24.5 0 0 1 0-49 a24.5 24.5 0 0 0 0-49 z"
                className="fill-black"
            />
            <circle cx="50" cy="25.5" r="7" className="fill-white" />
            <circle cx="50" cy="74.5" r="7" className="fill-black" />
        </svg>
    );
}

// mammoth's browser build (used for client-side .docx → HTML) ships no types.
// This must live in a script-context (no imports) file so it declares an
// ambient shorthand module rather than a module augmentation.
declare module 'mammoth/mammoth.browser' {
    export function convertToHtml(input: {
        arrayBuffer: ArrayBuffer;
    }): Promise<{ value: string; messages: unknown[] }>;
}

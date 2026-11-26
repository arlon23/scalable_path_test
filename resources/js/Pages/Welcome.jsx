import { Head } from '@inertiajs/react';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />

            <main className="min-h-screen bg-slate-950 text-white">
                <div className="mx-auto flex min-h-screen max-w-3xl items-center justify-center px-6">
                    <div className="text-center">
                        <p className="mb-3 text-sm font-semibold uppercase tracking-[0.25em] text-sky-400">
                            Laravel + React
                        </p>
                        <h1 className="text-4xl font-bold sm:text-6xl">Frontend ready</h1>
                        <p className="mt-5 text-lg text-slate-300">
                            Start building your application with Inertia and React.
                        </p>
                    </div>
                </div>
            </main>
        </>
    );
}

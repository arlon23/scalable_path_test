import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const sourceStyles = {
    Google: 'bg-blue-50 text-blue-700 ring-blue-200',
    Tripadvisor: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    Facebook: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
};

function formatDate(value) {
    if (!value) {
        return 'Date unavailable';
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'UTC',
    });
}

function getInitials(name) {
    return (name || 'Customer')
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
}

function ReviewCard({ review }) {
    const responded = Boolean(review.response_date || review.response);
    const rating = Number(review.rating || 0);
    const [editingResponse, setEditingResponse] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [responseText, setResponseText] = useState(review.response?.response || '');
    const [respondedBy, setRespondedBy] = useState(review.response?.responded_by || '');
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);
    const responseUrl = `/reviews/${review.id}/response`;

    function submitResponse(event) {
        event.preventDefault();
        setErrors({});

        const options = {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onError: (validationErrors) => setErrors(validationErrors),
            onFinish: () => setProcessing(false),
            onSuccess: () => setEditingResponse(false),
        };
        const values = {
            response: responseText,
            responded_by: respondedBy,
        };

        if (review.response) {
            router.put(responseUrl, values, options);
        } else {
            router.post(responseUrl, values, options);
        }
    }

    function deleteResponse() {
        router.delete(responseUrl, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setConfirmingDelete(false);
            },
            onSuccess: () => {
                setEditingResponse(false);
                setResponseText('');
                setRespondedBy('');
                setErrors({});
            },
        });
    }

    function cancelEditing() {
        setResponseText(review.response?.response || '');
        setRespondedBy(review.response?.responded_by || '');
        setErrors({});
        setEditingResponse(false);
    }

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03] sm:p-6">
            <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                    <div className="flex size-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600">
                        {getInitials(review.customer_name)}
                    </div>
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <h3 className="truncate font-semibold text-slate-900">
                                {review.customer_name || 'Customer'}
                            </h3>
                            <span
                                className={`rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ${sourceStyles[review.source.source] || 'bg-slate-50 text-slate-600 ring-slate-200'}`}
                            >
                                {review.source.source}
                            </span>
                        </div>
                        <p className="mt-1 truncate text-sm text-slate-500">
                            {review.customer_email || 'No email provided'}
                        </p>
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-3 sm:flex-col sm:items-end sm:gap-1">
                    <div
                        className="flex gap-0.5 text-amber-400"
                        aria-label={`${rating} out of 5 stars`}
                    >
                        {Array.from({ length: 5 }, (_, index) => (
                            <span
                                key={index}
                                className={index < Math.round(rating) ? '' : 'text-slate-200'}
                                aria-hidden="true"
                            >
                                ★
                            </span>
                        ))}
                    </div>
                    <time className="text-xs text-slate-500">
                        {formatDate(review.review_date)}
                    </time>
                </div>
            </div>

            <div className="mt-5">
                {review.review_title && (
                    <h4 className="font-semibold text-slate-900">{review.review_title}</h4>
                )}
                {review.review_text && (
                    <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                        {review.review_text}
                    </p>
                )}
                {!review.review_title && !review.review_text && (
                    <p className="text-sm italic text-slate-400">No written feedback.</p>
                )}
            </div>

            <div className="mt-5 border-t border-slate-100 pt-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <span
                            className={`size-2 rounded-full ${responded ? 'bg-emerald-500' : 'bg-amber-400'}`}
                            aria-hidden="true"
                        />
                        <span
                            className={`text-sm font-medium ${responded ? 'text-emerald-700' : 'text-amber-700'}`}
                        >
                            {responded ? 'Responded' : 'Needs response'}
                        </span>
                        {responded && (
                            <time className="text-xs text-slate-500">
                                {formatDate(review.response_date)}
                            </time>
                        )}
                    </div>
                    {review.response?.responded_by && (
                        <span className="text-xs text-slate-500">
                            by {review.response.responded_by}
                        </span>
                    )}
                </div>
                {review.response?.response && (
                    <div className="mt-3 rounded-xl bg-slate-50 px-4 py-3">
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Response text
                        </p>
                        <p className="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">
                            {review.response.response}
                        </p>
                    </div>
                )}
                <div className="mt-4 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            setErrors({});
                            setEditingResponse((isEditing) => !isEditing);
                        }}
                        className="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                    >
                        {review.response ? 'Edit response' : 'Write response'}
                    </button>
                    {review.response && !confirmingDelete && (
                        <button
                            type="button"
                            onClick={() => setConfirmingDelete(true)}
                            className="rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100"
                        >
                            Delete response
                        </button>
                    )}
                </div>
                {confirmingDelete && (
                    <div className="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-rose-100 bg-rose-50/70 px-4 py-3">
                        <p className="text-sm text-rose-800">
                            Remove this response? Any existing response date will be kept.
                        </p>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setConfirmingDelete(false)}
                                className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={deleteResponse}
                                disabled={processing}
                                className="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? 'Deleting…' : 'Confirm delete'}
                            </button>
                        </div>
                    </div>
                )}
                {editingResponse && (
                    <form
                        className="mt-4 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"
                        onSubmit={submitResponse}
                    >
                        <label className="grid gap-1.5 text-sm font-medium text-slate-700">
                            Response
                            <textarea
                                value={responseText}
                                onChange={(event) => setResponseText(event.target.value)}
                                rows={4}
                                maxLength={10000}
                                required
                                className="w-full resize-y rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 outline-none placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                placeholder="Write a thoughtful response..."
                            />
                            {errors.response && (
                                <span className="text-sm font-normal text-rose-600" role="alert">
                                    {errors.response}
                                </span>
                            )}
                        </label>
                        <label className="grid gap-1.5 text-sm font-medium text-slate-700">
                            Responded by <span className="font-normal text-slate-400">Optional</span>
                            <input
                                type="text"
                                value={respondedBy}
                                onChange={(event) => setRespondedBy(event.target.value)}
                                maxLength={255}
                                className="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-normal text-slate-900 outline-none placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                placeholder="Name"
                            />
                            {errors.responded_by && (
                                <span className="text-sm font-normal text-rose-600" role="alert">
                                    {errors.responded_by}
                                </span>
                            )}
                        </label>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-xs text-slate-500">
                                Saving sets the response date to now if no date is already recorded.
                            </p>
                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={cancelEditing}
                                    className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-white"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing
                                        ? 'Saving…'
                                        : review.response
                                          ? 'Update response'
                                          : 'Save response'}
                                </button>
                            </div>
                        </div>
                    </form>
                )}
            </div>
        </article>
    );
}

function StatCard({ label, value, detail, accent }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-950/[0.03]">
            <div className="flex items-center justify-between gap-4">
                <p className="text-sm font-medium text-slate-500">{label}</p>
                <span className={`size-2.5 rounded-full ${accent}`} aria-hidden="true" />
            </div>
            <p className="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{value}</p>
            <p className="mt-1 text-xs text-slate-500">{detail}</p>
        </div>
    );
}

function PaginationLink({ link, label, active = false }) {
    const className = `inline-flex min-w-9 items-center justify-center rounded-lg px-3 py-2 text-sm font-medium ${
        active
            ? 'bg-slate-900 text-white'
            : link?.url
              ? 'text-slate-600 hover:bg-slate-100'
              : 'cursor-not-allowed text-slate-300'
    }`;

    if (!link?.url) {
        return (
            <span className={className} aria-disabled="true">
                {label}
            </span>
        );
    }

    return (
        <Link href={link.url} className={className} preserveScroll>
            {label}
        </Link>
    );
}

export default function Index({ reviews, sources, filters, stats }) {
    const [search, setSearch] = useState(filters.search);
    const [sourceId, setSourceId] = useState(filters.source_id);
    const [status, setStatus] = useState(filters.status);
    const hasFilters = Boolean(filters.search || filters.source_id || filters.status !== 'all');

    function submitFilters(event) {
        event.preventDefault();

        router.get(
            '/',
            {
                search: search.trim() || undefined,
                source_id: sourceId || undefined,
                status: status === 'all' ? undefined : status,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    const pageLinks = reviews.links.slice(1, -1);

    return (
        <>
            <Head title="Customer reviews" />

            <div className="min-h-screen bg-slate-50 text-slate-900">
                <aside className="fixed inset-y-0 left-0 z-10 hidden w-64 flex-col border-r border-slate-200 bg-white px-5 py-6 lg:flex">
                    <Link href="/" className="flex items-center gap-3 px-2">
                        <span className="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-lg shadow-indigo-600/20">
                            RH
                        </span>
                        <span>
                            <span className="block font-semibold tracking-tight text-slate-900">
                                ReviewHub
                            </span>
                            <span className="block text-xs text-slate-500">Customer insights</span>
                        </span>
                    </Link>

                    <div className="mt-10">
                        <p className="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Workspace
                        </p>
                        <nav className="mt-3">
                            <Link
                                href="/"
                                className="flex items-center gap-3 rounded-xl bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-700"
                                aria-current="page"
                            >
                                <span aria-hidden="true">▤</span>
                                All reviews
                            </Link>
                        </nav>
                    </div>

                    <div className="mt-auto rounded-2xl bg-slate-900 p-4 text-white">
                        <p className="text-sm font-semibold">Every voice, together.</p>
                        <p className="mt-1 text-xs leading-5 text-slate-300">
                            Reviews from all your available sources in one inbox.
                        </p>
                    </div>
                </aside>

                <div className="lg:pl-64">
                    <header className="border-b border-slate-200 bg-white">
                        <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                            <div className="flex items-center gap-3 lg:hidden">
                                <span className="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-xs font-bold text-white">
                                    RH
                                </span>
                                <span className="font-semibold text-slate-900">ReviewHub</span>
                            </div>
                            <p className="hidden text-sm text-slate-500 sm:block">
                                Customer feedback workspace
                            </p>
                            <div className="rounded-full bg-slate-100 px-3 py-1.5">
                                <span className="text-xs font-medium text-slate-600">
                                    {sources.length} review sources
                                </span>
                            </div>
                        </div>
                    </header>

                    <main className="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-10">
                        <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                            <div>
                                <p className="text-sm font-semibold text-indigo-600">Overview</p>
                                <h1 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                                    Customer reviews
                                </h1>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                    A single place to read feedback, track replies, and understand how
                                    customers feel.
                                </p>
                            </div>
                            <p className="text-sm text-slate-500">
                                Showing {reviews.from ?? 0}–{reviews.to ?? 0} of {reviews.total} reviews
                            </p>
                        </div>

                        <section
                            className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                            aria-label="Review summary"
                        >
                            <StatCard
                                label="Total reviews"
                                value={stats.total.toLocaleString()}
                                detail="Across all sources"
                                accent="bg-indigo-500"
                            />
                            <StatCard
                                label="Average rating"
                                value={stats.total ? `${Number(stats.average_rating).toFixed(1)} / 5` : '—'}
                                detail="Overall customer sentiment"
                                accent="bg-amber-400"
                            />
                            <StatCard
                                label="Responded"
                                value={stats.responded.toLocaleString()}
                                detail="Response text or date on record"
                                accent="bg-emerald-500"
                            />
                            <StatCard
                                label="Needs response"
                                value={stats.needs_response.toLocaleString()}
                                detail="No response text or date recorded"
                                accent="bg-orange-400"
                            />
                        </section>

                        <section className="mt-10">
                            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-slate-900">
                                        Review inbox
                                    </h2>
                                    <p className="mt-1 text-sm text-slate-500">
                                        Search customer details and review content.
                                    </p>
                                </div>
                                {hasFilters && (
                                    <Link
                                        href="/"
                                        className="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                                    >
                                        Clear filters
                                    </Link>
                                )}
                            </div>

                            <form
                                className="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-950/[0.03] md:grid-cols-[minmax(16rem,1fr)_12rem_12rem_auto]"
                                onSubmit={submitFilters}
                            >
                                <label className="relative block">
                                    <span className="sr-only">Search reviews</span>
                                    <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.8"
                                            className="size-5"
                                            aria-hidden="true"
                                        >
                                            <circle cx="11" cy="11" r="7" />
                                            <path d="m20 20-4-4" />
                                        </svg>
                                    </span>
                                    <input
                                        type="search"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Search name, email, title or review..."
                                        className="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                    />
                                </label>

                                <label>
                                    <span className="sr-only">Filter by source</span>
                                    <select
                                        value={sourceId}
                                        onChange={(event) => setSourceId(event.target.value)}
                                        className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                    >
                                        <option value="">All sources</option>
                                        {sources.map((source) => (
                                            <option key={source.id} value={source.id}>
                                                {source.source}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label>
                                    <span className="sr-only">Filter by response status</span>
                                    <select
                                        value={status}
                                        onChange={(event) => setStatus(event.target.value)}
                                        className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                    >
                                        <option value="all">All statuses</option>
                                        <option value="responded">Responded</option>
                                        <option value="needs_response">Needs response</option>
                                    </select>
                                </label>

                                <button
                                    type="submit"
                                    className="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
                                >
                                    Search
                                </button>
                            </form>

                            {reviews.data.length > 0 ? (
                                <div className="mt-5 grid gap-4">
                                    {reviews.data.map((review) => (
                                        <ReviewCard key={review.id} review={review} />
                                    ))}
                                </div>
                            ) : (
                                <div className="mt-5 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                                    <div className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.6"
                                            className="size-6"
                                            aria-hidden="true"
                                        >
                                            <path d="M7 3.75h7l5 5v11.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" />
                                            <path d="M14 4v5h5M9 13h6M9 16.5h6" />
                                        </svg>
                                    </div>
                                    <h3 className="mt-4 font-semibold text-slate-900">No reviews found</h3>
                                    <p className="mt-1 text-sm text-slate-500">
                                        Try a different search or clear the selected filters.
                                    </p>
                                </div>
                            )}

                            {reviews.last_page > 1 && (
                                <nav
                                    className="mt-6 flex flex-col items-center justify-between gap-4 sm:flex-row"
                                    aria-label="Review pages"
                                >
                                    <p className="text-sm text-slate-500">
                                        Page {reviews.current_page} of {reviews.last_page}
                                    </p>
                                    <div className="flex items-center gap-1">
                                        <PaginationLink link={reviews.links[0]} label="Previous" />
                                        {pageLinks.map((link, index) => (
                                            <PaginationLink
                                                key={`${link.label}-${index}`}
                                                link={link}
                                                label={link.label}
                                                active={link.active}
                                            />
                                        ))}
                                        <PaginationLink
                                            link={reviews.links[reviews.links.length - 1]}
                                            label="Next"
                                        />
                                    </div>
                                </nav>
                            )}
                        </section>
                    </main>
                </div>
            </div>
        </>
    );
}

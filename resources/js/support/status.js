export function formatStatus(status) {
    if (typeof status !== 'string' || status.trim() === '') {
        return 'Unknown';
    }

    return status
        .trim()
        .toLowerCase()
        .split(/\s+/)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

export function statusClass(status) {
    const normalized = String(status ?? '').trim().toLowerCase();

    if (normalized === 'done' || normalized === 'closed' || normalized === 'resolved') {
        return 'status-done';
    }

    if (normalized.includes('progress') || normalized.includes('development')) {
        return 'status-progress';
    }

    if (normalized === 'to do' || normalized === 'todo' || normalized === 'open') {
        return 'status-todo';
    }

    return 'status-neutral';
}

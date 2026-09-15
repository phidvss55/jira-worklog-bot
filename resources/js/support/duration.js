export const MIN_DURATION_MINUTES = 15;
export const MAX_DURATION_MINUTES = 420;
export const DURATION_STEP_MINUTES = 15;

export const durationPresets = [15, 30, 45, 60, 90, 120, 180, 240, 300, 360, 420];

export function formatDuration(minutes) {
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (hours === 0) {
        return `${remainingMinutes}m`;
    }

    if (remainingMinutes === 0) {
        return `${hours}h`;
    }

    return `${hours}h ${remainingMinutes}m`;
}

export function durationForApi(minutes) {
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (hours === 0) {
        return `${remainingMinutes}m`;
    }

    if (remainingMinutes === 0) {
        return `${hours}h`;
    }

    return `${hours}h${remainingMinutes}m`;
}

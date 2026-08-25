import { useEcho } from '@laravel/echo-vue';
import { ref } from 'vue';

type OutputEvent = {
    chunk: string;
    sequence: number;
};

type FinishedEvent = {
    status: string;
};

/**
 * Subscribe to a team-scoped output channel, seeding from persisted output
 * and appending only chunks not already seen (guards against a duplicate
 * delivery on reconnect).
 */
export function useLiveOutput(channel: string, initialOutput: string | null) {
    const output = ref(initialOutput ?? '');
    const finished = ref(false);
    const finalStatus = ref<string | null>(null);
    const seenSequences = new Set<number>();

    useEcho<OutputEvent>(channel, '.output', (event) => {
        if (seenSequences.has(event.sequence)) {
            return;
        }

        seenSequences.add(event.sequence);
        output.value += event.chunk;
    });

    useEcho<FinishedEvent>(channel, '.finished', (event) => {
        finished.value = true;
        finalStatus.value = event.status;
    });

    return { output, finished, finalStatus };
}

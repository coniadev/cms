import { writable, get } from 'svelte/store';
import req from './req';

let settings = writable({});
let system = writable({
    sections: [{
        title: 'Section'
    }]
});


async function loadSettings() {
    let response = await req.get('/settings');

    if (response.ok) {
        settings.set(response.data);
    } else {
        throw new Error('Fatal error while requesting settings');
    }
}

async function boot() {
    let response = await req.get('/boot');

    if (response.ok) {
        system.set(response.data);
    } else {
        throw new Error('Fatal error while requesting settings');
    }
}

function getSettings() {
    return get(settings);
}

export {
    loadSettings,
    boot,
    getSettings,
    settings,
    system,
}
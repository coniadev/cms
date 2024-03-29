<script lang="ts">
    import type { FileItem } from '$types/data';
    import { _ } from '$lib/locale';
    import { system } from '$lib/sys';
    import IcoDocument from '$shell/icons/IcoDocument.svelte';
    import IcoDownload from '$shell/icons/IcoDownload.svelte';
    import IcoTrash from '$shell/icons/IcoTrash.svelte';
    import IcoPencil from '$shell/icons/IcoPencil.svelte';

    export let path: string;
    export let asset: FileItem;
    export let remove: () => void;
    export let edit: () => void;
    export let loading: boolean;

    let title = '';

    function getTitle(asset: FileItem) {
        if (asset.title) {
            if (typeof asset.title === 'string') {
                return asset.title;
            }

            for (const locale of $system.locales) {
                if (asset.title[locale.id]) {
                    return asset.title[locale.id];
                }
            }
        }

        return '';
    }

    $: title = getTitle(asset);
</script>

{#if asset}
    <div class="file pl-4">
        <IcoDocument />
        <div class="flex-grow text-left pl-3 truncate">
            <b class="font-semibold">{asset.file}</b>
            <span class="inline-block pl-4">{title}</span>
        </div>
        {#if loading}
            <div>Loading ...</div>
        {/if}
        <IcoDownload />
        <a
            href="{path}/{asset.file}"
            target="_blank"
            class="inline-block pl-2">
            {_('Datei herunterladen')}
        </a>

        <button
            on:click={edit}
            class="text-sky-800">
            <span class="inline-block h-4 w-4 ml-4 flex items-center">
                <IcoPencil />
            </span>
        </button>

        <button
            on:click={remove}
            class="text-rose-800">
            <span class="inline-block h-4 w-4 ml-4 flex items-center">
                <IcoTrash />
            </span>
        </button>
    </div>
{/if}

<style lang="postcss">
    .file {
        @apply flex flex-row items-center w-full;
        @apply bg-gray-100 border border-gray-300 py-2 px-4 text-center rounded-lg;
        @apply relative text-gray-600;
    }
</style>

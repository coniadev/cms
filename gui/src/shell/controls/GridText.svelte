<script lang="ts">
    import type { GridText } from '$types/data';
    import type { GridField } from '$types/fields';

    export let field: GridField;
    export let item: GridText;
    export let index: number;

    let showSettings = false;
</script>

<div class="grid-cell-header">
    <slot edit={() => (showSettings = !showSettings)} />
</div>
<div class="grid-cell-body flex-grow">
    {#if showSettings}
        <div>Keine Einstellungsmöglichkeiten vorhanden</div>
    {:else}
        <textarea
            name={field.name + '_' + index}
            bind:value={item.value} />
    {/if}
</div>

<style lang="postcss">
    textarea {
        height: 100%;
    }
</style>

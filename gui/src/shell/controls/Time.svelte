<script lang="ts">
    import Field from '$shell/Field.svelte';
    import Label from '$shell/Label.svelte';
    import type { TextData } from '$types/data';
    import type { SimpleField } from '$types/fields';

    export let field: SimpleField;
    export let data: TextData;
</script>

<Field required={field.required}>
    <Label of={field.name}>
        {field.label}
    </Label>
    <div class="mt-2">
        <input
            id={field.name}
            name={field.name}
            type="time"
            required={field.required}
            bind:value={data.value} />
    </div>
</Field>

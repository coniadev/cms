<script>
    import { _ } from '../lib/locale';
    import NavToggle from './NavToggle.svelte';
</script>

<style type="postcss">
    #searchbar {
        justify-content: center;
    }

    .controls {
        display: flex;
        width: 100%;
        max-width: var(--s-6xl);
        margin: 0 var(--s-4) 0 var(--s-14);

        & > div {
            display: flex;
            flex-grow: 1;
            align-items: center;

            border: var(--border);
            border-radius: var(--radius-full);

            svg {
                height: var(--s-5);
                width: var(--s-5);
                margin-left: var(--s-3);
                color: var(--gray-400);
            }

            input {
                flex-grow: 1;
                height: var(--s-10);
                box-shadow: none;
                border: 0;
                border-radius: 0 var(--radius-full) var(--radius-full) 0;

                &:focus {
                    outline: none;
                }
            }
        }

        button {
            display: flex;
            justify-content: center;
            align-items: center;
            height: var(--s-10);
            width: var(--s-10);
            padding: 0;
            margin-left: var(--s-2);

            border-radius: var(--radius-full);

            svg {
                margin: 0;
                box-shadow: none;
            }
        }
    }

    @media (--lg) {
        .controls {
            margin: 0 var(--s-16);
        }
    }
</style>

<div id="searchbar" class="headerbar">
    <NavToggle />
    <div class="controls">
        <div>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor">
                <path
                    fill-rule="evenodd"
                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                    clip-rule="evenodd" />
            </svg>
            <input type="text" placeholder={_('Search')} />
        </div>
        <button>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor">
                <path
                    fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
            </svg>
        </button>
    </div>
</div>
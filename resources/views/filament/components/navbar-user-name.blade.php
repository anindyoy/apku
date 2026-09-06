@auth
    <style>
        [data-testid="navbar-user-name"] {
            color: var(--color-gray-700);
        }

        .dark [data-testid="navbar-user-name"] {
            color: var(--color-gray-200);
        }
    </style>

    <span
        class="hidden max-w-48 truncate text-sm font-medium sm:inline"
        data-testid="navbar-user-name"
    >
        Hai, {{ auth()->user()->name }}
    </span>
@endauth

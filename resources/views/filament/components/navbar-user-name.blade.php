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
    @if (auth()->user()->trialPremiumAktif())
        <span
            class="inline-flex items-center rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-800 [.dark_&]:bg-teal-950 [.dark_&]:text-teal-200"
            data-testid="trial-premium-badge"
        >
            Trial
        </span>
    @endif
@endauth

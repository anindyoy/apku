@auth
    <span
        class="hidden max-w-48 truncate text-sm font-medium text-gray-700 sm:inline dark:text-gray-200"
        data-testid="navbar-user-name"
    >
        Hai, {{ auth()->user()->name }}
    </span>
@endauth

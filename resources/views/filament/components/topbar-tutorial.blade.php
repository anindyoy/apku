@auth
    <x-filament::icon-button
        tag="a"
        :href="route('tutorial')"
        target="_blank"
        rel="noopener noreferrer"
        icon="heroicon-o-question-mark-circle"
        color="gray"
        label="Tutorial Penggunaan"
        tooltip="Tutorial Penggunaan"
        data-testid="topbar-tutorial"
        :data-tutorial-url="route('tutorial')"
        :data-panel-url="\Filament\Facades\Filament::getPanel('admin')->getUrl()"
    />
    @once
        <script type="module" src="{{ asset('js/tutorial-context.js') }}"></script>
    @endonce
@endauth

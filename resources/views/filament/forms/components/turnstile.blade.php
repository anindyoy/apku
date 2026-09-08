<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="{ widgetId: null }"
        x-init="
            const renderWidget = () => {
                widgetId = window.turnstile.render($refs.widget, {
                    sitekey: @js($siteKey),
                    action: @js($action),
                    callback: (token) => $wire.set(@js($getStatePath()), token),
                    'expired-callback': () => $wire.set(@js($getStatePath()), null),
                    'error-callback': () => $wire.set(@js($getStatePath()), null),
                })
            }

            window.turnstile ? renderWidget() : window.addEventListener('apku-turnstile-siap', renderWidget, { once: true })
        "
        x-on:turnstile-reset.window="
            if (widgetId !== null && window.turnstile) {
                window.turnstile.reset(widgetId)
            }
        "
    >
        <div x-ref="widget"></div>
    </div>

    @once
        <script>
            window.apkuTurnstileSiap = () => window.dispatchEvent(new CustomEvent('apku-turnstile-siap'))
        </script>
        <script
            src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=apkuTurnstileSiap&render=explicit"
            async
            defer
        ></script>
    @endonce
</x-dynamic-component>

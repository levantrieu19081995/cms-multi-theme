@php
    $copyIconOnly = (bool) ($iconOnly ?? false);
    $copyClass = $class ?? 'btn btn-sm btn-outline-secondary';
    $copyLabel = __('admin.survey.copy_link');
@endphp

<button type="button"
        class="{{ $copyClass }}"
        data-survey-copy-link
        data-copy-url="{{ $url }}"
        data-copy-label="{{ $copyLabel }}"
        data-copied-label="{{ __('admin.survey.link_copied') }}"
        data-copy-failed-label="{{ __('admin.survey.copy_link_failed') }}"
        title="{{ $copyLabel }}"
        aria-label="{{ $copyLabel }}">
    <i class="ri-link" data-survey-copy-icon aria-hidden="true"></i>
    <span data-survey-copy-label class="{{ $copyIconOnly ? 'visually-hidden' : 'ms-1' }}">{{ $copyLabel }}</span>
</button>

@once
    <script>
        (() => {
            const fallbackCopy = (text) => {
                const input = document.createElement('textarea');
                input.value = text;
                input.setAttribute('readonly', '');
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();

                try {
                    return document.execCommand('copy');
                } finally {
                    input.remove();
                }
            };

            const copyText = async (text) => {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                    return true;
                }

                return fallbackCopy(text);
            };

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-survey-copy-link]');
                if (! button || button.disabled) return;

                const label = button.querySelector('[data-survey-copy-label]');
                const icon = button.querySelector('[data-survey-copy-icon]');
                const originalLabel = button.dataset.copyLabel;
                let message = button.dataset.copiedLabel;
                let copied = false;

                button.disabled = true;
                try {
                    copied = await copyText(button.dataset.copyUrl);
                    if (! copied) message = button.dataset.copyFailedLabel;
                } catch (error) {
                    message = button.dataset.copyFailedLabel;
                }

                if (label) label.textContent = message;
                button.title = message;
                button.setAttribute('aria-label', message);
                button.classList.toggle('btn-outline-secondary', ! copied);
                button.classList.toggle('btn-outline-success', copied);
                if (icon) icon.className = copied ? 'ri-check-line' : 'ri-error-warning-line';

                window.setTimeout(() => {
                    if (label) label.textContent = originalLabel;
                    button.title = originalLabel;
                    button.setAttribute('aria-label', originalLabel);
                    button.classList.remove('btn-outline-success');
                    button.classList.add('btn-outline-secondary');
                    if (icon) icon.className = 'ri-link';
                    button.disabled = false;
                }, 1800);
            });
        })();
    </script>
@endonce

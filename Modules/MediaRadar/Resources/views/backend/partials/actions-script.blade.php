<script type="text/javascript">
    /**
     * Media Radar editorial actions.
     *
     * The shared backend helper only handles GET/PUT/PATCH/DELETE links, so the
     * POST actions (approve, reject, publish, archive, analyse, trust, block)
     * are posted from here and the datatable is reloaded when one succeeds.
     */
    document.addEventListener('DOMContentLoaded', function () {
        const token = '{{ csrf_token() }}';

        function notify(success, message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: success ? '{{ __('messages.success') }}' : '{{ __('messages.error') }}',
                    text: message,
                    icon: success ? 'success' : 'error'
                });
                return;
            }

            alert(message);
        }

        function send(button) {
            const payload = {};

            (button.dataset.payload || '').split('&').filter(Boolean).forEach(function (pair) {
                const parts = pair.split('=');
                payload[parts[0]] = decodeURIComponent(parts[1] || '');
            });

            button.disabled = true;

            fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    notify(result.ok && result.data.status, result.data.message || '');

                    if (result.ok && result.data.status) {
                        if (button.dataset.redirect) {
                            window.location.href = button.dataset.redirect;
                            return;
                        }

                        if (typeof renderedDataTable !== 'undefined' && renderedDataTable) {
                            renderedDataTable.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    }
                })
                .catch(function () {
                    notify(false, '{{ __('messages.something_went_wrong') }}');
                })
                .finally(function () {
                    button.disabled = false;
                });
        }

        document.addEventListener('click', function (event) {
            const button = event.target.closest('.media-radar-action');

            if (!button) {
                return;
            }

            event.preventDefault();

            if (button.dataset.confirm && typeof Swal !== 'undefined') {
                Swal.fire({
                    title: button.dataset.confirm,
                    icon: 'question',
                    showCancelButton: true,
                    reverseButtons: true
                }).then(function (response) {
                    if (response.isConfirmed) {
                        send(button);
                    }
                });
                return;
            }

            if (button.dataset.confirm && !window.confirm(button.dataset.confirm)) {
                return;
            }

            send(button);
        });
    });
</script>

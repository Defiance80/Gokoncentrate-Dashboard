<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        const scheduleType = document.getElementById('schedule_type');
        const intervalWrapper = document.getElementById('interval_wrapper');

        function sync() {
            if (!scheduleType || !intervalWrapper) {
                return;
            }

            intervalWrapper.style.display = scheduleType.value === 'interval' ? '' : 'none';
        }

        if (scheduleType) {
            scheduleType.addEventListener('change', sync);

            // select2 replaces the native element, so listen on jQuery too.
            if (window.jQuery) {
                window.jQuery(scheduleType).on('change', sync);
            }
        }

        sync();
    });
</script>

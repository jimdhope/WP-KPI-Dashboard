(function($) {
    'use strict';

    $(document).ready(function() {
        // Form validation
        $('.kpi-add-metric-form form').on('submit', function(e) {
            const $form = $(this);
            const $userId = $form.find('#user_id');
            const $metricName = $form.find('#metric_name');
            const $metricValue = $form.find('#metric_value');
            
            if (!$userId.val()) {
                alert('Please select a user');
                e.preventDefault();
                return false;
            }
            
            if (!$metricName.val().trim()) {
                alert('Please enter a metric name');
                e.preventDefault();
                return false;
            }
            
            if (!$metricValue.val() || isNaN($metricValue.val())) {
                alert('Please enter a valid numeric value');
                e.preventDefault();
                return false;
            }
        });
    });
})(jQuery);
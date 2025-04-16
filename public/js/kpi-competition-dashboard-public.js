(function($) {
    'use strict';

    // KPI Dashboard Class
    class KPILeaderboard {
        constructor(element) {
            this.element = element;
            this.metric = element.data('metric');
            this.limit = element.data('limit');
            this.refreshInterval = 300000; // 5 minutes
            this.init();
        }

        init() {
            this.setupRefresh();
            this.setupAnimations();
        }

        setupRefresh() {
            // Initial load
            this.refreshData();

            // Set up periodic refresh
            setInterval(() => this.refreshData(), this.refreshInterval);
        }

        refreshData() {
            $.ajax({
                url: kpiDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_kpi_metrics',
                    nonce: kpiDashboard.nonce,
                    metric: this.metric,
                    limit: this.limit
                },
                success: (response) => {
                    if (response.success) {
                        this.updateLeaderboard(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error updating KPI leaderboard:', error);
                }
            });
        }

        updateLeaderboard(data) {
            const tbody = this.element.find('tbody');
            tbody.empty();

            data.forEach((item, index) => {
                const row = $('<tr>')
                    .addClass(index < 3 ? `top-three rank-${index + 1}` : '')
                    .append(
                        $('<td>').addClass('rank').text(index + 1),
                        $('<td>').addClass('user').text(item.user_name),
                        $('<td>').addClass('value').text(
                            new Intl.NumberFormat().format(item.total_value)
                        )
                    );
                tbody.append(row);
            });

            this.animateUpdates();
        }

        setupAnimations() {
            this.element.find('tr').css('opacity', 0).each(function(index) {
                $(this).delay(100 * index).animate({ opacity: 1 }, 500);
            });
        }

        animateUpdates() {
            this.element.find('tr').each(function(index) {
                $(this)
                    .css({ backgroundColor: '#ffeb3b' })
                    .delay(100 * index)
                    .animate({ backgroundColor: 'transparent' }, 1000);
            });
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        $('.kpi-leaderboard').each(function() {
            new KPILeaderboard($(this));
        });
    });

})(jQuery);
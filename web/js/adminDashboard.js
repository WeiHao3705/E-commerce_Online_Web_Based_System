// Global variable for sales line chart
var salesLineChart = null;

$(document).ready(function() {
    // Initialize sales line chart
    initializeSalesLineChart();
    
    // Auto-hide success/error popups
    $('.success-popup').each(function() {
        var $popup = $(this);
        setTimeout(function() {
            $popup.css('animation', 'slideOut 0.3s ease');
            setTimeout(function() {
                $popup.remove();
            }, 300);
        }, 3000);
    });

    $('.error-popup').each(function() {
        var $popup = $(this);
        setTimeout(function() {
            $popup.css('animation', 'slideOut 0.3s ease');
            setTimeout(function() {
                $popup.remove();
            }, 300);
        }, 5000);
    });

    // Navigation click handlers
    $('.admin-nav-item[data-view]').on('click', function(e) {
        e.preventDefault();
        var view = $(this).data('view');
        var url = $(this).data('url');

        // Update active state
        $('.admin-nav-item').removeClass('active');
        $(this).addClass('active');

        if (view === 'dashboard') {
            showDashboard();
        } else if (url) {
            showContentView(view, url);
        }
    });

    // Admin profile link click handler
    $('#adminProfileLink').on('click', function(e) {
        e.preventDefault();
        var view = $(this).data('view');
        var url = $(this).data('url');
        
        if (url) {
            showContentView(view, url);
        }
    });

    // Clickable stat cards
    $('.clickable-stat').on('click', function() {
        var view = $(this).data('view');
        var url = $(this).data('url');
        if (url) {
            // Update navigation active state
            $('.admin-nav-item').removeClass('active');
            $('.admin-nav-item[data-view="' + view + '"]').addClass('active');
            showContentView(view, url);
        }
    });

    // Quick action buttons with data-view and data-url
    $('.admin-quick-action-btn[data-view]').on('click', function(e) {
        e.preventDefault();
        var view = $(this).data('view');
        var url = $(this).data('url');
        if (url) {
            // Update navigation active state
            $('.admin-nav-item').removeClass('active');
            $('.admin-nav-item[data-view="' + view + '"]').addClass('active');
            showContentView(view, url);
        }
    });

    // View All Orders link handler
    $('.admin-orders-link[data-view]').on('click', function(e) {
        e.preventDefault();
        var view = $(this).data('view');
        var url = $(this).data('url');
        if (url) {
            // Update navigation active state
            $('.admin-nav-item').removeClass('active');
            $('.admin-nav-item[data-view="' + view + '"]').addClass('active');
            showContentView(view, url);
        }
    });

    function showDashboard() {
        $('#dashboard-view').show();
        $('#content-view').hide();
        // Update navigation
        $('.admin-nav-item').removeClass('active');
        $('.admin-nav-item[data-view="dashboard"]').addClass('active');
        
        // Update URL hash
        if (window.location.hash !== '#dashboard') {
            window.location.hash = '#dashboard';
        }

        // Fetch and update statistics
        var statsUrl = $('#dashboard-view').data('stats-url') || 'views/admin/getDashboardStats.php';
        $.ajax({
            url: statsUrl,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.stats) {
                    var stats = response.stats;
                    $('#stat-total-sales-value').text(stats.total_sales.value);
                    $('#stat-total-sales-change').text(stats.total_sales.change);
                    $('#stat-active-members-value').text(stats.active_members.value);
                    $('#stat-active-members-change').text(stats.active_members.change);
                    $('#stat-total-products-value').text(stats.total_products.value);
                    $('#stat-total-products-change').text(stats.total_products.change);
                    $('#stat-active-vouchers-value').text(stats.active_vouchers.value);
                    $('#stat-active-vouchers-change').text(stats.active_vouchers.change);
                    
                    // Update weekly sales chart if data is available
                    if (response.weekly_sales) {
                        $('.admin-chart-value').text('RM ' + parseFloat(response.weekly_sales.current).toLocaleString('en-MY', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('.admin-chart-change').text(response.weekly_sales.change);
                        
                        // Update or initialize line chart
                        if (response.weekly_sales.data && response.weekly_sales.data.length === 4) {
                            if (salesLineChart) {
                                salesLineChart.data.datasets[0].data = response.weekly_sales.data;
                                salesLineChart.update();
                            } else {
                                // Initialize chart if it doesn't exist yet
                                weeklySalesData = response.weekly_sales.data;
                                initializeSalesLineChart();
                            }
                        }
                    }
                    
                    // Update recent orders if data is available
                    if (response.recent_orders && response.recent_orders.length > 0) {
                        var ordersHtml = '';
                        response.recent_orders.forEach(function(order) {
                            ordersHtml += '<div class="admin-order-item">';
                            ordersHtml += '<div class="admin-order-info">';
                            ordersHtml += '<p class="admin-order-name">' + order.name + '</p>';
                            ordersHtml += '<p class="admin-order-id">#' + order.id + '</p>';
                            ordersHtml += '</div>';
                            ordersHtml += '<p class="admin-order-price">RM ' + order.price + '</p>';
                            ordersHtml += '</div>';
                        });
                        $('.admin-orders-list').html(ordersHtml);
                    }
                } else {
                    console.error('Invalid response format:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching stats:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    function showContentView(view, url) {
        var title = 'Content';

        if (view === 'members') {
            title = 'Members Management';
        } else if (view === 'admins') {
            title = 'Admins Management';
        } else if (view === 'products') {
            title = 'Products Management';
        } else if (view === 'vouchers') {
            // Check if URL contains VoucherRegisterForm to show "Create Voucher"
            if (url.indexOf('VoucherRegisterForm') !== -1) {
                title = 'Create Voucher';
            } else {
                title = 'Vouchers Management';
            }
        } else if (view === 'orders') {
            title = 'Orders Management';
        } else if (view === 'reviews') {
            title = 'Reviews Management';
        } else if (view === 'admin_profile') {
            title = 'Admin Profile';
        }

        $('#content-title').text(title);
        $('#content-iframe').attr('src', url);
        $('#dashboard-view').hide();
        $('#content-view').show();
        
        // Update URL hash to persist view state
        var hash = '#' + view;
        // Include URL in hash for more specific state
        if (url) {
            hash += '|' + encodeURIComponent(url);
        }
        if (window.location.hash !== hash) {
            window.location.hash = hash;
        }
    }

    // Handle iframe load events
    $('#content-iframe').on('load', function() {
        // Optional: Add loading indicator logic here
        console.log('Content loaded');
    });

    // Listen for messages from iframe (for back button functionality)
    $(window).on('message', function(event) {
        if (event.originalEvent.data && event.originalEvent.data.action === 'showDashboard') {
            showDashboard();
        }
    });

    // Restore view state from URL hash on page load
    function restoreViewFromHash() {
        var hash = window.location.hash;
        if (!hash || hash === '#dashboard' || hash === '#') {
            showDashboard();
            return;
        }

        // Parse hash: #view|url
        var parts = hash.substring(1).split('|');
        var view = parts[0];
        var url = parts.length > 1 ? decodeURIComponent(parts[1]) : null;

        if (view === 'members' || view === 'admins' || view === 'products' || view === 'vouchers' || view === 'orders' || view === 'reviews' || view === 'admin_profile') {
            // Get URL from navigation item if not in hash
            if (!url) {
                var navItem = $('.admin-nav-item[data-view="' + view + '"]');
                url = navItem.data('url');
            }

            if (url) {
                // Update navigation active state
                $('.admin-nav-item').removeClass('active');
                $('.admin-nav-item[data-view="' + view + '"]').addClass('active');
                showContentView(view, url);
            } else {
                showDashboard();
            }
        } else {
            showDashboard();
        }
    }

    // Restore view on page load
    restoreViewFromHash();

    // Handle hash changes (browser back/forward)
    $(window).on('hashchange', function() {
        restoreViewFromHash();
    });

    // Prevent back navigation to login page or home page
    (function() {
        var dashboardUrl = window.location.href;
        
        // Replace the previous history entry if it was login/home page
        // This prevents the back button from going to those pages
        if (window.history.length > 1) {
            window.history.replaceState(
                { page: 'admin-dashboard', preventBack: true },
                document.title,
                dashboardUrl
            );
        }
        
        // Push current state to ensure we have a valid entry
        window.history.pushState(
            { page: 'admin-dashboard', preventBack: true },
            document.title,
            dashboardUrl
        );
    })();

    // Intercept browser back/forward navigation (mouse side button, browser back button) using jQuery
    $(window).on('popstate', function(event) {
        var currentUrl = window.location.href;
        var viewsBasePath = $('#dashboard-view').data('views-base-path') || 'views/admin/';
        
        // Check if trying to navigate to login, account, or home page (but not admin pages)
        var isLoginPage = currentUrl.indexOf('login.php') !== -1;
        var isAccountPage = currentUrl.indexOf('account.php') !== -1;
        var isHomePage = currentUrl.indexOf('index.php') !== -1 && currentUrl.indexOf('/admin/') === -1;
        
        // If trying to go back to any of these pages, prevent it
        if (isLoginPage || isAccountPage || isHomePage) {
            // Immediately redirect back to dashboard without adding to history
            window.location.replace(viewsBasePath + 'AdminDashboard.php');
            return;
        }
        
        // If we have a valid dashboard state, restore the view
        if (event.originalEvent && event.originalEvent.state && event.originalEvent.state.page === 'admin-dashboard') {
            restoreViewFromHash();
        } else if (event.state && event.state.page === 'admin-dashboard') {
            restoreViewFromHash();
        }
    });

    // Sidebar toggle functionality
    var sidebar = $('#admin-sidebar');
    var sidebarToggle = $('#sidebar-toggle');
    var isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

    // Initialize sidebar state
    if (isCollapsed) {
        sidebar.addClass('collapsed');
        sidebarToggle.find('.material-symbols-outlined').text('menu_open');
    }

    // Toggle sidebar
    sidebarToggle.on('click', function() {
        sidebar.toggleClass('collapsed');
        var isNowCollapsed = sidebar.hasClass('collapsed');
        localStorage.setItem('sidebarCollapsed', isNowCollapsed);

        // Update icon
        if (isNowCollapsed) {
            sidebarToggle.find('.material-symbols-outlined').text('menu_open');
        } else {
            sidebarToggle.find('.material-symbols-outlined').text('menu');
        }
    });

});

// Initialize sales line chart
function initializeSalesLineChart() {
    var ctx = document.getElementById('salesLineChart');
    if (!ctx) return;
    
    var chartData = typeof weeklySalesData !== 'undefined' ? weeklySalesData : [0, 0, 0, 0];
    var chartLabels = typeof weeklyLabels !== 'undefined' ? weeklyLabels : ['3 weeks ago', '2 weeks ago', '1 week ago', 'This Week'];
    
    salesLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Sales (RM)',
                data: chartData,
                borderColor: '#FF523B',
                backgroundColor: 'rgba(255, 82, 59, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#FF523B',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointHoverBackgroundColor: '#FF523B',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return 'RM ' + parseFloat(context.parsed.y).toLocaleString('en-MY', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + parseFloat(value).toLocaleString('en-MY', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                        },
                        font: {
                            size: 11
                        },
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        },
                        color: '#6b7280'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}


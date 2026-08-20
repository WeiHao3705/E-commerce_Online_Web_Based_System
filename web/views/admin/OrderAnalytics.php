<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../security/login.php');
    exit;
}

require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
        SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN order_status = 'canceled' THEN 1 ELSE 0 END) as canceled_orders,
        SUM(CASE WHEN order_status = 'refunded' THEN 1 ELSE 0 END) as refunded_orders,
        SUM(CASE WHEN order_status NOT IN ('refunded', 'canceled') THEN total_amount ELSE 0 END) as total_revenue
    FROM orders
";
$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get payment method distribution
$paymentQuery = "
    SELECT 
        COALESCE(p.payment_method, 'Pending') as payment_method,
        COUNT(o.order_id) as count,
        ROUND((COUNT(o.order_id) * 100.0 / (SELECT COUNT(*) FROM orders)), 1) as percentage
    FROM orders o
    LEFT JOIN payment p ON o.order_id = p.order_id
    GROUP BY p.payment_method
";
$paymentStmt = $conn->query($paymentQuery);
$paymentMethods = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly revenue (last 6 months)
$revenueQuery = "
    SELECT 
        DATE_FORMAT(create_at, '%b') as month,
        SUM(CASE WHEN order_status NOT IN ('refunded', 'canceled') THEN total_amount ELSE 0 END) as revenue
    FROM orders
    WHERE create_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(create_at), MONTH(create_at)
    ORDER BY YEAR(create_at), MONTH(create_at)
";
$revenueStmt = $conn->query($revenueQuery);
$monthlyRevenue = $revenueStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate base path
$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$relativePath = str_replace($docRoot, '', str_replace('\\', '/', $webRootDir));
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';

$pageTitle = "Order Analytics - Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NGEAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>OrderAnalytics.css">
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <header class="header-actions">
            <h1 style="font-size: 2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-chart-line" style="color: #FF523B;"></i> Order Analytics
            </h1>
            <a href="AdminOrder.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </header>

        <!-- Statistics Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_orders']) ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['pending_orders']) ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['delivered_orders']) ?></h3>
                    <p>Delivered</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-info">
                    <h3>RM <?= number_format($stats['total_revenue'], 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </section>

        <!-- Key Insights -->
        <section class="insights-grid">
            <div class="insight-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <h3><i class="fas fa-percentage"></i> Conversion Rate</h3>
                <div class="value">
                    <?php 
                    $conversionRate = $stats['total_orders'] > 0 
                        ? round(($stats['delivered_orders'] / $stats['total_orders']) * 100, 1) 
                        : 0;
                    echo $conversionRate . '%';
                    ?>
                </div>
                <div class="description">Orders successfully delivered</div>
            </div>
            <div class="insight-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <h3><i class="fas fa-chart-line"></i> Average Order Value</h3>
                <div class="value">
                    RM <?php 
                    $avgOrder = $stats['total_orders'] > 0 
                        ? number_format($stats['total_revenue'] / $stats['total_orders'], 2) 
                        : 0;
                    echo $avgOrder;
                    ?>
                </div>
                <div class="description">Per order revenue</div>
            </div>
            <div class="insight-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <h3><i class="fas fa-exclamation-triangle"></i> Cancellation Rate</h3>
                <div class="value">
                    <?php 
                    $cancelRate = $stats['total_orders'] > 0 
                        ? round(($stats['canceled_orders'] / $stats['total_orders']) * 100, 1) 
                        : 0;
                    echo $cancelRate . '%';
                    ?>
                </div>
                <div class="description">Orders canceled or refunded</div>
            </div>
        </section>

        <!-- Charts Section -->
        <section class="charts-grid">
            <!-- Pie Chart - Order Status Distribution -->
            <div class="chart-card">
                <h2><i class="fas fa-chart-pie"></i> Order Status Distribution</h2>
                <div class="chart-container">
                    <canvas id="statusPieChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart - Orders by Status -->
            <div class="chart-card">
                <h2><i class="fas fa-chart-bar"></i> Orders by Status</h2>
                <div class="chart-container">
                    <canvas id="statusBarChart"></canvas>
                </div>
            </div>

            <!-- Line Chart - Revenue Overview -->
            <div class="chart-card">
                <h2><i class="fas fa-chart-line"></i> Revenue Trend</h2>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart - Payment Methods -->
            <div class="chart-card">
                <h2><i class="fas fa-credit-card"></i> Payment Methods Distribution</h2>
                <div class="chart-container">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>
        </section>
    </div>

    <script>
    // Prepare data from PHP
    const orderStats = {
        pending: <?= $stats['pending_orders'] ?>,
        paid: <?= $stats['paid_orders'] ?>,
        shipped: <?= $stats['shipped_orders'] ?>,
        delivered: <?= $stats['delivered_orders'] ?>,
        canceled: <?= $stats['canceled_orders'] ?>,
        refunded: <?= $stats['refunded_orders'] ?>,
        totalRevenue: <?= $stats['total_revenue'] ?>
    };

    const paymentMethods = <?= json_encode($paymentMethods) ?>;
    const monthlyRevenue = <?= json_encode($monthlyRevenue) ?>;

    // Chart colors
    const chartColors = {
        pending: '#f59e0b',
        paid: '#3b82f6',
        shipped: '#8b5cf6',
        delivered: '#10b981',
        canceled: '#ef4444',
        refunded: '#6b7280'
    };

    // 1. Pie Chart - Order Status Distribution
    const statusPieCtx = document.getElementById('statusPieChart').getContext('2d');
    new Chart(statusPieCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Paid', 'Shipped', 'Delivered', 'Canceled', 'Refunded'],
            datasets: [{
                data: [
                    orderStats.pending,
                    orderStats.paid,
                    orderStats.shipped,
                    orderStats.delivered,
                    orderStats.canceled,
                    orderStats.refunded
                ],
                backgroundColor: [
                    chartColors.pending,
                    chartColors.paid,
                    chartColors.shipped,
                    chartColors.delivered,
                    chartColors.canceled,
                    chartColors.refunded
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 12, family: 'Poppins' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // 2. Bar Chart - Orders by Status
    const statusBarCtx = document.getElementById('statusBarChart').getContext('2d');
    new Chart(statusBarCtx, {
        type: 'bar',
        data: {
            labels: ['Pending', 'Paid', 'Shipped', 'Delivered', 'Canceled', 'Refunded'],
            datasets: [{
                label: 'Number of Orders',
                data: [
                    orderStats.pending,
                    orderStats.paid,
                    orderStats.shipped,
                    orderStats.delivered,
                    orderStats.canceled,
                    orderStats.refunded
                ],
                backgroundColor: [
                    chartColors.pending,
                    chartColors.paid,
                    chartColors.shipped,
                    chartColors.delivered,
                    chartColors.canceled,
                    chartColors.refunded
                ],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Orders: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { family: 'Poppins' }
                    },
                    grid: { color: '#f3f4f6' }
                },
                x: {
                    ticks: { font: { family: 'Poppins' } },
                    grid: { display: false }
                }
            }
        }
    });

    // 3. Line Chart - Revenue Overview
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    
    // Prepare revenue data
    let revenueLabels = [];
    let revenueData = [];
    
    if (monthlyRevenue.length > 0) {
        monthlyRevenue.forEach(item => {
            revenueLabels.push(item.month);
            revenueData.push(parseFloat(item.revenue));
        });
    } else {
        // Fallback to simulated data if no orders
        revenueLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        revenueData = [0, 0, 0, 0, 0, 0];
    }

    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Revenue (RM)',
                data: revenueData,
                borderColor: '#FF523B',
                backgroundColor: 'rgba(255, 82, 59, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#FF523B',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'RM ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toFixed(0);
                        },
                        font: { family: 'Poppins' }
                    },
                    grid: { color: '#f3f4f6' }
                },
                x: {
                    ticks: { font: { family: 'Poppins' } },
                    grid: { display: false }
                }
            }
        }
    });

    // 4. Doughnut Chart - Payment Methods
    const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
    
    // Prepare payment method data
    let paymentLabels = [];
    let paymentData = [];
    let paymentColors = [];
    
    const colorMap = {
        'Card': '#3b82f6',           
        'Online Banking': '#10b981',  
        'E-Wallet': '#8b5cf6'        
    };

    if (paymentMethods.length > 0) {
        // Aggregate payment methods by type
        const aggregated = {
            'Card': 0,
            'Online Banking': 0,
            'E-Wallet': 0
        };
        
        paymentMethods.forEach(item => {
            const method = item.payment_method.toLowerCase();
            const percentage = parseFloat(item.percentage);
            
            // Categorize payment methods
            if (method === 'credit_card') {
                aggregated['Card'] += percentage;
            } else if (method === 'online-banking') {
                aggregated['Online Banking'] += percentage;
            } else if (method === 'e-wallet') {
                aggregated['E-Wallet'] += percentage;
            }
        });
        
        // Convert aggregated data to arrays, excluding zero values
        Object.keys(aggregated).forEach(key => {
            if (aggregated[key] > 0) {
                paymentLabels.push(key);
                paymentData.push(aggregated[key]);
                paymentColors.push(colorMap[key]);
            }
        });
    } else {
        // Fallback if no data
        paymentLabels = ['No Data'];
        paymentData = [100];
        paymentColors = ['#6b7280'];
    }

    new Chart(paymentMethodCtx, {
        type: 'doughnut',
        data: {
            labels: paymentLabels,
            datasets: [{
                data: paymentData,
                backgroundColor: paymentColors,
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { 
                            size: 12, 
                            family: 'Poppins',
                            weight: '500'
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 12,
                    titleFont: { size: 14, family: 'Poppins', weight: '600' },
                    bodyFont: { size: 13, family: 'Poppins' },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return label + ': ' + value.toFixed(1) + '%';
                        }
                    }
                }
            },
            cutout: '65%',
            animation: {
                animateRotate: true,
                animateScale: true
            }
        }
    });
    </script>
</body>
</html>

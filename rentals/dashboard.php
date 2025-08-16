<?php
// rentals/dashboard.php - SPF Shopping Complex Rentals Dashboard
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$pageTitle = 'SPF Rentals Dashboard';
$currentPage = 'rentals';

// Example: Fetch rental summary data (replace with real DB queries)
// $summary = ...;
// $rentals = ...;

include '../includes/header.php';
?>
<div class="main-container">
    <div class="page-header">
        <h1>SPF Rentals Dashboard</h1>
        <p>Overview of rental payment status for all shops in the complex.</p>
    </div>
    <div class="card" style="margin-bottom:2rem;">
        <h2>Rental Status Summary</h2>
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-number">--</div>
                <div class="stat-label">Total Shops</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number">--</div>
                <div class="stat-label">Fully Paid (All Months)</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-number">--</div>
                <div class="stat-label">Partially Paid</div>
            </div>
            <div class="stat-card red">
                <div class="stat-number">--</div>
                <div class="stat-label">Unpaid (Any Month)</div>
            </div>
        </div>
    </div>
    <div class="card">
        <h2>Rental Payment Table</h2>
        <!-- TODO: Display a table of all shops and their monthly payment status -->
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Shop No</th>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Jan</th>
                        <th>Feb</th>
                        <th>Mar</th>
                        <th>Apr</th>
                        <th>May</th>
                        <th>Jun</th>
                        <th>Jul</th>
                        <th>Aug</th>
                        <th>Sept</th>
                        <th>Oct</th>
                        <th>Nov</th>
                        <th>Dec</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Example row, replace with dynamic data -->
                    <tr>
                        <td>1</td>
                        <td>MAT P GENERAL DEALERS</td>
                        <td>--</td>
                        <td>PAID</td>
                        <td>PAID</td>
                        <td>PAID</td>
                        <td>PAID</td>
                        <td>PAID</td>
                        <td>PAID</td>
                        <td>PAID</td>
                        <td>PAID</td>
                        <td>PAID</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

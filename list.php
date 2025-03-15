<?php
require_once 'config/db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice List</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Back Button -->
        <div class="back-button">
            <a href="invoice.php" class="btn btn-primary">← Back to Main</a>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="invoice.php">Main</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="list.php">List</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Reports</a>
            </li>
        </ul>

        <!-- Search and Filter Section -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search Invoice No, Project Code, or Project Name">
                            <button class="btn btn-primary" id="searchBtn">Search</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="statusFilter" class="form-select">
                            <option value="All">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                            <option value="Deposited">Deposited</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice List Table -->
        <div class="card mt-3">
            <div class="card-body">
                <div id="invoiceTableContainer">
                    <!-- Table will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load invoice data on page load
            loadInvoiceData();
            
            // Search button click event
            document.getElementById('searchBtn').addEventListener('click', function() {
                loadInvoiceData();
            });
            
            // Status filter change event
            document.getElementById('statusFilter').addEventListener('change', function() {
                loadInvoiceData();
            });
            
            // Search input enter key event
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    loadInvoiceData();
                }
            });
            
            // Function to load invoice data
            function loadInvoiceData() {
                const searchValue = document.getElementById('searchInput').value;
                const statusValue = document.getElementById('statusFilter').value;
                const tableContainer = document.getElementById('invoiceTableContainer');
                
                // Show loading indicator
                tableContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                
                // Fetch data from server
                fetch(`fetch_billing_data.php?search=${encodeURIComponent(searchValue)}&status=${encodeURIComponent(statusValue)}`)
                    .then(response => response.text())
                    .then(data => {
                        tableContainer.innerHTML = data;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        tableContainer.innerHTML = '<div class="alert alert-danger">Error loading data. Please try again.</div>';
                    });
            }
        });
    </script>
</body>
</html> 
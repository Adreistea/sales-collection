<?php require_once 'config/db_connect.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Back Button -->
        <div class="back-button">
            <a href="main.php" class="btn btn-primary">← Back to Main</a>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="#">Main</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="list.php">List</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">Reports</a>
            </li>
        </ul>

        <!-- Invoice Form -->
        <div class="card">
            <div class="card-body">
                <h3 class="card-title mb-4">Invoice</h3>
                <form id="invoiceForm">
                    <!-- Add these hidden fields for data that might not be visible but needed for submission -->
                    <input type="hidden" name="rfb_id" value="">
                    <input type="hidden" name="project_name" value="">
                    <input type="hidden" name="customer_id" value="">
                    <input type="hidden" name="manager_id" value="">
                    <input type="hidden" name="project_title" value="">

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Invoice No:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="invoiceNo" placeholder="Enter Invoice No">
                                            <button class="btn btn-primary" type="button" id="searchBtn">🔍</button>
                                            <button class="btn btn-secondary" type="button" id="newInvoiceBtn">New</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date:</label>
                                        <input type="date" class="form-control" name="date" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status:</label>
                                        <input type="text" class="form-control" name="status" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Project ID:</label>
                                <input type="text" class="form-control" name="project_id" readonly>
                            </div>

                            <div class="form-group">
                                <label>Customer:</label>
                                <input type="text" class="form-control" name="customer_display" readonly>
                                <input type="hidden" name="customer_id">
                                <small class="form-text text-muted" id="customerNameDisplay"></small>
                            </div>

                            <div class="form-group">
                                <label>Acct. Manager:</label>
                                <input type="text" class="form-control" value="Acct. Manager">
                            </div>

                            <div class="form-group">
                                <label>Product Name:</label>
                                <input type="text" class="form-control" name="product_name" placeholder="Enter Product Name">
                            </div>

                            <div class="form-group">
                                <label>Remarks:</label>
                                <textarea class="form-control" name="remarks" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Invoice Type:</label>
                                <select class="form-control">
                                    <option>Standard Invoice</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Internal Terms:</label>
                                <select class="form-control" name="internal_terms">
                                    <option>1 day</option>
                                    <option>2 days</option>
                                    <option>3 days</option>
                                    <option>4 days</option>
                                    <option>5 days</option>
                                    <option>6 days</option>
                                    <option>7 days</option>
                                    <option>8 days</option>
                                    <option>9 days</option>
                                    <option>10 days</option>
                                    <option>11 days</option>
                                    <option>12 days</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Customer Terms:</label>
                                <select class="form-control" name="customer_terms">
                                    <option>1 day</option>
                                    <option>2 days</option>
                                    <option>3 days</option>
                                    <option>4 days</option>
                                    <option>5 days</option>
                                    <option>6 days</option>
                                    <option>7 days</option>
                                    <option>8 days</option>
                                    <option>9 days</option>
                                    <option>10 days</option>
                                    <option>11 days</option>
                                    <option>12 days</option>

                                </select>
                            </div>

                            <div class="form-group">
                                <label>Payment Type:</label>
                                <select class="form-control" name="payment_type">
                                    <option>Cash</option>
                                    <option>Bank Transfer</option>
                                    <option>Check</option>
                                </select>
                            </div>

                            <!-- Add these fields to the right column section -->
                            <div class="form-group payment-details" id="bankTransferDetails" style="display:none;">
                                <label>Bank:</label>
                                <select class="form-control" name="bank">
                                    <option value="BDO">BDO</option>
                                    <option value="BPI">BPI</option>
                                    <option value="Metrobank">Metrobank</option>
                                </select>
                            </div>

                            <div class="form-group payment-details" id="bankAccNoDetails" style="display:none;">
                                <label>Bank Account No:</label>
                                <input type="text" class="form-control" name="bank_acc_no" placeholder="Enter Bank Account No">
                            </div>

                            <div class="form-group payment-details" id="checkNoDetails" style="display:none;">
                                <label>Check No:</label>
                                <input type="text" class="form-control" name="check_no" placeholder="Enter Check No">
                            </div>
                        </div>
                    </div>

                    <!-- Services Table -->
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Service ID</th>
                                    <th>Description</th>
                                    <th>Accepted</th>
                                    <th>Gross</th>
                                    <th>Billed</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="text" class="form-control" value="SER-001" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" value="Data Gathering (Process Flow)">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control accepted-value" value="0.00" readonly>
                                    </td>
                                    <td class="gross-display">0.00</td>
                                    <td class="billed-display">0.00</td>
                                    <td class="amount-display">0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Section -->
                    <div class="row mt-4">
                        <div class="col-md-8 offset-md-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Gross:*</label>
                                        <input type="number" step="0.01" class="form-control calc-input" id="grossInput" name="gross" value="0.00">
                                    </div>
                                    <div class="form-group">
                                        <label>Returns:*</label>
                                        <input type="number" step="0.01" class="form-control calc-input" id="returnsInput" name="returns" value="0.00">
                                    </div>
                                    <div class="form-group">
                                        <label>Payment:*</label>
                                        <input type="number" step="0.01" class="form-control calc-input" id="paymentInput" name="payments" value="0.00">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Debit:*</label>
                                        <input type="number" step="0.01" class="form-control calc-input" id="debitInput" name="debit" value="0.00">
                                    </div>
                                    <div class="form-group">
                                        <label>Credit:*</label>
                                        <input type="number" step="0.01" class="form-control calc-input" id="creditInput" name="credit" value="0.00">
                                    </div>
                                    <div class="form-group">
                                        <label>Balance:*</label>
                                        <input type="number" step="0.01" class="form-control" id="balanceInput" name="balance" value="0.00">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save and Reset Buttons -->
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary me-2" id="resetButton">Reset</button>
                        <button type="submit" class="btn btn-primary">Save Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add this before closing body tag -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all input elements
            const grossInput = document.getElementById('grossInput');
            const returnsInput = document.getElementById('returnsInput');
            const paymentInput = document.getElementById('paymentInput');
            const debitInput = document.getElementById('debitInput');
            const creditInput = document.getElementById('creditInput');
            const balanceInput = document.getElementById('balanceInput');
            const acceptedInput = document.querySelector('.accepted-value');
            const resetButton = document.getElementById('resetButton');

            // Get display elements
            const grossDisplay = document.querySelector('.gross-display');
            const billedDisplay = document.querySelector('.billed-display');
            const amountDisplay = document.querySelector('.amount-display');

            // Function to update calculations
            function updateCalculations() {
                // Get values
                const gross = parseFloat(grossInput.value) || 0;
                const returns = parseFloat(returnsInput.value) || 0;
                const payment = parseFloat(paymentInput.value) || 0;
                const debit = parseFloat(debitInput.value) || 0;
                const credit = parseFloat(creditInput.value) || 0;

                // Calculate values
                const billed = gross - returns;
                const amount = billed - payment;
                const balance = billed - credit;
                const accepted = gross - returns - payment;

                // Update displays
                grossDisplay.textContent = gross.toFixed(2);
                billedDisplay.textContent = billed.toFixed(2);
                amountDisplay.textContent = amount.toFixed(2);
                balanceInput.value = balance.toFixed(2);
                acceptedInput.value = accepted.toFixed(2);
            }

            // Reset function
            function resetForm() {
                // Reset all inputs to 0.00
                const inputs = [grossInput, returnsInput, paymentInput, debitInput, creditInput];
                inputs.forEach(input => {
                    input.value = "0.00";
                });

                // Reset displays
                grossDisplay.textContent = "0.00";
                billedDisplay.textContent = "0.00";
                amountDisplay.textContent = "0.00";
                balanceInput.value = "0.00";
                acceptedInput.value = "0.00";
            }

            // Add event listeners to all calculation inputs
            const calcInputs = document.querySelectorAll('.calc-input');
            calcInputs.forEach(input => {
                input.addEventListener('input', updateCalculations);
            });

            // Add reset button event listener
            resetButton.addEventListener('click', resetForm);

            // Format number inputs to always show 2 decimal places
            calcInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value) {
                        this.value = parseFloat(this.value).toFixed(2);
                    }
                });
            });

            // Payment type change handler
            const paymentTypeSelect = document.querySelector('select[name="payment_type"]');
            paymentTypeSelect.addEventListener('change', function() {
                const selectedPaymentType = this.value;
                
                // Hide all payment details first
                document.querySelectorAll('.payment-details').forEach(el => {
                    el.style.display = 'none';
                });
                
                // Show relevant fields based on payment type
                if (selectedPaymentType === 'Bank Transfer') {
                    document.getElementById('bankTransferDetails').style.display = 'block';
                    document.getElementById('bankAccNoDetails').style.display = 'block';
                } else if (selectedPaymentType === 'Check') {
                    document.getElementById('bankTransferDetails').style.display = 'block';
                    document.getElementById('checkNoDetails').style.display = 'block';
                }
            });
            
            // Form submission handler
            const invoiceForm = document.getElementById('invoiceForm');
            invoiceForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Create FormData object
                const formData = new FormData(this);
                
                // Add invoice number
                formData.append('invoiceNo', document.getElementById('invoiceNo').value);
                
                // Make sure all financial values are included with correct names
                formData.set('amount', document.querySelector('.amount-display').textContent);
                formData.set('billed', document.querySelector('.billed-display').textContent);
                formData.set('gross', document.getElementById('grossInput').value);
                formData.set('returns', document.getElementById('returnsInput').value);
                formData.set('payments', document.getElementById('paymentInput').value); // Note: 'payments' not 'payment'
                formData.set('debit', document.getElementById('debitInput').value);
                formData.set('credit', document.getElementById('creditInput').value);
                formData.set('balance', document.getElementById('balanceInput').value);
                
                // Debug output
                console.log('Financial values being submitted:');
                console.log('Gross:', formData.get('gross'));
                console.log('Returns:', formData.get('returns'));
                console.log('Payments:', formData.get('payments'));
                console.log('Debit:', formData.get('debit'));
                console.log('Credit:', formData.get('credit'));
                console.log('Balance:', formData.get('balance'));
                
                // Send data to server
                fetch('save_invoice.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        
                        // Clear the form after successful save
                        clearForm();
                        
                        // Also clear the invoice number field
                        document.getElementById('invoiceNo').value = '';
                        
                        // Make all fields readonly if status is not Pending
                        if (data.status !== 'Pending') {
                            const formElements = document.querySelectorAll('#invoiceForm input:not(#invoiceNo), #invoiceForm select, #invoiceForm textarea');
                            formElements.forEach(el => {
                                el.setAttribute('readonly', true);
                                if (el.tagName === 'SELECT') {
                                    el.disabled = true;
                                }
                            });
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving invoice: ' + error.message);
                });
            });

            // Add event listener for the new invoice button
            document.getElementById('newInvoiceBtn').addEventListener('click', function() {
                // Clear the form
                clearForm();
                
                // Also clear the invoice number field
                document.getElementById('invoiceNo').value = '';
                
                // Set focus to the invoice number field
                document.getElementById('invoiceNo').focus();
            });
        });

        document.getElementById('searchBtn').addEventListener('click', function() {
            const invoiceNo = document.getElementById('invoiceNo').value;
            
            if (!invoiceNo) {
                alert('Please enter an invoice number to search');
                return;
            }
            
            fetch('search_invoice.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'invoice_no=' + encodeURIComponent(invoiceNo)
            })
            .then(response => response.json())
            .then(data => {
                if(data) {
                    console.log('Search result data:', data); // Debug log
                    
                    // Check if the invoice is pending
                    const isPending = data.status === 'Pending';
                    
                    // If the invoice is pending, clear the form first to reset all values
                    if (isPending) {
                        clearForm();
                    }
                    
                    // Update form fields with data - check if elements exist first
                    const dateInput = document.querySelector('input[name="date"]');
                    if (dateInput && data.date) {
                        dateInput.value = data.date;
                    } else if (dateInput && data.created_at) {
                        dateInput.value = data.created_at.split(' ')[0];
                    }
                    
                    const statusInput = document.querySelector('input[name="status"]');
                    if (statusInput) {
                        statusInput.value = data.status || '';
                    }
                    
                    const projectIdInput = document.querySelector('input[name="project_id"]');
                    if (projectIdInput) {
                        projectIdInput.value = data.project_code || '';
                    }
                    
                    // Set project_title
                    const projectTitleInput = document.querySelector('input[name="project_title"]');
                    if (projectTitleInput) {
                        projectTitleInput.value = data.project_title || '';
                    }
                    
                    // Display customer ID in the display field
                    const customerDisplayInput = document.querySelector('input[name="customer_display"]');
                    if (customerDisplayInput) {
                        customerDisplayInput.value = data.customer_id || '';
                    }
                    
                    // Set the customer_id in the hidden field
                    const customerIdInput = document.querySelector('input[name="customer_id"]');
                    if (customerIdInput) {
                        customerIdInput.value = data.customer_id || '';
                    }
                    
                    // Display customer name below the ID field
                    const customerNameDisplay = document.getElementById('customerNameDisplay');
                    if (customerNameDisplay) {
                        customerNameDisplay.textContent = data.customer_name || '';
                    }
                    
                    const rfbIdInput = document.querySelector('input[name="rfb_id"]');
                    if (rfbIdInput) {
                        rfbIdInput.value = data.rfb_id || '';
                    }
                    
                    // Set manager, product name, and remarks
                    const managerInput = document.querySelector('input[name="manager_id"]');
                    if (managerInput) {
                        managerInput.value = data.manager_id || '';
                    }
                    
                    const productNameInput = document.querySelector('input[name="product_name"]');
                    if (productNameInput) {
                        productNameInput.value = data.product_name || '';
                    }
                    
                    const remarksInput = document.querySelector('textarea[name="remarks"]');
                    if (remarksInput) {
                        remarksInput.value = data.remarks || '';
                    }
                    
                    // Set select fields
                    const internalTermsSelect = document.querySelector('select[name="internal_terms"]');
                    if (internalTermsSelect && data.internal_terms) {
                        setSelectValue(internalTermsSelect, data.internal_terms);
                    }
                    
                    const customerTermsSelect = document.querySelector('select[name="customer_terms"]');
                    if (customerTermsSelect && data.customer_terms) {
                        setSelectValue(customerTermsSelect, data.customer_terms);
                    }
                    
                    const paymentTypeSelect = document.querySelector('select[name="payment_type"]');
                    if (paymentTypeSelect && data.payment_type) {
                        setSelectValue(paymentTypeSelect, data.payment_type);
                        
                        // Show relevant payment details based on payment type
                        if (data.payment_type === 'Bank Transfer') {
                            document.getElementById('bankTransferDetails').style.display = 'block';
                            document.getElementById('bankAccNoDetails').style.display = 'block';
                            
                            // Set bank and account number if available
                            const bankSelect = document.querySelector('select[name="bank"]');
                            if (bankSelect && data.bank) {
                                setSelectValue(bankSelect, data.bank);
                            }
                            
                            const bankAccNoInput = document.querySelector('input[name="bank_acc_no"]');
                            if (bankAccNoInput) {
                                bankAccNoInput.value = data.bank_acc_no || '';
                            }
                        } else if (data.payment_type === 'Check') {
                            document.getElementById('bankTransferDetails').style.display = 'block';
                            document.getElementById('checkNoDetails').style.display = 'block';
                            
                            // Set bank and check number if available
                            const bankSelect = document.querySelector('select[name="bank"]');
                            if (bankSelect && data.bank) {
                                setSelectValue(bankSelect, data.bank);
                            }
                            
                            const checkNoInput = document.querySelector('input[name="check_no"]');
                            if (checkNoInput) {
                                checkNoInput.value = data.check_no || '';
                            }
                        }
                    }
                    
                    // Manually update display values
                    const gross = parseFloat(data.gross || 0);
                    const returns = parseFloat(data.returns || 0);
                    const payments = parseFloat(data.payments || 0);
                    const debit = parseFloat(data.debit || 0);
                    const credit = parseFloat(data.credit || 0);

                    // Calculate derived values
                    const billed = gross - returns;
                    const amount = billed - payments;
                    const balance = billed - credit;
                    const accepted = gross - returns - payments;

                    // Update display elements
                    const grossDisplay = document.querySelector('.gross-display');
                    if (grossDisplay) {
                        grossDisplay.textContent = gross.toFixed(2);
                    }

                    const billedDisplay = document.querySelector('.billed-display');
                    if (billedDisplay) {
                        billedDisplay.textContent = billed.toFixed(2);
                    }

                    const amountDisplay = document.querySelector('.amount-display');
                    if (amountDisplay) {
                        amountDisplay.textContent = amount.toFixed(2);
                    }

                    const acceptedInput = document.querySelector('.accepted-value');
                    if (acceptedInput) {
                        acceptedInput.value = accepted.toFixed(2);
                    }

                    // Make sure balance is set
                    const balanceInput = document.getElementById('balanceInput');
                    if (balanceInput) {
                        balanceInput.value = balance.toFixed(2);
                    }

                    console.log('Calculated values:', {
                        gross, returns, payments, debit, credit,
                        billed, amount, balance, accepted
                    });
                    
                    // Set readonly based on status
                    const formElements = document.querySelectorAll('#invoiceForm input:not(#invoiceNo), #invoiceForm select, #invoiceForm textarea');
                    
                    formElements.forEach(el => {
                        if (isPending) {
                            el.removeAttribute('readonly');
                            if (el.tagName === 'SELECT') {
                                el.disabled = false;
                            }
                        } else {
                            el.setAttribute('readonly', true);
                            if (el.tagName === 'SELECT') {
                                el.disabled = true;
                            }
                        }
                    });
                    
                    // Keep these fields readonly regardless
                    if (dateInput) dateInput.setAttribute('readonly', true);
                    if (statusInput) statusInput.setAttribute('readonly', true);
                    if (projectIdInput) projectIdInput.setAttribute('readonly', true);
                    if (customerDisplayInput) customerDisplayInput.setAttribute('readonly', true);
                    
                    // Add this code in your search function after retrieving the data
                    // IMPORTANT: Update the input fields with the values from the database
                    if (document.getElementById('grossInput')) {
                        document.getElementById('grossInput').value = gross.toFixed(2);
                        console.log('Setting grossInput value to:', gross.toFixed(2));
                    }

                    if (document.getElementById('returnsInput')) {
                        document.getElementById('returnsInput').value = returns.toFixed(2);
                        console.log('Setting returnsInput value to:', returns.toFixed(2));
                    }

                    if (document.getElementById('paymentInput')) {
                        document.getElementById('paymentInput').value = payments.toFixed(2);
                        console.log('Setting paymentInput value to:', payments.toFixed(2));
                    }

                    if (document.getElementById('debitInput')) {
                        document.getElementById('debitInput').value = debit.toFixed(2);
                        console.log('Setting debitInput value to:', debit.toFixed(2));
                    }

                    if (document.getElementById('creditInput')) {
                        document.getElementById('creditInput').value = credit.toFixed(2);
                        console.log('Setting creditInput value to:', credit.toFixed(2));
                    }
                } else {
                    alert('Invoice not found');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error searching for invoice');
            });
        });

        // Helper function to set select value
        function setSelectValue(selectElement, value) {
            for (let i = 0; i < selectElement.options.length; i++) {
                if (selectElement.options[i].value === value || selectElement.options[i].text === value) {
                    selectElement.selectedIndex = i;
                    break;
                }
            }
        }

        // Add this function to your JavaScript
        function clearForm() {
            // Reset all input fields
            document.querySelectorAll('#invoiceForm input:not(#invoiceNo), #invoiceForm textarea').forEach(el => {
                if (el.type === 'text' || el.type === 'hidden' || el.tagName === 'TEXTAREA') {
                    el.value = '';
                } else if (el.type === 'number') {
                    el.value = '0.00';
                } else if (el.type === 'date') {
                    el.value = '';
                }
            });
            
            // Reset select elements to first option
            document.querySelectorAll('#invoiceForm select').forEach(el => {
                if (el.options.length > 0) {
                    el.selectedIndex = 0;
                }
            });
            
            // Reset display elements
            document.querySelectorAll('.gross-display, .billed-display, .amount-display').forEach(el => {
                el.textContent = '0.00';
            });
            
            // Reset accepted value
            const acceptedInput = document.querySelector('.accepted-value');
            if (acceptedInput) {
                acceptedInput.value = '0.00';
            }
            
            // Reset balance
            const balanceInput = document.getElementById('balanceInput');
            if (balanceInput) {
                balanceInput.value = '0.00';
            }
            
            // Hide payment details
            document.querySelectorAll('.payment-details').forEach(el => {
                el.style.display = 'none';
            });
            
            // Hide VAT status if it exists
            const vatStatus = document.getElementById('vatStatus');
            if (vatStatus) {
                vatStatus.style.display = 'none';
            }
            
            // Remove any customer name display
            const customerNameDisplay = document.getElementById('customerNameDisplay');
            if (customerNameDisplay) {
                customerNameDisplay.textContent = '';
            }
        }
    </script>
</body>
</html> 
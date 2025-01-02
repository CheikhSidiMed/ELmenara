<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسديد المتأخرات</title>
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js"></script>
</head>
<body>
    <div class="container">
        <h2>تسديد المتأخرات</h2>
        <form id="arrears-form" method="POST" action="process_payment.php">
            <div>
                <label>المتأخرات</label>
                <div id="total-arrears"><?php echo number_format($total_remaining, 2); ?> أوقية جديدة</div>
            </div>
            <div>
                <label for="amount_paid">المبلغ المدفوع</label>
                <input type="number" id="amount_paid" name="amount_paid" required onchange="calculateRemaining()">
            </div>
            <div>
                <label for="remaining_amount">الباقي</label>
                <div id="remaining_amount">0.00 أوقية جديدة</div>
            </div>

            <div class="method-section mt-3">
                <div>  
                    <label for="method">طريقة الدفع</label>
                    <select id="method" name="payment_method" onchange="toggleBankModalInArrears(this.value)">
                        <option value="نقدي">نقدي</option>
                        <option value="بنكي">بنكي</option>
                    </select>
                </div>
                <!-- Display the selected bank name and value -->
                <div id="selected-bank-name-arrears"></div>
                <input type="hidden" id="selected-bank-id-arrears" name="bank">
            </div>

            <button type="submit" class="confirm-button">تأكيد العملية</button>
        </form>
    </div>

    <script>
        function calculateRemaining() {
            const totalArrears = parseFloat('<?php echo $total_remaining; ?>');
            const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
            const remaining = totalArrears - amountPaid;
            document.getElementById('remaining_amount').innerText = remaining >= 0 ? remaining.toFixed(2) + ' أوقية جديدة' : '0.00 أوقية جديدة';
        }

        // Other necessary functions for bank modal handling
        function toggleBankModalInArrears(paymentMethod) {
            if (paymentMethod === 'بنكي') {
                var bankModal = new bootstrap.Modal(document.getElementById('bankModal'), {
                    keyboard: false
                });
                bankModal.show();
            } else if (paymentMethod === 'نقدي') {
                clearSelectedBankNameInArrears();
            }
        }

        function selectBankInArrears() {
            const bankSelect = document.getElementById('bank');
            const selectedBankName = bankSelect.options[bankSelect.selectedIndex].text;
            const selectedBankId = bankSelect.options[bankSelect.selectedIndex].value;
            
            document.getElementById('selected-bank-id-arrears').value = selectedBankId;
            document.getElementById('selected-bank-name-arrears').innerText = 'البنك المحدد: ' + selectedBankName;
        }

        function clearSelectedBankNameInArrears() {
            document.getElementById('selected-bank-name-arrears').innerText = '';
        }
    </script>
</body>
</html>

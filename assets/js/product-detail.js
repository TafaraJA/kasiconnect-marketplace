document.addEventListener('DOMContentLoaded', function () {

    const quantityInput = document.getElementById('quantity');
    if (!quantityInput) return;

    const unitPriceEl = document.querySelector('.product-price-large');
    if (!unitPriceEl) return;

    // Extract numeric price from "R 1,000.00" format
    const priceText = unitPriceEl.textContent.replace(/[^\d.]/g, '');
    const unitPrice = parseFloat(priceText);
    const maxStock = parseInt(quantityInput.max, 10);

    // Build the live total element
    const totalDisplay = document.createElement('p');
    totalDisplay.id = 'live-total';
    totalDisplay.className = 'live-total';
    totalDisplay.innerHTML = 'Total: <strong>R ' + unitPrice.toFixed(2) + '</strong>';

    const actions = document.querySelector('.product-actions');
    if (actions) {
        actions.insertBefore(totalDisplay, actions.querySelector('form'));
    }

    // Update total when quantity changes
    function updateTotal() {
        let qty = parseInt(quantityInput.value, 10);

        // Validate quantity
        if (isNaN(qty) || qty < 1) qty = 1;
        if (qty > maxStock) {
            qty = maxStock;
            showStockWarning();
        }
        quantityInput.value = qty;

        const total = (unitPrice * qty).toFixed(2);
        totalDisplay.innerHTML = 'Total: <strong>R ' + total + '</strong>';
    }

    function showStockWarning() {
        let warn = document.getElementById('stock-warning');
        if (warn) return;
        warn = document.createElement('small');
        warn.id = 'stock-warning';
        warn.className = 'stock-warning';
        warn.textContent = 'Maximum available: ' + maxStock;
        quantityInput.parentNode.appendChild(warn);
        setTimeout(function () {
            if (warn.parentNode) warn.remove();
        }, 3000);
    }

    quantityInput.addEventListener('input', updateTotal);
    quantityInput.addEventListener('change', updateTotal);

    // Initialize on page load
    updateTotal();
});
let saleItems = [];

$(document).ready(function () {
    $("#inventory_search").focus();

    // Autocomplete Inventory
    $("#inventory_search").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "search_inventory.php",
                type: "GET",
                dataType: "json",
                data: { term: request.term },
                success: function (data) {
                    response($.map(data, function (item) {
                        return {
                            label: item.inventory_name + " (Stock: " + item.quantity + ")",
                            value: item.inventory_name,
                            id: item.inventory_id,
                            price: item.selling_price,
                            stock: item.quantity
                        };
                    }));
                }
            });
        },
        minLength: 1,
        select: function (event, ui) {
            $("#inventory_id").val(ui.item.id);
            $("#inventory_name").val(ui.item.value);
            $("#selling_price").val(ui.item.price);
            $("#quantity").val("1");

            $("#current_stock").text("Current stock: " + ui.item.stock);
            $("#inventory_search").data("stock", ui.item.stock);

            $("#quantity").focus();
        }
    });

    // Validate quantity vs stock
    $("#quantity, #selling_price").on("change", function () {
        let qty = parseInt($("#quantity").val()) || 0;
        let price = parseFloat($("#selling_price").val()) || 0;
        let currentStock = parseInt($("#inventory_search").data("stock")) || 0;

        if (qty > currentStock) {
            alert("Not enough stock available!");
            $("#quantity").val(currentStock || 1);
        }
    });

    // Amount tendered - calculate change
    $("#amount_tendered").on("change", function () {
        let amountTendered = parseFloat(this.value) || 0;
        let grandTotal = parseFloat($("#grand_total").text()) || 0;
        let change = amountTendered - grandTotal;
        $("#change_amount").val(change > 0 ? change.toFixed(2) : "0.00");
    });
});

function addItem() {
    let inventory_id = $("#inventory_id").val();
    let inventory_name = $("#inventory_name").val();
    let quantity = parseInt($("#quantity").val());
    let selling_price = parseFloat($("#selling_price").val());
    let customer_id = $("#customer_id").val();
    let stock = parseInt($("#inventory_search").data("stock")) || 0;

    if (!inventory_id || !inventory_name || !quantity || !selling_price) {
        alert("Please fill all required fields");
        return;
    }

    if (quantity > stock) {
        alert("Not enough stock available! Current stock: " + stock);
        return;
    }

    let total = (quantity * selling_price).toFixed(2);

    saleItems.push({
        inventory_id: inventory_id,
        inventory_name: inventory_name,
        quantity: quantity,
        selling_price: selling_price,
        total_amount: parseFloat(total),
        customer_id: customer_id
    });

    updateSalesTable();
    calculateTotals();

    // Clear fields
    $("#inventory_search").val("");
    $("#inventory_id").val("");
    $("#inventory_name").val("");
    $("#quantity").val("1");
    $("#selling_price").val("");
    $("#current_stock").text("Current stock: 0");
    $("#inventory_search").data("stock", 0);
    $("#inventory_search").focus();
}

function updateSalesTable() {
    let tableBody = $("#salesTable tbody");
    tableBody.empty();

    saleItems.forEach((item, index) => {
        tableBody.append(`
            <tr data-index="${index}" data-inventory-id="${item.inventory_id}">
                <td>${item.inventory_name}</td>
                <td>${item.quantity}</td>
                <td>${item.selling_price.toFixed(2)}</td>
                <td>${item.total_amount.toFixed(2)}</td>
                <td>
                    <button onclick="editItem(${index})" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                    <button onclick="removeItem(${index})" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `);
    });
}

function removeItem(index) {
    if (confirm("Are you sure you want to remove this item?")) {
        saleItems.splice(index, 1);
        updateSalesTable();
        calculateTotals();
    }
}

function editItem(index) {
    let item = saleItems[index];
    $("#inventory_search").val(item.inventory_name);
    $("#inventory_id").val(item.inventory_id);
    $("#inventory_name").val(item.inventory_name);
    $("#quantity").val(item.quantity);
    $("#selling_price").val(item.selling_price.toFixed(2));

    saleItems.splice(index, 1);
    updateSalesTable();
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    saleItems.forEach(item => {
        subtotal += item.total_amount;
    });

    let discountPercent = parseFloat($("#discount_percent").val()) || 0;
    let discountAmount = (subtotal * (discountPercent / 100)).toFixed(2);
    let taxableAmount = subtotal - discountAmount;
    let taxAmount = (taxableAmount * 0.015).toFixed(2);
    let grandTotal = subtotal.toFixed(2);

    $("#subtotal").text(subtotal.toFixed(2));
    $("#discount_amount").text(discountAmount);
    $("#tax_amount").text(taxAmount);
    $("#grand_total").text(grandTotal);
}

function submitSales() {
    if (saleItems.length === 0) {
        alert("No items to submit.");
        return;
    }

    let formData = {
        submit_sales: true,
        receipt_id: $("#receipt_id").val(),
        waiter_name: $("#waiter_name").val(),
        staff_id: $("#staff_id").val(),
        customer_id: $("#customer_id").text(),
        customer_name: "",
        total_amount: parseFloat($("#subtotal").text()) || 0,
        discount: parseFloat($("#discount_amount").text()) || 0,
        tax_amount: parseFloat($("#tax_amount").text()) || 0,
        grand_total: parseFloat($("#grand_total").text()) || 0,
        payment_method: $("#payment_method").val(),
        items: saleItems
    };

    console.log("Sending data:", formData);

    $.ajax({
        url: '../Sales/sales_submission.phpp', // ?? Make sure this is not a typo
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
            console.log("Server response:", response);
            if (response.status === "success") {
                alert(response.message);
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            } else {
                alert("Error: " + (response.message || "Unknown error."));
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", status, error);
            console.log("Response text:", xhr.responseText);
            alert("Failed to submit sale. Check console for details.");
        }
    });
}

function printReceipt(receiptIdToPrint = null) {
    let receiptId = receiptIdToPrint || $("#receipt_id").val();
    if (!receiptId) {
        alert("No receipt to print");
        return;
    }
    window.open(`print_receipt.php?receipt_id=${receiptId}`, '_blank').focus();
}

function clearSale() {
    if (confirm("Are you sure you want to clear this sale?")) {
        saleItems = [];
        $("#salesTable tbody").empty();
        $("#inventory_search").val("");
        $("#inventory_id").val("");
        $("#inventory_name").val("");
        $("#quantity").val("1");
        $("#selling_price").val("");
        $("#discount_percent").val("0");
        $("#payment_method").val("Cash");
        $("#amount_tendered").val("");
        $("#change_amount").val("");
        $("#current_stock").text("Current stock: 0");
        $("#subtotal").text("0.00");
        $("#discount_amount").text("0.00");
        $("#tax_amount").text("0.00");
        $("#grand_total").text("0.00");

        $.get('generate_receipt_id.php', function (data) {
            $("#receipt_id").val(data.receipt_id);
        });
    }
}

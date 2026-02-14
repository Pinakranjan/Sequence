"use strict";

// currency format
function currencyFormat(amount, type = "icon", decimals = 2) {
    let symbol = $("#currency_symbol").val();
    let position = $("#currency_position").val();
    let code = $("#currency_code").val();

    let formatted_amount = formattedAmount(amount, decimals);

    // Apply currency format based on the position and type
    if (type === "icon" || type === "symbol") {
        if (position === "right") {
            return formatted_amount + symbol;
        } else {
            return symbol + formatted_amount;
        }
    } else {
        if (position === "right") {
            return formatted_amount + " " + code;
        } else {
            return code + " " + formatted_amount;
        }
    }
}
// Format the amount
function formattedAmount(amount, decimals) {
    return Number.isInteger(+amount)
        ? parseInt(amount)
        : (+amount).toFixed(decimals);
}

// get number only
function getNumericValue(value) {
    return parseFloat(value.replace(/[^0-9.-]+/g, "")) || 0;
}


//purchase js start

$(document).ready(function() {
    $('select[id="ingredient"]').on('change', function() {
        let selectedOption = $(this).find('option:selected');
        let id = selectedOption.data('id');
        let name = selectedOption.data('name');
        let route = selectedOption.data('route');

        if(!id) return;

        $.ajax({
            url: route,
            type: 'POST',
            data: {
                type: 'purchase',
                id: id,
                name: name,
            },
            success: function(response) {
                if(response.success) {
                    fetchUpdatedCart();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message || 'Failed to add item.');
                }
            },
            error: function(xhr) {
                toastr.error('Something went wrong while adding to cart.');
            }
        });
    });
});

// Update the cart list and call the callback once complete
function fetchUpdatedCart(callback) {
    let url = $("#purchase-cart").val();
    $.ajax({
        url: url,
        type: "GET",
        success: function (response) {
            $("#purchase_cart_list").html(response);
            if (typeof callback === "function") callback(); // Call the callback after updating the cart
        },
    });
}

// Remove item from the cart
$(document).on("click", ".remove-cart", function (e) {
    e.preventDefault();
    var $row = $(this).closest("tr");
    var destroyRoute = $row.data("destroy_route");

    $.ajax({
        url: destroyRoute,
        type: "DELETE",
        success: function (response) {
            if (response.success) {
                // Item was successfully removed, fade out and remove the row from DOM
                $row.fadeOut(400, function () {
                    $(this).remove();
                    if ($(".product-cart-tr").length === 0) {
                        // reset inputs
                        $("#receive_amount").val("");
                        $("#due_amount").val("");
                        $("#sub_total").val("");
                        $("#total_amount").text(currencyFormat("0"));
                        $("#discountPercentShow").text(currencyFormat("0"));
                        $(".discount_input").val("");
                        $("#discountPercentage").val("");
                        $("#taxPercentShow").text(currencyFormat("0"));
                        $("#tax_percentage").val("");
                        $(".tax_input").val("");
                    } else {
                        // If still items remain, just recalc totals
                        fetchUpdatedCart(calTotalAmount);
                    }
                });

            } else {
                toastr.error(response.message || "Failed to remove item");
            }
        },
        error: function () {
            toastr.error("Error removing item from cart");
        },
    });
});

// Cancel btn action
$(".cancel-purchase-btn").on("click", function (e) {
    e.preventDefault();
    clearCart("purchase");
     // reset inputs
     $("#receive_amount").val("");
     $("#due_amount").val("");
     $("#sub_total").val("");
     $("#total_amount").text(currencyFormat("0"));
     $("#discountPercentShow").text(currencyFormat("0"));
     $(".discount_input").val("");
     $("#discountPercentage").val("");
     $("#taxPercentShow").text(currencyFormat("0"));
     $("#tax_percentage").val("");
     $(".tax_input").val("");
});

function clearCart(cartType) {
    let route = $("#clear-cart").val();
    $.ajax({
        type: "POST",
        url: route,
        data: {
            type: cartType,
        },
        dataType: "json",
        success: function (response) {
            fetchUpdatedCart(calTotalAmount); // Call calTotalAmount after cart fetch completes
        },
        error: function () {
            console.error("There was an issue clearing the cart.");
        },
    });
}





// plus button
$(document).on("click", ".plus-btn", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());
    let unitPrice = parseFloat($row.find(".unitPrice").val()) || 0;
    let newUnitId = $row.find(".cart-unit-select").val();


    let newQty = currentQty + 1;
    $qtyInput.val(newQty);
    updateCartItem(rowId, newQty, unitPrice, updateRoute, newUnitId);
});

// minus button
$(document).on("click", ".minus-btn", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());
    let unitPrice = parseFloat($row.find(".unitPrice").val()) || 0;
    let newUnitId = $row.find(".cart-unit-select").val();

    if (currentQty > 1) {
        let newQty = currentQty - 1;
        $qtyInput.val(newQty);
        updateCartItem(rowId, newQty, unitPrice, updateRoute, newUnitId);
    }
});

// Cart quantity input field change event
$(document).on("change", ".cart-qty", function () {
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let newQty = parseFloat($(this).val());
    let unitPrice = parseFloat($row.find(".unitPrice").val()) || 0;
    let newUnitId = $row.find(".cart-unit-select").val();


    // Ensure quantity does not go below 1
    if (newQty >= 0) {
        updateCartItem(rowId, newQty, unitPrice, updateRoute, newUnitId);
    }
});

// unit price update
$(document).on("change", ".unitPrice", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let unitPrice = parseFloat($(this).val()) || 0;
    let qty = parseFloat($row.find(".cart-qty").val());
    let newUnitId = $row.find(".cart-unit-select").val();

    updateCartItem(rowId, qty, unitPrice, updateRoute, newUnitId);
});

// Cart unit change event
$(document).on("change", ".cart-unit-select", function () {
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let newUnitId = $(this).val();

    // Get the current qty and price so they are not lost during update
    let newQty = parseFloat($row.find(".cart-qty").val()) || 0;
    let unitPrice = parseFloat($row.find(".unitPrice").val()) || 0;

    updateCartItem(rowId, newQty, unitPrice, updateRoute, newUnitId);
});


// unified update function
function updateCartItem(rowId, qty, price, updateRoute, unitId) {
    $.ajax({
        url: updateRoute,
        type: "PUT",
        data: {
            rowId: rowId,
            qty: qty,
            price: price,
            unit_id: unitId
        },
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(calTotalAmount); // refresh cart from server
            } else {
                toastr.error(response.message || "Failed to update cart");
            }
        },
        error: function () {
            toastr.error("Error updating cart");
        },
    });
}

// Trigger calculation whenever Discount, or Receive Amount fields change
$(document).on("input", ".discount_input, #receive_amount, .tax_input", function() {
    calTotalAmount();
});


// Function to calculate the total amount
function calTotalAmount() {
    let subtotal = 0;

    // Calculate subtotal from cart list
    $("#purchase_cart_list tr").each(function () {
        let cart_subtotal = getNumericValue($(this).find(".cart-subtotal").text()) || 0;
        subtotal += cart_subtotal;
    });

    $("#sub_total").text(currencyFormat(subtotal));

    // === Discount ===
    let discount_input = parseFloat($(".discount_input").val()) || 0;
    let discount_amount = 0;
    let discount_percent = 0;

    if (discount_input > 0) {
        // User entered discount amount -> calculate percent
        discount_amount = discount_input;
        discount_percent = (discount_amount / subtotal) * 100;
    }
    // Cap discount to subtotal
    if (discount_amount > subtotal) {
        toastr.error("Discount cannot be more than the subtotal!");
        discount_amount = subtotal;
        discount_percent = 100;
        $(".discount_input").val(subtotal);
    }

    $("#discountPercentage").val(discount_percent.toFixed(2));
    $("#discountPercentShow").text(discount_percent.toFixed(2));


    // === Tax ===
    let tax_input = getNumericValue($(".tax_input").val()) || 0;

    let tax_amount = 0;
    let tax_percent = 0;

    if (tax_input > 0) {
        // User entered tax amount -> calculate percent
        tax_amount = tax_input;
        tax_percent = (tax_amount / subtotal) * 100;
    }
    $("#tax_percentage").val(tax_percent.toFixed(2));
    $("#taxPercentShow").text(tax_percent.toFixed(2));

    // === Total Amount ===
    let total_amount = subtotal + tax_amount - discount_amount;
    $("#total_amount").text(currencyFormat(total_amount));

    // === Receive Amount & Due ===
    let receive_amount = getNumericValue($("#receive_amount").val()) || 0;
    if (receive_amount < 0) {
        toastr.error("Receive amount cannot be less than 0!");
        receive_amount = 0;
        $("#receive_amount").val(receive_amount);
    }

    let due_amount = total_amount > receive_amount ? total_amount - receive_amount : 0;
    $("#due_amount").val(due_amount.toFixed(2));
}


calTotalAmount();


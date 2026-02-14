"use strict";
// Sidebar compress style
$(document).ready(function () {
    var $sidebarPlan = $(".lg-sub-plan");
    var $subPlan = $(".sub-plan");
    var isActive = $(window).width() >= 1150;

    // Toggle the “active” class on load
    $(".side-bar, .section-container").toggleClass("active", isActive);

    // Show/hide plans based on width
    if (isActive) {
        $sidebarPlan.hide();
        $subPlan.show();
    } else {
        $sidebarPlan.show();
        $subPlan.hide();
    }
});

document.addEventListener("DOMContentLoaded", function () {
    new Splide("#sales-splide", {
        perPage: 5,
        gap: "15px",
        arrows: true,
        pagination: false,
        breakpoints: {
            1024: {
                perPage: 5,
            },
            640: {
                perPage: 3,
            },
            320: {
                perPage: 2,
            },
        },
    }).mount();
});

//currency js start

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

function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1).replace(/\.0$/, "") + "M";
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1).replace(/\.0$/, "") + "k";
    }
    return num;
}

// get number only
function getNumericValue(value) {
    return parseFloat(value.replace(/[^0-9.-]+/g, "")) || 0;
}

document.querySelectorAll(".category-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
        document
            .querySelectorAll(".category-btn")
            .forEach((b) => b.classList.remove("active"));
        this.classList.add("active");
    });
});

//product search start
const form = $(".product-filter-form")[0];
$(form).on("submit", function (e) {
    e.preventDefault();
    fetchProducts();
});

let typingTimer;
$(".search-input").on("keyup", function () {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        fetchProducts();
    }, 300);
});

$(".category-btn").on("click", function () {
    $(".category-btn").removeClass("active");
    $(this).addClass("active");

    // set hidden category_id input
    $("#category_id").val($(this).data("id"));

    fetchProducts();
});

function fetchProducts() {
    $.ajax({
        type: "POST",
        url: $(form).attr("action"),
        data: new FormData(form),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            $("#products-list").html(res.data);
        },
    });
}
//product search end

$(document).ready(function () {
    function toggleFields(type) {
        $(".row.g-3[data-type]")
            .hide()
            .find("select, input, textarea")
            .prop("disabled", true);
        $('.row.g-3[data-type="' + type + '"]')
            .show()
            .find("select, input, textarea")
            .prop("disabled", false);
    }

    // Show Dine In initially
    toggleFields("dine_in");
    $("#saveBtn").addClass("d-none").prop("disabled", true);

    $(".saleType").on("click", function () {
        var type = $(this).data("type");
        var $form = $("form.ajaxform_redirect_invoice");
        var defaultRoute = $form.data("default_route");

        $(".saleType").removeClass("active-sale-type");
        $(this).addClass("active-sale-type");

        toggleFields(type);
        if (type === "pick_up") {
            $("#salesTypeForPickUp").val("pick_up").prop("disabled", false);
        } else {
            $("#salesTypeForPickUp").val("").prop("disabled", true); // fully disabled
        }

        if (type === "quotation") {
            $(".print, .kot, #paymentBtnHide")
                .addClass("d-none")
                .prop("disabled", true);
            $("#saveBtn").removeClass("d-none").prop("disabled", false);

            $(".bottom-button-group").addClass("single-btn-mode");

            var quotationRoute = $(this).data("quotation_route");
            $form.attr("action", quotationRoute);
        } else {
            $(".print, .kot, #paymentBtnHide")
                .removeClass("d-none")
                .prop("disabled", false);
            $("#saveBtn").addClass("d-none").prop("disabled", true);

            // reset
            $(".bottom-button-group").removeClass("single-btn-mode");

            $form.attr("action", defaultRoute);
        }
    });
});

// Dropdown option select
$(document).on("click", ".tableSelect", function (e) {
    let tableId = $(this).data("id");
    let tableName = $(this)
        .clone()
        .children(".hold")
        .remove()
        .end()
        .text()
        .trim();

    $(this)
        .closest(".custom-dropdown-2")
        .find(".dropdown-toggle")
        .text(tableName);

    $("#table_id").val(tableId);

    $(this).closest(".custom-dropdown-2").find(".dropdown-menu").hide();
});

$(document).on("click", function (e) {
    if (!$(e.target).closest(".custom-dropdown-2").length) {
        $(".custom-dropdown-2 .dropdown-menu").hide();
    }
});

$(document).on("click", ".dropdown-toggle", function (e) {
    e.stopPropagation();
    $(this).siblings(".dropdown-menu").toggle();
});

$(document).ready(function () {
    $("#customerSelect").on("change", function () {
        var customerId = $(this).val();
        var $addressSelect = $("#deliveryAddressAppend");

        if (customerId) {
            var route = $("#getDeliveryAddress").val();
            var selectedDeliveryAddressId = $(
                "#selectedDeliveryAddressId"
            ).val();
            $.ajax({
                url: route,
                type: "GET",
                data: { customer_id: customerId }, // send customer id
                dataType: "json",
                success: function (data) {
                    if (data.length) {
                        $.each(data, function (index, address) {
                            $addressSelect.append(
                                '<option value="' +
                                    address.id +
                                    '">' +
                                    address.name +
                                    "</option>"
                            );
                        });

                        if (selectedDeliveryAddressId) {
                            $addressSelect.val(selectedDeliveryAddressId);
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error(error);
                },
            });
        }
    });
    //  Trigger on page load (edit mode)
    if ($("#customerSelect").val()) {
        $("#customerSelect").trigger("change");
    }
});

$(document).ready(function () {
    $(".deliveryAddBtn").on("click", function (e) {
        let customerId = $(".getCustomerId").val();
        let modalPartyId = $("#modalPartyId");

        if (!customerId) {
            e.stopPropagation();
            toastr.error("Please select a customer first.");
            return false;
        }

        modalPartyId.val(customerId);

        $("#deliveryAddress-create-modal").modal("show");
    });
});

//item added in cart js start
let selectedProduct = {};

$(document).on("click", ".variation-product", function () {
    showProductModal($(this));
});

function showProductModal(element) {
    selectedProduct = {};

    selectedProduct = {
        productId: element.data("product-id"),
        productName: element.data("product-name"),
        category: element.data("category"),
        foodType: element.data("food-type"),
        preparationTime: element.data("preparation-time"),
        image: element.data("image"),
        description: element.data("description"),
        variations: element.data("variations") || [],
        modifierGroupOptions: element.data("modifier-groups-option") || [],
    };


    // Set modal display values
    $("#variationProductName").text(selectedProduct.productName);
    $("#category-food").text(
        selectedProduct.category + " - " + selectedProduct.foodType
    );
    $("#preparation_time").text(selectedProduct.preparationTime + " min");
    $("#modalProductImage").attr("src", selectedProduct.image);
    $("#description").text(selectedProduct.description);

    //Render Variations
    let variationHtml = "";
    if (selectedProduct.variations.length > 0) {
        selectedProduct.variations.forEach((variation, index) => {
                    variationHtml += `
                    <div class="option-card addons-item variation-item ${index === 0 ? "active" : ""}"
                        data-variation-id="${variation.id}">
                        <label class="option-label">
                            <input type="checkbox"
                                class="variation-option"
                                value="${variation.id}"
                                ${index === 0 ? "checked" : ""}>
                            <span class="custom-check"></span>
                            <span class="addons-name">${variation.name || "Variation"}</span>
                            <span class="price" data-price="${variation.price ?? 0 }">${currencyFormat(variation.price ?? 0)}</span>
                        </label>
                    </div>
                `;
            });

        // Set default first variation as selected
        let firstVariation = selectedProduct.variations[0];
        selectedProduct.selectedVariation = {
            variation_id: firstVariation.id,
            price: firstVariation.price ?? 0,
        };
    } else {
        //If no variations
        variationHtml = `<p class="text-muted">No variations available</p>`;

        // Get base sales price from data attribute
        const basePrice = parseFloat(element.data("sales-price") ?? 0);

        selectedProduct.selectedVariation = {
            variation_id: null,
            price: basePrice,
        };
    }
    $("#variation-container").html(variationHtml);

    // Render Modifier Group
    let modifierHtml = "";
    if (selectedProduct.modifierGroupOptions.length > 0) {
        selectedProduct.modifierGroupOptions.forEach((group, index) => {
            let optionsHtml = "";
            group.options.forEach((option) => {
                if (option.is_available) {
                    optionsHtml += `
                    <label class="addons-item">
                        <input type="checkbox"
                            class="modifier-option"
                            data-modifier-id="${group.modifier_id}"
                            data-option-id="${option.id}"
                            data-name="${option.name}"
                            data-price="${option.price}"
                            data-group-id="${group.id}"
                            data-multiple="${group.is_multiple ? 1 : 0}">
                        <span class="custom-check"></span>
                        <span class="addons-name">${option.name}</span>
                        <span class="addons-price">${currencyFormat(
                            option.price ?? 0
                        )}</span>
                    </label>
                    `;
                }
            });

            modifierHtml += `
                <div class="addons-section mt-3" data-required="${
                    group.is_required ? 1 : 0
                }">
                    <div class="addons-header d-flex align-items-center justify-content-between" style="cursor:pointer">
                     <div class="d-flex align-items-center svg-toggle">
                        <span class="circle-icon me-2">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="">
                                    <path d="M10.0003 18.3337C14.6027 18.3337 18.3337 14.6027 18.3337 10.0003C18.3337 5.39795 14.6027 1.66699 10.0003 1.66699C5.39795 1.66699 1.66699 5.39795 1.66699 10.0003C1.66699 14.6027 5.39795 18.3337 10.0003 18.3337Z" stroke="#FC8019" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6.66699 10H13.3337" stroke="#FC8019" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                            </svg>
                        </span>
                        <h3  class="m-0">${group.name}</h3>
                        </div>
                    </div>
                    <div class="group-options" style="display:${
                        index === 0 ? "block" : "none"
                    };">
                        ${optionsHtml}
                    </div>
                    <div class="modifier-error text-danger" style="display:none;"></div>
                </div>
            `;
        });
    } else {
        modifierHtml = `<p class="text-muted">No addons available</p>`;
    }
    $("#modifier-container").html(modifierHtml);

    $("#variation-product-modal").modal("show");
    updateTotalPrice();
}
// Collapse/Expand on SVG click
$(document).on("click", ".svg-toggle", function () {
    const groupDiv = $(this).closest(".addons-section").find(".group-options");
    groupDiv.slideToggle(); // smooth toggle

    // Optional: rotate SVG for UX
    $(this).find("svg").toggleClass("rotated");
});


// Handle variation selection
$(document).on("change", ".variation-item .variation-option", function () {
    $(".variation-item .variation-option").not(this).prop("checked", false);

    $(".variation-item").removeClass("active");

    if ($(this).is(":checked")) {
        $(this).closest(".variation-item").addClass("active");
    }

    let $variationItem = $(this).closest(".variation-item");
    let variationId = $variationItem.data("variation-id");

    let selectedPrice = $variationItem.find(".price").data('price');

    selectedProduct.selectedVariation = {
        variation_id: variationId,
        price: selectedPrice,
    };

    updateTotalPrice();
});




//check multiple select
$(document).on("change", ".modifier-option", function () {
    const groupId = $(this).data("group-id");
    const isMultiple = $(this).data("multiple");

    if (!isMultiple) {
        $(`.modifier-option[data-group-id="${groupId}"]`)
            .not(this)
            .prop("checked", false);
    }
    updateTotalPrice();
});

//variation modal qty
let quantity = 1;
$(document).on("click", ".variationPlus", function () {
    let input = $(this).siblings(".variationInputQTy");
    let val = parseInt(input.val()) || 1;
    input.val(val + 1).trigger("input");
});

$(document).on("click", ".variationMinus", function () {
    let input = $(this).siblings(".variationInputQTy");
    let val = parseInt(input.val()) || 1;
    if (val > 1) {
        input.val(val - 1).trigger("input");
    }
});

// When input changes manually
$(document).on("input", ".variationInputQTy", function () {
    let val = parseInt($(this).val()) || 1;
    if (val < 1) val = 1;
    $(this).val(val);

    quantity = val;
    updateTotalPrice();
});

function calculateTotalWithoutQty() {
    let total = 0;

    // Variation price
    if (selectedProduct.selectedVariation) {
        total += parseFloat(selectedProduct.selectedVariation.price || 0);
    }

    // Modifiers
    selectedProduct.selectedModifiers = [];
    $(".modifier-option:checked").each(function () {
        let price = parseFloat($(this).data("price") || 0);
        let optionId = $(this).data("option-id");
        let modifierId = $(this).data("modifier-id");
        total += price;

        selectedProduct.selectedModifiers.push({
            option_id: optionId,
            modifier_id: modifierId,
            price: price,
        });
    });

    return total;
}

//variation and mdifier total price
function updateTotalPrice() {
    let total = calculateTotalWithoutQty();

    let qty = parseInt($(".variationInputQTy").val()) || 1;
    total = total * qty;

    $(".add-to-cart").text(`Add to Cart  ${currencyFormat(total)}`);
}

function validateModifiers() {
    let isValid = true;

    $(".addons-section").each(function () {
        const $section = $(this);
        const required = $section.data("required");
        const checked = $section.find("input.modifier-option:checked").length;
        const $errorDiv = $section.find(".modifier-error");

        if (required && checked === 0) {
            isValid = false;
            $errorDiv.text("Please select the modifier option").show();
        } else {
            $errorDiv.text("").hide();
        }
    });

    return isValid;
}

let savingLoader =
        '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
    $product_modal_reload = $("#variationAddCartmodal");
$product_modal_reload.initFormValidation(),
    // item modal action
    $("#variationAddCartmodal").on("submit", function (e) {
        e.preventDefault();
        if (!validateModifiers()) {
            // Scroll to first error if present
            const firstError = $(".modifier-error:visible").first();
            if (firstError.length) {
                $("html, body").animate(
                    { scrollTop: firstError.offset().top - 100 },
                    300
                );
            }
            return false; // stop submission
        }
        if (!$product_modal_reload.valid()) return;

        let t = $(this).find(".submit-btn"),
            a = t.html();
        let url = $(this).data("route");
        let quantity = parseFloat($(".variationInputQTy").val());
        $(".variationInputQTy").val("1");
        let price = calculateTotalWithoutQty();
        $product_modal_reload.valid() &&
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    type: "sale",
                    id: selectedProduct.productId,
                    name: selectedProduct.productName,
                    variation_id:
                        selectedProduct.selectedVariation?.variation_id || null,
                    modifiers: selectedProduct.selectedModifiers,
                    price: price,
                    quantity: quantity,
                },
                beforeSend: function () {
                    t.html(savingLoader).attr("disabled", !0);
                },
                success: function (response) {
                    t.html(a).removeClass("disabled").attr("disabled", !1);

                    if (response.success) {
                        playCartSound();
                        fetchUpdatedCart(calTotalAmount);
                        $("#variation-product-modal").modal("hide");
                    } else {
                        toastr.error(
                            response.message || "Failed to add product to cart."
                        );
                    }
                },
                error: function (xhr) {
                    toastr.error(
                        "An error occurred while adding product to cart."
                    );
                },
            });
    });

function playCartSound() {
    const soundPath = document.getElementById("cart-sound-path")?.value;
    if (soundPath) {
        let audio = new Audio(soundPath);
        audio.play().catch((err) => console.log("Audio play error:", err));
    }
}
//single product add to cart
$(document).on("click", ".single-product", function () {
    addItemToCart($(this));
});

function addItemToCart(element) {
    let url = element.data("route");
    let productId = element.data("product-id");
    let productName = element.data("product-name");
    let salesPrice = element.data("sales-price");

    $.ajax({
        url: url,
        type: "POST",
        data: {
            type: "sale",
            id: productId,
            name: productName,
            price: salesPrice,
            quantity: 1,
        },
        success: function (response) {
            if (response.success) {
                playCartSound();
                fetchUpdatedCart(calTotalAmount);
                $("#sale_product_search").val("");
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr) {
            console.error("Error:", xhr.responseText);
        },
    });
}

// Update the cart list and call the callback once complete
function fetchUpdatedCart(callback) {
    let url = $("#get-cart").val();
    $.ajax({
        url: url,
        type: "GET",
        success: function (response) {
            $("#cart-list").html(response);
            $(document).ready(function () {
                updateCartProductCount();
            });
            if (typeof callback === "function") callback();
        },
    });
}

$(document).ready(function () {
    updateCartProductCount();
});

function updateCartProductCount() {
    let productCount = $("#cart-list").find("tr.product-cart-tr").length;
    $(".totalCartProduct").text(productCount);
}


// Increase quantity
$(document).on("click", ".plus-btn", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());
    let newQty = currentQty + 1;
    $qtyInput.val(newQty);

    // Get the current price
    let currentPrice = parseFloat($row.find(".cart-price").text());

    if (isNaN(currentPrice) || currentPrice < 0) {
        toastr.error("Price can not be negative.");
        return;
    }
    updateCart(rowId, newQty, updateRoute, currentPrice);
});

// Decrease quantity
$(document).on("click", ".minus-btn", function (e) {
    e.preventDefault();
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let $qtyInput = $row.find(".cart-qty");
    let currentQty = parseFloat($qtyInput.val());

    // Ensure quantity does not go below 1
    if (currentQty > 1) {
        let newQty = currentQty - 1;
        $qtyInput.val(newQty);

        // Get the current price
        let currentPrice = parseFloat($row.find(".cart-price").text());
        if (isNaN(currentPrice) || currentPrice < 0) {
            toastr.error("Price can not be negative.");
            return;
        }

        // Call updateCart with both qty and price
        updateCart(rowId, newQty, updateRoute, currentPrice);
    }
});

// Cart quantity input field change event
$(document).on("change", ".cart-qty", function () {
    let $row = $(this).closest("tr");
    let rowId = $row.data("row_id");
    let updateRoute = $row.data("update_route");
    let newQty = parseFloat($(this).val());

    // Retrieve the cart price
    let currentPrice = parseFloat($row.find(".cart-price").text());
    if (isNaN(currentPrice) || currentPrice < 0) {
        toastr.error("Price can not be negative.");
        return;
    }

    // Ensure quantity does not go below 0
    if (newQty >= 0) {
        updateCart(rowId, newQty, updateRoute, currentPrice);
    }
});

// Remove item from the cart
$(document).on("click", ".singleCartRemove", function (e) {
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
                        $("#receive_amount").val("");
                        $("#due_amount").val("");
                        $("#sub_total").text(currencyFormat("0"));
                        $(".vatAmountValue").text(currencyFormat("0"));
                        $(".vatAmountValue").val("");
                        $(".null_by_reset").val("");
                        $(".customer-select").val("");
                        $(".tip").val("");
                        $(".discountPercentageShow").text("0");
                        $(".discountPercentageShow").val("");
                        $(".discount_amount").val("");
                        $(".couponPercentageShow").text("(0%)");
                        $(".couponAmountShow").text(currencyFormat("0"));
                        $(".couponPercentageVal").val(0);
                        $(".couponAmountVal").val(0);
                        $(".coupondiscount").val("");
                        $(".couponType").val("");
                        $(".couponId").val("");
                        $("#crossBtn").addClass("d-none");
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

// Function to update cart item with the new quantity
function updateCart(rowId, qty, updateRoute, price) {
    $.ajax({
        url: updateRoute,
        type: "PUT",
        data: {
            rowId: rowId,
            qty: qty,
            price: price,
        },
        success: function (response) {
            if (response.success) {
                fetchUpdatedCart(calTotalAmount); // Refresh the cart and recalculate totals
            } else {
                toastr.error(response.message || "Failed to update cart");
            }
        },
    });
}

// Clear the cart and then refresh the UI with updated values
function clearCart(cartType) {
    let route = $("#clear-cart").val();
    $.ajax({
        type: "POST",
        url: route,
        data: {
            type: cartType,
        },
        dataType: "json",
        success: function () {
            fetchUpdatedCart(calTotalAmount);
        },
        error: function () {
            console.error("There was an issue clearing the cart.");
        },
    });
}

$(document).on("click", ".getCoupon", function () {
    let url = $("#getSaleCoupon").val();

    $("#couponList").html(
        '<div class="text-center p-3">Loading coupons...</div>'
    );

    $.get(url, function (coupons) {
        if (coupons.length === 0) {
            $("#couponList").html(
                '<div class="text-center text-muted">No coupons available</div>'
            );
            return;
        }
        let colors = [
            "custom-bg-green",
            "custom-bg-orange",
            "custom-bg-blue",
            "custom-bg-pink",
        ];
        let html = "";
        coupons.forEach((coupon) => {
            // Pick random color
            let randomColor = colors[Math.floor(Math.random() * colors.length)];

            let description = coupon.description ?? "";
            if (description.length > 20) {
                description = description.substring(0, 50) + "...";
            }

            html += `
            <div class="coupon-card ${randomColor} mb-2">
              <span class="circle-top"></span>
                <span class="circle-bottom"></span>
                <div>
                    <div class="coupon-discount text-success">
                        ${
                            coupon.discount_type === "percentage"
                                ? coupon.discount + "%"
                                : currencyFormat(coupon.discount)
                        }
                        <br><small class="discount-text">Discount</small>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end w-100">
                <div class="coupon-details">
                    <div class="fw-bold">${coupon.name ?? "Coupon Offer"}</div>
                    <div class="small text-muted">${description}</div>
                    <div class="small text-muted">Till: ${
                        coupon.end_date ?? "N/A"
                    }</div>
                    <div class="coupon-code">CODE: ${coupon.code}</div>
                </div>
                <button class="btn-apply applyCoupon" data-id="${
                    coupon.id
                }" data-discount="${coupon.discount}" data-type="${
                coupon.discount_type
            }">Apply</button>
                </div>
            </div>`;
        });

        $("#couponList").html(html);
    });
});

$(document).on("click", ".applyCoupon", function () {
    let subtotal = 0;

    // Calculate current subtotal before applying coupon
    $("#cart-list tr.product-cart-tr").each(function () {
        let qty = getNumericValue($(this).find(".cart-qty").val()) || 0;
        let price = getNumericValue($(this).find(".cart-price").text()) || 0;
        subtotal += qty * price;
    });

    if (subtotal <= 0) {
        toastr.error("Coupon cannot be more than subtotal!");

        // Reset coupon values and UI
        $(".couponPercentageShow").text("(0%)");
        $(".couponAmountShow").text(currencyFormat("0"));
        $(".couponPercentageVal").val(0);
        $(".couponAmountVal").val(0);
        $(".coupondiscount").val("");
        $(".couponType").val("");
        $(".couponId").val("");
        $("#crossBtn").addClass("d-none");
        $("#couponModal").modal("hide");
        return; // Stop here, don't apply
    }

    let id = $(this).data("id");
    let discount = parseFloat($(this).data("discount"));
    let type = $(this).data("type"); // "percent" or "flat"

    $(".couponId").val(id);
    $(".coupondiscount").val(discount);
    $(".couponType").val(type);

    $("body").focus();
    $("#couponModal").modal("hide");
    $("#crossBtn").removeClass("d-none");

    //Trigger recalculation after applying coupon
    calTotalAmount();
});

$(document).on("click", ".crossBtnRemove", function () {
    $(".couponPercentageShow").text("(0%)");
    $(".couponAmountShow").text("0");
    $("#coupondiscount").val("");
    $("#couponType").val("");

    $(this).addClass("d-none");

    //Trigger recalculation after removing coupon
    calTotalAmount();
});

//calculation part

// Trigger calculation whenever Discount, or Receive Amount fields change
$(".discount_amount, #receive_amount, .tip, .deliveryCharge").on(
    "input",
    function () {
        calTotalAmount();
    }
);

// Function to calculate
function calTotalAmount() {
    let subtotal = 0;

    // Calculate subtotal from cart list using qty * price
    if ($("#cart-list").find("tr.product-cart-tr").length > 0) {
        $("#cart-list tr.product-cart-tr").each(function () {
            let qty = getNumericValue($(this).find(".cart-qty").val()) || 0;
            let price =
                getNumericValue($(this).find(".cart-price").text()) || 0;
            let row_subtotal = qty * price;
            subtotal += row_subtotal;
        });
    }
    $("#sub_total").text(currencyFormat(subtotal));
    //vat
    let vatRate = parseFloat($(".vatOnSale").data("vat-sale")) || 0;
    let vatAmount = subtotal * (vatRate / 100);
    $(".vatAmountValue").text(currencyFormat(vatAmount.toFixed(2)));
    $(".vatAmountValue").val(formattedAmount(vatAmount, 2));
    //Tip
    let tip = parseFloat($(".tip").val()) || 0;
    let deliveryAmount = parseFloat($(".deliveryCharge").val()) || 0;

    //Discount
    let discount_input = parseFloat($(".discount_amount").val()) || 0;
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
        $(".discount_amount").val(subtotal);
    }
    $(".discountPercentageShow").text(formattedAmount(discount_percent, 2));
    $(".discountPercentageShow").val(formattedAmount(discount_percent, 2));

    //Counpon
    let coupon_type = $("#couponType").val();
    let coupon_value = parseFloat($("#coupondiscount").val()) || 0;

    let coupon_amount = 0;
    let coupon_percent = 0;

    if (coupon_type) {
        if (coupon_type === "percentage") {
            coupon_percent = coupon_value;
            coupon_amount = (subtotal * coupon_value) / 100;
        } else if (coupon_type === "flat") {
            coupon_amount = coupon_value;
            coupon_percent = ((coupon_value / subtotal) * 100).toFixed(2);
        }
    }

    $(".couponPercentageShow").text(
        "(" + formattedAmount(coupon_percent, 2) + "%)"
    );
    $(".couponAmountShow").text(currencyFormat(coupon_amount.toFixed(2)));

    //for data store value pass
    $(".couponPercentageVal").val(formattedAmount(coupon_percent, 2));
    $(".couponAmountVal").val(formattedAmount(coupon_amount, 2));

    // Total Amount
    let total_amount = subtotal - discount_amount - coupon_amount + vatAmount + tip + deliveryAmount;
    $(".totalAmmount").val(formattedAmount(total_amount, 2));
    $(".totalAmmount").text(currencyFormat(total_amount.toFixed(2)));

    // Receive Amount
    let receive_amount = getNumericValue($("#receive_amount").val()) || 0;
    if (receive_amount < 0) {
        toastr.error("Receive amount cannot be less than 0!");
        receive_amount = 0;
        $("#receive_amount").val(formattedAmount(receive_amount, 2));
    }

    // Hide change amount initially
    $("#change_amount").closest(".custom-form-group").hide();

    // Change Amount
    let change_amount =
        receive_amount > total_amount ? receive_amount - total_amount : 0;
    $("#change_amount").val(formattedAmount(change_amount, 2));

    // Due Amount
    let due_amount =
        total_amount > receive_amount ? total_amount - receive_amount : 0;
    $("#due_amount").val(formattedAmount(due_amount, 2));

    // Show conditionally
    if (receive_amount > total_amount) {
        $("#due_amount").closest(".custom-form-group").hide();
        $("#change_amount").closest(".custom-form-group").show();
    } else if (receive_amount < total_amount) {
        $("#due_amount").closest(".custom-form-group").show();
        $("#change_amount").closest(".custom-form-group").hide();
    } else {
        $("#due_amount").closest(".custom-form-group").show();
        $("#change_amount").closest(".custom-form-group").hide();
    }
}

calTotalAmount();

$(document).on("click", ".cancel-sale-btn", function (e) {
    e.preventDefault();

    let route = $("#clear-cart").val(); // hidden input route
    clearCart("sale", route);

    // reset inputs
    $("#receive_amount").val("");
    $("#due_amount").val("");
    $(".null_by_reset").val("");
    $(".customer-select").val("");
    $(".tip").val("");
    $(".discountPercentageShow").text("0");
    $(".discountPercentageShow").val("");
    $(".discount_amount").val("");
    $(".couponPercentageShow").text("(0%)");
    $(".couponAmountShow").text(currencyFormat("0"));
    $(".couponPercentageVal").val(0);
    $(".couponAmountVal").val(0);
    $(".coupondiscount").val("");
    $(".couponType").val("");
    $(".couponId").val("");
    $("#crossBtn").addClass("d-none");
});

document.addEventListener("DOMContentLoaded", () => {
    const dropdown = document.querySelector(".custom-dropdown-2");
    const toggle = dropdown.querySelector(".dropdown-toggle");
    const items = dropdown.querySelectorAll(".dropdown-item");
    toggle.addEventListener("click", () => {
        dropdown.classList.toggle("active");
    });
    items.forEach((item) => {
        item.addEventListener("click", () => {
            const textOnly = item.childNodes[0].textContent.trim();
            toggle.textContent = textOnly;
            dropdown.classList.remove("active");
        });
    });
});

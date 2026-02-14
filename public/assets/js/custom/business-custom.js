"use strict";

$(".common-validation-modal").on("shown.bs.modal", function () {
    $(this)
        .find("form.ajaxform_instant_reload")
        .each(function () {
            $(this).validate();
        });
});

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

// Category edit form
$(document).on("click", ".category-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("category-name");

    $("#category_name").val(name);
    $(".categoryUpdateForm").attr("action", url);
});

// Item edit form
$(document).on("click", ".item-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");

    $("#item_name").val(name);

    $(".itemUpdateForm").attr("action", url);
});

// Unit edit form
$(document).on("click", ".units-edit-btn", function () {
    var url = $(this).data("url");
    var unitName = $(this).data("units-name");

    $("#unit_view_name").val(unitName);
    $(".unitUpdateForm").attr("action", url);
});

// Payment-type edit form
$(document).on("click", ".payment-types-edit-btn", function () {
    var url = $(this).data("url");
    var PaymentTypeName = $(this).data("payment-types-name");

    $("#PaymentTypeName").val(PaymentTypeName);
    $(".paymentTypeUpdateForm").attr("action", url);
});

// Menu edit form
$(document).on("click", ".menu-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");

    $("#menu_name").val(name);

    $(".menuUpdateForm").attr("action", url);
});

// Income category edit form
$(document).on("click", ".income-categories-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");

    $("#income_categories_name").val(name);

    $(".incomeCategoryUpdateForm").attr("action", url);
});

// Income edit form
$(document).on("click", ".incomes-edit-btn", function () {
    var url = $(this).data("url");
    var income_category_id = $(this).data("income-category-id");
    var incomeAmount = $(this).data("income-amount");
    var incomeFor = $(this).data("income-for");
    var incomePaymentTypeId = $(this).data("income-payment-type-id");
    var incomeReferenceNo = $(this).data("income-reference-no");
    var incomedate = $(this).data("income-date-update");
    var incomenote = $(this).data("income-note");

    $("#income_categoryId").val(income_category_id);
    $("#inc_price").val(incomeAmount);
    $("#inc_for").val(incomeFor);
    $("#inc_paymentType").val(incomePaymentTypeId);
    $("#inc_paymentType").val(incomePaymentTypeId || incomePaymentType);
    $("#incomeReferenceNo").val(incomeReferenceNo);
    $("#inc_date_update").val(incomedate);
    $("#inc_note").val(incomenote);

    $(".incomeUpdateForm").attr("action", url);
});

// Expense category edit form
$(document).on("click", ".expense-categories-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");

    $("#expense_categories_name").val(name);

    $(".expenseCategoryUpdateForm").attr("action", url);
});

// Expense edit form
$(document).on("click", ".expense-edit-btn", function () {
    var url = $(this).data("url");
    var expenseCategoryId = $(this).data("expense-category-id");
    var expenseAmount = $(this).data("expense-amount");
    var expensePaymentTypeId = $(this).data("expense-payment-type-id");
    var expenseReferenceNo = $(this).data("expense-reference-no");
    var expenseFor = $(this).data("expense-for");
    var expenseDate = $(this).data("expense-date");
    var expenseNote = $(this).data("expense-note");

    $("#expenseCategoryId").val(expenseCategoryId);
    $("#expense_amount").val(expenseAmount);
    $("#expensePaymentType").val(expensePaymentTypeId);
    $("#refeNo").val(expenseReferenceNo);
    $("#expe_for").val(expenseFor);
    $("#edit_date_expe").val(expenseDate);
    $("#expenote").val(expenseNote);

    $(".expenseUpdateForm").attr("action", url);
});

// Coupon edit form
$(document).on("click", ".coupon-edit-btn", function () {
    var url = $(this).data("url");
    var image = $(this).data("image");
    var name = $(this).data("name");
    var code = $(this).data("code");
    var discount = $(this).data("discount");
    var discountType = $(this).data("discount-type");
    var startDate = $(this).data("start-date");
    var endDate = $(this).data("end-date");
    var desc = $(this).data("desc");

    $("#cpn_img").attr("src", image);
    $("#cpn_name").val(name);
    $("#cpn_code").val(code);
    $("#cpn_discount").val(discount);
    $("#cpn_disc_type").val(discountType);
    $("#cpn_st_date").val(startDate);
    $("#cpn_end_date").val(endDate);
    $("#cpn_desc").val(desc);

    $(".couponUpdateForm").attr("action", url);
});

// Coupon view
$(document).on("click", ".coupon-view-btn", function () {
    $("#coupon_name").text($(this).data("name"));
    $("#coupon_code").text($(this).data("code"));
    $("#coupon_start_date").text($(this).data("start-date"));
    $("#coupon_end_date").text($(this).data("end-date"));
    $("#coupon_discount").text($(this).data("discount"));
    $("#coupon_desc").text($(this).data("desc"));

    var status = $("#coupon_status");
    var endDate = new Date($(this).data("end-date"));
    var today = new Date();

    if (endDate < today) {
        status.text("Expired").addClass("text-danger");
    } else {
        status.text("Active").addClass("text-success");
    }

    $("#coupon_image").attr("src", $(this).data("image"));
});

// Staff edit form
$(document).on("click", ".staff-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");
    var email = $(this).data("email");
    var phone = $(this).data("phone");
    var designation = $(this).data("designation");
    var address = $(this).data("address");

    $("#stf_name").val(name);
    $("#stf_email").val(email);
    $("#stf_phone").val(phone);
    $("#stf_designation").val(designation);
    $("#stf_address").val(address);

    $(".staffUpdateForm").attr("action", url);
});

// Staff view
$(document).on("click", ".staff-view-btn", function () {
    $("#staff_name").text($(this).data("name"));
    $("#staff_email").text($(this).data("email"));
    $("#staff_phone").text($(this).data("phone"));
    $("#staff_desig").text($(this).data("designation"));
    $("#staff_address").text($(this).data("address"));
});

// Vat select
$(document).ready(function () {
    $("#sub_vat").select2({
        placeholder: "Select vats",
        width: "100%",
    });
});

//Vat start
$(document).on("click", ".vat-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("vat-name");
    var rate = $(this).data("rate");
    var vat_sale = $(this).data("vat-sale");
    var status = $(this).data("vat-status");

    $("#vat_name").val(name);
    $("#vat_rate").val(rate);
    $("#vat_sale").prop("checked", vat_sale === "checked");
    $("#vat_status").val(status ? "1" : "0");

    $(".updateVatForm").attr("action", url);
});
//Vat End

// Collects Due Start
$("#invoiceSelect").on("change", function () {
    const selectedOption = $(this).find("option:selected");
    const dueAmount = selectedOption.data("due-amount");
    const openingDue = selectedOption.data("opening-due");

    if (!selectedOption.val()) {
        $("#totalAmount").val(openingDue);
        $("#dueAmount").val(openingDue);
    } else {
        $("#totalAmount").val(dueAmount);
        $("#dueAmount").val(dueAmount);
    }

    calculateDueChange();
});

$("#paidAmount").on("input", function () {
    calculateDueChange();
});
function calculateDueChange() {
    const payingAmount = parseFloat($("#paidAmount").val()) || 0;
    const totalAmount = parseFloat($("#totalAmount").val()) || 0;

    if (payingAmount > totalAmount) {
        toastr.error("Cannot pay more than due.");
    }

    const updatedDueAmount = totalAmount - payingAmount;
    $("#dueAmount").val(updatedDueAmount >= 0 ? updatedDueAmount : 0);
}
// Collects Due End

// Table edit form
$(document).on("click", ".tables-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");
    var capacity = $(this).data("capacity");
    var status = $(this).data("status");

    $("#table_name").val(name);
    $("#table_capacity").val(capacity);
    $("#table_status").val(status);

    $(".tableUpdateForm").attr("action", url);
});

// Modifier edit form
$(document).on("click", ".modifiers-edit-btn", function () {
    var url = $(this).data("url");
    var productId = $(this).data("product-id");
    var groupId = $(this).data("group-id");
    var required = $(this).data("required");
    var multiple = $(this).data("multiple");

    $("#modif_product_id").val(productId);
    $("#modif_group_id").val(groupId);
    $("#modif_is_multiple").prop("checked", multiple === "checked");
    $("#modif_is_required").prop("checked", required === "checked");

    $(".modifierUpdateForm").attr("action", url);
});


$(document).on("click", ".add-address-row", function () {
    let html = `
    <div class="row address-row-items">
        <div class="col-sm-5">
            <label for="">Name</label>
            <input type="text" name="delivery_name[]" value="" class="form-control" placeholder="Enter your name">
        </div>
        <div class="col-sm-5">
            <label for="">Phone</label>
            <input type="text" name="delivery_phone[]" value="" class="form-control" placeholder="Enter your phone">
        </div>
        <div class="col-sm-5">
            <label for="">Address</label>
            <input type="text" name="delivery_address[]" value="" class="form-control" placeholder="Enter delivery address">
        </div>

        <div class="col-sm-2 align-self-center mt-3">
            <button type="button" class="btn text-danger trash remove-btn-features mt-3"><i class="fas fa-trash"></i></button>
        </div>
    </div>
    `;
    $(".address-manual-rows").append(html);
});

$(document).on("click", ".remove-btn-features", function () {
    var $row = $(this).closest(".address-row-items");
    $row.remove();
});

// Modifier group products select
$(document).ready(function () {
    $("#product_ids").select2({
        width: "100%",
        placeholder: "Select products",
    });
});

// Modifier Group dynamic options start
$(document).ready(function () {
    var optionCount = 0;

    $(document).on("click", ".add-option", function () {
        var uniqueId = "check_" + optionCount;
        optionCount++;

        var html = `
        <div class="option-row row mb-2">
            <div class="col-md-6 mb-2">
                <label>Name</label>
                <input type="text" name="option_name[]" class="form-control"
                    placeholder="Enter Name">
            </div>
            <div class="col-md-5 mb-2">
                <label>Price</label>
                <input type="number" name="option_price[]" class="form-control"
                    placeholder="Enter Price">
            </div>
            <div class="col-md-1 mt-md-4 d-flex align-items-center justify-content-end">
                <button type="button" class="btn dynamic-delete-btn remove-option">
                    <svg class="text-danger" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                        class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </button>
            </div>
            <div class="row col-md-12 mx-auto">
                <div class="form-check d-flex align-items-center">
                    <input type="checkbox" id="${uniqueId}" name="is_available[]" value="1"
                        class="form-check-input me-2" checked>
                    <label for="${uniqueId}" class="form-check-label">Is Available</label>
                </div>
            </div>
        </div>`;
        $(".modifier-options").append(html);
    });

    $(document).on("click", ".remove-option", function () {
        $(this).closest(".option-row").remove();
    });
});
// Modifier Group dynamic options end

$(document).on("click", ".parties-view-btn", function () {
    $("#parties_name").text($(this).data("name"));
    $("#parties_phone").text($(this).data("phone"));
    $("#parties_email").text($(this).data("email"));
    $("#parties_type").text($(this).data("type"));
    $("#parties_address").text($(this).data("address"));
    $("#parties_due").text($(this).data("due"));

    var party_image = $(this).data("image");
    $("#parties_image").attr("src", party_image);

    // Clear old delivery addresses
    $("#delivery_addresses_container").empty();

    // Parse delivery addresses JSON
    var addresses = $(this).data("delivery-addresses");
    if (addresses && Array.isArray(addresses)) {
        addresses.forEach(function (addr) {
            var card = `
                 <div class="address-container mt-2">
                     <p class="add-name">${addr.name}</p>
                     <p class="add-phone">${addr.phone}</p>
                     <p class="mt-1 text-muted ">${addr.address}</p>
                 </div>`;
            $("#delivery_addresses_container").append(card);
        });
    }
});

/** Report Filter: Start **/

// Handle Custom Date Selection
$(".custom-days").on("change", function () {
    let selected = $(this).val();
    let dateFilters = $(".date-filters");

    // Show or hide the date filters based on selection
    if (selected === "custom_date") {
        dateFilters.removeClass("d-none");
    } else {
        dateFilters.addClass("d-none");
    }

    // Trigger the form submission to apply the filters
    $(".report-filter-form").trigger("input");
});
// Report Filter Form Submission
$(".report-filter-form").on("input change", function (e) {
    e.preventDefault();
    let form = $(this);
    let table = form.attr("table");

    $.ajax({
        type: "POST",
        url: form.attr("action"),
        data: new FormData(this),
        dataType: "json",
        contentType: false,
        cache: false,
        processData: false,
        success: function (res) {
            $(table).html(res.data);
            if (res.total_sale !== undefined) {
                $("#total_sale").text(res.total_sale);
            }
            if (res.total_sale_return !== undefined) {
                $("#total_sale_return").text(res.total_sale_return);
            }
            if (res.total_purchase !== undefined) {
                $("#total_purchase").text(res.total_purchase);
            }
            if (res.total_purchase_return !== undefined) {
                $("#total_purchase_return").text(res.total_purchase_return);
            }
            if (res.total_income !== undefined) {
                $("#total_income").text(res.total_income);
            }
            if (res.total_expense !== undefined) {
                $("#total_expense").text(res.total_expense);
            }
            if (res.total_loss !== undefined) {
                $("#total_loss").text(res.total_loss);
            }
            if (res.total_profit !== undefined) {
                $("#total_profit").text(res.total_profit);
            }
            if (res.total_sale_count !== undefined) {
                $("#total_sale_count").text(res.total_sale_count);
            }
            if (res.total_due !== undefined) {
                $("#total_due").text(res.total_due);
            }
            if (res.total_paid !== undefined) {
                $("#total_paid").text(res.total_paid);
            }

            //

            if (res.opening_stock_by_purchase !== undefined) {
                $("#opening_stock_by_purchase").text(
                    res.opening_stock_by_purchase
                );
            }
            if (res.closing_stock_by_purchase !== undefined) {
                $("#closing_stock_by_purchase").text(
                    res.closing_stock_by_purchase
                );
            }
            if (res.total_purchase_price !== undefined) {
                $("#total_purchase_price").text(res.total_purchase_price);
            }
            if (res.total_purchase_shipping_charge !== undefined) {
                $("#total_purchase_shipping_charge").text(
                    res.total_purchase_shipping_charge
                );
            }
            if (res.total_purchase_discount !== undefined) {
                $("#total_purchase_discount").text(res.total_purchase_discount);
            }
            if (res.all_purchase_return !== undefined) {
                $("#all_purchase_return").text(res.all_purchase_return);
            }
            if (res.all_sale_return !== undefined) {
                $("#all_sale_return").text(res.all_sale_return);
            }
            if (res.opening_stock_by_sale !== undefined) {
                $("#opening_stock_by_sale").text(res.opening_stock_by_sale);
            }
            if (res.closing_stock_by_sale !== undefined) {
                $("#closing_stock_by_sale").text(res.closing_stock_by_sale);
            }
            if (res.total_sale_price !== undefined) {
                $("#total_sale_price").text(res.total_sale_price);
            }
            if (res.total_sale_shipping_charge !== undefined) {
                $("#total_sale_shipping_charge").text(
                    res.total_sale_shipping_charge
                );
            }
            if (res.total_sale_discount !== undefined) {
                $("#total_sale_discount").text(res.total_sale_discount);
            }
            if (res.total_sale_rounding_off !== undefined) {
                $("#total_sale_rounding_off").text(res.total_sale_rounding_off);
            }
        },
    });
});
/** Report Filter: End **/

// Tax Report Tab Start
function showTab(tabId) {
    $(".tab-item").removeClass("active");
    $(".tab-content").removeClass("active");

    $("#" + tabId).addClass("active");
    $(`[onclick="showTab('${tabId}')"]`).addClass("active");

    let type = tabId === "sales" ? "sales" : "purchases";

    let pdfHref = $("#pdfExportLink").attr("href");
    let csvHref = $("#csvExportLink").attr("href");
    let excelHref = $("#excelExportLink").attr("href");

    if (pdfHref) {
        let pdfBaseUrl = pdfHref.split("?")[0];
        $("#pdfExportLink").attr("href", `${pdfBaseUrl}?type=${type}`);
    }
    if (csvHref) {
        let csvBaseUrl = csvHref.split("?")[0];
        $("#csvExportLink").attr("href", `${csvBaseUrl}?type=${type}`);
    }
    if (excelHref) {
        let excelBaseUrl = excelHref.split("?")[0];
        $("#excelExportLink").attr("href", `${excelBaseUrl}?type=${type}`);
    }
}

$(document).ready(function () {
    showTab("sales");
});
// Tax Report Tab End

$(document).ready(function () {
    $("#modifier_group_ids").select2({
        width: "100%",
        placeholder: "Select products",
    });
});

$(document).ready(function () {
    function toggleFields() {
        if ($("#single").is(":checked")) {
            $(".singlePriceFiled").removeClass("d-none");
            $(".variationPriceFiled").addClass("d-none");
        } else {
            $(".singlePriceFiled").addClass("d-none");
            $(".variationPriceFiled").removeClass("d-none");
        }
    }

    // Run on page load
    toggleFields();

    // Run on radio button change
    $("input[name='price_type']").on("change", toggleFields);
});

$(document).on("click", ".variationPrice", function () {
    let html = `
        <div class="variation-row row mb-2">
        <div class="col-md-6 mb-2">
            <label>Name</label>
            <input type="text" name="variation_names[]" class="form-control"
                placeholder="Enter Name">
        </div>
        <div class="col-md-5 mb-2">
            <label>Price</label>
            <input type="number" name="variation_prices[]" class="form-control"
                placeholder="Enter Price">
        </div>
        <div class="col-md-1 mt-md-4 d-flex align-items-center">
            <button type="button" class="btn dynamic-delete-btn remove-btn-variation">
                <svg class="text-danger" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                    class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            </button>
        </div>
      </div>

    `;
    $(".duplicateVariation").append(html);
});

$(document).on("click", ".remove-btn-variation", function () {
    var $row = $(this).closest(".variation-row");
    $row.remove();
});

// Settings Tab Start
$(document).ready(function () {
    var hash = window.location.hash;

    if (hash) {
        $(".nav-link.settings-link, .tab-pane").removeClass("active show");

        $('.nav-link.settings-link[data-bs-target="' + hash + '"]').addClass(
            "active"
        );

        $(hash).addClass("active show");
    } else {
        $(".nav-link.settings-link:first").addClass("active");
        $(".tab-pane:first").addClass("active show");
    }
});
// Settings Tab End

$(document).on("click", ".variation-details-btn", function () {
    // Set product info
    $("#name").text($(this).data("name"));
    $("#category-food").text(
        $(this).data("category") + " - " + $(this).data("food-type")
    );
    $("#description").text($(this).data("description"));

    var image = $(this).data("image");
    $("#image").attr("src", image);

    // Clear old variations
    $("#variation-list").empty();

    var baseDeleteUrl = $("#variationDelete").val();
    var baseUpdateUrl = $("#variationUpdate").val();

    // Parse variations
    var variations = $(this).data("variation");

    if (variations && Array.isArray(variations)) {
        variations.forEach(function (variation) {
            let deleteUrl = baseDeleteUrl.replace(":id", variation.id);
            let updateUrl = baseUpdateUrl.replace(":id", variation.id);
            var card = `
        <div class="item-variation-box d-flex justify-content-between align-items-center">
                <div>
            <span class="d-block">${variation.name}</span>
            <span class="fw-bold">${currencyFormat(variation.price)}</span>
          </div>
         <div class="icon-buttons">
        <!-- Edit Button -->
        <a href="#VariationsDeleteModal" class="variation-edit-btn action-btn edit" data-bs-toggle="modal"
                data-url="${updateUrl}"
                data-name="${variation.name}"
                data-price="${variation.price}">
            <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M11.6479 19.4575C11.6479 18.9869 12.0294 18.6055 12.5 18.6055H17.612C18.0825 18.6055 18.4641 18.9869 18.4641 19.4575C18.4641 19.9281 18.0825 20.3095 17.612 20.3095H12.5C12.0294 20.3095 11.6479 19.9281 11.6479 19.4575Z"
                    fill="#979797" />
                <path
                    d="M18.5061 3.93895C17.8507 3.6141 17.081 3.6141 16.4255 3.93895C16.0785 4.11088 15.7751 4.41482 15.3831 4.80746L14.6299 5.5606L18.9418 9.87244L19.6949 9.11929C20.0875 8.7273 20.3915 8.42385 20.5634 8.0769C20.8883 7.42137 20.8883 6.65174 20.5634 5.99621C20.3915 5.64926 20.0875 5.3458 19.6949 4.95382L19.5486 4.80746C19.1566 4.41482 18.8531 4.11088 18.5061 3.93895Z"
                    fill="#979797" />
                <path
                    d="M18.0381 10.7767L13.7264 6.46484L5.30538 14.8857C4.88409 15.3063 4.55068 15.639 4.37123 16.0722C4.19179 16.5054 4.19224 16.9765 4.19281 17.5717L4.19288 19.671C4.19288 20.024 4.47897 20.3101 4.83189 20.3101L6.93125 20.3101C7.52647 20.3107 7.99755 20.3112 8.43076 20.1317C8.86396 19.9523 9.19674 19.6189 9.61723 19.1976L18.0381 10.7767Z"
                    fill="#979797" />
            </svg>
        </a>
        <!-- Delete Button -->
        <a href="${deleteUrl}" class="confirm-action action-btn delete" data-method="DELETE">
            <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M18.6273 15.5714C18.5653 16.5711 18.5161 17.3644 18.4154 17.998C18.3119 18.6479 18.1464 19.1891 17.8154 19.6625C17.5125 20.0955 17.1226 20.4611 16.6704 20.7357C16.1761 21.036 15.6242 21.1673 14.9672 21.2301L10.0171 21.23C9.35943 21.167 8.80689 21.0355 8.31224 20.7347C7.85974 20.4595 7.46969 20.0934 7.16693 19.6596C6.83598 19.1854 6.671 18.6435 6.56838 17.9927C6.46832 17.3581 6.42023 16.5636 6.35961 15.5625L5.83337 6.87109H19.1667L18.6273 15.5714ZM10.4798 17.4097C10.1451 17.4097 9.87378 17.1419 9.87378 16.8114V12.0251C9.87378 11.6947 10.1451 11.4268 10.4798 11.4268C10.8146 11.4268 11.0859 11.6947 11.0859 12.0251V16.8114C11.0859 17.1419 10.8146 17.4097 10.4798 17.4097ZM15.1263 12.0251C15.1263 11.6947 14.8549 11.4268 14.5202 11.4268C14.1855 11.4268 13.9142 11.6947 13.9142 12.0251V16.8114C13.9142 17.1419 14.1855 17.4097 14.5202 17.4097C14.8549 17.4097 15.1263 17.1419 15.1263 16.8114V12.0251Z"
                    fill="#979797" />
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M13.6746 2.79397C14.1673 2.83166 14.6303 2.96028 15.0279 3.21397C15.322 3.4016 15.5262 3.63127 15.7008 3.88001C15.8625 4.11051 16.0253 4.39789 16.21 4.72389L16.5821 5.38044H20.3462C20.8277 5.38044 21.218 5.71436 21.218 6.12627C21.218 6.53818 20.8277 6.8721 20.3462 6.8721C15.1153 6.8721 9.88483 6.8721 4.6539 6.8721C4.17242 6.8721 3.7821 6.53818 3.7821 6.12627C3.7821 5.71436 4.17242 5.38044 4.6539 5.38044H8.49831L8.80849 4.7983C8.98857 4.46029 9.14716 4.1626 9.30646 3.92373C9.4783 3.66606 9.68172 3.42762 9.97935 3.23228C10.3819 2.96811 10.8542 2.83419 11.3579 2.79495C11.7371 2.76542 12.1194 2.76948 12.5001 2.77001C12.9454 2.77062 13.3457 2.7688 13.6746 2.79397ZM10.4145 5.38044H14.6444C14.4468 5.03185 14.3204 4.81091 14.2058 4.64767C14.0382 4.40876 13.8373 4.30403 13.5193 4.27969C13.2932 4.2624 12.9986 4.2617 12.5301 4.2617C12.0499 4.2617 11.7477 4.26242 11.516 4.28046C11.1898 4.30586 10.9865 4.41458 10.8209 4.66293C10.7124 4.82568 10.5942 5.04353 10.4145 5.38044Z"
                    fill="#979797" />
            </svg>
        </a>
        </div>
        </div>
                `;
            $("#variation-list").append(card);
        });
    } else {
        $("#variation-list").append(
            `<div class="text-muted">No variations available</div>`
        );
    }
});

$(document).on("click", ".variation-edit-btn", function () {
    var url = $(this).data("url");
    var name = $(this).data("name");
    var price = $(this).data("price");

    $("#editVariationName").val(name);
    $("#editVariationPrice").val(price);

    $(".variationUpdateForm").attr("action", url);
});

$(document).on("click", ".product-view-btn", function () {
    $("#view_name").text($(this).data("view-name"));
    $("#view_category_food").text(
        $(this).data("view-category") + " - " + $(this).data("view-food-type")
    );
    $("#view_description").text($(this).data("view-description"));
    $("#view_preparation_time").text(
        $(this).data("view-preparation-time") + " min"
    );
    $("#view_menu").text($(this).data("view-menu"));

    var image = $(this).data("view-image");
    $("#view_image").attr("src", image);

    // Clear old variations
    $("#view-variation").empty();
    // Parse variations
    var variations = $(this).data("view-variations");
    if (variations && Array.isArray(variations)) {
        variations.forEach(function (variation) {
            var card = `
                    <div class="variation d-flex justify-content-between align-items-center">
                        <span>${variation.name}</span>
                        <span>${currencyFormat(variation.price)}</span>
                    </div>`;
            $("#view-variation").append(card);
        });
    } else {
        $("#view-variation").append(
            `<div class="text-muted">No variations available</div>`
        );
    }

    // Clear existing content
    $("#view-modifier-group").empty();
    var modifier_groups = $(this).data("view-modifier-group");

    if (modifier_groups && Array.isArray(modifier_groups)) {
        modifier_groups.forEach(function (group, index) {
            // Unique ID for each group's options
            var groupId = "modifier-options-" + index;

            // Add group title with clickable SVG
            var groupCard = `
            <div class="modifier-group mb-2">
                <h6 class="fw-bold d-flex align-items-center justify-content-between">
                    <span class="group-title svg-toggle" style="cursor:pointer">
                        <span class="orange">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_3957_50781)">
                                    <path d="M10.0003 18.3346C14.6027 18.3346 18.3337 14.6037 18.3337 10.0013C18.3337 5.39893 14.6027 1.66797 10.0003 1.66797C5.39795 1.66797 1.66699 5.39893 1.66699 10.0013C1.66699 14.6037 5.39795 18.3346 10.0003 18.3346Z" stroke="#FC8019" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6.66699 10H13.3337" stroke="#FC8019" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_3957_50781">
                                        <rect width="20" height="20" fill="white"/>
                                    </clipPath>
                                </defs>
                            </svg>
                        </span>
                        ${group.name}
                    </span>
                </h6>
                <div class="group-options" id="${groupId}" style="display: ${
                index === 0 ? "block" : "none"
            };">
                </div>
            </div>
            `;

            $("#view-modifier-group").append(groupCard);

            // Append options inside the group's div
            if (group.options && Array.isArray(group.options)) {
                group.options.forEach(function (option) {
                    var optionCard = `
                        <div class="d-flex justify-content-between px-3 py-1">
                            <span>${option.name}</span>
                            <span>${currencyFormat(option.price)}</span>
                        </div>
                    `;
                    $("#" + groupId).append(optionCard);
                });
            }
        });

        // Toggle group options on SVG click
        $(document).on("click", ".svg-toggle", function () {
            var groupDiv = $(this)
                .closest(".modifier-group")
                .find(".group-options");
            groupDiv.slideToggle();
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const searchBtn = document.getElementById("searchBtn");
    const searchIcon = document.querySelector(".initial-search-icon");

    // Check if elements exist
    if (!searchBtn || !searchIcon) return;

    const input = searchBtn.querySelector("input");
    if (!input) return;

    // Button click
    searchBtn.addEventListener("click", () => {
        searchBtn.classList.add("active");
        input.focus();
    });

    // Click outside
    document.addEventListener("click", (e) => {
        if (!searchBtn.contains(e.target) && !searchIcon.contains(e.target)) {
            searchBtn.classList.remove("active");
            input.value = "";
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const searchBtn = document.getElementById("searchBtn-2");
    const searchIcon = document.querySelector(".initial-search-icon");

    // Check if elements exist
    if (!searchBtn || !searchIcon) return;

    const input = searchBtn.querySelector("input");
    if (!input) return;

    // Button click
    searchBtn.addEventListener("click", () => {
        searchBtn.classList.add("active");
        input.focus();
    });

    // Click outside
    document.addEventListener("click", (e) => {
        if (!searchBtn.contains(e.target) && !searchIcon.contains(e.target)) {
            searchBtn.classList.remove("active");
            input.value = "";
        }
    });
});

$(document).ready(function () {
    $(document).on("change", ".custom-select-status", function () {
        console.log("ok");
        var url = $(this).data("url");
        var status = $(this).val();
        var saleId = $(this).data("sale-id");
        var $select = $(this);

        // Optional: disable select while processing
        $select.prop("disabled", true);

        $.ajax({
            url: url,
            type: "POST",
            data: {
                sale_id: saleId,
                status: status,
            },
            success: function (response) {
                toastr.success(response.message);
                if (response.success) {
                    if (status === "completed") {
                        $select.find('option[value="pending"]').remove();
                        $select.val("completed"); // ensure completed is selected
                    } else {
                        // pending selected (if allowed)
                        $select.val("pending");
                    }
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseText);
            },
            complete: function () {
                $select.prop("disabled", false);
            },
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const subVat = document.getElementById("choice_sub_vat");
    if (subVat) {
        new Choices(subVat, {
            removeItemButton: true,
            searchPlaceholderValue: "Search VAT...",
            placeholder: true,
            placeholderValue: "Select VAT",
        });
    }
});
document.addEventListener("DOMContentLoaded", function () {
    const tagProduct = document.getElementById("choice_tag_product");
    if (tagProduct) {
        new Choices(tagProduct, {
            removeItemButton: true,
            searchPlaceholderValue: "Search Products ...",
            placeholder: true,
            placeholderValue: "Select Items ",
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll("[title]")
    );
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.querySelector(".sidebar-opner");
    const sidebarPlan = document.querySelector(".lg-sub-plan");
    const subPlan = document.querySelector(".sub-plan");

    toggleBtn.addEventListener("click", function () {
        if (sidebarPlan.style.display === "none") {
            sidebarPlan.style.display = "block";
            subPlan.style.display = "none";
        } else {
            sidebarPlan.style.display = "none";
            subPlan.style.display = "block";
        }
    });
});

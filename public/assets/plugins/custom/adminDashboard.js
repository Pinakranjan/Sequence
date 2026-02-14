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

function renderArrow(selector, direction) {
    let up_icon = `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M8 12.6667V3.33333" stroke="#00932C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M3.33334 7.99999L8 3.33333L12.6667 7.99999" stroke="#00932C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`;
    let down_icon = `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M8 3.33334V12.6667" stroke="#FF3B30" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M3.33331 8.00001L7.99998 12.6667L12.6666 8.00001" stroke="#FF3B30" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`;

    let iconHtml = "";
    if (direction === "up") {
        iconHtml = up_icon;
    } else if (direction === "down") {
        iconHtml = down_icon;
    } else {
        iconHtml = up_icon;
    }
    $(selector).html(iconHtml);
}

function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1).replace(/\.0$/, "") + "M";
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1).replace(/\.0$/, "") + "k";
    }
    return num;
}

getDashboardData();
function getDashboardData() {
    var url = $("#get-dashboard").val();
    $.ajax({
        type: "GET",
        url: url,
        dataType: "json",
        success: function (res) {
            $("#total_businesses").text(formatNumber(res.total_businesses));
            $("#this_month_total_businesses").text(
                formatNumber(res.this_month_total_businesses)
            );
            $("#expired_businesses").text(formatNumber(res.expired_businesses));
            $("#this_month_expired_businesses").text(
                formatNumber(res.this_month_expired_businesses)
            );
            $("#paid_users").text(formatNumber(res.paid_users));
            $("#this_month_paid_users").text(
                formatNumber(res.this_month_paid_users)
            );
            $("#free_users").text(formatNumber(res.free_users));
            $("#this_month_free_users").text(
                formatNumber(res.this_month_free_users)
            );

            // Render arrows
            renderArrow("#total_business_arrow", res.total_business_arrow);
            renderArrow("#expired_arrow", res.expired_arrow);
            renderArrow("#paid_arrow", res.paid_arrow);
            renderArrow("#free_arrow", res.free_arrow);
        },
    });
}

$(document).ready(function () {
    getYearlySubscriptions();
    bestPlanSubscribes();
});

$(".plan-overview-yearly").on("change", function () {
    let year = $(this).val();
    bestPlanSubscribes(year);
});

let userOverView = false;

function bestPlanSubscribes(year = new Date().getFullYear()) {
    if (userOverView) {
        userOverView.destroy();
    }

    let url = $("#get-plans-overview").val();
    $.ajax({
        url: (url += "?year=" + year),
        type: "GET",
        dataType: "json",

        success: function (res) {
            var labels = [];
            var data = [];
            var colors = ["#00B243", "#FC8019", "#AF52DE", "#0079CE"];
            var backgroundColors = [];
            var borderColors = [];

            if (res.length > 0) {
                res.forEach((planData, index) => {
                    var label = `${planData.plan.subscriptionName}: ${planData.plan_count}`;
                    labels.push(label);

                    var count =
                        planData.plan_count > 0 ? planData.plan_count : 0.1;
                    data.push(count);

                    let planColor = colors[index % colors.length];
                    backgroundColors.push(
                        planData.plan_count > 0 ? planColor : planColor
                    );
                    borderColors.push(planColor);
                });
            } else {
                var defaultPlans = [
                    { subscriptionName: "Free", count: 0 },
                    { subscriptionName: "Standard", count: 0 },
                    { subscriptionName: "Premium", count: 0 },
                ];

                defaultPlans.forEach((plan, index) => {
                    var label = `${plan.subscriptionName}: ${plan.count}`;
                    labels.push(label);

                    var count = plan.count > 0 ? plan.count : 0.1;
                    data.push(count);

                    let planColor = colors[index % colors.length];
                    backgroundColors.push(planColor);
                    borderColors.push(planColor);
                });
            }

            const chartCtx = document
                .getElementById("planChart")
                .getContext("2d");

            userOverView = new Chart(chartCtx, {
                type: "doughnut",
                data: {
                    labels: labels,
                    datasets: [
                        {
                            data: data,
                            backgroundColor: backgroundColors,
                            borderWidth: 0,
                            borderColor: borderColors,
                            borderRadius: 10,
                            spacing: 5,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "80%",
                    plugins: {
                        legend: {
                            position: "bottom",
                            padding: 20,
                            labels: {
                                color: "#333",
                                font: {
                                    size: 14,
                                },
                                usePointStyle: true,
                                pointStyle: "circle",
                                boxWidth: 12,
                                boxHeight: 12,
                                padding: 20,
                            },
                        },
                        tooltip: {
                            backgroundColor: "#ffffff",
                            titleColor: "#000",
                            bodyColor: "#000",
                            borderColor: "#ddd",
                            borderWidth: 1,
                            boxPadding: 6,
                            cornerRadius: 6,
                            titleFont: { size: 12, weight: "bold" },
                            bodyFont: { size: 13 },
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || "";
                                    const value = context.raw;
                                    return `${label}: ${value}`;
                                },
                            },
                        },
                    },
                },
            });
        },
        error: function (xhr, textStatus, errorThrown) {
            console.log("Error fetching user overview data: " + textStatus);
        },
    });
}

$(".income-overview-yearly").on("change", function () {
    let year = $(this).val();
    getYearlySubscriptions(year);
});

function getYearlySubscriptions(year = new Date().getFullYear()) {
    var url = $("#yearly-subscriptions-url").val();
    $.ajax({
        type: "GET",
        url: (url += "?year=" + year),
        dataType: "json",
        success: function (res) {
            var subscriptions = [];
            var totalAmount = 0;

            for (var i = 0; i <= 11; i++) {
                var monthName = getMonthNameFromIndex(i);

                var subscriptionsData = res.find(
                    (item) => item.month === monthName
                );
                var amount = subscriptionsData
                    ? subscriptionsData.total_amount
                    : 0;
                subscriptions[i] = amount;
                totalAmount += amount;
            }

            updateTotalAmountDisplay(totalAmount);

            subscriptionChart(subscriptions);
        },
    });
}

function updateTotalAmountDisplay(amount) {
    let formattedAmount = Number.isInteger(amount) ? amount : amount.toFixed(2);
    $(".total_sub_amt").text(currencyFormat(formattedAmount));
}

function getMonthNameFromIndex(index) {
    const months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];
    return months[index];
}

let statiSticsValu = null;
function subscriptionChart(subscriptions) {
    if (statiSticsValu) {
        statiSticsValu.destroy();
    }
    //  Correcting month indexing issue
    let correctedSubscriptions = new Array(12).fill(0);
    subscriptions.forEach((value, index) => {
        if (index >= 0 && index < 12) {
            correctedSubscriptions[index] = value;
        }
    });
    const ctx = document.getElementById("revenueChart").getContext("2d");

    const labels = [
        "Jan",
        "Feb",
        "March",
        "April",
        "May",
        "June",
        "July",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
    ];
    const data = {
        labels,
        datasets: [
            {
                label: "Revenue",
                data: correctedSubscriptions,
                borderColor: "#FC8019",
                borderWidth: 3,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverRadius: 6,
                pointBackgroundColor: "#fff",
                pointBorderColor: "#FC8019",
                pointBorderWidth: 4,
                tension: 0.4,
                fill: false,
            },
        ],
    };

    const backgroundPlugin = {
        id: "custom_bg",
        beforeDraw: (chart) => {
            const {
                ctx,
                chartArea: { left, right, top, bottom },
                scales: { x },
            } = chart;
            ctx.save();
            ctx.fillStyle = "#fff6f0";
            for (let i = 0; i < x.ticks.length; i += 2) {
                const xStart = x.getPixelForTick(i);
                const xEnd = x.getPixelForTick(i + 1) || right;
                ctx.fillRect(xStart, top, xEnd - xStart, bottom - top);
            }
            ctx.restore();
        },
    };

    const config = {
        type: "line",
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                mode: "index",
                intersect: false,
            },

            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: "#fff",
                    titleColor: "#000",
                    bodyColor: "#000",
                    borderColor: "#ddd",
                    borderWidth: 1,
                    cornerRadius: 4,
                    padding: 10,
                    callbacks: {
                        label: (ctx) => `$${ctx.parsed.y.toLocaleString()}`,
                    },
                    displayColors: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (v) => `$${v >= 1000 ? v / 1000 + "k" : v}`,
                    },
                    grid: { drawTicks: false, display: false },
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { weight: "normal" } },
                },
            },
        },
        plugins: [backgroundPlugin],
    };

    statiSticsValu = new Chart(ctx, config);
}


  document.addEventListener("DOMContentLoaded", function () {
    const closeBtn = document.querySelector("#demoAlert .btn-close");
    const alertBox = document.getElementById("demoAlert");

    closeBtn.addEventListener("click", function () {
      alertBox.style.display = "none";
    });
  });

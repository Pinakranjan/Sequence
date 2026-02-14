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

// dashborad main datas
$(document).ready(function () {
    var url = $("#get-dashboard-data").val();

    // Up / Down SVG
    var upSVG =
        '<svg width="25" height="27" viewBox="0 0 25 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.4558 6.6123L18.8111 9.13598L13.792 14.5139L9.67793 10.1058L2.05664 18.2829L3.50685 19.8368L9.67793 13.2246L13.792 17.6327L20.2716 10.7009L22.6269 13.2246V6.6123H16.4558Z" fill="#00B69B"></path></svg>';
    var downSVG =
        '<svg width="25" height="27" viewBox="0 0 25 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.456 19.8368L18.8113 17.3131L13.7921 11.9352L9.67806 16.3433L2.05676 8.16618L3.50697 6.6123L9.67806 13.2245L13.7921 8.81638L20.2718 15.7482L22.6271 13.2245V19.8368H16.456Z" fill="#F93C65"></path></svg>';

    $.ajax({
        type: "GET",
        url: url,
        dataType: "json",
        success: function (res) {
            // Totals
            $("#total_order").text(res.total_order);
            $("#total_items").text(res.total_items);
            $("#total_sales").text(currencyFormat(res.total_sales));
            $("#total_expense").text(currencyFormat(res.total_expense));

            // Orders
            $("#order_update").html(
                (res.order_status === "up" ? upSVG : downSVG) +
                    ' <span class="' +
                    (res.order_status === "up"
                        ? "para-count"
                        : "para-count-low") +
                    ' fw-bold">' +
                    Math.abs(res.order_diff) +
                    "</span> " +
                    (res.order_status === "up" ? "Up" : "Down") +
                    " from yesterday"
            );

            // Items
            $("#item_update").html(
                (res.item_status === "up" ? upSVG : downSVG) +
                    ' <span class="' +
                    (res.item_status === "up"
                        ? "para-count"
                        : "para-count-low") +
                    ' fw-bold">' +
                    Math.abs(res.item_diff) +
                    "</span> " +
                    (res.item_status === "up" ? "Up" : "Down") +
                    " from yesterday"
            );

            // Sales
            $("#sale_update").html(
                (res.sales_status === "up" ? upSVG : downSVG) +
                    ' <span class="' +
                    (res.sales_status === "up"
                        ? "para-count"
                        : "para-count-low") +
                    ' fw-bold">' +
                    res.sales_percent +
                    "%</span> " +
                    (res.sales_status === "up" ? "Up" : "Down") +
                    " from yesterday"
            );

            // Expense
            $("#expense_update").html(
                (res.expense_status === "up" ? upSVG : downSVG) +
                    ' <span class="' +
                    (res.expense_status === "up"
                        ? "para-count"
                        : "para-count-low") +
                    ' fw-bold">' +
                    res.expense_percent +
                    "%</span> " +
                    (res.expense_status === "up" ? "Up" : "Down") +
                    " from yesterday"
            );
        },
    });
});

// Money in money out chart
const ctx = document.getElementById("moneyChart").getContext("2d");

const moneyChart = new Chart(ctx, {
    type: "line",
    data: {
        labels: [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sept",
            "Oct",
            "Nov",
            "Dec",
        ],
        datasets: [
            {
                label: "Money In",
                data: [], // dynamic
                borderColor: "#28a745",
                backgroundColor: (ctx) => {
                    const gradient = ctx.chart.ctx.createLinearGradient(
                        0,
                        0,
                        0,
                        ctx.chart.height
                    );
                    gradient.addColorStop(0, "#07B32F1A"); // 10% opacity
                    gradient.addColorStop(1, "#07B32F00"); // 0% opacity
                    return gradient;
                },
                fill: true,
                tension: 0.4,
                pointBackgroundColor: "#28a745",
                pointRadius: 0,
                pointHoverRadius: 7,
                pointBorderWidth: 2,
            },
            {
                label: "Money Out",
                data: [], // dynamic
                borderColor: "#FF8C32",
                backgroundColor: (ctx) => {
                    const gradient = ctx.chart.ctx.createLinearGradient(
                        0,
                        0,
                        0,
                        ctx.chart.height
                    );
                    gradient.addColorStop(0, "#FF7A1319"); // 10% opacity
                    gradient.addColorStop(1, "#FF7A1300"); // 0% opacity

                    return gradient;
                },
                fill: true,
                tension: 0.4,
                pointBackgroundColor: "#FF8C32",
                pointRadius: 0,
                pointHoverRadius: 7,
                pointBorderWidth: 2,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: "index", intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                enabled: true,
                usePointStyle: true,
                backgroundColor: "#fff",
                titleColor: "#000",
                bodyColor: "#000",
                borderColor: "#ddd",
                borderWidth: 1,
                padding: 10,
                displayColors: true,
                callbacks: {
                    title: (context) => {
                        const months = [
                            "Jan",
                            "Feb",
                            "Mar",
                            "Apr",
                            "May",
                            "Jun",
                            "Jul",
                            "Aug",
                            "Sept",
                            "Oct",
                            "Nov",
                            "Dec",
                        ];
                        return (
                            months[context[0].dataIndex] +
                            " " +
                            document.querySelector(".moneyFlow-yearly").value
                        );
                    },
                    label: (context) => {
                        const label = context.dataset.label || "";
                        return `${label} : ${currencyFormat(context.raw)}`;
                    },
                },
            },
        },
        scales: {
            x: { grid: { display: true, color: "#f0f0f0" } },
            y: {
                grid: { color: "#f0f0f0", display: false },
                ticks: {
                    callback: (value) => currencyFormat(value),
                },
                beginAtZero: true,
            },
        },
        elements: { line: { borderWidth: 2 } },
    },
    plugins: [
        {
            id: "hoverLine",
            afterDraw: (chart) => {
                if (chart.tooltip._active && chart.tooltip._active.length) {
                    const ctx = chart.ctx;
                    const activePoint = chart.tooltip._active[0];
                    const x = activePoint.element.x;
                    const topY = chart.scales.y.top;
                    const bottomY = chart.scales.y.bottom;

                    // Draw vertical dashed line
                    ctx.save();
                    ctx.beginPath();
                    ctx.setLineDash([5, 5]);
                    ctx.moveTo(x, topY);
                    ctx.lineTo(x, bottomY);
                    ctx.lineWidth = 1;
                    ctx.strokeStyle = "#999";
                    ctx.stroke();
                    ctx.restore();

                    // Draw circle above the month label
                    const yLabel = chart.scales.y.bottom + 10;
                    ctx.beginPath();
                    ctx.arc(x, yLabel - 10, 4, 0, 2 * Math.PI);
                    ctx.fillStyle = "#FF8C32";
                    ctx.fill();
                }
            },
        },
    ],
});

// Load Data Function
function loadMoneyFlow(year) {
    const url = document.getElementById("get-money-flow-data").value;
    $.get(url, { year: year }, function (response) {
        let moneyInData = Array(12).fill(0);
        let moneyOutData = Array(12).fill(0);

        for (const [month, amount] of Object.entries(response.money_in)) {
            moneyInData[month - 1] = amount;
        }
        for (const [month, amount] of Object.entries(response.money_out)) {
            moneyOutData[month - 1] = amount;
        }

        moneyChart.data.datasets[0].data = moneyInData;
        moneyChart.data.datasets[1].data = moneyOutData;
        moneyChart.update();

        // Update top summary
        const totalIn = moneyInData.reduce((a, b) => a + b, 0);
        const totalOut = moneyOutData.reduce((a, b) => a + b, 0);
        $(".money-in + p strong").text(`${currencyFormat(totalIn)}`);
        $(".money-out + p strong").text(`${currencyFormat(totalOut)}`);
    });
}

// Initial load
loadMoneyFlow($(".moneyFlow-yearly").val());

// On year change
$(".moneyFlow-yearly").change(function () {
    loadMoneyFlow($(this).val());
});

// loss and profit chart
const ctxloss = document.getElementById("profitLossChart").getContext("2d");

const data = {
    labels: ["Expense", "Income"],
    datasets: [
        {
            data: [0, 0],
            backgroundColor: ["#FF8C34", "#00B243"],
            borderWidth: 0,
            cutout: "70%",
        },
    ],
};

const config = {
    type: "doughnut",
    data: data,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: "#ffffff",
                titleColor: "#000000",
                bodyColor: "#000000",
                borderColor: "#ccc",
                borderWidth: 1,
                callbacks: {
                    label: function (context) {
                        const label = context.label || "";
                        const value = context.raw;
                        return `${label}: ${currencyFormat(value)}`;
                    },
                },
            },
        },
    },
};

const profitLossChart = new Chart(ctxloss, config);

function loadLossProfit(year) {
    const url = document.getElementById("get-loss-profit-data").value;

    $.get(url, { year: year }, function (response) {
        let profit = response.profit_total || 0;
        let loss = response.loss_total || 0;

        if (profit === 0 && loss === 0) {
            profit = 0.00001;
            loss = 0.00001;
        }

        profitLossChart.data.datasets[0].data = [loss, profit];
        profitLossChart.update();

        // Update summary numbers (show 0 if demo chart)
        document.querySelector(".profit + p strong").textContent = `${
            currencyFormat(response.profit_total) || 0
        }`;
        document.querySelector(".loss + p strong").textContent = `${
            currencyFormat(response.loss_total) || 0
        }`;
    });
}

// 🔹 Initial load for current year
loadLossProfit($(".loss-profit").val());

// 🔹 Update chart and summary on year change
$(".loss-profit").change(function () {
    loadLossProfit($(this).val());
});

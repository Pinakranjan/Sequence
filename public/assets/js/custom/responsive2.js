function handleResize() {
    const salesContent = document.getElementById("salesContent");
    const salesRight = document.getElementById("salesRight");
    const salesLeft = document.getElementById("salesLeft");
    const searchContainer = document.getElementById("searchContainer");
    const categoryBtnContainer = document.getElementById(
        "categoryBtnContainer"
    );

    if (window.innerWidth <= 550) {
        salesContent.classList.remove("sales-content");
        salesContent.classList.add("row");

        salesRight.classList.remove("sales-right-content");
        salesRight.classList.add("col-lg-7");

        salesLeft.classList.remove("left-content-sales");
        salesLeft.classList.add("col-lg-5");

        searchContainer.classList.add("custom-padding");
        // categoryBtnContainer.classList.add("custom-padding");
    } else {
        salesContent.classList.add("sales-content");
        salesContent.classList.remove("row");

        salesRight.classList.add("sales-right-content");
        salesRight.classList.remove("col-lg-7");

        salesLeft.classList.add("left-content-sales");
        salesLeft.classList.remove("col-lg-5");

        searchContainer.classList.remove("custom-padding");
        // categoryBtnContainer.classList.remove("custom-padding");
    }
}

handleResize();

window.addEventListener("resize", handleResize);

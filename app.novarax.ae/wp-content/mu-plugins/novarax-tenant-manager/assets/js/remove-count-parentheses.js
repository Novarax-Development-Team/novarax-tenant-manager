document.addEventListener("DOMContentLoaded", () => {
    const counts = document.querySelectorAll("span.count");

    counts.forEach(span => {
        if (!span.innerText) return;

        // Remove parentheses everywhere: (4) → 4
        span.innerText = span.innerText.replace(/[()]/g, "").trim();
    });
});

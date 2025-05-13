export function generalFormat() {
    function formatDateTime(date, includeTime = true) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const formattedDate = new Date(date);

        const day = formattedDate.getDate().toString().padStart(2, '0');
        const month = months[formattedDate.getMonth()];
        const year = formattedDate.getFullYear();
        const hours = formattedDate.getHours().toString().padStart(2, '0');
        const minutes = formattedDate.getMinutes().toString().padStart(2, '0');
        const seconds = formattedDate.getSeconds().toString().padStart(2, '0');

        if (includeTime) {
            return `${day} ${month} ${year} ${hours}:${minutes}:${seconds}`;
        } else {
            return `${day} ${month} ${year}`;
        }
    }

    function formatAmount(amount, decimalPlaces = 2, currencySymbol = '$') {
        const isNegative = amount < 0; // Check if the amount is negative
        const absoluteAmount = Math.abs(amount).toFixed(decimalPlaces); // Format absolute value

        const [integerPart, decimalPart] = absoluteAmount.split('.');
        const integerWithCommas = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        const formattedAmount = decimalPlaces > 0 ? `${integerWithCommas}.${decimalPart}` : integerWithCommas;

        return isNegative ? `-${currencySymbol}${formattedAmount}` : `${currencySymbol}${formattedAmount}`;
    }

    const formatRgbaColor = (hex, opacity) => {
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
    };

    const formatNameLabel = (name) => {
        if (!name) return "";
        return name
            .split(" ")
            .map(part => part.charAt(0).toUpperCase())
            .join("")
            .slice(0, 2);
    };

    return {
        formatDateTime,
        formatAmount,
        formatRgbaColor,
        formatNameLabel
    };
}
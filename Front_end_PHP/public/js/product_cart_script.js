document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById("pickup-date");
    const timeInput = document.getElementById("pickup-time");
    const checkoutButton = document.querySelector(".button.is-primary");
    const validDays = ["Wednesday", "Thursday", "Friday"];
    const collectionSlots = {
        "10:00": "13:00",
        "13:00": "16:00",
        "16:00": "19:00",
    };

    // Enable the checkout button initially
    checkoutButton.disabled = false;

    // Helper function to check if the selected date is valid
    function isValidDate(selectedDate) {
        if (!selectedDate) return false; // Ensure a date is selected

        const today = new Date();
        const selected = new Date(selectedDate);

        // Set both dates to midnight to ignore the time component
        today.setHours(0, 0, 0, 0);
        selected.setHours(0, 0, 0, 0);

        const dayName = selected.toLocaleDateString("en-US", { weekday: "long" });

        // Check if the selected day is Wed, Thu, or Fri and at least 24 hours in the future
        return (
            validDays.includes(dayName) &&
            selected.getTime() > today.getTime() + 24 * 60 * 60 * 1000
        );
    }

    // Helper function to check if the selected time is within a valid slot
    function isValidTime(selectedTime) {
        if (!selectedTime) return false; // Ensure a time is selected

        for (const [start, end] of Object.entries(collectionSlots)) {
            if (selectedTime >= start && selectedTime <= end) {
                return true;
            }
        }
        return false;
    }

    // Add a click event listener to the checkout button
    checkoutButton.addEventListener("click", function (event) {
        const selectedDate = dateInput.value;
        const selectedTime = timeInput.value;

        if (!selectedDate) {
            alert("Please select a date for your collection slot 😊");
            return;
        }

        if (!selectedTime) {
            alert("Please select a time for your collection slot 😊");
            return;
        }

        if (!isValidDate(selectedDate)) {
            alert(
                `Invalid date selected. Please choose a Wednesday, Thursday, or Friday at least 24 hours from now😊`
            );
            return;
        }

        if (!isValidTime(selectedTime)) {
            alert(
                `Invalid time selected. Please choose a time within one of the following slots ⌚: 
                \n10:00 - 13:00 
                \n13:00 - 16:00 
                \n16:00 - 19:00.`
            );
            return;
        }

        alert("Proceeding to checkout...");
    });
});
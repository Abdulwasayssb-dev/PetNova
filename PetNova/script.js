
"use strict";

// ------------------------------------
// 1. Pet Health Assistant (demo only)
// ------------------------------------

const healthForm = document.getElementById("health-form");
const petPhoto = document.getElementById("pet-photo");
const photoPreview = document.getElementById("photo-preview");
const healthResult = document.getElementById("health-result");

if (petPhoto && photoPreview) {
    petPhoto.addEventListener("change", function () {
        const file = petPhoto.files[0];

        if (!file) {
            photoPreview.removeAttribute("src");
            photoPreview.hidden = true;
            return;
        }

        if (!file.type.startsWith("image/")) {
            healthResult.textContent = "Please select a valid image file.";
            petPhoto.value = "";
            photoPreview.hidden = true;
            return;
        }

        photoPreview.src = URL.createObjectURL(file);
        photoPreview.hidden = false;
    });
}

if (healthForm) {
    healthForm.addEventListener("submit", function (event) {
        event.preventDefault();

        healthResult.textContent =
            "This is currently a demo. No AI diagnosis has been performed. " +
            "A veterinarian should assess your pet's symptoms. " +
            "For breathing difficulty, collapse, severe bleeding, or suspected poisoning, " +
            "seek urgent veterinary care.";
    });
}


// ------------------------------------
// 2. Pet Listing and Price Limit
// ------------------------------------

const listingForm = document.getElementById("seller-listing");
const listingPetType = document.getElementById("listing-pet-type");
const listingAge = document.getElementById("listing-age");
const listingBreed = document.getElementById("listing-breed");
const listingCity = document.getElementById("listing-city");
const calculateListingPrice = document.getElementById("calculate-listing-price");
const sellerPrice = document.getElementById("seller-price");
const listingPriceResult = document.getElementById("listing-price-result");
const listingPriceWarning = document.getElementById("listing-price-warning");
const listingSubmitResult = document.getElementById("listing-submit-result");

let recommendedMaximum = null;

// These are placeholder values for testing the website.
// They are NOT verified pet market prices.
function getDemoRecommendedPrice(petType) {
    const demoPrices = {
        dog: 30000,
        cat: 20000,
        other: 10000
    };

    return demoPrices[petType] ?? null;
}

if (calculateListingPrice) {
    calculateListingPrice.addEventListener("click", function () {
        if (
            !listingPetType.value ||
            !listingBreed.value.trim() ||
            listingAge.value === "" ||
            !listingCity.value.trim()
        ) {
            listingPriceResult.textContent =
                "Please complete the pet type, breed, age, and city first.";
            return;
        }

        recommendedMaximum = getDemoRecommendedPrice(listingPetType.value);

        if (recommendedMaximum === null) {
            listingPriceResult.textContent =
                "A recommendation is not available for this pet type.";
            return;
        }

        sellerPrice.max = String(recommendedMaximum);

        listingPriceResult.textContent =
            "Demo recommended maximum: PKR " +
            recommendedMaximum.toLocaleString("en-PK") +
            ". These placeholder values must be replaced with verified market data.";

        listingPriceWarning.textContent = "";
        listingSubmitResult.textContent = "";
    });
}

if (sellerPrice) {
    sellerPrice.addEventListener("input", function () {
        const enteredPrice = Number(sellerPrice.value);

        if (
            recommendedMaximum !== null &&
            sellerPrice.value !== "" &&
            enteredPrice > recommendedMaximum
        ) {
            listingPriceWarning.textContent =
                "Price exceeds the recommended maximum of PKR " +
                recommendedMaximum.toLocaleString("en-PK") +
                ". Please enter a lower price.";
        } else {
            listingPriceWarning.textContent = "";
        }
    });
}

if (listingForm) {
    listingForm.addEventListener("submit", function (event) {
        event.preventDefault();

        if (recommendedMaximum === null) {
            listingSubmitResult.textContent =
                "Please calculate the recommended price before submitting.";
            return;
        }

        const enteredPrice = Number(sellerPrice.value);

        if (
            !Number.isFinite(enteredPrice) ||
            enteredPrice <= 0 ||
            enteredPrice > recommendedMaximum
        ) {
            listingSubmitResult.textContent =
                "Listing blocked. Enter a valid price that does not exceed the recommended maximum.";
            return;
        }

        listingSubmitResult.textContent =
            "Demo check passed. The listing has NOT been saved. " +
            "PHP and MySQL will be connected to save it securely later.";
    });
}


// ------------------------------------
// 3. Separate Pet Price Advisor
// ------------------------------------

const priceForm = document.getElementById("price-form");
const priceResult = document.getElementById("price-result");

if (priceForm) {
    priceForm.addEventListener("submit", function (event) {
        event.preventDefault();

        priceResult.textContent =
            "This advisor is currently a prototype. Verified local listing data " +
            "is needed before PetNova can provide a reliable market-based estimate.";
    });
}


// ------------------------------------
// 4. Veterinary Appointment Demo
// ------------------------------------

const appointmentForm = document.getElementById("appointment-form");
const appointmentDate = document.getElementById("appointment-date");
const appointmentResult = document.getElementById("appointment-result");

if (appointmentDate) {
    const today = new Date();
    const localToday = new Date(
        today.getTime() - today.getTimezoneOffset() * 60000
    ).toISOString().split("T")[0];

    appointmentDate.min = localToday;
}

if (appointmentForm) {
    appointmentForm.addEventListener("submit", function (event) {
        event.preventDefault();

        if (appointmentDate.value < appointmentDate.min) {
            appointmentResult.textContent =
                "Please select today or a future date.";
            return;
        }

        appointmentResult.textContent =
            "Demo only: your appointment request has not been saved or sent to a veterinarian.";
    });
}


// ------------------------------------
// 5. Temporary Marketplace Buttons
// ------------------------------------

document.querySelectorAll("[data-message]").forEach(function (button) {
    button.addEventListener("click", function () {
        alert(button.dataset.message);
    });
});

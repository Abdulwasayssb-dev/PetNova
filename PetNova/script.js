
"use strict";

// ------------------------------------
// 1. Pet Health Assistant (Demo Only)
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
            if (healthResult) {
                healthResult.textContent =
                    "Please select a valid image file.";
            }

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

        if (healthResult) {
            healthResult.textContent =
                "This is currently a demo. No AI diagnosis has been performed. " +
                "A veterinarian should assess your pet's symptoms. " +
                "For breathing difficulty, collapse, severe bleeding, or suspected poisoning, " +
                "seek urgent veterinary care.";
        }
    });
}


// ------------------------------------
// 2. Pet Listing and Price Limit
// ------------------------------------

// The old demo listing submission handler has been removed.
// sell_pet.php now handles listing validation, photo upload,
// price checking, and database saving through PHP.

const listingPetType = document.getElementById("listing-pet-type");
const listingAge = document.getElementById("listing-age");
const listingBreed = document.getElementById("listing-breed");
const listingCity = document.getElementById("listing-city");
const calculateListingPrice = document.getElementById("calculate-listing-price");
const sellerPrice = document.getElementById("seller-price");
const listingPriceResult = document.getElementById("listing-price-result");
const listingPriceWarning = document.getElementById("listing-price-warning");

let recommendedMaximum = null;

// Placeholder values for the homepage prototype.
// These are NOT verified market prices.
function getDemoRecommendedPrice(petType) {
    const demoPrices = {
        dog: 30000,
        cat: 20000,
        other: 10000
    };

    return demoPrices[petType] ?? null;
}

// Homepage price calculation prototype
if (calculateListingPrice) {
    calculateListingPrice.addEventListener("click", function () {

        if (
            !listingPetType ||
            !listingBreed ||
            !listingAge ||
            !listingCity ||
            !sellerPrice
        ) {
            return;
        }

        if (
            !listingPetType.value ||
            !listingBreed.value.trim() ||
            listingAge.value === "" ||
            !listingCity.value.trim()
        ) {
            if (listingPriceResult) {
                listingPriceResult.textContent =
                    "Please complete the pet type, breed, age, and city first.";
            }
            return;
        }

        recommendedMaximum =
            getDemoRecommendedPrice(listingPetType.value);

        if (recommendedMaximum === null) {
            if (listingPriceResult) {
                listingPriceResult.textContent =
                    "A recommendation is not available for this pet type.";
            }
            return;
        }

        sellerPrice.max = String(recommendedMaximum);

        if (listingPriceResult) {
            listingPriceResult.textContent =
                "Demo recommended maximum: PKR " +
                recommendedMaximum.toLocaleString("en-PK") +
                ". These placeholder values must be replaced with verified market data.";
        }

        if (listingPriceWarning) {
            listingPriceWarning.textContent = "";
        }
    });
}

// Homepage price warning prototype
if (sellerPrice) {
    sellerPrice.addEventListener("input", function () {

        const enteredPrice = Number(sellerPrice.value);

        if (
            recommendedMaximum !== null &&
            sellerPrice.value !== "" &&
            enteredPrice > recommendedMaximum
        ) {
            if (listingPriceWarning) {
                listingPriceWarning.textContent =
                    "Price exceeds the recommended maximum of PKR " +
                    recommendedMaximum.toLocaleString("en-PK") +
                    ". Please enter a lower price.";
            }
        } else {
            if (listingPriceWarning) {
                listingPriceWarning.textContent = "";
            }
        }
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

        if (priceResult) {
            priceResult.textContent =
                "This advisor is currently a prototype. Verified local listing data " +
                "is needed before PetNova can provide a reliable market-based estimate.";
        }
    });
}


// ------------------------------------
// 4. Veterinary Appointment Form
// ------------------------------------

// PHP handles appointment submission and database saving.
// JavaScript only sets the minimum selectable date.

const appointmentDate =
    document.getElementById("appointment_date");

if (appointmentDate) {
    const today = new Date();

    const localToday = new Date(
        today.getTime() - today.getTimezoneOffset() * 60000
    ).toISOString().split("T")[0];

    appointmentDate.min = localToday;
}


// ------------------------------------
// 5. Temporary Marketplace Buttons
// ------------------------------------

document.querySelectorAll("[data-message]").forEach(function (button) {
    button.addEventListener("click", function () {
        alert(button.dataset.message);
    });
});

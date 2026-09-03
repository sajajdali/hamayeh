import React from 'react';
import { createRoot } from 'react-dom/client';

window.React = React;
window.ReactDOM = { createRoot };

const normalizeDigits = (value) => String(value)
    .replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
    .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));

const phoneInputs = 'input[data-phone-input], input[wire\\:model$="phone"], input[wire\\:model$="Phone"]';

document.addEventListener('focusin', (event) => {
    if (!event.target.matches(phoneInputs)) {
        return;
    }

    event.target.type = 'tel';
    event.target.inputMode = 'numeric';
    event.target.lang = 'en';
    event.target.dir = 'ltr';
}, true);

document.addEventListener('input', (event) => {
    if (!event.target.matches('input[wire\\:model$="phone"], input[wire\\:model$="Phone"]')) {
        return;
    }

    const normalized = normalizeDigits(event.target.value).replace(/[^\d]/g, '');
    if (event.target.value !== normalized) {
        event.target.value = normalized;
    }
}, true);

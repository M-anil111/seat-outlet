/*flatpickr("#dateRange", {
    mode: "range",
    dateFormat: "M d, Y",
	minDate: "today",
    allowInput: false,
    locale: {
        rangeSeparator: " to "
    },
    onChange: function(selectedDates) {
        const cpid  = input.dataset.cpid;
        const dcat  = input.dataset.dcat;
        const startDate = toApiDate(selectedDates[0]);
        const endDate   = toApiDate(selectedDates[1]);
        if (selectedDates.length === 2) {
            updateHeading(null, null, null, null);
            updateEventsSection({
                startDate, endDate, cpid, dcat
            });   
        }
    }
});*/

function toApiDate(dateObj) {
    if (!dateObj) return null;

    const year  = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day   = String(dateObj.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

const dateRange = document.getElementById('dateRange');
const datePickerSection = document.getElementById('datePickerSection');

const startInput = document.getElementById('startInput');
const endInput = document.getElementById('endInput');

let selectedStart = null;
let selectedEnd = null;

let picker = flatpickr('#calendar', {
    inline: true,
    minDate: "today",
    mode: 'range',
    showMonths: 2,
    dateFormat: "M d, Y",
    onChange: function(selectedDates) {
        selectedStart = selectedDates[0] || null;
        selectedEnd   = selectedDates[1] || null;

        const cpid = input.dataset.cpid;
        const dcat = input.dataset.dcat;

        if (selectedDates.length === 2) {
            const startDate = toApiDate(selectedStart);
            const endDate   = toApiDate(selectedEnd);

            updateHeading(null, null, null, null);
            updateEventsSection({
                startDate,
                endDate,
                cpid,
                dcat
            });
        }
    }
});

// Open picker on input click
dateRange.addEventListener('click', (e) => {
    e.stopPropagation();

    if (datePickerSection.classList.contains('opacity-zero')) {
        datePickerSection.classList.remove('opacity-zero');

        setTimeout(() => {
            picker.redraw();
        }, 10);
    }
});

// Close on outside click
document.addEventListener('click', (e) => {
    if (
        !datePickerSection.contains(e.target) &&
        e.target !== dateRange
    ) {
        datePickerSection.classList.add('opacity-zero');
    }
});

// Reset
document.getElementById('resetDates').addEventListener('click', () => {
    picker.clear();
    selectedStart = selectedEnd = null;
    startInput.value = '';
    endInput.value = '';
    dateRange.value = '';
});

// Cancel
document.getElementById('cancelDates').addEventListener('click', () => {
    datePickerSection.classList.add('opacity-zero');
});

// Apply
document.getElementById('applyDates').addEventListener('click', () => {
    if (!selectedStart || !selectedEnd) return;

    const startText = flatpickr.formatDate(selectedStart, 'm/d/Y');
    const endText = flatpickr.formatDate(selectedEnd, 'm/d/Y');

    dateRange.value = startText + ' - ' + endText;

    const apiStart = flatpickr.formatDate(selectedStart, 'Y-m-d');
    const apiEnd = flatpickr.formatDate(selectedEnd, 'Y-m-d');

    //console.log('Apply Dates:', apiStart, apiEnd);

    datePickerSection.classList.add('opacity-zero');
});
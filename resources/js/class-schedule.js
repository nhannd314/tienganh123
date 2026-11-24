// The class timetable (resources/views/admin/schedule/index.blade.php): clicking a slot opens a dialog with the classroom of the slot
// selected and its note filled in; the dialog posts the date and shift of the slot, the chosen classroom and the note. Without a
// classroom the note is disabled, since a slot with no class has nothing to note.
const dialog = document.querySelector('[data-schedule-dialog]');

// Open on the week of today (only marked when the month shown is the current one and the page was not just saved).
document.querySelector('[data-schedule-current-week]')?.scrollIntoView({ block: 'start' });

if (dialog) {
    const title = dialog.querySelector('[data-schedule-dialog-title]');
    const dateInput = dialog.querySelector('[data-schedule-date]');
    const shiftInput = dialog.querySelector('[data-schedule-shift]');
    const classroomSelect = dialog.querySelector('[data-schedule-classroom]');
    const noteInput = dialog.querySelector('[data-schedule-note]');

    classroomSelect.addEventListener('change', () => {
        noteInput.disabled = classroomSelect.value === '';
    });

    document.querySelectorAll('[data-schedule-slot]').forEach((slot) => {
        slot.addEventListener('click', () => {
            title.textContent = slot.dataset.title;
            dateInput.value = slot.dataset.date;
            shiftInput.value = slot.dataset.shift;
            classroomSelect.value = slot.dataset.classroom;
            noteInput.value = slot.dataset.note;
            noteInput.disabled = classroomSelect.value === '';

            dialog.showModal();
        });
    });

    dialog.querySelector('[data-schedule-cancel]').addEventListener('click', () => dialog.close());
}

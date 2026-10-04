document.addEventListener('DOMContentLoaded', initBookingModals);

function initBookingModals() {
  const BASE_URL = document.body.dataset.baseUrl || '';

  const rescheduleModal = document.getElementById('rescheduleModal');
  if (rescheduleModal) {
    const form = document.getElementById('rescheduleForm');
    const dateInput = document.getElementById('rescheduleDate');
    const timeInput = document.getElementById('rescheduleTime');

    if (form && dateInput && timeInput) {
      rescheduleModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        const id = trigger.getAttribute('data-booking-id');
        if (!id) return;

        form.action = BASE_URL + 'storage/booking/reschedule/' + id;
        dateInput.value = trigger.getAttribute('data-booking-date') || '';
        timeInput.value = (trigger.getAttribute('data-booking-time') || '').slice(0, 5);
      });
    }
  }

  const declineModal = document.getElementById('declineModal');
  if (declineModal) {
    const form = document.getElementById('declineForm');

    if (form) {
      declineModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        const id = trigger.getAttribute('data-booking-id');
        if (!id) return;

        form.action = BASE_URL + 'storage/booking/decline/' + id;
      });
    }
  }

  const imageModal = document.getElementById('imageModal');
  if (imageModal) {
    const img = document.getElementById('imageModalSrc');

    if (img) {
      imageModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        const src = trigger.getAttribute('data-src');
        if (src) img.src = src;
      });

      imageModal.addEventListener('hidden.bs.modal', function () {
        img.src = '';
      });
    }
  }
}
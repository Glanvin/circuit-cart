<div class="container py-5" style="max-width: 720px;">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4" aria-hidden="true">
                <i class="bi bi-tools text-info" style="font-size: 3rem;"></i>
            </div>
            <h1 class="h4 fw-bold text-center mb-1">Book a Repair</h1>
            <p class="text-center text-body-secondary mb-4">Tell us about the device you need fixed and pick a preferred visit slot.</p>

            <?= form_open('booking', ['enctype' => 'multipart/form-data']); ?>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control <?= is_invalid('device') ?>" id="device" name="device" value="<?= set_value('device') ?>" placeholder="Device Name & Model">
                    <label for="device">Device Name & Model*</label>
                    <?= form_error('device', '<div class="invalid-feedback">', '</div>') ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="issue">Brief Issue Description*</label>
                    <textarea class="form-control <?= is_invalid('issue') ?>" id="issue" name="issue" rows="4" placeholder="e.g. Screen is cracked and the touch sometimes stops responding."><?= set_value('issue') ?></textarea>
                    <?= form_error('issue', '<div class="invalid-feedback">', '</div>') ?>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control <?= is_invalid('preferred_date') ?>" id="preferred_date" name="preferred_date" value="<?= set_value('preferred_date') ?>" placeholder="Visit Date">
                            <label for="preferred_date">Preferred Visit Date*</label>
                            <?= form_error('preferred_date', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select <?= is_invalid('preferred_time') ?>" id="preferred_time" name="preferred_time" required>
                                <option value="">Select a date first</option>
                            </select>
                            <label for="preferred_time">Preferred Visit Time*</label>
                            <?= form_error('preferred_time', '<div class="invalid-feedback">', '</div>') ?>
                        </div>
                        <div id="slot-loading" class="form-text text-body-secondary d-none">Loading available slots...</div>
                        <div id="slot-empty" class="form-text text-danger d-none">No available slots for this date.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="images">Photos of the Device</label>
                    <input type="file" class="form-control" id="images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                    <div class="form-text text-body-secondary">Optional. Up to 3 images, 5 MB each (JPG, PNG, WebP, GIF).</div>
                </div>

                <button type="submit" class="btn btn-info w-100 py-2" name="booking" value="booking">Submit Booking</button>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('preferred_date');
    const timeSelect = document.getElementById('preferred_time');
    const loadingEl = document.getElementById('slot-loading');
    const emptyEl = document.getElementById('slot-empty');

    if (!dateInput || !timeSelect) return;

    dateInput.addEventListener('change', function() {
        const date = this.value;
        if (!date) {
            timeSelect.innerHTML = '<option value="">Select a date first</option>';
            emptyEl.classList.add('d-none');
            return;
        }

        //Show loading
        timeSelect.disabled = true;
        timeSelect.innerHTML = '<option value="">Loading...</option>';
        loadingEl.classList.remove('d-none');
        emptyEl.classList.add('d-none');

        fetch('<?= site_url('booking/slots/') ?>' + encodeURIComponent(date))
            .then(response => response.json())
            .then(data => {
                loadingEl.classList.add('d-none');
                timeSelect.disabled = false;

                if (!data.ok) {
                    timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                    emptyEl.textContent = data.error || 'Failed to load slots.';
                    emptyEl.classList.remove('d-none');
                    return;
                }

                if (!data.slots || data.slots.length === 0) {
                    timeSelect.innerHTML = '<option value="">No slots available</option>';
                    emptyEl.classList.remove('d-none');
                    return;
                }

                //Populate time slots
                timeSelect.innerHTML = '<option value="">Choose a time</option>';
                data.slots.forEach(slot => {
                    const start = slot.time.substring(0, 5);
                    const end = slot.end_time.substring(0, 5);
                    const opt = document.createElement('option');
                    opt.value = start;
                    opt.textContent = start + ' - ' + end + ' (' + slot.remaining + ' left)';
                    timeSelect.appendChild(opt);
                });
            })
            .catch(() => {
                loadingEl.classList.add('d-none');
                timeSelect.disabled = false;
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                emptyEl.textContent = 'Failed to load slots. Please try again.';
                emptyEl.classList.remove('d-none');
            });
    });

    //Pre-select time if form was re-rendered with validation error
    const preselectedTime = '<?= set_value('preferred_time') ?>';
    if (preselectedTime && timeSelect.options.length > 1) {
        timeSelect.value = preselectedTime;
    }
});
</script>
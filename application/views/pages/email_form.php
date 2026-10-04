<div class="container py-5" style="max-width: 720px;">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4" aria-hidden="true">
                <i class="bi bi-envelope text-info" style="font-size: 3rem;"></i>
            </div>
            <h1 class="h4 fw-bold text-center mb-1">Send an Email</h1>
            <p class="text-center text-body-secondary mb-4">Fill out the details below to send a message with an optional attachment.</p>

            <?= form_open('email', ['enctype' => 'multipart/form-data']); ?>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control <?= is_invalid('to_email') ?>" id="to_email" name="to_email" value="<?= set_value('to_email') ?>" placeholder="Recipient Email">
                    <label for="to_email">Recipient Email*</label>
                    <?= form_error('to_email', '<div class="invalid-feedback">', '</div>') ?>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control <?= is_invalid('subject') ?>" id="subject" name="subject" value="<?= set_value('subject') ?>" placeholder="Subject">
                    <label for="subject">Subject*</label>
                    <?= form_error('subject', '<div class="invalid-feedback">', '</div>') ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="message">Message*</label>
                    <textarea class="form-control <?= is_invalid('message') ?>" id="message" name="message" rows="4" placeholder="Type your message here..."><?= set_value('message') ?></textarea>
                    <?= form_error('message', '<div class="invalid-feedback">', '</div>') ?>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="attachment">Attachment</label>
                    <input type="file" class="form-control" id="attachment" name="attachment">
                    <div class="form-text text-body-secondary">Optional. Max 5 MB. Will be deleted securely after sending.</div>
                </div>

                <button type="submit" class="btn btn-info w-100 py-2" name="send_email" value="send_email">Send Email</button>
            <?= form_close(); ?>
        </div>
    </div>
</div>
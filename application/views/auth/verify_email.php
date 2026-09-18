<?php $maskedEmail = mask_email($this->session->userdata('email')); ?>

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card p-4 text-center shadow" style="max-width: 400px; width: 100%;">
    <h5 class="mb-3">Verify Your Email</h5>
    <p class="text-muted small mb-4">A verification code has been sent to <span class="fw-semibold"><?= html_escape($maskedEmail) ?></span>.</p>

    <div id="otp-form" class="mb-4">
      <div id="otp-inputs" class="d-flex justify-content-center gap-2 mb-3">
        <input type="text" class="form-control text-center fs-4 otp-input" inputmode="numeric" maxlength="1" required />
        <input type="text" class="form-control text-center fs-4 otp-input" inputmode="numeric" maxlength="1" required />
        <input type="text" class="form-control text-center fs-4 otp-input" inputmode="numeric" maxlength="1" required />
        <input type="text" class="form-control text-center fs-4 otp-input" inputmode="numeric" maxlength="1" required />
        <input type="text" class="form-control text-center fs-4 otp-input" inputmode="numeric" maxlength="1" required />
        <input type="text" class="form-control text-center fs-4 otp-input" inputmode="numeric" maxlength="1" required />
      </div>

      <button type="button" id="verify-btn" class="btn btn-primary w-100" data-action="<?= site_url('auth/verify') ?>">Verify OTP</button>
    </div>

    <p class="small text-body-secondary mb-0">Didn't get the code? <a href="<?= site_url('auth/verify') ?>">Resend it</a></p>
  </div>
</div>

<script src="<?= base_url('assets/js/verify_email.js') ?>"></script>
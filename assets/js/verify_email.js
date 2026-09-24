(function () {
  var inputs = document.querySelectorAll('.otp-input');
  var verifyBtn = document.getElementById('verify-btn');

  inputs.forEach(function (input, index) {
    input.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
      if (this.value && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !this.value && index > 0) {
        inputs[index - 1].focus();
      }
    });

    input.addEventListener('paste', function (e) {
      e.preventDefault();
      var digits = (e.clipboardData || window.clipboardData).getData('text')
        .replace(/[^0-9]/g, '')
        .slice(0, inputs.length);
      for (var i = 0; i < digits.length; i++) {
        inputs[i].value = digits.charAt(i);
      }
      inputs[Math.min(digits.length, inputs.length - 1)].focus();
    });
  });

  function submitOtp() {
    var code = '';
    for (var i = 0; i < inputs.length; i++) {
      code += inputs[i].value;
    }
    if (code.length !== inputs.length) {
      for (var j = 0; j < inputs.length; j++) {
        if (!inputs[j].value) {
          inputs[j].focus();
          break;
        }
      }
      return;
    }

    var form = document.createElement('form');
    form.method = 'post';
    form.action = verifyBtn.dataset.action;

    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'otp';
    hidden.value = code;
    form.appendChild(hidden);

    if (window.CSRF && window.CSRF.name() && window.CSRF.token()) {
      var csrfField = document.createElement('input');
      csrfField.type = 'hidden';
      csrfField.name = window.CSRF.name();
      csrfField.value = window.CSRF.token();
      form.appendChild(csrfField);
    }

    document.body.appendChild(form);
    form.submit();
  }

  if (verifyBtn) {
    verifyBtn.addEventListener('click', submitOtp);
  }

  for (var k = 0; k < inputs.length; k++) {
    inputs[k].addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        submitOtp();
      }
    });
  }

  inputs[0].focus();
})();
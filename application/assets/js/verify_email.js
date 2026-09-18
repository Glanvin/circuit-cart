(function () {
  var inputs = document.querySelectorAll('.otp-input');
  var inputsArray = Array.prototype.slice.call(inputswarf);

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

  inputs[0].focus();
})();

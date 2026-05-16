(function (root) {
  'use strict';

  var defaults = {
    width: 220,
    height: 220,
    colorDark: '#003e5c',
    colorLight: '#ffffff',
    correctLevel: 'H',
    imageUrl: '',
    logoUrl: '',
    logoSizeRatio: 0.20,
    backgroundPadding: 12,
    backgroundColor: '#ffffff',
    backgroundRadiusRatio: 0.30,
    logoRadiusRatio: 0.25,
    statusTarget: null
  };

  function mergeOptions(options) {
    var out = {};
    var key;
    options = options || {};
    for (key in defaults) out[key] = defaults[key];
    for (key in options) out[key] = options[key];
    if (!out.imageUrl && out.logoUrl) out.imageUrl = out.logoUrl;
    return out;
  }

  function resolveCorrectLevel(value) {
    if (!root.QRCode || !root.QRCode.CorrectLevel) return value;
    if (typeof value === 'number') return value;
    return root.QRCode.CorrectLevel[String(value || 'H').toUpperCase()] || root.QRCode.CorrectLevel.H;
  }

  function clear(target) {
    if (typeof target === 'string') target = document.querySelector(target);
    if (target) target.innerHTML = '';
  }

  function setStatus(target, text) {
    if (!target) return;
    if (typeof target === 'string') target = document.querySelector(target);
    if (target) target.textContent = text || '';
  }

  function overlay(qrEl, imageUrl, options) {
    if (!qrEl || !imageUrl) return;
    options = mergeOptions(options);

    var wrapper = qrEl.parentNode;
    if (!wrapper || !wrapper.classList || !wrapper.classList.contains('qris-manual-overlay-wrap')) {
      wrapper = document.createElement('div');
      wrapper.className = 'qris-manual-overlay-wrap';
      wrapper.style.position = 'relative';
      wrapper.style.display = 'inline-block';
      qrEl.parentNode.insertBefore(wrapper, qrEl);
      wrapper.appendChild(qrEl);
    }

    var oldBg = wrapper.querySelector('.qris-manual-overlay-bg');
    var oldImg = wrapper.querySelector('.qris-manual-overlay-img');
    if (oldBg) oldBg.remove();
    if (oldImg) oldImg.remove();

    qrEl.style.display = 'block';
    var qrSize = qrEl.width || qrEl.offsetWidth || options.width;
    var logoSize = Math.round(qrSize * options.logoSizeRatio);
    var bgSize = logoSize + (options.backgroundPadding * 2);

    var bg = document.createElement('div');
    bg.className = 'qris-manual-overlay-bg';
    bg.style.cssText = [
      'position:absolute',
      'left:50%',
      'top:50%',
      'width:' + bgSize + 'px',
      'height:' + bgSize + 'px',
      'background:' + options.backgroundColor,
      'border-radius:' + Math.round(bgSize * options.backgroundRadiusRatio) + 'px',
      'transform:translate(-50%,-50%)',
      'z-index:1',
      'pointer-events:none'
    ].join(';');

    var img = document.createElement('img');
    img.className = 'qris-manual-overlay-img';
    img.alt = 'QRIS logo overlay';
    img.style.cssText = [
      'position:absolute',
      'left:50%',
      'top:50%',
      'width:' + logoSize + 'px',
      'height:' + logoSize + 'px',
      'object-fit:contain',
      'border-radius:' + Math.round(logoSize * options.logoRadiusRatio) + 'px',
      'transform:translate(-50%,-50%)',
      'z-index:2',
      'pointer-events:none'
    ].join(';');
    img.onload = function () { setStatus(options.statusTarget, ''); };
    img.onerror = function () {
      bg.remove();
      img.remove();
      setStatus(options.statusTarget, 'Logo overlay gagal dimuat.');
    };

    wrapper.appendChild(bg);
    wrapper.appendChild(img);
    img.src = imageUrl;
  }

  function render(target, payload, options) {
    if (!root.QRCode) throw new Error('QRCode library belum dimuat. Tambahkan qrcodejs dulu.');
    if (typeof target === 'string') target = document.querySelector(target);
    if (!target) throw new Error('Target QR container tidak ditemukan.');
    if (!payload) throw new Error('Payload QRIS kosong.');

    options = mergeOptions(options);
    clear(target);

    var holder = document.createElement('div');
    holder.style.display = 'inline-block';
    target.appendChild(holder);

    new root.QRCode(holder, {
      text: payload,
      width: options.width,
      height: options.height,
      colorDark: options.colorDark,
      colorLight: options.colorLight,
      correctLevel: resolveCorrectLevel(options.correctLevel)
    });

    setTimeout(function () {
      var qrEl = holder.querySelector('canvas, img');
      overlay(qrEl, options.imageUrl, options);
    }, 30);
  }

  function bindUpload(input, callback) {
    if (typeof input === 'string') input = document.querySelector(input);
    if (!input) return;
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (event) { callback(event.target.result, file); };
      reader.readAsDataURL(file);
    });
  }

  root.QrisManualOverlay = {
    render: render,
    overlay: overlay,
    clear: clear,
    bindUpload: bindUpload
  };
})(window);

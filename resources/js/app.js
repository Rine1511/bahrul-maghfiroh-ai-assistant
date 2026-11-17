/*
 * Widget chatbot yang bisa dipasang di website mana pun lewat satu baris <script>.
 * Variabel CSS dan CONFIG disisipkan otomatis oleh server (ChatbotController::embed).
 * Tampilan memakai Shadow DOM supaya tidak bentrok dengan gaya website induk.
 */
(function () {
  'use strict';

  var script = document.currentScript || document.querySelector('script[src*="chatbot/embed.js"]');
  if (!script || document.getElementById('bm-chatbot-host')) return;

  var API = new URL(script.src, location.href).origin + '/chatbot/ask';
  var pageMode = script.getAttribute('data-mode') === 'page';
  var autoOpen = script.getAttribute('data-open') === 'true';

  var HISTORY_KEY = 'bmc_history_v1';
  var SID_KEY = 'bmc_sid';
  var ICON_CHAT = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M4 4h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H9l-5 4v-4H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>';
  var ICON_CLOSE = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';

  function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function rand() { return Math.random().toString(36).slice(2) + Date.now().toString(36); }

  function getSid() {
    try {
      var id = localStorage.getItem(SID_KEY);
      if (!id) { id = rand(); localStorage.setItem(SID_KEY, id); }
      return id;
    } catch (e) { return rand(); }
  }

  // Escape HTML, lalu ubah **tebal**, URL, dan baris baru menjadi markup aman.
  function format(text) {
    var s = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
    return s.replace(/\n/g, '<br>');
  }

  function template() {
    return '<div class="bmc-root' + (pageMode ? ' bmc-page' : '') + '">' +
      '<button type="button" class="bmc-launcher" aria-expanded="false" aria-controls="bmc-panel">' +
        ICON_CHAT + '<span>' + esc(CONFIG.launcher) + '</span></button>' +
      '<section class="bmc-panel" id="bmc-panel" role="dialog" aria-label="' + esc(CONFIG.name) + '"' + (pageMode ? '' : ' hidden') + '>' +
        '<header class="bmc-head"><div><strong>' + esc(CONFIG.name) + '</strong><small>' + esc(CONFIG.subtitle) + '</small></div>' +
          '<button type="button" class="bmc-close" aria-label="Tutup chat">' + ICON_CLOSE + '</button></header>' +
        '<div class="bmc-log" role="log" aria-live="polite"></div>' +
        '<div class="bmc-quick"></div>' +
        '<form class="bmc-form" autocomplete="off">' +
          '<input type="text" class="bmc-input" maxlength="500" placeholder="Tulis pertanyaan Anda" aria-label="Pertanyaan Anda" required>' +
          '<button type="submit" class="bmc-send">Kirim</button></form>' +
      '</section></div>';
  }

  function mount() {
    if (document.getElementById('bm-chatbot-host')) return;

    var host = document.createElement('div');
    host.id = 'bm-chatbot-host';
    document.body.appendChild(host);
    var shadow = host.attachShadow({ mode: 'open' });
    shadow.innerHTML = '<style>' + CSS + '</style>' + template();

    var launcher = shadow.querySelector('.bmc-launcher');
    var panel = shadow.querySelector('.bmc-panel');
    var closeBtn = shadow.querySelector('.bmc-close');
    var log = shadow.querySelector('.bmc-log');
    var quickBox = shadow.querySelector('.bmc-quick');
    var form = shadow.querySelector('.bmc-form');
    var input = shadow.querySelector('.bmc-input');
    var sendBtn = shadow.querySelector('.bmc-send');

    var sid = getSid();
    var history = [];
    var busy = false;

    try { history = JSON.parse(sessionStorage.getItem(HISTORY_KEY) || '[]'); } catch (e) { history = []; }
    if (!Array.isArray(history)) history = [];

    function save() {
      try { sessionStorage.setItem(HISTORY_KEY, JSON.stringify(history.slice(-20))); } catch (e) {}
    }

    function addMessage(role, text) {
      var el = document.createElement('div');
      el.className = 'bmc-msg ' + (role === 'user' ? 'user' : 'bot');
      el.innerHTML = format(text);
      log.appendChild(el);
      log.scrollTop = log.scrollHeight;
      return el;
    }

    function showTyping() {
      var el = document.createElement('div');
      el.className = 'bmc-msg bot';
      el.innerHTML = '<span class="bmc-typing" aria-label="Sedang mengetik"><i></i><i></i><i></i></span>';
      log.appendChild(el);
      log.scrollTop = log.scrollHeight;
      return el;
    }

    function setBusy(state) {
      busy = state;
      input.disabled = state;
      sendBtn.disabled = state;
      if (!state) input.focus();
    }

    function renderQuick() {
      quickBox.innerHTML = '';
      (CONFIG.quick || []).forEach(function (q) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = q;
        b.addEventListener('click', function () { send(q); });
        quickBox.appendChild(b);
      });
    }

    function send(text) {
      text = (text || '').trim();
      if (!text || busy) return;

      quickBox.hidden = true;
      addMessage('user', text);
      var past = history.slice(-6);
      history.push({ role: 'user', content: text });
      input.value = '';
      setBusy(true);
      var typing = showTyping();

      fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ message: text, history: past, sid: sid })
      })
        .then(function (res) {
          if (res.status === 429) throw new Error('Pertanyaan terlalu cepat. Tunggu sebentar lalu coba lagi.');
          if (!res.ok) throw new Error('Terjadi gangguan pada server. Coba lagi beberapa saat.');
          return res.json();
        })
        .then(function (data) {
          typing.remove();
          addMessage('bot', data.answer);
          history.push({ role: 'assistant', content: data.answer });
          save();
        })
        .catch(function (err) {
          typing.remove();
          history.pop(); // pesan yang gagal tidak disimpan
          addMessage('bot', err && err.message && err.message.indexOf('Failed to fetch') === -1
            ? err.message
            : 'Koneksi bermasalah. Periksa internet Anda lalu coba lagi.');
        })
        .then(function () { setBusy(false); });
    }

    function open() {
      panel.hidden = false;
      launcher.hidden = true;
      launcher.setAttribute('aria-expanded', 'true');
      input.focus();
    }

    function close() {
      panel.hidden = true;
      launcher.hidden = false;
      launcher.setAttribute('aria-expanded', 'false');
      launcher.focus();
    }

    launcher.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
      if (!pageMode && e.key === 'Escape' && !panel.hidden) close();
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });

    if (history.length) {
      history.forEach(function (m) { addMessage(m.role, m.content); });
      quickBox.hidden = true;
    } else {
      addMessage('bot', CONFIG.greeting);
      renderQuick();
    }

    if (autoOpen && !pageMode) open();
  }

  if (document.body) mount();
  else document.addEventListener('DOMContentLoaded', mount);
})();

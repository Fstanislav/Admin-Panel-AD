// Basic UI behavior and modal logic
(function(){
  const modal = document.getElementById('modal');
  const form = document.getElementById('password-form');
  const usernameInput = document.getElementById('username');
  const msg = form ? form.querySelector('.form-msg') : null;

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-edit]');
    if (btn) {
      const u = btn.getAttribute('data-username');
      if (usernameInput) usernameInput.value = u;
      openModal();
    }
    if (e.target.matches('[data-close]')) { closeModal(); }
    if (e.target.classList.contains('modal-backdrop')) { closeModal(); }
  });

  function openModal(){ if (modal) modal.classList.remove('hidden'); }
  function closeModal(){ if (modal) modal.classList.add('hidden'); if (form) form.reset(); if (msg) msg.textContent=''; msg?.classList.remove('error','success'); }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      msg.textContent = ''; msg.classList.remove('error','success');
      const fd = new FormData(form);
      const password = (fd.get('password')||'').toString();
      const password2 = (fd.get('password2')||'').toString();
      if (password !== password2) { msg.textContent='Пароли не совпадают'; msg.classList.add('error'); return; }
      if (password.length < 8 || !/[A-Za-zА-Яа-я]/u.test(password) || !/\d/.test(password)) { msg.textContent='Пароль не соответствует требованиям'; msg.classList.add('error'); return; }
      const payload = {
        username: fd.get('username'),
        password
      };
      try {
        const res = await fetch('/ad-panel/password_change.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
          credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.ok) { msg.textContent = 'Пароль успешно изменён'; msg.classList.add('success'); setTimeout(closeModal, 900); }
        else { msg.textContent = data.error || 'Не удалось изменить пароль'; msg.classList.add('error'); }
      } catch (err) {
        msg.textContent = 'Ошибка сети'; msg.classList.add('error');
      }
    });
  }
})();

(function(){
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

  function showModal(username){
    const modal = $('#modal');
    $('#usernameField').value = username;
    $('#new_password').value = '';
    $('#confirm_password').value = '';
    const alert = $('#modalAlert');
    alert.classList.add('hidden');
    alert.textContent = '';

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden','false');
    document.body.classList.add('no-scroll');
  }

  function hideModal(){
    const modal = $('#modal');
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden','true');
    document.body.classList.remove('no-scroll');
  }

  function validatePasswords(p1, p2){
    if (p1 !== p2) return 'Пароли не совпадают';
    if (p1.length < 8) return 'Пароль должен быть не менее 8 символов';
    if (!/[A-Za-zА-Яа-я]/u.test(p1)) return 'Пароль должен содержать хотя бы одну букву';
    if (!/\d/.test(p1)) return 'Пароль должен содержать хотя бы одну цифру';
    return '';
  }

  function showAlert(msg, ok=false){
    const el = $('#modalAlert');
    el.textContent = msg;
    el.classList.remove('hidden','alert-error','alert-success');
    el.classList.add(ok ? 'alert-success' : 'alert-error');
  }

  async function submitChange(){
    const username = $('#usernameField').value.trim();
    const p1 = $('#new_password').value;
    const p2 = $('#confirm_password').value;
    const err = validatePasswords(p1, p2);
    if (err){ showAlert(err, false); return; }

    const payload = new URLSearchParams();
    payload.set('username', username);
    payload.set('new_password', p1);
    payload.set('csrf_token', window.AD_PANEL?.csrf || '');

    try{
      const res = await fetch('/ad-panel/password_change.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json'
        },
        body: payload.toString()
      });
      const data = await res.json();
      if (data.success){
        showAlert(data.message || 'Пароль успешно изменён', true);
        setTimeout(hideModal, 1200);
      } else {
        showAlert(data.message || 'Не удалось изменить пароль', false);
      }
    } catch(e){
      showAlert('Ошибка сети', false);
    }
  }

  function wire(){
    $$('.btn.small[data-edit-user]').forEach(btn => {
      btn.addEventListener('click', () => showModal(btn.getAttribute('data-edit-user')));
    });
    $$('#modal [data-close]').forEach(el => el.addEventListener('click', hideModal));
    const save = $('#saveBtn');
    if (save) save.addEventListener('click', submitChange);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') hideModal();
    });

    // Settings page wiring
    const settingsForm = $('#settingsForm');
    if (settingsForm){
      const statusBadge = $('#statusBadge');
      const alert = $('#settingsAlert');
      const testBtn = $('#testBtn');

      function setStatus(ok, msg){
        statusBadge.textContent = 'Статус: ' + (msg || (ok ? 'подключено' : 'ошибка'));
        statusBadge.classList.remove('ok','err');
        statusBadge.classList.add(ok ? 'ok' : 'err');
      }

      async function testConnection(){
        const fd = new FormData(settingsForm);
        fd.set('csrf_token', window.AD_PANEL?.csrf || '');
        try{
          const res = await fetch('/ad-panel/settings_test.php', { method:'POST', body: fd, headers: { 'Accept': 'application/json' } });
          const data = await res.json();
          setStatus(!!data.success, data.message);
        }catch(e){ setStatus(false, 'Ошибка сети'); }
      }

      if (testBtn){ testBtn.addEventListener('click', testConnection); }

      let debounceTimer;
      settingsForm.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(testConnection, 500);
      });

      settingsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        alert.classList.add('hidden');
        const fd = new FormData(settingsForm);
        fd.set('csrf_token', window.AD_PANEL?.csrf || '');
        try{
          const res = await fetch('/ad-panel/settings_save.php', { method:'POST', body: fd, headers: { 'Accept': 'application/json' } });
          const data = await res.json();
          alert.textContent = data.message || (data.success ? 'Сохранено' : 'Ошибка сохранения');
          alert.classList.remove('hidden','alert-error','alert-success');
          alert.classList.add(data.success ? 'alert-success' : 'alert-error');
          if (data.success){
            setTimeout(testConnection, 300);
          }
        }catch(err){
          alert.textContent = 'Ошибка сети';
          alert.classList.remove('hidden','alert-success');
          alert.classList.add('alert-error');
        }
      });

      // Initial test
      testConnection();
    }
  }

  document.addEventListener('DOMContentLoaded', wire);
})();

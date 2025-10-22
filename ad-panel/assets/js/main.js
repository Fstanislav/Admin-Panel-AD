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
  }

  document.addEventListener('DOMContentLoaded', wire);
})();

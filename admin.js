const ADMIN_SESSION_KEY = 'himasi-admin-session-v2';
const ADMIN_PASSWORD = 'himasi123';

let data = loadPortalData();

function isLoggedIn() {
  return localStorage.getItem(ADMIN_SESSION_KEY) === 'true';
}

function syncView() {
  const authCard = document.getElementById('auth-card');
  const adminPanel = document.getElementById('admin-panel');
  if (isLoggedIn()) {
    authCard.classList.add('hidden');
    adminPanel.classList.remove('hidden');
    renderAdminLists();
  } else {
    authCard.classList.remove('hidden');
    adminPanel.classList.add('hidden');
  }
}

function memberItem(item) {
  return `
    <div class="admin-item">
      <div>
        <strong>${item.name}</strong>
        <p>${item.role} • ${item.division}</p>
      </div>
      <button data-id="${item.id}" data-type="member">Hapus</button>
    </div>
  `;
}

function prokerItem(item) {
  return `
    <div class="admin-item">
      <div>
        <strong>${item.title}</strong>
        <p>${item.description}</p>
      </div>
      <button data-id="${item.id}" data-type="proker">Hapus</button>
    </div>
  `;
}

function renderAdminLists() {
  document.getElementById('member-admin-list').innerHTML = data.members.map(memberItem).join('');
  document.getElementById('proker-admin-list').innerHTML = data.proker.map(prokerItem).join('');
}

function upsertMember(payload) {
  const existing = data.members.find((item) => item.name.toLowerCase() === payload.name.toLowerCase());

  if (existing) {
    existing.role = payload.role;
    existing.division = payload.division;
    existing.photo = payload.photo;
    return;
  }

  data.members.push({
    id: crypto.randomUUID(),
    ...payload,
  });
}

function bindAuth() {
  const loginForm = document.getElementById('login-form');
  const loginMsg = document.getElementById('login-msg');

  loginForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const password = document.getElementById('password-input').value;

    if (password === ADMIN_PASSWORD) {
      localStorage.setItem(ADMIN_SESSION_KEY, 'true');
      loginForm.reset();
      loginMsg.textContent = '';
      syncView();
      return;
    }

    loginMsg.textContent = 'Password salah.';
  });

  document.getElementById('logout-btn').addEventListener('click', () => {
    localStorage.removeItem(ADMIN_SESSION_KEY);
    syncView();
  });
}

function bindForms() {
  document.getElementById('member-form').addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);

    upsertMember({
      name: formData.get('name').toString().trim(),
      role: formData.get('role').toString().trim(),
      division: formData.get('division').toString().trim(),
      photo: formData.get('photo').toString().trim(),
    });

    savePortalData(data);
    renderAdminLists();
    event.target.reset();
  });

  document.getElementById('proker-form').addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);

    data.proker.push({
      id: crypto.randomUUID(),
      title: formData.get('title').toString().trim(),
      description: formData.get('description').toString().trim(),
    });

    savePortalData(data);
    renderAdminLists();
    event.target.reset();
  });
}

function bindDelete() {
  document.getElementById('admin-panel').addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLButtonElement)) {
      return;
    }

    const type = target.dataset.type;
    const id = target.dataset.id;
    if (!type || !id) {
      return;
    }

    if (type === 'member') {
      data.members = data.members.filter((item) => item.id !== id);
    }

    if (type === 'proker') {
      data.proker = data.proker.filter((item) => item.id !== id);
    }

    savePortalData(data);
    renderAdminLists();
  });
}

bindAuth();
bindForms();
bindDelete();
syncView();

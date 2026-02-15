function renderHome() {
  const data = loadPortalData();

  document.getElementById('proker-list').innerHTML = data.proker
    .map(
      (item) => `
        <article class="card">
          <h3>${item.title}</h3>
          <p>${item.description}</p>
        </article>
      `,
    )
    .join('');

  document.getElementById('member-list').innerHTML = data.members
    .map(
      (item) => `
        <article class="member-card">
          <img src="${item.photo}" alt="Foto ${item.name}" loading="lazy" />
          <h3>${item.name}</h3>
          <p><strong>${item.role}</strong></p>
          <p class="muted">${item.division}</p>
        </article>
      `,
    )
    .join('');

  document.getElementById('news-list').innerHTML = data.news
    .map(
      (item) => `
        <article class="card">
          <h3>${item.title}</h3>
          <p>${item.content}</p>
        </article>
      `,
    )
    .join('');
}

function bindMobileMenu() {
  const menuBtn = document.getElementById('menu-btn');
  const navLinks = document.getElementById('nav-links');

  menuBtn.addEventListener('click', () => {
    navLinks.classList.toggle('show');
  });
}

function bindNavigationFeedback() {
  const navLinks = document.getElementById('nav-links');
  const links = document.querySelectorAll('a[href^="#"]');

  links.forEach((link) => {
    link.addEventListener('click', (event) => {
      const targetId = link.getAttribute('href');
      const section = document.querySelector(targetId);
      if (!section) {
        return;
      }

      event.preventDefault();
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      navLinks.classList.remove('show');
    });
  });

  const adminLink = document.getElementById('admin-link');
  adminLink.addEventListener('click', (event) => {
    event.preventDefault();
    window.location.assign('admin.html');
  });
}

renderHome();
bindMobileMenu();
bindNavigationFeedback();

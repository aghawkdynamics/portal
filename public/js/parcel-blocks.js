document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('blocksModal');
  if (!modal) return;

  const body  = modal.querySelector('.modal-body');
  const title = modal.querySelector('.modal-title');

  document.querySelectorAll('.blocks-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      title.textContent = `Blocks for parcel #${id}`;
      body.textContent = 'Loading…';

      try {
        const res  = await fetch(`/?q=apiBlock/list&id=${id}`);
        const data = await res.json();
        if (Array.isArray(data) && data.length) {
          body.innerHTML = '<ul>' + data.map(
            b => `<li>${b.name} (${b.acres} acres, ${b.crop_category})</li>`
          ).join('') + '</ul>';
        } else {
          body.textContent = 'No blocks yet';
        }
      } catch { body.textContent = 'Error loading'; }

      modal.classList.add('show');
    });
  });

  modal.querySelector('.close').onclick = () => modal.classList.remove('show');
  modal.onclick = e => { if (e.target === modal) modal.classList.remove('show'); };
});

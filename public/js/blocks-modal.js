// document.addEventListener('DOMContentLoaded', () => {
//     const modal = document.getElementById('blocksModal');
//     if (!modal) return;
//     const body  = modal.querySelector('.modal-body');
//     const title = modal.querySelector('.modal-title');
//     const full  = modal.querySelector('.full-link');
  
//     document.querySelectorAll('.blocks-btn').forEach(btn => {
//       btn.addEventListener('click', async () => {
//         const id = btn.dataset.id;
//         title.textContent = `Blocks (first 10) — Parcel #${id}`;
//         body.textContent  = 'Loading…';
//         full.href         = `/?q=block/index&parcel=${id}`;
  
//         try {
//           const res  = await fetch(`/?q=apiBlock/list&id=${id}&limit=10`);
//           const data = await res.json();
//           body.innerHTML = (data.length)
//             ? '<ul>'+data.map(b=>`<li>${b.name} (${b.acres} ac, ${b.crop_category})</li>`).join('')+'</ul>'
//             : 'No blocks yet';
//         } catch { body.textContent = 'Error loading'; }
  
//         modal.classList.add('show');
//       });
//     });
  
//     modal.querySelectorAll('.close').forEach(el => el.onclick = () => modal.classList.remove('show'));
//     modal.onclick = e => { if (e.target === modal) modal.classList.remove('show'); };
//   });
  
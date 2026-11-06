document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('icon');
  const preview = document.getElementById('icon-preview');
  if (!input || !preview) return;
  const show = value => {
    preview.style.display = value ? 'block' : 'none';
    const symbol = preview.querySelector('i');
    const caption = preview.querySelector('.small');
    if (symbol) symbol.className = value ? `ri-${value}` : '';
    if (caption) caption.textContent = value;
    document.querySelectorAll('.icon-item').forEach(item => {
      item.classList.toggle('active', item.querySelector('.icon-select')?.dataset.icon === value);
    });
  };
  show(input.value);
  document.querySelectorAll('.icon-item .icon-select').forEach(symbol => {
    symbol.closest('.icon-item').addEventListener('click', () => {
      input.value = symbol.dataset.icon;
      input.dispatchEvent(new Event('change', { bubbles: true }));
      show(input.value);
    });
  });
});

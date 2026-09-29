document.querySelectorAll('[name^="seo_title_"], [name^="seo_description_"]').forEach((field) => {
  const title = field.name.startsWith('seo_title_');
  const recommended = title ? '50–60' : '140–160';
  const counter = document.createElement('small');
  counter.className = 'seo-length-hint';
  counter.setAttribute('aria-live', 'polite');
  const update = () => { counter.textContent = `${field.value.length} caracteres · recomendado: ${recommended}`; };
  field.addEventListener('input', update);
  field.insertAdjacentElement('afterend', counter);
  update();
});

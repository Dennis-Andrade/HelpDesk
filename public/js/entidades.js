//Nota: depurar el codigo ya que me dio pereza hacerlo a tiempo ATT: Dennis del pasado
(function(){
  const tipoSel   = document.querySelector('#tipo_entidad');
  const segWrap   = document.querySelector('#segmento_wrap');
  const provSel   = document.querySelector('#provincia_id');
  const cantonSel = document.querySelector('#canton_id');
  const svcChecks = document.querySelectorAll('input[name="servicios[]"]');

  function toggleSegmento(){
    if (!tipoSel || !segWrap) return;
    segWrap.style.display = (tipoSel.value === 'cooperativa') ? 'block' : 'none';
  }

  function enforceMatrix(){
    const matrix = [...svcChecks].find(c => c.value === '1'); // id 1 = Matrix
    if (!matrix) return;
    if (matrix.checked) {
      [...svcChecks].forEach(c => { if (c !== matrix) { c.checked=false; c.disabled=true; }});
    } else {
      [...svcChecks].forEach(c => c.disabled=false);
    }
  }

  async function loadCantones(preselect){
    if (!provSel || !cantonSel) return;
    const pid = provSel.value;
    cantonSel.innerHTML = '<option value="">-- Seleccione --</option>';
    if (!pid) return;
    try{
      const r = await fetch('/shared/cantones?provincia_id=' + encodeURIComponent(pid));
      const data = await r.json();
      data.forEach(c => {
        const o = document.createElement('option');
        o.value = c.id; o.textContent = c.nombre;
        if (preselect && String(preselect) === String(c.id)) o.selected = true;
        cantonSel.appendChild(o);
      });
    }catch(e){ /* noop */ }
  }

  if (tipoSel) {
    tipoSel.addEventListener('change', toggleSegmento);
    toggleSegmento();
  }
  if (provSel) {
    // preselección en editar: el controller envía el canton elegido via data-attr
    const pre = provSel.getAttribute('data-canton-selected') || '';
    provSel.addEventListener('change', () => loadCantones(null));
    if (provSel.value) { loadCantones(pre); } // editar
  }
  if (svcChecks.length) {
    svcChecks.forEach(c => c.addEventListener('change', enforceMatrix));
    enforceMatrix();
  }
    // Fallback cross-browser: aplica clase is-checked al label .chip
  const chipInputs = document.querySelectorAll('.chip input[type="checkbox"]');
  function syncChipClass(cb){
    const label = cb.closest('.chip');
    if (!label) return;
    label.classList.toggle('is-checked', cb.checked);
  }
  chipInputs.forEach(cb => {
    syncChipClass(cb);
    cb.addEventListener('change', () => syncChipClass(cb));
  });
})();

/* ====== Provincias → Cantones (encadenado) ====== */
(function () {
  const $prov = document.querySelector('#provincia_id, select[name="provincia_id"]');
  const $cant = document.querySelector('#canton_id, select[name="canton_id"]');
  if (!$prov || !$cant) return;

  async function cargarCantones(provId, selectedId) {
    // limpiar
    $cant.innerHTML = '<option value="">-- Seleccione --</option>';
    if (!provId) return;

    try {
      const base = $prov.getAttribute('data-cantones-url') || '/shared/cantones';
      const url  = base + '?provincia_id=' + encodeURIComponent(provId);
      const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();

      (data || []).forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.nombre;
        if (String(selectedId ?? '') === String(c.id)) opt.selected = true;
        $cant.appendChild(opt);
      });
    } catch (e) {
      // opcional: console.error('cantones error', e);
    }
  }

  // Al cambiar la provincia
  $prov.addEventListener('change', e => {
    cargarCantones(e.target.value, null);
  });

  // Carga inicial (edición / volver del servidor con errores)
  const initialProv = $prov.value;
  const selectedCanton = $cant.getAttribute('data-selected');
  if (initialProv) {
    cargarCantones(initialProv, selectedCanton);
  }
})();

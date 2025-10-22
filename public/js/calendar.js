(function(){
  const root = document.getElementById('calendar-app');
  if (!root) { return; }

  const endpoint = root.getAttribute('data-events-endpoint') || '/calendario/eventos';
  const monthLabel = root.querySelector('[data-calendar-month]');
  const grid = root.querySelector('[data-calendar-grid]');
  const list = root.querySelector('[data-calendar-list]');
  const btnPrev = root.querySelector('[data-calendar-prev]');
  const btnNext = root.querySelector('[data-calendar-next]');
  const moduleSelect = root.querySelector('[data-calendar-module]');

  let events = [];
  try {
    const initial = root.getAttribute('data-initial-events') || '[]';
    events = JSON.parse(initial);
    if (!Array.isArray(events)) {
      events = [];
    }
  } catch (err) {
    events = [];
  }

  let moduleFilter = root.getAttribute('data-initial-module') || '';
  if (moduleSelect) {
    moduleSelect.value = moduleFilter;
  }

  const initialMonth = root.getAttribute('data-initial-month');
  let currentDate = initialMonth ? parseMonth(initialMonth) : new Date();
  if (Number.isNaN(currentDate.getTime())) {
    currentDate = new Date();
  }
  currentDate.setDate(1);

  render();

  if (btnPrev) {
    btnPrev.addEventListener('click', function(){
      currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
      fetchAndRender();
    });
  }
  if (btnNext) {
    btnNext.addEventListener('click', function(){
      currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
      fetchAndRender();
    });
  }
  if (moduleSelect) {
    moduleSelect.addEventListener('change', function(){
      moduleFilter = moduleSelect.value || '';
      fetchAndRender();
    });
  }

  function fetchAndRender(){
    const params = new URLSearchParams();
    params.set('start', formatDate(startOfMonth(currentDate)));
    params.set('end', formatDate(endOfMonth(currentDate)));
    if (moduleFilter) {
      params.set('module', moduleFilter);
    }

    fetch(endpoint + '?' + params.toString(), {
      headers: { 'Accept': 'application/json' }
    })
      .then(function(response){
        if (!response.ok) {
          throw new Error('No se pudieron obtener los eventos.');
        }
        return response.json();
      })
      .then(function(payload){
        events = Array.isArray(payload.events) ? payload.events : [];
        render();
      })
      .catch(function(){
        // En un fallo mantenemos eventos actuales pero actualizamos título
        render();
      });
  }

  function render(){
    if (!grid || !list || !monthLabel) { return; }
    monthLabel.textContent = formatMonth(currentDate);

    renderGrid();
    renderList();
  }

  function renderGrid(){
    grid.innerHTML = '';
    const firstDay = startOfMonth(currentDate);
    const lastDay = endOfMonth(currentDate);
    const daysInMonth = lastDay.getDate();
    const startWeekday = mapWeekday(firstDay.getDay());
    const totalCells = Math.ceil((startWeekday + daysInMonth) / 7) * 7;
    const eventsByDate = groupEventsByDate(events);

    for (let cell = 0; cell < totalCells; cell++) {
      const dayOffset = cell - startWeekday;
      const cellDate = new Date(firstDay.getFullYear(), firstDay.getMonth(), firstDay.getDate() + dayOffset);
      const dateKey = formatDate(cellDate);
      const dayNode = document.createElement('div');
      dayNode.className = 'calendar__day';

      if (cellDate.getMonth() !== currentDate.getMonth()) {
        dayNode.classList.add('calendar__day--outside');
      }

      if (isToday(cellDate)) {
        dayNode.classList.add('calendar__day--today');
      }

      const number = document.createElement('span');
      number.className = 'calendar__day-number';
      number.textContent = String(cellDate.getDate());
      dayNode.appendChild(number);

      const dayEvents = eventsByDate.get(dateKey) || [];
      if (dayEvents.length) {
        const eventsContainer = document.createElement('div');
        eventsContainer.className = 'calendar__events';
        dayEvents.slice(0, 3).forEach(function(ev){
          const chip = document.createElement('span');
          chip.className = 'calendar__event';
          if (typeof ev.module === 'string') {
            chip.classList.add('calendar__event--' + ev.module.toLowerCase());
          }
          chip.textContent = (ev.badge || ev.title || 'Evento');
          chip.title = buildTooltip(ev);
          eventsContainer.appendChild(chip);
        });
        if (dayEvents.length > 3) {
          const more = document.createElement('span');
          more.className = 'calendar__event';
          more.textContent = '+' + (dayEvents.length - 3) + ' más';
          eventsContainer.appendChild(more);
        }
        dayNode.appendChild(eventsContainer);
      }

      grid.appendChild(dayNode);
    }
  }

  function renderList(){
    list.innerHTML = '';
    if (!events.length) {
      const empty = document.createElement('li');
      empty.textContent = 'No hay eventos registrados para este período.';
      empty.className = 'calendar__list-item';
      list.appendChild(empty);
      return;
    }

    const sorted = events.slice().sort(function(a, b){
      return (a.start || '').localeCompare(b.start || '');
    });

    sorted.forEach(function(ev){
      const item = document.createElement('li');
      item.className = 'calendar__list-item';

      const dateLine = document.createElement('div');
      dateLine.className = 'calendar__list-date';
      const dateLabel = formatLongDate(ev.start);
      const moduleLabel = ev.module ? ' · ' + ev.module : '';
      dateLine.textContent = dateLabel + moduleLabel;

      const title = document.createElement('div');
      title.className = 'calendar__list-description';
      const parts = [];
      if (ev.title) {
        parts.push(ev.title);
      }
      if (ev.entity) {
        parts.push('Entidad: ' + ev.entity);
      }
      if (ev.description) {
        parts.push(ev.description);
      }
      title.textContent = parts.join(' — ');

      item.appendChild(dateLine);
      item.appendChild(title);
      list.appendChild(item);
    });
  }

  function groupEventsByDate(data){
    const map = new Map();
    data.forEach(function(ev){
      if (!ev || !ev.start) { return; }
      const key = ev.start;
      if (!map.has(key)) {
        map.set(key, []);
      }
      map.get(key).push(ev);
    });
    return map;
  }

  function formatMonth(date){
    return new Intl.DateTimeFormat('es-EC', { month: 'long', year: 'numeric' }).format(date);
  }

  function formatLongDate(dateString){
    if (!dateString) { return 'Sin fecha'; }
    const date = parseDate(dateString);
    return new Intl.DateTimeFormat('es-EC', {
      day: '2-digit', month: 'long', year: 'numeric'
    }).format(date);
  }

  function startOfMonth(date){
    return new Date(date.getFullYear(), date.getMonth(), 1);
  }

  function endOfMonth(date){
    return new Date(date.getFullYear(), date.getMonth() + 1, 0);
  }

  function formatDate(date){
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
  }

  function mapWeekday(day){
    // Convert Sunday=0..Saturday=6 to Monday=0..Sunday=6
    return (day + 6) % 7;
  }

  function parseMonth(value){
    const parts = value.split('-');
    if (parts.length !== 2) {
      return new Date();
    }
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    return new Date(year, month, 1);
  }

  function parseDate(value){
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return new Date();
    }
    return date;
  }

  function isToday(date){
    const today = new Date();
    return today.getFullYear() === date.getFullYear()
      && today.getMonth() === date.getMonth()
      && today.getDate() === date.getDate();
  }

  function buildTooltip(event){
    const lines = [];
    if (event.title) {
      lines.push(event.title);
    }
    if (event.entity) {
      lines.push('Entidad: ' + event.entity);
    }
    if (event.description) {
      lines.push(event.description);
    }
    if (event.notes) {
      lines.push(event.notes);
    }
    return lines.join('\n');
  }
})();

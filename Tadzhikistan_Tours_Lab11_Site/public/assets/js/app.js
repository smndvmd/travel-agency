// public/assets/js/app.js — ES-модуль для лабораторной №11.
// Создаём элементы через DOM API и вставляем данные только через textContent.

function debounce(fn, delay = 300) {
  let timerId;
  return (...args) => {
    clearTimeout(timerId);
    timerId = setTimeout(() => fn(...args), delay);
  };
}

function messageFor(field) {
  const validity = field.validity;
  if (validity.valid) return '';
  if (validity.customError) return field.validationMessage;
  const missing = {
    tour_id: 'Выберите тур.',
    full_name: 'Введите имя и фамилию.',
    email: 'Укажите электронную почту.',
    phone: 'Укажите номер телефона.',
    start_date: 'Выберите дату начала.',
    end_date: 'Выберите дату окончания.',
    travelers: 'Укажите количество участников.'
  };
  if (validity.valueMissing) return missing[field.name] ?? 'Заполните это поле.';
  if (validity.typeMismatch) return 'Проверьте формат адреса электронной почты.';
  if (validity.patternMismatch) return 'Укажите номер с цифрами и символами + ( ) -.';
  if (validity.rangeUnderflow) return `Минимум: ${field.min}.`;
  if (validity.rangeOverflow) return `Максимум: ${field.max}.`;
  if (validity.tooLong) return `Не более ${field.maxLength} символов.`;
  return 'Проверьте значение поля.';
}

function showFieldState(field) {
  const error = messageFor(field);
  const hint = document.getElementById(`${field.id}-error`);
  field.classList.toggle('is-invalid', Boolean(error));
  if (error) field.setAttribute('aria-invalid', 'true');
  else field.removeAttribute('aria-invalid');
  if (hint) hint.textContent = error;
  return !error;
}

function checkBookingDates(form) {
  const start = form.elements.start_date;
  const end = form.elements.end_date;
  end.setCustomValidity(''); // обязательно очистить предыдущую ошибку
  if (start.value && end.value && end.value < start.value) {
    end.setCustomValidity('Дата окончания не может быть раньше даты начала.');
  }
}

function initBookingForm() {
  const form = document.getElementById('booking-form');
  if (!form) return;
  const start = form.elements.start_date;
  const end = form.elements.end_date;
  const allFields = () => form.querySelectorAll('input, select, textarea');
  start.addEventListener('change', () => { end.min = start.value; });
  function refresh(field) {
    checkBookingDates(form);
    showFieldState(field);
    if (field !== end && end.classList.contains('is-invalid')) showFieldState(end);
  }
  // focusout всплывает: один обработчик для всех полей.
  form.addEventListener('focusout', (event) => {
    if (event.target.matches('input, select, textarea')) refresh(event.target);
  });
  form.addEventListener('input', (event) => {
    if (event.target.matches('input, select, textarea') && event.target.classList.contains('is-invalid')) {
      refresh(event.target);
    }
  });
  form.addEventListener('submit', (event) => {
    checkBookingDates(form);
    let firstInvalid = null;
    allFields().forEach((field) => {
      if (!showFieldState(field) && !firstInvalid) firstInvalid = field;
    });
    if (firstInvalid) {
      event.preventDefault();
      firstInvalid.focus();
    }
    // При корректных данных форма уйдёт на /bookings/create (пока 404).
  });
}

function el(tag, className = '', text = '') {
  const node = document.createElement(tag);
  if (className) node.className = className;
  node.textContent = String(text ?? '');
  return node;
}
function makeTourCard(tour) {
  const card = el('article', 'tour-card');
  const validStyles = ['tour-mountains','tour-lakes','tour-pamir','tour-city'];
  const image = el('div', `tour-illustration ${validStyles.includes(tour.style) ? tour.style : 'tour-mountains'}`, tour.emoji);
  image.setAttribute('role', 'img');
  image.setAttribute('aria-label', `Условная иллюстрация: ${tour.title}`);
  const body = el('div', 'card-body p-3');
  body.append(el('span', 'badge-tour d-inline-block mb-2', tour.category_label));
  body.append(el('h2', 'h5', tour.title));
  body.append(el('p', 'text-secondary mb-2', tour.description));
  body.append(el('p', 'mb-0', `Продолжительность: ${tour.days} дн.`));
  const footer = el('div', 'card-footer p-3 tour-controls');
  footer.append(el('span', 'tour-price', tour.price ? `От ${Number(tour.price).toLocaleString('ru-RU')} сомони` : 'Тестовая запись'));
  const actions = el('div', 'd-flex flex-wrap gap-2');
  const detail = el('a', 'btn btn-primary btn-sm', 'Подробнее');
  detail.href = 'tour.html';
  const remove = el('button', 'btn btn-outline-danger btn-sm', 'Удалить');
  remove.type = 'button';
  remove.dataset.deleteUrl = `/tours/${encodeURIComponent(tour.id)}/delete`;
  remove.dataset.tourName = String(tour.title ?? '');
  actions.append(detail, remove);
  footer.append(actions);
  card.append(image, body, footer);
  return card;
}

function initTourSearch() {
  const input = document.getElementById('tour-search');
  const grid = document.getElementById('tours-grid');
  if (!input || !grid) return;
  const filter = document.getElementById('tour-type');
  const searchForm = document.getElementById('tours-search-form');
  const status = document.getElementById('tour-search-status');
  let controller = null;
  let requestSerial = 0;
  const setStatus = (message) => { if (status) status.textContent = message; };

  async function search() {
    controller?.abort();
    controller = new AbortController();
    const thisRequest = ++requestSerial;
    grid.setAttribute('aria-busy', 'true');
    setStatus('Поиск…');
    const params = new URLSearchParams({ q: input.value.trim(), type: filter?.value ?? '' });
    try {
      const response = await fetch(`/api/tours?${params.toString()}`, {
        headers: { Accept: 'application/json' },
        signal: controller.signal
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const items = await response.json();
      if (!Array.isArray(items)) throw new Error('Некорректный формат ответа API');
      if (thisRequest !== requestSerial) return;
      if (items.length) grid.replaceChildren(...items.map(makeTourCard));
      else grid.replaceChildren(el('p', 'col-span-full text-secondary', 'По вашему запросу туры не найдены.'));
      setStatus(items.length ? `Найдено туров: ${items.length}` : 'Туры не найдены.');
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error('Ошибка поиска:', error);
      setStatus('Не удалось выполнить поиск. Проверьте, запущен ли PHP-сервер.');
    } finally {
      if (thisRequest === requestSerial) grid.removeAttribute('aria-busy');
    }
  }
  const debouncedSearch = debounce(search, 300);
  input.addEventListener('input', debouncedSearch);
  filter?.addEventListener('change', search);
  searchForm?.addEventListener('submit', (event) => { event.preventDefault(); search(); });
  search();
}

function initDeleteConfirm() {
  const modalElement = document.getElementById('confirm-delete-modal');
  if (!modalElement || !window.bootstrap) return;
  const form = document.getElementById('confirm-delete-form');
  const title = document.getElementById('confirm-delete-name');
  const submit = form.querySelector('[type="submit"]');
  const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
  let lastTrigger = null;
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-delete-url]');
    if (!button) return;
    lastTrigger = button;
    form.action = button.dataset.deleteUrl;
    title.textContent = button.dataset.tourName ?? '';
    submit.disabled = false;
    modal.show();
  });
  modalElement.addEventListener('hidden.bs.modal', () => {
    if (lastTrigger?.isConnected) lastTrigger.focus();
  });
  form.addEventListener('submit', () => { submit.disabled = true; });
}

initBookingForm();
initTourSearch();
initDeleteConfirm();

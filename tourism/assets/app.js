(() => {
  const root = document.documentElement, $ = (s, c = document) => [...c.querySelectorAll(s)];
  // мавзӯи равшан/торик
  const saved = (() => { try { return localStorage.getItem('theme'); } catch (e) { return null; } })();
  root.dataset.theme = saved || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  const tb = document.getElementById('themeBtn');
  const icon = () => tb && (tb.textContent = root.dataset.theme === 'dark' ? '☀️' : '🌙');
  icon();
  tb && tb.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('theme', root.dataset.theme); } catch (e) {}
    icon();
  });
  // навори пешрафти скролл + навигатсияи шишагин
  const bar = document.querySelector('.progress'), top = document.querySelector('.top');
  const onScroll = () => {
    const h = root.scrollHeight - innerHeight;
    if (bar) bar.style.width = (h > 0 ? scrollY / h * 100 : 0) + '%';
    top && top.classList.toggle('scrolled', scrollY > 20);
  };
  addEventListener('scroll', onScroll, { passive: true }); onScroll();
  // ҳаракат ҳангоми намоён шудан (бо навбат)
  const sel = '.card, .stat, .hero > *, .scroll, h1, h2, .gallery';
  $(sel).forEach((el, i) => { if (!el.closest('.nav')) { el.setAttribute('data-reveal', ''); el.style.setProperty('--i', i % 6); } });
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return;
    e.target.classList.add('in'); io.unobserve(e.target);
    $('.bar i', e.target).forEach(b => b.style.width = b.dataset.w);
    if (e.target.matches('.bar')) e.target.firstElementChild.style.width = e.target.firstElementChild.dataset.w;
  }), { threshold: .08 });
  $('[data-reveal]').forEach(el => io.observe(el));
  $('.bar i').forEach(b => { new IntersectionObserver((es, o) => es.forEach(e => { if (e.isIntersecting) { b.style.width = b.dataset.w; o.disconnect(); } })).observe(b); });
  // ҳисобкунаки рақамҳо
  $('[data-count]').forEach(el => {
    const end = +el.dataset.count;
    new IntersectionObserver((es, o) => es.forEach(e => {
      if (!e.isIntersecting) return; o.disconnect();
      const t0 = performance.now(), dur = 1400;
      const step = t => { const p = Math.min(1, (t - t0) / dur); el.textContent = Math.round(end * (1 - Math.pow(1 - p, 4))); p < 1 && requestAnimationFrame(step); };
      requestAnimationFrame(step);
    })).observe(el);
  });
  // нишебии 3D дар кортҳои тур
  $('.tour').forEach(c => {
    c.addEventListener('mousemove', e => {
      const r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      c.style.transform = `perspective(900px) rotateX(${-y * 5}deg) rotateY(${x * 6}deg) translateY(-8px)`;
    });
    c.addEventListener('mouseleave', () => c.style.transform = '');
  });
  // эффекти мавҷ дар тугмаҳо
  document.addEventListener('click', e => {
    const b = e.target.closest('.btn, button'); if (!b || b.classList.contains('theme')) return;
    const r = b.getBoundingClientRect(), s = Math.max(r.width, r.height), sp = document.createElement('span');
    sp.className = 'ripple'; sp.style.cssText = `width:${s}px;height:${s}px;left:${e.clientX - r.left - s / 2}px;top:${e.clientY - r.top - s / 2}px`;
    b.appendChild(sp); setTimeout(() => sp.remove(), 650);
  });
})();

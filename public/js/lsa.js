/* LSA — site script (vanilla, no dependencies) */
(() => {
    'use strict';
    window.LSA_READY = true;

    const d = document;
    const root = d.documentElement;
    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const $  = (s, c = d) => c.querySelector(s);
    const $$ = (s, c = d) => [...c.querySelectorAll(s)];

    /* ---------- Nav: background on scroll ---------- */
    const nav = $('[data-nav]');
    if (nav) {
        const onScroll = () => nav.classList.toggle('is-scrolled', scrollY > 8);
        onScroll();
        addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------- Mobile menu ---------- */
    const toggle = $('[data-menu-toggle]');
    const menu = $('[data-menu]');
    if (toggle && menu) {
        const setMenu = (open) => {
            toggle.setAttribute('aria-expanded', String(open));
            menu.classList.toggle('is-open', open);
            root.classList.toggle('menu-open', open);
        };
        toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
        $$('a', menu).forEach((a) => a.addEventListener('click', () => setMenu(false)));
        addEventListener('keydown', (e) => { if (e.key === 'Escape') { setMenu(false); toggle.focus(); } });
        matchMedia('(min-width: 900px)').addEventListener('change', (e) => e.matches && setMenu(false));
    }

    /* ---------- Headline: split into words ---------- */
    $$('[data-split]').forEach((el) => {
        let i = 0;
        const walk = (node) => {
            [...node.childNodes].forEach((n) => {
                if (n.nodeType === 3) {
                    const frag = d.createDocumentFragment();
                    n.textContent.split(/(\s+)/).forEach((part) => {
                        if (!part) return;
                        if (/^\s+$/.test(part)) { frag.append(' '); return; }
                        const w = d.createElement('span');
                        const inner = d.createElement('span');
                        w.className = 'w';
                        inner.textContent = part;
                        inner.style.setProperty('--i', i++);
                        w.append(inner);
                        frag.append(w);
                    });
                    n.replaceWith(frag);
                } else if (n.nodeType === 1) {
                    walk(n);
                }
            });
        };
        walk(el);
    });

    /* ---------- Form pills from "WWDWL" strings ---------- */
    const renderPills = (el, str) => {
        el.innerHTML = '';
        el.setAttribute('role', 'img');
        el.setAttribute('aria-label', 'Last five: ' + str.split('').map((c) => ({ W: 'win', D: 'draw', L: 'loss' }[c])).join(', '));
        str.split('').forEach((c) => {
            const p = d.createElement('span');
            p.className = 'pill pill--' + c;
            p.textContent = c;
            p.setAttribute('aria-hidden', 'true');
            el.append(p);
        });
    };
    $$('[data-pills]').forEach((el) => renderPills(el, el.dataset.pills));

    /* ---------- Count-up numbers ---------- */
    const countUp = (el) => {
        const target = parseFloat(el.dataset.count);
        const decimals = (el.dataset.count.split('.')[1] || '').length;
        if (reduceMotion) { el.textContent = target.toFixed(decimals); return; }
        const dur = 1600;
        const t0 = performance.now();
        const ease = (t) => 1 - Math.pow(2, -10 * t);
        const tick = (now) => {
            const p = Math.min((now - t0) / dur, 1);
            el.textContent = (target * ease(p)).toFixed(decimals);
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = target.toFixed(decimals);
        };
        el.textContent = (0).toFixed(decimals);
        requestAnimationFrame(tick);
    };

    /* ---------- Scroll reveal ---------- */
    $$('[data-stagger]').forEach((p) => [...p.children].forEach((c, i) => c.style.setProperty('--i', i)));

    const reveal = (el) => {
        el.classList.add('is-in');
        $$('[data-count]', el).forEach(countUp);
        if (el.hasAttribute('data-stagger')) {
            const n = el.children.length;
            setTimeout(() => el.classList.add('is-done'), 900 + n * 90);
        }
    };

    if (reduceMotion) {
        $$('[data-reveal], [data-stagger]').forEach(reveal);
    } else if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) { reveal(e.target); io.unobserve(e.target); }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
        $$('[data-reveal], [data-stagger]').forEach((el) => io.observe(el));
    } else {
        $$('[data-reveal], [data-stagger]').forEach(reveal);
    }

    /* ---------- Cursor spotlight ---------- */
    $$('[data-spot]').forEach((el) => {
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            el.style.setProperty('--mx', `${e.clientX - r.left}px`);
            el.style.setProperty('--my', `${e.clientY - r.top}px`);
        });
    });

    /* ---------- Hero analysis console ---------- */
    const consoleEl = $('[data-console]');
    if (consoleEl) {
        // Sample data. Replace with your own fixtures (or fetch from your backend).
        const FIXTURES = [
            {
                league: 'Premier League', kick: 'Sat 18 Jan, 15:00',
                home: { n: 'Man City', s: 'MCI', c: '#1e6fb8' },
                away: { n: 'Arsenal',  s: 'ARS', c: '#c1121f' },
                prob: [48, 26, 26],
                xg: { h: [0, .18, .41, .66, .9, 1.12, 1.38, 1.62, 1.9, 2.14], a: [0, .1, .28, .4, .62, .8, 1.02, 1.25, 1.48, 1.71] },
                stats: [['Possession', 58, 42, '%'], ['Shots / game', 16.2, 12.4, '']],
                form: ['WWDWL', 'WDWWW'],
                lean: 'Home edge', conf: 3,
            },
            {
                league: 'La Liga', kick: 'Sun 19 Jan, 20:00',
                home: { n: 'Real Madrid', s: 'RMA', c: '#4c1d95' },
                away: { n: 'Barcelona',   s: 'BAR', c: '#a50044' },
                prob: [41, 24, 35],
                xg: { h: [0, .22, .4, .61, .83, 1.05, 1.31, 1.55, 1.8, 2.05], a: [0, .2, .44, .63, .81, 1.02, 1.24, 1.47, 1.7, 1.92] },
                stats: [['Possession', 47, 53, '%'], ['Shots / game', 15.1, 15.8, '']],
                form: ['WWWDW', 'WWLWW'],
                lean: 'Open game', conf: 2,
            },
            {
                league: 'Bundesliga', kick: 'Fri 17 Jan, 19:30',
                home: { n: 'Bayern', s: 'BAY', c: '#c8102e' },
                away: { n: 'Dortmund', s: 'BVB', c: '#f5c400', t: '#0b1220' },
                prob: [55, 22, 23],
                xg: { h: [0, .3, .55, .84, 1.1, 1.38, 1.7, 1.98, 2.3, 2.6], a: [0, .12, .3, .44, .63, .8, .98, 1.18, 1.4, 1.6] },
                stats: [['Possession', 61, 39, '%'], ['Shots / game', 18.3, 12.9, '']],
                form: ['WWWWD', 'WLWDW'],
                lean: 'Home edge, goals', conf: 3,
            },
        ];

        const body     = $('[data-swap]', consoleEl);
        const progress = $('[data-progress]', consoleEl);
        const dotsWrap = $('[data-dots]');
        const pauseBtn = $('[data-pause]');
        const pauseLbl = $('[data-pause-label]');
        const DUR = 7000;
        let index = 0;
        let paused = reduceMotion;  // no auto-rotation for reduced-motion users
        let hoverHold = false;

        const X = (i) => 10 + i * (300 / 9);
        const Y = (v) => 104 - (v / 3) * 91.3;
        const stepPath = (arr) => arr.reduce((acc, v, i) => i === 0 ? `M${X(0)} ${Y(v)}` : `${acc} H${X(i)} V${Y(v)}`, '');

        const setText = (key, val) => $$(`[data-c="${key}"]`, consoleEl).forEach((el) => { el.textContent = val; });
        const fmt = (v, unit) => (Number.isInteger(v) ? v : v.toFixed(1)) + unit;

        const dots = FIXTURES.map((f, i) => {
            const b = d.createElement('button');
            b.type = 'button';
            b.className = 'console__dot';
            b.setAttribute('aria-label', `Show ${f.home.n} vs ${f.away.n}`);
            b.addEventListener('click', () => go(i, true));
            dotsWrap.append(b);
            return b;
        });

        const drawLines = () => {
            $$('[data-xg]', consoleEl).forEach((p) => {
                p.classList.remove('is-drawing');
                void p.getBoundingClientRect();  // restart animation
                if (!reduceMotion) p.classList.add('is-drawing');
            });
        };

        const render = (f) => {
            setText('league', f.league);
            setText('kick', f.kick);
            setText('home', f.home.n);
            setText('away', f.away.n);

            const hc = $('[data-c="hcrest"]', consoleEl);
            const ac = $('[data-c="acrest"]', consoleEl);
            hc.textContent = f.home.s; hc.style.setProperty('--c', f.home.c); hc.style.setProperty('--t', f.home.t || '#fff');
            ac.textContent = f.away.s; ac.style.setProperty('--c', f.away.c); ac.style.setProperty('--t', f.away.t || '#fff');

            ['h', 'd', 'a'].forEach((k, i) => {
                $(`[data-p="${k}"]`, consoleEl).textContent = f.prob[i] + '%';
                $(`[data-pb="${k}"]`, consoleEl).style.flexBasis = f.prob[i] + '%';
            });

            $('[data-xg="h"]', consoleEl).setAttribute('d', stepPath(f.xg.h));
            $('[data-xg="a"]', consoleEl).setAttribute('d', stepPath(f.xg.a));
            $('[data-xg="area"]', consoleEl).setAttribute('d', `${stepPath(f.xg.h)} V104 H${X(0)} Z`);
            setText('hxg', f.xg.h.at(-1).toFixed(2));
            setText('axg', f.xg.a.at(-1).toFixed(2));

            f.stats.forEach(([label, h, a, unit], i) => {
                const total = h + a;
                $(`[data-s="${i}h"]`, consoleEl).textContent = fmt(h, unit);
                $(`[data-s="${i}a"]`, consoleEl).textContent = fmt(a, unit);
                $(`[data-sb="${i}h"]`, consoleEl).style.flexBasis = (h / total * 100) + '%';
                $(`[data-sb="${i}a"]`, consoleEl).style.flexBasis = (a / total * 100) + '%';
            });

            renderPills($('[data-form="h"]', consoleEl), f.form[0]);
            renderPills($('[data-form="a"]', consoleEl), f.form[1]);

            setText('lean', f.lean);
            const conf = $('[data-conf]', consoleEl);
            conf.setAttribute('aria-label', `Confidence ${f.conf} of 5`);
            $$('i', conf).forEach((bar, i) => { bar.style.setProperty('--n', i); bar.classList.toggle('on', i < f.conf); });

            dots.forEach((b, i) => b.setAttribute('aria-current', String(i === index)));
            drawLines();
        };

        const restartProgress = () => {
            progress.classList.remove('is-running');
            void progress.getBoundingClientRect();
            if (!reduceMotion) progress.classList.add('is-running');
        };

        const go = (i, user = false) => {
            index = (i + FIXTURES.length) % FIXTURES.length;
            if (reduceMotion) { render(FIXTURES[index]); return; }
            body.classList.add('is-swapping');
            setTimeout(() => {
                render(FIXTURES[index]);
                body.classList.remove('is-swapping');
            }, 320);
            restartProgress();
            if (user && !paused) syncPause();
        };

        const syncPause = () => {
            const stopped = paused || hoverHold || d.hidden;
            consoleEl.classList.toggle('is-paused', stopped);
            pauseBtn.setAttribute('aria-pressed', String(paused));
            pauseLbl.textContent = paused ? 'Resume rotation' : 'Pause rotation';
        };

        progress.style.setProperty('--dur', DUR + 'ms');
        progress.addEventListener('animationend', () => go(index + 1));

        pauseBtn.addEventListener('click', () => {
            paused = !paused;
            if (!paused && reduceMotion) { go(index + 1); }
            syncPause();
        });
        const wrap = consoleEl.parentElement;
        wrap.addEventListener('pointerenter', () => { hoverHold = true;  syncPause(); });
        wrap.addEventListener('pointerleave', () => { hoverHold = false; syncPause(); });
        wrap.addEventListener('focusin',  () => { hoverHold = true;  syncPause(); });
        wrap.addEventListener('focusout', () => { hoverHold = false; syncPause(); });
        d.addEventListener('visibilitychange', syncPause);

        // Subtle 3D tilt — desktop with a mouse only
        if (!reduceMotion && matchMedia('(hover: hover) and (pointer: fine) and (min-width: 1024px)').matches) {
            wrap.addEventListener('pointermove', (e) => {
                const r = wrap.getBoundingClientRect();
                const px = (e.clientX - r.left) / r.width - 0.5;
                const py = (e.clientY - r.top) / r.height - 0.5;
                consoleEl.style.transform = `rotateY(${px * 5}deg) rotateX(${-py * 5}deg)`;
            });
            wrap.addEventListener('pointerleave', () => { consoleEl.style.transform = ''; });
        }

        render(FIXTURES[0]);
        syncPause();
        // Wait for the hero entrance to finish before the first rotation starts
        setTimeout(restartProgress, reduceMotion ? 0 : 900);
    }


    /* ---------- Results ledger: filter by outcome ---------- */
    const ledger = $('[data-ledger]');
    if (ledger) {
        const rows = $$('tbody tr[data-outcome]', ledger);
        const status = $('[data-filter-status]');
        const buttons = $$('[data-filter]');
        $$('[data-filter-count]').forEach((el) => {
            el.textContent = rows.filter((r) => r.dataset.outcome === el.dataset.filterCount).length;
        });
        buttons.forEach((btn) => btn.addEventListener('click', () => {
            const f = btn.dataset.filter;
            buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
            let shown = 0;
            rows.forEach((r) => {
                const show = f === 'all' || r.dataset.outcome === f;
                r.hidden = !show;
                if (show) {
                    r.style.setProperty('--n', shown++);
                    if (!reduceMotion) r.animate([{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }], { duration: 400, delay: Math.min(shown, 12) * 30, easing: 'cubic-bezier(.16,1,.3,1)', fill: 'backwards' });
                }
            });
            if (status) status.textContent = f === 'all' ? `Showing all ${shown}` : `Showing ${shown} ${f}`;
        }));
    }

    /* ---------- Fixtures: filter by day ---------- */
    const fixtureFilters = $('[data-fixture-filters]');
    if (fixtureFilters) {
        const dayGroups = $$('[data-day-group]');
        const dayButtons = $$('[data-day-filter]', fixtureFilters);
        dayButtons.forEach((btn) => btn.addEventListener('click', () => {
            const day = btn.dataset.dayFilter;
            dayButtons.forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
            dayGroups.forEach((g) => {
                const show = day === 'all' || g.dataset.dayGroup === day;
                g.hidden = !show;
                if (show && !reduceMotion) {
                    g.animate([{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }], { duration: 350, easing: 'cubic-bezier(.16,1,.3,1)', fill: 'backwards' });
                }
            });
        }));
    }

    /* ---------- Contact form: inline validation ---------- */
    $$('form[data-validate]').forEach((form) => {
        const controls = $$('input, select, textarea', form).filter((c) => c.required);
        const fieldOf = (c) => c.closest('.field');
        const errorOf = (c) => { const id = c.getAttribute('aria-describedby'); return id ? d.getElementById(id) : null; };

        const check = (c, show) => {
            const ok = c.checkValidity();
            const field = fieldOf(c);
            const err = errorOf(c);
            if (show) {
                field.classList.toggle('is-invalid', !ok);
                field.classList.toggle('is-valid', ok && c.type !== 'checkbox' && c.value !== '');
                if (ok) c.removeAttribute('aria-invalid'); else c.setAttribute('aria-invalid', 'true');
                if (err) err.textContent = ok ? '' : (c.dataset.msg || c.validationMessage);
            }
            return ok;
        };

        controls.forEach((c) => {
            c.addEventListener('blur', (e) => {
                // Skip when focus moves to the submit button: an error appearing here would shift
                // the button under the pointer and swallow the click. Submit validates everything anyway.
                if (e.relatedTarget && e.relatedTarget.type === 'submit' && form.contains(e.relatedTarget)) return;
                if (c.value !== '' || fieldOf(c).classList.contains('is-invalid')) check(c, true);
            });
            c.addEventListener(c.type === 'checkbox' || c.tagName === 'SELECT' ? 'change' : 'input', () => {
                if (fieldOf(c).classList.contains('is-invalid')) check(c, true);
            });
        });

        form.addEventListener('submit', (e) => {
            const invalid = controls.filter((c) => !check(c, true));
            if (invalid.length) {
                e.preventDefault();
                invalid[0].focus();
                return;
            }
            const btn = $('button[type="submit"]', form);
            if (btn) { btn.setAttribute('aria-busy', 'true'); btn.firstChild.textContent = 'Sending '; }
        });
    });

    /* ---------- Disclaimer: contents state, scroll-spy, reading progress ---------- */
    const toc = $('[data-toc]');
    if (toc) {
        const desktop = matchMedia('(min-width: 1024px)');
        const syncToc = () => { toc.open = desktop.matches; };
        syncToc();
        desktop.addEventListener('change', syncToc);
        toc.addEventListener('toggle', () => { if (desktop.matches && !toc.open) toc.open = true; });
        $$('a', toc).forEach((a) => a.addEventListener('click', () => { if (!desktop.matches) toc.open = false; }));

        const links = new Map($$('a', toc).map((a) => [a.getAttribute('href').slice(1), a]));
        const spy = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (!e.isIntersecting) return;
                links.forEach((a) => a.classList.remove('is-active'));
                links.get(e.target.id)?.classList.add('is-active');
            });
        }, { rootMargin: '-30% 0px -60% 0px' });
        $$('[data-clause]').forEach((c) => spy.observe(c));
    }

    const progress = $('[data-read-progress]');
    if (progress) {
        let ticking = false;
        const update = () => {
            const max = d.documentElement.scrollHeight - innerHeight;
            progress.style.transform = `scaleX(${max > 0 ? Math.min(scrollY / max, 1) : 0})`;
            ticking = false;
        };
        addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
        update();
    }

    /* ---------- Footer year ---------- */
    $$('[data-year]').forEach((el) => { el.textContent = new Date().getFullYear(); });

    /* ---------- Kick off hero entrance ---------- */
    requestAnimationFrame(() => requestAnimationFrame(() => {
        root.classList.add('is-loaded');
        setTimeout(() => $$('.hero [data-count]').forEach(countUp), 650);
    }));
})();

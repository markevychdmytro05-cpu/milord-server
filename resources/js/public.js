// Скрипти публічного сайту (раніше вбудовані в сторінки): тости й форма, меню, підсвітка розділу, акордеон, слайдер тарифів.
(function () {
    var toast = document.getElementById('toast');
    var live = document.getElementById('live-status');
    var timer;

    function announce(message) {
        if (!live || !message) { return; }
        live.textContent = '';
        setTimeout(function () { live.textContent = message; }, 100);
    }

    function showToast(message, type) {
        if (!toast || !message) { return; }
        toast.querySelector('.toast-text').textContent = message;
        toast.classList.toggle('is-error', type === 'error');
        toast.hidden = false;
        requestAnimationFrame(function () { toast.classList.add('is-visible'); });
        clearTimeout(timer);
        timer = setTimeout(hideToast, 10000);
        announce(message);
    }

    function hideToast() {
        if (!toast) { return; }
        toast.classList.remove('is-visible');
        setTimeout(function () { toast.hidden = true; }, 350);
    }

    if (toast) {
        toast.querySelector('.toast-close').addEventListener('click', hideToast);
        // Повідомлення не зникає, поки на ньому курсор або фокус (WCAG 2.2.1).
        toast.addEventListener('mouseenter', function () { clearTimeout(timer); });
        toast.addEventListener('focusin', function () { clearTimeout(timer); });
        toast.addEventListener('mouseleave', function () { timer = setTimeout(hideToast, 4000); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !toast.hidden) { hideToast(); } });
        if (toast.dataset.flash) { showToast(toast.dataset.flash); }
        if (toast.dataset.flashError) { showToast(toast.dataset.flashError, 'error'); }
    }

    function clearErrors(form) {
        form.querySelectorAll('.contact-error').forEach(function (el) { el.remove(); });
        form.querySelectorAll('[aria-invalid]').forEach(function (field) {
            field.removeAttribute('aria-invalid');
            if (field.dataset.hintId) { field.setAttribute('aria-describedby', field.dataset.hintId); } else { field.removeAttribute('aria-describedby'); }
        });
    }

    function setStatus(form, message, isError) {
        var box = form.querySelector('.contact-status');
        if (!box) { return; }
        box.textContent = message || '';
        box.classList.toggle('is-error', !!isError);
        box.hidden = !message;
        announce(message);
    }

    // 419: сторінка пережила сесію. Беремо свіжий CSRF-токен і повторюємо відправку один раз.
    function refreshToken(form) {
        return fetch(location.href, { credentials: 'same-origin' })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                var match = html.match(/name="_token"\s+value="([^"]+)"/);
                if (match) { form.querySelector('[name="_token"]').value = match[1]; }
                return !!match;
            })
            .catch(function () { return false; });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest ? event.target.closest('.contact-form') : null;
        if (!form || !window.fetch) { return; }
        event.preventDefault();

        var button = form.querySelector('button[type=submit]');
        if (button.getAttribute('aria-disabled') === 'true') { return; }
        // aria-disabled, а не disabled: вимкнена кнопка скидає фокус клавіатури на <body>.
        button.setAttribute('aria-disabled', 'true');
        form.classList.add('is-loading');
        clearErrors(form);
        setStatus(form, '');

        function send(retried) {
            return fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (response) { return response.json().catch(function () { return {}; }).then(function (data) { return { response: response, data: data }; }); })
                .then(function (result) {
                    var status = result.response.status;
                    if (result.response.ok) {
                        form.reset();
                        setStatus(form, form.dataset.success || result.data.message);
                    } else if (status === 419 && !retried) {
                        return refreshToken(form).then(function (ok) { return ok ? send(true) : setStatus(form, form.dataset.expired, true); });
                    } else if (status === 422 && result.data.errors) {
                        var first = null;
                        Object.keys(result.data.errors).forEach(function (name) {
                            var field = form.querySelector('[name="' + name + '"]');
                            var wrap = field && field.closest('.contact-field');
                            if (!wrap) { return; }
                            var error = document.createElement('em');
                            error.className = 'contact-error';
                            error.id = 'error-' + name;
                            error.textContent = result.data.errors[name][0];
                            wrap.appendChild(error);
                            field.setAttribute('aria-invalid', 'true');
                            field.setAttribute('aria-describedby', (field.dataset.hintId ? field.dataset.hintId + ' ' : '') + error.id);
                            first = first || field;
                        });
                        setStatus(form, form.dataset.invalid, true);
                        if (first) { first.focus(); }
                    } else if (status === 429) {
                        setStatus(form, form.dataset.tooMany, true);
                    } else {
                        setStatus(form, form.dataset.failed, true);
                    }
                });
        }

        send(false)
            .catch(function () { setStatus(form, form.dataset.network, true); })
            .finally(function () { button.removeAttribute('aria-disabled'); form.classList.remove('is-loading'); });
    });
})();

(function () {
    var root = document.documentElement;
    var nav = document.querySelector('.nav');
    var toggle = document.querySelector('.nav-toggle');
    var links = document.querySelector('.links');
    var mobile = window.matchMedia('(max-width: 1120px)');
    var behind = [document.getElementById('main'), document.querySelector('.site-foot')];

    function setSection(item, open) {
        item.classList.toggle('is-open', open);
        item.querySelectorAll('[data-submenu-toggle]').forEach(function (button) { button.setAttribute('aria-expanded', open ? 'true' : 'false'); });
    }
    function closeSections(except) {
        document.querySelectorAll('.dropdown.is-open').forEach(function (item) { if (item !== except) { setSection(item, false); } });
    }
    function fitMenu() { links.style.setProperty('--menu-h', Math.max(0, window.innerHeight - nav.getBoundingClientRect().bottom) + 'px'); }
    function setMenu(open, how) {
        if (!nav || !toggle || !links) { return; }
        nav.classList.toggle('is-open', open);
        root.classList.toggle('menu-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? toggle.dataset.labelClose : toggle.dataset.labelOpen);
        // Сторінка під повноекранним меню не отримує фокус (WCAG 2.4.3).
        behind.forEach(function (el) { if (el) { el.inert = open && mobile.matches; } });
        if (open) {
            fitMenu();
            // Панель стає видимою з наступного кадру, тож фокус переносимо трохи згодом.
            if (how === 'keyboard') { setTimeout(function () { var first = links.querySelector('a[href], button'); if (first) { first.focus(); } }, 50); }
        } else {
            closeSections(null);
            if (how === 'escape') { toggle.focus(); }
        }
    }

    if (toggle && links) {
        toggle.addEventListener('click', function (event) { setMenu(!nav.classList.contains('is-open'), event.detail === 0 ? 'keyboard' : 'pointer'); });
        window.addEventListener('resize', function () {
            if (!mobile.matches) { setMenu(false); } else if (nav.classList.contains('is-open')) { fitMenu(); }
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        var openItem = document.querySelector('.dropdown.is-open');
        if (openItem) {
            // Перший Esc закриває підменю, другий – усе меню.
            var hadFocus = openItem.contains(document.activeElement);
            setSection(openItem, false);
            if (hadFocus) { openItem.querySelector('[data-submenu-toggle]').focus(); }
            return;
        }
        if (nav && nav.classList.contains('is-open')) { setMenu(false, 'escape'); }
    });
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-submenu-toggle]');
        var item = event.target.closest('.dropdown');
        closeSections(button ? item : null);
        if (button) { setSection(item, !item.classList.contains('is-open')); return; }
        if (nav && nav.classList.contains('is-open') && (!event.target.closest('.nav') || event.target.closest('.links a[href]'))) { setMenu(false); }
    });
    document.addEventListener('focusin', function (event) {
        var openItem = document.querySelector('.dropdown.is-open');
        if (openItem && !openItem.contains(event.target)) { setSection(openItem, false); }
    });

    function onScroll() { if (nav) { nav.classList.toggle('is-scrolled', window.scrollY > 8); } }

    // Підсвічування розділу в меню під час прокрутки: лише для якорів на поточну сторінку (/#pricing на головній).
    var spy = [].slice.call(document.querySelectorAll('.links a[href*="#"]')).map(function (link) {
        var url = new URL(link.href, location.href);
        var target = url.pathname === location.pathname && url.hash.length > 1 ? document.querySelector(url.hash) : null;
        return target ? { link: link, target: target } : null;
    }).filter(Boolean);
    var spyFrame = 0;
    function updateSpy() {
        spyFrame = 0;
        var line = window.innerHeight * 0.35, current = null;
        spy.forEach(function (item) {
            var rect = item.target.getBoundingClientRect();
            if (rect.top <= line && rect.bottom > line) { current = item; }
        });
        spy.forEach(function (item) {
            var on = item === current;
            item.link.classList.toggle('is-active', on);
            if (on) { item.link.setAttribute('aria-current', 'location'); } else { item.link.removeAttribute('aria-current'); }
        });
    }
    if (spy.length) {
        window.addEventListener('scroll', function () { if (!spyFrame) { spyFrame = requestAnimationFrame(updateSpy); } }, { passive: true });
        window.addEventListener('resize', updateSpy);
        updateSpy();
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    // Активний пункт горизонтального перемикача (категорії, місяці) завжди у полі зору.
    document.querySelectorAll('.kb-filters').forEach(function (strip) {
        var active = strip.querySelector('.is-active');
        if (active && strip.scrollWidth > strip.clientWidth) {
            strip.scrollLeft = active.getBoundingClientRect().left - strip.getBoundingClientRect().left + strip.scrollLeft - (strip.clientWidth - active.offsetWidth) / 2;
        }
    });
})();

document.addEventListener('click', function (event) {
    var toggler = event.target.closest('.accordion__toggler');
    if (!toggler) { return; }
    toggler.setAttribute('aria-expanded', toggler.closest('.accordion__item').classList.toggle('is-open'));
});

(function () {
    var root = document.querySelector('[data-plans-slider]');
    if (!root) { return; }
    var track = root.querySelector('.plans--track');
    var buttons = root.closest('section').querySelectorAll('.plans-arrow');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    function step() { var card = track.querySelector('.plan'); return card ? card.offsetWidth + parseFloat(getComputedStyle(track).columnGap || 0) : 300; }
    // aria-disabled замість disabled: інакше на краю слайдера фокус клавіатури падає на <body>.
    function update() {
        buttons[0].setAttribute('aria-disabled', track.scrollLeft <= 2 ? 'true' : 'false');
        buttons[1].setAttribute('aria-disabled', track.scrollLeft + track.clientWidth >= track.scrollWidth - 2 ? 'true' : 'false');
    }
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.getAttribute('aria-disabled') === 'true') { return; }
            track.scrollBy({ left: step() * Number(button.dataset.dir), behavior: reduce.matches ? 'auto' : 'smooth' });
        });
    });
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
})();

// «Замовити» веде на форму заявки: підставляємо назву тарифу, якщо повідомлення ще порожнє.
document.addEventListener('click', function (event) {
    var link = event.target.closest('[data-plan]');
    var message = link && document.querySelector('.contact-form [name="message"]');
    if (message && !message.value) { message.value = link.dataset.prefill.replace(':plan', link.dataset.plan); }
});

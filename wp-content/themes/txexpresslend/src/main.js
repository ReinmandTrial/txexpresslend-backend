const AMOUNT_FORMAT = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const THUMB_SIZE = '1.25rem';

const initLoanSlider = (slider) => {
    const output = document.querySelector(`output[for="${slider.id}"]`);

    const sync = () => {
        const min = Number(slider.min);
        const max = Number(slider.max);
        const ratio = (Number(slider.value) - min) / (max - min);

        slider.style.setProperty(
            '--range-progress',
            `calc(${THUMB_SIZE} / 2 + (100% - ${THUMB_SIZE}) * ${ratio})`,
        );

        if (output) {
            output.textContent = AMOUNT_FORMAT.format(Number(slider.value));
        }
    };

    slider.addEventListener('input', sync);
    sync();
};

document.querySelectorAll('.loan-slider').forEach(initLoanSlider);

/*
 * Quick Loan → loan form.
 * The email must not end up in the URL (server logs, analytics), so the Quick Loan
 * email input has no name and its value travels in sessionStorage. The amount stays
 * in the URL (?amount=500). The loan form is prefilled here rather than in PHP, so its
 * HTML is the same for every visitor and can be cached.
 */
const QUICK_LOAN_EMAIL_KEY = 'txl:quick-loan-email';

const initQuickLoan = (form) => {
    const email = form.querySelector('[data-quick-loan-email]');

    form.addEventListener('submit', () => {
        try {
            sessionStorage.setItem(QUICK_LOAN_EMAIL_KEY, email.value.trim());
        } catch {
            // Storage unavailable (e.g. blocked by the browser): the email just isn't carried over.
        }
    });
};

// Values the server restored after a validation error take precedence over the prefill.
const prefillLoanForm = (form) => {
    const amount = form.querySelector('[data-dropdown-value]');
    const email = form.querySelector('input[name="email"]');
    let storedEmail = null;

    try {
        storedEmail = sessionStorage.getItem(QUICK_LOAN_EMAIL_KEY);
        sessionStorage.removeItem(QUICK_LOAN_EMAIL_KEY);
    } catch {
        // Storage unavailable: nothing to prefill.
    }

    // The dropdown picks the matching option up from the hidden field (see initDropdown).
    if (amount && !amount.value) {
        amount.value = new URLSearchParams(window.location.search).get('amount') ?? '';
    }

    if (email && !email.value && storedEmail) {
        email.value = storedEmail;
    }
};

document.querySelectorAll('[data-quick-loan]').forEach(initQuickLoan);

// Must run before the dropdowns are initialized below.
document.querySelectorAll('[data-loan-form]').forEach(prefillLoanForm);

/*
 * Custom dropdown instead of <select>.
 * The value goes into a hidden input, the open state is kept in the button's
 * aria-expanded, and the highlighted option in data-active: styles for these
 * attributes live in the markup, this code only manages state.
 */
const initDropdown = (root) => {
    const toggle = root.querySelector('[data-dropdown-toggle]');
    const list = root.querySelector('[data-dropdown-list]');
    const label = root.querySelector('[data-dropdown-label]');
    const field = root.querySelector('[data-dropdown-value]');
    const options = [...list.querySelectorAll('[role="option"]')];

    if (!toggle || !list || !label || !field || !options.length) return;

    options.forEach((option, index) => {
        option.id ||= `${list.id}-option-${index}`;
    });

    const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';

    // Focus stays on the button; the "cursor" in the list is tracked via aria-activedescendant.
    let activeIndex = Math.max(
        options.findIndex((option) => option.getAttribute('aria-selected') === 'true'),
        0,
    );

    const highlight = (index) => {
        activeIndex = (index + options.length) % options.length;

        options.forEach((option, i) => option.toggleAttribute('data-active', i === activeIndex));
        list.setAttribute('aria-activedescendant', options[activeIndex].id);
        options[activeIndex].scrollIntoView({ block: 'nearest' });
    };

    const open = () => {
        toggle.setAttribute('aria-expanded', 'true');
        highlight(activeIndex);
    };

    const close = () => {
        toggle.setAttribute('aria-expanded', 'false');
        options.forEach((option) => option.removeAttribute('data-active'));
        list.removeAttribute('aria-activedescendant');
    };

    const select = (index) => {
        activeIndex = index;

        options.forEach((option, i) => option.setAttribute('aria-selected', String(i === index)));
        label.textContent = options[index].textContent.trim();
        field.value = options[index].dataset.value;
        field.dispatchEvent(new Event('change', { bubbles: true }));

        // The placeholder goes away after the first choice: its color depends on this attribute.
        toggle.removeAttribute('data-placeholder');
    };

    // Initial value from the hidden field (Quick Loan prefill or the server after an error).
    // Anything that doesn't match an option is dropped.
    const initial = options.findIndex((option) => option.dataset.value === field.value);

    if (initial >= 0) {
        select(initial);
    } else {
        field.value = '';
    }

    toggle.addEventListener('click', () => (isOpen() ? close() : open()));

    toggle.addEventListener('keydown', (event) => {
        const { key } = event;

        if (key === 'Tab') {
            close();
            return;
        }

        if (!isOpen()) {
            if (key === 'ArrowDown' || key === 'ArrowUp' || key === 'Enter' || key === ' ') {
                event.preventDefault();
                open();
            }
            return;
        }

        const actions = {
            ArrowDown: () => highlight(activeIndex + 1),
            ArrowUp: () => highlight(activeIndex - 1),
            Home: () => highlight(0),
            End: () => highlight(options.length - 1),
            Escape: () => close(),
            Enter: () => {
                select(activeIndex);
                close();
            },
            ' ': () => {
                select(activeIndex);
                close();
            },
        };

        if (actions[key]) {
            event.preventDefault();
            actions[key]();
        }
    });

    options.forEach((option, index) => {
        option.addEventListener('click', () => {
            select(index);
            close();
            toggle.focus();
        });
        option.addEventListener('mousemove', () => highlight(index));
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) close();
    });
};

document.querySelectorAll('[data-dropdown]').forEach(initDropdown);

/*
 * Mobile menu. The state is data-open on the panel; styles for it live in the
 * markup. While the menu is open, the background doesn't scroll.
 */
const initMobileMenu = () => {
    const menu = document.querySelector('[data-menu]');
    const toggle = document.querySelector('[data-menu-open]');
    const close = menu?.querySelector('[data-menu-close]');

    if (!menu || !toggle || !close) return;

    const isOpen = () => menu.hasAttribute('data-open');

    // While the menu is open the rest of the page is inert, otherwise Tab moves under the panel.
    const shell = [...document.body.children].filter((element) => element !== menu);
    const setShellInert = (inert) =>
        shell.forEach((element) => element.toggleAttribute('inert', inert));

    const openMenu = () => {
        menu.setAttribute('data-open', '');
        toggle.setAttribute('aria-expanded', 'true');
        document.documentElement.classList.add('overflow-hidden');
        setShellInert(true);
        close.focus();
    };

    const closeMenu = ({ restoreFocus = true } = {}) => {
        menu.removeAttribute('data-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('overflow-hidden');
        setShellInert(false);
        if (restoreFocus) toggle.focus();
    };

    toggle.addEventListener('click', openMenu);
    close.addEventListener('click', () => closeMenu());

    // Following a link closes the menu, but there's no need to move focus.
    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => closeMenu({ restoreFocus: false }));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) closeMenu();
    });

    // Desktop has its own navigation: close the menu when the window gets wider,
    // otherwise scrolling stays locked on desktop.
    window.matchMedia('(width >= 64rem)').addEventListener('change', (event) => {
        if (event.matches && isOpen()) closeMenu({ restoreFocus: false });
    });
};

initMobileMenu();

/*
 * Time trap against bots: on submit a form reports how long the page has been open.
 * Bots submit instantly, and the server rejects anything faster than a couple of
 * seconds (see inc/forms.php). The field is filled here, not in PHP, so it works
 * with cached pages.
 */
document.querySelectorAll('input[name="txl_elapsed"]').forEach((input) => {
    input.form?.addEventListener('submit', () => {
        input.value = String(Math.round(performance.now()));
    });
});

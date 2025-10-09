(function (window, document) {
    'use strict';

    const FluxUI = {};
    const state = {
        mutationObserver: null,
        timers: new Map(),
        timerInterval: null,
        timerLastTick: null,
        resourceTicker: null,
        resourceLastTick: null,
        resourceConfig: null,
        resourceElements: null,
        resourceToastFlags: new Set(),
        toastContainer: null
    };

    const RESOURCE_NAMES = {
        l1: 'Wood',
        l2: 'Clay',
        l3: 'Iron',
        l4: 'Crop'
    };

    function ensureBodyClass() {
        if (document && document.body && !document.body.classList.contains('flux-enabled')) {
            document.body.classList.add('flux-enabled');
        }
    }

    function formatNumber(value) {
        try {
            const lang = document.documentElement ? document.documentElement.lang || undefined : undefined;
            return new Intl.NumberFormat(lang, { maximumFractionDigits: 0 }).format(Math.max(0, Math.floor(value)));
        } catch (error) {
            return String(Math.max(0, Math.floor(value)));
        }
    }

    FluxUI.formatNumber = formatNumber;

    function ensureToastContainer() {
        if (state.toastContainer && document.body.contains(state.toastContainer)) {
            return state.toastContainer;
        }
        const container = document.createElement('div');
        container.className = 'flux-toast-container';
        document.body.appendChild(container);
        state.toastContainer = container;
        return container;
    }

    function closeToast(toast, immediate) {
        if (!toast) {
            return;
        }
        const remove = function () {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        };
        if (immediate) {
            remove();
        } else {
            toast.style.animationPlayState = 'paused';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px) scale(0.98)';
            window.setTimeout(remove, 180);
        }
    }

    function toast(message, type = 'info', options = {}) {
        if (!message) {
            return null;
        }
        const container = ensureToastContainer();
        const toastEl = document.createElement('div');
        toastEl.className = ['flux-toast', type ? `flux-toast--${type}` : ''].join(' ').trim();

        const contentWrapper = document.createElement('div');
        contentWrapper.style.display = 'flex';
        contentWrapper.style.flexDirection = 'column';
        contentWrapper.style.gap = '0.2rem';

        if (options.title || type) {
            const titleEl = document.createElement('div');
            titleEl.className = 'flux-toast__title';
            titleEl.textContent = options.title || (type === 'success' ? 'Success' : type === 'warning' ? 'Warning' : type === 'danger' ? 'Alert' : 'Notice');
            contentWrapper.appendChild(titleEl);
        }

        const bodyEl = document.createElement('div');
        bodyEl.className = 'flux-toast__body';
        bodyEl.textContent = message;
        contentWrapper.appendChild(bodyEl);

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'flux-toast__close';
        closeButton.innerHTML = '&times;';
        closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeToast(toastEl, true);
        });

        toastEl.appendChild(contentWrapper);
        toastEl.appendChild(closeButton);

        container.appendChild(toastEl);

        window.setTimeout(function () {
            closeToast(toastEl, false);
        }, options.duration || 5000);

        return toastEl;
    }

    FluxUI.toast = toast;

    function stylizeButtons(root) {
        const scope = root instanceof Element ? root : document;
        scope.querySelectorAll('button:not(.flux-button)').forEach(function (button) {
            button.classList.add('flux-button');
        });
    }

    function stylizeInputs(root) {
        const scope = root instanceof Element ? root : document;
        scope.querySelectorAll('input:not(.flux-input)').forEach(function (input) {
            if (!input.type || ['text', 'password', 'email', 'number', 'search', 'tel', 'url', 'time', 'date'].includes(input.type)) {
                if (!input.classList.contains('flux-input') && !input.classList.contains('checkbox') && !input.classList.contains('radio')) {
                    input.classList.add('flux-input');
                }
            }
        });
        scope.querySelectorAll('textarea:not(.flux-input)').forEach(function (textarea) {
            textarea.classList.add('flux-input');
        });
        scope.querySelectorAll('select:not(.flux-input)').forEach(function (select) {
            select.classList.add('flux-input');
        });
    }

    function stylizeForms(root) {
        const scope = root instanceof Element ? root : document;
        scope.querySelectorAll('form:not(.flux-form)').forEach(function (form) {
            form.classList.add('flux-form');
        });
    }

    function stylizeModals(root) {
        const scope = root instanceof Element ? root : document;
        scope.querySelectorAll('.dialogWrapper:not(.flux-modal)').forEach(function (modal) {
            modal.classList.add('flux-modal');
        });
    }

    FluxUI.apply = function (root) {
        stylizeButtons(root);
        stylizeInputs(root);
        stylizeForms(root);
        stylizeModals(root);
    };

    function initialiseResourceElements() {
        const resources = window.FluxResourceConfig || window.resources;
        const valueIds = ['l1', 'l2', 'l3', 'l4'];
        const elements = {};
        valueIds.forEach(function (id) {
            const valueEl = document.getElementById(id);
            const barEl = document.getElementById(`lbar${id.substring(1)}`);
            if (valueEl) {
                elements[id] = {
                    value: valueEl,
                    bar: barEl,
                    container: valueEl.closest('.stockBarButton')
                };
            }
        });
        const freeCrop = document.getElementById('stockBarFreeCrop');
        if (freeCrop) {
            elements.freeCrop = {
                value: freeCrop
            };
        }
        if (!Object.keys(elements).length || !resources || !resources.production) {
            return null;
        }
        const config = {
            production: {},
            storage: {},
            maxStorage: {},
            freeCrop: resources.production.l5 !== undefined ? Number(resources.production.l5) : 0
        };
        ['l1', 'l2', 'l3', 'l4'].forEach(function (key) {
            config.production[key] = Number(resources.production[key] || 0);
            config.storage[key] = Number(resources.storage && resources.storage[key] !== undefined ? resources.storage[key] : resources[`l${key}`] || 0);
            config.maxStorage[key] = Number(resources.maxStorage && resources.maxStorage[key] !== undefined ? resources.maxStorage[key] : resources[`max${key}`] || 0);
        });
        state.resourceConfig = config;
        state.resourceElements = elements;
        return config;
    }

    function updateResourceToast(resourceKey, percent, production, amount) {
        if (!RESOURCE_NAMES[resourceKey]) {
            return;
        }
        const fullKey = `${resourceKey}-full`;
        if (percent >= 95 && production >= 0) {
            if (!state.resourceToastFlags.has(fullKey)) {
                toast(`${RESOURCE_NAMES[resourceKey]} storage is almost full.`, 'warning', { title: 'Resource warning' });
                state.resourceToastFlags.add(fullKey);
            }
        } else if (percent < 90 && state.resourceToastFlags.has(fullKey)) {
            state.resourceToastFlags.delete(fullKey);
        }
        if (resourceKey === 'l4') {
            const deficitKey = `${resourceKey}-deficit`;
            if (production < 0 && amount <= 0) {
                if (!state.resourceToastFlags.has(deficitKey)) {
                    toast('Crop production is negative. Your troops may starve.', 'danger', { title: 'Crop alert' });
                    state.resourceToastFlags.add(deficitKey);
                }
            } else if (production >= 0 && state.resourceToastFlags.has(deficitKey)) {
                state.resourceToastFlags.delete(deficitKey);
            }
        }
    }

    function startResourceTicker() {
        if (state.resourceTicker || !initialiseResourceElements()) {
            return;
        }
        const config = state.resourceConfig;
        const elements = state.resourceElements;
        const current = {
            l1: config.storage.l1,
            l2: config.storage.l2,
            l3: config.storage.l3,
            l4: config.storage.l4
        };
        if (elements.freeCrop && !Number.isNaN(config.freeCrop)) {
            elements.freeCrop.value.textContent = formatNumber(config.freeCrop);
        }
        state.resourceLastTick = performance.now();
        state.resourceTicker = window.setInterval(function () {
            if (!document.body) {
                return;
            }
            const now = performance.now();
            const deltaSeconds = Math.min(5, (now - (state.resourceLastTick || now)) / 1000);
            state.resourceLastTick = now;
            ['l1', 'l2', 'l3', 'l4'].forEach(function (key) {
                const productionPerHour = config.production[key] || 0;
                const capacity = config.maxStorage[key] || 0;
                const element = elements[key];
                if (!element || !element.value) {
                    return;
                }
                const changePerSecond = productionPerHour / 3600;
                current[key] = Math.max(0, current[key] + changePerSecond * deltaSeconds);
                if (capacity > 0) {
                    current[key] = Math.min(current[key], capacity);
                }
                element.value.textContent = formatNumber(current[key]);
                element.value.setAttribute('data-flux-value', current[key].toFixed(2));
                const percent = capacity > 0 ? Math.min(100, Math.max(0, (current[key] / capacity) * 100)) : 0;
                if (element.bar) {
                    element.bar.style.width = `${percent}%`;
                }
                if (element.container) {
                    if (percent >= 95 && productionPerHour >= 0) {
                        element.container.classList.add('flux-resource-alert');
                    } else {
                        element.container.classList.remove('flux-resource-alert');
                    }
                    if (productionPerHour < 0 && current[key] <= 0) {
                        element.container.classList.add('flux-resource-danger');
                    } else {
                        element.container.classList.remove('flux-resource-danger');
                    }
                }
                updateResourceToast(key, percent, productionPerHour, current[key]);
            });
        }, 1000);
    }

    function registerTimer(element) {
        if (!element || state.timers.has(element)) {
            return;
        }
        const rawValue = parseInt(element.getAttribute('value'), 10);
        if (Number.isNaN(rawValue)) {
            return;
        }
        const direction = (element.getAttribute('counting') || 'down').toLowerCase();
        const initial = direction === 'up' ? Math.max(0, rawValue) : Math.max(0, rawValue);
        const timerData = {
            element: element,
            direction: direction === 'up' ? 'up' : 'down',
            remaining: direction === 'up' ? initial : initial,
            lastRendered: Math.max(0, initial),
            done: direction !== 'up' && initial <= 0,
            toastSent: false,
            options: {
                message: element.getAttribute('data-toast-message'),
                title: element.getAttribute('data-toast-title'),
                type: element.getAttribute('data-toast-type')
            }
        };
        state.timers.set(element, timerData);
    }

    function scanTimers(root) {
        const scope = root instanceof Element ? root : document;
        scope.querySelectorAll('.timer[counting]').forEach(registerTimer);
    }

    function formatTimer(seconds) {
        const positive = Math.max(0, Math.floor(seconds));
        const hours = Math.floor(positive / 3600);
        const minutes = Math.floor((positive % 3600) / 60);
        const secs = positive % 60;
        const hh = hours.toString();
        const mm = minutes < 10 ? `0${minutes}` : `${minutes}`;
        const ss = secs < 10 ? `0${secs}` : `${secs}`;
        return `${hh}:${mm}:${ss}`;
    }

    function updateTimers(deltaSeconds) {
        const completed = [];
        state.timers.forEach(function (timer, element) {
            if (!document.body.contains(element)) {
                completed.push(element);
                return;
            }
            if (timer.direction === 'down') {
                if (!timer.done) {
                    timer.remaining = Math.max(0, timer.remaining - deltaSeconds);
                    if (timer.remaining <= 0) {
                        timer.done = true;
                    }
                }
            } else {
                timer.remaining += deltaSeconds;
            }
            const floored = Math.floor(timer.remaining);
            if (floored !== timer.lastRendered) {
                element.textContent = formatTimer(timer.remaining);
                element.setAttribute('value', String(floored));
                timer.lastRendered = floored;
            }
            if (timer.direction === 'down' && timer.remaining <= 0) {
                element.classList.add('timer--finished');
                element.classList.remove('timer--critical');
                if (!timer.toastSent) {
                    const message = timer.options.message || 'One of your timers has completed.';
                    const title = timer.options.title || 'Timer finished';
                    const type = timer.options.type || 'success';
                    toast(message, type, { title: title, duration: 6000 });
                    timer.toastSent = true;
                }
            } else if (timer.direction === 'down' && timer.remaining <= 60) {
                element.classList.add('timer--critical');
            } else {
                element.classList.remove('timer--critical');
            }
        });
        completed.forEach(function (element) {
            state.timers.delete(element);
        });
    }

    function startTimerTicker() {
        if (state.timerInterval) {
            return;
        }
        scanTimers(document);
        state.timerLastTick = performance.now();
        state.timerInterval = window.setInterval(function () {
            if (!state.timers.size) {
                state.timerLastTick = performance.now();
                return;
            }
            const now = performance.now();
            const deltaSeconds = Math.min(5, (now - (state.timerLastTick || now)) / 1000);
            state.timerLastTick = now;
            updateTimers(deltaSeconds);
        }, 1000);
    }

    function startMutationObserver() {
        if (state.mutationObserver) {
            return;
        }
        if (!document.body) {
            return;
        }
        state.mutationObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    FluxUI.apply(node);
                    if (node.classList.contains('timer')) {
                        registerTimer(node);
                    }
                    scanTimers(node);
                    if (!state.resourceConfig) {
                        initialiseResourceElements();
                    }
                });
            });
        });
        state.mutationObserver.observe(document.body, { childList: true, subtree: true });
    }

    function initialise() {
        if (document.readyState === 'loading') {
            return;
        }
        ensureBodyClass();
        ensureToastContainer();
        FluxUI.apply(document);
        startResourceTicker();
        startTimerTicker();
        startMutationObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initialise();
        });
    } else {
        initialise();
    }

    window.FluxUI = FluxUI;
})(window, document);

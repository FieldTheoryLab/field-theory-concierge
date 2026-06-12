(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        document.querySelectorAll('[data-ftc-app]').forEach(initFTC);
    });

    function initFTC(app) {
        const settings = window.ftcData && ftcData.settings ? ftcData.settings : {};
        const nonce = window.ftcData ? ftcData.nonce : '';
        const ajaxUrl = window.ftcData ? ftcData.ajaxUrl : '/wp-admin/admin-ajax.php';

        const intro = app.querySelector('[data-ftc-intro]');
        const chat = app.querySelector('[data-ftc-chat]');
        const nameForm = app.querySelector('[data-ftc-name-form]');
        const nameInput = app.querySelector('[data-ftc-name-input]');
        const chatForm = app.querySelector('[data-ftc-chat-form]');
        const chatInput = app.querySelector('[data-ftc-chat-input]');
        const stream = app.querySelector('[data-ftc-stream]');
        const themeBtn = app.querySelector('[data-ftc-theme]');
        const menuBtn = app.querySelector('[data-ftc-menu]');
        const modal = app.querySelector('[data-ftc-modal]');
        const menuContent = app.querySelector('[data-ftc-menu-content]');
        const resetBtn = app.querySelector('[data-ftc-reset]');
        const clearBtn = app.querySelector('[data-ftc-clear]');
        const typeTarget = app.querySelector('[data-ftc-typewriter]') || app.querySelector('.ftc-intro-copy');

        let visitor = localStorage.getItem('ftcVisitorName') || '';
        let menuLoaded = false;
        let introTypingDone = false;
        let introTimer = null;

        const introText = [
            'Hi.',
            'Welcome to our website.',
            '',
            "We're Field Theory Lab.",
            'We help businesses with website technology, analytics, AI automation, and digital marketing.',
            '',
            "What's your name?"
        ].join('\n');

        const returnText = visitor
            ? [
                'Welcome back, ' + visitor + '.',
                '',
                'What would you like to explore today?'
            ].join('\n')
            : introText;

        const storedTheme = localStorage.getItem('ftcTheme');
        setTheme(storedTheme || 'dark');

        if (visitor) {
            prepareIntroTyping(returnText);
            runIntroTyping(returnText, function () {
                setTimeout(function () {
                    beginChat(false);
                }, 650);
            });
        } else {
            prepareIntroTyping('');
            runIntroTyping(introText, function () {
                introTypingDone = true;
                nameInput.focus();
            });
        }

        nameForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const name = nameInput.value.trim();

            if (!name) return;

            visitor = name;

            localStorage.setItem(
                'ftcVisitorName',
                name
            );

            nameInput.blur();

            beginChat(true);
        });

        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitPrompt(chatInput.value);
        });

        app.addEventListener('click', function (e) {
            const prompt = e.target.closest('[data-prompt]');

            if (prompt) {
                e.preventDefault();
                closeMenu();
                submitPrompt(prompt.getAttribute('data-prompt'));
            }
        });

        clearBtn.addEventListener('click', function () {
            stream.innerHTML = '';
            chatInput.value = '';
            addAssistantMessage(welcomeHTML(false));
        });

        resetBtn.addEventListener('click', function () {
            localStorage.removeItem('ftcVisitorName');

            visitor = '';

            clearTimeout(introTimer);

            app.classList.remove('is-chat');
            chat.classList.remove('is-visible');
            intro.classList.add('is-visible');

            stream.innerHTML = '';
            nameInput.value = '';

            prepareIntroTyping('');

            setTimeout(function () {
                runIntroTyping(introText, function () {
                    introTypingDone = true;
                    nameInput.focus();
                });
            }, 250);
        });

        themeBtn.addEventListener('click', function () {
            setTheme(app.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });

        menuBtn.addEventListener('click', openMenu);

        modal.querySelectorAll('[data-ftc-close]').forEach(function (btn) {
            btn.addEventListener('click', closeMenu);
        });

        function prepareIntroTyping(text) {
            if (!typeTarget) return;

            typeTarget.innerHTML =
                '<div class="ftc-typewriter">' +
                formatTypedText(text) +
                '<span class="ftc-cursor">|</span>' +
                '</div>';
        }

        function runIntroTyping(text, done) {
            if (!typeTarget) {
                if (typeof done === 'function') done();
                return;
            }

            clearTimeout(introTimer);

            let i = 0;
            let output = '';

            typeTarget.innerHTML =
                '<div class="ftc-typewriter"><span class="ftc-cursor">█</span></div>';

            const typeBox = typeTarget.querySelector('.ftc-typewriter');

            function tick() {
                if (i >= text.length) {
                    typeBox.innerHTML =
                        formatTypedText(output) +
                        '<span class="ftc-cursor">█</span>';

                    if (typeof done === 'function') done();

                    return;
                }

                output += text.charAt(i);

                typeBox.innerHTML =
                    formatTypedText(output) +
                    '<span class="ftc-cursor">█</span>';

                i++;

                const char = text.charAt(i - 1);

                let delay = 20;

                if (char === '.') delay = 260;
                if (char === '\n') delay = 120;
                if (char === ',') delay = 90;

                introTimer = setTimeout(tick, delay);
            }

            tick();
        }

        function formatTypedText(text) {
            return escapeHTML(text).replace(/\n/g, '<br>');
        }

        function setTheme(theme) {
            app.setAttribute('data-theme', theme);
            localStorage.setItem('ftcTheme', theme);

            if (themeBtn) {
                themeBtn.textContent = theme === 'dark' ? '☾' : '☀';
            }
        }

        function beginChat(withIntro) {
            clearTimeout(introTimer);

            intro.classList.remove('is-visible');
            app.classList.add('is-chat');

            setTimeout(function () {
                chat.classList.add('is-visible');

                if (!stream.children.length) {
                    addAssistantMessage(welcomeHTML(withIntro));
                }

                setTimeout(function () {
                    chatInput.focus();
                }, 250);

            }, 420);
        }
        function welcomeHTML(first) {

            const name = escapeHTML(visitor || 'friend');

            if (first) {

                return `
        <div class="ftc-onboarding">

            <div class="ftc-answer-heading ftc-typewriter"
                 data-typewriter="Welcome, ${name}.">
                Welcome, ${name}.
            </div>

            <div class="ftc-answer-body">

                <p class="ftc-lead">
                    Most organizations come to us because something feels disconnected.
                    The website isn't converting. Marketing isn't measurable.
                    Analytics are messy. AI sounds interesting but isn't producing results.
                </p>

                <p>
                    Field Theory Lab helps connect your website, marketing,
                    analytics, and technology into a system that actually
                    supports growth.
                </p>

            </div>

            <div class="ftc-video-wrap">
                <video autoplay muted loop playsinline>
                    <source src="/wp-content/uploads/field-theory-intro.mp4" type="video/mp4">
                </video>
            </div>

            <div class="ftc-section-label">
                HOW WE HELP
            </div>

            <div class="ftc-service-grid">

                <div class="ftc-service-card">
                    <strong>Clarify</strong>
                    <p>
                        Understand what's happening across your website,
                        marketing, analytics, and customer journey.
                    </p>
                </div>

                <div class="ftc-service-card">
                    <strong>Build</strong>
                    <p>
                        Websites, dashboards, applications,
                        landing pages, and digital systems.
                    </p>
                </div>

                <div class="ftc-service-card">
                    <strong>Measure</strong>
                    <p>
                        GA4, reporting, KPI frameworks,
                        conversion tracking, and data storytelling.
                    </p>
                </div>

                <div class="ftc-service-card">
                    <strong>Grow</strong>
                    <p>
                        SEO, digital marketing,
                        AI automation, and optimization.
                    </p>
                </div>

            </div>

            <div class="ftc-section-label">
                EXPLORE FIELD THEORY
            </div>

            <div class="ftc-followup-row">

                <button class="ftc-followup"
                        data-prompt="Show me your work">
                    Show me your work
                </button>

                <button class="ftc-followup"
                        data-prompt="What services do you offer?">
                    What services do you offer?
                </button>

                <button class="ftc-followup"
                        data-prompt="Can you help with analytics?">
                    Can you help with analytics?
                </button>

                <button class="ftc-followup"
                        data-prompt="How can AI help my business?">
                    How can AI help my business?
                </button>

                <button class="ftc-followup"
                        data-prompt="How can we work together?">
                    Let's talk
                </button>

            </div>

        </div>
        `;
            }

            return `
    <div class="ftc-answer-heading">
        Welcome back, ${name}.
    </div>

    <div class="ftc-answer-body">
        What would you like to explore today?
    </div>
    `;
        }
        function submitPrompt(raw) {
            const term = (raw || '').trim();

            if (!term) return;

            if (!app.classList.contains('is-chat')) {
                beginChat(false);
            }

            addUserMessage(term);

            chatInput.value = '';

            const thinking = addThinking();

            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'ftc_answer',
                    nonce: nonce,
                    term: term
                }).toString()
            })

                .then(function (r) {
                    return r.json();
                })

                .then(function (data) {
                    setTimeout(function () {
                        thinking.remove();

                        if (data && data.success) {
                            addAssistantMessage(data.data.html);
                        } else {
                            addAssistantMessage(fallbackHTML());
                        }

                    }, 520);
                })

                .catch(function () {
                    thinking.remove();
                    addAssistantMessage(fallbackHTML());
                });
        }

        function addUserMessage(text) {
            const el = document.createElement('div');

            el.className = 'ftc-message ftc-user';

            el.innerHTML =
                '<div class="ftc-bubble">' +
                escapeHTML(text) +
                '</div>';

            stream.appendChild(el);

            scrollToBottom();
        }

        function addAssistantMessage(html) {

            const el = document.createElement('div');

            el.className = 'ftc-message ftc-assistant';

            el.innerHTML =
                '<div class="ftc-card">' +
                html +
                '</div>';

            stream.appendChild(el);

            // Initialize any typewriter headlines
            el.querySelectorAll('.ftc-typewriter').forEach(typewriterElement);

            // Scroll answer into view
            requestAnimationFrame(() => {

                stream.scrollTo({
                    top: Math.max(0, el.offsetTop - 220),
                    behavior: 'smooth'
                });

            });

        }

        function typewriterElement(el) {

            if (el.dataset.initialized) return;

            el.dataset.initialized = 'true';

            const text =
                el.dataset.text ||
                el.textContent.trim();

            el.textContent = '';

            let i = 0;

            const speed = 24;

            const timer = setInterval(() => {

                el.textContent += text.charAt(i);

                i++;

                if (i >= text.length) {

                    clearInterval(timer);

                    el.classList.add('is-complete');

                }

            }, speed);

        }

        function addThinking() {

            const el = document.createElement('div');

            el.className = 'ftc-message ftc-assistant';

            el.innerHTML =
                '<div class="ftc-card">' +
                '<div class="ftc-thinking">' +
                '<span>Field Theory is thinking</span>' +
                '<i></i><i></i><i></i>' +
                '</div>' +
                '</div>';

            stream.appendChild(el);

            scrollToBottom();

            return el;
        }
        function fallbackHTML() {
            return '' +
                '<div class="ftc-answer-heading">Good question.</div>' +
                '<div class="ftc-answer-body">' +
                '<p>I can help with Field Theory services, portfolio, analytics, AI automation, web technology, marketing, and contact information.</p>' +
                '</div>' +
                promptHTML(['Portfolio', 'Services', 'Contact']);
        }

        function promptHTML(items) {
            return '' +
                '<div class="ftc-followups">' +
                '<div class="ftc-section-label">Try asking</div>' +
                '<div class="ftc-followup-row">' +
                items.map(function (item) {
                    return '' +
                        '<button type="button" class="ftc-followup" data-prompt="' + escapeAttr(item) + '">' +
                        escapeHTML(item) +
                        '</button>';
                }).join('') +
                '</div>' +
                '</div>';
        }

        function openMenu() {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');

            if (menuLoaded) return;

            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'ftc_menu',
                    nonce: nonce
                }).toString()
            })

                .then(function (r) {
                    return r.json();
                })

                .then(function (data) {
                    menuLoaded = true;
                    menuContent.innerHTML =
                        data.success
                            ? data.data.html
                            : '<p>Menu unavailable.</p>';
                })

                .catch(function () {
                    menuContent.innerHTML =
                        '<p>Menu unavailable.</p>';
                });
        }

        function closeMenu() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }

        function scrollToBottom() {
            requestAnimationFrame(function () {
                stream.scrollTop = stream.scrollHeight;
            });
        }

        function escapeHTML(str) {
            return String(str).replace(/[&<>"']/g, function (s) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[s];
            });
        }

        function escapeAttr(str) {
            return escapeHTML(str).replace(/`/g, '&#096;');
        }
    }
})();
(function () {
    'use strict';

    var config = window.CHATBOT_CONFIG || {};
    var baseUrl = (config.baseUrl || '').replace(/\/$/, '');
    var isCustomer = config.isCustomer === true;
    var apiUrl = baseUrl + '/api/ai/chat.php';
    var cartAddUrl = baseUrl + '/api/cart/add.php';

    var DEFAULT_SUGGESTIONS = [
        'Tìm điện thoại dưới 10 triệu',
        'Điện thoại chơi game tốt',
        'Điện thoại chụp ảnh đẹp',
        'So sánh hai điện thoại'
    ];

    var state = {
        open: false,
        sending: false,
        history: [],
        conversationId: newConversationId(),
        lastMessage: ''
    };

    var els = {};

    function newConversationId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'c-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
    }

    function make(tag, className, text) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined && text !== null) {
            node.textContent = text;
        }
        return node;
    }

    var ICONS = {
        chat: '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        close: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
        send: '<svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>'
    };

    function buildShell() {
        els.fab = make('button', 'chatbot-fab');
        els.fab.type = 'button';
        els.fab.id = 'chatbotFab';
        els.fab.setAttribute('aria-label', 'Mở hộp thoại tư vấn AI');
        els.fab.setAttribute('aria-expanded', 'false');
        els.fab.innerHTML = ICONS.chat;
        els.fab.insertAdjacentElement('beforeend', make('span', 'chatbot-fab-label', 'Tư vấn AI'));

        els.panel = make('section', 'chatbot-panel');
        els.panel.id = 'chatbotPanel';
        els.panel.setAttribute('role', 'dialog');
        els.panel.setAttribute('aria-label', 'Tư vấn AI MCHAphone');
        els.panel.hidden = true;

        var head = make('header', 'chatbot-head');
        var headInfo = make('div', 'chatbot-head-info');
        headInfo.appendChild(make('strong', null, 'MCHA Assistant'));
        headInfo.appendChild(make('span', null, 'Tư vấn AI • MCHAphone Store'));
        var closeBtn = make('button', 'chatbot-close');
        closeBtn.type = 'button';
        closeBtn.setAttribute('aria-label', 'Đóng hộp thoại');
        closeBtn.innerHTML = ICONS.close;
        head.appendChild(headInfo);
        head.appendChild(closeBtn);
        els.closeBtn = closeBtn;

        els.messages = make('div', 'chatbot-messages');
        els.messages.setAttribute('aria-live', 'polite');

        els.error = make('div', 'chatbot-error');
        els.error.hidden = true;
        els.errorText = make('span', null, 'Không kết nối được máy chủ.');
        var retryBtn = make('button', 'chatbot-retry', 'Thử lại');
        retryBtn.type = 'button';
        els.error.appendChild(els.errorText);
        els.error.appendChild(retryBtn);
        els.retryBtn = retryBtn;

        els.suggestions = make('div', 'chatbot-suggestions');

        els.form = make('form', 'chatbot-input');
        els.form.setAttribute('aria-label', 'Gửi câu hỏi cho trợ lý AI');
        els.input = make('input');
        els.input.type = 'text';
        els.input.placeholder = 'Nhập câu hỏi của bạn...';
        els.input.maxLength = 500;
        els.input.autocomplete = 'off';
        els.input.setAttribute('aria-label', 'Nội dung câu hỏi');
        var sendBtn = make('button', 'chatbot-send');
        sendBtn.type = 'submit';
        sendBtn.setAttribute('aria-label', 'Gửi tin nhắn');
        sendBtn.innerHTML = ICONS.send;
        els.sendBtn = sendBtn;
        els.form.appendChild(els.input);
        els.form.appendChild(sendBtn);

        els.panel.appendChild(head);
        els.panel.appendChild(els.messages);
        els.panel.appendChild(els.error);
        els.panel.appendChild(els.suggestions);
        els.panel.appendChild(els.form);

        document.body.appendChild(els.fab);
        document.body.appendChild(els.panel);
    }

    function setOpen(open) {
        state.open = open;
        els.panel.hidden = !open;
        els.fab.setAttribute('aria-expanded', open ? 'true' : 'false');
        els.fab.classList.toggle('is-open', open);
        if (open) {
            els.input.focus();
            scrollToBottom();
        }
    }

    function scrollToBottom() {
        els.messages.scrollTop = els.messages.scrollHeight;
    }

    function addBubble(role, text) {
        var bubble = make('div', 'chatbot-bubble ' + (role === 'user' ? 'is-user' : 'is-bot'), text);
        els.messages.appendChild(bubble);
        scrollToBottom();
        return bubble;
    }

    function addTyping() {
        var wrap = make('div', 'chatbot-bubble is-bot is-typing');
        wrap.setAttribute('aria-label', 'Trợ lý đang nhập');
        for (var i = 0; i < 3; i++) {
            wrap.appendChild(make('span', 'chatbot-dot'));
        }
        els.messages.appendChild(wrap);
        scrollToBottom();
        return wrap;
    }

    function renderSuggestions(labels) {
        els.suggestions.textContent = '';
        (labels || []).forEach(function (label) {
            var chip = make('button', 'chatbot-chip', label);
            chip.type = 'button';
            chip.addEventListener('click', function () {
                if (!state.sending) {
                    sendMessage(label);
                }
            });
            els.suggestions.appendChild(chip);
        });
    }

    function formatPrice(value) {
        var number = Math.round(Number(value) || 0);
        return number.toLocaleString('vi-VN') + ' đ';
    }

    function buildCard(product) {
        var card = make('article', 'chatbot-card');

        var thumbWrap = make('div', 'chatbot-card-thumb');
        if (product.image) {
            var img = document.createElement('img');
            img.src = product.image;
            img.alt = product.name || '';
            img.loading = 'lazy';
            img.addEventListener('error', function () {
                thumbWrap.textContent = '';
                thumbWrap.appendChild(make('span', 'chatbot-card-placeholder', 'PHONE'));
            });
            thumbWrap.appendChild(img);
        } else {
            thumbWrap.appendChild(make('span', 'chatbot-card-placeholder', 'PHONE'));
        }
        card.appendChild(thumbWrap);

        var body = make('div', 'chatbot-card-body');
        body.appendChild(make('span', 'chatbot-card-brand', product.brand_name || ''));
        body.appendChild(make('h4', 'chatbot-card-name', product.name || ''));

        var priceRow = make('div', 'chatbot-card-price');
        priceRow.appendChild(make('strong', null, product.price_display || formatPrice(product.min_price)));
        priceRow.appendChild(make('span', product.in_stock ? 'chatbot-stock in' : 'chatbot-stock out',
            product.in_stock ? 'Còn hàng' : 'Hết hàng'));
        body.appendChild(priceRow);

        if (product.spec_summary) {
            body.appendChild(make('p', 'chatbot-card-specs', product.spec_summary));
        }
        if (product.reason) {
            body.appendChild(make('p', 'chatbot-card-reason', product.reason));
        }

        var actions = make('div', 'chatbot-card-actions');
        var detailLink = make('a', 'chatbot-btn chatbot-btn-outline', 'Xem chi tiết');
        detailLink.href = product.detail_url || (baseUrl + '/product/detail.php?id=' + product.id);
        actions.appendChild(detailLink);

        if (isCustomer && product.in_stock && product.variant_id) {
            var addBtn = make('button', 'chatbot-btn chatbot-btn-primary', 'Thêm vào giỏ');
            addBtn.type = 'button';
            addBtn.dataset.variantLabel = product.variant_label || '';
            addBtn.addEventListener('click', function () {
                addToCart(addBtn, product);
            });
            actions.appendChild(addBtn);
        }
        body.appendChild(actions);
        card.appendChild(body);
        return card;
    }

    function renderProducts(products) {
        if (!products || !products.length) {
            return;
        }
        var grid = make('div', 'chatbot-products');
        products.forEach(function (product) {
            grid.appendChild(buildCard(product));
        });
        els.messages.appendChild(grid);
        scrollToBottom();
    }

    function addToCart(button, product) {
        var original = button.textContent;
        button.disabled = true;
        button.textContent = 'Đang thêm...';

        var body = new URLSearchParams();
        body.append('variant_id', String(product.variant_id));
        body.append('quantity', '1');

        fetch(cartAddUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () {
                throw new Error('Phản hồi không hợp lệ từ máy chủ.');
            });
        }).then(function (result) {
            if (!result.success) {
                throw new Error(result.message || 'Không thể thêm vào giỏ hàng.');
            }
            button.textContent = 'Đã thêm ✓';
            updateCartCount(result.data && result.data.cart);
            addBubble('bot', 'Đã thêm ' + product.name + (button.dataset.variantLabel ? ' (' + button.dataset.variantLabel + ')' : '') + ' vào giỏ hàng.');
        }).catch(function (error) {
            button.disabled = false;
            button.textContent = original;
            addBubble('bot', 'Có lỗi khi thêm vào giỏ: ' + (error && error.message ? error.message : 'vui lòng thử lại.'));
        });
    }

    function updateCartCount(cart) {
        var link = document.querySelector('.cart-link span');
        if (!link || !cart) {
            return;
        }
        var total = 0;
        Object.keys(cart).forEach(function (key) {
            total += Number(cart[key]) || 0;
        });
        link.textContent = String(total);
    }

    function showError(message) {
        els.errorText.textContent = message || 'Không kết nối được máy chủ. Vui lòng thử lại.';
        els.error.hidden = false;
    }

    function request(message) {
        state.sending = true;
        els.input.disabled = true;
        els.sendBtn.disabled = true;
        els.error.hidden = true;
        state.lastMessage = message;

        var typing = addTyping();

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=UTF-8' },
            body: JSON.stringify({
                message: message,
                history: state.history.slice(-10),
                conversation_id: state.conversationId
            })
        }).then(function (response) {
            return response.json().catch(function () {
                throw new Error('Máy chủ trả về dữ liệu không hợp lệ.');
            }).then(function (result) {
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Máy chủ từ chối yêu cầu (' + response.status + ').');
                }
                return result;
            });
        }).then(function (result) {
            var data = result.data || {};
            typing.remove();
            addBubble('bot', data.message || 'Xin lỗi, mình chưa hiểu câu hỏi. Bạn thử diễn đạt lại nhé.');
            renderProducts(data.products);
            renderSuggestions((data.actions || []).map(function (action) {
                return action.label;
            }).filter(function (label) {
                return !!label;
            }));
            state.history.push({ role: 'user', content: message });
            state.history.push({ role: 'assistant', content: data.message || '' });
            state.history = state.history.slice(-10);
        }).catch(function (error) {
            typing.remove();
            showError(error && error.message ? error.message : 'Không kết nối được máy chủ. Vui lòng thử lại.');
        }).then(function () {
            state.sending = false;
            els.input.disabled = false;
            els.sendBtn.disabled = false;
            els.input.focus();
            scrollToBottom();
        });
    }

    function sendMessage(message) {
        var text = (message || '').trim();
        if (!text || state.sending) {
            return;
        }
        addBubble('user', text);
        els.input.value = '';
        request(text);
    }

    function bindEvents() {
        els.fab.addEventListener('click', function () {
            setOpen(!state.open);
        });
        els.closeBtn.addEventListener('click', function () {
            setOpen(false);
            els.fab.focus();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && state.open) {
                setOpen(false);
            }
        });
        els.form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendMessage(els.input.value);
        });
        els.retryBtn.addEventListener('click', function () {
            if (state.lastMessage && !state.sending) {
                request(state.lastMessage);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        buildShell();
        bindEvents();
        addBubble('bot', 'Xin chào! Mình là trợ lý AI của MCHAphone Store. Mình có thể tư vấn điện thoại theo ngân sách, nhu cầu, kiểm tra giá - tồn kho và so sánh các máy cho bạn.');
        renderSuggestions(DEFAULT_SUGGESTIONS);
    });
})();

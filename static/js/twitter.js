document.addEventListener("DOMContentLoaded", () => {
    // 滚动处理
    const scrollElement = document.querySelector('.js-scroll');
    let lastScrollPosition = 0;
    let isScrolled = false;

    const handleScroll = () => {
        const currentScrollPosition = window.scrollY || window.pageYOffset;

        if (!scrollElement) {
            return; // 如果元素不存在，直接返回
        }

        // 向下滚动且未标记为 scrolled
        if (currentScrollPosition > lastScrollPosition && !isScrolled) {
            scrollElement.classList.add('scrolled');
            isScrolled = true;
        }

        // 滚动到顶部附近时重置状态
        if (currentScrollPosition <= 50) {
            scrollElement.classList.remove('scrolled');
            isScrolled = false;
        }

        lastScrollPosition = currentScrollPosition;
    };

    const throttle = (func, delay) => {
        let timeoutId;
        let lastCallTime = 0;

        return (...args) => {
            const now = Date.now();
            const timeSinceLastCall = now - lastCallTime;

            if (timeSinceLastCall < delay) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    lastCallTime = now;
                    func(...args);
                }, delay - timeSinceLastCall);
            } else {
                lastCallTime = now;
                func(...args);
            }
        };
    };

    window.addEventListener('scroll', throttle(handleScroll, 100));

    // 下滑隐藏，上滑显示
    const autohideElements = document.querySelectorAll('.js-autohide');
    if (autohideElements.length) {
        let lastScrollTop = 0;
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const direction = scrollTop < lastScrollTop ? 'up' : 'down';
            autohideElements.forEach(el => {
                el.classList.remove(`scrolled-${direction === 'up' ? 'down' : 'up'}`);
                el.classList.add(`scrolled-${direction}`);
            });
            lastScrollTop = scrollTop;
        });
    }

    // 点击不关闭下拉菜单
    document.body.addEventListener('click', (e) => {
        if (e.target.matches('[data-stopPropagation]')) {
            e.stopPropagation();
        }
    });

    // 刷新页面
    const jRefresh = document.querySelector('.js-refresh');
    if (jRefresh) {
        jRefresh.addEventListener('click', () => window.location.reload());
    }

    // 后退
    const jBack = document.querySelector('.js-back');
    if (jBack) {
        jBack.addEventListener('click', () => {
            if (!document.referrer) {
                window.location.href = "/";
            } else {
                window.history.back();
            }
        });
    }

    // 回到页面顶部
    document.querySelectorAll('.js-top').forEach(element => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // 点击刷新验证码
    const resetCaptchaElement = document.getElementById('resetCaptcha');
    if (resetCaptchaElement) {
        resetCaptchaElement.addEventListener('click', () => {
            this.src = '/auth/captcha?' + Math.random();
        });
    }


    // 初始化提示框
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltipTriggerEl => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 设置 sidebar-col 高度
    const primaryCol = document.querySelector('.primary-col');
    const sidebarCol = document.querySelector('.sidebar-col');
    if (primaryCol && sidebarCol) {
        sidebarCol.style.height = `${primaryCol.offsetHeight}px`;
    }

    // 点击响应整行
    document.querySelectorAll('.js-tap').forEach(element => {
        element.addEventListener('click', (e) => {
            const href = element.getAttribute('href') || element.getAttribute('data-href');
            if (e.target.nodeName === 'IMG' || e.target.nodeName === 'I' || e.target.nodeName === 'CANVAS') return;
            if (e.ctrlKey) {
                window.open(href);
            } else {
                window.location = href;
            }
        });
    });

    // 自适应高度的 textarea
    const jTextarea = document.querySelectorAll('.js-textarea');
    if (jTextarea.length) {
        jTextarea.forEach(textarea => {
            textarea.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = `${this.scrollHeight}px`;
            });
            textarea.addEventListener('keyup', function () {
                const content = this.value.length;
                const submitButtons = document.querySelectorAll('.submitButton');
                submitButtons.forEach(button => {
                    button.disabled = !(content > 0 && content <= 140);
                });
            });
        });
    }
});

// 动态创建并显示 Toast
function toast(message, type = 'dark', delay = 2000) {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.classList.add('toast-container');
        document.body.appendChild(toastContainer);
    }

    const toastElement = document.createElement('div');
    toastElement.classList.add('toast', 'rounded-top-0', 'text-center');
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');

    const toastBody = document.createElement('div');
    toastBody.classList.add('toast-body');
    toastBody.textContent = message;

    toastElement.appendChild(toastBody);
    if (type) {
        toastElement.classList.add(`text-bg-${type}`);
    }
    toastContainer.appendChild(toastElement);

    const toast = new bootstrap.Toast(toastElement, { delay });

    toastElement.addEventListener('show.bs.toast', () => {
        toastElement.classList.add('show');
    });

    toast.show();

    toastElement.addEventListener('hide.bs.toast', () => {
        toastElement.classList.add('hiding');
    });

    toastElement.addEventListener('hidden.bs.toast', () => {
        toastContainer.removeChild(toastElement);
        if (toastContainer.children.length === 0) {
            document.body.removeChild(toastContainer);
        }
    });
}

// 高亮当前链接
function highlightActiveLink(activeLink) {
    const element = document.querySelector(`a[data-active="${activeLink}"]`);
    element?.classList.add('active');
}

// 更新图标类名
function updateIconClassOnActive(selector, fromClass, toClass) {
    document.querySelectorAll(`${selector}.active`).forEach(appLink => {
        const iconElement = appLink.querySelector(`.${fromClass}`);
        if (iconElement) {
            iconElement.classList.replace(fromClass, toClass);
        }
    });
}

// 计算字符长度（中文算2个字节）
const getStringLength = (str) => {
    let realLength = 0;
    const len = str.length;
    for (let charIndex = 0; charIndex < len; charIndex++) {
        const charCode = str.charCodeAt(charIndex);
        // 判断是否为中文字符
        realLength += (charCode >= 0x4E00 && charCode <= 0x9FFF) ? 2 : 1;
    }
    return realLength;
};

document.addEventListener("DOMContentLoaded", () => {
    let lastScrollPosition = 0;
    let scrolled = false; // 引入一个标志位来跟踪是否已经添加了 'scrolled' 类

    function handleScroll() {
        let currentScrollPosition = window.scrollY || window.pageYOffset; // 兼容更多浏览器
        const scrollElement = document.querySelector('.js-scroll');

        if (scrollElement) {
            if (currentScrollPosition > lastScrollPosition && !scrolled) {
                // 向下滚动并且之前没有滚动过（即scrolled为false）
                scrollElement.classList.add('scrolled');
                scrolled = true; // 标记为已滚动
            }

            if (currentScrollPosition <= 80) {
                // 无论滚动方向如何，只要当前滚动位置小于等于50就移除 'scrolled' 类
                scrollElement.classList.remove('scrolled');
                scrolled = false; // 标记为未滚动
            }

            lastScrollPosition = currentScrollPosition;
        }
    }

    // 使用节流函数来限制scroll事件的处理频率
    function throttle(func, delay) {
        let lastCall = 0;
        return function (...args) {
            const now = new Date().getTime();
            if (now - lastCall < delay) return;
            lastCall = now;
            return func(...args);
        };
    }

    window.addEventListener('scroll', throttle(handleScroll, 100));

    // 下滑隐藏，上滑显示
    if (document.querySelector('.js-autohide')) {
        var lastScrollTop = 0;
        window.addEventListener('scroll', function () {
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var direction = scrollTop < lastScrollTop ? 'up' : 'down';
            document.querySelectorAll('.js-autohide').forEach(el => {
                el.classList.remove(`scrolled-${direction === 'up' ? 'down' : 'up'}`);
                el.classList.add(`scrolled-${direction}`);
            });
            lastScrollTop = scrollTop;
        });
    }

    // 点击不关闭下拉菜单
    document.body.addEventListener('click', function (e) {
        if (e.target.matches('[data-stopPropagation]')) {
            e.stopPropagation();
        }
    });

    // 刷新页面，只为第一个.js-refresh元素添加事件监听器
    let jRefresh = document.querySelector('.js-refresh');
    if (jRefresh) {
        jRefresh.addEventListener('click', () => window.location.reload());
    }

    // 后退, 没有来源页面信息的时候, 改成首页URL地址
    let jBack = document.querySelector('.js-back');
    if (jBack) {
        jBack.addEventListener('click', () => {
            if (document.referrer === "") {
                window.location.href = "/";
            } else {
                window.history.back();
            }
        });
    }

    // 回到页面顶部
    document.querySelectorAll('.js-top').forEach((element) => {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });

    // 点击刷新验证码
    const resetCaptchaElement = document.getElementById('resetCaptcha');
    if (resetCaptchaElement) {
        resetCaptchaElement.addEventListener('click', function () {
            this.src = '/auth/captcha?' + Math.random();
        });
    }

    /**
     * 初始化提示框 bootstrap tooltips
     */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 把 sidebar-col 高度设置为 primary-col 的值
    const primaryCol = document.querySelector('.primary-col');
    const sidebarCol = document.querySelector('.sidebar-col');
    if (primaryCol && sidebarCol) {
        const primaryColHeight = primaryCol.offsetHeight;
        sidebarCol.style.height = primaryColHeight + 'px';
    }

    // 点击响应整行
    document.querySelectorAll('.js-tap').forEach(function (element) {
        element.addEventListener('click', function (e) {
            var href = this.getAttribute('href') || this.getAttribute('data-href');
            // 图片不响应
            if (e.target.nodeName === 'IMG') return true;
            // 操作按钮不响应
            if (e.target.nodeName === 'I') return true;
            // GIF动画不响应
            if (e.target.nodeName === 'CANVAS') return true;
            if (e.ctrlKey) {
                window.open(href);
                return false;
            } else {
                window.location = href;
            }
        });
    });

    // 遍历每一个 textarea 自适应高度
    const jTextarea = document.querySelectorAll('.js-textarea');
    if (jTextarea) {
        jTextarea.forEach(function (e) {
            // 为其添加 input 事件监听器
            e.addEventListener('input', function () {
                // 将 textarea 的高度设置为 auto，以便它可以根据内容自动调整大小
                this.style.height = 'auto';
                // 计算 textarea 的 scrollHeight，这将返回内容所需的最小高度
                this.style.height = `${this.scrollHeight}px`;
            });
            e.addEventListener('keyup', function () {
                let content = this.value.length;
                if (content > 0 && content <= 140) {
                    // 选择所有包含 .submitButton 类名的元素并使它们可用
                    document.querySelectorAll('.submitButton').forEach(function (button) {
                        button.disabled = false;
                    });
                } else {
                    // 选择所有包含 .submitButton 类名的元素并禁用它们
                    document.querySelectorAll('.submitButton').forEach(function (button) {
                        button.disabled = true;
                    });
                }
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
        toastElement.classList.add(`text-bg-${type}`); // 添加背景类
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

    toastElement.addEventListener('hidden.bs.toast', function () {
        toastContainer.removeChild(toastElement);
        if (toastContainer.children.length === 0) {
            document.body.removeChild(toastContainer);
        }
    });
}

/**
 * 高亮当前链接
 * @param {string} activeLink - 需要高亮的链接对应的 data-active 值
 */
function highlightActiveLink(activeLink) {
    // 选择具有特定 data-active 值的 <a> 元素
    const element = document.querySelector(`a[data-active="${activeLink}"]`);

    // 如果元素存在，则添加 'active' 类
    element?.classList.add('active');
}

// 更新图标类名
function updateIconClassOnActive(selector, fromClass, toClass) {
    const appLinks = document.querySelectorAll(`${selector}.active`);
    appLinks.forEach(appLink => {
        const iconElement = appLink.querySelector(`.${fromClass}`);
        if (iconElement) {
            iconElement.classList.replace(fromClass, toClass);
        }
    });
}

// 计算字符长度（中文算2个字节）
const getStringLength = (str) => {
    let realLength = 0;
    const len = str.length; // 缓存字符串长度，避免重复计算
    for (let i = 0; i < len; i++) {
        const charCode = str.charCodeAt(i);
        // 使用位运算优化条件判断
        realLength += (charCode - 0x4E00) >>> 0 <= 0x9FFF - 0x4E00 ? 2 : 1;
    }
    return realLength;
};
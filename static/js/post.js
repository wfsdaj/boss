document.addEventListener("DOMContentLoaded", function () {
    // 点赞
    const likeButtons = document.querySelectorAll('.js-like');

    likeButtons.forEach(likeButton => {
        likeButton.addEventListener('click', async function () {
            const postId = this.getAttribute('data-pid');
            const likesNumElement = document.querySelector(`.js-likesNum${postId}`);
            const iconHeartElement = this.querySelector('.icon-thumbs-up');
            const iconHeartFillElement = this.querySelector('.icon-thumbs-up-fill');

            // 获取当前点赞数
            let currentLikes = parseInt(likesNumElement.textContent.trim(), 10) || 0;

            try {
                const response = await fetch(`/like/love/${postId}`);
                const result = await response.json();

                if (result.status === 'success') {
                    const isLiked = likeButton.classList.toggle('liked');

                    // 更新点赞数
                    currentLikes += isLiked ? 1 : -1;
                    likesNumElement.textContent = currentLikes;

                    // 更新按钮的提示文本
                    likeButton.setAttribute('data-bs-original-title', isLiked ? '取消赞' : '赞同');

                    // 切换图标状态
                    iconHeartElement.classList.toggle('hidden', isLiked); // 点赞时隐藏空心图标
                    iconHeartFillElement.classList.toggle('hidden', !isLiked); // 点赞时显示实心图标
                } else {
                    toast(result.message);
                }
            } catch (error) {
                toast('Error');
            }
        });
    });

    // 收藏
    var jFavs = document.querySelectorAll('.js-fav');

    jFavs.forEach(jFav => {
        jFav.addEventListener('click', function () {
            let pid = jFav.getAttribute("data-pid");
            var favNumSelector = `.js-favNum${pid}`;
            var favNumElement = jFav.parentElement.querySelector(favNumSelector);
            var postActionFavElement = jFav.parentElement.querySelector('.post-action-fav'); // 关联到当前按钮的父元素或其他适当的选择器
            var iconFavFillElement = jFav.parentElement.querySelector('.icon-fav-fill'); // 同上
            var iconFavElement = jFav.parentElement.querySelector('.icon-fav'); // 同上

            let numTextContent = favNumElement.textContent.trim();
            let num = 0;

            if (numTextContent !== '') {
                num = parseInt(numTextContent, 10); // 尝试将非空文本转换为整数
            }

            fetch(`/fav/add/${pid}`)
                .then((response) => response.json())
                .then((res) => {
                    if (res.status === 'success') {
                        if (jFav.classList.contains("favorited")) {
                            num--;
                            postActionFavElement.setAttribute('data-bs-original-title', '收藏');
                            iconFavFillElement.classList.remove('icon-fav-fill');
                            iconFavFillElement.classList.add('icon-fav');
                        } else {
                            num++;
                            postActionFavElement.setAttribute('data-bs-original-title', '取消收藏');
                            iconFavElement.classList.remove('icon-fav');
                            iconFavElement.classList.add('icon-fav-fill');
                        }

                        jFav.classList.toggle('favorited');
                        favNumElement.textContent = num;
                    } else {
                        toast(res.message);
                    }
                });
        });
    });

    // 删除帖子
    // 绑定点击事件到document上，通过事件冒泡来捕获动态生成的元素的事件
    document.addEventListener('click', function (event) {
        // 检查被点击的元素是否有'jsDelBtn'这个ID
        if (event.target.id === 'jsDelBtn') {
            event.preventDefault();

            // 获取被点击的按钮
            var button = event.target;
            var postId = button.dataset['postId'];

            if (!postId) {
                toast('未提供帖子ID');
                return; // 如果没有提供帖子ID，则退出函数
            }

            // 禁用删除按钮并显示加载指示器
            button.disabled = true;
            button.innerText = '删除中...';

            fetch(`/post/del/${postId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ post_id: postId })
            })
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success') {
                        toast(res.message);
                        setTimeout(function () {
                            window.location.href = "/";
                        }, 2000);
                    } else {
                        toast(res.message);
                    }
                    // 重新启用删除按钮并恢复原始文本
                    button.disabled = false;
                    button.innerText = '确认删除';
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    // 重新启用删除按钮并恢复原始文本
                    button.disabled = false;
                    button.innerText = '确认删除';
                });
        }
    });

    // 置顶帖子
    // const jPin = document.getElementById("js-pin");

    // jPin.addEventListener('click', function (event) {
    //     event.preventDefault();
    //     // 获取被点击的按钮
    //     var button = event.target;
    //     var postId = button.dataset['postId'];

    //     if (!postId) {
    //         toast('未提供帖子ID');
    //         return; // 如果没有提供帖子ID，则退出函数
    //     }

    //     fetch(`/post/pin/${postId}`, {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json'
    //         },
    //         body: JSON.stringify({ post_id: postId })
    //     })
    //         .then(response => response.json())
    //         .then(res => {
    //             if (res.status === 'success') {
    //                 toast(res.message);
    //             } else {
    //                 toast(res.message);
    //             }
    //             // 重新启用删除按钮并恢复原始文本
    //             button.disabled = false;
    //         })
    //         .catch(error => {
    //             console.error('Fetch Error:', error);
    //             // 重新启用删除按钮并恢复原始文本
    //             button.disabled = false;
    //         });
    // });

    // 未登录跳转
    const jumpElements = document.querySelectorAll('.js-jump');

    jumpElements.forEach(element => {
        element.addEventListener('click', e => {
            e.preventDefault();

            // 显示提示信息
            if (typeof toast === 'function') {
                toast('正在跳转至登录页面...');
            }

            // 延迟跳转
            setTimeout(() => {
                window.location.href = "/login";
            }, 1500);
        });
    });
});

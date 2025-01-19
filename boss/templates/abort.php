<!doctype html>
<html lang="zh-cn">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-size: 1rem;
            line-height: 1.5;
            color: #a0aec0;
            text-align: center;
            background-color: #f7fafc
        }

        a {
            color: #1a73e8;
            text-decoration: none;
        }

        h1 {
            padding: 0;
            margin: 0;
            line-height: 1.25;
            font-size: 6rem;
            font-weight: 400;
            color: #a0aec0
        }

        p {
            padding: 0;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .message {
            margin: 150px auto 0 auto;
            padding: 1rem;
            width: 380px;
            word-break: break-word;
            text-align: center;
        }

        .btn {
            display: inline-block;
            min-width: 100px;
            margin-top: 2rem;
            padding: .75rem 1rem;
            font-size: 1rem;
            color: #fff;
            background-color: #212529;
            border-radius: 4rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: color .15s ease-in-out, background-color .15s ease-in-out, box-shadow .15s ease-in-out
        }

        .btn:hover {
            background-color: #1aa179;
        }
    </style>
</head>

<body>
    <div class="message">
        <h1><?= (int)$code; ?></h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="javascript:;" class="btn" id="js-back">返回</a>
    </div>
    <script>
        const backButton = document.getElementById('js-back');
        backButton.addEventListener('click', function(event) {
            event.preventDefault();

            // 检查来源网址并决定回退或重定向到首页
            if (document.referrer === "") {
                window.location.href = "/";
            } else {
                window.history.back();
            }
        });
    </script>
</body>

</html>
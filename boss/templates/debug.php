<?php defined('ROOT_PATH') || exit() ?>
<!doctype html>
<html lang="zh-cn">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Debug</title>
    <style>
        .exception {
            margin: 150px auto 0 auto;
            padding: 2rem 2rem 1rem 2rem;
            max-width: 600px;
            background-color: #fff;
            border-top: 5px solid #dc3545;
            word-break: break-word;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15)
        }
        h1 {
            margin: 0 0 1.5rem 0;
            font-size: 2rem;
            line-height: 1.25;
            font-weight: 600;
            color: #332F51
        }
        .text {
            font-size: 1.125rem;
            line-height: 1.25;
            color: #332F51
        }
    </style>
</head>

<body>
    <div class="exception">
        <h1>Debug</h1>
        <p class="text"><?= $this->getMessage(); ?></p>
    </div>
</body>

</html>
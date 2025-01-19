<?php defined('ROOT_PATH') || exit() ?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug</title>
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
            color: #212529;
            text-align: left;
            background-color: #fff
        }
        pre {
            display: block;
            margin-top: 0;
            margin-bottom: 1rem;
            overflow: auto;
            font-size: .9375em;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .exception {
            margin: 150px auto 0 auto;
            padding: 2rem 2rem 1rem 2rem;
            max-width: 800px;
            background-color: #fff;
            border-top: 5px solid #dc3545;
            word-break: break-word;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15)
        }

        .e-text {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            line-height: 1.25;
            font-weight: 600;
            color: #332F51
        }

        .e-file {
            padding: 0;
            margin: 0 0 1rem 0;
            font-size: 1rem;
            color: #666
        }

        .line {
            display: block;
            margin: 0;
            padding: 4px 0;
            width: 100%;
            font-size: 0.875rem;
        }

        .index {
            color: #757575;
            padding: 2px 4px;
            margin-right: 8px;
        }

        #target {
            padding: 8px 0;
            color: #9f3a38;
            background-color: #ffe8e6;
            font-weight: 700;
        }

        #target .index {
            color: #9f3a38;
            font-weight: normal;
        }

        .tab {
            padding-left: 32px;
        }
    </style>
</head>

<body>
    <div class="exception">
        <p class="e-text"><?= $errorInfo['message'] ?></p>
        <pre><?= $errorInfo['source_code'] ?></pre>
        <p class="e-file"><?= realpath($errorInfo['file']) ?></p>
    </div>
</body>

</html>
<?php
$transferer = new \base\Transferer;
$current_url = url_current();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transferer->process_post();
    die();
}
?>

<!doctype html>
<html lang="zh-cn">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>安装 SQL 文件</title>
    <link rel="stylesheet" href="<?= url_full() ?>/css/bootstrap.css">
</head>

<body>
    <main>
        <div class="my-5 text-center">
            <div class="col-lg-6 p-4 mx-auto">
            <?php
            $info = '<p>在 ' . APP_NAME . ' 模块目录中找到以下 SQL 文件：</p>';
            $info .= '<ul class="list-unstyled">';

            foreach ($files as $file) {

                //display last segment only
                $bits = explode('/', $file);
                $target_file = $bits[count($bits) - 1];

                $filesize = filesize($file); // bytes
                $filesize = round($filesize / 1024, 2); // kilobytes with two digits
                $info .= '<li class="border-top border-bottom py-4 d-flex justify-content-between align-items-center"> ' . $target_file . '&nbsp;&nbsp;&nbsp;&nbsp;' . $filesize . ' KB';
                if ($filesize > 1000) {
                    $info .= '<button class="danger" onclick="explainTooBig(\'' . $target_file . '\', \'' . $file . '\')">太大了！</button></li>';
                } else {

                    //check for dangerous SQL
                    $file_contents = file_get_contents($file);
                    $all_clear = $transferer->check_sql($file_contents);

                    if ($all_clear === true) {
                        $info .= '<button type="button" class="btn btn-outline-secondary px-3 rounded-2" onclick="viewSql(\'' . $file . '\', false)">查看</button></li>';
                    } else {
                        $info .= '<button type="button" class="btn btn-warning" onclick="viewSql(\'' . $file . '\', true)">可疑的</button></li>';
                    }
                }
            }
            $info .= '</ul>';
            ?>

            <h1 id="headline" class="h2 fw-bold mb-3">找到文件</h1>
            <div id="info" class="lead mb-4"><?= $info ?></div>

            <script type="text/javascript">
                var targetFile = '';
                var sqlCode = '';

                function viewSql(file, warning) {

                    document.getElementById("headline").innerHTML = '读取 SQL';
                    document.getElementById("info").innerHTML = '';

                    var params = {
                        controllerPath: file,
                        action: 'viewSql'
                    }

                    var http = new XMLHttpRequest()
                    http.open('POST', '<?= $current_url ?>')
                    http.setRequestHeader('Content-type', 'application/json')
                    http.send(JSON.stringify(params)) // Make sure to stringify
                    http.onload = function() {
                        // Do whatever with response
                        sqlCode = http.responseText;
                        drawShowSQLPage(http.responseText, file, warning);
                    }

                }

                function drawShowSQLPage(sql, file, warning) {

                    targetFile = file;

                    if (warning === true) {
                        alert("检测到此 SQL 文件中嵌入了一些潜在的危险代码。要格外小心！");
                    }

                    <?php
                    $show_sql_content = '<p>SQL 文件的内容如下：</p>';
                    $show_sql_content .= '<p>';
                    $show_sql_content .= '<a href="' . $current_url . '"><button type="button" class="btn btn-secondary px-3">返回</button></a>';
                    $show_sql_content .= '<button type="button" class="btn btn-danger px-3 ms-3" onclick="drawConfDelete()">删除</button>';
                    $show_sql_content .= '<button type="button" class="btn btn-primary px-3 ms-3" onclick="drawConfRun()">运行 SQL</button>';
                    $show_sql_content .= '</p>';
                    $show_sql_content .= '<div><textarea class="form-control font-monospace fs-6" rows="18" id="sql-preview"></textarea></div>';
                    ?>
                    document.getElementById("headline").innerHTML = '查看 SQL';
                    document.getElementById("info").innerHTML = '<?= $show_sql_content ?>';
                    document.getElementById("sql-preview").innerHTML = sql;
                }

                function explainTooBig(target_file, filePath) {

                    targetFile = filePath;

                    <?php
                    $page_content = '<p>对于自动数据库设置，Sql 的文件大小限制为 1MB (1,000kb).</p>';
                    $page_content .= '<button type="button" class="btn btn-danger px-3" onclick="deleteSqlFile()">删除文件</button>';
                    $page_content .= '<a href="' . $current_url . '"><button type="button" class="btn btn-secondary px-3">返回</button></a>';
                    $page_content .= '</p>';
                    $page_content .= '<div></div>';
                    ?>

                    document.getElementById("headline").innerHTML = 'SQL 文件太大了！';
                    document.getElementById("info").innerHTML = '文件 ' + target_file + '，太大了。 <?= $page_content ?>';
                    document.getElementById("sql-preview").innerHTML = sql;
                }

                function drawConfDelete() {

                    <?php
                    $extra_conf_content = '<p class="text-danger fw-bold">确定删除吗？</p>';
                    $extra_conf_content .= ' <a href="' . $current_url . '"><butto  type="button" class="btn btn-secondary px-3">取消</button></a>';
                    $extra_conf_content .= '<button type="button" class="btn btn-danger px-3 ms-3" onclick="deleteSqlFile()">删除文件</button>';
                    $extra_conf_content .= '</p>';
                    ?>

                    document.getElementById("headline").innerHTML = '<span class="danger">删除文件</span>';
                    document.getElementById("info").innerHTML = '<p>您将要删除一个SQL文件。</p><div class="border-top border-bottom py-4 mb-4">位置: ' + targetFile + '</div><?= $extra_conf_content ?>';

                }

                function drawConfRun() {

                    sqlCode = document.getElementById("sql-preview").value;

                    <?php
                    $run_conf_content = '<p class="text-danger fw-bold">执行成功后，为了安全会自动删除该文件。</p>';
                    $run_conf_content .= ' <a href="' . $current_url . '"><button type="button" class="btn btn-secondary px-3 ms-3">返回</button></a>';
                    $run_conf_content .= ' <button type="button" class="btn btn-outline-secondary px-3 ms-3" onclick="previewSql()">预览 SQL</button>';
                    $run_conf_content .= '<button type="button" class="btn btn-primary px-3 ms-3" onclick="runSql()">我了解风险，执行 SQL</button>';
                    $run_conf_content .= '</p>';
                    $run_conf_content .= '<div><textarea class="form-control font-monospace fs-6" rows="18" id="sql-preview" style="display: none;" disabled></textarea></div>';
                    ?>

                    document.getElementById("headline").innerHTML = '运行 SQL';
                    document.getElementById("info").innerHTML = '<p>您将要运行SQL文件。</p><div class="border-top border-bottom py-4 mb-4">位置： ' + targetFile + '</div><?= $run_conf_content ?>';
                }

                function runSql() {
                    document.getElementById("headline").innerHTML = '请稍候';
                    document.getElementById("info").innerHTML = '<p class="blink">执行 SQL...</p>';

                    <?php
                    $finished_content = '<p><button type="button" class="btn btn-primary" onclick="clickOkay()">好的</button></p>';
                    ?>

                    var params = {
                        sqlCode,
                        action: 'runSql',
                        targetFile
                    }

                    var http = new XMLHttpRequest()
                    http.open('POST', '<?= $current_url ?>')
                    http.setRequestHeader('Content-type', 'application/json')
                    http.send(JSON.stringify(params))

                    http.onload = function() {

                        var response = http.responseText;
                        var status = http.status;

                        if (status === 403) {
                            document.getElementById("headline").innerHTML = 'Finished';
                            response = response.replace('Finished.', '');
                            document.getElementById("info").innerHTML = '<p>请删除该文件, ' + response + '.</p>';
                            document.getElementById("info").innerHTML += '<p>删除文件后, 按 \'Okay\'</p><?= $finished_content ?>';
                        } else {

                            if (http.responseText === 'Finished.') {
                                document.getElementById("headline").innerHTML = 'Finished';
                                document.getElementById("info").innerHTML = '<p>SQL 文件已成功处理</p><?= $finished_content ?>';
                            } else {

                                <?php
                                $error_content = '<p>哎呀，好像出了点差错。</p>';
                                $error_content = '<p><a href="' . $current_url . '"><button type="button" class="btn btn-secondary">返回</button></a></p>';
                                $error_content .= '<p>SQL 文件生成了以下响应：</p>';
                                $error_content .= '<p><textarea class="form-control" id="error-msg" rows="18"></textarea></p>';
                                ?>

                                document.getElementById("headline").innerHTML = '<span class="danger">SQL 错误</span>';
                                document.getElementById("info").innerHTML = '<?= $error_content ?>';
                                document.getElementById("error-msg").innerHTML = http.responseText;
                            }

                        }

                    }

                }

                function deleteSqlFile() {
                    document.getElementById("headline").innerHTML = '请稍等';
                    document.getElementById("info").innerHTML = '<p class="blink">删除 SQL...</p>';

                    var params = {
                        targetFile,
                        action: 'deleteFile'
                    }

                    var http = new XMLHttpRequest()
                    http.open('POST', '<?= $current_url ?>')
                    http.setRequestHeader('Content-type', 'application/json')
                    http.send(JSON.stringify(params)) // Make sure to stringify
                    http.onload = function() {

                        if (http.responseText === 'Finished.') {
                            document.getElementById("headline").innerHTML = 'Finished';
                            document.getElementById("info").innerHTML = '<p>SQL 文件成功删除。</p><?= $finished_content ?>';
                        } else {

                            <?php
                            $error_content = '<p>哎呀，好像出了点差错。</p>';
                            $error_content = '<p><a href="' . $current_url . '"><button type="button" class="btn btn-secondary">返回</button></a></p>';
                            $error_content .= '<p>The following response was generated by file:</p>';
                            $error_content .= '<p><textarea id="error-msg" style="height: 30vh; background-color: #ffe9e8;"></textarea></p>';
                            ?>

                            document.getElementById("headline").innerHTML = '<span class="danger">SQL ERROR</span>';
                            document.getElementById("info").innerHTML = '<?= $error_content ?>';
                            document.getElementById("error-msg").innerHTML = http.responseText;
                        }

                    }
                }

                function previewSql() {
                    document.getElementById("sql-preview").innerHTML = sqlCode;
                    document.getElementById("sql-preview").style.display = 'block';
                }

                function clickOkay() {

                    var params = {
                        sampleFile: '<?= $files[0] ?>',
                        action: 'getFinishUrl'
                    }

                    var http = new XMLHttpRequest()
                    http.open('POST', '<?= $current_url ?>')
                    http.setRequestHeader('Content-type', 'application/json')
                    http.send(JSON.stringify(params)) // Make sure to stringify
                    http.onload = function() {

                        if (http.responseText === 'current_url') {
                            location.reload();
                        } else {
                            window.location.href = '<?= BASE_URL ?>';
                        }

                    }

                }
            </script>
        </div>
        </div>
    </main>
</body>

</html>
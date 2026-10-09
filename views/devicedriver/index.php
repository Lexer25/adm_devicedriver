<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Device Driver — отладка устройств</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 300;
        }
        h1 small {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: normal;
            margin-left: 15px;
        }
        .form-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(52,152,219,0.3);
        }

        /* Результаты */
        .result-section {
            margin-top: 25px;
            border-top: 2px solid #ecf0f1;
            padding-top: 25px;
        }
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .result-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-error   { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-info    { background: #d1ecf1; color: #0c5460; }

        .result-message {
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .result-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .result-meta .meta-item { display: flex; flex-direction: column; }
        .result-meta .meta-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #7f8c8d;
            font-weight: 600;
        }
        .result-meta .meta-value {
            font-size: 14px;
            font-family: 'Consolas', monospace;
            word-break: break-all;
        }
        .result-data {
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 6px;
            padding: 15px;
            overflow-x: auto;
            font-family: 'Consolas', monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .result-data .key     { color: #9cdcfe; }
        .result-data .string  { color: #ce9178; }
        .result-data .number  { color: #b5cea8; }
        .result-data .boolean { color: #569cd6; }

        @media (max-width: 768px) {
            .container { padding: 15px; }
            .form-group { min-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            🔧 Device Driver
            <small>отладка устройств по id_door</small>
        </h1>

        <form method="POST" action="">
            <div class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_door">🚪 ID точки прохода (id_door)</label>
                        <input type="text"
                               id="id_door"
                               name="id_door"
                               value="<?php echo htmlspecialchars($defaults['id_door']); ?>"
                               placeholder="например, 1"
                               autofocus
                               required>
                    </div>
                    <div class="form-group" style="flex: 0 0 auto; min-width: auto;">
                        <button type="submit" class="btn btn-primary">▶️ Выполнить</button>
                    </div>
                </div>
            </div>
        </form>

        <?php if (!empty($result) && $result['status'] !== 'info'): ?>
        <div class="result-section">
            <div class="result-header">
                <h3 style="font-weight: 300; color: #2c3e50;">📊 Результат</h3>
                <span class="result-status status-<?php echo $result['status']; ?>">
                    <?php
                        $labels = array(
                            'success' => '✅ Успешно',
                            'error'   => '❌ Ошибка',
                            'warning' => '⚠️ Предупреждение',
                            'info'    => 'ℹ️ Информация',
                        );
                        echo isset($labels[$result['status']]) ? $labels[$result['status']] : $result['status'];
                    ?>
                </span>
            </div>

            <div class="result-message">
                <?php echo htmlspecialchars($result['message']); ?>
            </div>

            <?php if (!empty($result['meta'])): ?>
            <div class="result-meta">
                <?php foreach ($result['meta'] as $label => $value): ?>
                <div class="meta-item">
                    <span class="meta-label"><?php echo htmlspecialchars($label); ?></span>
                    <span class="meta-value"><?php echo htmlspecialchars($value); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($result['data'])): ?>
            <div class="result-data">
                <?php if (is_array($result['data'])): ?>
                    <?php echo formatArray($result['data']); ?>
                <?php else: ?>
                    <?php echo htmlspecialchars($result['data']); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1; font-size: 12px; color: #95a5a6; text-align: center;">
            <p>Device Driver v<?php echo htmlspecialchars(DEVICEDRIVER_VERSION); ?> &bull; <?php echo date('d.m.Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>

<?php
/**
 * Форматирование массива для вывода в result-data
 */
function formatArray($data, $level = 0)
{
    if (!is_array($data)) {
        return '<span class="string">' . htmlspecialchars($data) . '</span>';
    }

    $out    = '';
    $indent = str_repeat('  ', $level);

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $out .= $indent . '<span class="key">' . htmlspecialchars($key) . '</span>: {<br>';
            $out .= formatArray($value, $level + 1);
            $out .= $indent . '}<br>';
        } else {
            $type = gettype($value);
            switch ($type) {
                case 'integer':
                case 'double':
                    $display = '<span class="number">' . htmlspecialchars($value) . '</span>';
                    break;
                case 'boolean':
                    $display = '<span class="boolean">' . ($value ? 'true' : 'false') . '</span>';
                    break;
                case 'NULL':
                    $display = '<span class="boolean">null</span>';
                    break;
                default:
                    $display = '<span class="string">' . htmlspecialchars($value) . '</span>';
            }
            $out .= $indent . '<span class="key">' . htmlspecialchars($key) . '</span>: ' . $display . '<br>';
        }
    }

    return $out;
}
?>
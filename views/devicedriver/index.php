<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<?php
/**
 * Представление модуля Device Driver (отладка устройств по id_door).
 *
 * Это фрагмент, который подставляется в общий шаблон
 * modules/basis/views/template.php в переменную $content,
 * поэтому здесь нет <html>, <head> и <body>.
 *
 * Ожидаемые переменные:
 *   $defaults['id_door'] - ранее введённое значение поля формы
 *   $result              - array('status', 'message', 'data', 'meta')
 */

/**
 * Форматирование массива для вывода в консоль результата.
 * Имя уникально для модуля, чтобы не конфликтовать с другими вью.
 */
if ( ! function_exists('devicedriver_format_data'))
{
    function devicedriver_format_data($data, $level = 0)
    {
        if ( ! is_array($data))
        {
            return '<span class="string">' . htmlspecialchars((string) $data) . '</span>';
        }

        $out    = '';
        $indent = str_repeat('  ', $level);

        foreach ($data as $key => $value)
        {
            if (is_array($value))
            {
                $out .= $indent . '<span class="key">' . htmlspecialchars((string) $key) . '</span>: {<br>';
                $out .= devicedriver_format_data($value, $level + 1);
                $out .= $indent . '}<br>';
                continue;
            }

            $type = gettype($value);

            switch ($type)
            {
                case 'integer':
                case 'double':
                    $display = '<span class="number">' . htmlspecialchars((string) $value) . '</span>';
                    break;
                case 'boolean':
                    $display = '<span class="boolean">' . ($value ? 'true' : 'false') . '</span>';
                    break;
                case 'NULL':
                    $display = '<span class="boolean">null</span>';
                    break;
                default:
                    $display = '<span class="string">' . htmlspecialchars((string) $value) . '</span>';
            }

            $out .= $indent . '<span class="key">' . htmlspecialchars((string) $key) . '</span>: ' . $display . '<br>';
        }

        return $out;
    }
}

$status_map = array(
    'success' => array('label' => 'label-success', 'alert' => 'alert-success', 'icon' => 'glyphicon-ok-sign',      'text' => __('Успешно')),
    'error'   => array('label' => 'label-danger',  'alert' => 'alert-danger',  'icon' => 'glyphicon-remove-sign',  'text' => __('Ошибка')),
    'warning' => array('label' => 'label-warning', 'alert' => 'alert-warning', 'icon' => 'glyphicon-warning-sign', 'text' => __('Предупреждение')),
    'info'    => array('label' => 'label-info',    'alert' => 'alert-info',    'icon' => 'glyphicon-info-sign',    'text' => __('Информация')),
);

$result_status = isset($result['status']) ? $result['status'] : 'info';
$status        = isset($status_map[$result_status]) ? $status_map[$result_status] : $status_map['info'];

$result_message = isset($result['message']) ? $result['message'] : '';
$result_meta    = ( ! empty($result['meta']) && is_array($result['meta'])) ? $result['meta'] : array();
$result_data    = isset($result['data']) ? $result['data'] : '';

// Блок результата показываем только если что-то реально выполнилось
$has_result = ($result_status !== 'info' || $result_message !== '' || $result_data !== '');
?>

<style>
    /* Консоль вывода ответа устройства */
    .devicedriver-console {
        background: #1e1e1e;
        color: #d4d4d4;
        border: 1px solid #2d2d2d;
        border-radius: 4px;
        padding: 12px 15px;
        margin-bottom: 0;
        font-family: Consolas, Menlo, Monaco, 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
        max-height: 500px;
        overflow: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .devicedriver-console .key     { color: #9cdcfe; }
    .devicedriver-console .string  { color: #ce9178; }
    .devicedriver-console .number  { color: #b5cea8; }
    .devicedriver-console .boolean { color: #569cd6; }

    .devicedriver-meta th {
        width: 180px;
        font-weight: 600;
        color: #555;
    }
    .devicedriver-meta td {
        font-family: Consolas, Menlo, Monaco, 'Courier New', monospace;
        word-break: break-all;
    }
    .devicedriver-form .control-label {
        font-weight: 600;
    }
</style>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">
            <span class="glyphicon glyphicon-cog"></span>
            Device Driver
            <small style="color: #d9edf7;">&#8212; <?php echo __('отладка устройств по id_door'); ?></small>
        </h3>
    </div>

    <div class="panel-body">

        <div class="alert alert-info" style="margin-bottom: 20px;">
            <span class="glyphicon glyphicon-info-sign"></span>
            <?php echo __('Модуль выполняет команду устройства по номеру точки прохода.'); ?>
            <?php echo __('Тип устройства, IP-адрес и команда определяются автоматически по данным контроллера.'); ?>
        </div>

        <form method="post" action="" class="form-inline devicedriver-form" role="form">
            <div class="form-group">
                <label class="control-label" for="id_door">
                    <span class="glyphicon glyphicon-log-in"></span>
                    <?php echo __('Точка прохода (id_door)'); ?>
                </label>
                &nbsp;
                <input type="text"
                       class="form-control"
                       id="id_door"
                       name="id_door"
                       style="width: 220px;"
                       value="<?php echo htmlspecialchars($defaults['id_door']); ?>"
                       placeholder="<?php echo __('например, 483 Калибр'); ?>"
                       autofocus
                       required>
            </div>
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-play"></span>
                <?php echo __('Выполнить'); ?>
            </button>
        </form>

        <?php if ($has_result): ?>
        <hr>

        <h4 style="margin-top: 0;">
            <span class="glyphicon glyphicon-dashboard"></span>
            <?php echo __('Результат'); ?>
            <span class="label <?php echo $status['label']; ?> pull-right">
                <span class="glyphicon <?php echo $status['icon']; ?>"></span>
                <?php echo $status['text']; ?>
            </span>
        </h4>

        <div class="alert <?php echo $status['alert']; ?>" style="margin-bottom: 15px;">
            <?php echo htmlspecialchars($result_message); ?>
        </div>

        <?php if ( ! empty($result_meta)): ?>
        <table class="table table-bordered table-condensed table-striped devicedriver-meta">
            <tbody>
                <?php foreach ($result_meta as $label => $value): ?>
                <tr>
                    <th><?php echo htmlspecialchars($label); ?></th>
                    <td><?php echo htmlspecialchars(is_array($value) ? print_r($value, TRUE) : (string) $value); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if ($result_data !== ''): ?>
        <h4 style="font-size: 14px;">
            <span class="glyphicon glyphicon-transfer"></span>
            <?php echo __('Ответ устройства'); ?>
        </h4>
        <pre class="devicedriver-console"><?php
            if (is_array($result_data))
            {
                echo devicedriver_format_data($result_data);
            }
            else
            {
                echo htmlspecialchars((string) $result_data);
            }
        ?></pre>
        <?php endif; ?>

        <?php endif; ?>

    </div>

    <div class="panel-footer">
        <small class="text-muted">
            <?php echo __('Модуль'); ?>: devicedriver &bull;
            <?php echo __('Версия'); ?>: <?php echo htmlspecialchars(DEVICEDRIVER_VERSION); ?> &bull;
            <?php echo date('d.m.Y H:i:s'); ?>
        </small>
    </div>
</div>

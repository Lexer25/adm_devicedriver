private function executeCommand($post)
{
    $start_time = microtime(true);

    try {
        // Валидация
        if (empty($post['command'])) {
            throw new Exception('Не указана команда');
        }
        if (empty($post['connection'])) {
            throw new Exception('Не указана строка подключения');
        }

        // Формируем строку подключения из полей формы
        $connectionString = $this->buildConnectionString($post);
        $command = trim($post['command']);
        $type = isset($post['device_type']) ? $post['device_type'] : '';

        // Создаём устройство через фабрику
        $device = Device_Factory::create($connectionString, $command, $type);

        // Выполняем
        $result = $device->execute();

        $this->result['status'] = 'success';
        $this->result['message'] = 'Команда выполнена успешно';
        $this->result['data'] = $result;
        $this->result['command_parsed'] = $connectionString . ' → ' . $command;
        $this->result['method'] = $device instanceof Device_Artonit ? 'artonit' : 'other';

    } catch (Exception $e) {
        $this->result['status'] = 'error';
        $this->result['message'] = 'Ошибка: ' . $e->getMessage();
    }

    $this->result['execution_time'] = round(microtime(true) - $start_time, 4);
}

/**
 * Собрать строку подключения из полей формы
 */
private function buildConnectionString($post)
{
    $scheme = isset($post['scheme']) ? $post['scheme'] : 'ts2';
    $host   = $post['ip_server'];
    $port   = $post['port'];

    if ($scheme === 'ts2') {
        $qs = http_build_query(array(
            'dev_name' => $post['dev_name'],
            'login'    => '3',
            'password' => '3',
        ));
        return "ts2://{$host}:{$port}?{$qs}";
    }

    if ($scheme === 'http') {
        return "http://{$host}";
    }

    if ($scheme === 'basip') {
        return "basip://{$host}:{$port}";
    }

    return "{$scheme}://{$host}:{$port}";
}
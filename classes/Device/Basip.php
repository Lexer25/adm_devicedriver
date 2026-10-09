<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Устройство Басип
 * 
 * Строка подключения (пример):
 *   udp://192.168.1.100:4000
 *   tcp://192.168.1.100:5000
 * 
 * Здесь — заготовка под конкретную реализацию протокола Басип.
 */
class Device_Basip extends Device_Base
{
    /**
     * Выполнить команду
     * 
     * @return string
     * @throws Exception
     */
    public function execute()
    {
        $this->reset();

        try {
            $scheme = $this->getParam('scheme');
            $host   = $this->getParam('host');
            $port   = $this->getParam('port');

            if (empty($host)) {
                throw new Exception('Не указан IP устройства Басип');
            }

            // TODO: здесь реальная реализация работы с Басип.
            // Ниже — заглушка для демонстрации структуры.

            switch (strtolower($this->command)) {
                case 'getversion':
                    $response = $this->basipGetVersion($host, $port);
                    break;

                case 'getstatus':
                    $response = $this->basipGetStatus($host, $port);
                    break;

                case 'opendoor':
                    $response = $this->basipOpenDoor($host, $port);
                    break;

                default:
                    throw new Exception('Неизвестная команда Басип: ' . $this->command);
            }

            return $this->setResult($response)->getResult();

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Заглушка: получить версию
     */
    protected function basipGetVersion($host, $port)
    {
        // TODO: реальная реализация
        return 'Basip version: stub';
    }

    /**
     * Заглушка: получить статус
     */
    protected function basipGetStatus($host, $port)
    {
        // TODO: реальная реализация
        return 'Basip status: stub';
    }

    /**
     * Заглушка: открыть дверь
     */
    protected function basipOpenDoor($host, $port)
    {
        // TODO: реальная реализация
        return 'Basip door opened: stub';
    }
}
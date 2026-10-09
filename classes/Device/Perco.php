<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Устройство Перко
 * 
 * Строка подключения (пример):
 *   http://192.168.1.100
 *   tcp://192.168.1.100:4000
 * 
 * Здесь — заготовка под конкретную реализацию протокола Перко.
 */
class Device_Perco extends Device_Base
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
            $host = $this->getParam('host');
            $port = $this->getParam('port');

            if (empty($host)) {
                throw new Exception('Не указан IP устройства Перко');
            }

            // TODO: здесь реальная реализация работы с Перко.
            // Ниже — заглушка для демонстрации структуры.

            switch (strtolower($this->command)) {
                case 'getversion':
                    $response = $this->percoGetVersion($host, $port);
                    break;

                case 'getstatus':
                    $response = $this->percoGetStatus($host, $port);
                    break;

                case 'opendoor':
                    $response = $this->percoOpenDoor($host, $port);
                    break;

                default:
                    throw new Exception('Неизвестная команда Перко: ' . $this->command);
            }

            return $this->setResult($response)->getResult();

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            throw $e;
        }
    }

    protected function percoGetVersion($host, $port)
    {
        // TODO: реальная реализация
        return 'Perco version: stub';
    }

    protected function percoGetStatus($host, $port)
    {
        // TODO: реальная реализация
        return 'Perco status: stub';
    }

    protected function percoOpenDoor($host, $port)
    {
        // TODO: реальная реализация
        return 'Perco door opened: stub';
    }
}
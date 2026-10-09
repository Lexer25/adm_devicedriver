<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Базовый абстрактный класс для работы с устройствами
 * 
 * Обязательные свойства, которые должны быть заданы:
 * - connectionString — строка подключения
 * - command — команда для выполнения
 * 
 * Основной метод:
 * - execute() — выполнить команду, вернуть строку с результатом
 * 
 * Наследники: Device_Artonit, Device_Basip, Device_Perco
 */
abstract class Device_Base
{
    /**
     * Строка подключения устройства
     * Формат определяется наследником, например:
     *   ts2://127.0.0.1:1967?dev_name=VP3 K3\1
     *   http://192.168.1.100
     *   udp://192.168.1.100:4000
     * 
     * @var string
     */
    protected $connectionString = '';

    /**
     * Команда для выполнения
     * 
     * @var string
     */
    protected $command = '';

    /**
     * Разобранные параметры строки подключения
     * (scheme, host, port, path, query)
     * 
     * @var array
     */
    protected $params = array();

    /**
     * Результат выполнения команды (строка)
     * 
     * @var string
     */
    protected $result = '';

    /**
     * Флаг успешности выполнения
     * 
     * @var bool
     */
    protected $success = false;

    /**
     * Сообщение об ошибке
     * 
     * @var string
     */
    protected $error = '';

    /**
     * Дополнительные опции (таймаут и т.п.)
     * 
     * @var array
     */
    protected $options = array(
        'timeout' => 30,
        'verbose' => false,
        'logging' => false,
    );

    /**
     * Конструктор
     * 
     * @param string $connectionString Строка подключения
     * @param string $command Команда для выполнения
     * @param array $options Дополнительные опции
     */
    public function __construct($connectionString = '', $command = '', array $options = array())
    {
        $this->options = array_merge($this->options, $options);

        if (!empty($connectionString)) {
            $this->setConnectionString($connectionString);
        }
        if (!empty($command)) {
            $this->setCommand($command);
        }
    }

    // =========================================================================
    // Обязательные свойства
    // =========================================================================

    /**
     * Установить строку подключения
     * 
     * @param string $connectionString
     * @return $this
     * @throws Exception
     */
    public function setConnectionString($connectionString)
    {
        $connectionString = trim($connectionString);

        if (empty($connectionString)) {
            throw new Exception('Строка подключения не может быть пустой');
        }

        $this->connectionString = $connectionString;
        $this->params = $this->parseConnectionString($connectionString);

        return $this;
    }

    /**
     * Получить строку подключения
     * 
     * @return string
     */
    public function getConnectionString()
    {
        return $this->connectionString;
    }

    /**
     * Установить команду
     * 
     * @param string $command
     * @return $this
     * @throws Exception
     */
    public function setCommand($command)
    {
        $command = trim($command);

        if (empty($command)) {
            throw new Exception('Команда не может быть пустой');
        }

        $this->command = $command;

        return $this;
    }

    /**
     * Получить команду
     * 
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    // =========================================================================
    // Основной метод — должен быть реализован в наследниках
    // =========================================================================

    /**
     * Выполнить команду
     * 
     * @return string Результат выполнения команды
     * @throws Exception
     */
    abstract public function execute();

    // =========================================================================
    // Результат и состояние
    // =========================================================================

    /**
     * Получить результат выполнения (строку)
     * 
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Проверить успешность выполнения
     * 
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * Получить сообщение об ошибке
     * 
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Получить параметры подключения
     * 
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Получить конкретный параметр
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        return isset($this->params[$key]) ? $this->params[$key] : $default;
    }

    /**
     * Получить значение из query-строки
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParam($key, $default = null)
    {
        return isset($this->params['query'][$key]) ? $this->params['query'][$key] : $default;
    }

    /**
     * Получить опцию
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    // =========================================================================
    // Внутренние вспомогательные методы
    // =========================================================================

    /**
     * Сбросить состояние перед выполнением
     * 
     * @return void
     */
    protected function reset()
    {
        $this->result = '';
        $this->success = false;
        $this->error = '';
    }

    /**
     * Записать результат
     * 
     * @param string $result
     * @return $this
     */
    protected function setResult($result)
    {
        $this->result = (string)$result;
        $this->success = true;
        $this->error = '';

        return $this;
    }

    /**
     * Записать ошибку
     * 
     * @param string $error
     * @return $this
     */
    protected function setError($error)
    {
        $this->error = (string)$error;
        $this->success = false;
        $this->result = '';

        return $this;
    }

    /**
     * Разобрать строку подключения в массив
     * 
     * Поддерживаемые форматы:
     *   scheme://host:port/path?key=value&key2=value2
     *   host:port
     *   host
     * 
     * @param string $connectionString
     * @return array
     */
    protected function parseConnectionString($connectionString)
    {
        $params = array(
            'scheme' => '',
            'host'   => '',
            'port'   => 0,
            'path'   => '',
            'query'  => array(),
        );

        // Схема
        if (preg_match('#^([a-z][a-z0-9+.-]*)://(.+)$#i', $connectionString, $matches)) {
            $params['scheme'] = strtolower($matches[1]);
            $rest = $matches[2];
        } else {
            $rest = $connectionString;
        }

        // Query-строка
        if (strpos($rest, '?') !== false) {
            list($rest, $queryString) = explode('?', $rest, 2);
            parse_str($queryString, $params['query']);
        }

        // Path
        if (strpos($rest, '/') !== false) {
            list($rest, $params['path']) = explode('/', $rest, 2);
        }

        // host:port
        if (strpos($rest, ':') !== false) {
            list($host, $port) = explode(':', $rest, 2);
            $params['host'] = trim($host);
            $params['port'] = (int)trim($port);
        } else {
            $params['host'] = trim($rest);
        }

        // Порт по умолчанию
        if (empty($params['port'])) {
            $params['port'] = $this->getDefaultPort($params['scheme']);
        }

        return $params;
    }

    /**
     * Порт по умолчанию для схемы
     * 
     * @param string $scheme
     * @return int
     */
    protected function getDefaultPort($scheme)
    {
        switch ($scheme) {
            case 'http':  return 80;
            case 'https': return 443;
            case 'ts2':   return 1967;
            case 'udp':   return 4000;
            default:      return 0;
        }
    }

    /**
     * Магический вызов execute() при преобразовании в строку
     * 
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->execute();
        } catch (Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }
}
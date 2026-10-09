<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Устройство Артонит
 * 
 * Поддерживает два транспорта:
 *   - TS2:  ts2://127.0.0.1:1967?dev_name=VP3 K3\1&login=3&password=3
 *   - HTTP: http://192.168.1.100
 * 
 * Примеры команд:
 *   TS2:  getversion, getdevicetime, getjmp, getmac, opendoor, closedoor
 *   HTTP: getversion, getdevicemode, getdoormode, getinputports, getallinfo
 */
class Device_Artonit extends Device_Base
{
    /**
     * Карта команд → транспорт
     * 
     * @var array
     */
    protected $commandTransportMap = array(
        // TS2
        'getversion'     => 'ts2',
        'getdevicetime'  => 'ts2',
        'getjmp'         => 'ts2',
        'getmac'         => 'ts2',
        'getkeycount'    => 'ts2',
        'synctime'       => 'ts2',
        'opendoor'       => 'ts2',
        'closedoor'      => 'ts2',
        // HTTP
        'getdevicemode'  => 'http',
        'getdoormode'    => 'http',
        'getinputports'  => 'http',
        'getscudmode'    => 'http',
        'getallinfo'     => 'http',
    );

    /**
     * Выполнить команду через нужный транспорт
     * 
     * @return string
     * @throws Exception
     */
    public function execute()
    {
        $this->reset();

        try {
            $transport = $this->detectTransport();

            if ($transport === 'http') {
                return $this->executeHttp();
            }

            return $this->executeTs2();

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Определить транспорт по схеме или по команде
     * 
     * @return string 'ts2' | 'http'
     */
    protected function detectTransport()
    {
        $scheme = $this->getParam('scheme');

        // Явно указанная схема в строке подключения имеет приоритет
        if ($scheme === 'http' || $scheme === 'https') {
            return 'http';
        }
        if ($scheme === 'ts2') {
            return 'ts2';
        }

        // Иначе — по карте команд
        $command = strtolower($this->command);
        if (isset($this->commandTransportMap[$command])) {
            return $this->commandTransportMap[$command];
        }

        return 'ts2';
    }

    /**
     * Выполнить через TS2
     * 
     * @return string
     * @throws Exception
     */
    protected function executeTs2()
    {
        $ipServer = $this->getParam('host');
        $port     = $this->getParam('port', 1967);
        $devName  = $this->getQueryParam('dev_name');
        $login    = $this->getQueryParam('login', '3');
        $password = $this->getQueryParam('password', '3');

        if (empty($ipServer)) {
            throw new Exception('Не указан IP сервера TS2 в строке подключения');
        }
        if (empty($devName)) {
            throw new Exception('Не указан параметр dev_name в строке подключения');
        }

        $ts2 = new TS2client($ipServer, $port);
        $ts2->startServer();

        if (!$ts2->connReady) {
            throw new Exception('Не удалось подключиться к серверу TS2');
        }

        // Авторизация
        $ts2->sendMessage('r77 login name="' . $login . '", password="' . $password . '"');
        $loginResponse = $ts2->readMessage();

        if (strpos($loginResponse, 'r77 OK') === false) {
            $ts2->stopClient();
            throw new Exception('Ошибка авторизации TS2: ' . $loginResponse);
        }

        // Выполнение команды
        $fullCommand = 'r77 exec device="' . $devName . '", command="' . $this->command . '"';
        $ts2->sendMessage($fullCommand);

        $response = '';
        $attempt = 0;
        $maxAttempts = 15;

        while ($attempt < $maxAttempts) {
            $response = $ts2->readMessage();
            if (strpos($response, 'r77 OK') !== false || strpos($response, 'ERR') !== false) {
                break;
            }
            $attempt++;
            usleep(50000);
        }

        $ts2->stopClient();

        if (empty($response)) {
            throw new Exception('Не получен ответ от устройства по TS2');
        }

        return $this->setResult($response)->getResult();
    }

    /**
     * Выполнить через HTTP
     * 
     * @return string
     * @throws Exception
     */
    protected function executeHttp()
    {
        $ip = $this->getParam('host');

        if (empty($ip)) {
            throw new Exception('Не указан IP контроллера в строке подключения');
        }

        $httpClient = new artonitHTTP($ip);

        if (!$httpClient->isOnline) {
            throw new Exception('Контроллер не доступен по HTTP (IP: ' . $ip . ')');
        }

        $response = '';

        switch (strtolower($this->command)) {
            case 'getversion':
                $httpClient->getSoftVersion();
                $response = $httpClient->softVersion;
                break;

            case 'getdevicemode':
                $httpClient->getDeviceMode();
                $response  = 'WP: '   . ($httpClient->isWp   ? 'Включен' : 'Выключен') . "\n";
                $response .= 'Test: ' . ($httpClient->isTest ? 'Включен' : 'Выключен') . "\n";
                $response .= 'MAC: '  . $httpClient->mac_address;
                break;

            case 'getdoormode':
                $httpClient->getDoorMode();
                $response  = 'Дверь A: ' . $httpClient->doorMode[0] . "\n";
                $response .= 'Дверь B: ' . $httpClient->doorMode[1];
                break;

            case 'getinputports':
                $httpClient->getInputPortState();
                $response = "Состояние входных портов:\n";
                foreach ($httpClient->portStateInput as $i => $state) {
                    $response .= 'IN' . ($i + 1) . ': ' . $state . "\n";
                }
                break;

            case 'getscudmode':
                $httpClient->getScudMode();
                $response = 'Режим СКУД: ' . $httpClient->scud;
                break;

            case 'getallinfo':
                $httpClient->getDeviceInfo();
                $response  = "IP: "          . $httpClient->ip_address   . "\n";
                $response .= "MAC: "         . $httpClient->mac_address  . "\n";
                $response .= "Online: "      . ($httpClient->isOnline ? 'Да' : 'Нет') . "\n";
                $response .= "WP: "          . ($httpClient->isWp   ? 'Включен' : 'Выключен') . "\n";
                $response .= "Test: "        . ($httpClient->isTest ? 'Включен' : 'Выключен') . "\n";
                $response .= "Режим СКУД: "  . $httpClient->scud         . "\n";
                $response .= "Версия: "      . $httpClient->softVersion;
                break;

            default:
                throw new Exception('Неизвестная HTTP-команда: ' . $this->command);
        }

        $httpClient->disconnect();

        return $this->setResult($response)->getResult();
    }
}

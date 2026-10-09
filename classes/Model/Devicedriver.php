<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Модель для работы с устройствами по id_door
 * 
 * Достаёт из БД информацию о точке прохода:
 *   - тип устройства (artonit / basip / perco)
 *   - IP, порт
 *   - dev_name (для Артонит TS2)
 *   - команду
 */
class Model_Devicedriver extends Model
{
    /**
     * Получить данные устройства по id_door
     * 
     * @param int $idDoor
     * @return array
     * @throws Exception
     */
    public function getDoorById($idDoor)
    {
        $idDoor = (int)$idDoor;

        if ($idDoor <= 0) {
            throw new Exception('id_door должен быть положительным числом');
        }

        // Пример SQL. Замените на свою реальную таблицу.
        // Здесь предполагается, что есть таблица device с колонками:
        //   ID_DEV, NAME, NETADDR, ID_READER, ACTIVE
        // и таблица point (или reader) с колонками:
        //   ID_POINT, ID_DEV, ...
        $sql = 'SELECT d.ID_DEV,
                       d.NAME       AS DEV_NAME,
                       d.NETADDR    AS DEV_IP,
                       d.ID_READER  AS DEV_TYPE
                  FROM device d
                 WHERE d.ID_DEV = '.$idDoor.'
                   AND d."ACTIVE" = 1';


        try {
            $row = DB::query(Database::SELECT, $sql)
                ->execute(Database::instance('fb'))
                ->current();

        } catch (Exception $e) {
            Kohana::$log->add(Log::ERROR, 'Devicedriver: ошибка БД: ' . $e->getMessage());
            throw new Exception('Ошибка обращения к БД: ' . $e->getMessage());
        }

        if (empty($row)) {
            throw new Exception("Точка прохода с id_door = {$idDoor} не найдена");
        }

        // Кодировка: переводим строки из windows-1251 в UTF-8
        $devName = isset($row['DEV_NAME']) ? $row['DEV_NAME'] : '';
        if ($devName !== '') {
            $devName = @iconv('windows-1251', 'UTF-8//IGNORE', $devName);
            if ($devName === false) {
                $devName = $row['DEV_NAME'];
            }
        }

        return array(
            'id_door'    => $idDoor,
            'device_type'=> $this->detectDeviceType($row),
            'dev_name'   => $devName,
            'ip'         => isset($row['DEV_IP']) ? $row['DEV_IP'] : '',
            'port'       => 1967,
            'scheme'     => 'ts2',
            'command'    => 'getversion',
        );
    }

    /**
     * Определить тип устройства
     * 
     * Логика зависит от вашей БД. Здесь — пример:
     *  - если ID_READER = 1 → artonit
     *  - если 2 → basip
     *  - если 3 → perco
     * 
     * Замените на реальное поле/правило.
     * 
     * @param array $row
     * @return string
     */
    protected function detectDeviceType(array $row)
    {
        // Пример: маппинг по ID_READER
        $readerId = isset($row['DEV_TYPE']) ? (int)$row['DEV_TYPE'] : 0;

        $map = array(
            1 => 'artonit',
            2 => 'basip',
            3 => 'perco',
        );

        if (isset($map[$readerId])) {
            return $map[$readerId];
        }

        // По умолчанию — Артонит
        return 'artonit';
    }

    /**
     * Собрать строку подключения для устройства
     * 
     * @param array $door данные из getDoorById()
     * @return string
     * @throws Exception
     */
    public function buildConnectionString(array $door)
    {
        $type   = $door['device_type'];
        $host   = $door['ip'];
        $port   = $door['port'];
        $scheme = $door['scheme'];

        if (empty($host)) {
            throw new Exception('Не указан IP адрес устройства');
        }

        if ($type === 'artonit') {
            if ($scheme === 'http') {
                return "http://{$host}";
            }

            $devName = $door['dev_name'];
            if (empty($devName)) {
                throw new Exception('Не указано имя контроллера Артонит (dev_name)');
            }

            $qs = http_build_query(array(
                'dev_name' => $devName,
                'login'    => '3',
                'password' => '3',
            ));

            return "ts2://{$host}:{$port}?{$qs}";
        }

        if ($type === 'basip') {
            return "udp://{$host}:{$port}";
        }

        if ($type === 'perco') {
            return "http://{$host}";
        }

        throw new Exception('Неизвестный тип устройства: ' . $type);
    }
}

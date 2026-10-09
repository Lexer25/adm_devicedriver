<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Фабрика устройств
 * 
 * Создаёт нужный класс-наследник Device_Base
 * по явно указанному типу устройства.
 * 
 * Тип ОБЯЗАТЕЛЕН. Автодетект по строке подключения не выполняется.
 */
class Device_Factory
{
    /**
     * Карта: тип устройства → класс
     * 
     * @var array
     */
    protected static $typeMap = array(
        'artonit' => 'Device_Artonit',
        'basip'   => 'Device_Basip',
        'perco'   => 'Device_Perco',
    );

    /**
     * Создать устройство
     * 
     * Тип устройства ОБЯЗАТЕЛЕН. Если не указан — выбрасывается исключение.
     * 
     * @param string $connectionString Строка подключения
     * @param string $command          Команда
     * @param string $type             Тип устройства ('artonit', 'basip', 'perco')
     * @param array  $options          Доп. опции
     * @return Device_Base
     * @throws Exception
     */
    public static function create($connectionString, $command = '', $type = '', array $options = array())
    {
        // Тип обязателен
        if (empty($type) || !is_string($type) || trim($type) === '') {
            throw new Exception('Не указан тип устройства. Допустимые типы: ' . implode(', ', array_keys(self::$typeMap)));
        }

        $type = strtolower(trim($type));

        if (!isset(self::$typeMap[$type])) {
            throw new Exception(
                'Неизвестный тип устройства: ' . $type
                . '. Допустимые типы: ' . implode(', ', array_keys(self::$typeMap))
            );
        }

        $class = self::$typeMap[$type];

        if (!class_exists($class)) {
            throw new Exception('Класс устройства не найден: ' . $class);
        }

        return new $class($connectionString, $command, $options);
    }

    /**
     * Получить список поддерживаемых типов устройств
     * 
     * @return array
     */
    public static function getSupportedTypes()
    {
        return array_keys(self::$typeMap);
    }

    /**
     * Проверить, поддерживается ли тип
     * 
     * @param string $type
     * @return bool
     */
    public static function isTypeSupported($type)
    {
        return isset(self::$typeMap[strtolower(trim($type))]);
    }
}

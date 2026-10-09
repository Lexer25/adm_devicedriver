<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Контроллер отладки устройств
 * 
 * Принимает id_door, через модель получает данные двери,
 * строит строку подключения и вызывает Device_Factory.
 * 
 * Наследуется от Controller_Template, поэтому представление
 * выводится внутри общего шаблона (modules/basis/views/template.php).
 */
class Controller_Devicedriver extends Controller_Template
{
    /**
     * Общий шаблон страницы
     */
    public $template = 'template';

    /**
     * Результат выполнения
     */
    private $result = array(
        'status'  => 'info',
        'message' => '',
        'data'    => '',
        'meta'    => array(),
    );

    /**
     * Заголовок страницы задаём до parent::before(),
     * чтобы он попал в общий шаблон.
     */
    public function before()
    {
        $this->title = __('Device Driver');

        parent::before();
    }

    /**
     * Главная страница
     */
    public function action_index()
    {
        $post = $this->request->post();

        if (!empty($post) && isset($post['id_door'])) {
            $this->executeByDoor($post);
        }

        $view = View::factory('devicedriver/index');

        $view->defaults = array(
            'id_door' => isset($post['id_door']) ? $post['id_door'] : '',
        );

        $view->result = $this->result;

        $this->template->content = $view;
    }

    /**
     * Выполнить команду по id_door
     */
    private function executeByDoor($post)
    {
        $start = microtime(true);

        // Данные устройства инициализируем заранее: даже если выполнение
        // прервётся на любом шаге, их можно будет показать пользователю.
        $idDoor           = isset($post['id_door']) ? trim($post['id_door']) : '';
        $door             = array();
        $type             = '';
        $command          = '';
        $connectionString = '';

        try {
            if ($idDoor === '') {
                throw new Exception('Не указан id_door');
            }

            // 1. Данные двери из БД
            $model = Model::factory('Devicedriver');
            $door  = $model->getDoorById($idDoor);

            // 2. Параметры устройства (нужны и при ошибке выполнения команды)
            $type    = isset($door['device_type']) ? $door['device_type'] : '';
            $command = isset($door['command']) ? $door['command'] : '';

            // 3. Строка подключения
            $connectionString = $model->buildConnectionString($door);

            // 4. Создаём устройство через фабрику
            $device = Device_Factory::create($connectionString, $command, $type);

            // 5. Выполняем
            $raw = $device->execute();

            // 6. Результат
            $this->result['status']  = 'success';
            $this->result['message'] = 'Команда выполнена успешно';
            $this->result['data']    = $raw;

        } catch (Exception $e) {
            $this->result['status']  = 'error';
            $this->result['message'] = 'Ошибка: ' . $e->getMessage();

            Kohana::$log->add(Log::ERROR, 'Devicedriver: ' . $e->getMessage());
        }

        // Мета-данные собираются в обоих случаях: и при успехе, и при ошибке.
        $this->result['meta'] = $this->buildMeta($idDoor, $door, $type, $command, $connectionString, $start);
    }

    /**
     * Собрать мета-данные для вывода в представлении.
     * 
     * Метод не зависит от того, чем закончилось выполнение: всё, что
     * не удалось получить, выводится прочерком.
     * 
     * @param string $idDoor           id_door из формы
     * @param array  $door             данные точки прохода (может быть пустым)
     * @param string $type             тип устройства
     * @param string $command          команда
     * @param string $connectionString строка подключения
     * @param float  $start            время начала выполнения
     * @return array
     */
    private function buildMeta($idDoor, array $door, $type, $command, $connectionString, $start)
    {
        return array(
            'id_door'       => $this->metaValue($idDoor),
            'Тип'           => $this->metaValue($type),
            'Устройство'    => $this->metaValue(isset($door['dev_name']) ? $door['dev_name'] : ''),
            'IP'            => $this->metaValue(isset($door['ip']) ? $door['ip'] : ''),
            'Команда'       => $this->metaValue($command),
            'Строка подкл.' => $this->metaValue($connectionString),
            'Время'         => round(microtime(true) - $start, 4) . ' сек',
        );
    }

    /**
     * Значение для мета-данных: пустое заменяем прочерком.
     * 
     * @param mixed $value
     * @return mixed
     */
    private function metaValue($value)
    {
        if ($value === null || $value === '' || $value === false) {
            return '—';
        }

        return $value;
    }
}

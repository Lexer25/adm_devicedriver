<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Контроллер отладки устройств
 * 
 * Принимает id_door, через модель получает данные двери,
 * строит строку подключения и вызывает Device_Factory.
 */
class Controller_Devicedriver extends Controller
{
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

        $this->response->body($view);
    }

    /**
     * Выполнить команду по id_door
     */
    private function executeByDoor($post)
    {
        $start = microtime(true);

        try {
            $idDoor = isset($post['id_door']) ? trim($post['id_door']) : '';

            if ($idDoor === '') {
                throw new Exception('Не указан id_door');
            }

            // 1. Данные двери из БД
            $model = Model::factory('Devicedriver');
            $door  = $model->getDoorById($idDoor);

            // 2. Строка подключения
            $connectionString = $model->buildConnectionString($door);

            $type    = $door['device_type'];
            $command = $door['command'];

            // 3. Создаём устройство через фабрику
            $device = Device_Factory::create($connectionString, $command, $type);

            // 4. Выполняем
            $raw = $device->execute();

            // 5. Результат
            $this->result['status']  = 'success';
            $this->result['message'] = 'Команда выполнена успешно';
            $this->result['data']    = $raw;
            $this->result['meta']    = array(
                'id_door'      => $door['id_door'],
                'Тип'          => $type,
                'Устройство'   => $door['dev_name'],
                'IP'           => $door['ip'],
                'Команда'      => $command,
                'Строка подкл.'=> $connectionString,
                'Время'        => round(microtime(true) - $start, 4) . ' сек',
            );

        } catch (Exception $e) {
            $this->result['status']  = 'error';
            $this->result['message'] = 'Ошибка: ' . $e->getMessage();
            $this->result['meta']    = array(
                'id_door' => isset($post['id_door']) ? $post['id_door'] : '—',
                'Время'   => round(microtime(true) - $start, 4) . ' сек',
            );

            Kohana::$log->add(Log::ERROR, 'Devicedriver: ' . $e->getMessage());
        }
    }
}
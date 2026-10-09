<?php
/**
 * Класс WebSocket сервера
 https://tokmakov.msk.ru/blog/item/202
 ver 1.1 4.03.2024
 */
class TS2client {

    /**
     * Функция вызывается, когда получено сообщение от клиента
     */
    public $handler;

    /**
     * IP адрес сервера
     */
    private $ip;
    /**
     * Порт сервера
     */
    private $port;
    /**
     * Сокет для принятия новых соединений, прослушивает указанный порт
     */
    private $connection;
	
	
	/**
     * Сокет подключен и готов передавать команды 3.11.2021 Бухаров
     */
   // private $connReady;
    public $connReady;
	
	
	
    /**
     * Для хранения всех подключений, принятых слушающим сокетом
     */
    private $connects;

    /**
     * Ограничение по времени работы клиента
     */
    private $timeLimit = 0;
    /**
     * Время начала работы клиента
     */
    private $startTime;
    /**
     * Выводить сообщения в консоль?
     */
    private $verbose = false;
    /**
     * Записывать сообщения в log-файл?
     */
    private $logging = false;
    /**
     * Имя log-файла
     */
    private $logFile = 'ws-log.txt';
    /**
     * Ресурс log-файла
     */
    private $resource;
    
	
	private $errDesc;//описание ошибки
	
	
	

    public function __construct($ip = '127.0.0.1', $port = 1967) {
        $this->ip = $ip;
        $this->port = $port;

    }
	
	/*
	инициализация по id_ts
	
	*/
	public function init($id_ts) {
		$sql='select * from server s
			where s.id_server='.$id_ts;
		try {
			$query = Arr::flatten(DB::query(Database::SELECT, $sql)
				->execute(Database::instance('fb'))
				->as_array());	
				
				$this->ip = Arr::get($query, 'IP');
				$this->ip = Model::Factory('Stat')->IntToIP(Arr::get($query, 'IP'));
				$this->port = Arr::get($query, 'PORT');
				//echo Debug::vars('84',$sql,  $query, $this); exit;
				return 0;

			} catch (Exception $e) {
							
				Log::instance()->add(log::INFO, $e->getMessage());
				return 2;// ошибка: в базе данных нет ТС с указанным номером.
			}

    }
	
	

    public function __destruct() {
        if (is_resource($this->connection)) {
            $this->stopClient();
        }
        if ($this->logging) {
            //fclose($this->resource);
        }
    }

    /**
     * Дополнительные настройки для отладки
     */
    public function settings($timeLimit = 0, $verbose = false, $logging = false, $logFile = 'ws-log.txt') {
        $this->timeLimit = $timeLimit;
        $this->verbose = $verbose;
        $this->logging = $logging;
        $this->logFile = $logFile;
        if ($this->logging) {
            $this->resource = fopen($this->logFile, 'a');
        }
    }

    /**
     * Выводит сообщение в консоль и/или записывает в лог-файл
     */
    private function debug($message) {
       /*  $message = '[' . date('r') . '] ' . $message . PHP_EOL;
        if ($this->verbose) {
            echo $message;
        }
        if ($this->logging) {
            fwrite($this->resource, $message);
        } */
		//Log::instance()->add(Log::NOTICE, '135 '.$message);
		
    }

 
	/*
	*отправка сообщения
	*/
	public function sendMessage($command)
	{
		if ($this->logging) Log::instance()->add(Log::DEBUG,'Стр. 121. Вызов функции sendMessage '. $this->connection.','. $this->connReady);
			
		 if (true === $this->connReady) {
			 //$login_mes = 'r51 login name="3", password="35"';
				$reply=socket_write($this->connection, $command."\r\n", strlen($command."\r\n"));
				//получаем ответ
				           
        } else {
			$reply='No connection TS2.';
		}
		
		 return $reply;
	}
	
	/*
	*чтение данных из сокета для получения ответа на команду
	*/	
	public function readMessage()
	{
				
		 if (true === $this->connReady) {
			 //надо знать, что тут программа будет стоять до тех пор, пока что-то не получит из сокета
			 $reply=socket_read($this->connection,4096);
			 
           
        } else {
			$reply='Err. No connection TS2.';
		}
		
		 return $reply;
	}
	
	
	

    /**
     * Запускает клиента в работу
     */
    public function startServer() {

        
		$this->debug('Try start server...');
		if ($this->logging) Log::instance()->add(Log::DEBUG, 'Try start server...');
					

        $this->connection = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($this->connection,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>30, "usec"=>0));

        if (false === $this->connection) {
            $this->debug('Error socket_create(): ' . socket_strerror(socket_last_error()));
			if ($this->logging) Log::instance()->add(Log::DEBUG, "Line 147.Couldn't create socket, error code is: " . socket_last_error().", error message is: " . socket_strerror(socket_last_error()));
					
            return;
        }

        // подключаюсь к ТС2
        $this->connReady = @socket_connect($this->connection, $this->ip, $this->port); // слушаем сокет
        
		if (false === $this->connReady) {
            $this->debug('Error socket_listen(): ' . socket_strerror(socket_last_error()));
			if ($this->logging) Log::instance()->add(Log::DEBUG, 'Стр. 170. Не могу выполнить socket_connect.');
					
            return;
        }

 $this->debug('210 Success socket_listen(): ' );
       
        $this->connects = array($this->connection);// а вот где-то тут можно указать время ожидания ответов и т.п.
        $this->startTime = time();
		socket_read($this->connection,4096);
        while (true) {

           

            // если истекло ограничение по времени, останавливаем сервер
            if ($this->timeLimit && time() - $this->startTime > $this->timeLimit) {
                $this->debug('Time limit. Stopping server.');
				if ($this->logging) Log::instance()->add(Log::DEBUG, 'Стр. 188. Time limit. Stopping server!' .$this->timeLimit);
       
                $this->stopClient();
                return;
            }
			
			// if (time() - $this->startTime > 20) {
                // Log::instance()->add(Log::DEBUG, 'Стр. 195. Кручусь в бесконечном цикле.');
                return;
            // }

        }

    }

    /**
     * Останавливает работу клиента
     */
    public function stopClient() {
              
        socket_close($this->connection);
        if (!empty($this->connects)) { // отправляем все клиентам сообщение о разрыве соединения
            foreach ($this->connects as $connect) {
                if (is_resource($connect)) {
                    socket_write($connect, self::encode('  Closed on server demand', 'close'));
                    socket_shutdown($connect);
                    socket_close($connect);
                }
            }
        }
    }

	/** парсинг строки ифнормации о контроллере из БД ТС.
		/* Извлекает из строки содержимое между указанными параметрами.
		*/
		public function deviceInfoPars($strdata)
		{
			$res=array();
			if(is_string($strdata)){

				$_strdata=(explode(",", trim(str_replace('"','', str_replace('t56 OK', '', trim($strdata))))));
				if(is_array($_strdata)){
				
					foreach ($_strdata as $key=>$value)
					{
						$data2=explode(",", $value);
						if(is_array($data2)){
						
							foreach($data2 as $key2=>$value2)
							{
								$data3=explode("=", $value2);
								
								$res[trim($data3[0])]=trim($data3[1]);
							}
						// 
						} else {
							
							Log::instance()->add(Log::DEBUG, '110 Ожидается массив, а получено неизвестно что.');	
							return $res;
						}
					}
				} else {
					
					Log::instance()->add(Log::DEBUG, '111 Ожидается массив, а получено неизвестно что.');	
					return $res;
				}
			} else {
					Log::instance()->add(Log::DEBUG, '121 Ответ не является строкой.');	
				return $res;
			
			}
			
			
			
			return $res;
		}
   

}

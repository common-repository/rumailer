<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 19.09.2016
 * Time: 11:28
 */

class API {

    private $key = '';

    public function __construct(){
        $this->key = get_option('rumailer_setting_api');
    }

    private function get_data_rumailer($metod = '',$data =null){
        if(!empty($data)){
            $data['api_key'] = urlencode($this->key);
            $data = http_build_query($data);
        } else {
            $data = 'api_key='.urlencode($this->key);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://rumailer.ru/ru_api/'.$metod);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $header = "Content-Type: application/x-www-form-urlencodedrn";
        $header .= "Content-Length: ".strlen($data)."rnrn";
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result=json_decode($result);
        return $result;
    }

    /**
     * Возвращает статистику по отправленным всего письмам, отправленным в этом месяце, о количестве подписчиков, о лимитах Вашего тарифа
     * @return array|mixed|null|object
     */
    public function get_quota(){
        $result = $this->get_data_rumailer('get_quota');
        return $result;
    }

    /**
     * Добавляет подписчика, используя различные опции, передаваемые в параметрах
     * @param $data
     * @return array|mixed|null|object
     */
    public function add_subscriber($data = array()){
        if(!empty($data['fields'])){
            $data['fields'] =serialize($data['fields']);
        }
        $result = $this->get_data_rumailer('add_subscriber',$data);
        return $result;
    }

    /**
     * Удалить пользователя
     * @param array $data
     * @return array|mixed|null|object
     */
    public function del_subscriber($data = array()){
        $result = $this->get_data_rumailer('del_subscriber',$data);
        return $result;
    }

    /**
     * Возвращает статистику конкретного листа
     * @param $data
     * @return array|mixed|null|object
     */
    public function get_list_stat($data = null){
        $result = $this->get_data_rumailer('get_list_stat',$data);
        return $result;
    }

    /**
     * Получаем форм пользователя
     * @return array|mixed|null|object
     */
    public function get_forms_list(){
        $result = $this->get_data_rumailer('get_forms_list');
        return $result;
    }

    /**
     *
     * @param $data
     * @return array|mixed|null|object
     */
    public function get_lists($data = null){
        $result = $this->get_data_rumailer('get_lists',$data);
        return $result;
    }

    /**
     * Возвращает Ваш баланс в рублях
     * Возвращает имя и id 100 последних листов
     * @param $data
     * @return array|mixed|null|object
     */
    public function get_balance($data = null){
        $result = $this->get_data_rumailer('get_balance',$data);
        return $result;
    }

    /**
     * Отправка сообщений
     * @param $data
     * @return array|mixed|null|object
     */
    public function send_message($data = null){
        $result = $this->get_data_rumailer('send_message',$data);
        return $result;
    }

}
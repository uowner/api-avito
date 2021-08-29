<?php
//Функция для подключения к БД
function db_connect() {
    $dblocation = "localhost";
    $dbuser = "mysql";
    $dbpasswd = "PASSWORD";
    $dbname = "avito-api";
    $mysqli = new mysqli($dblocation, $dbuser, $dbpasswd, $dbname);
    if ($mysqli->connect_errno) {
        $api_answer['error'] = true;
        $api_answer['error_desc'] = 'MySQL connection error: '.$mysqli->connect_errno;
        die(json_encode($api_answer));
    }
    $mysqli->query('SET names utf8');
    return $mysqli;
}

//Подключаемся
$mysqli = db_connect();

//Запрос текущего баланса пользователя из БД
//(Указатель базы данных, ID пользователя)
//Возвращает массив с ID, NAME и BALANCE если такой пользователь найден в базе
//api/user_balance_get/ID/CURRENCY
function user_balance_get($mysqli, int $id, string $currency = "RUB") {
    $temp_answer['error'] = False;
    //Запрашиваем данные из БД
    $result = $mysqli->query("SELECT * FROM users WHERE id='$id'");
    if (!$result) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'MySQL error: '.$mysqli->errno;
        return $temp_answer;
    }
    $db_temp = $result->fetch_assoc();
    //Проверяем существует ли пользователь
    if(!$db_temp['id']) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'User ID #'.$id.' is not found in database.';
        return $temp_answer;
    }
    //Переводим наш баланс (который хранится в БД как integer копеек * 100) в формат float (Рублей.Копеек)
    //(так мы обеспечиваем точность математических операций до 4х знаков)
    //В конце переводим в формат string чтобы не потерять точность при переводе в json
    $db_temp['balance'] = (string) round($db_temp['balance'] / 10000, 4);
    //Конвертируем валюту
    if($currency != "RUB") {
        //Не самый лучший API курсов валют, но зато не требует API-KEY как 99% сервисов
        $temp_data = file_get_contents("http://currency-api.appspot.com/api/RUB/".$currency.".jsonp?amount=".$db_temp['balance']."&callback=data");
        $temp_data = trim(trim($temp_data, 'data('),')');
        $temp_data = json_decode($temp_data);
        if(!$temp_data->success) {
            $temp_answer['error'] = True;
            $temp_answer['error_desc'] = 'Currency conversion error.';
            return $temp_answer;
        }
        $db_temp['balance'] = $temp_data->amount;
    }
    $db_temp['currency'] = $currency;
    return $db_temp;
}

//Добавление денег на баланс пользователя
//(Указатель базы данных, ID пользователя, Количество средств для добавления ТОЛЬКО ПОЛОЖИТЕЛЬНОЕ ЧИСЛО)
//api/user_balance_add/ID/AMOUNT/COMMENT
function user_balance_add($mysqli, int $id, float $sum, string $comment = "Default comment for method ADD") {
    $temp_answer['error'] = False;
    if(!$comment) {$comment = "Default comment for method ADD";}
    //Защита от отрицательных чисел
    if($sum < 0){
        http_response_code(400);
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = "Error: Negative amount. If you need to subtract money - use user_balance_sub or user_balance_subadd instead.";
        return $temp_answer;
    }
    //Запрашиваем баланс пользователя, если на этом этапе была какая-либа ошибка - пробрасываем ее и прерываем дальнейшее выполнение
    //Таким образом проверяем что пользователь существует в БД
    $balance = user_balance_get($mysqli, $id);
    if($balance['error']) {
        return $balance;
    }
    //Переводим наш float в integer с точностью 4 знака
    $sum = floor($sum*10000);
    //Обновляем данные в БД
    if (!$mysqli->query("UPDATE users SET balance=balance+'$sum' WHERE id='$id'")) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'MySQL error: '.$mysqli->errno;
        return $temp_answer;
    }
    //Вносим запись в историю транзакций
    $temp_answer = user_balance_history_create($mysqli, $id, $sum, $comment);
    if($temp_answer['error']) {
        return $temp_answer;
    }
    return $temp_answer;
}

//Списание средств с баланса пользователя
//(Указатель базы данных, ID пользователя, Количество средств для списания ТОЛЬКО ПОЛОЖИТЕЛЬНОЕ ЧИСЛО)
//api/user_balance_sub/ID/AMOUNT/COMMENT
function user_balance_sub($mysqli, int $id, float $sum, string $comment = "Default comment for method SUB") {
    $temp_answer['error'] = False;
    if(!$comment) {$comment = "Default comment for method SUB";}
    //Защита от отрицательных чисел
    if($sum < 0) {
        http_response_code(400);
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = "Error: Negative amount. If you need to add money - use user_balance_add or user_balance_subadd instead.";
        return $temp_answer;
    }
    //Запрашиваем баланс пользователя, если на этом этапе была какая-либа ошибка - пробрасываем ее и прерываем дальнейшее выполнение
    $balance = user_balance_get($mysqli, $id);
    if($balance['error']) {
        return $balance;
    }
    //Переводим наш float в integer с точностью 4 знака
    $sum = floor($sum*10000);
    //Проверяем не уходит ли баланс пользователя в отрицательные значения после выполнения операции (хватает ли денег для списания)
    if($balance['balance']*10000 < $sum) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = "Error: Insufficient funds.";
        return $temp_answer;
    }
    //Обновляем данные в БД
    if (!$mysqli->query("UPDATE users SET balance=balance-'$sum' WHERE id='$id'")) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'MySQL error: '.$mysqli->errno;
        return $temp_answer;
    }
    //Вносим запись в историю транзакций
    $temp_answer = user_balance_history_create($mysqli, $id, -$sum, $comment);
    if($temp_answer['error']) {
        return $temp_answer;
    }
    return $temp_answer;
}

//Универсальная функция для работы с балансом пользователя
//Для добавления средств на баланс необходимо указывать ПОЛОЖИТЕЛЬНОЕ значение
//Для списания средств с баланса пользователя необходимо указывать ОТРИЦАТЕЛЬНОЕ значение
//(Указатель базы данных, ID пользователя, Количество средств для списания/зачисления (положительное для зачисления, отрицательное для списания))
//api/user_balance_subadd/ID/AMOUNT/COMMENT
function user_balance_subadd($mysqli, int $id, float $sum, string $comment = "Default comment for method SUBADD") {
    $temp_answer['error'] = False;
    if(!$comment) {$comment = "Default comment for method SUBADD";}
    //Запрашиваем баланс пользователя, если на этом этапе была какая-либа ошибка - пробрасываем ее и прерываем дальнейшее выполнение
    $balance = user_balance_get($mysqli, $id);
    if($balance['error']) {
        return $balance;
    }
    //Переводим наш float в integer с точностью 4 знака
    $sum = floor($sum*10000);
    //Проверяем не уходит ли баланс пользователя в отрицательные значения после выполнения операции (хватает ли денег для списания)
    if($sum < 0) {
        if($balance['balance']*10000 < abs($sum)) {
            $temp_answer['error'] = True;
            $temp_answer['error_desc'] = "Error: Insufficient funds.";
            return $temp_answer;
        }
    }
    //Обновляем данные в БД
    if (!$mysqli->query("UPDATE users SET balance=balance+'$sum' WHERE id='$id'")) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'MySQL error: '.$mysqli->errno;
        return $temp_answer;
    }
    //Вносим запись в историю транзакций
    $temp_answer = user_balance_history_create($mysqli, $id, $sum, $comment);
    if($temp_answer['error']) {
        return $temp_answer;
    }
    return $temp_answer;
}

//Перевод средств между двух пользователей
//(Указатель базы данных, ID пользователя отправителя, ID пользователя получателя, Количество средств для списания ТОЛЬКО ПОЛОЖИТЕЛЬНОЕ ЧИСЛО)
//api/user_balance_transfer/ID_SENDER/ID_RECEIVER/AMOUNT
function user_balance_transfer($mysqli, int $id_from, int $id_to, float $sum) {
    //Если ID одинаковы - выдаем ошибку
    if($id_from == $id_to) {
        http_response_code(400);
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'Error: Sender & Receiver ID are same';
        return $temp_answer;
    }
    //Убедимся что оба пользователя существуют в БД
    $result = $mysqli->query("SELECT COUNT(*) FROM users WHERE id='$id_from' OR id='$id_to'");
    if (!$result) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'MySQL error: '.$mysqli->errno;
        return $temp_answer;
    }
    $result = $result->fetch_row();
    if($result[0] != 2) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'One of specified users is not found in database.';
        return $temp_answer;
    }
    //Проводим операцию списания с отправителя, в случае ошибки - пробрасываем ее и прерываем выполнение
    $temp_answer = user_balance_sub($mysqli, $id_from, $sum, "Transfer to user ID #".$id_to);
    if($temp_answer['error']) {
        return $temp_answer;
    }
    //Проводим операцию зачисления получателю, в случае ошибки - пробрасываем ее и прерываем выполнение
    $temp_answer = user_balance_add($mysqli, $id_to, $sum, "Transfer from user ID #".$id_from);
    if($temp_answer['error']) {
        return $temp_answer;
    }
    $temp_answer['error'] = False;
    return $temp_answer;
}

//Создание записи в истории транзакций данный метод недоступен извне и используется только внутри самого API
function user_balance_history_create($mysqli, int $id, float $sum, string $comment) {
    $temp_answer['error'] = False;
    //Запрашиваем баланс пользователя, если на этом этапе была какая-либа ошибка - пробрасываем ее и прерываем дальнейшее выполнение
    $balance = user_balance_get($mysqli, $id);
    if($balance['error']) {
        return $balance;
    }
    //Обновляем данные в БД
    if (!$mysqli->query("INSERT INTO history (user_id, amount, comment) VALUES ('$id', '$sum', '$comment')")) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'MySQL error: '.$mysqli->errno;
        return $temp_answer;
    }
    return $temp_answer;
}

//Запрос истории транзакций пользователя с поддержкой пагинации
//api/user_balance_history_get/ID/MAX_TRANSACTIONS/PAGE/ORDER/SORT
function user_balance_history_get($mysqli, int $id, int $max, int $page, string $order = "date", string $sort = "DESC") {
    $temp_answer['error'] = False;
    //Проверим входные данные
    $sort = strtoupper($sort);
    $order = strtolower($order);
    if(($sort != "DESC" && $sort != "ASC") || ($order != "date" && $order != "id" && $order != "amount")) {
        http_response_code(400);
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'Error: wrong input data. Allowed: DESC/ASC for sort | date/id/amount for order.';
        return $temp_answer;
    }
    //Заменяем сортировку по дате, на сортировку по ID - результат аналогичен, конкретно по дате (CURRENT TIMESTAMP) почему-то сортировка не работает на моем сервере (ASC/DESC)
    if($order == "date") {$order = "id";}
    //Запрашиваем данные
    $limit_from = $page*$max;
    $result = $mysqli->query("SELECT * FROM history WHERE user_id=".$id." ORDER BY ".$order." ".$sort." LIMIT ".$limit_from.",".$max);
    if (!$result) {
        $temp_answer['error'] = True;
        $temp_answer['error_desc'] = 'MySQL error: '.$mysqli->errno;
        return $temp_answer;
    }
    $i = 0;
    while($temp = $result->fetch_assoc()) {
        $history[$i] = $temp;
        //Преобразуем нашу точность
        $history[$i]['amount'] = (string) round($history[$i]['amount'] / 10000, 4);
        $i++;
    }
    return $history;
}

//Обработка JSON-POST запросов
$jsonpost = file_get_contents('php://input');
$jsonpost = json_decode($jsonpost);
if($jsonpost){
    switch ($jsonpost->method) {
        case 'user_balance_get':
            //Если не получили данные о валюте - устанавливаем дефолтное значение - RUB
            if(!$jsonpost->currency) {$jsonpost->currency = "RUB";}
            echo json_encode(user_balance_get($mysqli,(int) $jsonpost->user_id,(string) $jsonpost->currency));
            break;
        case 'user_balance_add':
            echo json_encode(user_balance_add($mysqli,(int) $jsonpost->user_id,(float) $jsonpost->amount, (string) $jsonpost->comment));
            break;
        case 'user_balance_sub':
            echo json_encode(user_balance_sub($mysqli,(int) $jsonpost->user_id,(float) $jsonpost->amount, (string) $jsonpost->comment));
            break;
        case 'user_balance_subadd':
            echo json_encode(user_balance_subadd($mysqli,(int) $jsonpost->user_id,(float) $jsonpost->amount, (string) $jsonpost->comment));
            break;
        case 'user_balance_transfer':
            echo json_encode(user_balance_transfer($mysqli,(int) $jsonpost->id_sender,(int) $jsonpost->id_receiver,(float) $jsonpost->amount));
            break;
        case 'user_balance_history_get':
            //Если не получили данные о сортировке установим их дефолтные значения
            if(!$jsonpost->order) {$jsonpost->order = "id";}
            if(!$jsonpost->sort) {$jsonpost->sort = "ASC";}
            echo json_encode(user_balance_history_get($mysqli,(int) $jsonpost->user_id,(int) $jsonpost->max_transactions,(int) $jsonpost->page,(string) $jsonpost->order,(string) $jsonpost->sort));
            break;
        //Ошибка если ни один из наших методов не подходит для полученного запроса (например опечатка в запросе)
        default:
            http_response_code(400);
            $temp_answer['error'] = True;
            $temp_answer['error_desc'] = "Error: Method not found.";
            echo json_encode($temp_answer);
            break;
    }
} else {
    //Обработка HTTP запросов
    $method = explode('/', $_SERVER['REQUEST_URI']);
    switch ($method[2]) {
        case 'user_balance_get':
            //Если не получили данные о валюте - устанавливаем дефолтное значение - RUB
            if(!$method[4]) {$method[4] = "RUB";}
            echo json_encode(user_balance_get($mysqli,(int) $method[3],(string) $method[4]));
            break;
        case 'user_balance_add':
            echo json_encode(user_balance_add($mysqli,(int) $method[3],(float) $method[4], (string) $method[5]));
            break;
        case 'user_balance_sub':
            echo json_encode(user_balance_sub($mysqli,(int) $method[3],(float) $method[4], (string) $method[5]));
            break;
        case 'user_balance_subadd':
            echo json_encode(user_balance_subadd($mysqli,(int) $method[3],(float) $method[4], (string) $method[5]));
            break;
        case 'user_balance_transfer':
            echo json_encode(user_balance_transfer($mysqli,(int) $method[3],(int) $method[4],(float) $method[5]));
            break;
        case 'user_balance_history_get':
            //Если не получили данные о сортировке установим их дефолтные значения
            if(!$method[6]) {$method[6] = "id";}
            if(!$method[7]) {$method[7] = "ASC";}
            echo json_encode(user_balance_history_get($mysqli,(int) $method[3],(int) $method[4],(int) $method[5],(string) $method[6],(string) $method[7]));
            break;
        //Ошибка если ни один из наших методов не подходит для полученного запроса (например опечатка в запросе)
        default:
            http_response_code(400);
            $temp_answer['error'] = True;
            $temp_answer['error_desc'] = "Error: Method not found.";
            echo json_encode($temp_answer);
            break;
    }
}
$mysqli->close();
?>
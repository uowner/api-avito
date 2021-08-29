<?
$domain = "http://avito-api.ru";

function call_api(string $data) {
	global $domain;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $domain."/api/".$data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

echo "<h3>Минимальное задание</h3><hr>";
//
//
echo "<b>Запрашиваем баланс пользователя (user_balance_get/ID/CURRENCY) #1 и #3:</b><br>";
echo call_api("user_balance_get/1");
echo "<br>";
echo call_api("user_balance_get/3");
echo "<br><br><b>Проверим обработку ошибок, запросим несуществующего пользователя, пользователя с ID < 0 и пользователя с неправильным типом ID (float/string):</b><br>";
echo call_api("user_balance_get/43534");
echo "<br>";
echo call_api("user_balance_get/-13");
echo "<br><br>";
echo call_api("user_balance_get/1.95");
echo "<br><b>FLOAT-значение (1.95) округлилось до наименьшего целого.</b><br><br>";
echo call_api("user_balance_get/Alexander");
echo "<br><b>STRING-значение приравнялось к 0.</b>";
echo "<br><br><b>Попробуем запросить несуществующий метод:</b><br>";
echo call_api("user_bbalance_get/2");
echo "<hr>";
//
//
echo "<br><b>Добавим пользователю #1 3000 рублей на баланс (user_balance_add/ID/AMOUNT):</b><br>";
echo call_api("user_balance_add/1/3000");
echo "<br><br><b>Получили ответ error: false, значит ошибок не было и средства зачислены успешно. Проверяем:</b><br>";
echo call_api("user_balance_get/1");
echo "<br>";
echo "<br><b>Добавим пользователю #1 11 копеек (0.11 рубля) на баланс (user_balance_add/ID/AMOUNT):</b><br>";
echo call_api("user_balance_add/1/0.11");
echo "<br><br><b>Получили ответ error: false, значит ошибок не было и средства зачислены успешно. Проверяем:</b><br>";
echo call_api("user_balance_get/1");
echo "<br>";
echo "<br><b>Попробуем отправить отрицательное число (user_balance_add/ID/AMOUNT):</b><br>";
echo call_api("user_balance_add/1/-0.11");
echo "<br><br><b>Получили ответ error: true, данный метод поддерживает только положительные значения AMOUNT. Баланс не изменился:</b><br>";
echo call_api("user_balance_get/1");
echo "<br>";
echo "<br><b>Попробуем добавить денег несуществующему пользователю (user_balance_add/ID/AMOUNT):</b><br>";
echo call_api("user_balance_add/2523/1234");
echo "<br><br><b>Получили ответ error: true, и ошибку что пользователь не найден в БД.</b><hr>";
//
//
echo "<br><b>Спишем у пользователя #1 1000 рублей (user_balance_sub/ID/AMOUNT):</b><br>";
echo call_api("user_balance_sub/1/1000");
echo "<br><br><b>Получили ответ error: false, значит ошибок не было и средства списаны успешно. Проверяем:</b><br>";
echo call_api("user_balance_get/1");
echo "<br><br><b>Спишем у пользователя #1 22 копейки (0.22 рубля) (user_balance_sub/ID/AMOUNT):</b><br>";
echo call_api("user_balance_sub/1/0.22");
echo "<br><br><b>Получили ответ error: false, значит ошибок не было и средства списаны успешно. Проверяем:</b><br>";
echo call_api("user_balance_get/1");
echo "<br>";
echo "<br><b>Попробуем отправить отрицательное число (user_balance_sub/ID/AMOUNT):</b><br>";
echo call_api("user_balance_sub/1/-0.22");
echo "<br><br><b>Получили ответ error: true, данный метод поддерживает только положительные значения AMOUNT. Баланс не изменился:</b><br>";
echo call_api("user_balance_get/1");
echo "<br>";
echo "<br><b>Попробуем списать деньги у несуществующего пользователя (user_balance_sub/ID/AMOUNT):</b><br>";
echo call_api("user_balance_sub/2523/1234");
echo "<br><br><b>Получили ответ error: true, и ошибку что пользователь не найден в БД.</b><br>";
echo "<br><b>Попробуем списать 99999999999 рублей у пользователя #1 (user_balance_sub/ID/AMOUNT):</b><br>";
echo call_api("user_balance_sub/1/99999999999");
echo "<br><br><b>Получили ответ error: true, и сообщение что средств на балансе не хватает для проведения операции.</b><hr>";
//
//
echo "<br><b>Протестируем универсальный (положительное число - добавить, отрицательное - списать) метод subadd<br>Спишем у пользователя #1 1000.55 рублей (amount = -1000.55) (user_balance_subadd/ID/AMOUNT):</b><br>";
echo call_api("user_balance_subadd/1/-1000.55");
echo "<br><br><b>Получили ответ error: false, значит ошибок не было и средства списаны успешно. Проверяем:</b><br>";
echo call_api("user_balance_get/1");
echo "<br><br><b>Добавим пользователю #1 2222.15 рублей (amount = 2222.15) (user_balance_subadd/ID/AMOUNT):</b><br>";
echo call_api("user_balance_subadd/1/2222.15");
echo "<br><br><b>Получили ответ error: false, значит ошибок не было и средства зачислены успешно. Проверяем:</b><br>";
echo call_api("user_balance_get/1");
echo "<br><br><b>Попробуем списать 99999999999 рублей у пользователя #1 (user_balance_subadd/ID/AMOUNT):</b><br>";
echo call_api("user_balance_subadd/1/-99999999999");
echo "<br><br><b>Получили ответ error: true, и сообщение что средств на балансе не хватает для проведения операции.</b><hr>";
//
//
echo "<br><b>Протестируем перевод средств между пользователями<br>Спишем у пользователя #1 55.55 рублей и отправим их пользователю #4 (amount = 55.55) (user_balance_transfer/ID_SENDER/ID_RECEIVER/AMOUNT):</b><br>";
echo call_api("user_balance_transfer/1/4/55.55");
echo "<br><br><b>Получили ответ error: false, значит ошибок не было и средства списаны успешно. Проверяем:</b><br>";
echo call_api("user_balance_get/1");
echo "<br>";
echo call_api("user_balance_get/4");
echo "<br><br>";
echo "<b>Попробуем отправить отрицательное значение -55.55:</b><br>";
echo call_api("user_balance_transfer/1/4/-55.55");
echo "<br><br>";
echo "<b>Попробуем отправить 55.55 рублей несуществующему пользователю:</b><br>";
echo call_api("user_balance_transfer/1/435453/55.55");
//
//
echo "<br><br><br><h3>Дополнительное задание №1</h3><hr>";
echo "<b>Запросим баланс пользователя #1 в разных валютах: </b><br>";
echo call_api("user_balance_get/1/USD");
echo "<br>";
echo call_api("user_balance_get/1/EUR");
echo "<br>";
echo call_api("user_balance_get/1/GBP");
echo "<br>";
echo call_api("user_balance_get/1/AUD");
echo "<br>";
echo "<br><b>Запросим баланс пользователя #1 в несуществующей валюте: </b><br>";
echo call_api("user_balance_get/1/FFF");
echo "<br>";
//
//
echo "<br><br><br><h3>Дополнительное задание №2</h3><hr>";
echo "<br><b>Запросим историю пользователя #1 3 записи на страницу, страницу номер 0, сортировка по умолчанию: </b><br>";
echo call_api("user_balance_history_get/1/3/0");
echo "<br>";
echo "<br><b>Запросим историю пользователя #1 6 записей на страницу, страницу номер 0, сортировка по умолчанию: </b><br>";
echo call_api("user_balance_history_get/1/6/0");
echo "<br>";
echo "<br><b>Запросим историю пользователя #1 3 записи на страницу, страницу номер 1, сортировка по умолчанию: </b><br>";
echo call_api("user_balance_history_get/1/3/1");
echo "<br>";
echo "<br><b>Запросим историю пользователя #1 3 записей на страницу, страницу номер 2, сортировка по умолчанию: </b><br>";
echo call_api("user_balance_history_get/1/3/2");
echo "<br>";
echo "<br><b>Запросим историю пользователя #1 3 записи на страницу, страницу номер 1 сортировка по сумме операции по убыванию: </b><br>";
echo call_api("user_balance_history_get/1/3/1/amount/DESC");
echo "<br>";
echo "<br><b>Запросим историю пользователя #1 3 записей на страницу, страницу номер 1 сортировка по сумме операции по возрастанию: </b><br>";
echo call_api("user_balance_history_get/1/3/1/amount/ASC");
echo "<br>";
?>
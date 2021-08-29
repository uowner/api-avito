<?
$domain = "http://avito-api.ru";

function call_json_api($data) {
	global $domain;
	$data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
	$curl = curl_init($domain."/api");
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	   'Content-Type: application/json',
	   'Content-Length: ' . strlen($data_string))
	);
	$result = curl_exec($curl);
	curl_close($curl);
	return $result;
}

echo "<b>user_balance_get</b><br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1, "currency" => "GBP"];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 2, "currency" => "AUD"];
echo call_json_api($data);
echo "<br><br>";

echo "<b>user_balance_add (+1000.25 RUB)</b><br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_add", "user_id" => 1, "amount" => "1000.25"];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";

echo "<b>user_balance_sub (-200.25 RUB)</b><br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_sub", "user_id" => 1, "amount" => "200.25"];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";

echo "<b>user_balance_subadd (+200.5 RUB -100.5 RUB)</b><br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_subadd", "user_id" => 1, "amount" => "200.5"];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_subadd", "user_id" => 1, "amount" => "-100.5"];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";

echo "<b>user_balance_transfer (500.45 RUB)</b><br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 2];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_transfer", "id_sender" => 1, "id_receiver" => 2, "amount" => 500.45];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 1];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_get", "user_id" => 2];
echo call_json_api($data);
echo "<br><br>";

echo "<b>user_balance_history_get</b><br><br>";
$data = ["method" => "user_balance_history_get", "user_id" => 1, "max_transactions" => 3, "page" => 2, "order" => "date", "sort" => "DESC"];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_history_get", "user_id" => 2, "max_transactions" => 3, "page" => 0, "order" => "id", "sort" => "ASC"];
echo call_json_api($data);
echo "<br><br>";
$data = ["method" => "user_balance_history_get", "user_id" => 1, "max_transactions" => 3, "page" => 1, "order" => "amount", "sort" => "ASC"];
echo call_json_api($data);
echo "<br><br>";

?>
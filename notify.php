<?
  $queryUrl = 'https://cremaprodotti.bitrix24.ua/rest/1/46mtf1m9s5vhe8zz/im.notify';
     $queryData = http_build_query(array(
       'to' => 1,
       'message' => "Оплачен счет № 123",
       'type' => "system",
	));

 $curl = curl_init();
 curl_setopt_array($curl, array(
 CURLOPT_SSL_VERIFYPEER => 0,
 CURLOPT_POST => 1,
 CURLOPT_HEADER => 0,
 CURLOPT_RETURNTRANSFER => 1,
 CURLOPT_URL => $queryUrl,
 CURLOPT_POSTFIELDS => $queryData,
 ));

 $result = curl_exec($curl);
 curl_close($curl);

 $result = json_decode($result, 1);


	






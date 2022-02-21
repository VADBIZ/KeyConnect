<?php

	class amo {
		
		public $subdomain     = 'vadbizya';
		public $token_file    = 'tokens.txt';
		public $addLeads = "";
		public $errors = [
			301 => 'Moved permanently.',
			400 => 'Wrong structure of the array of transmitted data, or invalid identifiers of custom fields.',
			401 => 'Not Authorized. There is no account information on the server. You need to make a request to another server on the transmitted IP.',
			403 => 'The account is blocked, for repeatedly exceeding the number of requests per second.',
			404 => 'Not found.',
			500 => 'Internal server error.',
			502 => 'Bad gateway.',
			503 => 'Service unavailable.'
		];
		
		//конструктор класса
		function __construct($moreArgs) {
			$phpInput = file_get_contents('php://input');
			if (strlen($phpInput) == 0) {
				$data["name"] = $moreArgs["testName"];
				$data["email"] = $moreArgs["testEmail"];
				$data["phone"] = $moreArgs["testPhone"];
				$data["price"] = $moreArgs["testPrice"];
			} else {
				$data = json_decode(file_get_contents('php://input'), true);
			}
			echo $this->create($data, $moreArgs);
	   }
		
		//генерация token
		public function access(){
			
			$client_secret = 'cKuQmaMlp6fJzOhCevxRx6TB7Svgs9gTCS64szYSBotOYwvOF0Wxr40nFkS8HGo1';
			$client_id     = '5ccd08cd-6ec0-4b97-afbc-d949950bb9cc';
			$code          = 'def502009e4af6b4e99de2790339c45cc0d239f19c089374b40eb347809a0fc1e192ac483f37763abe5ded89481b049f4e0ad37e454d5d8ce9add5c9ce5ec4fc590f9565b4abd85319a19f4c47b918642d3a09d8adfc9e331d2ede1f3b71510ba8fb8e8ad71921b84ca757c8769078f8e171c83058235fdf476ca70daadafc15fbf0e352b9c256de3024f27de4a871aacbfa15136e55a63cded19b30961e912918cc6d8181744d0398640b31caa99b0cd67bd94af7e7d1e7b1dbf850b6e7568bbcd7db1a2cb5980dbf17086bf473ba14f4499c4dcf973c43c3ef3d8b329f95ffc1bc916a975d2b5c62936327c122358a5d6837c5666b32491128c12d4c3499d2030902c3e932d1d7405bd03527ce96b3bbb3920404b07189549643dab6946a95363c3edc7aa82fadd2d40f004ed5bb47c696d092c891deab7b556f5ff425a7b786a35e34a14ad38bcb43d1134f7ccbbe5aa4e9c9ef695e6e28323d3fa5408278418b2c7745e0dc50ab1fe9edff1cf8b98d19ce89f740072c70605ae1eb983b47f68fa11e8051eb851035c54f1b04cbf230f2cd018bac2a80a75fcf1462d8c221a1a9d3f54de627be9ac0b62cb5de9b8dab2d5c9385c3ab57dfa8032d7ae3b243519709a19694a55e9e';
			$redirect_uri  = 'https://test.vadev.ru/testHR/';
			
			$dataToken = file_get_contents($this->token_file);
			$dataToken = json_decode($dataToken, true);
			
			if ($dataToken["endTokenTime"] - 60 < time()) {

				$link = "https://$this->subdomain.amocrm.ru/oauth2/access_token";

				$data = [
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'grant_type'    => 'refresh_token',
					'refresh_token' => $dataToken["refresh_token"],
					'redirect_uri'  => $redirect_uri,
				];

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
				curl_setopt($curl, CURLOPT_URL, $link);
				curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
				$out = curl_exec($curl);
				
				$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);

				$code = (int)$code;

				if ($code < 200 || $code > 204) die( "Error $code. " . (isset($this->errors[$code]) ? $this->errors[$code] : 'Undefined error') );

				$response = json_decode($out, true);

				$arrParamsAmo = [
					"access_token"  => $response['access_token'],
					"refresh_token" => $response['refresh_token'],
					"token_type"    => $response['token_type'],
					"expires_in"    => $response['expires_in'],
					"endTokenTime"  => $response['expires_in'] + time(),
				];

				$arrParamsAmo = json_encode($arrParamsAmo);

				$f = fopen($this->token_file, 'w');
				fwrite($f, $arrParamsAmo);
				fclose($f);

				$access_token = $response['access_token'];

			} else {
				$access_token = $dataToken["access_token"];
			}
			
			return $access_token;
		}
		
		//создание сделки и подкрепление контакта
		public function create($data, $moreArgs) {
			
			$access_token = $this->access();

			$fields = [
				[
					"name" => $moreArgs['leadTitle'],
					"price" => (int) $data['price'],
					"responsible_user_id" => (int) $moreArgs['user_amo'],
					"pipeline_id" => (int) $moreArgs['pipeline_id'],
					"_embedded" => [
						"metadata" => [
							"form_name" => $moreArgs['leadTitle'],
							"category" => "forms",
							"form_sent_at" => strtotime(date("Y-m-d H:i:s")),
							"form_id" => 1,
							"form_page" => $moreArgs['pageTitle']
						],
						"contacts" => [
							[
								"first_name" => $data['name'],
								"custom_fields_values" => [
									[
										"field_code" => "EMAIL",
										"values" => [
											[
												"enum_code" => "WORK",
												"value" => $data['email']
											]
										]
									],
									[
										"field_code" => "PHONE",
										"values" => [
											[
												"enum_code" => "WORK",
												"value" => $data['phone']
											]
										]
									]
								]
							]
						]
					]
				]
			];

			$method = "/api/v4/leads/complex";

			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $access_token,
			];

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
			curl_setopt($curl, CURLOPT_URL, "https://$this->subdomain.amocrm.ru".$method);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_COOKIEFILE, 'amo/cookie.txt');
			curl_setopt($curl, CURLOPT_COOKIEJAR, 'amo/cookie.txt');
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			$out = curl_exec($curl);
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			$code = (int) $code;
			

			if ($code < 200 || $code > 204) die( "Error $code. " . (isset($this->errors[$code]) ? $this->errors[$code] : 'Undefined error') );


			$Response = json_decode($out, true);
			$output = 'ID добавленных элементов списков:' . PHP_EOL;
			
			foreach ($Response as $v) {
				$i++;
				if (is_array($v))
					$output .= $v['id'];
				if (count($Response) < $i) $output .= PHP_EOL;
			}
			return $output;
		}
		
		//получение вспомогательных ссылок
		public function getLinksHTML($method, $title){
			echo '<br>';
			echo "<a href='https://$this->subdomain.amocrm.ru/$method' target='_blank'>$title</a>";
			echo '<br>';
		}
		
		public function getLinks(){
			$this->getLinksHTML('api/v4/leads/custom_fields', 'Список utm меток');
			$this->getLinksHTML('api/v4/users', 'Список пользователей');
			$this->getLinksHTML('api/v4/contacts/custom_fields', 'Список полей контакта');
			$this->getLinksHTML('/api/v4/contacts', 'Список контактов в amoCRM');
		} 
		
	}

	//инициализация нашего класса
	$moreArgs = [
		"pipeline_id" => 5172685,
		"user_amo" => 7952275,
		"pageTitle" => "Тестовая страница",
		"leadTitle" => "Название формы",
		"testName" => "Тестовое имя",
		"testEmail" => "email@test.ru",
		"testPhone" => "+7 (901) 755-31-48",
		"testPrice" => 100
	];

	$addLeads = new amo($moreArgs);

?>
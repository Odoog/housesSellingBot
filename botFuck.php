<?php
	
	use GuzzleHttp\Client;
	class TelegramBot{
		protected $token = "542512961:AAHmfy_5vH2leGh5BuT4UYnDMpe_ouh7uMA";

		protected $updateId;

		protected function query($method, $params = []){
			try {
				$url = "https://api.telegram.org/bot";
				$url .= $this->token;

				$url .= "/" . $method;

				if(!empty($params)){
					$url .= "?" . http_build_query($params);
				}

				print("Quest! = " . $url . "\n");

				$client = new Client([
					'base_uri' => $url
				]);

				$result = $client->request('GET');

				return json_decode($result->getBody());
			} catch(Exception $e) {
				////print('Выброшено исключение: '.  $e->getMessage(). "\n");
			}

		}

		public function getUpdates(){
			$response = $this->query('getUpdates', [
				'offset' => $this->updateId + 1
			]);

			if(!empty($response->result)){
				$this->updateId = $response->result[count($response->result) - 1]->update_id;
			}

			return $response->result;
		}

		public function deleteMessage($chat_id, $messageId){
			$postfields = [
				'chat_id' => "$chat_id",
				'message_id' => "$messageId"
			];

			$output = $this->query("deleteMessage", $postfields);
			return $output;

		}

		public function editMessage($messageId, $chat_id, $text, $keyboardType = NULL, $answers = NULL){
			$postfields = [
				'chat_id' => "$chat_id",
				'text' => "$text",
				'message_id' => "$messageId"
			];

			if($keyboardType){
				if($keyboardType == "reply"){
					$keyboard = array(
						"keyboard" => $answers,
						"one_time_keyboard" => false, // можно заменить на FALSE,клавиатура скроется после нажатия кнопки автоматически при True
						"resize_keyboard" => true // можно заменить на FALSE, клавиатура будет использовать компактный размер автоматически при True
					);
				};
				if($keyboardType == "inline"){
					////print_r($answers);
					$keyboard = array(
						"inline_keyboard" => $answers
					);	
				}
				$postfields['reply_markup'] = json_encode($keyboard);
							////print_r($postfields);
			};
			$output = $this->query("editMessageText", $postfields);
			return $output;
		}

		public function editCaption($messageId, $chat_id, $text, $keyboardType = NULL, $answers = NULL){
			$postfields = [
				'chat_id' => "$chat_id",
				'caption' => "$text",
				'message_id' => "$messageId"
			];

			if($keyboardType){
				if($keyboardType == "reply"){
					$keyboard = array(
						"keyboard" => $answers,
						"one_time_keyboard" => false, // можно заменить на FALSE,клавиатура скроется после нажатия кнопки автоматически при True
						"resize_keyboard" => true // можно заменить на FALSE, клавиатура будет использовать компактный размер автоматически при True
					);
				};
				if($keyboardType == "inline"){
					////print_r($answers);
					$keyboard = array(
						"inline_keyboard" => $answers
					);	
				}
				$postfields['reply_markup'] = json_encode($keyboard);
							////print_r($postfields);
			};
			$output = $this->query("editMessageCaption", $postfields);
			return $output;
		}

		public function sendMessage($chat_id, $text, $keyboardType = NULL, $answers = NULL){
			$postfields = [
				'chat_id' => "$chat_id",
				'text' => "$text"
			];
			if($keyboardType){
				if($keyboardType == "reply"){
					$keyboard = array(
						"keyboard" => $answers,
						"one_time_keyboard" => false, // можно заменить на FALSE,клавиатура скроется после нажатия кнопки автоматически при True
						"resize_keyboard" => true // можно заменить на FALSE, клавиатура будет использовать компактный размер автоматически при True
					);
				};
				if($keyboardType == "inline"){
					$keyboard = array(
						"inline_keyboard" => $answers
					);
				};	
				$postfields['reply_markup'] = json_encode($keyboard);
				//print_r($postfields);
			};
			$output = $this->query("sendMessage", $postfields);
			return $output;
		}

		public function sendPhoto($chat_id, $photoAdress, $text = NULL, $keyboardType = NULL, $answers = NULL){
			$postfields = array(
				'photo' => $photoAdress,
				'chat_id' => $chat_id,
			);
			if($keyboardType){
				if($keyboardType == "reply"){
					$keyboard = array(
						"keyboard" => $answers,
						"one_time_keyboard" => false, // можно заменить на FALSE,клавиатура скроется после нажатия кнопки автоматически при True
						"resize_keyboard" => true // можно заменить на FALSE, клавиатура будет использовать компактный размер автоматически при True
					);
				};
				if($keyboardType == "inline"){
					$keyboard = array(
						"inline_keyboard" => $answers
					);	
				}
				$postfields['reply_markup'] = json_encode($keyboard);
			};
			if($text){
				$postfields['caption'] = $text;
			}
			$output = $this->query("sendPhoto", $postfields);
			return $output;
		}

		public function sendMapPoint($chat_id, $latitude, $longitude, $keyboardType = NULL, $answers = NULL){
			$postfields = array(
				'latitude' => $latitude,
				'longitude' => $longitude,
				'chat_id' => $chat_id,
			);
			if($keyboardType){
				if($keyboardType == "reply"){
					$keyboard = array(
						"keyboard" => $answers,
						"one_time_keyboard" => true, // можно заменить на FALSE,клавиатура скроется после нажатия кнопки автоматически при True
						"resize_keyboard" => true // можно заменить на FALSE, клавиатура будет использовать компактный размер автоматически при True
					);
				};
				if($keyboardType == "inline"){
					$keyboard = array(
						"inline_keyboard" => $answers,
					);	
				}
				$postfields['reply_markup'] = json_encode($keyboard);
			};
			$output = $this->query("sendLocation", $postfields);
			return $output;
		}

		public function reactQuery($queryId){

			$postfields = array(
				'callback_query_id' => $queryId
			);

			$this->query("answerCallbackQuery", $postfields);
		}
	};

?>
<?php

	require __DIR__ . '/vendor/autoload.php';

	include('botFuck.php');

	$texts = Texts::getTexts();

	//print('..........');

	//print_r($areas);

	//print('..........');

	$telegramApi = new TelegramBot();

	$areas = [
		"Ижевск" => ["Ленинский район", "Октябрьский район", "Индустриальный район", "Устиновский район", "Первомайский район"],
		"Пермь" => ["Дзержинский", "Индустриальный", "Кировский", "Ленинский", "Мотовилихинский", "Орджоникидзевский", "Свердловский"], 
		"Смоленск" => ["Заднепровский район", "Ленинский", "Промышленный"]
	];

	$buttons = [
		"старт" => ["Обратная связь", "Обьекты", "Карта"],
		"обратнаясвязь" => ["Позвоните мне", "Помощь", "Назад"],
		"city_undefine" => ["Ижевск", "Пермь", "Смоленск"],
		"позвонитемне_define" => ["Изменить номер", "Назад"],
		"city_define" => ["Все дома", "Дома по районам", "Дома по планировкам", "Назад"],
		"showAllHouses" => ["Выбрать этот дом"],
		"showApartaments" => ["Выбрать эту квартиру"]
	];

	$cityPictures = [
		"Ижевск" => "AgADAgAD06gxG1Ya4UhC-qzdZNH3tVcEMw4ABNhVtgABmRzEp7a9BAABAg",
		"Пермь" => "AgADAgAD1agxG1Ya4Uibv1Bc7YZPNl_tAw4ABCpTD4TVVJs_VvcDAAEC",
		"Смоленск" => "AgADAgAD1qgxG1Ya4UhTfbS1bBw1Ns0anA4ABGE6r2v-fxvd-RcCAAEC"
	];

	$layouts = [
		0 => "Студия",
		1 => "1 Комнатная", 
		2 => "2 Комнатная",
		3 => "3 Комнатная",
		4 => "4 Комнатная"
	];

	class Texts{
		public static function getTexts(){
			$answer = [];
			$textsArray = mysqlQuest("SELECT * FROM `texts`", "Group");
			while($newText = mysqli_fetch_assoc($textsArray)){
				$answer[$newText["name"]] = $newText["text"];
			}
			return $answer;
		}
	}

	function makeAnswerArray($type, $answerArray, $callbacks = []){
		$updateAnswerArray = [];
		if($type == "reply"){
			foreach ($answerArray as $ind => $value) {
				$updateAnswerArray[] = ["text" => $value];
			}
			$updateAnswerArray = [$updateAnswerArray];
		}
		if($type == "inline"){
			foreach ($answerArray as $ind => $value) {
				if($callbacks[$ind]) $callback_data = $callbacks[$ind];
				else $callback_data = $value;
				$updateAnswerArray[] = [["text" => $value, "callback_data" => $callback_data]];
			}
			$updateAnswerArray = $updateAnswerArray;
		}
		return $updateAnswerArray;
	}

	function mysqlQuest($quest, $type = "Single"){
		try{
			print("MYSQL : " . $quest . "\n");
			$connection = mysqli_connect('127.0.0.1', "root", '', 'house');
			//print("Quest  = " . $quest . "\n");
			$answer = mysqli_query($connection, $quest);
			if($answer){
				if($type == "Single") $answer = mysqli_fetch_assoc($answer);
				return $answer;
			} else {
				return false;
			}
		} catch(Exception $e) {
			print('Выброшено исключение: '.  $e->getMessage(). "\n");
		}
	}

	class Action{

		public static function del($messageId){
			global $chatId, $telegramApi;
			$telegramApi->deleteMessage($chatId, $messageId);
		}

		public static function pic($picId, $message = NULL, $type = NULL, $buttons = NULL, $callbacks = NULL){
			global $chatId, $telegramApi;
			print("! " . $buttons . " " . $callbacks . "\n");
			if($buttons){
				$telegramApi->sendPhoto($chatId, $picId, $message, $type, makeAnswerArray($type, $buttons, $callbacks));
			} else {
				$telegramApi->sendPhoto($chatId, $picId, $message);
			}
		}
//
		public static function text($message, $type, $buttons = NULL, $callbacks = NULL){
			global $chatId, $telegramApi, $user;
			if($user["lastMessageId"] != 0){
				$editMessageId = $user["lastMessageId"];
				if($buttons){
					$sendMessageObject = $telegramApi->editMessage($editMessageId, $chatId, $message, $type, makeAnswerArray($type, $buttons, $callbacks));
					print_r($sendMessageObject);
					User::updateLastMessageId($sendMessageObject->result->message_id);
				} else {
					$sendMessageObject = $telegramApi->editMessage($editMessageId, $chatId, $message);
					print_r($sendMessageObject);
					User::updateLastMessageId($sendMessageObject->result->message_id);
				}	
			} else {
				if($buttons){
					$sendMessageObject = $telegramApi->sendMessage($chatId, $message, $type, makeAnswerArray($type, $buttons, $callbacks));
					print_r($sendMessageObject);
					User::updateLastMessageId($sendMessageObject->result->message_id);
				} else {
					$sendMessageObject = $telegramApi->sendMessage($chatId, $message);
					print_r($sendMessageObject);
					User::updateLastMessageId($sendMessageObject->result->message_id);
				}
			}
		}

		public static function point($x, $y, $type = NULL, $buttons = NULL, $callbacks = NULL){
			global $chatId, $telegramApi;
			if($buttons){
				$telegramApi->sendMapPoint($chatId, $x, $y, $type, makeAnswerArray($type, $buttons, $callbacks));
			} else {
				$telegramApi->sendMapPoint($chatId, $x, $y);
			}
		}
		public static function reactQuery(){
			global $queryId, $telegramApi;
			$telegramApi->reactQuery($queryId);	
		}

		public static function houses($city, $area = "All"){
			global $chatId, $telegramApi, $buttons;
			if($area == "All"){
				$houses = mysqlQuest("SELECT * FROM `houses` WHERE `city` = '$city'", "Group");
			} else {
				$houses = mysqlQuest("SELECT * FROM `houses` WHERE `city` = '$city' AND `area` = '$area'", "Group");
			}
			
			$trueInWhileSituation = false;

			while($house = mysqli_fetch_assoc($houses)){
				$trueInWhileSituation = true;
				$housePic = $house["photo"];
				$text = $house["adress"];
				Action::pic($housePic, $text, "inline", $buttons["showAllHouses"], [$house["Id"]]);
			}

			if(!$trueInWhileSituation){
				print("Домов с такими параметрами не найдено, Ошибка");
			}
		}

		public static function apartaments($house){
			global $chatId, $telegramApi, $buttons;
			$apartamentsArr = mysqlQuest("SELECT * FROM `apartaments` WHERE `house` = $house", "Group");
			while($apartament = mysqli_fetch_assoc($apartamentsArr)){
				$apartPic = $apartament["photo"];
				if($apartament["layout"] == 0) $text = "Студия";
				else $text = $apartament["layout"] . "-тная квартира";
				Action::pic($apartPic, $text, "inline", $buttons["showApartaments"], [$apartament["ind"]]);
			}
		}

		public static function apartament($apartamentId){
			global $chatId, $telegramApi, $buttons;
			$apartamentShow = mysqlQuest("SELECT * FROM `apartaments` WHERE `ind` = $apartamentId");

		}
	}


	class System{

		public static function makeLayoutButtons(){
			global $layouts, $user;
			$buttons = $layouts;
			$city = $user['city'];
			foreach ($buttons as $key => $value) {
				$freeApartaments = mysqlQuest("SELECT SUM(`quantity`) AS 'summ' FROM `apartaments` WHERE `city` = '$city' && `layout` = $key", 'Single');
				$freeApartaments = $freeApartaments['summ'];
				if(!$freeApartaments) $freeApartaments = 0;
				$strPlus = ' (Осталось ' . $freeApartaments . ' квартир)';
				$buttons[$key] .= $strPlus;
			}
			$answer = [];
			foreach ($buttons as $key => $value) {
				$answer[] = $value;
			}
			//print_r($answer);
			return $answer;
		}
	}

	class User{
		public static $currentStage;
		public static function newUser(){
			global $userId;
			mysqlQuest("INSERT INTO `users`(`id`, `stage`) VALUES ('$userId', 'старт')");
		}
		
		public static function updatePhoneNumber($newPhoneNumber){
			global $userId, $user;
			mysqlQuest("UPDATE `users` SET `phoneNumber`= '$newPhoneNumber' WHERE `id` = $userId");
			$user["phoneNumber"] = $newPhoneNumber;
		}

		public static function updateName($newName){
			global $userId, $user;
			mysqlQuest("UPDATE `users` SET `name`= '$newName' WHERE `id` = $userId");
			$user["name"] = $newName;
		}

		public static function updateStage($newStage){
			global $userId, $user;
			mysqlQuest("UPDATE `users` SET `stage`= '$newStage' WHERE `id` = $userId"); 
			$user["stage"] = $newStage;
		}

		public static function updateCity($newCity){
			global $userId, $user;
			mysqlQuest("UPDATE `users` SET `city`= '$newCity' WHERE `id` = $userId"); 
			$user["city"] = $newCity;	
		}

		public static function updateArea($newArea){
			global $userId, $user;
			mysqlQuest("UPDATE `users` SET `area`= '$newArea' WHERE `id` = $userId");
			$user["area"] = $newArea;
		}

		public static function getUser($userId){
			return mysqlQuest("SELECT * FROM `users` WHERE `id` = $userId");
		}

		public static function updateLastMessageId($newMessageId){
			global $userId, $user;
			mysqlQuest("UPDATE `users` SET `lastMessageId` = '$newMessageId' WHERE `id` = $userId");
			$user["lastMessageId"] = $newMessageId;
		}
	}

	while(true){
		
		$updates = $telegramApi->getUpdates();

		foreach($updates as $update){

			sleep(1);

			//
			// $dest = imagecreatefromjpeg('1.jpg');
			// $src = imagecreatefromjpeg('2.jpg');
			// imagecopymerge($dest, $src, 10, 9, 0, 0, 181, 180, 100);
			// imagejpeg($dest, '3.jpg');
			// continue;
			//

			//print_r($update);

			if($update->callback_query){
				$queryId = $update->callback_query->id;
				$chatId = $update->callback_query->message->chat->id;
				$userId = $update->callback_query->from->id;
				$messageText = $update->callback_query->data;
				print("Callback = " . $messageText . "\n"); 
				Action::reactQuery();
			} else {
				$chatId = $update->message->chat->id;
				$userId = $update->message->from->id;
				$messageText = $update->message->text;
			}

			$user = User::getUser($userId);
			if($user){
				User::$currentStage = $user['stage'];
			} else {
				Action::text($texts['старт'] , "reply", $buttons['старт']);
				User::newUser();
				continue;
			}
			switch (User::$currentStage){
				case 'старт':
					if($messageText == "Обратная связь"){
						Action::text($texts['обратнаясвязь'], "reply", $buttons['обратнаясвязь']);	
						User::updateStage('обратнаясвязь');
					}
					if($messageText == "Обьекты"){
						Action::text($texts['city_undefine'], "inline", $buttons['city_undefine']);
						User::updateStage('city_undefine');
					}
					if($messageText == "Карта"){
						Action::point(56.858289, 53.182234);
						Action::text($texts['старт'], $buttons['старт']);
					}
					break;

				case 'обратнаясвязь':
					if($messageText == "Позвоните мне"){
						if($user['phoneNumber'] != "null"){
							$namePhone = $user['phoneNumber'] . ' (' . $user['name'] . ')';
							Action::text($texts['позвонитемне_define'], "reply", array_merge((array)$namePhone, $buttons['позвонитемне_define']));
							User::updateStage("позвонитемне_define");
						} else {
							Action::text($texts['позвонитемне_undefine']);
							User::updateStage("позвонитемне_undefine");
						}
					}
					if($messageText == "Назад"){
						Action::text($texts['старт'], "reply", $buttons['старт']);
						User::updateStage("старт");
					}
					break;
				case 'city_undefine':
					switch ($messageText){
						case 'Ижевск':
							User::updateCity('Ижевск');
							User::updateStage("city_define");
							Action::text("Вы выбрали город " . $messageText, "inline", $buttons['city_define']);
							break;
						case 'Пермь':
							User::updateCity('Пермь');
							User::updateStage("city_define");
							Action::text("Вы выбрали город " . $messageText, "inline", $buttons['city_define']);
							break;
						case 'Смоленск':
							User::updateCity('Смоленск');
							User::updateStage("city_define");
							Action::text("Вы выбрали город " . $messageText, "inline", $buttons['city_define']);
							break;
					}
					break;
				case 'city_define':
					switch ($messageText) {
						case 'Назад':
							Action::text($texts['city_undefine'], "inline", $buttons['city_undefine']);
							User::updateStage('city_undefine');
							break;
						case 'Все дома':
							User::updateStage("showAllHouses");
							Action::houses($user["city"]);
							break;
						case 'Дома по планировкам':
							User::updateStage("layout_undefine");
							Action::text($texts['layout_undefine'], "inline", System::makeLayoutButtons());
							break;
						case 'Дома по районам':
							User::updateStage("area_undefine");
							Action::text($texts['area_undefine'], "inline", array_merge($areas[$user["city"]]), (array)"Назад");
							break;
					}
					break;

				case 'showAllHouses':
					User::updateStage("showApartaments");
					Action::apartaments($messageText);
					break;

				case 'showApartaments':
					User::updateStage("showApartement");
					Action::apartament($messageText);
					break;

				case "area_undefine":
					User::updateStage("showAllHouses");
					Action::houses($user["city"], $messageText);
					break;

				case 'позвонитемне_undefine':
					User::updatePhoneNumber($messageText);
					User::updateStage("вашеимя_undefine");
					Action::text($texts['вашеимя_undefine']);
					break;
				case 'вашеимя_undefine':
					User::updateName($messageText);
					User::updateStage("позвонитемне_define");
					$namePhone = $user['phoneNumber'] . ' (' . $user['name'] . ')';
					Action::text($texts['позвонитемне_define'], "reply", array_merge((array)$namePhone, $buttons['позвонитемне_define']));
					break;

				case 'позвонитемне_define':
					if($messageText == "Назад"){
						Action::text($texts['обратнаясвязь'], "reply", $buttons['обратнаясвязь']);	
						User::updateStage('обратнаясвязь');
					}
					if($messageText == "Изменить номер"){
						Action::text($texts['позвонитемне_undefine']);
						User::updateStage("позвонитемне_undefine");
					} else {

					}
					break;

				default:
					break;
			}
		};
	};
?>
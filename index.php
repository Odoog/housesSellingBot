<?php
		
	mb_internal_encoding("UTF-8");

	require __DIR__ . '/vendor/autoload.php';

	include('botFuck.php');

	$texts = Texts::getTexts();

	////print('..........');

	////print_r($areas);

	////print('..........');

	$telegramApi = new TelegramBot();

	$areas = [
		"Ижевск" => ["Ленинский", "Октябрьский", "Индустриальный", "Устиновский", "Первомайский"],
		"Пермь" => ["Дзержинский", "Индустриальный", "Кировский", "Ленинский", "Мотовилихинский", "Орджоникидзевский", "Свердловский"], 
		"Смоленск" => ["Заднепровский район", "Ленинский", "Промышленный"]
	];

	$buttons = [
		"старт" => ["Обратная связь", "Обьекты"],
		"обратнаясвязь" => ["Позвоните мне", "Помощь", "Назад"],
		"city_undefine" => ["Ижевск", "Пермь", "Смоленск"],
		"позвонитемне_define" => ["Изменить номер", "Назад"],
		"city_define" => ["Все дома", "Дома по районам", "Дома по планировкам", "Назад"],
		"showAllHouses" => ["Выбрать этот дом"],
		"showApartaments" => ["Оставить заявку на звонок", "Запросить стоимость"],
		"layout_undefine" => ["Студия", "1 Комнатная", "2 Комнатная", "3 Комнатная", "4 Комнатная", "5 Комнатная"],
		"старт админ" => ["Изменить текста", "Добавить дом", "Добавить квартиру"]
	];

	$cityPictures = [
		"Ижевск" => "AgADAgAD06gxG1Ya4UhC-qzdZNH3tVcEMw4ABNhVtgABmRzEp7a9BAABAg",
		"Пермь" => "AgADAgAD1agxG1Ya4Uibv1Bc7YZPNl_tAw4ABCpTD4TVVJs_VvcDAAEC",
		"Смоленск" => "AgADAgAD1qgxG1Ya4UhTfbS1bBw1Ns0anA4ABGE6r2v-fxvd-RcCAAEC"
	];

	//Нахождение низлежайшего блока вызывает сомнения, части кода связаны с построением статистики квартир
	/*

	$layouts = [
		0 => "Студия",
		1 => "1 Комнатная", 
		2 => "2 Комнатная",
		3 => "3 Комнатная",
		4 => "4 Комнатная"
	]; */

	class Texts{
		public static function getTexts(){
			$answer = [];
			$textsArray = mysqlQuest("SELECT * FROM `texts`", "Group");
			while($newText = mysqli_fetch_assoc($textsArray)){
				$answer[$newText["name"]] = $newText["text"];
			}
			return $answer;
		}

		public static function updateTexts($ind, $newText){
			mysqlQuest("UPDATE `texts` SET `text` = '$newText' WHERE `ind` = $ind");
		}

		public static function refreshTexts(){
			global $texts;
			$texts = Texts::getTexts();
		}

		public static function makeButtonsArray(){
			global $texts;
			$answer = [];
			$textsArray = mysqlQuest("SELECT * FROM `texts`", "Group");
			while($newText = mysqli_fetch_assoc($textsArray)){
				print($value . " ");
				if(mb_strlen($value) > 20) $answer["buttons"][] = mb_substr($newText['text'], 0, 20);
				else $answer["buttons"][] = $newText['text'];
				$answer["callbacks"][] = $newText['ind'];
			}
			$answer["buttons"][] = 'Назад';
			//print_r($answer);
			return $answer;
		}
	}

	function makeAnswerArray($type, $answerArray, $callbacks = [], $oneLine = false){
		$updateAnswerArray = [];
		if($type == "reply"){
			foreach ($answerArray as $ind => $value) {
				$updateAnswerArray[] = ["text" => $value];
			}
			$updateAnswerArray = [$updateAnswerArray];
		}
		if($type == "inline"){
			if($oneLine){
				foreach ($answerArray as $ind => $value) {
					if($callbacks[$ind]) $callback_data = $callbacks[$ind];
					else $callback_data = $value;
					$updateAnswerArray[] = ["text" => $value, "callback_data" => $callback_data];
				}
				$updateAnswerArray = [$updateAnswerArray];
			} else {
				foreach ($answerArray as $ind => $value) {
					if($callbacks[$ind]) $callback_data = $callbacks[$ind];
					else $callback_data = $value;
					$updateAnswerArray[] = [["text" => $value, "callback_data" => $callback_data]];
				}
				$updateAnswerArray = $updateAnswerArray;
			}
		}
		//print_r($updateAnswerArray);
		return $updateAnswerArray;
	}

	//Нахождение низлежайшего блока вызывает сомнения, части кода связаны с построением информации о квартире
	/* function textReplace($oldText, $apartament){
		$replaceStruct = [
			'Этаж' => $apartament['floor'],
			'Колличество' => $apartament['quantity']
		];

		foreach ($replaceStruct as $key => $value) {
			$oldText = str_replace('[' . $key . ']', $value, $oldText);
		}
		//print("^^^^^^^^^^^ " . $oldText);
		return $oldText;
	} */

	function mysqlQuest($quest, $type = "Single"){
		try{
			print("MYSQL : " . $quest . "\n");
			$connection = mysqli_connect('127.0.0.1', "root", '', 'house');
			$connection->set_charset('utf8mb4');

			////print("Quest  = " . $quest . "\n");
			$answer = mysqli_query($connection, $quest);
			if($answer){
				if($type == "Single" && gettype() != "boolean") $answer = mysqli_fetch_assoc($answer);
				return $answer;
			} else {
				return false;
			}
		} catch(Exception $e) {
			//print('Выброшено исключение: '.  $e->getMessage(). "\n");
		}
	}

	class Action{

		public static function editObjectArray($index, $value){
			global $user;
			$currentArray = json_decode($user['objectArray']);
			$currentArray[$index] = $value;
			User::updateObjectArray($currentArray);
		}

		public static function editMes($editMessageId, $message, $type, $buttons = NULL, $callbacks = NULL){
			global $chatId, $telegramApi;
			$sendMessageObject = $telegramApi->editMessage($editMessageId, $chatId, $message, $type, makeAnswerArray($type, $buttons, $callbacks));
			//print_r($sendMessageObject);
			return $sendMessageObject->result->message_id;
		}

		public static function editCap($editMessageId, $message, $type, $buttons = NULL, $callbacks = NULL){
			global $chatId, $telegramApi;
			$sendMessageObject = $telegramApi->editCaption($editMessageId, $chatId, $message, $type, makeAnswerArray($type, $buttons, $callbacks));
			//print_r($sendMessageObject);
			return $sendMessageObject->result->message_id;
		}

		public static function del($messageId){
			global $chatId, $telegramApi;
			$telegramApi->deleteMessage($chatId, $messageId);
		}

		public static function deleteLastMessage(){
			global $user;
			Action::del($user["lastMessageId"]);
			User::updateLastMessageId(0); //Выключаем последнее сообщение
		}

		public static function pic($picId, $message = NULL, $type = NULL, $buttons = NULL, $callbacks = NULL, $oneLine = false){
			global $chatId, $telegramApi;
			User::updateLastMessageId(0);
			//print("! " . $buttons . " " . $callbacks . "\n");
			if($buttons){
				$sendMessageObject = $telegramApi->sendPhoto($chatId, $picId, $message, $type, makeAnswerArray($type, $buttons, $callbacks, $oneLine));
			} else {
				$sendMessageObject = $telegramApi->sendPhoto($chatId, $picId, $message);
			}
			//print_r($sendMessageObject);
			return $sendMessageObject->result->message_id;
		}
		public static function text($message, $type, $buttons = NULL, $callbacks = NULL){
			global $chatId, $telegramApi, $user;
			if($lastMessageType == "inline" && $type == "reply"){
				User::updateLastMessageId(0);
			}

			if($lastMessageType == "reply"){
				User::updateLastMessageId(0);
			}

			$lastMessageType = $type;
			if($user["lastMessageId"] != 0){
				$editMessageId = $user["lastMessageId"];
				if($buttons){
					$sendMessageObject = $telegramApi->editMessage($editMessageId, $chatId, $message, $type, makeAnswerArray($type, $buttons, $callbacks));
					//print_r($sendMessageObject);
					User::updateLastMessageId($sendMessageObject->result->message_id);
				} else {
					$sendMessageObject = $telegramApi->editMessage($editMessageId, $chatId, $message);
					//print_r($sendMessageObject);
					User::updateLastMessageId($sendMessageObject->result->message_id);
				}	
			} else {
				if($buttons){
					$sendMessageObject = $telegramApi->sendMessage($chatId, $message, $type, makeAnswerArray($type, $buttons, $callbacks));
					//print_r($sendMessageObject);
					User::updateLastMessageId($sendMessageObject->result->message_id);
				} else {
					$sendMessageObject = $telegramApi->sendMessage($chatId, $message);
					//print_r($sendMessageObject);
					User::updateLastMessageId($sendMessageObject->result->message_id);
				}
			}
			if($type == "reply"){
				User::updateLastMessageId(0);
			}
			return $sendMessageObject->result->message_id;
		}

		public static function point($x, $y, $type = NULL, $buttons = NULL, $callbacks = NULL){
			global $chatId, $telegramApi;
			User::updateLastMessageId(0);
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

		public static function houses($city, $area = "All", $layout = -1){
			global $chatId, $telegramApi, $buttons, $user, $cityPictures, $texts;
			if(!$area){
				// Пользователь может искать в любой area -> Вероятный переход с квартир
				$area = "All"; 
			}

			$sendMessageIds = [];
			$sendId = Action::pic($cityPictures[$user['city']], $texts['showAllHouses'] . " " . $user['city'], "inline", ['Назад']); //Непосредственно добавляем фото города с кнопкой назад
			$sendMessageIds[] = $sendId;

			$trueInWhileSituation = false;
			if($layout >= 0){
				$houses = mysqlQuest("SELECT * FROM `houses` WHERE `city` = '$city'", "Group");
				while($house = mysqli_fetch_assoc($houses)){
					$houseId = $house['Id'];
					$apartaments = mysqlQuest("SELECT * FROM `apartaments` WHERE `layout` = $layout AND `house` = $houseId", "Group");
					if($apartaments){
						$trueInWhileSituation = true;
						$housePic = $house["photo"];
						$text = $house["adress"];
						$sendId = Action::pic($housePic, $text, "inline", $buttons["showAllHouses"], [$house["Id"]]);
						$sendMessageIds[] = $sendId;
					}
				}
			} else {
				if($area == "All"){
					$houses = mysqlQuest("SELECT * FROM `houses` WHERE `city` = '$city'", "Group");
				} else {
					$houses = mysqlQuest("SELECT * FROM `houses` WHERE `city` = '$city' AND `area` = '$area'", "Group");
				}
				while($house = mysqli_fetch_assoc($houses)){
					$trueInWhileSituation = true;
					$housePic = $house["photo"];
					$text = $house["adress"];
					$sendId = Action::pic($housePic, $text, "inline", $buttons["showAllHouses"], [$house["Id"]]);
					$sendMessageIds[] = $sendId;
				}
			}

			if(!$trueInWhileSituation){
				//print("Домов с такими параметрами не найдено, Ошибка");
			}
			User::updateObjectArray($sendMessageIds);
		}

		public static function apartaments($house){
			global $chatId, $telegramApi, $buttons, $user, $texts;
			$sendMessageIds = [];

			$houseObject = mysqlQuest("SELECT * FROM `houses` WHERE `Id` = $house");
			$sendId = Action::pic($houseObject['photo'], $texts['showAllApartaments'] . " " . $houseObject['adress'], "inline", ['Назад']); //Непосредственно добавляем фото дома с кнопкой назад
			$sendMessageIds[] = $sendId;

			if($user["layout"] >= 0){
				$layout = $user["layout"];
				$apartamentsArr = mysqlQuest("SELECT * FROM `apartaments` WHERE `house` = $house AND `layout` = $layout", "Group");
			} else {
				$apartamentsArr = mysqlQuest("SELECT * FROM `apartaments` WHERE `house` = $house", "Group");
			}
			$apartamentNumber = 0; //Число обозначающее номер квартиры
			while($apartament = mysqli_fetch_assoc($apartamentsArr)){
				$apartamentNumber += 1; //Фото дома - первое сообщение
				$apartPic = $apartament["photo"];
				if($apartament["layout"] == 0) $text = "Студия";
				else $text = $apartament["layout"] . "-тная квартира";
				$sendId = Action::pic($apartPic, $text, "inline", $buttons["showApartaments"], ["o" . $apartamentNumber, "i" . json_encode([$apartamentNumber, $apartament["ind"]])], true); //order + information
				$sendMessageIds[] = $sendId;
			}
			User::updateObjectArray($sendMessageIds);	
		}

		public static function apartament($apartamentId){
			global $chatId, $telegramApi, $buttons, $texts;
			$apartamentShow = mysqlQuest("SELECT * FROM `apartaments` WHERE `ind` = $apartamentId");
			$houseId = $apartamentShow["house"];
			$houseShow = mysqlQuest("SELECT * FROM `houses` WHERE `Id` = $houseId");
			Action::point($houseShow["latitude"], $houseShow["longitude"]);
			Action::text(textReplace($texts['showApartament'], $apartamentShow), "inline", $buttons['showApartament']);
		}

		public static function deleteAllObjects(){
			global $chatId, $user;
			$houseArray = json_decode($user['objectArray']);
			foreach (array_reverse($houseArray) as $key => $value) {
				Action::del($value);
			}
		}
	}


	class System{
		//Нахождение низлежайшего блока вызывает сомнения, части кода связаны с построением информации о колличестве квартир в стеке
		/* public static function makeLayoutButtons(){
			global $layouts, $user;
			$buttons = $layouts;
			$city = $user['city'];
			foreach ($buttons as $key => $value) {
				$freeApartaments = mysqlQuest("SELECT SUM(`quantity`) AS 'summ' FROM `apartaments` WHERE `city` = '$city' AND `layout` = $key", 'Single');
				$freeApartaments = $freeApartaments['summ'];
				if(!$freeApartaments) $freeApartaments = 0;
				$strPlus = ' (Осталось ' . $freeApartaments . ' квартир)';
				$buttons[$key] .= $strPlus;
			}
			$answer = [];
			foreach ($buttons as $key => $value) {
				$answer[] = $value;
			}
			$answer[] = "Назад";
			////print_r($answer);
			return $answer;
		}
		*/
	}

	class Admin{
		public static function updateTextToEdit($newText){
			global $userId, $user;
			mysqlQuest("UPDATE `users` SET `admin_textToEdit`= '$newText' WHERE `id` = $userId");
			$user['admin_textToEdit'] = $arrayInJSON;
		}
	}

	class User{
		public static $currentStage;
		public static function newUser(){
			global $userId;
			mysqlQuest("INSERT INTO `users`(`id`, `stage`) VALUES ('$userId', 'старт')");
		}

		public static function updateObjectArray($newArray){
			global $userId, $user;
			$arrayInJSON = json_encode($newArray);
			mysqlQuest("UPDATE `users` SET `objectArray`= '$arrayInJSON' WHERE `id` = $userId");
			$user['objectArray'] = $arrayInJSON;
		}

		public static function updateLayout($newLayout){
			global $userId, $user;
			mysqlQuest("UPDATE `users` SET `layout`= $newLayout WHERE `id` = $userId");
			$user["layout"] = $newLayout;
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
			mysqlQuest("UPDATE `users` SET `lastMessageId` = $newMessageId WHERE `id` = $userId");
			$user["lastMessageId"] = $newMessageId;
		}
	}

	while(true){
		
		$updates = $telegramApi->getUpdates();

		foreach($updates as $update){

			//
			// $dest = imagecreatefromjpeg('1.jpg');
			// $src = imagecreatefromjpeg('2.jpg');
			// imagecopymerge($dest, $src, 10, 9, 0, 0, 181, 180, 100);
			// imagejpeg($dest, '3.jpg');
			// continue;
			//

			////print_r($update);

			if($update->callback_query){
				$queryId = $update->callback_query->id;
				$chatId = $update->callback_query->message->chat->id;
				$userId = $update->callback_query->from->id;
				$messageText = $update->callback_query->data;
				//print("Callback = " . $messageText . "\n"); 
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

			//Проверка на общие кнопки снизу

			if($messageText == "Обратная связь"){
				Action::text($texts['обратнаясвязь'], "reply", $buttons['обратнаясвязь']);	
				User::updateStage('обратнаясвязь');
				continue;
			}
			if($messageText == "Обьекты"){
				Action::text($texts['city_undefine'], "inline", $buttons['city_undefine']);
				User::updateStage('city_undefine');
				continue;
			}

			//Проверка этапов 
			
			switch (User::$currentStage){
				case 'старт':
					if($messageText == "Сосочка"){
						Action::text($texts['старт'], "reply", $buttons['старт админ']);
						User::updateStage('старт админ');
					}
					break;

				case 'старт админ':
					switch ($messageText) {
						case 'Юзер':
							Action::text($texts['старт'] , "reply", $buttons['старт']);
							User::updateStage('старт');
							break;
						case 'Изменить текста':
							Action::text($texts['all_texts'], "inline", Texts::makeButtonsArray()["buttons"], Texts::makeButtonsArray()["callbacks"]);
							User::updateStage('all_texts');
							break;
						case 'Добавить дом':

							break;
						case 'Добавить квартиру':

							break;
					}
					break;

				case 'all_texts':
					if($messageText == "Назад"){
						User::updateLastMessageId(0);
						Action::text($texts['старт'], "reply", $buttons['старт админ']);
						User::updateStage('старт админ');

					} else {
						Admin::updateTextToEdit($messageText);
						Action::text($texts['all_texts_edit']);
						User::updateStage('all_texts_edit');
					}
					break;

				case 'all_texts_edit':
					Texts::updateTexts($user['admin_textToEdit'], $messageText);
					Texts::refreshTexts();
					Action::text($texts['all_texts'], "inline", Texts::makeButtonsArray()["buttons"], Texts::makeButtonsArray()["callbacks"]);
					User::updateStage('all_texts');
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
							Action::deleteLastMessage();
							User::updateStage("showAllHouses");
							User::updateLayout(-1);
							Action::houses($user["city"]);
							break;
						case 'Дома по планировкам':
							User::updateStage("layout_undefine");
							Action::text($texts['layout_undefine'], "inline", $buttons['layout_undefine'], [0, 1, 2, 3, 4]);
							break;
						case 'Дома по районам':
							User::updateStage("area_undefine");
							User::updateLayout(-1);
							Action::text($texts['area_undefine'], "inline", array_merge($areas[$user["city"]], ["Назад"]));
							break;
					}
					break;

				case 'layout_undefine':
					if($messageText == "Назад"){
						User::updateStage("city_define");
						Action::text("Вы выбрали город " . $user["city"], "inline", $buttons['city_define']);
					} else {
						Action::deleteLastMessage();
						User::updateStage("showAllHouses");
						User::updateLayout($messageText);
						Action::houses($user["city"], "All", $messageText);
					}
					break;



				case 'showAllHouses':
					if($messageText == 'Назад'){
						User::updateStage("city_define");
						Action::deleteAllObjects();
						Action::text("Вы выбрали город " . $user["city"], "inline", $buttons['city_define']);
					} else {
						Action::deleteAllObjects();
						Action::apartaments($messageText);
						User::updateStage('showAllApartaments');
					}
					break;

				case 'showAllApartaments':
					if($messageText == 'Назад'){
						Action::deleteAllObjects();
						User::updateStage("showAllHouses");
						Action::houses($user["city"], $user["area"], $user["layout"]);
					} else {
						if(substr($messageText, 0, 1) == "o"){ //order

						} else { //information
							$apartamentArray = json_decode(substr($messageText, 1));
							$apartamentNumber = $apartamentArray[0];
							$apartamentIndex = $apartamentArray[1];
							$objectIndexArray = json_decode($user['objectArray']);
							$apartamentObject = mysqlQuest("SELECT * FROM `apartaments` WHERE `ind` = $apartamentIndex");
							$apartamentPrice = $apartamentObject['price'];
							$messageId = Action::editCap($objectIndexArray[$apartamentNumber], "Цена " . $apartamentPrice . " рублей", "inline", [$buttons['showApartaments'][0]], ["o" . $apartamentIndex]);
							Action::editObjectArray($apartamentNumber, $messageId);
						}
					}
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
					
				case "area_undefine":
					if($messageText == "Назад"){
						User::updateStage("city_define");
						Action::text("Вы выбрали город " . $user["city"], "inline", $buttons['city_define']);
					} else {
						Action::deleteLastMessage();
						User::updateStage("showAllHouses");
						Action::houses($user["city"], $messageText);
					}
					break;
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
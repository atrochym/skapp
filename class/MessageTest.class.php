<?php

class MessageTest {

	// public function set($name, $type) {
	// 	$_SESSION['message']['name'] = $name;
	// 	$_SESSION['message']['type'] = $type;
	// }

	public function set(array $data) {
		$_SESSION['message']['name'] = $data['messageContent'];
		$_SESSION['message']['type'] = $data['messageType'];
	}

	public function show() {
		if (!isset($_SESSION['message'])) {
			return ['messageContent' => '',
					'messageType' =>  'empty'];
			// klasa empty-message jest w css zdefiniowana jako display:none
		}

		$message = $_SESSION['message']['name'];
		$type = $_SESSION['message']['type'];
		unset($_SESSION['message']);
		// return $message;

		// switch($_SESSION['message']['type']) {
		// 	case 'success':
		// 		$class = 'Ta strona dostępna jest po zalogowaniu. Podaj email oraz hash zgłoszenia.';
		// 		break;
		// 	default:
		// 		$class = 'Nieznany kod komunikatu. Identyfikator: '.$id;
		// }

		// $message = '<div class="message message-'.$type.'">'.$message.'</div>';
		// $this->data['messageContent'] = $message;
		// $this->data['messageType'] =  $type;
		return ['messageContent' => $message,
				'messageType' =>  $type];
	}
}

?>
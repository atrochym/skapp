<?php

if ($action == 'create')
{
	$input = $router->requestJson();

	$validate = new Validate;
	$validate->add('receiveId', $input['receive_id'], 'require integer');
	$validate->add('note', $input['note'], 'require text 3 1000');

	$response['success'] = false;

	if (!$validate->check())
	{
		$response['message'] = 'warn::Wystąpił błąd walidacji danych.';
	}
	else
	{
		$receive = new Receive($db);
		$receive->setReceiveId($validate->receiveId);

		if (!$receive->getData())
		{
			$response['message'] = 'error::Wystąpił błąd weryfikacji przyjęcia.';
		}
		else
		{
			$note = new Note($db);
			$result = $note->create($validate->getValidData());

			if ($result)
			{
				$response['success'] = true;
				$response['noteId'] = $result;
			}
		}
	}

	echo json_encode($response);
	exit;
}

if ($action == 'edit')
{
	$validate = new Validate;
	$validate->add('noteId', $_POST['note_id'], 'require integer');
	$validate->add('note', $_POST['note'], 'require text 3 1000');

	if (!$validate->check())
	{
		setMessage('error::Błąd walidacji danych.');
	}
	else
	{
		$workerId = getFromSession('workerId');
		$note = new Note($db);
		$note->setNoteId($validate->noteId);
		$noteAuthor = $note->get('worker_id');
	
		if ($noteAuthor != $workerId && !workerPermit('note_edit_other'))
		{
			setMessage('error::Nie masz uprawnień do edycji notatek innych pracowników.');
		}
		else
		{
			$note->update($validate->note);
			setMessage($note->message);
		}
	}

	$router->redirect('back');
}
elseif ($action == 'delete' || $action == 'recover')
{
	$validate = new Validate;
	$validate->add('noteId', $router->getId(), 'require integer');

	if (!$validate->check())
	{
		setMessage('error::Niepoprawny identyfikator notatki.');
	}
	else
	{
		$workerId = getFromSession('workerId');
		$note = new Note($db);
		$note->setNoteId($validate->noteId);
		$noteAuthor = $note->get('worker_id');

		if (($action == 'delete' && $noteAuthor != $workerId && !workerPermit('note_edit_other'))
			|| ($action == 'recover' && !workerPermit('note_edit_other')))
		{
			setMessage('error::Nie masz uprawnień do edycji tej notatki.');
		}
		else
		{
			$note->{$action}();
			setMessage($note->message);
		}
	}

	$router->redirect('back');
}
elseif ($action == 'pin' || $action == 'unpin' )
{
	$input = $router->requestJson();

	$validate = new Validate;
	$validate->add('noteId', $input['note_id'], 'require integer');

	$response['success'] = false;

	if (!$validate->check())
	{
		$response['message'] = 'warn::Wystąpił błąd walidacji danych.';
	}
	elseif(!workerPermit('note_edit_other'))
	{
		$response['message'] = 'error::Nie masz uprawnień do edycji tej notatki.';
	}
	else
	{
		$note = new Note($db);
		$note->setNoteId($validate->noteId);
		$response['success'] = $note->{$action}();
	}

	echo json_encode($response);
	exit;
}


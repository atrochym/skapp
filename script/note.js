document.querySelectorAll('.content-input:not(.note-edit)').forEach(element => {
	element.style.height = element.scrollHeight + 10 + "px";
});

document.querySelector('.note-box').addEventListener('click', function (e) {

	if (e.target.classList.contains('note-edit-btn')) {
		toggleNoteEdit(e.target.closest('.note'));
		toggleVisible(e.target.parentElement.querySelector('.update-btn'));
	}

	if (e.target.classList.contains('note-delete-btn') || e.target.classList.contains('note-cancel-btn')) {
		toggleVisible(e.target.parentElement.querySelector('.note-delete-btn'));
		toggleVisible(e.target.parentElement.querySelector('.note-cancel-btn'));
		toggleVisible(e.target.parentElement.querySelector('.delete-confirm-btn'));
	}

	if (e.target.classList.contains('star')) {
		changePinNote(e);
	}
});

document.querySelector('.note').addEventListener('submit', event => sendNote(event));

async function sendNote(event) {
	event.preventDefault();
	const note = event.target.querySelector('[name=note]').value;
	const receiveId = event.target.querySelector('[name=receive_id]').value;

	const data = {
		'note': note,
		'receive_id': receiveId
	}

	const fetch = await fetchData('/sk/note/create/json', data);

	if (!fetch || !fetch.success) {
		const messageResponse = document.querySelector('.error-message');
		messageResponse.textContent = prettyMessage(fetch.message);
		messageResponse.classList.remove('hidden');
		return;
	}

	const createNoteForm = document.querySelector('.note');

	createNoteForm.after(createNoteForm.cloneNode(true));

	const newNote = document.querySelector('.note:nth-child(2)');
	const commentId = newNote.querySelector('[name=note_id]');
	const linkDelete = newNote.querySelector('.delete-confirm-btn');
	const date = new Date;

	commentId.value = commentId.value = fetch.noteId;
	linkDelete.href = linkDelete.href.replace('%id', fetch.noteId);
	newNote.action = newNote.action.replace('create', 'edit');

	toggleVisible(newNote.querySelector('.add-btn-box'))
	toggleVisible(newNote.querySelector('.edit-btn-box'));
	newNote.querySelector('.error-message').classList.add('hidden');
	newNote.querySelector('.date').textContent = `dziś, ${date.getHours()}:${date.getMinutes()}`;

	toggleNoteEdit(newNote);

	newNote.classList.add('create-success');

	createNoteForm.querySelector('.error-message').classList.add('hidden');
	createNoteForm.querySelector('[name=note]').value = '';
}

async function changePinNote(event) {
	event.preventDefault();
	
	const noteForm = event.target.closest('form');
	const noteId = noteForm.querySelector('[name=note_id]').value;
	const messageResponse = noteForm.querySelector('.error-message');
	const action = noteForm.classList.contains('pinned') ? 'unpin' : 'pin';
	const data = {
		'note_id': noteId,
	}
	const fetch = await fetchData(`/sk/note/${action}/json`, data);

	if (!fetch || !fetch.success) {
		messageResponse.textContent = prettyMessage(fetch.message);
		messageResponse.classList.remove('hidden');
		return;
	}

	noteForm.classList.toggle('pinned');
	noteForm.querySelector('.star').classList.toggle('fa-star-o');
	noteForm.querySelector('.star').classList.toggle('fa-star');
	messageResponse.textContent = '';
}

function toggleNoteEdit(note)
{
	let noteInput = note.querySelector('textarea');
	noteInput.classList.toggle('note-edit');
	noteInput.classList.toggle('note-read');
	noteInput.disabled = !noteInput.disabled;
}

function resetNotes() {
	document.querySelectorAll('.note:not(:first-child)').forEach(element => {
		element.querySelector('textarea').classList.remove('note-edit');
		element.querySelector('textarea').classList.add('note-read');
		element.querySelector('.update-btn').classList.add('hidden');
		element.querySelector('.note-cancel-btn').classList.add('hidden');
		element.querySelector('.note-delete-btn').classList.remove('hidden');
		element.querySelector('.delete-confirm-btn').classList.add('hidden');
	});
}

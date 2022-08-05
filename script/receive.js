import {removeSolution, addSolution, countCost, hidePromptBox, startPromptBox} from './solution.js';

const solutionsList = document.querySelector('.solutions-list');
const priceHandler = '.solutions-list .input-cost';
const priceResultHandler = '.price-total';

document.addEventListener('click', e => {
	if(document.querySelector('.visible') == null || e.target.closest('.input-name'))
		return;

	hidePromptBox();
});

document.addEventListener('keyup', e => {
	if (e.key == 'a' && e.altKey) {
		addSolution(solutionsList);
	}

	if (e.key == 'Escape' && document.querySelector('.visible')) {
		hidePromptBox();
	}
});

document.querySelector('.add-solution-btn').addEventListener('click', () => addSolution(solutionsList));

document.querySelector('.parts-list').addEventListener('click', e => {
	const row = e.target.closest('.row');
	row.querySelector('input').checked = true;
});



solutionsList.addEventListener('click', e => {
	// const solutionsTable = document.querySelector('.solutions-list');
	
	if (e.target.classList.contains('solution-delete')) {
		removeSolution(e.target);
	}

	if (e.target.classList.contains('solution-edit') || e.target.classList.contains('solution-edit-end')) {
		const row = e.target.closest('.row');
		const [inputName, inputPrice] = row.querySelectorAll('input');

		inputName.classList.toggle('input-edit');
		inputName.disabled = !inputName.disabled

		inputPrice.classList.toggle('input-edit');
		inputPrice.disabled = !inputPrice.disabled

		toggleVisible(row.querySelector('.action-buttons'));
		toggleVisible(row.querySelector('.edit-buttons'));

		if (e.target.classList.contains('solution-edit')) {
			inputName.dataset.default = inputName.value;
			inputPrice.dataset.default = inputPrice.value;
		}
		if (e.target.classList.contains('solution-edit-end')) {
			inputName.value = inputName.dataset.default;
			inputPrice.value = inputPrice.dataset.default;
		}
	}

	if (e.target.classList.contains('solution-update-btn')) {
		updateSolution(e);
	}

	if (e.target.closest('.element')) {
		const row = e.target.closest('.row');
		const promptBox = row.querySelector('.prompt-box');
		// const inputName = row.querySelector('.input-name');
		// const inputPrice = row.querySelector('.input-cost');
		const [inputName, inputPrice] = row.querySelectorAll('input');
		const element = e.target.closest('.element');
		// const name = element.querySelector('.name').textContent;
		// const price = element.querySelector('.price').textContent;

		inputName.value = element.querySelector('.name').textContent;
		inputPrice.value = element.querySelector('.price').textContent;

		toggleVisible(promptBox);

	}

	if (e.target.classList.contains('input-name')) {
		// const row = e.target.closest('.row');
		const promptBox = e.target.closest('.row').querySelector('.prompt-box');
		const promptBoxOpened = document.querySelector('.visible');

		if (promptBoxOpened) {
			// promptBoxOpened.classList.add('hidden');
			toggleVisible(promptBoxOpened);
			promptBoxOpened.classList.remove('visible');
		}

		if (promptBox.dataset.response == "true")
		{
			promptBox.classList.remove('hidden');
			promptBox.classList.add('visible');
		}
	}
});

solutionsList.addEventListener('keydown', e => {
	const inputName = e.target;
	const row = e.target.closest('.row');

	if (!row) return;

	let element, allElements, promptBox;

	if (row.querySelector('.prompt-box')) {
		promptBox = row.querySelector('.prompt-box');
		element = promptBox.querySelector('.selected');
		allElements = promptBox.querySelectorAll('.element').length;
	}

	if (e.key == 'ArrowDown' && allElements) {
		e.preventDefault();

		if (element) {
			if (element.dataset.id == allElements) {
				return;
			}

			element.classList.remove('selected');
			element.nextElementSibling.classList.add('selected');
			element.nextElementSibling.focus();

		} else {
			element = promptBox.querySelector('.element');
			element.classList.add('selected');
			element.focus();
		}

	} else if (e.key == 'ArrowUp') {
		e.preventDefault();

		if (element) {
			if (element.dataset.id == 1) {
				row.querySelector('.input-name').focus();
				element.classList.remove('selected');
				return;
			}

			element.classList.remove('selected');
			element.previousElementSibling.classList.add('selected');
			element.previousElementSibling.focus();
		}

	} else if (e.key == 'Enter') {
		const element = promptBox.querySelector('.selected');
		if (element) {
			element.click();
			row.querySelector('.input-cost').focus();
		}

	} else if(e.key == 'Delete' && inputName.value.length == 0) {
		removeSolution(inputName);

	} else if(e.key == 'Delete' && e.shiftKey) {
		removeSolution(inputName);

	}

});

solutionsList.addEventListener('keyup', e => {
	if (e.target.classList.contains('input-cost')) {
		countCost(priceHandler, priceResultHandler);
	}

	if (e.target.classList.contains('input-name')) {
		startPromptBox(e);
	}
});

solutionsList.addEventListener('focusin', e => {
	const inputName = e.target;
	if (inputName.classList.contains('input-name') && inputName.value.length < 3) {
		const charCounter = inputName.nextElementSibling;
		charCounter.classList.remove('hidden');
	}
});

solutionsList.addEventListener('focusout', e => {
	const inputName = e.target;
	if (inputName.classList.contains('input-name')) {
		const charCounter = inputName.nextElementSibling;
		charCounter.classList.add('hidden');
	}
});

async function updateSolution(event) {
	const solution = event.target.closest('.row');
	solution.classList.remove('create-success');

	const serviceId = solution.dataset.service_id;
	const [name, price] = solution.querySelectorAll('input');

	const data = {
		'serviceId': serviceId,
		'name': name.value,
		'price': price.value
	}

	const fetch = await fetchData('/sk/service/update/json', data);

	if (!fetch || !fetch.success) {
		showMessageBox('warn::Błąd podczas aktualizacji usługi.');
		return;
	}

	name.classList.toggle('input-edit');
	name.disabled = !name.disabled

	price.classList.toggle('input-edit');
	price.disabled = !price.disabled

	toggleVisible(solution.querySelector('.action-buttons'));
	toggleVisible(solution.querySelector('.edit-buttons'));
	toggleVisible(document.querySelector('.message'));
	solution.classList.add('create-success');
}

countCost(priceHandler, priceResultHandler);


//jq
$(".ico-test").click(function() {
	$(this).siblings('.animate').slideToggle();
	$(this).toggleClass('ico-test-rotate');
});

// TODO animacja
// document.querySelector('.ico-test').addEventListener('click', e => {

// });
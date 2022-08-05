import {removeSolution, addSolution, countCost, hidePromptBox, startPromptBox} from './solution.js';

const solutionsList = document.querySelector('.solutions-list');
const priceHandler = '.solutions-list .input-cost';
const priceResultHandler = '.price-total';

solutionsList.addEventListener('click', e => {
	const target = e.target;

	if (target.classList.contains('add-solution-btn')) {
		addSolution(solutionsList);

	} else if (e.target.classList.contains('remove')) {
		solutionsList.dataset.count > 0 && removeSolution(e.target);
	}

	if (e.target.closest('.element')) {
		const row = e.target.closest('.row');
		const promptBox = row.querySelector('.prompt-box');
		const inputName = row.querySelector('.input-name');
		const inputPrice = row.querySelector('.input-cost');
		const element = e.target.closest('.element');
		const name = element.querySelector('.name').textContent;
		const price = element.querySelector('.price').textContent;

		inputName.value = name;
		inputPrice.value = price;
		toggleVisible(promptBox);



	}

	if (e.target.classList.contains('input-name')) {
		const row = e.target.closest('.row');
		const promptBox = row.querySelector('.prompt-box');
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
		// promptBoxOpened && promptBoxOpened.classList.remove('.visible');

		// promptBox.classList.remove('hidden');
		// promptBox.classList.add('visible');

	}

});

function _addSolution() {
	const solutionsArea = document.querySelector('.solution-area');
	const serviceId = ++solutionsArea.dataset.id;
	// const solutionsCount = ++solutionsArea.dataset.count;
	const newSolutionRow = document.querySelector('.row').cloneNode(true);

	const [inputName, inputPrice] = newSolutionRow.querySelectorAll('input');
	inputName.name = `solution[${serviceId}][name]`;
	inputName.value = '';
	inputPrice.name = `solution[${serviceId}][price]`;
	inputPrice.value = '';
	// toggleVisible(newSolutionRow);  // jakoś nie działa o.O
	newSolutionRow.classList.remove('hidden');
	buttons = solutionsArea.querySelector('.buttons');
	buttons.before(newSolutionRow);
	
	// buttons.querySelector('.solution-save-btn').classList.remove('hidden');
	inputName.focus();

	// ++solutionsTable.dataset.id;
	// ++solutionsTable.dataset.count;

	/// ogarnąć przycisk anulowania, zapisania, zliczania kwoty itp
	// mimo focusu po odkliknięciu ucieka lista podpowiedzi
}

document.addEventListener('click', e => {
	if(document.querySelector('.visible') == null || e.target.closest('.input-name'))
		return;

	hidePromptBox();

})

function _deleteRow(element) {
	solutionsTable = document.querySelector('.solutions-area');

	element.closest('.row').remove();
	// --solutionsTable.dataset.count;
	// if(solutionsTable.dataset.count == 0) {
	// 	toggleVisible(document.querySelector('.solution-save-btn'));
	// }
	recountCost();
}




// skopiowane z receive

function _hidePromptBox() {
	const element = document.querySelector('.visible');
	element.classList.add('hidden');
	element.classList.remove('visible');
	element.querySelector('.selected') && element.querySelector('.selected').classList.remove('selected');
}

function _recountCost() {
	let priceMin = 0;
	let priceMax = 0;

	document.querySelectorAll('.solution-area .input-cost').forEach(element => {
		let thisPrice = element.value;

		if (thisPrice.indexOf('-') != -1) {
			thisPrice = thisPrice.replaceAll(' ', '');
			period = thisPrice.split('-').sort();
			periodMin = parseInt(period[0]);
			periodMax = parseInt(period[1]);
			if (Number.isNaN(periodMin)) {
				periodMin = 0;
			}
			if (Number.isNaN(periodMax)) {
				periodMax = 0;
			}
			thisPrice = 0;
		} else {
			thisPrice = parseInt(thisPrice);
			periodMin = 0;
			periodMax = 0;
			if (Number.isNaN(thisPrice)) {
				thisPrice = 0;
			}
		}
		priceMin = priceMin + thisPrice + periodMin;
		priceMax = priceMax + thisPrice + periodMax;

	});

	if(priceMin !== priceMax) {
		priceMin = priceMin + ' - ' + priceMax;
	}

	document.querySelector('.price-total').textContent = priceMin + ' PLN';
}

async function _test(string, item) {
	const name = string;

	const data = {
		'name': name,
	}

	const fetch = await fetchData('/sk/service/test/json', data);

	if (!fetch || !fetch.success) {
		showMessageBox('warn::Błąd podczas aktualizacji usługi.');
		return;
	}

	// ustalić do którego promptboxa mają wpadać elementy - OK
	// pomyśleć o pobraniu danych i filtrowaniu locala? - 
	// przejście na listę strzałką w dół
	// po kliknięciu w wypełniony input przywrócić listę ostatnich wyników

	const services = fetch.data;
	

	const promptBox = item.querySelector('.prompt-box');
	promptBox.classList.remove('hidden');
	promptBox.classList.add('visible');

	// promptBox.innerHTML = '';

	if (services.length == 0) {
		a = document.createElement('span');
		a.classList.add('empty-result');
		a.textContent = 'Brak wyników';
		promptBox.appendChild(a);

		return;
	}
	
	// const promptBox = document.querySelector('.prompt-box');
	// const promptBox = item.querySelector('.prompt-box');
	// promptBox.classList.remove('hidden');
	// promptBox.classList.add('visible');


	// const promptBox = inputName.nextElementSibling;
	
	// const template =  document.querySelector('.prompt-box .element').cloneNode(true);
	let count = 0;
	services.forEach(element => {
		count++;
		const template =  document.querySelector('.prompt-box .template').cloneNode(true);
		template.classList.remove('template');
		template.classList.add('element');
		template.querySelector('.name').textContent = element.name;
		template.querySelector('.price').textContent = element.price;
		template.dataset.id = count;
		// template = document.createElement('div');
		// template.classList.add('element');
		
		promptBox.appendChild(template);
		// return template;
		// cl(inputName);
	});

	promptBox.dataset.response = true;


}

solutionsList.addEventListener('keyup', e => {
	if (e.target.classList.contains('input-cost')) {
		countCost(priceHandler, priceResultHandler);
	}

	if (e.target.classList.contains('input-name')) {
		startPromptBox(e);
	}
});

solutionsList.addEventListener('keydown', e => {
	const inputName = e.target;

	const item = e.target.closest('.row');
	if (!item) return;
	let element, allElements, promptBox;

	if (item.querySelector('.prompt-box')) {
		promptBox = item.querySelector('.prompt-box');
		element = promptBox.querySelector('.selected');
		allElements = promptBox.querySelectorAll('.element').length;
	}

	// cl (allElements);



	if (e.key == 'ArrowDown' && allElements) {
		e.preventDefault();
		// let next = element.nextElementSibling;

		if (element) {
			if (element.dataset.id == allElements) {
				return
			}
			element.classList.remove('selected');
			element.nextElementSibling.classList.add('selected');
			element.nextElementSibling.focus();
		}
		else
		{
			let element = promptBox.querySelector('.element');
			element.classList.add('selected');
			element.focus();
		}
		// promptBox.querySelector('.element').focus();
	} else if (e.key == 'ArrowUp') {
		e.preventDefault();

		if (element) {
			if (element.dataset.id == 1) {

				item.querySelector('.input-name').focus();
				element.classList.remove('selected');
				return;
			}

			element.classList.remove('selected');
			element.previousElementSibling.classList.add('selected');
			element.previousElementSibling.focus();
		}

	} else if (e.key == 'Enter') {
		let element = promptBox.querySelector('.selected');
		if (element) {
			element.click();
			item.querySelector('.input-cost').focus();
		}
		e.preventDefault();

	} else if(e.key == 'Delete' && inputName.value.length == 0) {
		// cl('usunę');
		solutionsList.dataset.count > 0 && removeSolution(inputName);

	} else if(e.key == 'Delete' && e.shiftKey) {
		// cl('usunę');
		solutionsList.dataset.count > 0 && removeSolution(inputName);
	} else if(e.key == 's' && e.altKey) {
		cl('usunę');
		// deleteRow(inputName);
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

document.addEventListener('keyup', e => {
	if (e.key == 'a' && e.altKey) {
		addSolution(solutionsList);
	}

	if (e.key == 'Escape' && document.querySelector('.visible')) {
		hidePromptBox();
	}
})





// $(`#part-url`).keyup(function() {
// 	let url = $(this).val();

// 	// https://allegro.pl/oferta/klawiatura-hp-omen-15-ce-15-nw-led-10243381485
// 	// https://allegro.pl/oferta/obudowa-palmrest-klawiatura-hp-omen-15-ce-10510994217?reco_id=094b5a26-11a2-11ec-b367-b026284c79a0&sid=1147bbb04b955b96a226cab2e664258d9124ef0862320b2d7366fdd6604cf017
// 	// https://allegro.pl/oferta/klawiatura-apple-macbook-air-13-a1369-a1466-duzy-9711831336?reco_id=1a624c49-119d-11ec-933f-bc97e12c3c80&sid=041047f9c36843e364ecb91b45c568a2755aa386fe7e14ee7421a14291fbf951
// 	// https://allegro.pl/oferta/klawiatura-hp-omen-15-ce-15-nw-led-10243381485#metody-platnosci

// 	index = url.indexOf("/oferta/");
// 	if(index > 0) {
// 		url = url.substring(index+8);
// 		index = url.indexOf("?");

// 		if(index > 0) {
// 			url = url.slice(0, index);
// 		}

// 		index = url.indexOf("#");

// 		if(index > 0) {
// 			url = url.slice(0, index);
// 		}

// 		index = url.lastIndexOf("-");
// 		item = url.substring(index+1);

// 		url = url.substr(0, index)
// 		url = url.replace(/-/g, " ");
// 		url = url.charAt(0).toUpperCase() + url.slice(1);

// 		$(`#part-name`).val(url);
// 		$(`#part-id`).val(item);
// 		$(`#part-price`).focus();

		
// 	}
		
// });

function togglePartAdd() {
	$(`#part-add-background`).toggle();
	$(`#part-add-window`).toggle();
}

// $(".ico-test").click(function() {
// 	$(".tescik").toggleClass('dupa');
// });



// $(".ico-testX").click(function() {
// 	// var test = $(this)[0].classList[0];
// 	// var test = $(this).prop("classList");

// 	var wysokosc = $(".log-box").height();
// 	var wysokosc = $(".comment").height();
// 	var wysokosc = $(".comment").height();


// 	var teraz = parseInt($(".animate").css('max-height'));

// 	if(teraz > 40) {
// 		// $(`.animate`).css('max-height', '');
// 		$(this).next().css('max-height', '');

// 	} else {
// 		// $(`.animate`).css('max-height', wysokosc);
// 		$(this).next().css('max-height', wysokosc);
// 		$(".animate > .ico-test").removeClass('fa-caret-up');

// 	}


// 	// cl(lol);
// });

// $(".ico-test").click(function() {
// 	$(this).siblings('.animate').slideToggle();
// 	$(this).toggleClass('ico-test-rotate');
// });



function testsubmit(id) {
	var form = $(".form-test");

	$("<input>").attr({type: 'hidden', name: 'serviceId', value: id}).prependTo(form);
	// $("<input>").attr({type: 'hidden', name: 'type', value: 'update'}).prependTo(form);
	let formm = document.querySelector('.form-test');
	formm.action = `/sk/service/${id}/update`;
	form.submit();
}

// function delegateService () {


// }

$(".change-worker-button").click(function() {  // używane w receive

	var row = $(this).parents('.row');

	row.find(".worker-name-test").toggle();
	select = row.find(".delegate");
	var workerId = select.data('worker_id');
	var serviceId = row.data('service_id');

	select.toggle();

	select.children(`[value=${workerId}]`).attr('selected','selected');

	select.on('change', function() {
		var form = $(".form-test");

		form.attr('action', '/sk/service/set-worker')
		val = select.val();
		$("<input>").attr({type: 'hidden', name: 'service_id', value: serviceId}).prependTo(form);
		$("<input>").attr({type: 'hidden', name: 'worker_id', value: val}).prependTo(form);

		// window.location.href = `/sk/device/26/delegate/worker-${val}/service-xx/`;
		form.submit();

	})
	

	// cl($(this.closest(".worker-name-test")));

});

// set prediction date to next work day
function setPredictionDate() {
	const date = new Date;

	if (date.getDay() >= 5) {
		date.setUTCDate(date.getDate() + 3);
	} else {
		date.setUTCDate(date.getDate() + 1);
	}

	return date.toLocaleDateString('en-CA');
}

document.querySelector('.prediction-date').value = setPredictionDate();
document.querySelector('.prediction-date').addEventListener('change', e => {
	const selectedDate = e.target.value.split('-');
	const date = new Date;

	date.setFullYear();

	cl(selectedDate[0]);
});

// TODO sprawdź czy na dzień naprawy nie wybrano weekendu


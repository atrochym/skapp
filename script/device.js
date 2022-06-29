
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

$(".ico-test").click(function() {
	$(this).siblings('.animate').slideToggle();
	$(this).toggleClass('ico-test-rotate');
});


function delrow(element) {
	var solutionsTable = $(".solution-list");

	$(element).parents('.row').remove();
	solutionsTable.data('count', --count);
	if(count == 0) {
		$(".save-solution").remove();
	}
	newPrice();

}

$(".add-solution").click(function() {

	var solutionsTable = $(".services-list");

	numberOfSolutions = parseInt(solutionsTable.data('id'));
	count = parseInt(solutionsTable.data('count'));

	var newRow = `	<tr class="row">
						<td class="kek">
							<input class="add-row input-test" type="text" name="solution[${numberOfSolutions}][name]" value="">
						</td>
						<td>
							<input class="add-row input-test-cost price" type="text" name="solution[${numberOfSolutions}][price]" value="">
						</td>
						<td colspan="2"></td>
						<td>
							<a class="ico fa fa-trash delete-row" title="Usuń" onclick="delrow(this)"></a>
						</td>
					</tr>
					
					`;

	var saveButton = `<button class="button save-solution" style="position: absolute; left:108px; bottom: 27px; top:auto; width:80px;" type="submit">Zapisz</button>`;

	solutionsTable.data('id', ++numberOfSolutions);
	solutionsTable.data('count', ++count);

	$(".buttons").before(newRow);
	if(count == 1) {
		$(".add-solution").before(saveButton);
	}
	createListeners();

});

function newPrice() {
	let priceMin = 0;
	let priceMax = 0;
	$("input.price").each(function() {
		thisPrice = $(this).val();
		if(thisPrice.indexOf("-") != -1) {
			period = thisPrice.split("-").sort();
			periodMin = parseInt(period[0]);
			periodMax = parseInt(period[1]);
			thisPrice = 0;
		} else {
			thisPrice = parseInt(thisPrice);
			periodMin = 0;
			periodMax = 0;
			if(Number.isNaN(thisPrice)) {
				thisPrice = 0;
			}
		}
		priceMin = priceMin + thisPrice + periodMin;
		priceMax = priceMax + thisPrice + periodMax;
	});
	// ogarnij if w 1 linii
	if(priceMin !== priceMax) {
		priceMin = priceMin + ' - ' + priceMax;
	} 
	
	$(".price-total").text(priceMin + ' PLN');
}

function createListeners() {
	$(`.price`).change(function() {
		newPrice();
	});
}

newPrice();
createListeners();



$(".row-edit").click(function() {

	var row = $(this).parents('.row');
	

	input = row.find("input");

	nameValue = input.eq(0).val();
	priceValue = input.eq(1).val();

	input.addClass("add-row");
	input.removeAttr("disabled");
	input.eq(0).attr("name", "name");
	input.eq(1).attr("name", "price");
	row.find(".action-buttons").toggle();
	row.find(".edit-buttons").toggle();

});

$(".row-edit-end").click(function() {

	var row = $(this).parents('.row');
	
	input = row.find("input");
	input.eq(0).val(nameValue);
	input.eq(1).val(priceValue);
	input.removeClass("add-row");
	input.attr("disabled", true);
	row.find(".action-buttons").toggle();
	row.find(".edit-buttons").toggle();

	newPrice();

});

function testsubmit(id) {
	var form = $(".form-test");

	$("<input>").attr({type: 'hidden', name: 'serviceId', value: id}).prependTo(form);
	// $("<input>").attr({type: 'hidden', name: 'type', value: 'update'}).prependTo(form);
	let formm = document.querySelector('.form-test');
	formm.action = `/sk/service/${id}/update`;
	form.submit();
}

function delegateService () {


}

$(".change-worker-button").click(function() {

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




$("#add-button").click(function() {
	numberOfSolutions = parseInt($("#numberOfSolutions").val());
	numberOfSolutions ++;
	var newdiv = `<div id="solution-%" style="width:auto;display: flex; justify-items: center;margin-bottom: 10px;">
					<input type="hidden" id="changed-%" name="solution[%][changed]" value="1">
					<input type="hidden" name="solution[%][id]" value="0">
					<input type="text" class="form-input input-form2 name" name="solution[%][name]" style="width: 405px;" placeholder="nazwa" value="">
					<input type="text" id="price-%" class="form-input input-form2 price" name="solution[%][price]" style="width: 100px;margin-left: 40px;" placeholder="cena" value="">
						<select id="select-%" name="solution[%][worker_id]" size="1" class="input-form2" style="display:none;width:184px;height:24px;padding-top: 2px;margin-left: 20px;">
							<option value="0">-</option>
							// js genering
						</select>
						<button id="who-%" type="button" class="who fa fa-user-circle service-form-icon" style=" font-size: 18px;"></button>
						<button id="remove-%" type="button" class="fa fa-minus-circle service-form-icon" style=""></button>
						<button class="fa fa-check-circle service-form-icon" type="button" style="color:#008B8B;display:none;"></button>
					</div>`;

	newdiv = newdiv.replaceAll("%", `${numberOfSolutions}`);
	$("#solution-list-form div:last").after(newdiv);
	$("#numberOfSolutions").val(numberOfSolutions);
	$(`#select-${numberOfSolutions}`).append(selectWorkersList(workersListfromPHP));
	createListeners(numberOfSolutions);

	$(`[id^=remove-${numberOfSolutions}`).click(function() {
		$(this).parent().remove();
		newPrice();
	});

});

function createListeners(value='') {
	// listenery dla wszystkich solutions
	$(`[id^=who-${value}`).click(function() {
		$(this).prev("select").toggle();
	});
	

	
	$(`[id^=price-${value}]`).change(function() {
		newPrice();    
	});
}


function checkInputChanges(deviceID, serviceID) {
	let changes = [];
	$(`[id^=changed-]`).each(function() {
		changes.push($(this).val());
	});
	if(changes.includes('1')) {
		xx = confirm("Studio-Komp -- ostrzeżenie \n\n  Masz niezapisane zmiany w formularzu. \n\n  Kliknij OK żeby kontynuować lub anuluj by pozostać.");
		if(xx) {
			location.href=`/sk/device/${deviceID}/mark-done/service-${deviceID}`;
		}
	} else {
		location.href=`/sk/device/${deviceID}/mark-done/service-${deviceID}`;
	}
}


//listener usuwający wywalić z obiektów z bazy
// usuwane rekordy pzrekazywać z jakimś jidden czy coś

// listenery tylko dla solutions z bazy
$(`[id^=price-], [id^=select-], .name`).change(function() {
	let changeFor = $(this).parent().attr('data');
	$(`#changed-${changeFor}`).val('1');
});


$(`[id^=remove-`).click(function() {
	let changeFor = $(this).parent().attr('data');

	$(this).parent().toggle();
	$(`#changed-${changeFor}`).val('-1');
	$(`#price-${changeFor}`).val('0');

newPrice();
});

$(`#label-list-button`).click(function() {
	$("#label-list").toggle();
});

$(`.toggle-list`).click(function() {
	$(this).next().slideToggle("fast");
});

function selectWorkersList(workersListfromPHP) {
	let selectWorkersList = '';
	workersListfromPHP.forEach(function(element) {
		selectWorkersList = `${selectWorkersList} <option value="${element['id']}">${element['name']}</option> \n`;
	});
	return selectWorkersList;
}


// newPrice();
createListeners();

function cl(value) {
	console.log(value);
	
}

function assignPart($serviceId) {
	$(`.assign-part-box .service_id`).val($serviceId);
	$(`#part-add-background`).toggle();
	$(`#part-add-window`).toggle();
}



// testy ponizej



function copyInput() {
	copy = $("#copy-this-link");
	copy.focus();
	copy.select();
	document.execCommand("copy");
	$("#copy-button").focus();
}


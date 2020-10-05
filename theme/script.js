function add()
{
	var items = document.getElementById("items");
	var count = document.getElementsByName("count")[0].value;
	var item = document.createElement("div");

	item.innerHTML = '<input class="form-control" type="text" name="package[]" placeholder="название пакета" required>';
	item.innerHTML += '<a href="#" onclick="remove(' + count + ')" class="input-group-text"><i class="icon icon-trash"></i></a>';

	item.setAttribute("id", "item-" + count);
	item.setAttribute("class", "input-group mb-3");

	items.insertBefore(item, items.children[items.children.length - 2]);
}

function edit(item)
{
	var addon = (item > 0 ? false : true);

	if (!addon)
	{
		var pack = document.getElementById("package-" + item).querySelectorAll("a[id], span[id]");
		var temp = document.getElementsByName("package_id")[0];

		for (var i = 0; i < temp.length; i++)
		if (temp[i].textContent === pack[1].innerHTML) index = temp[i].index;
	}

	document.getElementById("caption").innerHTML = (addon ? 'Создание обновления' : 'Редактирование обновления');
	document.getElementById("form").action = (addon ? '?add' : "?edit=" + item);
	document.getElementsByName("package_id")[0].selectedIndex = (addon ? null : index);
	document.getElementsByName("target_version")[0].value = (addon ? null : pack[0].innerHTML);
	document.getElementsByName("source_version")[0].value = (addon ? null : pack[2].innerHTML);
	document.getElementsByName("description")[0].value = (addon ? null : pack[3].innerHTML);
	document.getElementsByName("url")[0].value = (addon ? null : pack[4]);

	new bootstrap.Modal(document.getElementById('pack')).toggle();
}

function remove(id)
{
	var item = document.getElementById("item-" + id);
	var name = item.querySelector('input[name="package[]"]').value;

	if (name != '' && !confirm("При удалении пакета будут удалены все связанные обновления!\nВы действительно хотите удалить пакет '" + name + "'?")) return;

	item.remove();
}
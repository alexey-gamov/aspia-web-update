<?php

//
// Aspia Project
// Copyright (C) 2020 Dmitry Chapyshev <dmitry@aspia.ru>
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <https://www.gnu.org/licenses/>.
//

require_once 'config.php';
session_start();
	
if (file_exists('install.php') and empty(Config::$admin_user))
{
	header('Location: install.php');
	exit;
}

if (isset($_SESSION['admin']) and $_SESSION['admin'] === true)
{
	$mysqli = new mysqli(Config::$db_host, Config::$db_user, Config::$db_pass, Config::$db_name);
	$mysqli->set_charset('utf8');

	if ($mysqli->connect_error)
	{
		switch ($mysqli->connect_errno)
		{
			case 2002:
				$description = 'Отсутствует подключение к MySQL серверу';
				break;
			case 1045:
				$description = 'Неверный логин или пароль подключения к БД';
				break;
			case 1044: case 1049:
				$description = 'База данных с таким именем не существует';
				break;
			default:
				$description =  $mysqli->connect_error;
		}

		$navigation = '<a class="nav-link" href="javascript:window.location.reload(true)">обновить</a>';
		$content = '<div class="alert alert-danger"><b>Ошибка!</b> ' .  $description . '</div>';

		goto Display;
	}

	if (isset($_GET['add']))
	{
		if(!empty($_POST))
		{
			$mysqli->query("INSERT INTO `updates` VALUES (0, '" . $_POST['package_id'] . "', '" . $_POST['source_version'] . "', '" . $_POST['target_version'] . "', '" . nl2br($_POST['description']) . "', '" . $_POST['url'] . "');");
			header("location: " . dirname($_SERVER['REQUEST_URI']));
		}
	}
	elseif (!empty($_GET['edit']))
	{
		$mysqli->query("UPDATE `updates` SET `package_id` = '" . $_POST['package_id'] . "',  `source_version` = '" . $_POST['source_version'] . "', `target_version` = '" . $_POST['target_version'] . "', `description` = '" . nl2br($_POST['description']) . "',  `url` = '" . $_POST['url'] . "' WHERE id='".intval($_GET['edit'])."'");
		header("location: " . dirname($_SERVER['REQUEST_URI']));
	}
	elseif (!empty($_GET['delete']))
	{
		$mysqli->query("DELETE FROM `updates` WHERE id='" . intval($_GET['delete']) . "'");
		header("location: " . dirname($_SERVER['REQUEST_URI']));
	}
	elseif (isset($_GET['settings']))
	{
		$all = $mysqli->query("SELECT `name` FROM `packages` ORDER BY `name`");
		$new = preg_replace('%[^A-Za-zА-Яа-я0-9]%', '', $_POST['package']);
		
		for($i = 0; $cur[$i] = $all->fetch_assoc()['name']; $i++);
		array_pop($cur);

		foreach (array_diff($new, $cur) as $item)
		{
			$mysqli->query("INSERT IGNORE INTO `packages` VALUES (NULL, '" . $item . "')");
		}

		foreach (array_diff($cur, $new) as $item)
		{
			$mysqli->query("DELETE FROM `updates` WHERE `package_id` IN (SELECT `id` FROM `packages` WHERE `name` = '" . $item . "')");
			$mysqli->query("DELETE FROM `packages` WHERE `name` = '" . $item . "'");
		}

		$all->close();

		header("location: " . dirname($_SERVER['REQUEST_URI']));

		if ($_POST['password'] === $_POST['confirm'] and $_POST['password'] <> null)
		{
			$new_user = preg_replace('%[^A-Za-zА-Яа-я0-9]%', '', $_POST['username']);
			$new_pass = preg_replace('%[^A-Za-zА-Яа-я0-9]%', '', $_POST['password']);

			$file = file_get_contents('config.php');

			$file = str_replace("admin_user = '" . Config::$admin_user, "admin_user = '" . ($new_user <> null ? $new_user : Config::$admin_user), $file);
			$file = str_replace("admin_pass = '" . Config::$admin_pass, "admin_pass = '" . $new_pass, $file);

			$file = file_put_contents('config.php', $file);

			header("location: " . dirname($_SERVER['REQUEST_URI']) . "?logout");
		}
	}
	elseif (isset($_GET['logout']))
	{
		unset($_SESSION['admin']);
		header("location: " . dirname($_SERVER['REQUEST_URI']));
	}
	
	$navigation = '<a class="nav-link" href="#settings" data-toggle="modal">настройки</a> <a class="nav-link" href="?logout">выход</a>';

	$content = '<table class="table table-bordered table-striped table-hover">
				<thead>
					<tr>
						<th>Обновление</th>
						<th>Исходная версия</th>
						<th>Описание</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>';

	$updates = $mysqli->query("SELECT * FROM `updates` ORDER BY `package_id`, `target_version` DESC");

	while ($entry = $updates->fetch_array())
	{
		$package_name = $mysqli->query("SELECT `name` FROM `packages` WHERE id='" . intval($entry['package_id']) . "'")->fetch_array()[0];
		
		$content .= '
					<tr id="package-' . $entry['id'] . '">
						<td>
							<span id="target_version" class="badge color' . $entry['package_id'] % 8 . '">' . $entry['target_version'] . '</span>
							<span id="package_id" class="badge color' . $entry['package_id'] % 8 . '">' . ($package_name <> null ? $package_name : 'deleted!') . '</span>
						</td>
						<td><span id="source_version" class="badge bg-secondary">' . $entry['source_version'] . '</span></td>
						<td><span id="description">' . $entry['description'] . '</span></td>
						<td>
							<a href="' . $entry['url'] . '" target="_blank" id="url" rel="tooltip" title="Скачать файл обновления"><i class="icon icon-download"></i></a>
							<a href="#edit-' . $entry['id'] . '" rel="tooltip" title="Изменить обновление" onclick="edit(' . $entry['id'] . ')"><i class="icon icon-pencil"></i></a>
							<a href="?delete=' . $entry['id'] . '" rel="tooltip" onclick="return window.confirm(\'Вы действительно хотите удалить это обновление?\')" title="Удалить обновление"><i class="icon icon-trash"></i></a>
						</td>
					</tr>';
	}

	$packages = $mysqli->query("SELECT * FROM `packages`");

	$select = null;
	$types = null;
	$items = 1;

	while ($options = $packages->fetch_array())
	{
		$select .= '<option value="' . $options['id'] . '">' . $options['name'] . '</option>';

		$types .= '<div class="input-group" id="item-' . $items . '">';
		$types .= '<input class="form-control" type="text" name="package[]" value="' . $options['name'] . '" readonly>';
		$types .= '<a href="#" onclick="remove(' . $items . ')" class="input-group-text"><i class="icon icon-trash"></i></a>';
		$types .= '</div>';

		$items++;
	}

	$packages->close();
	$updates->close();

	$content .= '
				</tbody>
			</table>

			<a href="#add" class="btn btn-info" onclick="edit(0)"><i class="icon icon-add"></i> Добавить</a>

			<div class="modal fade" id="pack">
				<div class="modal-dialog modal-dialog-centered">
					<form class="modal-content" id="form" action="#" method="post">
						<div class="modal-header">
							<h4 class="modal-title" id="caption">Управление обновлением</h4>
							<button class="close" type="button" data-dismiss="modal">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<div class="row">
								<label class="col-sm-4 col-form-label">Пакет:</label>
								<div class="col-sm">
									<select class="form-select form-control" name="package_id">
										' . $select . '
									</select>
								</div>
							</div>
							<div class="row">
								<label class="col-sm-4 col-form-label">Версия обновления:</label>
								<div class="col-sm">
									<input class="form-control" type="text" name="target_version" placeholder="ex. 1.0.1" required/>
								</div>
							</div>
							<div class="row">
								<label class="col-sm-4 col-form-label">Исходная версия:</label>
								<div class="col-sm">
									<input class="form-control" type="text" name="source_version" placeholder="ex. 1.0.0" required/>
								</div>
							</div>
							<div class="row">
								<label class="col-sm-4 col-form-label">Описание:</label>
								<div class="col-sm">
									<textarea class="form-control" rows="4" name="description" placeholder="..."></textarea>
								</div>
							</div>
							<div class="row">
								<label class="col-sm-4 col-form-label">Ссылка:</label>
								<div class="col-sm">
									<input class="form-control" type="text" name="url" placeholder="http://..." required/>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button class="btn btn-success" type="submit" name="add">Сохранить</button>
							<button class="btn btn-light" type="button" data-dismiss="modal">Отмена</button>
						</div>
					</form>
				</div>
			</div>
			
			<div class="modal fade" id="settings">
				<div class="modal-dialog modal-dialog-centered">
					<form class="modal-content" action="?settings" method="post">
						<div class="modal-body">
							<ul class="nav nav-tabs">
								<li><a class="nav-link active" data-toggle="tab" href="#items">Пакеты</a></li>
								<li><a class="nav-link" data-toggle="tab" href="#auth">Авторизация</a></li>
							</ul>
							<div class="tab-content">
								<div class="tab-pane fade show active" id="items">
									' . $types . '
									<button class="btn btn-sm btn-primary" type="button" onclick="add()">Добавить ещё пакет</button>
									<input name="count" type="hidden" value="' . $items . '">
								</div>
								<div class="tab-pane fade" id="auth">
									<input class="form-control" type="text" name="username" placeholder="Имя пользователя">
									<input class="form-control" type="password" name="password" placeholder="Новый пароль">
									<input class="form-control" type="password" name="confirm" placeholder="Подверждение">
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button class="btn btn-danger" type="submit">Применить настройки</button>
							<button class="btn btn-light" type="button" onclick="location.reload(true)">Сбросить</button>
						</div>
					</form>
				</div>
			</div>';
}
else
{
	$navigation = '<a class="nav-link" href="https://aspia.org" target="_blank">aspia.org</a>';

	$error = null;
	
	$form = '<form class="row" method="post">
				<div class="col-auto">
					<input class="form-control" type="text" name="login" maxlength="15" placeholder="Логин">
				</div>
				<div class="col-auto">
					<input class="form-control" type="password" name="password" maxlength="15" placeholder="Пароль">
				</div>
				<div class="col-auto">
					<button class="btn btn-dark" type="submit">Авторизироваться</button>
				</div>
			</form>';

	if (!empty($_POST))
	{
		if (empty($_POST['login']) and empty($_POST['password']))
		{
			$error = '<div class="alert alert-danger"><b>Ошибка!</b> Вы не ввели логин/пароль</div>';
		}
		else
		{
			if ($_POST['login'] == Config::$admin_user and $_POST['password'] == Config::$admin_pass)
			{
				$_SESSION['admin'] = true;
				header("location: " . $_SERVER['REQUEST_URI']);
			}
			else
			{
				$error = '<div class="alert alert-danger"><b>Ошибка!</b> Проверьте правильность вводимых данных</div>';
			}
		}
	}

	$content = $error . $form;
}

Display:

echo '<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<title>Центр обновлений · Aspia</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="theme/style.min.css" rel="stylesheet">
		<link href="theme/favicon.ico" rel="shortcut icon">
	</head>

	<body class="container">
		<nav class="navbar navbar-expand navbar-dark rounded shadow user-select-none">
			<a class="navbar-brand" href=".">Центр обновлений</a>
			<div class="collapse navbar-collapse">
				<div class="navbar-nav">
					' . $navigation . '
				</div>
			</div>
		</nav>
		<main>
			' . $content . '
		</main>
	</body>

	<script src="theme/bootstrap.min.js"></script>
	<script src="theme/script.js"></script>
</html>';

?>
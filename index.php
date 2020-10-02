<?php

//
// Aspia Project
// Copyright (C) 2018 Dmitry Chapyshev <dmitry@aspia.ru>
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

		$navigation = '<a href="javascript:window.location.reload(true)">обновить</a>';
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
	
	$navigation = '<a href="#settings" data-toggle="modal">настройки</a></li> <li><a href="?logout">выйти</a>';

	$content = '<table class="table table-bordered table-striped">
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
		
		$content .= '<tr id="package-' . $entry['id'] . '">
			<td>
				<span id="target_version" class="label tag' . $entry['package_id'] % 8 . '">' . $entry['target_version'] . '</span>
				<span id="package_id" class="label tag' . $entry['package_id'] % 8 . '">' . ($package_name <> null ? $package_name : 'deleted!') . '</span>
			</td>
			<td><span id="source_version" class="label">' . $entry['source_version'] . '</span></td>
			<td><span id="description">' . $entry['description'] . '</span></td>
			<td>
				<a href="' . $entry['url'] . '" target="_blank" id="url" rel="tooltip" title="Скачать файл обновления"><i class="icon-download-alt"></i></a>
				<a href="#edit-' . $entry['id'] . '" rel="tooltip" title="Изменить обновление" onclick="edit(' . $entry['id'] . ')"><i class="icon-pencil"></i></a>
				<a href="?delete=' . $entry['id'] . '" rel="tooltip" onclick="return window.confirm(\'Вы действительно хотите удалить это обновление?\')" title="Удалить обновление"><i class="icon-trash"></i></a>
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

		$types .= '<div class="input-append" id="item-' . $items . '">';
		$types .= '<input name="package[]" type="text" class="input-medium" value="' . $options['name'] . '" readonly>';
		$types .= '<a href="#" onclick="remove(' . $items . ')" class="add-on"><i class="icon-trash"></i></a></div>';
		
		$items++;
	}

	$packages->close();
	$updates->close();

	$content .= '</tbody>
	</table>

	<a href="#add" class="btn btn-info" onclick="edit(0)"><i class="icon-white icon-plus"></i> Добавить</a>

	<div class="modal fade" id="pack">
		<div class="modal-header">
			<a class="close" data-dismiss="modal">×</a>
			<h3 id="caption">Управление обновлением</h3>
		</div>
			<div class="modal-body">
			<form class="form-horizontal" id="form" action="?add" method="post">
				<fieldset>
					<div class="control-group">
						<label class="control-label">Пакет:</label>
						<div class="controls btn-group" data-toggle="buttons-radio">
							<select name="package_id" required>
							' . $select . '
							</select>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Версия обновления:</label>
						<div class="controls">
							<input name="target_version" type="text" class="input-large" placeholder="ex. 1.0.1" required/>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Исходная версия:</label>
						<div class="controls">
							<input name="source_version" type="text" class="input-large" placeholder="ex. 1.0.0" required/>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Описание:</label>
						<div class="controls">
							<textarea name="description" rows="4" placeholder="..."></textarea>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label">Ссылка:</label>
						<div class="controls">
							<input name="url" type="text" class="input-large" placeholder="http://..." required/>
						</div>
					</div>
				</fieldset>
			</div>
			
			<div class="modal-footer">
				<button type="submit" class="btn btn-success" name="add">Сохранить</button>
				<a href="#" class="btn" data-dismiss="modal">Отмена</a>
			</div>
			</form>
		</div>
	</div>

	<div class="modal fade" id="settings">
		<div class="modal-body">
			<form class="form-horizontal" id="form" action="?settings" method="post">
			<div class="tabbable">
				<ul class="nav nav-tabs">
					<li class="active"><a href="#items" data-toggle="tab">Пакеты</a></li>
					<li><a href="#auth" data-toggle="tab">Авторизация</a></li>
				</ul>
				<div class="tab-content">
					<div class="tab-pane active" id="items">
						' . $types . '
						<p>
							<a href="#" onclick="add()" class="btn btn-primary btn-mini">Добавить пакет</a>
							<input name="count" type="hidden" value="' . $items . '">
						</p>
					</div>
					<div class="tab-pane" id="auth">
						<div class="control-group">
							<input name="username" type="text" class="input-large" placeholder="Имя пользователя">
						</div>
						<div class="control-group">
							<input name="password" type="password" class="input-large" placeholder="Новый пароль">
						</div>
						<div class="control-group">
							<input name="confirm" type="password" class="input-large" placeholder="Подверждение">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button type="submit" class="btn btn-danger">Применить настройки</button>
			<a href="#" onclick="location.reload(true)" class="btn" data-dismiss="modal">Сбросить</a>
		</div>
		</form>
	</div>';
}
else
{
	$navigation = '<a href="https://aspia.org" target="_blank">aspia.org</a>';
	$error = null;

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

	$content = '<div class="row">
					<div class="span12">' . $error . '
						<form class="form-inline" method="post">
							<div class="input">
								<input type="text" class="input-medium" name="login" maxlength="15" placeholder="Логин" >
								<input type="password" class="input-medium" name="password" maxlength="15" placeholder="Пароль">
							</div>
							<button type="submit" class="btn btn-info">Авторизироваться</button>
						</form>
					</div>
				</div>';
}

Display:

echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Центр обновлений &sdot; Aspia</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="theme/style.min.css" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<div class="navbar">
				<div class="navbar-inner">
					<div class="container">
						<a class="brand" href=".">Центр обновлений</a>
						<div class="nav-collapse">
							<ul class="nav pull-right">
								<li>' . $navigation . '</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			' . $content . '
			<script src="theme/jquery.min.js"></script>
			<script src="theme/script.min.js"></script>
		</body>
	</html>';
?>
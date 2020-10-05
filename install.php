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

$sql = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\"; SET AUTOCOMMIT = 0; START TRANSACTION;

CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `packages` (`id`, `name`) VALUES
(1, 'console'),
(2, 'host');

CREATE TABLE `updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `source_version` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `target_version` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

$status = '<div class="alert alert-info">Для установки центра обновлений введите данные для <b>подключения к базе данных</b> и придумайте пару <b>логин-пароль администратора</b>!</div>';
$control = null;

if ($_POST)
{
	$mysqli = new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], $_POST['db_name']);

	if ($mysqli->connect_error)
	{
		switch ($mysqli->connect_errno)
		{
			case 2002:
				$description = 'Отсутствует подключение к MySQL серверу';
				break;
			case 1045:
				$description = 'Неверный логин или пароль MySQL';
				break;
			case 1044: case 1049:
				$description = 'База данных с таким именем не существует';
				break;
			default:
				$description =  $mysqli->connect_error;
		}

		$control = 'is-invalid';
		$status = '<div class="alert alert-danger"><b>Ошибка!</b> ' . $description . ', проверьте вводимые данные</div>';
	}
	else
	{
		$file = file_get_contents('config.php');

		foreach ($_POST as $replace => $value)
		$file = str_replace($replace . " = '", $replace . " = '" . $value, $file);

		$file = file_put_contents('config.php', $file);

		$mysqli->set_charset('utf8');
		$mysqli->multi_query($sql);
		$mysqli->close();

		if (filter_var($_POST['install'], FILTER_VALIDATE_BOOLEAN) == true) array_map('unlink', glob("install*.php"));
		else rename("install.php", "install.later.php");

		header("location: " . dirname($_SERVER['REQUEST_URI']));
	}
}

echo '<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<title>Центр обновлений · Install</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="theme/bootstrap.css" rel="stylesheet">
		<link href="theme/style.css" rel="stylesheet">
		<link href="theme/favicon.ico" rel="shortcut icon">
	</head>

	<body class="container">
		<nav class="navbar navbar-expand navbar-dark rounded shadow user-select-none">
			<a class="navbar-brand" href=".">Центр обновлений</a>
			<div class="collapse navbar-collapse">
				<div class="navbar-nav ml-auto">
					<a class="nav-link" href="https://aspia.org" target="_blank">aspia.org</a>
				</div>
			</div>
		</nav>
		<main>
			' . $status . '
			<form action="' . ltrim($_SERVER['REQUEST_URI'], '/') . '" method="post">
				<div class="row">
					<div class="col-md-4">
						<h4>Подключение к БД</h4>
						<div class="row">
							<label class="col-sm-3 col-form-label">Сервер:</label>
							<div class="col-sm">
								<input class="form-control ' . $control . '" type="text" name="db_host" value="' . (isset($_POST['db_host']) ? $_POST['db_host'] : null) . '" required/>
							</div>
						</div>
						<div class="row">
							<label class="col-sm-3 col-form-label">Пользователь:</label>
							<div class="col-sm">
								<input class="form-control ' . $control . '" type="text" name="db_user" value="' . (isset($_POST['db_user']) ? $_POST['db_user'] : null) . '" required/>
							</div>
						</div>
						<div class="row">
							<label class="col-sm-3 col-form-label">Пароль:</label>
							<div class="col-sm">
								<input class="form-control ' . $control . '" type="text" name="db_pass" value="' . (isset($_POST['db_pass']) ? $_POST['db_pass'] : null) . '" required/>
							</div>
						</div>
						<div class="row">
							<label class="col-sm-3 col-form-label">База данных:</label>
							<div class="col-sm">
								<input class="form-control ' . $control . '" type="text" name="db_name" value="' . (isset($_POST['db_name']) ? $_POST['db_name'] : null) . '" required/>
							</div>
						</div>
					</div>
					<div class="col-md-4 offset-md-1 mb-3">
						<h4>Настройки администратора</h4>
						<div class="row">
							<label class="col-sm-2 col-form-label">Логин:</label>
							<div class="col-sm">
								<input class="form-control" type="text" name="admin_user" value="' . (isset($_POST['admin_user']) ? $_POST['admin_user'] : null) . '" required />
							</div>
						</div>
						<div class="row">
							<label class="col-sm-2 col-form-label">Пароль:</label>
							<div class="col-sm">
								<input class="form-control" type="text" name="admin_pass" value="' . (isset($_POST['admin_pass']) ? $_POST['admin_pass'] : null) . '" required/>
							</div>
						</div>
						<h4 class="mt-4">Дополнительно</h4>
						<div class="form-check form-switch">
							<input class="form-check-input" type="checkbox" name="install" value="1" id="switch" ' . (isset($_POST['install']) ? 'checked' : null) . '>
							<label class="form-check-label" for="switch">Удалить установочный файл после завершения</label>
						</div>
					</div>
				</div>

				<button class="btn btn-dark" type="submit" id="apply">Установить</button>
				<button class="btn btn-light" type="reset">Отчистить</button>
			</form>
		</main>
	</body>

	<script src="theme/bootstrap.min.js"></script>
</html>';

?>
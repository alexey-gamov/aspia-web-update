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

		$control = 'error';
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

		if (filter_var($_POST['install'], FILTER_VALIDATE_BOOLEAN) == true) unlink('install.php');
		else rename("install.php", "install.later.php");
	
		header("location: " . dirname($_SERVER['SCRIPT_NAME']));
	}
}

echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Центр обновлений &sdot; Install</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="theme/style.min.css" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<div class="navbar">
				<div class="navbar-inner">
					<div class="container">
						<a class="brand" href=".">Центр обновлений</a>
					</div>
				</div>
			</div>

			<form id="edit-settings" class="form-horizontal" action="install.php" method="post">
			<fieldset>
				' . $status . '
				<div class="row">
					<div class="span6 ">
						<div class="control-group ' . $control . '">
							<label class="control-label">Сервер:</label>
							<div class="controls">
								<input name="db_host" type="text" class="input-xlarge" value="' . (isset($_POST['db_host']) ? $_POST['db_host'] : null) . '" required/>
							</div>
						</div>

						<div class="control-group ' . $control . '">
							<label class="control-label">Пользователь:</label>
							<div class="controls">
								<input name="db_user" type="text" class="input-xlarge" value="' . (isset($_POST['db_user']) ? $_POST['db_user'] : null) . '" required/>
							</div>
						</div>

						<div class="control-group ' . $control . '">
							<label class="control-label">Пароль:</label>
							<div class="controls">
								<input name="db_pass" type="text" class="input-xlarge" value="' . (isset($_POST['db_pass']) ? $_POST['db_pass'] : null) . '" required/>
							</div>
						</div>

						<div class="control-group ' . $control . '">
							<label class="control-label">Базы данных:</label>
							<div class="controls">
								<input name="db_name" type="text" class="input-xlarge" value="' . (isset($_POST['db_name']) ? $_POST['db_name'] : null) . '" required/>
							</div>
						</div>
					</div>

					<div class="span6">
						<div class="control-group info">
							<label class="control-label">Логин:</label>
							<div class="controls">
								<input name="admin_user" type="text" class="input-xlarge" value="' . (isset($_POST['admin_user']) ? $_POST['admin_user'] : null) . '" required />
							</div>
						</div>

						<div class="control-group">
							<label class="control-label">Пароль:</label>
							<div class="controls">
								<input name="admin_pass" type="text" class="input-xlarge" value="' . (isset($_POST['admin_pass']) ? $_POST['admin_pass'] : null) . '" required/>
							</div>
						</div>
						
						<div class="control-group" style="margin-top: 30px">
							<label class="control-label">Файл:</label>
							<div class="controls">
								<label class="radio">
									<input type="radio" name="install" value="1" checked>
									<span class="label tag3">Удалить установочный файл</span>
								</label>
								<label class="radio">
									<input type="radio" name="install" value="0">
									<span class="label tag6">Оставить, но переименовать</span>
								</label>
							</div>
						</div>		

					</div>
				</div>

				<div class="form-actions">
					<button type="submit" id="apply" class="btn btn-inverse">Установить</button>
					<button type="reset" class="btn">Отмена</button>
				</div>
			<fieldset>
			</form>
		</div>

		<script src="theme/jquery.min.js"></script>
		<script src="theme/script.min.js"></script>
	</body>
</html>';

?>
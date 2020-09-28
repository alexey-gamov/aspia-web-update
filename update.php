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

function getPackageId($mysqli, $package)
{
    $sql = "SELECT id FROM packages WHERE name='$package'";

    if (!$result = $mysqli->query($sql))
    {
        die('Failed to execute database query: ' . $mysqli->error);
    }

    if ($result->num_rows == 0)
    {
        die('Package not found');
    }

    $row = $result->fetch_array();
    $result->close();

    return $row['id'];
}

function getUpdates($mysqli, $package_id, $version)
{
    $sql = "SELECT target_version, description, url
            FROM updates
            WHERE package_id = '$package_id' AND source_version = '$version'";

    if (!$result = $mysqli->query($sql))
    {
        die('Failed to execute database query: ' . $mysqli->error);
    }
	
	header('Content-Type: application/xml');

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<update>';

    if ($result->num_rows != 0)
    {
        // There is an update available.
        $row = $result->fetch_array();

        echo '<version>' . $row['target_version'] . '</version>';
        echo '<description>' . $row['description'] . '</description>';
        echo '<url>' . $row['url'] . '</url>';
    }

    echo '</update>';

    $result->close();
}

// Run the update check.
parse_str($_SERVER['QUERY_STRING'], $query);

if (empty($query['package']) or empty($query['version']))
{
	die('Invalid request received');
}
else
{
	// Connect to the database.
	$mysqli = new mysqli(Config::$db_host, Config::$db_user, Config::$db_pass, Config::$db_name);
	$mysqli->set_charset('utf8');

	if (mysqli_connect_errno()) die('Could not connect to database: ' . $mysqli->connect_error);

	// Get the package name and version from the query.
	$package = $mysqli->real_escape_string($query['package']);
	$version = $mysqli->real_escape_string($query['version']);

	getUpdates($mysqli, getPackageId($mysqli, $package), $version);

	$mysqli->close();
}

?>
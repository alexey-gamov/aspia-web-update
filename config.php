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

class Config
{
    // Update server version
    public static $version = '1.0.0';

    // Data to connect to MySQL server
    public static $db_host = '';
    public static $db_user = '';
    public static $db_password = '';
    public static $db_name = '';

    // Admin auth credits
    public static $admin_user = '';
    public static $admin_pass = '';
}

?>
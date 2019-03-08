<?php
/**
 * db.inc.php
 * Copyright (C) 2016 - 2019 Chris Wiese (http://basisweb.de)
 *
 * PHP version 7 
 *
 * @copyright  	Christian Wiese (basisweb) 2016 - 2019
 * @author     	Christian Wiese <http://www.basisweb.de>
 * 
 * @file       	db.inc.php
 * @lastchange 	08.03.19
 * @encoding   	UTF-8
 *
 */

$DB = new mysqli('localhost', 'root', 'hoemann_22', 'basisweb_history');
$DB->set_charset("utf8");
 
?>
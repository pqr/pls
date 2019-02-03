<?php

use Light\ApplicationAbstract;
use Light\Db;
use Light\Table;
use Light\Field\FieldAbstract as F;
/** @noinspection ForgottenDebugOutputInspection */
phpinfo();

$table = new Table();
$table->addFields(
    F::createString('Variable_name'),
    F::createString('Value')
);
$table->setTableData(Db::getAll("SHOW VARIABLES"));
print $table->getHtmlTable();

ApplicationAbstract::terminate();

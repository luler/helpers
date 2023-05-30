<?php
require_once './vendor/autoload.php';

var_dump(\Luler\Helpers\CommonHelper::buildBatchUpdateSql('lz_user', [
    ['id' => 1, 'name' => 'test1'],
    ['id' => 2, 'name' => 'test2'],
]));
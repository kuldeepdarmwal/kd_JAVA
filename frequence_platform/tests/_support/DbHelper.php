<?php
namespace Codeception\Module;
use Codeception\Module\Db;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class DbHelper extends Db
{
	protected function removeInserted()
    {
        $this->insertedIds = [];
    }
}

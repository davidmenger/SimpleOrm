SimpleOrm
=========

Just ripped out PHP ORM from my project based on Dibi and Nette.

Features
========

```php
$predicate = Predicates\pAnd(array( "name~LIKE"=>'%Somethink%' ))->orderBy('name');
$offset = 10;
$limit = 10;
$data = Table::getAll($predicate, $limit, $offset);

$filtered = $data->filterWithPredicate(Predicates\pAnd(array( "enabled"=>true )));

foreach ($data as $item) {
  $item->updated = new DateTime();
  $item->setCategory(CategoryTable::getAll(pA::i('category_id', 1)->setLazy(), 1)->getFirst());
  $item->update();
}

```

Example of single Entity:

```php
<?php 

namespace Foundation\User;

/**
 *
 *
 * @key account_group_id
 * @storage database
 * @table account_group
 * @cached
 */
class AccountGroup extends \Foundation\Object\DBStored
{

  /**
	 * @key autoincrement
	 * @var int
	 * @column(name="account_group_id", type="INT", null=false, len=10, signed=false)
	 */
	public $account_group_id;

	/**
	 * @var string
	 * @column(name="code", type="VARCHAR", null=false, len=20)
	 */
	public $code;

	/**
	 * @var string
	 * @column(name="name", type="VARCHAR", null=false, len=90)
	 */
	public $name;

	/**
	 * @var bool
	 * @column(name="isSuperUser", type="TINYINT", null=false, len=1)
	 */
	public $isSuperUser;


	/**
	 *  @return \Foundation\Set\DbSet
	 */
	public function getAccountSet()
	{
		return new \Foundation\Set\DbSet("account_relations", $this, "account_group_id", new \Nette\Reflection\ClassType("\\Foundation\\User\\Account"), "account_id");
	}


	public function addAccount(\Foundation\User\Account $object)
	{
		$this->getAccountSet()->add($object);
	}


	public function removeAccount(\Foundation\User\Account $object)
	{
		$this->getAccountSet()->remove($object);
	}

    //#CUSTOM DECLARATION BEGIN
    //#CUSTOM DECLARATION END
}
```

Requirements
============

- Dibi
- Nette


Todo
====


- Overall cleanup
- Remove dependencies
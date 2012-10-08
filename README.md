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

Requirements
============

- Dibi
- Nette


Todo
====


- Overall cleanup
- Remove dependencies
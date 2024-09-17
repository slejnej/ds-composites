# base_repository
A base repository to simplify working with Drupal entities such as the `Node` entity. 

Comes with methods `find`, `findBy` and `findOneBy`. You can add as many repository methods as you wish in your custom repository.  
Also provides a few aggregate functionalities, namely `count`, `average`, `min`, `max`, and `sum`.


## Usage
```php
<?php

namespace \Drupal\your_module\Repository;

class WebformRepository extends BaseRepository {
    protected string $entityType = 'webform'; //The machine name of the entity you want to work with, e.g. 'webform'

}
```

## Examples
In the following examples we asssume you created a repository to work with `Node`s.

Get an instance of Node or null by its ID
`\Drupal::service('<module>.repository.node')->find(1);`

Get an array of Nodes, or an empty array, by supplying the filters
`\Drupal::service('<module>.repository.node')->findBy(['uid' => 15, 'published' => true]);`

Get an array of Nodes, or an empty array, by supplying the filters, maximum of 10
`\Drupal::service('<module>.repository.node')->findBy(['uid' => 15, 'published' => true], [], 10);`

Get an array of Nodes, or an empty array, by supplying the filters, ordered by creation date
`\Drupal::service('<module>.repository.node')->findBy(['uid' => 15, 'published' => true], ['created_at' => 'DESC']);`

### Search API
The repository also works with the Search API. The public methods are the same as above, however it uses the Search API instead of the database.  
This allows for slightly different behaviours, such as the ability to search for stripped entity titles.   
This feature requires `remora_search`, however the feature as a whole is optional and therefore the repository will work without it.

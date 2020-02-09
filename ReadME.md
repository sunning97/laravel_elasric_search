### Elastic Search For Yii 2

***This lib compatible with Elastic >= 7.0.1***

For any problem please contact me: giangnguyen.neko.130@gmail.com


### Usage
Just create a new class extend from Kuroneko\Yii2ElasticSearch\Abstracts\BaseElasticSearchAbstract

**Recommendation**: Class should implement interface for strict code

```php
use Kuroneko\Yii2ElasticSearch\Abstracts\BaseElasticSearchAbstract;

class CatElasticSearch extends BaseElasticSearchAbstract
{

    /**
     * CatElasticSearch constructor.
     */
    public function __construct()
    {
        $timeout = '1s';
        $server = env('ELASTIC_SERVER');
        parent::__construct($server, $timeout);
    }

    /**
     * @inheritDoc
     */
    public function index(): string
    {
        return 'cat';
    }

    /**
     * @return array
     */
    public function settings()
    {
        // define your setting here
        return [
            'analysis' => [
                'analyzer' => [
                    'vietnamese_standard' => [
                        'tokenizer' => 'icu_tokenizer',
                        'filter' => [
                            'icu_folding',
                            'icu_normalizer',
                            'icu_collation'
                        ]
                    ]
                ]
            ],
            ...
        ];
    }

    /**
     * @inheritDoc
     */
    public function map(): array
    {
        // Define your mapping here
        return [
            'id' => [
                'type' => 'long',
                "fields" => [
                    'keyword' => [
                        'type' => 'keyword',
                        "ignore_above" => 256
                    ]
                ]
            ],
            ...
        ];
    }
}
```

### insert document
***Note** The data must compatible with your mapping define in class
```php
$data = [
    'id' => 1,
    'name' => 'Tom',
    ...
];
$elastic = new CatElasticSearch();
$elastic->insert($data);
```

### delete document
```php
$elastic = new CatElasticSearch();
$elastic->find()
    ->where(['id', '=', 1])
    // another query
    ->delete();
```

### Find one
```php
$elastic = new CatElasticSearch();
$cat = $elastic->find()
    ->where(['id', '=', 1])
    ->andWhere(['name','=','Tom'])
    ->one();
```

### Find by Id
```php
$elastic = new CatElasticSearch();
$cat = $elastic->findOne(1);
```

### Query explain

Start with find() function first and then call where | andWhere

```php
$elastic = new CatElasticSearch();
$elastic->find()
// where =
->where(['id','=',1])
// where !=
->where(['id','<>',1])
// where In
->where(['id','in',[1,2,3,4,5]])
// where not in
->where(['id','not_in',[6,7,8,9]])
// where between
->where(['id','between',[5,10]])
// where like
->where(['name','like','tom'])
// size
->limit(10)
//from
->offset(1)
//order by
->orderBy(['created_at' => 'desc'])
// search
->search(true) //if provide true => will reset the query after execute | if false query will be continue use for next call
// or count
->count() //if provide true => will reset the query after execute | if false query will be continue use for next call
// or one
->one() //if provide true => will reset the query after execute | if false query will be continue use for next call
//or delete
->delete(); //if provide true => will reset the query after execute | if false query will be continue use for next call
```
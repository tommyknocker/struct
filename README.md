## PHP Structure library

Easy usage. Just extends Struct class, define template in your class and check incoming data based on defined template. Supports soft checking with type casting.

## Examples

```php
<?php
class Hit extends Tommyknocker\Struct\Struct {
    /**
     * Strict mode flag
     * @var bool
     */
    protected $strict = false;

    /**
     * Track structure template
     * @var array
     */
    protected $template = [
        'date' => 'string',
        'type' => 'int',
        'ip' => 'string',
        'uuid' => 'string',
        'referer' => 'string',
    ];
    
}

$hit = new Hit([
    'date' => "2018-05-05",
    "type" => "1",
    "ip" => "127.0.0.1",
    "referer" => "http://google.com"
]);

echo $hit->date; // "2018-05-05"
echo $hit->type; // 1 

$hit = new Hit([
    'date' => "2018-05-05",
    "type" => "1",
    "ip" => "127.0.0.1",
    "referer" => null
]);

// Exception, cause referer cannot be null in defined template


```

## Plans

* Support other classes in template. ex "time" => Time::class
* Test coverage
* More functionality

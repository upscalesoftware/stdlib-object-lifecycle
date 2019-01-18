Object Lifecycle Monitoring
===========================

This library tracks existence of objects throughout their lifecycle until eventual destruction.
It can accurately detect memory leaks in long-running CLI scripts or event-driven web-servers, such as [Swoole](https://www.swoole.co.uk/) or [ReactPHP](https://reactphp.org/).

The monitoring can be easily activated for applications with centralized object instantiation, i.e. using a DI container. 

**Features:**
- Watch any object w/o modifying its source code
- Detect objects that are still alive
- Detect objects that have been destroyed
- Debug info on alive/destroyed objects: class, hash, trace
- Garbage collection of circular references 

## Installation

The library is to be installed via [Composer](https://getcomposer.org/) as a dependency:
```bash
composer require upscale/stdlib-object-lifecycle
```

## Usage

Detect destruction of objects:
```php
$obj1 = new \stdClass();
$obj2 = new \stdClass();
$obj3 = new \stdClass();

// Circular references subject to garbage collection
$obj1->ref = $obj2;
$obj2->ref = $obj1;

$watcher = new \Upscale\Stdlib\Object\Lifecycle\Watcher();
$watcher->watch($obj1);
$watcher->watch([$obj2, $obj3]);

unset($obj1);

// Outputs 3 because of circular references
echo count($watcher->detectAliveObjects());

unset($obj2);

// Outputs 3 because of pending garbage collection 
echo count($watcher->detectAliveObjects(false));

// Outputs 1 after forced garbage collection 
echo count($watcher->detectAliveObjects());

unset($obj3);

// Outputs 0
echo count($watcher->detectAliveObjects());
```

Method `detectAliveObjects()` returns the following debug information:
```php
array(
  array(
    'type' => 'stdClass',
    'hash' => '00000000524c32e1000000002cee0034',
    'trace' => '#0 demo.php(26): Upscale\\Stdlib\\Object\\Lifecycle\\Watcher->watch(Object(stdClass))
#1 demo.php(10): Example->runTest()
#2 demo.php(53): Example->test()
#3 {main}',
  ),
  ...
)
```

## Contributing

Pull Requests with fixes and improvements are welcome!

## License

Licensed under the [Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0).

## RconPHP

CS:GO Rcon client made in PHP.

### Usage

```php
<?php

use Reflex\Rcon\Rcon;

$rcon = new Rcon(':ip_address', :port, ':rcon_password');
$rcon->connect();

// Set the socket timeout if you want to, defaults to 2 seconds.
$rcon->setTimeout(:seconds);

// Execute a rcon command
$rcon->exec('say PHP!');
```

### License

The code in this repository is subject to the MIT license which can be found in the `LICENSE` file in this repository.

### Credits

- [David Cole](mailto:david@team-reflex.com)

SMB-Bundle
===

PHP wrapper for `smbclient` and [`libsmbclient-php`](https://github.com/eduardok/libsmbclient-php)

- Reuses a single `smbclient` instance for multiple requests
- Doesn't leak the password to the process list
- Simple 1-on-1 mapping of SMB commands
- A stream-based api to remove the need for temporary files
- Support for using libsmbclient directly trough [`libsmbclient-php`](https://github.com/eduardok/libsmbclient-php)

## Installation

1. **Add as a dependency in your composer file**

    ```json
    "require": {
        "isklv/smb-bundle":"dev-master"
    }
    ```

2. **Add to your Kernel**

    ```php
    // application/ApplicationKernel.php
    public function registerBundles()
    {
        $bundles = array(
            new SMBBundle\SMBBundle()
        );
     }
    ```
3. **(optional) Adjust configurations**

    ```yml
    # application/config/config.yml
    smb:
        host: localhost
        user: test
        password: test
    ```

Examples
----

### Upload a file ###

```php
<?php

$fileToUpload = __FILE__;

$server = $this->get('smb.server');
$share = $server->getShare('test');
$share->put($fileToUpload, 'example.txt');
```

### Download a file ###

```php
<?php
$target = __DIR__ . '/target.txt';

$server = $this->get('smb.server');
$share = $server->getShare('test');
$share->get('example.txt', $target);
```

### List shares on the remote server ###

```php
<?php

$server = $this->get('smb.server');
$shares = $server->listShares();

foreach ($shares as $share) {
	echo $share->getName() . "\n";
}
```

### List the content of a folder ###

```php
<?php

$server = $this->get('smb.server');
$share = $server->getShare('test');
$content = $share->dir('test');

foreach ($content as $info) {
	echo $name->getName() . "\n";
	echo "\tsize :" . $info->getSize() . "\n";
}
```

### Using read streams

```php
<?php

$server = $this->get('smb.server');
$share = $server->getShare('test');

$fh = $share->read('test.txt');
echo fread($fh, 4086);
fclose($fh);
```

### Using write streams

```php
<?php

$server = $this->get('smb.server');
$share = $server->getShare('test');

$fh = $share->write('test.txt');
fwrite($fh, 'bar');
fclose($fh);
```
### Using other configurations

```php
<?php

$server = $this->get('smb.server');
$server->setAuthParams('localhost', 'user0', 'user0');
$share = $server->getShare('test');

```

### Using libsmbclient-php ###

Install [libsmbclient-php](https://github.com/eduardok/libsmbclient-php)

```php
<?php

$fileToUpload = __FILE__;

if (Server::NativeAvailable()) {
    $server = new NativeServer('localhost', 'test', 'test');
} else {
    echo 'libsmbclient-php not available, falling back to wrapping smbclient';
    $server = $server = $this->get('smb.server');;
}
$share = $server->getShare('test');
$share->put($fileToUpload, 'example.txt');
```

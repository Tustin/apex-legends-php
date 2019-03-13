# Apex-Legends-PHP Wrapper

A PHP wrapper for the Apex Legends API.

## Getting started

Pull in project with Composer:
`composer require tustin/apex-legends-php`

Then, create a script to test with and set it up like so:

```php
<?php

// Pull in library
require_once 'vendor/autoload.php';

$client = new ApexLegends\Client();
```

Then you can login with your Origin email and password:

```php
$client->login('my@email.com', 'p@55w0rd');
```

## Getting stats

### PC

You can get a user's PC stats by either looking up their Origin username or by using their Origin user id (if you have it saved)

To get a user id from their username, do this (I will add a method to just return the first user it finds in the future):

```php
// This users method will return an array of users that were returned from the search query API.
// You can enumerate over multiple users this way if you'd like.
$user = $client->users('SpikedPuddinPops')[0];
```

With this `$user` object, you can now call the `stats` method to obtain their Apex Legends stats:

```php
// Pass the platform as a string. In this case, use PC for PC stats.
var_dump($user->stats('PC'));
```

### PS4

PS4 stats are a bit trickier. You need the user's PSN account id which you can only obtain from the PSN API. There might be a way to get this account id from Origin, or maybe there's some way you can use the Origin presence id to get stats; I'm not 100% sure yet.

If you don't have the PSN account id for the user, you will need to look it up. The easiest way to do this is by using my [psn-php library](https://github.com/Tustin/psn-php) to call the PSN API. If you follow the documentation for that library, you can get a user's account id.

Once you have the account id, you can do something similiar to the PC example by passing the PSN account id to the `user` method:

```php
$user = $client->user(4421126145254737307);
```

And like the PC example, you can grab the stats for PS4 like so:


```php
// Pass the platform as a string. In this case, use PS4 for PS4 stats.
var_dump($user->stats('PS4'));
```

### Xbox

To get XBOX statistics, you need an XUID, you can get it using [OpenXBL PHP](https://github.com/OpenXBL/OpenXBL-PHP), it’s not difficult, then everything is just like with PS4

```php
$user = $client->user(2535413617198328);
```
And like the PS4 example, you can grab the stats for XBOX like so:



```php
// Pass the platform as a string. In this case, use X1 for XBOX stats.
var_dump($user->stats('X1'));
```
